<?php
/**
 * 55 — Order emails switched on for the whole flow.
 *
 *  Customer: On hold (bank transfer order received), Processing (card order
 *  received / payment confirmed), Processed, Shipped, Complete, Cancelled,
 *  Refunded, Failed payment, and the Abandoned cart reminder.
 *  Company: New order (paid or bank transfer), Shipped, Complete – plus
 *  WooCommerce's Cancelled / Failed order notices, left on.
 *
 * Only the "enabled" switch is set; recipients and the sender address are set
 * after the move to the live server. The Complete email says "complete" (delivered)
 * rather than WooCommerce's "on its way". Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$emails = array(
	'customer_on_hold_order',
	'customer_processing_order',
	'customer_processed_order',
	'customer_shipped_order',
	'customer_completed_order',
	'customer_cancelled_order',
	'customer_refunded_order',
	'customer_failed_order',
	'efb_email_abandoned_cart',
	'new_order',
	'admin_shipped_order',
	'admin_completed_order',
);

foreach ( $emails as $id ) {
	$option   = 'woocommerce_' . $id . '_settings';
	$settings = (array) get_option( $option, array() );
	if ( 'yes' !== ( $settings['enabled'] ?? '' ) ) {
		$settings['enabled'] = 'yes';
		update_option( $option, $settings );
		WP_CLI::log( "Enabled: $id" );
	}
}

// WooCommerce's "Completed order" says "is on its way" – in this flow that is the
// Shipped email; Complete means delivered.
$option   = 'woocommerce_customer_completed_order_settings';
$settings = (array) get_option( $option, array() );
$defaults = array(
	'subject'            => 'Your {site_title} order #{order_number} is complete',
	'heading'            => 'Your order is complete',
	'additional_content' => 'Your order has been delivered. Thank you for choosing ECO-Fridge – if you need anything for your new equipment, call us on 01280 811411.',
);
foreach ( $defaults as $key => $value ) {
	if ( empty( $settings[ $key ] ) ) {
		$settings[ $key ] = $value;
	}
}
update_option( $option, $settings );

WP_CLI::success( 'Order emails enabled.' );
