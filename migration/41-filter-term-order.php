<?php
/**
 * 41 — Sidebar filters: Width and Height values in size order.
 *
 * The attributes are set to "Custom ordering" (menu_order), but their terms had
 * no order, so the filters listed them alphabetically ("1200–1500 mm" before
 * "Up to 600 mm"). This sets WooCommerce's term `order` meta smallest → largest.
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$orders = array(
	'pa_width'  => array( 'up-to-600-mm', '600-900-mm', '900-1200-mm', '1200-1500-mm', 'over-1500-mm' ),
	'pa_height' => array( 'up-to-900-mm', '900-1400-mm', '1400-1800-mm', 'over-1800-mm' ),
);

$done = 0;
foreach ( $orders as $taxonomy => $slugs ) {
	foreach ( $slugs as $i => $slug ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term ) {
			WP_CLI::warning( "Term not found: $taxonomy/$slug" );
			continue;
		}
		update_term_meta( $term->term_id, 'order', $i );
		$done++;
	}
}
wc_delete_product_transients();
WP_CLI::success( "Filter term order set on $done terms." );
