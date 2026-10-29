<?php
/**
 * 74 — no text message when an order is shipped or completed (Doru, 2026-10-29).
 *
 * The shop's reasoning: at Shipped the driver and the engineer ring the customer
 * anyway, and at Complete the customer has the goods in front of them. Both
 * messages would only cost credits (around 40 each to a UK number), so the
 * texts stay only where the customer has to wait or to act:
 *
 *   Processing  payment taken by card, stock and funds being checked
 *   Preparing   approved, being prepared, delivery 5 to 10 working days
 *   Processed   ready to be shipped
 *   On hold     waiting for the bank transfer (the customer has to act)
 *   Refunded / Partially refunded   when the money goes back
 *
 * The emails for Shipped and Complete are untouched: they stay as the written
 * record of the delivery.
 *
 * The templates are left in place, so switching a status back on in SMS Alert
 * is one tick.
 *
 * Safe to run again. Backup: ef_smsalert_statuses_before_74.
 * Run: wp eval-file migration/74-sms-statuses-trimmed.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$general  = (array) get_option( 'smsalert_general', array() );
$statuses = (array) ( $general['order_status'] ?? array() );

if ( ! get_option( 'ef_smsalert_statuses_before_74' ) ) {
	add_option( 'ef_smsalert_statuses_before_74', $statuses, '', false );
	echo "Backup saved in ef_smsalert_statuses_before_74.\n";
}

foreach ( array( 'shipped', 'completed' ) as $slug ) {
	$statuses[ $slug ] = '';
}

$general['order_status'] = $statuses;
update_option( 'smsalert_general', $general );

$on = array();
foreach ( $statuses as $slug => $value ) {
	if ( '' !== $value ) {
		$on[] = $slug;
	}
}

echo 'Customer texts now only on: ' . implode( ', ', $on ) . "\n";
echo "Shipped and Complete: email only.\n";
