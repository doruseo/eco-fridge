<?php
/**
 * Pasul 18: TVA UK 20% în WooCommerce + afișare preț FĂRĂ TVA (TVA se adaugă separat
 * în coș, ca linie distinctă).
 *   - taxe activate, prețuri introduse fără TVA, bază = adresa magazinului (UK) → mereu 20%
 *   - afișare magazin + coș „excl" (fără TVA), total TVA „itemized" (linie separată)
 *   - cotă standard: GB „VAT" 20% (aplicabilă și la fee-uri: Removal & Disposal, Warranty)
 *
 * Idempotent. Rulare: cp migration/18-tax-vat.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// —— setări de taxe ——
update_option( 'woocommerce_calc_taxes', 'yes' );            // activează taxele
update_option( 'woocommerce_prices_include_tax', 'no' );     // prețuri introduse FĂRĂ TVA
update_option( 'woocommerce_tax_based_on', 'base' );         // pe adresa magazinului (UK) → mereu 20%
update_option( 'woocommerce_tax_display_shop', 'excl' );     // magazin: afișează fără TVA
update_option( 'woocommerce_tax_display_cart', 'excl' );     // coș/checkout: afișează fără TVA
update_option( 'woocommerce_tax_total_display', 'itemized' );// TVA = linie separată
update_option( 'woocommerce_default_country', 'GB' );
update_option( 'woocommerce_currency', 'GBP' );

// —— cotă standard UK: GB „VAT" 20% (dacă nu există deja) ——
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
		'tax_rate_shipping' => 1,   // TVA și pe livrare
		'tax_rate_order'    => 0,
		'tax_rate_class'    => '',  // clasa Standard
	) );
	WP_CLI::log( 'Cotă TVA „VAT" GB 20% adăugată (rate #' . $rate_id . ').' );
} else {
	WP_CLI::log( 'Cotă TVA GB 20% deja prezentă — nemodificată.' );
}

WP_CLI::success( 'TVA UK 20% + afișare fără TVA configurate (TVA separat în coș).' );
