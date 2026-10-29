<?php
/**
 * 73 — card payments stay on Processing; the warehouse step is the new Preparing.
 *
 * Migration 70 and 71 were written while card payments were being pushed onto
 * Pending. That was dropped: the AG Lloyds Cardnet gateway and its webhook check
 * `has_status( array( 'processing', 'completed', 'on-hold' ) )` in a dozen places
 * to decide whether an order has been paid, and a future update could add more,
 * so a paid order resting on Pending is a standing risk. WooCommerce reads
 * Processing as paid, and so does the gateway, so the order stays there and the
 * warehouse step the shop wanted is the new Preparing status (registered in
 * astra-child/inc/order-statuses.php).
 *
 * What the customer now hears, status by status:
 *   Pending    nothing. It only means "payment not taken", an abandoned checkout.
 *   Processing payment confirmed, stock and funds being checked, 3 to 4 working days
 *   Preparing  approved, being prepared, delivery usually 5 to 10 working days
 *   Processed  ready to be shipped
 *   Shipped    out for delivery with our own vehicles and engineers
 *   Complete   complete, thank you
 *   On hold    waiting for the bank transfer (bank details in the email)
 *
 * Safe to run again. Backups: ef_smsalert_message_before_73 and
 * ef_processing_email_before_73.
 * Run: wp eval-file migration/73-preparing-status-messages.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

/* ---------- 1. SMS texts move one step along the flow ---------- */

$messages = (array) get_option( 'smsalert_message', array() );

if ( ! get_option( 'ef_smsalert_message_before_73' ) ) {
	add_option( 'ef_smsalert_message_before_73', $messages, '', false );
	echo "SMS backup saved in ef_smsalert_message_before_73.\n";
}

$texts = array(
	// What Pending used to say is what Processing says now: the card payment went
	// through and we are checking stock and waiting for the funds.
	'sms_body_processing' => 'Eco-Fridge: payment for order #[order_id] (£[order_amount]) is confirmed. We are now checking stock and clearing the payment, usually 3 to 4 working days.',
	// What Processing used to say is what Preparing says now.
	'sms_body_preparing'  => 'Eco-Fridge: your order #[order_id] is approved and we are preparing it. Delivery is usually 5 to 10 working days.',
	// Pending is an unpaid checkout again, so nothing is promised there.
	'sms_body_pending'    => 'Eco-Fridge: order #[order_id] has been started but the payment has not gone through yet. Your items are still in your basket.',
);

$changed = 0;
foreach ( $texts as $key => $text ) {
	if ( ! isset( $messages[ $key ] ) || $messages[ $key ] !== $text ) {
		$messages[ $key ] = $text;
		++$changed;
	}
}
$messages['admin_sms_body_preparing'] = 'Eco-Fridge: order #[order_id] is approved and being prepared.';
update_option( 'smsalert_message', $messages );
echo "SMS texts set: {$changed} changed.\n";

/* ---------- 2. Which statuses text the customer ---------- */

$general = (array) get_option( 'smsalert_general', array() );
$statuses = (array) ( $general['order_status'] ?? array() );

// No SMS for Pending: it is the unpaid state. Preparing gets one instead.
$statuses['pending']   = '';
$statuses['preparing'] = 'preparing';

$general['order_status'] = $statuses;
update_option( 'smsalert_general', $general );

$on = array();
foreach ( $statuses as $slug => $value ) {
	if ( '' !== $value ) {
		$on[] = $slug;
	}
}
echo 'Customer SMS on: ' . implode( ', ', $on ) . "\n";

/* ---------- 3. The Processing email matches its SMS again ---------- */

$option   = 'woocommerce_customer_processing_order_settings';
$settings = (array) get_option( $option, array() );

if ( ! get_option( 'ef_processing_email_before_73' ) ) {
	add_option( 'ef_processing_email_before_73', $settings, '', false );
}

$settings['enabled']            = 'yes';
$settings['subject']            = 'We have received your payment for {site_title} order #{order_number}';
$settings['heading']            = 'Payment received';
$settings['additional_content'] = 'We are checking stock and waiting for the payment to reach our account, which usually takes 3 to 4 working days. We will email you again as soon as your order has been approved and is being prepared.';

update_option( $option, $settings );

echo "Processing email: " . $settings['subject'] . "\n";
echo "Preparing email comes from the theme (EF_Email_Customer_Preparing_Order).\n";
