<?php
/**
 * No customer accounts: every order is a guest order.
 *
 * ECO-Fridge does not want accounts for the people who buy from the shop.
 *
 *  - The settings that would offer an account are locked off in code, so a
 *    click in WooCommerce → Settings or a plugin cannot turn them back on:
 *    "Anyone can register", account creation on My Account, during checkout
 *    and after checkout, the login reminder at checkout; guest checkout is
 *    always on.
 *  - Every registration path is refused on the server as well (wp-login.php
 *    registration, WooCommerce My Account / checkout / Store API), whatever a
 *    bot sends.
 *  - Visitors who open My Account go to the homepage; the page is left out of
 *    page lists. Staff still sign in at /wp-admin/, and the lost-password and
 *    reset-password pages keep working for them.
 *  - Staff accounts are created by an administrator under Users → Add New.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Settings locked.
 * ------------------------------------------------------------------------- */
foreach (
	array(
		'users_can_register'                               => '0',
		'woocommerce_enable_myaccount_registration'        => 'no',
		'woocommerce_enable_signup_and_login_from_checkout' => 'no',
		'woocommerce_enable_delayed_account_creation'      => 'no',
		'woocommerce_enable_checkout_login_reminder'       => 'no',
		'woocommerce_enable_guest_checkout'                => 'yes',
	) as $efb_option => $efb_value
) {
	add_filter( 'pre_option_' . $efb_option, static fn() => $efb_value );
}
unset( $efb_option, $efb_value );

add_filter( 'woocommerce_checkout_registration_required', '__return_false' );
add_filter( 'woocommerce_checkout_registration_enabled', '__return_false' );

// The settings screens say why the boxes cannot be ticked.
add_action(
	'woocommerce_settings_account',
	static function () {
		echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Customer accounts are switched off by the ECO-Fridge Blocks plugin: all orders are guest orders, and account creation cannot be turned on here.', 'ecofridge-blocks' ) . '</p></div>';
	}
);

/* ---------------------------------------------------------------------------
 * Registration refused on the server.
 * ------------------------------------------------------------------------- */

/**
 * The error every registration attempt gets.
 *
 * @return WP_Error
 */
function efb_registration_refused() {
	return new WP_Error( 'efb_no_accounts', __( 'We do not create customer accounts – you can order as a guest.', 'ecofridge-blocks' ) );
}

// WooCommerce: My Account, classic and block checkout, Store API.
add_filter( 'woocommerce_registration_errors', static fn() => efb_registration_refused(), 999 );
// WordPress: wp-login.php?action=register.
add_filter( 'registration_errors', static fn() => efb_registration_refused(), 999 );

add_action(
	'login_form_register',
	static function () {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
);

/* ---------------------------------------------------------------------------
 * My Account: not a page for visitors.
 * ------------------------------------------------------------------------- */
add_action(
	'template_redirect',
	static function () {
		if ( is_user_logged_in() || ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}
		if ( is_wc_endpoint_url( 'lost-password' ) || isset( $_GET['key'], $_GET['login'] ) || isset( $_GET['show-reset-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- password reset for staff.
			return;
		}
		wp_safe_redirect( home_url( '/' ) );
		exit;
	},
	5
);

add_filter(
	'wp_list_pages_excludes',
	static function ( $exclude ) {
		$exclude[] = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'myaccount' ) : 0;
		return array_filter( $exclude );
	}
);
