<?php
/**
 * 71 — the customer "Processing" email now says the order was approved.
 *
 * Card payments rest on Pending until the shop picks the order up (see
 * astra-child/inc/card-payments.php), and the customer already had an email
 * saying the payment went through. By the time the order reaches Processing it
 * has been approved and is being prepared, so WooCommerce's default wording,
 * "Your order has been received", is both late and wrong.
 *
 * The new wording matches the SMS for the same status (migration 70): approved
 * and being prepared, delivery usually 5 to 10 working days.
 *
 * Safe to run again. The previous settings are kept in the option
 * ef_processing_email_before_71.
 * Run: wp eval-file migration/71-processing-email-wording.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$option   = 'woocommerce_customer_processing_order_settings';
$settings = (array) get_option( $option, array() );

if ( ! get_option( 'ef_processing_email_before_71' ) ) {
	add_option( 'ef_processing_email_before_71', $settings, '', false );
	echo "Backup saved in ef_processing_email_before_71.\n";
}

$settings['enabled']            = 'yes';
$settings['subject']            = 'Your {site_title} order #{order_number} has been approved';
$settings['heading']            = 'Your order has been approved';
$settings['additional_content'] = 'Delivery is usually 5 to 10 working days from cleared payment. We will email you again when your order is ready to be shipped.';

update_option( $option, $settings );

echo "Processing email wording updated:\n";
echo '  subject: ' . $settings['subject'] . "\n";
echo '  heading: ' . $settings['heading'] . "\n";
