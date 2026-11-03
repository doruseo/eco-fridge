<?php
/**
 * The two delivery questions the shop asks at checkout.
 *
 * The old shop asked them through the WooCommerce Checkout Manager plugin
 * (option `wc_fields_additional`) and the delivery team relies on the answers:
 * whether the customer has measured their doorways, and how many steps there
 * are at the delivery address. Together they decide whether a cabinet can be
 * taken inside or has to be left at the kerb, and the team tells the customer
 * before the van leaves. The answers were on 4.436 and 3.877 of the old
 * orders, so this is not a leftover: it is how the shop works.
 *
 * Carried over exactly as they were, including the required flag, the
 * YES/NO choice, the free-text answer and the order in which they appear:
 *
 *   check_doors  select, required  "We recomend you to check the height of
 *                                   your doors through the final fridge
 *                                   position. Have you checked the size of
 *                                   your doors? (T&C apply)"
 *   stairs       text, required    "How many door steps? Curb side delivery
 *                                   will be at the drivers discretion
 *                                   (T&C apply)"
 *
 * The wording keeps the original's spelling of "recomend", so the checkout
 * reads the way the client knows it; `efb_checkout_doors_label` and
 * `efb_checkout_steps_label` are there to change it without touching code.
 *
 * Our checkout is the WooCommerce checkout block, so the fields are registered
 * through the Additional Checkout Fields API rather than the classic
 * `woocommerce_checkout_fields` filter, which the block ignores. That API
 * stores its values under namespaced meta keys, so every saved answer is also
 * mirrored to `check_doors` and `stairs`, the keys the old shop used: the
 * orders imported from it carry those keys, and the admin screen, the emails
 * and any later export then read old and new orders the same way.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

const EFB_DOORS_FIELD_ID = 'ecofridge/doors-checked';
const EFB_STEPS_FIELD_ID = 'ecofridge/door-steps';
const EFB_DOORS_META_KEY = 'check_doors';
const EFB_STEPS_META_KEY = 'stairs';

/**
 * The question about doorway height, as the old shop worded it.
 *
 * @return string
 */
function efb_checkout_doors_label() {
	return (string) apply_filters(
		'efb_checkout_doors_label',
		'We recomend you to check the height of your doors through the final fridge position. Have you checked the size of your doors? (T&C apply)'
	);
}

/**
 * The question about steps at the delivery address, as the old shop worded it.
 *
 * @return string
 */
function efb_checkout_steps_label() {
	return (string) apply_filters(
		'efb_checkout_steps_label',
		'How many door steps? Curb side delivery will be at the drivers discretion (T&C apply)'
	);
}

/* ---------------------------------------------------------------------------
 * The fields themselves.
 * ------------------------------------------------------------------------- */

add_action(
	'woocommerce_init',
	static function () {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		// Both questions are about getting the goods through a doorway, so they
		// belong to the order, not to an address: they are asked once, even when
		// billing and shipping differ.
		woocommerce_register_additional_checkout_field(
			array(
				'id'         => EFB_DOORS_FIELD_ID,
				'label'      => efb_checkout_doors_label(),
				'location'   => 'order',
				'type'       => 'select',
				'required'   => true,
				'attributes' => array(
					'title' => 'The delivery team needs this to tell you whether the cabinet can come inside.',
				),
				'options'    => array(
					array(
						'value' => 'YES',
						'label' => 'YES',
					),
					array(
						'value' => 'NO',
						'label' => 'NO',
					),
				),
			)
		);

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => EFB_STEPS_FIELD_ID,
				'label'    => efb_checkout_steps_label(),
				'location' => 'order',
				'type'     => 'text',
				'required' => true,
			)
		);
	}
);

/* ---------------------------------------------------------------------------
 * Hold the YES/NO answer to its two values.
 *
 * WooCommerce enforces "required" on the server, so an order cannot be placed
 * with either question unanswered — checked, and it is refused. It does not,
 * however, check a select answer against its own option list, so a request
 * made outside the checkout page could store any string. Harmless, but it
 * would reach the delivery team as nonsense, so it is refused here.
 * ------------------------------------------------------------------------- */

add_action(
	'woocommerce_validate_additional_field',
	static function ( $errors, $field_key, $field_value ) {
		if ( EFB_DOORS_FIELD_ID !== $field_key ) {
			return;
		}

		if ( ! in_array( (string) $field_value, array( 'YES', 'NO' ), true ) ) {
			$errors->add(
				'efb_doors_checked_invalid',
				'Please answer YES or NO to the question about the size of your doors.'
			);
		}
	},
	10,
	3
);

/* ---------------------------------------------------------------------------
 * Keep the old meta keys filled, so one order screen reads every order.
 * ------------------------------------------------------------------------- */

