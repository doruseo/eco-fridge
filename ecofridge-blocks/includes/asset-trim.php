<?php
/**
 * Page speed: SMS Alert's phone-field and OTP files (2 render-blocking
 * stylesheets, 4 scripts) were printed on every page. On this shop OTP is
 * switched off and the country-code phone field exists only on the checkout,
 * so they are kept on the checkout and dropped everywhere else.
 *
 * Not trimmed, on purpose: Astra's WooCommerce stylesheets. Dropping them on
 * pages without a product loop (tried on 2026-11-07) broke the header basket
 * dropdown and the commercial mega menu, which rely on them on every page.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}
		foreach ( array( 'sa-login-css', 'wpv_telinputcss_style' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
		foreach ( array( 'sa-handle-footer', 'sa_pv_intl-phones-lib', 'wccheckout_utils', 'wccheckout_default' ) as $handle ) {
			wp_dequeue_script( $handle );
		}
	},
	999
);
