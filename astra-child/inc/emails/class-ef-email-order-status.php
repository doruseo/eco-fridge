<?php
/**
 * Base for the shop's own order-status emails (Processed, Shipped, and the
 * Shipped / Completed notices to the company).
 *
 * Each email is a native WooCommerce email: it shows up under WooCommerce →
 * Settings → Emails with its own enable switch, subject, heading, additional
 * content and HTML/plain choice; company emails also get a Recipient(s) field.
 * One template pair (woocommerce/emails/ef-order-status.php + plain/) renders
 * them all: greeting, the email's own intro lines, then WooCommerce's order
 * details, meta and customer details.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'WC_Email' ) ) { return; }

/**
 * An email sent when an order enters a status.
 */
abstract class EF_Email_Order_Status extends WC_Email {

	/** Order status (without `wc-`) that sends the email. */
	protected $ef_status = '';

	/**
	 * Sets up the email; subclasses set id, title, description, $ef_status and
	 * $customer_email before calling this.
	 */
	public function __construct() {
		$this->template_html  = 'emails/ef-order-status.php';
		$this->template_plain = 'emails/plain/ef-order-status.php';
		$this->template_base  = get_stylesheet_directory() . '/woocommerce/';

		$this->placeholders = array(
			'{order_date}'   => '',
			'{order_number}' => '',
			'{customer}'     => '',
		);

		// Fires on any transition into the status; queued by WC_Emails like the core ones.
		add_action( 'woocommerce_order_status_' . $this->ef_status . '_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();

		if ( ! $this->customer_email ) {
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}
	}

	/**
	 * The paragraphs shown above the order details.
	 *
	 * @return string[]
	 */
	abstract public function get_intro();

	/**
	 * @param int            $order_id Order ID.
	 * @param WC_Order|false $order    Order.
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                         = $order;
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{customer}']     = trim( $order->get_formatted_billing_full_name() ) ? $order->get_formatted_billing_full_name() : $order->get_billing_email();
			if ( $this->customer_email ) {
				$this->recipient = $order->get_billing_email();
			}
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Template arguments shared by the HTML and plain versions.
	 *
	 * @param bool $plain_text Plain text version.
	 * @return array
	 */
	protected function template_args( $plain_text ) {
		return array(
			'order'              => $this->object,
			'email_heading'      => $this->get_heading(),
			'intro'              => array_map( array( $this, 'format_string' ), $this->get_intro() ),
			'greeting'           => $this->customer_email,
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => ! $this->customer_email,
			'plain_text'         => $plain_text,
			'email'              => $this,
		);
	}

	public function get_content_html() {
		return wc_get_template_html( $this->template_html, $this->template_args( false ), '', $this->template_base );
	}

	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, $this->template_args( true ), '', $this->template_base );
	}

	/** Company emails get a Recipient(s) field, like WooCommerce's "New order". */
	public function init_form_fields() {
		parent::init_form_fields();
		if ( $this->customer_email ) {
			return;
		}
		$this->form_fields = array_merge(
			array_slice( $this->form_fields, 0, 1, true ),
			array(
				'recipient' => array(
					'title'       => 'Recipient(s)',
					'type'        => 'text',
					/* translators: %s: admin email */
					'description' => sprintf( 'Enter recipients (comma separated) for this email. Defaults to %s.', '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
			),
			array_slice( $this->form_fields, 1, null, true )
		);
	}
}

/**
 * Customer: "Order processed" – ready for delivery.
 */
class EF_Email_Customer_Processed_Order extends EF_Email_Order_Status {

	public function __construct() {
		$this->id             = 'customer_processed_order';
		$this->customer_email = true;
		$this->ef_status      = 'processed';
		$this->title          = 'Order processed';
		$this->description    = 'Sent to the customer when their order is marked Processed: it has been prepared and is ready for delivery.';
		parent::__construct();
	}

	public function get_default_subject() {
		return 'Your {site_title} order #{order_number} has been processed';
	}

	public function get_default_heading() {
		return 'Your order has been processed';
	}

	public function get_default_additional_content() {
		return 'Thanks for shopping with us.';
	}

	public function get_intro() {
		return array(
			'Your order has been processed and is ready for delivery. We will email you again as soon as it is on its way.',
			'Here is a summary of your order:',
		);
	}
}

/**
 * Customer: "Order shipped" – out for delivery.
 */
class EF_Email_Customer_Shipped_Order extends EF_Email_Order_Status {

	public function __construct() {
		$this->id             = 'customer_shipped_order';
		$this->customer_email = true;
		$this->ef_status      = 'shipped';
		$this->title          = 'Order shipped';
		$this->description    = 'Sent to the customer when their order is marked Shipped, to tell them it is out for delivery.';
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

	public function get_intro() {
		return array(
			'Good news — your order is on its way to you.',
			"Here's a reminder of what's coming:",
		);
	}
}

/**
 * Company: an order has been shipped.
 */
class EF_Email_Admin_Shipped_Order extends EF_Email_Order_Status {

	public function __construct() {
		$this->id             = 'admin_shipped_order';
		$this->customer_email = false;
		$this->ef_status      = 'shipped';
		$this->title          = 'Order shipped (company)';
		$this->description    = 'Sent to the company when an order is marked Shipped.';
		parent::__construct();
	}

	public function get_default_subject() {
		return '[{site_title}]: Order #{order_number} shipped';
	}

	public function get_default_heading() {
		return 'Order shipped: #{order_number}';
	}

	public function get_default_additional_content() {
		return '';
	}

	public function get_intro() {
		return array( 'Order #{order_number} for {customer} has been marked Shipped and is out for delivery.' );
	}
}

/**
 * Company: an order has been completed (delivered).
 */
class EF_Email_Admin_Completed_Order extends EF_Email_Order_Status {

	public function __construct() {
		$this->id             = 'admin_completed_order';
		$this->customer_email = false;
		$this->ef_status      = 'completed';
		$this->title          = 'Order complete (company)';
		$this->description    = 'Sent to the company when an order is marked Complete (delivered).';
		parent::__construct();
	}

	public function get_default_subject() {
		return '[{site_title}]: Order #{order_number} complete';
	}

	public function get_default_heading() {
		return 'Order complete: #{order_number}';
	}

	public function get_default_additional_content() {
		return '';
	}

	public function get_intro() {
		return array( 'Order #{order_number} for {customer} has been marked Complete (delivered).' );
	}
}
