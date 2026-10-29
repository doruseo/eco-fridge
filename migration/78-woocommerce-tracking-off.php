<?php
/**
 * 78 — WooCommerce usage tracking off (Doru, 2026-10-29).
 *
 * `woocommerce_allow_tracking` was "yes", so WooCommerce sent usage data about
 * the shop to woocommerce.com once a day. Nothing on the site depends on it.
 *
 * Safe to run again. Backup: ef_tracking_before_78.
 * Run: wp eval-file migration/78-woocommerce-tracking-off.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

if ( ! get_option( 'ef_tracking_before_78' ) ) {
	add_option( 'ef_tracking_before_78', get_option( 'woocommerce_allow_tracking', '' ), '', false );
}

update_option( 'woocommerce_allow_tracking', 'no' );

// The daily job that sends the data; it is rescheduled only if tracking is on.
wp_clear_scheduled_hook( 'woocommerce_tracker_send_event' );

echo 'woocommerce_allow_tracking = ' . get_option( 'woocommerce_allow_tracking' ) . "\n";
echo 'tracker job scheduled: ' . ( wp_next_scheduled( 'woocommerce_tracker_send_event' ) ? 'yes' : 'no' ) . "\n";
