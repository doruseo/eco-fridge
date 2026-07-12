<?php
/**
 * Pasul 4 migrare: media (attachments) legacy -> site nou + atașare la produse.
 * Rulare: wp eval-file wp-content/source-dump/_run.php
 * PRECONDIȚIE: fișierele fizice deja copiate în wp-content/uploads (docker cp din media-sync).
 *
 * - Recreează posturile `attachment` din legacy pentru fișierele prezente pe disc.
 * - Copiază _wp_attached_file, _wp_attachment_metadata (verbatim), alt text, titlu/caption.
 * - Construiește harta old_attachment_id -> new_id (source-dump/_attachment-id-map.json).
 * - Setează pe produse imaginea principală (_ef_legacy_thumb) + galeria (_ef_legacy_gallery).
 * Idempotent prin meta _ef_legacy_id.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once ABSPATH . 'wp-admin/includes/image.php';

$LP        = '43lz2ypq8_';
$uploads   = wp_get_upload_dir();
$base_dir  = trailingslashit( $uploads['basedir'] );

$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Legacy DB: ' . mysqli_connect_error() ); }
mysqli_set_charset( $db, 'utf8mb4' );

/* ---- 1. migrare attachments pentru fișierele existente pe disc ---- */
$res = mysqli_query( $db, "SELECT ID, post_title, post_name, post_excerpt, post_content, post_mime_type, post_date, guid
                           FROM {$LP}posts WHERE post_type='attachment' ORDER BY ID" );
$att_map = array();
$created = 0; $skipped = 0; $missing = 0;

while ( $row = mysqli_fetch_assoc( $res ) ) {
	$old_id = (int) $row['ID'];

	// meta legacy
	$mr = mysqli_query( $db, "SELECT meta_key, meta_value FROM {$LP}postmeta WHERE post_id=$old_id AND meta_key IN ('_wp_attached_file','_wp_attachment_metadata','_wp_attachment_image_alt')" );
	$lmeta = array();
	while ( $m = mysqli_fetch_assoc( $mr ) ) { $lmeta[ $m['meta_key'] ] = $m['meta_value']; }

	$file = isset( $lmeta['_wp_attached_file'] ) ? $lmeta['_wp_attached_file'] : '';
	if ( '' === $file ) { $missing++; continue; }
	if ( ! file_exists( $base_dir . $file ) ) { $missing++; continue; } // ex. matomo/woo_uploads/webp excluse

	// idempotency
	$exist = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_ef_legacy_id', 'meta_value' => $old_id, 'fields' => 'ids', 'posts_per_page' => 1 ) );
	if ( $exist ) { $att_map[ $old_id ] = (int) $exist[0]; $skipped++; continue; }

	$new_id = wp_insert_attachment( array(
		'post_mime_type' => $row['post_mime_type'],
		'post_title'     => $row['post_title'],
		'post_name'      => $row['post_name'],
		'post_excerpt'   => $row['post_excerpt'],   // caption
		'post_content'   => $row['post_content'],   // description
		'post_status'    => 'inherit',
		'post_date'      => ( $row['post_date'] && '0000-00-00 00:00:00' !== $row['post_date'] ) ? $row['post_date'] : current_time( 'mysql' ),
		'guid'           => $row['guid'],
	), $base_dir . $file, 0, true );

	if ( is_wp_error( $new_id ) || ! $new_id ) { $missing++; continue; }
	$new_id = (int) $new_id;

	update_post_meta( $new_id, '_wp_attached_file', $file );
	if ( isset( $lmeta['_wp_attachment_metadata'] ) ) {
		$md = maybe_unserialize( $lmeta['_wp_attachment_metadata'] );
		if ( $md ) { update_post_meta( $new_id, '_wp_attachment_metadata', $md ); }
	}
	if ( isset( $lmeta['_wp_attachment_image_alt'] ) ) { update_post_meta( $new_id, '_wp_attachment_image_alt', $lmeta['_wp_attachment_image_alt'] ); }
	update_post_meta( $new_id, '_ef_legacy_id', $old_id );

	$att_map[ $old_id ] = $new_id;
	$created++;
}

file_put_contents( ABSPATH . 'wp-content/source-dump/_attachment-id-map.json', wp_json_encode( $att_map ) );
WP_CLI::log( "Attachments: create=$created, sarite=$skipped, lipsa/exclus=$missing, total mapate=" . count( $att_map ) );

/* ---- 2. atașare imagini la produse (featured + galerie) ---- */
$products = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
$with_thumb = 0; $with_gallery = 0;
foreach ( $products as $pid ) {
	$product = wc_get_product( $pid );
	if ( ! $product ) { continue; }
	$changed = false;

	$old_thumb = get_post_meta( $pid, '_ef_legacy_thumb', true );
	if ( $old_thumb && isset( $att_map[ $old_thumb ] ) ) {
		$product->set_image_id( $att_map[ $old_thumb ] );
		$with_thumb++; $changed = true;
	}

	$old_gallery = get_post_meta( $pid, '_ef_legacy_gallery', true );
	if ( $old_gallery ) {
		$ids = array();
		foreach ( array_filter( array_map( 'trim', explode( ',', $old_gallery ) ) ) as $oid ) {
			if ( isset( $att_map[ $oid ] ) ) { $ids[] = (int) $att_map[ $oid ]; }
		}
		if ( $ids ) { $product->set_gallery_image_ids( array_values( array_unique( $ids ) ) ); $with_gallery++; $changed = true; }
	}
	if ( $changed ) { $product->save(); }
}

WP_CLI::log( "Produse cu imagine principala: $with_thumb | cu galerie: $with_gallery" );
WP_CLI::success( 'Migrare media (pasul 4) finalizată.' );
