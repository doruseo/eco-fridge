<?php
/**
 * 72 — the order emails say the same as the order SMS.
 *
 * The SMS texts were agreed first (migration 70), so the emails follow them,
 * status by status, and the two channels no longer tell the customer different
 * things:
 *
 *   Pending   payment taken, stock and funds being checked, 3 to 4 working days
 *             (the shop's own "Payment received (card)" email)
 *   On hold   waiting for the bank transfer; the only message with the phone
 *             number in it, because the customer has to act
 *   Processing approved and being prepared, delivery 5 to 10 working days
 *             (set in migration 71)
 *   Processed ready to be shipped
 *   Shipped   out for delivery with our own vehicles and engineers; we call first
 *   Complete  complete, thank you
 *   Refunded  the funds reach the account in 3 to 5 working days
 *
 * The shop's own emails (Payment received, Processed, Shipped) take their
 * wording from the theme, so only WooCommerce's own emails are set here.
 *
 * Safe to run again. The previous settings are kept in the option
 * ef_order_emails_before_72.
 * Run: wp eval-file migration/72-order-email-wording.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$emails = array(
	'woocommerce_customer_on_hold_order_settings'   => array(
		'enabled'            => 'yes',
		'subject'            => 'Your {site_title} order #{order_number} is on hold',
		'heading'            => 'Your order is on hold',
		'additional_content' => 'Your order is on hold until your bank transfer clears; our bank details are below. Goods are not reserved until the payment has cleared. If anything is unclear, call us on 01280 811411.',
	),
	'woocommerce_customer_completed_order_settings' => array(
		'enabled'            => 'yes',
		'subject'            => 'Your {site_title} order #{order_number} is complete',
		'heading'            => 'Your order is complete',
		'additional_content' => 'Your order is complete. Thank you for your business. If you need parts or service for your equipment, just reply to this email.',
	),
	'woocommerce_customer_refunded_order_settings'  => array(
		'enabled'            => 'yes',
		'subject'            => 'Your {site_title} order #{order_number} has been refunded',
		'heading'            => 'Your order has been refunded',
		'additional_content' => 'The funds should reach your account within 3 to 5 working days.',
	),
);

$before = array();
foreach ( $emails as $option => $values ) {
	$before[ $option ] = get_option( $option, array() );
}

if ( ! get_option( 'ef_order_emails_before_72' ) ) {
	add_option( 'ef_order_emails_before_72', $before, '', false );
	echo "Backup saved in ef_order_emails_before_72.\n";
}

foreach ( $emails as $option => $values ) {
	$settings = (array) get_option( $option, array() );
	// Keep anything else that is stored there, such as a recipient list.
	update_option( $option, array_merge( $settings, $values ) );
	echo 'Set: ' . $option . "\n";
}

echo "\nThe shop's own emails take their wording from the theme:\n";
echo "  Payment received (card) - inc/emails/class-ef-email-payment-received.php\n";
echo "  Processed, Shipped      - inc/emails/class-ef-email-order-status.php\n";
