<?php
/**
 * Step 12: sets thumbnails (images) on the product categories that have none,
 * using the main image of the category's first product. Needed for
 * the sub-category tiles on the homepage (Display/Storage) and the category
 * pages. Editable later from Products → Categories. Idempotent (skips
 * those that already have a thumbnail). Run: wp eval-file wp-content/source-dump/_run.php
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

WP_CLI::log( "Category thumbnails: set=$set, already had one=$skip, no product with an image=$none" );
WP_CLI::success( 'Category thumbnails finished.' );
