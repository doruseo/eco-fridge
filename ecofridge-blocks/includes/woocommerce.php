<?php
/**
 * WooCommerce integration: block editor for product descriptions.
 *
 * Product descriptions are built from blocks (text + efb/spec-table), so the
 * product screen uses the block editor. WooCommerce's product data boxes
 * (price, stock, short description…) stay available below the content.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'use_block_editor_for_post_type',
	static function ( $use, $post_type ) {
		return 'product' === $post_type ? true : $use;
	},
	100,
	2
);

/**
 * Expose products to the REST API editor routes (needed by the block editor).
 */
add_filter(
	'woocommerce_register_post_type_product',
	static function ( $args ) {
		$args['show_in_rest'] = true;
		return $args;
	}
);
