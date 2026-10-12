<?php
/**
 * Cart and checkout: clearer and more reassuring.
 *
 *  - a trust strip above the cart and the checkout (free UK mainland delivery,
 *    trade prices, secure checkout, phone help);
 *  - the cart says plainly that UK mainland delivery is free, instead of
 *    "Shipping options will be updated during checkout";
 *  - "Continue shopping" under the cart table.
 * The look (cards, green accents) is in style.css → "CART & CHECKOUT".
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The trust strip (shared by cart and checkout). */
function ef_trust_strip_html() {
	$items = array(
		array( 'truck', __( 'Free delivery to UK mainland', 'eco-fridge-child' ), __( 'T&C apply', 'eco-fridge-child' ) ),
		array( 'tag', __( 'Trade prices', 'eco-fridge-child' ), __( 'Shown ex VAT, VAT added at checkout', 'eco-fridge-child' ) ),
		array( 'lock', __( 'Secure checkout', 'eco-fridge-child' ), __( 'Your details are encrypted', 'eco-fridge-child' ) ),
		array( 'phone', __( 'Need help?', 'eco-fridge-child' ), '<a href="tel:01280811411">01280 811411</a>' ),
	);
	$icons = array(
		'truck' => 'M1 3h15v13H1zM16 8h4l3 3v5h-7zM5.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM18.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
		'tag'   => 'M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8zM7 7h.01',
		'lock'  => 'M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4',
		'phone' => 'M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z',
	);
	$out = '<ul class="ef-trust-strip">';
	foreach ( $items as $it ) {
		$out .= '<li><span class="ef-trust-strip__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . esc_attr( $icons[ $it[0] ] ) . '"/></svg></span>'
			. '<span><strong>' . esc_html( $it[1] ) . '</strong><small>' . wp_kses( $it[2], array( 'a' => array( 'href' => true ) ) ) . '</small></span></li>';
	}
	return $out . '</ul>';
}

// Cart: trust strip above the table.
add_action( 'woocommerce_before_cart', function () {
	echo ef_trust_strip_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.
}, 5 );

// Checkout (block): trust strip above the form.
add_filter( 'render_block_woocommerce/checkout', function ( $html ) {
	return is_admin() || is_wc_endpoint_url( 'order-received' ) ? $html : ef_trust_strip_html() . $html;
} );
// Checkout (classic shortcode, if ever used): same strip.
add_action( 'woocommerce_before_checkout_form', function () {
	if ( is_wc_endpoint_url( 'order-received' ) ) { return; }
	echo ef_trust_strip_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.
}, 5 );

// Cart: "Continue shopping" under the table.
add_action( 'woocommerce_after_cart_table', function () {
	echo '<p class="ef-continue-shopping"><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">&larr; ' . esc_html__( 'Continue shopping', 'eco-fridge-child' ) . '</a></p>';
} );

// Cart totals before an address is known: say that UK mainland delivery is free.
add_filter( 'woocommerce_shipping_estimate_html', function () {
	return '<span class="ef-free-delivery">' . esc_html__( 'Free delivery to UK mainland', 'eco-fridge-child' ) . '</span><br><small>' . esc_html__( 'Confirmed at checkout. Deliveries above York, Leeds, Blackpool and outside UK mainland incur an extra charge.', 'eco-fridge-child' ) . '</small>';
} );
