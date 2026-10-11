<?php
/**
 * Customer "Order shipped" email.
 *
 * Sent when an order moves to Shipped — the status has no core email, and it's the
 * point the customer most wants to hear about. Behaves like a native WooCommerce
 * email: it shows up under WooCommerce → Settings → Emails with its own
 * subject/heading/additional content, and it honours the HTML/plain preference.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'WC_Email' ) ) { return; }

/**
 * Order shipped email, sent to the customer.
 */
class EF_Email_Customer_Shipped_Order extends WC_Email {

	public function __construct() {
		$this->id             = 'customer_shipped_order';
		$this->customer_email = true;
		$this->title          = 'Order shipped';
		$this->description    = 'Sent to the customer when their order is marked Shipped, to tell them it is out for delivery.';

		$this->template_html  = 'emails/customer-shipped-order.php';
		$this->template_plain = 'emails/plain/customer-shipped-order.php';
		$this->template_base  = get_stylesheet_directory() . '/woocommerce/';

		$this->placeholders = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// Fires on any transition into Shipped; queued by WC_Emails like the core ones.
		add_action( 'woocommerce_order_status_shipped_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
	}

	public function get_default_subject() {
		return 'Your {site_title} order #{order_number} is on its way';
	}

	public function get_default_heading() {
		return 'Your order is on its way';
	}

	public function get_default_additional_content() {
		return 'Thanks for shopping with us.';
	}

	/**
	 * @param int      $order_id
	 * @param WC_Order $order
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                         = $order;
			$this->recipient                      = $order->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}
}
