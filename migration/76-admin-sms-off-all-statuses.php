<?php
/**
 * 76 — the "no SMS to the company" rule covers every status, including new ones.
 *
 * Doru found the company text still ticked for the new Preparing status and
 * unticked it. The cause is a gap in migration 70: that one switched off the
 * `admin_notification_*` keys that existed at the time, and Preparing was
 * created later. The plugin reads a missing key as ON
 * (`smsalert_get_option( 'admin_notification_' . $status, 'smsalert_general', 'on' )`),
 * so any status added after a run starts out ticked.
 *
 * Nothing was ever sent: the plugin also requires a company mobile number, and
 * that field is empty. But the tick would have become real the moment someone
 * filled it in, so the switches are now written out for every status
 * WooCommerce knows, not only for the keys that happen to be stored.
 *
 * Run this again after adding an order status, or on the live site if a status
 * appears there first.
 *
 * Safe to run again. Backup: ef_smsalert_admin_before_76.
 * Run: wp eval-file migration/76-admin-sms-off-all-statuses.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$general = (array) get_option( 'smsalert_general', array() );

if ( ! get_option( 'ef_smsalert_admin_before_76' ) ) {
	add_option( 'ef_smsalert_admin_before_76', $general, '', false );
	echo "Backup saved in ef_smsalert_admin_before_76.\n";
}

// Every status in the flow, plus the two the plugin adds on its own.
$slugs = array( 'partially_refunded', 'checkout-draft' );
foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
	$slugs[] = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
}
$slugs = array_unique( $slugs );

$changed = 0;
foreach ( $slugs as $slug ) {
	$key = 'admin_notification_' . $slug;
	if ( ! isset( $general[ $key ] ) || 'off' !== $general[ $key ] ) {
		$general[ $key ] = 'off';
		++$changed;
	}
}

// The company alerts that are not tied to a status.
foreach ( array( 'admin_registration_msg', 'admin_low_stock_msg', 'admin_out_of_stock_msg' ) as $key ) {
	if ( ! isset( $general[ $key ] ) || 'off' !== $general[ $key ] ) {
		$general[ $key ] = 'off';
		++$changed;
	}
}

update_option( 'smsalert_general', $general );

echo "Company texts switched off: {$changed} settings (" . count( $slugs ) . " statuses covered).\n";

$still_on = array();
foreach ( $general as $key => $value ) {
	if ( 0 === strpos( $key, 'admin_notification_' ) && 'off' !== $value ) {
		$still_on[] = $key;
	}
}
echo 'Still on: ' . ( $still_on ? implode( ', ', $still_on ) : 'none' ) . "\n";
