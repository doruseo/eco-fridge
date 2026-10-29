<?php
/**
 * Card payments: a paid card order waits on Pending.
 *
 * The shop's decision (2026-10-29): when a customer pays by card, the money
 * still has to arrive in the bank account from the payment processor, so the
 * order waits on Pending until the shop picks it up, exactly like the fulfilment
 * flow in inc/order-statuses.php describes it (Pending = placed, not yet picked
 * up; Processing = picked up and being prepared).
 *
 * WooCommerce treats Pending as "not paid yet", so four things have to be
 * handled or a paid order would be mistreated:
 *
 *  1. The gateway calls payment_complete(), which would set Processing. The
 *     filter below returns Pending instead, while payment_complete() still
 *     records the transaction, the paid date and the stock reduction.
 *  2. "Hold stock (60 minutes)" cancels unpaid Pending orders. A paid order is
 *     never cancelled by that job any more.
 *  3. A Pending order is payable, so the customer could be shown a payment link
 *     for an order they have already paid. Paid orders are no longer payable.
 *  4. WooCommerce only sends the company "New order" email when an order moves
 *     into Processing, Completed or On hold, so a paid order resting on Pending
 *     would never be announced. It is sent here instead, once per order.
 *
 * The customer gets the "Payment received" email registered below, and the SMS
 * for Pending set in migration 70.
 *
 * Bank transfer and cheque orders are untouched: they still go to On hold.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The card gateways this applies to (AG WooCommerce Lloyds Cardnet). */
const EF_CARD_GATEWAYS = array( 'ag_cardnet_checkout', 'ag_lloyds_hosted_checkout' );

/**
 * Whether an order was paid through one of the card gateways.
 *
 * @param WC_Order|false $order Order.
 * @return bool
 */
function ef_is_card_order( $order ) {
	return is_a( $order, 'WC_Order' ) && in_array( $order->get_payment_method(), EF_CARD_GATEWAYS, true );
}

/* =========================================================================
 * 1. A successful card payment lands on Pending, not Processing.
 * ========================================================================= */
add_filter(
	'woocommerce_payment_complete_order_status',
	function ( $status, $order_id, $order = false ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}
		return ef_is_card_order( $order ) ? 'pending' : $status;
	},
	10,
	3
);

/* =========================================================================
 * 2. The "cancel unpaid orders" job leaves paid orders alone.
 *
 * wc_cancel_unpaid_orders() looks for Pending orders older than the hold-stock
 * minutes. Anything with a paid date has been through a gateway, so it is not an
 * abandoned checkout and must not be cancelled.
 * ========================================================================= */
add_filter(
	'woocommerce_cancel_unpaid_order',
	function ( $cancel, $order ) {
		if ( is_a( $order, 'WC_Order' ) && $order->get_date_paid() ) {
			return false;
		}
		return $cancel;
	},
	10,
	2
);

/* =========================================================================
 * 3. A paid order cannot be paid again.
 *
 * Pending is normally payable; dropping it for paid orders closes the
 * order-pay page and hides the payment link in emails and in the account area.
 * ========================================================================= */
add_filter(
	'woocommerce_valid_order_statuses_for_payment',
	function ( $statuses, $order ) {
		if ( is_a( $order, 'WC_Order' ) && $order->get_date_paid() ) {
			return array_values( array_diff( $statuses, array( 'pending', 'failed' ) ) );
		}
		return $statuses;
	},
	10,
	2
);

/* =========================================================================
 * 4. The two emails that announce a paid order resting on Pending.
 *
 * Both are sent from payment_complete() rather than from a status change:
 * the block checkout creates the order as Pending and the payment leaves it on
 * Pending, so there is no transition for WooCommerce to hang an email on. Each
 * one is tracked with order meta, so a repeated webhook or a later status
 * change cannot send it twice.
 *
 * Recipients of the company email are the ones set in WooCommerce → Settings →
 * Emails → New order (migration 68: mads@ and orders@).
 * ========================================================================= */
add_action(
	'woocommerce_payment_complete',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! ef_is_card_order( $order ) || ! $order->has_status( 'pending' ) ) {
			return;
		}

		$mailer = WC()->mailer();
		$sent   = false;

		if ( ! $order->get_meta( '_ef_new_order_email_sent' ) && isset( $mailer->emails['WC_Email_New_Order'] ) ) {
			$mailer->emails['WC_Email_New_Order']->trigger( $order_id, $order );
			$order->update_meta_data( '_ef_new_order_email_sent', current_time( 'mysql' ) );
			$sent = true;
		}

		if ( isset( $mailer->emails['EF_Email_Customer_Payment_Received'] ) ) {
			$mailer->emails['EF_Email_Customer_Payment_Received']->trigger( $order_id, $order );
			$sent = true;
		}

		if ( $sent ) {
			$order->save();
		}
	},
	20
);

/* The company email is announced once. When the shop later picks the order up,
 * WooCommerce would send "New order" again on the Pending → Processing move, so
 * it is switched off for orders that have already been announced. */
add_filter(
	'woocommerce_email_enabled_new_order',
	function ( $enabled, $order ) {
		if ( is_a( $order, 'WC_Order' ) && $order->get_meta( '_ef_new_order_email_sent' ) ) {
			return false;
		}
		return $enabled;
	},
	10,
	2
);

/* =========================================================================
 * 5. The customer's "Payment received" email, sent when a card order lands on
 *    Pending. WooCommerce has no email for that status, so the action is added
 *    to the mailer's list and the email class is registered below.
 * ========================================================================= */
add_filter(
	'woocommerce_email_actions',
	function ( $actions ) {
		$actions[] = 'woocommerce_order_status_pending';
		return $actions;
	}
);

add_filter(
	'woocommerce_email_classes',
	function ( $emails ) {
		require_once __DIR__ . '/emails/class-ef-email-payment-received.php';
		if ( class_exists( 'EF_Email_Customer_Payment_Received' ) ) {
			$emails['EF_Email_Customer_Payment_Received'] = new EF_Email_Customer_Payment_Received();
		}
		return $emails;
	},
	20
);
