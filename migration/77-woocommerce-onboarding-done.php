<?php
/**
 * 77 — WooCommerce → Home opens the dashboard, not the setup wizard.
 *
 * Everything the wizard asks for is already set on this shop: address in
 * Buckingham, country GB, currency GBP, tax, shipping, payments, emails. The
 * wizard kept appearing only because `woocommerce_onboarding_profile` was never
 * written — the site was built by migration scripts, not by clicking through
 * the setup. WooCommerce shows the wizard whenever that option's `completed`
 * flag is missing.
 *
 * The profile is filled in with what the shop actually is (one physical product
 * type, 101-1000 products, a trade counter as well as the website) and marked
 * complete, and the task list is hidden so Home shows the normal dashboard.
 *
 * Safe to run again. Backup: ef_onboarding_before_77.
 * Run: wp eval-file migration/77-woocommerce-onboarding-done.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$before = array(
	'profile'           => get_option( 'woocommerce_onboarding_profile', array() ),
	'task_list_hidden'  => get_option( 'woocommerce_task_list_hidden', '' ),
	'task_list_done'    => get_option( 'woocommerce_task_list_complete', '' ),
);

if ( ! get_option( 'ef_onboarding_before_77' ) ) {
	add_option( 'ef_onboarding_before_77', $before, '', false );
	echo "Backup saved in ef_onboarding_before_77.\n";
}

$profile = (array) get_option( 'woocommerce_onboarding_profile', array() );

$profile = array_merge(
	array(
		'setup_client'       => false,
		'industry'           => array(
			array(
				'slug'   => 'other',
				'detail' => 'Commercial refrigeration equipment',
			),
		),
		'product_types'      => array( 'physical' ),
		'product_count'      => '101-1000',
		'selling_venues'     => 'brick-mortar-other',
		'business_extensions' => array(),
		'theme'              => get_stylesheet(),
		'is_agree_marketing' => false,
		'store_email'        => get_option( 'admin_email' ),
	),
	$profile,
	array(
		'completed' => true,
		'skipped'   => true,
	)
);

update_option( 'woocommerce_onboarding_profile', $profile );
update_option( 'woocommerce_task_list_hidden', 'yes' );
update_option( 'woocommerce_task_list_complete', 'yes' );

echo "Onboarding marked complete; the task list on WooCommerce - Home is hidden.\n";
echo 'Store: ' . get_option( 'woocommerce_store_address' ) . ', ' . get_option( 'woocommerce_store_city' ) . ' ' . get_option( 'woocommerce_store_postcode' ) . ' (' . get_option( 'woocommerce_default_country' ) . '), currency ' . get_option( 'woocommerce_currency' ) . "\n";
