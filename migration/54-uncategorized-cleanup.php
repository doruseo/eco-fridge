<?php
/**
 * 54 — "Uncategorized" clean-up.
 *
 *  - products that have real categories lose the leftover "Uncategorized"
 *    product category (kept from the original site);
 *  - both "Uncategorized" archives – /product-category/uncategorized/ and the
 *    empty blog category /uncategorized/ – are noindex (Rank Math), so they also
 *    leave the sitemap. They can't be deleted: WordPress and WooCommerce use
 *    them as the default categories;
 *  - "Removal & Disposal Extra" (the original site's add-on product; the shop
 *    now charges Removal & Disposal as an option on each product) is hidden from
 *    the catalogue, search and search engines (noindex). Its URL keeps working.
 *
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$product_cat = get_term_by( 'slug', 'uncategorized', 'product_cat' );
$blog_cat    = get_term_by( 'slug', 'uncategorized', 'category' );

if ( $product_cat ) {
	$removed = 0;
	foreach ( get_objects_in_term( $product_cat->term_id, 'product_cat' ) as $product_id ) {
		$others = array_diff( wp_get_post_terms( (int) $product_id, 'product_cat', array( 'fields' => 'ids' ) ), array( $product_cat->term_id ) );
		if ( $others ) {
			wp_remove_object_terms( (int) $product_id, $product_cat->term_id, 'product_cat' );
			++$removed;
		}
	}
	wp_update_term_count_now( array( $product_cat->term_taxonomy_id ), 'product_cat' );
	WP_CLI::log( "Uncategorized removed from $removed products that have other categories." );
}

foreach ( array_filter( array( $product_cat, $blog_cat ) ) as $term ) {
	update_term_meta( $term->term_id, 'rank_math_robots', array( 'noindex', 'follow' ) );
	WP_CLI::log( "noindex: {$term->taxonomy}/{$term->slug}" );
}

$addon = get_page_by_path( 'removal-disposal', OBJECT, 'product' );
$addon = $addon ? wc_get_product( $addon->ID ) : null;
if ( $addon ) {
	if ( 'hidden' !== $addon->get_catalog_visibility() ) {
		$addon->set_catalog_visibility( 'hidden' );
		$addon->save();
	}
	// Hidden from search engines too (and so from the sitemap).
	update_post_meta( $addon->get_id(), 'rank_math_robots', array( 'noindex', 'follow' ) );
	WP_CLI::log( 'Removal & Disposal Extra hidden from the catalogue, search and search engines.' );
}

if ( class_exists( '\RankMath\Sitemap\Cache' ) ) {
	\RankMath\Sitemap\Cache::invalidate_storage();
}
WP_CLI::success( 'Uncategorized cleaned up.' );
