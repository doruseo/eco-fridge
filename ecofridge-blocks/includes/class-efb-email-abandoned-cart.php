<?php
/**
 * "Abandoned cart" reminder email (includes/abandoned-carts.php).
 *
 * A native WooCommerce email (WooCommerce → Settings → Emails): enable switch,
 * subject and heading for the first and the second reminder, additional
 * content, the two delays and HTML/plain.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cart reminder sent to the customer.
 */
class EFB_Email_Abandoned_Cart extends WC_Email {

	/** Reminder number being sent (1 or 2). */
	protected $number = 1;

	public function __construct() {
		$this->id             = 'efb_email_abandoned_cart';
		$this->customer_email = true;
		$this->title          = 'Abandoned cart';
		$this->description    = 'Reminds customers (signed in, or guests who typed their email at checkout) about the products left in their cart, with a link back to their cart. Sent at most twice; WooCommerce → Abandoned carts lists the carts.';
		$this->template_html  = 'emails/abandoned-cart.php';
		$this->template_plain = 'emails/plain/abandoned-cart.php';
		$this->template_base  = EFB_DIR . 'templates/';
		$this->placeholders   = array( '{first_name}' => '' );
		parent::__construct();
	}

	public function get_default_subject() {
		return 'You left something in your {site_title} cart';
	}

	public function get_default_heading() {
		return 'Your cart is waiting for you';
	}

	/** Second reminder subject. */
	public function get_default_subject_second() {
		return 'Still thinking it over? Your {site_title} cart is saved';
	}

	public function get_default_additional_content() {
		return 'Questions about sizes, delivery or installation? Call us on 01280 811411 or reply to this email – we are happy to help.';
	}

	public function get_subject() {
		if ( 2 === $this->number ) {
			return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->get_option( 'subject_second', $this->get_default_subject_second() ) ), $this->object, $this );
		}
		return parent::get_subject();
	}

	/**
	 * Sends a reminder.
	 *
	 * @param object $row    Cart row.
	 * @param int    $number 1 or 2.
	 * @return bool Sent.
	 */
	public function trigger( $row, $number = 1 ) {
		$this->setup_locale();
		$this->object                       = $row;
		$this->number                       = (int) $number;
		$this->recipient                    = $row->email;
		$this->placeholders['{first_name}'] = $row->first_name;
		$sent                               = false;
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$sent = (bool) $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
		$this->restore_locale();
		return $sent;
	}

	/**
	 * The cart's products with their current price.
	 *
	 * @return array[] name, quantity, price (formatted), image, link, extras
	 */
	protected function items() {
		$items = array();
		$defs  = function_exists( 'ef_addon_defs' ) ? ef_addon_defs() : array();
		foreach ( (array) json_decode( $this->object->cart, true ) as $item ) {
			$product = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
			if ( ! $product ) {
				continue;
			}
			$extras = array();
			foreach ( (array) $item['extra'] as $key => $on ) {
				$addon = substr( $key, 3 );
				if ( $on && isset( $defs[ $addon ] ) ) {
					$extras[] = $defs[ $addon ]['fee']; // plain-text name, e.g. "Removal & Disposal".
				}
			}
			$items[] = array(
				'name'     => $product->get_name(),
				'quantity' => (int) $item['quantity'],
				'price'    => wc_price( (float) $product->get_price() * (int) $item['quantity'] ),
				'image'    => (string) wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ),
				'link'     => $product->get_permalink(),
				'extras'   => $extras,
			);
		}
		return $items;
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
			'first_name'         => $this->object->first_name,
			'items'              => $this->items(),
			'total'              => wc_price( $this->object->total ),
			'restore_url'        => efb_ac_link( $this->object ),
			'unsubscribe_url'    => efb_ac_link( $this->object, 'unsubscribe' ),
			'second'             => 2 === $this->number,
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
		$extra = array(
			'subject_second' => array(
				'title'       => 'Subject (second reminder)',
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => 'Available placeholders: {site_title}, {first_name}',
				'placeholder' => $this->get_default_subject_second(),
				'default'     => '',
			),
			'delay_first'    => array(
				'title'       => 'First reminder after (hours)',
				'type'        => 'number',
				'default'     => '1',
				'desc_tip'    => true,
				'description' => 'Hours without activity on the cart before the first reminder.',
				'custom_attributes' => array( 'min' => '0.25', 'step' => '0.25' ),
			),
			'delay_second'   => array(
				'title'       => 'Second reminder after (hours)',
				'type'        => 'number',
				'default'     => '24',
				'desc_tip'    => true,
				'description' => 'Hours without activity before the second reminder; 0 = no second reminder.',
				'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
			),
		);
		$fields = array();
		foreach ( $this->form_fields as $key => $field ) {
			$fields[ $key ] = $field;
			if ( 'subject' === $key ) {
				$fields['subject_second'] = $extra['subject_second'];
			}
		}
		$this->form_fields = $fields + array(
			'delay_first'  => $extra['delay_first'],
			'delay_second' => $extra['delay_second'],
		);
	}
}
