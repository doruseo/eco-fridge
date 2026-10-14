<?php
/**
 * Cart and checkout: clearer and more reassuring.
 *
 *  - the cart says plainly that UK mainland delivery is free, instead of
 *    "Shipping options will be updated during checkout";
 *  - "Continue shopping" under the cart table.
 * The look (cards, green accents) is in style.css → "CART & CHECKOUT".
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// No trust strip on the cart or the checkout: it distracted from the order.

// Cart: "Continue shopping" under the table.
add_action( 'woocommerce_after_cart_table', function () {
	echo '<p class="ef-continue-shopping"><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">&larr; ' . esc_html__( 'Continue shopping', 'eco-fridge-child' ) . '</a></p>';
} );

// Cart totals before an address is known: say that UK mainland delivery is free.
add_filter( 'woocommerce_shipping_estimate_html', function () {
	return '<span class="ef-free-delivery">' . esc_html__( 'Free delivery to UK mainland', 'eco-fridge-child' ) . '</span><br><small>' . esc_html__( 'Confirmed at checkout. Deliveries above York, Leeds, Blackpool and outside UK mainland incur an extra charge.', 'eco-fridge-child' ) . '</small>';
} );

/*
 * Checkout (block): an empty optional County no longer blocks the order.
 * WooCommerce Blocks first renders the County field with the default rules
 * ("State/County", required), records a hidden "Please enter a valid
 * state/county" error, and keeps it when the UK rules (County, optional) apply –
 * so "Place order" stopped with an empty County. The stale error is cleared
 * whenever the field is optional and empty.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) { return; }
	?>
<script>
( function () {
	if ( ! window.wp || ! wp.data ) { return; }
	var ids = [ 'shipping-state', 'billing-state' ];
	wp.data.subscribe( function () {
		var store = wp.data.select( 'wc/store/validation' );
		if ( ! store || ! store.getValidationErrors ) { return; }
		var errors = store.getValidationErrors();
		ids.forEach( function ( id ) {
			var field = document.getElementById( id );
			if ( errors[ id ] && field && ! field.required && ! field.value ) {
				wp.data.dispatch( 'wc/store/validation' ).clearValidationError( id );
			}
		} );
	} );
} )();
</script>
	<?php
}, 100 );
