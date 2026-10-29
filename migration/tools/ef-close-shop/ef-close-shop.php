<?php
/**
 * Plugin Name: ECO-Fridge - Close online ordering
 * Description: Keeps the shop visible but stops any order being placed, for the morning the new website goes live. Products cannot be added to the basket, the checkout refuses to run, no payment method is offered, and visitors are asked to ring the office instead. Deactivate this plugin to open the shop again.
 * Version: 1.0.0
 * Author: O2 Digital
 * Requires PHP: 7.0
 *
 * HOW TO INSTALL (about thirty seconds, no FTP and nothing for the host to do):
 *   WordPress admin > Plugins > Add New > Upload Plugin > choose ef-close-shop.zip
 *   > Install Now > Activate.
 * HOW TO UNDO: Plugins > deactivate "ECO-Fridge - Close online ordering".
 * The shop opens again the moment it is deactivated.
 *
 * WHAT IT DOES: turns the shop into a catalogue. Products can be read, but
 * nothing can be added to the basket, the basket and checkout pages say the
 * same thing, and a submitted checkout is refused on the server as well. A
 * notice tells the customer to ring the office.
 *
 * WHY NOT MAINTENANCE MODE: a 503 or "be right back" page would be wrong here.
 * After the DNS change, visitors reach the old server for several hours while
 * the change spreads, and all of them would see a dead site. In catalogue mode
 * they see the shop exactly as before, with ordering by phone for one morning,
 * and Google sees a normal, indexable site throughout.
 *
 * WHY NOT WP_DEBUG: debugging does not stop a single order. On PHP 7.3 it would
 * only print warnings and file paths to visitors.
 *
 * PHP 7.3 SAFE: plain closures and functions only - no arrow functions, no
 * typed properties, no null-coalescing assignment, no str_contains, nothing
 * newer than PHP 7.0. Tested against WooCommerce 3.5 hook names.
 *
 * SECOND LAYER: also switch every gateway off in WooCommerce > Settings >
 * Payments, and disable any 100% discount coupon - a zero-total order needs no
 * payment method at all.
 *
 * @package EcoFridgeMigration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The message shown to customers while the shop is paused. */
function ef_pause_shop_message() {
	return 'Our online ordering is closed this morning while we move to our new website. '
		. 'To place an order today please call <strong>01280 811411</strong> or email '
		. '<a href="mailto:sales@eco-fridge.co.uk">sales@eco-fridge.co.uk</a>. '
		. 'Everything will be back to normal shortly - thank you for your patience.';
}

/* Nothing is purchasable, so no price form and no "Add to cart" button. Stock
   figures are left alone: a product that really is out of stock still says so. */
add_filter( 'woocommerce_is_purchasable', '__return_false', 99 );

/* The standard "Add to cart" templates are unhooked as well, on the product page
   and in the grids. A theme that prints its own button keeps printing it, which
   is why the validation filter below is the real guard. */
add_action(
	'init',
	function () {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	},
	99
);

/* The basket refuses new items, even from a cached page or a direct link. */
add_filter(
	'woocommerce_add_to_cart_validation',
	function ( $passed ) {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( ef_pause_shop_message(), 'notice' );
		}
		return false;
	},
	99
);

/* A submitted checkout is refused on the server too. */
add_action(
	'woocommerce_checkout_process',
	function () {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( ef_pause_shop_message(), 'error' );
		}
	},
	1
);

/* Belt and braces: no gateway is offered at checkout. */
add_filter( 'woocommerce_available_payment_gateways', '__return_empty_array', 99 );

/* The same notice at the top of the shop, the basket and the checkout. */
$ef_pause_notice = function () {
	if ( ! function_exists( 'wc_print_notice' ) ) {
		return;
	}
	echo '<div class="woocommerce-info" style="margin:0 0 1.5em;padding:1em 1.25em;background:#f4f8f4;border-top:3px solid #1f821f;color:#223027;font-size:1em;line-height:1.5;">'
		. ef_pause_shop_message()
		. '</div>';
};

add_action( 'woocommerce_before_main_content', $ef_pause_notice, 5 );
add_action( 'woocommerce_before_cart', $ef_pause_notice, 5 );
add_action( 'woocommerce_cart_is_empty', $ef_pause_notice, 5 );
add_action( 'woocommerce_before_checkout_form', $ef_pause_notice, 5 );

/* Any add-to-cart control is hidden, whatever the theme prints. The guards above
   already refuse the order; this keeps a dead button out of the customer's way. */
add_action(
	'wp_head',
	function () {
		echo '<style id="ef-close-shop">form.cart, .add_to_cart_button, .single_add_to_cart_button, .ajax_add_to_cart, .wc-block-components-product-button { display: none !important; }</style>' . "
";
	},
	99
);

/* If someone reaches the checkout with an old session basket, send them back to
   the basket page, where the notice explains why. */
add_action(
	'template_redirect',
	function () {
		if ( ! function_exists( 'is_checkout' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
			return;
		}
		if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
			wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}
	}
);

/* A line in the admin bar so staff can see at a glance that the shop is paused. */
add_action(
	'admin_notices',
	function () {
		echo '<div class="notice notice-warning"><p><strong>Online ordering is closed</strong> while the new website goes live. '
			. 'Deactivate the plugin "ECO-Fridge - Close online ordering" to open the shop again.</p></div>';
	}
);
