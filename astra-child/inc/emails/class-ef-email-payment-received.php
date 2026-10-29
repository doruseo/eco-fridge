<?php
/**
 * Customer email: "Payment received", sent when a card payment has gone
 * through and the order waits on Pending (see inc/card-payments.php).
 *
 * WooCommerce has no email for the Pending status, and the one for Processing
 * now only goes out later, when the shop picks the order up, so without this
 * email a customer who paid by card would hear nothing.
 *
 * It is only sent for an order that really was paid by card: any other order
 * sitting on Pending (an abandoned checkout, an order an administrator moved
 * back) must not get a payment confirmation.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'EF_Email_Order_Status' ) ) {
	require_once __DIR__ . '/class-ef-email-order-status.php';
}

if ( ! class_exists( 'EF_Email_Order_Status' ) ) { return; }

/**
 * Customer: "Payment received" – card paid, order waiting to be picked up.
 */
class EF_Email_Customer_Payment_Received extends EF_Email_Order_Status {

	public function __construct() {
		$this->id             = 'customer_payment_received';
		$this->customer_email = true;
		$this->ef_status      = 'pending';
		$this->title          = 'Payment received (card)';
		$this->description    = 'Sent to the customer when a card payment has gone through. The order then waits on Pending until the payment reaches the bank account and the order is picked up.';
		parent::__construct();
	}

	public function get_default_subject() {
		return 'We have received your payment for {site_title} order #{order_number}';
	}

	public function get_default_heading() {
		return 'Payment received';
	}

	public function get_default_additional_content() {
		return 'Thanks for shopping with us.';
	}

	public function get_intro() {
		return array(
			'Thank you – your card payment has gone through and your order is now with our team.',
			'We are checking stock and waiting for the payment to reach our account, which usually takes 3 to 4 working days. We will email you again as soon as your order has been approved and is being prepared.',
			'Here is a summary of your order:',
		);
	}

	/**
	 * Only for an order that was actually paid by card.
	 *
	 * @param int            $order_id Order ID.
	 * @param WC_Order|false $order    Order.
	 */
	public function trigger( $order_id, $order = false ) {
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! function_exists( 'ef_is_card_order' ) || ! ef_is_card_order( $order ) || ! $order->get_date_paid() ) {
			return;
		}

		// Once per order: the payment can be confirmed again by a repeated webhook.
		if ( $order->get_meta( '_ef_payment_received_email_sent' ) ) {
			return;
		}

		parent::trigger( $order_id, $order );

		$order->update_meta_data( '_ef_payment_received_email_sent', current_time( 'mysql' ) );
		$order->save();
	}
}
