<?php
/**
 * Step 18: UK VAT 20% in WooCommerce + prices shown EXCLUDING VAT (VAT is added separately
 * in the basket, as its own line).
 *   - taxes on, prices entered without VAT, based on the shop address (UK) → always 20%
 *   - shop and basket shown "excl" (without VAT), VAT total "itemized" (separate line)
 *   - standard rate: GB "VAT" 20% (also applied to fees: Removal & Disposal, Warranty)
 *
 * Idempotent. Run: cp migration/18-tax-vat.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// —— tax settings ——
update_option( 'woocommerce_calc_taxes', 'yes' );            // turn taxes on
update_option( 'woocommerce_prices_include_tax', 'no' );     // prices entered WITHOUT VAT
update_option( 'woocommerce_tax_based_on', 'base' );         // by the shop address (UK) → always 20%
update_option( 'woocommerce_tax_display_shop', 'excl' );     // shop: show without VAT
update_option( 'woocommerce_tax_display_cart', 'excl' );     // basket/checkout: show without VAT
update_option( 'woocommerce_tax_total_display', 'itemized' );// VAT = separate line
update_option( 'woocommerce_default_country', 'GB' );
update_option( 'woocommerce_currency', 'GBP' );

// —— UK standard rate: GB "VAT" 20% (unless it already exists) ——
global $wpdb;
$exists = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country=%s AND tax_rate=%s",
	'GB', '20.0000'
) );

if ( ! $exists && class_exists( 'WC_Tax' ) ) {
	$rate_id = WC_Tax::_insert_tax_rate( array(
		'tax_rate_country'  => 'GB',
		'tax_rate_state'    => '',
		'tax_rate'          => '20.0000',
		'tax_rate_name'     => 'VAT',
		'tax_rate_priority' => 1,
		'tax_rate_compound' => 0,
		'tax_rate_shipping' => 1,   // VAT on shipping too
		'tax_rate_order'    => 0,
		'tax_rate_class'    => '',  // Standard class
	) );
	WP_CLI::log( 'GB "VAT" 20% rate added (rate #' . $rate_id . ').' );
} else {
	WP_CLI::log( 'GB VAT 20% rate already present: left unchanged.' );
}

WP_CLI::success( 'UK VAT 20% and ex-VAT display configured (VAT as its own basket line).' );
