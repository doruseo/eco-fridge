<?php
/**
 * "Review request" email (includes/reviews.php).
 *
 * A native WooCommerce email (WooCommerce → Settings → Emails): enable switch,
 * subject, heading, additional content, the delay after completion and
 * HTML/plain.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asks the buyer to review the products of a completed order.
 */
class EFB_Email_Review_Request extends WC_Email {

	public function __construct() {
		$this->id             = 'efb_email_review_request';
		$this->customer_email = true;
		$this->title          = 'Review request';
		$this->description    = 'Sent to the buyer a few days after an order is completed (live site only), with a personal link per product to leave a review without an account. Reviews wait for approval under Products → Reviews. Order actions → "Send review request" sends it at once.';
		$this->template_html  = 'emails/review-request.php';
		$this->template_plain = 'emails/plain/review-request.php';
		$this->template_base  = EFB_DIR . 'templates/';
		$this->placeholders   = array(
			'{first_name}'   => '',
			'{order_number}' => '',
		);
		parent::__construct();
	}

	public function get_default_subject() {
		return 'How is your new {site_title} equipment?';
	}

	public function get_default_heading() {
		return 'Tell us how it is going';
	}

	public function get_default_additional_content() {
		return 'Any problem with your order? Call us on 01280 811411 or reply to this email – we will sort it out.';
	}

	/**
	 * Sends the email for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return bool Sent.
	 */
	public function trigger( $order ) {
		$this->setup_locale();
		$this->object                         = $order;
		$this->recipient                      = $order->get_billing_email();
		$this->placeholders['{first_name}']   = $order->get_billing_first_name();
		$this->placeholders['{order_number}'] = $order->get_order_number();
		$sent                                 = false;
		if ( $this->get_recipient() && $this->items() ) {
			$sent = (bool) $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
		$this->restore_locale();
		return $sent;
	}

	/**
	 * The order's products not reviewed yet, once each (variations → their product).
	 *
	 * @return array[] name, image, link (review link)
	 */
	protected function items() {
		$items = array();
		foreach ( $this->object->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();
			$product    = wc_get_product( $product_id );
			if ( ! $product || isset( $items[ $product_id ] ) || 'publish' !== $product->get_status() || ! comments_open( $product_id ) || efb_rv_reviewed( $this->object->get_id(), $product_id ) ) {
				continue;
			}
			$items[ $product_id ] = array(
				'name'  => $product->get_name(),
				'image' => (string) wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ),
				'link'  => efb_rv_link( $this->object, $product_id ),
			);
		}
		return array_values( $items );
	}

	/**
	 * Template arguments.
	 *
	 * @param bool $plain_text Plain text version.
	 * @return array
	 */
	protected function template_args( $plain_text ) {
		return array(
			'email_heading'      => $this->get_heading(),
			'first_name'         => $this->object->get_billing_first_name(),
			'order_number'       => $this->object->get_order_number(),
			'items'              => $this->items(),
			'optout_url'         => efb_rv_optout_link( $this->object ),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
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

	public function init_form_fields() {
		parent::init_form_fields();
		$this->form_fields['delay_days'] = array(
			'title'             => 'Send after (days)',
			'type'              => 'number',
			'default'           => '7',
			'desc_tip'          => true,
			'description'       => 'Days after the order is marked Completed.',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => '1',
			),
		);
	}
}
