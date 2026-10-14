<?php
/**
 * The shop's order-status emails (Processed, Shipped, company notices) — HTML.
 *
 * Not a WooCommerce core template override: it belongs to the child theme's own
 * emails (inc/emails/class-ef-email-order-status.php) and is modelled on
 * WooCommerce's customer-completed-order.php.
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var string[] $intro         Paragraphs above the order details.
 * @var bool     $greeting      "Hi <name>," first (customer emails).
 * @var string   $additional_content
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var WC_Email $email
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$ef_email_improvements = class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' )
	&& \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $ef_email_improvements ? '<div class="email-introduction">' : ''; ?>
<?php if ( $greeting ) : ?>
<p>
	<?php
	if ( ! empty( $order->get_billing_first_name() ) ) {
		printf( 'Hi %s,', esc_html( $order->get_billing_first_name() ) );
	} else {
		echo 'Hi,';
	}
	?>
</p>
<?php endif; ?>
<?php foreach ( $intro as $ef_paragraph ) : ?>
<p><?php echo esc_html( $ef_paragraph ); ?></p>
<?php endforeach; ?>
<?php echo $ef_email_improvements ? '</div>' : ''; ?>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $ef_email_improvements ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $ef_email_improvements ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