/**
 * Find an additional-field answer on an order without guessing its meta key.
 *
 * The API namespaces the key (`_wc_other/ecofridge/door-steps` today), and the
 * prefix is its own business, so the field is found by its tail instead.
 *
 * @param WC_Order $order    Order to read.
 * @param string   $field_id Registered field id.
 * @return string Answer, or an empty string.
 */
function efb_find_additional_field_value( WC_Order $order, $field_id ) {
	foreach ( $order->get_meta_data() as $meta ) {
		$key = is_object( $meta ) && isset( $meta->key ) ? (string) $meta->key : '';
		if ( '' !== $key && ( $key === $field_id || str_ends_with( $key, '/' . $field_id ) ) ) {
			return (string) $meta->value;
		}
	}

	return '';
}

/**
 * Copy both answers to the keys the old shop used.
 *
 * @param WC_Order $order Order just placed.
 * @return void
 */
function efb_mirror_delivery_answers( $order ) {
	if ( ! $order instanceof WC_Order ) {
		$order = wc_get_order( $order );
	}
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$changed = false;

	foreach (
		array(
			EFB_DOORS_META_KEY => EFB_DOORS_FIELD_ID,
			EFB_STEPS_META_KEY => EFB_STEPS_FIELD_ID,
		) as $legacy_key => $field_id
	) {
		$answer = efb_find_additional_field_value( $order, $field_id );
		if ( '' === $answer || (string) $order->get_meta( $legacy_key ) === $answer ) {
			continue;
		}

		$order->update_meta_data( $legacy_key, $answer );
		$changed = true;
	}

	if ( $changed ) {
		$order->save();
	}
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'efb_mirror_delivery_answers', 20 );
add_action( 'woocommerce_checkout_order_processed', 'efb_mirror_delivery_answers', 20 );

/* ---------------------------------------------------------------------------
 * Where the answers are read: the order screen, the emails, the customer's
 * own copy. Always from the old keys, so imported orders show up too.
 * ------------------------------------------------------------------------- */

/**
 * The two answers of an order, labelled, ready to print.
 *
 * @param WC_Order $order Order to read.
 * @return array<string,string> Label => answer.
 */
function efb_delivery_answers( WC_Order $order ) {
	$answers = array();

	$doors = (string) $order->get_meta( EFB_DOORS_META_KEY );
	if ( '' === $doors ) {
		$doors = efb_find_additional_field_value( $order, EFB_DOORS_FIELD_ID );
	}
	if ( '' !== $doors ) {
		$answers['Doorways measured'] = $doors;
	}

	$steps = (string) $order->get_meta( EFB_STEPS_META_KEY );
	if ( '' === $steps ) {
		$steps = efb_find_additional_field_value( $order, EFB_STEPS_FIELD_ID );
	}
	if ( '' !== $steps ) {
		$answers['Steps at the delivery address'] = $steps;
	}

	return $answers;
}

/**
 * Show both answers on the order screen, under the addresses.
 *
 * @param WC_Order $order Order being edited.
 * @return void
 */
function efb_show_delivery_answers_in_admin( $order ) {
	$answers = efb_delivery_answers( $order );
	if ( ! $answers ) {
		return;
	}

	echo '<div class="efb-delivery-answers"><h3>Delivery access</h3><p>';
	$lines = array();
	foreach ( $answers as $label => $answer ) {
		$lines[] = '<strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $answer );
	}
	echo wp_kses_post( implode( '<br />', $lines ) );
	echo '</p></div>';
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'efb_show_delivery_answers_in_admin', 20 );

/**
 * Put both answers in the order emails, including the one the shop receives.
 *
 * @param array    $fields Meta fields to show.
 * @param bool     $sent_to_admin Whether this is the shop's copy.
 * @param WC_Order $order  Order being emailed.
 * @return array
 */
function efb_add_delivery_answers_to_emails( $fields, $sent_to_admin, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $fields;
	}

	foreach ( efb_delivery_answers( $order ) as $label => $answer ) {
		$fields[ sanitize_key( $label ) ] = array(
			'label' => $label,
			'value' => $answer,
		);
	}

	return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'efb_add_delivery_answers_to_emails', 20, 3 );

/**
 * Show both answers on the order-received page and in the customer's order view.
 *
 * @param WC_Order $order Order being shown.
 * @return void
 */
function efb_show_delivery_answers_to_customer( $order ) {
	$answers = efb_delivery_answers( $order );
	if ( ! $answers ) {
		return;
	}

	echo '<h2 class="woocommerce-column__title">Delivery access</h2><ul class="efb-delivery-answers">';
	foreach ( $answers as $label => $answer ) {
		echo '<li>' . esc_html( $label ) . ': <strong>' . esc_html( $answer ) . '</strong></li>';
	}
	echo '</ul>';
}
add_action( 'woocommerce_order_details_after_order_table', 'efb_show_delivery_answers_to_customer', 20 );
