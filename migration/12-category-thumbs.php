<?php
/**
 * Pasul 12: setează thumbnail-uri (imagini) pe categoriile de produse care nu au,
 * folosind imaginea principală a primului produs din categorie. Necesar pentru
 * tile-urile de sub-categorii de pe homepage (Display/Storage) + paginile de
 * categorie. Editabil ulterior din Products → Categories. Idempotent (sare peste
 * cele care au deja thumbnail). Rulare: wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$terms = get_terms( array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => true,
) );
if ( is_wp_error( $terms ) ) { WP_CLI::error( $terms->get_error_message() ); }

$set = 0; $skip = 0; $none = 0;
foreach ( $terms as $t ) {
	$existing = get_term_meta( $t->term_id, 'thumbnail_id', true );
	if ( $existing ) { $skip++; continue; }

	$q = get_posts( array(
		'post_type'   => 'product',
		'numberposts' => 1,
		'fields'      => 'ids',
		'orderby'     => 'menu_order title',
		'order'       => 'ASC',
		'tax_query'   => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $t->term_id ) ),
		'meta_key'    => '_thumbnail_id',
	) );
	if ( ! $q ) { $none++; continue; }

	$thumb = get_post_thumbnail_id( $q[0] );
	if ( $thumb ) {
		update_term_meta( $t->term_id, 'thumbnail_id', (int) $thumb );
		$set++;
	} else {
		$none++;
	}
}

WP_CLI::log( "Thumbnail categorii: setate=$set, deja aveau=$skip, fără produs cu imagine=$none" );
WP_CLI::success( 'Thumbnail-uri categorii finalizate.' );
