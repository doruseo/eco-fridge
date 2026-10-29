<?php
/**
 * 69 — Google Tag Manager for WordPress (GTM4WP) settings + cookie consent.
 *
 * GTM4WP loads the container (GTM-NFMC9Z3, entered by Doru) right after the
 * opening <body>; EF Cookie Consent prints the Consent Mode v2 defaults
 * earlier in <head>, so it is told GTM is loaded by another plugin, and
 * GTM4WP's own consent defaults stay off.
 *
 * dataLayer:
 *  - page: post type, categories, tags, title, ID, author, date, search
 *    data, language, logged-in state and role (to filter staff out);
 *    no personal data (no user email / name / IP);
 *  - WooCommerce GA4 ecommerce: view_item_list, select_item, view_item,
 *    add_to_cart, remove_from_cart, view_cart, begin_checkout,
 *    add_shipping_info, add_payment_info, purchase (also for bank-transfer
 *    orders on hold); values without VAT and shipping, brand from WooCommerce
 *    Brands, business vertical retail, ecommerce object cleared between
 *    events; item IDs = Merchant Center feed IDs (ecofridge-blocks);
 *  - purchase: order, customer, location and traffic-source details plus
 *    hashed user_data for Google Ads enhanced conversions come from
 *    ecofridge-blocks/includes/analytics.php, so GTM4WP's own customer and
 *    order data (plain names, email, address) stay off;
 *  - contact form: form_start / form_submit / generate_lead come from the
 *    efb/contact-form script (Contact Form 7 is not used).
 *
 * Keeps the container ID that is set. Safe to run again.
 * Run: wp eval-file migration/69-gtm4wp-settings.php
 *
 * @package EcoFridgeMigration
 */

$opts = (array) get_option( 'gtm4wp-options', array() );
$set  = array(
	// Container: after <body> opens, automatically; for everyone.
	'gtm-code-placement'                                  => 2,
	'gtm-no-gtm-for-logged-in'                            => false,
	// Page data.
	'include-posttype'                                    => true,
	'include-categories'                                  => true,
	'include-tags'                                        => true,
	'include-posttitle'                                   => true,
	'include-postid'                                      => true,
	'include-author'                                      => true,
	'include-postdate'                                    => true,
	'include-searchdata'                                  => true,
	'include-page-language'                               => true,
	'include-loggedin'                                    => true,
	'include-userrole'                                    => true,
	'include-userid'                                      => false,
	'include-useremail'                                   => false,
	'include-username'                                    => false,
	'include-visitor-ip'                                  => false,
	// Events.
	'event-form-move'                                     => false,
	'event-youtube'                                       => true,
	// Contact Form 7 is not used on this site.
	'integrate-wpcf7'                                     => false,
	'integrate-wpcf7-ga4events'                           => false,
	// WooCommerce.
	'integrate-woocommerce-track-enhanced-ecommerce'      => true,
	'integrate-woocommerce-cart-content-in-datalayer'     => true,
	'integrate-woocommerce-brand-taxonomy'                => 'product_brand',
	'integrate-woocommerce-business-vertical'             => 'retail',
	'integrate-woocommerce-remarketing-usesku'            => false,
	'integrate-woocommerce-use-full-category-path'        => true,
	'integrate-woocommerce-persist-list-attribution'      => true,
	'integrate-woocommerce-clear-ecommerce-datalayer'     => true,
	'integrate-woocommerce-exclude-tax'                   => true,
	'integrate-woocommerce-exclude-shipping'              => true,
	'integrate-woocommerce-purchase-track-statuses'       => array( 'processing', 'on-hold', 'completed' ),
	'integrate-woocommerce-customer-data'                 => false,
	'integrate-woocommerce-order-data'                    => false,
	'integrate-woocommerce-do-not-use-order-tracked-flag' => false,
	// Consent Mode defaults come from EF Cookie Consent.
	'integrate-consent-mode'                              => false,
);
update_option( 'gtm4wp-options', array_merge( $opts, $set ) );
WP_CLI::log( 'GTM4WP: ' . count( $set ) . ' settings applied; container ' . ( $opts['gtm-code'] ?? '(none)' ) . ' kept.' );

if ( class_exists( 'EFCC_Settings' ) ) {
	$all                    = (array) get_option( EFCC_Settings::OPTION, array() );
	$all['gcm']             = wp_parse_args( (array) ( $all['gcm'] ?? array() ), EFCC_Settings::defaults()['gcm'] );
	$all['gcm']['gtm_load'] = 'external';
	$all['gcm']['enabled']  = 1;
	update_option( EFCC_Settings::OPTION, $all );
	WP_CLI::log( 'EF Cookie Consent: Consent Mode v2 on, GTM loaded by another plugin (GTM4WP).' );
}
WP_CLI::success( 'Done.' );
