<?php
/**
 * Switch the /cart page from the WooCommerce Cart Block to the classic
 * `[woocommerce_cart]` shortcode.
 *
 * Why: the Cart Block renders its "Order summary" client-side with a FIXED
 * structure that cannot reproduce the required Cart totals order
 * (Subtotal → Removal & Disposal → Warranty → Shipping → VAT → Total) and
 * ignores our `woocommerce/cart/cart-totals.php` template override. The classic
 * cart is fully driven by that template, so the totals column shows the product
 * subtotal plus every fee/shipping/tax row in the requested order.
 *
 * Idempotent: only rewrites the page if it isn't already the classic shortcode.
 * The previous (block) content is saved as a revision so it can be restored.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }

$cart_id = wc_get_page_id( 'cart' );
if ( $cart_id <= 0 ) {
	WP_CLI::warning( 'No cart page configured.' );
	return;
}

$post = get_post( $cart_id );
if ( has_shortcode( (string) $post->post_content, 'woocommerce_cart' ) ) {
	WP_CLI::log( 'Cart page already uses the classic [woocommerce_cart] shortcode — nothing to do.' );
	return;
}

// Keep a revision of the current (block) content before replacing it.
wp_save_post_revision( $cart_id );

$result = wp_update_post(
	array(
		'ID'           => $cart_id,
		'post_content' => '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
	),
	true
);

if ( is_wp_error( $result ) ) {
	WP_CLI::error( 'Failed to update the cart page: ' . $result->get_error_message() );
}

WP_CLI::success( 'Cart page switched to the classic [woocommerce_cart] shortcode (block content kept as a revision).' );
