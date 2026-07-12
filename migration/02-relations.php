<?php
/**
 * Pasul 2 migrare produse: remapează upsell/crosssell (ID-uri vechi -> noi).
 * Rulare: wp eval-file wp-content/source-dump/_run.php
 * Necesită harta creată de 01-products.php (source-dump/_product-id-map.json).
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$map_file = ABSPATH . 'wp-content/source-dump/_product-id-map.json';
if ( ! file_exists( $map_file ) ) { WP_CLI::error( 'Lipsește _product-id-map.json (rulează 01-products.php întâi).' ); }
$id_map = json_decode( file_get_contents( $map_file ), true );
if ( ! $id_map ) { WP_CLI::error( 'Harta de ID-uri e goală/invalidă.' ); }

$remap = function ( $csv ) use ( $id_map ) {
	$out = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) $csv ) ) ) as $old ) {
		if ( isset( $id_map[ $old ] ) ) { $out[] = (int) $id_map[ $old ]; }
	}
	return array_values( array_unique( $out ) );
};

$products = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
$up = 0; $cross = 0;
foreach ( $products as $pid ) {
	$product = wc_get_product( $pid );
	if ( ! $product ) { continue; }
	$changed = false;
	$legacy_up = get_post_meta( $pid, '_ef_legacy_upsell', true );
	if ( $legacy_up ) {
		$ids = $remap( maybe_unserialize( $legacy_up ) ? implode( ',', (array) maybe_unserialize( $legacy_up ) ) : $legacy_up );
		if ( $ids ) { $product->set_upsell_ids( $ids ); $up++; $changed = true; }
	}
	$legacy_cross = get_post_meta( $pid, '_ef_legacy_crosssell', true );
	if ( $legacy_cross ) {
		$ids = $remap( maybe_unserialize( $legacy_cross ) ? implode( ',', (array) maybe_unserialize( $legacy_cross ) ) : $legacy_cross );
		if ( $ids ) { $product->set_cross_sell_ids( $ids ); $cross++; $changed = true; }
	}
	if ( $changed ) { $product->save(); }
}

WP_CLI::log( "Upsell setate pe $up produse | Crosssell pe $cross produse." );
WP_CLI::success( 'Pasul 2 (relații) finalizat.' );
