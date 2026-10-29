<?php
/**
 * 70 — SMS Alert order message templates (Doru, 2026-10-29).
 *
 * The plugin ships Indian defaults: amounts in "Rs.", a "Powered by
 * www.smsalert.co.in" footer, a Pending text that asks the customer to finish
 * paying and a Completed text that says the order has been dispatched. None of
 * that fits this shop, so every template we send is written here.
 *
 * The wording follows the shop's own flow (astra-child/inc/order-statuses.php):
 *   Pending    - order placed, payment taken, stock and funds being checked
 *   Processing - order picked up by the shop, being prepared
 *   Processed  - ready for delivery
 *   Shipped    - out for delivery
 *   On hold    - waiting for a bank transfer
 *   Complete   - delivered
 * and the delivery times published on the product pages and in the Terms:
 * bank transfer 5-8 working days, card 8-10 working days from cleared payment.
 *
 * Eco-Fridge do not want to be called non-stop, so the phone number appears
 * only in the On hold message, where the customer has to act (send the
 * transfer). Deliveries are made with the shop's own vehicles and engineers,
 * so the Shipped message promises a call instead of courier tracking. Every template uses [billing_first_name], never [first_name],
 * which is empty on guest orders, and prints the currency itself, because
 * [order_amount] renders as a bare number.
 *
 * It also sets the notification switches the shop asked for: no SMS at all to
 * the company (Mads reads the order emails, and credits are not spent on
 * texting him), no sign-up alert (the shop has no customer accounts) and no
 * abandoned-cart SMS.
 *
 * Safe to run again. The previous templates are kept in the option
 * ef_smsalert_message_before_70 and the previous switches in
 * ef_smsalert_switches_before_70.
 * Run: wp eval-file migration/70-sms-alert-templates.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$messages = (array) get_option( 'smsalert_message', array() );

if ( ! get_option( 'ef_smsalert_message_before_70' ) ) {
	add_option( 'ef_smsalert_message_before_70', $messages, '', false );
	echo "Backup saved in ef_smsalert_message_before_70.\n";
}

// Customer messages, one per order status. Sender ID is ECOFRIDGE, so the
// shop name is repeated in the text only where it reads naturally.
$customer = array(
	'sms_body_pending'            => 'Eco-Fridge: payment for order #[order_id] (£[order_amount]) is confirmed. We are now checking stock and clearing the payment, usually 3 to 4 working days.',
	'sms_body_processing'         => 'Eco-Fridge: your order #[order_id] is approved and we are preparing it. Delivery is usually 5 to 10 working days.',
	'sms_body_processed'          => 'Eco-Fridge: your order #[order_id] is ready to be shipped. We will text you when it is dispatched.',
	'sms_body_shipped'            => 'Eco-Fridge: your order #[order_id] goes out for delivery with our own vehicles and engineers. We will contact you before we deliver.',
	'sms_body_on-hold'            => 'Eco-Fridge: thanks [billing_first_name], order #[order_id] for £[order_amount] is on hold until your bank transfer clears; the bank details are in your email. Help: 01280 811411',
	'sms_body_completed'          => 'Eco-Fridge: your order #[order_id] is complete. Thank you.',
	'sms_body_refunded'           => 'Eco-Fridge: order #[order_id] has been refunded (£[order_amount]). The funds should reach your account within 3 to 5 working days.',
	'sms_body_partially_refunded' => 'Eco-Fridge: part of your order #[order_id] has been refunded. The funds should reach your account within 3 to 5 working days.',
	'sms_body_cancelled'          => 'Eco-Fridge: order #[order_id] has been cancelled. If this is unexpected, please reply to our email and we will look into it.',
	'sms_body_failed'             => 'Eco-Fridge: the payment for order #[order_id] did not go through, so the order was not placed. Your items are still in your basket.',
	'sms_body_returned'           => 'Eco-Fridge: your return for order #[order_id] has been received. We will email you once it has been processed.',
	'sms_body_new_note'           => 'Eco-Fridge: a note has been added to your order #[order_id]: [note]',
);

// Internal messages. They are only sent if a company mobile number is set in
// the plugin (SMS Alert > Notification > Admin phone), which is empty for now.
$admin = array(
	'admin_sms_body_pending'    => 'Eco-Fridge: new order #[order_id], £[order_amount], [payment_method_title], [billing_first_name] [billing_phone]. Payment taken, awaiting stock and funds check.',
	'admin_sms_body_processing' => 'Eco-Fridge: order #[order_id], £[order_amount], [payment_method_title], [billing_first_name] [billing_phone] is approved and being prepared.',
	'admin_sms_body_processed'  => 'Eco-Fridge: order #[order_id] is ready to be shipped.',
	'admin_sms_body_shipped'    => 'Eco-Fridge: order #[order_id] has been dispatched.',
	'admin_sms_body_on-hold'    => 'Eco-Fridge: order #[order_id], £[order_amount], bank transfer, [billing_first_name] [billing_phone] is on hold until the payment clears.',
	'admin_sms_body_completed'  => 'Eco-Fridge: order #[order_id] is complete.',
	'admin_sms_body_cancelled'  => 'Eco-Fridge: order #[order_id], £[order_amount] has been cancelled.',
	'admin_sms_body_failed'     => 'Eco-Fridge: the payment for order #[order_id], £[order_amount] failed.',
	'admin_sms_body_returned'   => 'Eco-Fridge: order #[order_id] has been returned.',
	'admin_sms_body_refunded'   => 'Eco-Fridge: order #[order_id], £[order_amount] has been refunded.',
);

$changed = 0;
foreach ( array_merge( $customer, $admin ) as $key => $text ) {
	if ( ! isset( $messages[ $key ] ) || $messages[ $key ] !== $text ) {
		$messages[ $key ] = $text;
		++$changed;
	}
}

// Anything we have not rewritten (low stock, sign-up, OTP…) keeps its text but
// loses the plugin's footer and the rupee amounts.
foreach ( $messages as $key => $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		continue;
	}
	$clean = preg_replace( '/\s*Powered by\s*' . "\n*" . '\s*www\.smsalert\.co\.in\s*/i', '', $text );
	$clean = str_replace( array( 'Rs. ', 'Rs.' ), '£', $clean );
	$clean = trim( $clean );
	if ( $clean !== $text ) {
		$messages[ $key ] = $clean;
		++$changed;
	}
}

