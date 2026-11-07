<?php
/**
 * Step 21: overwrites the category copy with the IMPROVED versions (text rewritten
 * in professional British English, with ALL images and links kept 1:1).
 * Runs AFTER 20-range-content.php (which imports the spammy original from the legacy DB).
 *
 * Source = the files in migration/category-content/<slug>.html (tracked in git).
 * Run: cp migration/21-range-content-improved.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$dir = dirname( __DIR__ ) . '/category-content/';
// migration/ is not mapped into the container, so source-dump is used; copied before the run (see below).
$dir = '/var/www/html/wp-content/source-dump/_improved/';
if ( ! is_dir( $dir ) ) { WP_CLI::error( $dir . ' is missing: copy migration/category-content/* there first.' ); }
$n = 0;
foreach ( glob( $dir . '*.html' ) as $f ) {
	$slug = basename( $f, '.html' );
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $t || is_wp_error( $t ) ) { WP_CLI::warning( 'slug without a term: ' . $slug ); continue; }
	update_term_meta( $t->term_id, '_ef_range_content', file_get_contents( $f ) );
	$n++;
}

// Category archives: show the SUB-CATEGORIES first, then the PRODUCTS.
update_option( 'woocommerce_category_archive_display', 'both' );
update_option( 'woocommerce_shop_page_display', 'both' );
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
	update_term_meta( $term->term_id, 'display_type', '' ); // inherits the global setting ('both')
}

WP_CLI::success( "Improved copy imported: $n categories; display = sub-categories + products." );