update_option( 'smsalert_message', $messages );

echo "Templates written: {$changed} fields changed.\n";
echo "Customer messages now in place:\n";
foreach ( array_keys( $customer ) as $key ) {
	$status = str_replace( 'sms_body_', '', $key );
	echo '  ' . str_pad( $status, 20 ) . mb_strlen( $messages[ $key ] ) . " chars (template)\n";
}

/* =========================================================================
 * Notification switches (shop's decision, 2026-10-29).
 *   - No SMS to the company at all: Mads reads the order emails, and credits
 *     are not spent on texting him. That covers the per-status alerts, the
 *     stock alerts and the sign-up alert (there are no customer accounts).
 *   - No abandoned-cart SMS: the customer emails already cover that, and the
 *     database holds thousands of real customer numbers.
 * Which statuses the CUSTOMER is texted about stays as set in the plugin.
 * ========================================================================= */
$general = (array) get_option( 'smsalert_general', array() );
$cart    = (array) get_option( 'smsalert_abandoned_cart', array() );

if ( ! get_option( 'ef_smsalert_switches_before_70' ) ) {
	add_option(
		'ef_smsalert_switches_before_70',
		array(
			'general'        => $general,
			'abandoned_cart' => $cart,
		),
		'',
		false
	);
}

$company_alerts = 0;
foreach ( $general as $key => $value ) {
	$is_company_alert = 0 === strpos( $key, 'admin_notification_' )
		|| in_array( $key, array( 'admin_registration_msg', 'admin_low_stock_msg', 'admin_out_of_stock_msg' ), true );
	if ( $is_company_alert && 'off' !== $value ) {
		$general[ $key ] = 'off';
		++$company_alerts;
	}
}
update_option( 'smsalert_general', $general );

$cart_alerts = 0;
foreach ( array( 'customer_notify', 'cart_exit_intent_status' ) as $key ) {
	if ( isset( $cart[ $key ] ) && 'off' !== $cart[ $key ] && '' !== $cart[ $key ] ) {
		$cart[ $key ] = 'off';
		++$cart_alerts;
	}
}
update_option( 'smsalert_abandoned_cart', $cart );

echo "Company alerts switched off: {$company_alerts}. Abandoned-cart SMS switched off: {$cart_alerts}.
";
