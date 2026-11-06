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
 *   check_doors  select, required  "We recommend you to check the height of
 *                                   your doors through the final fridge
 *                                   position. Have you checked the size of
 *                                   your doors? (T&C apply)"
 *   stairs       select, required  "How many door steps? Curb side delivery
 *                                   will be at the drivers discretion
 *                                   (T&C apply)"
 *
 * Two deliberate departures from the old shop, both asked for on 3 November:
 *
 *  - the old label misspelled "recommend" with one m, and the typo is fixed
 *    here;
 *  - the question about steps was a free-text field, which produced 396
 *    different answers to one question ("No", "1", "none", "no steps", "N/A",
 *    "one"): readable one order at a time, useless to filter or count. It is a
 *    choice of 0, 1, 2, 3 or 4+ now. Imported orders keep whatever they were
 *    answered with, and the screens below print that text as it stands.
 *
 * `efb_checkout_doors_label`, `efb_checkout_steps_label` and
 * `efb_checkout_steps_options` change the wording and the choices without
 * touching code.
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
		'We recommend you to check the height of your doors through the final fridge position. Have you checked the size of your doors? (T&C apply)'
	);
}

/**
 * The answers the customer can give about steps, as stored value => shown label.
 *
 * Kept short on purpose: anything past four steps is the same decision for the
 * driver, so "4+" is as much detail as the question needs.
 *
 * The customer picks 0, 1, 2, 3 or 4+, but what is stored reads as "0 steps"
 * rather than "0". Two reasons: WooCommerce treats a bare "0" as an unanswered
 * required field and refuses the order — measured, not assumed — and the stored
 * text is what the delivery team reads in the email and on the order screen,
 * where "0 steps" says more than a lone digit.
 *
 * @return array<string,string> Stored value => label at checkout.
 */
function efb_checkout_steps_options() {
	$options = apply_filters(
		'efb_checkout_steps_options',
		array(
			'0 steps'  => '0',
			'1 step'   => '1',
			'2 steps'  => '2',
			'3 steps'  => '3',
			'4+ steps' => '4+',
		)
	);

	return is_array( $options ) ? $options : array();
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
				'id'          => EFB_DOORS_FIELD_ID,
				'label'       => efb_checkout_doors_label(),
				'location'    => 'order',
				'type'        => 'select',
				'required'    => true,
				// Without this, WooCommerce builds the empty option out of the
				// label — "Select a we recommend you to check the height of your
				// doors…" — which is both wrong and too long to read.
				'placeholder' => 'Choose YES or NO',
				'attributes'  => array(
					'title' => 'The delivery team needs this to tell you whether the cabinet can come inside.',
				),
				'options'     => array(
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

		$steps_options = array();
		foreach ( efb_checkout_steps_options() as $steps_value => $steps_label ) {
			$steps_options[] = array(
				'value' => (string) $steps_value,
				'label' => (string) $steps_label,
			);
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'          => EFB_STEPS_FIELD_ID,
				'label'       => efb_checkout_steps_label(),
				'location'    => 'order',
				'type'        => 'select',
				'required'    => true,
				'placeholder' => 'Choose the number of steps',
				'options'     => $steps_options,
			)
		);
	}
);

/* ---------------------------------------------------------------------------
 * Make the questions readable.
 *
 * The checkout block draws a select with a floating label: the label sits over
 * the control on one line and is cut off where it runs out of room. That is
 * fine for "Country" and useless for a question of 137 characters, which is
 * what the client saw — the question was there but unreadable.
 *
 * So, for this section only, the label goes back to being an ordinary block
 * above the control, free to wrap over as many lines as it needs, with the
 * answer underneath. `!important` is deliberate and kept inside this one
 * container: the rules it overrides come from WooCommerce's own stylesheet,
 * which loads after ours and is rewritten on every update, so specificity
 * alone would not survive. The order-notes field in the same section gets the
 * same treatment, which reads better there too.
 * ------------------------------------------------------------------------- */

add_action(
	'wp_head',
	static function () {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		?>
<style id="efb-delivery-questions">
.wc-block-checkout__additional-information-fields .wc-block-components-select-input,
.wc-block-checkout__additional-information-fields .wc-block-components-text-input,
.wc-block-checkout__order-fields .wc-block-components-select-input,
.wc-block-checkout__order-fields .wc-block-components-text-input {
	display: block !important;
	width: 100% !important;
	flex: 1 1 100% !important;
	margin-top: 1.25em;
}
.wc-block-checkout__additional-information-fields label,
.wc-block-checkout__order-fields label {
	position: static !important;
	transform: none !important;
	display: block !important;
	max-width: 100% !important;
	white-space: normal !important;
	overflow: visible !important;
	text-overflow: clip !important;
	margin: 0 0 0.4em !important;
	padding: 0 !important;
	line-height: 1.4 !important;
	font-size: 1em !important;
	font-weight: 600 !important;
	color: inherit !important;
	opacity: 1 !important;
}
.wc-block-checkout__additional-information-fields select,
.wc-block-checkout__additional-information-fields textarea,
.wc-block-checkout__order-fields select,
.wc-block-checkout__order-fields textarea {
	width: 100% !important;
	height: auto !important;
	padding: 0.7em 0.8em !important;
	line-height: 1.4 !important;
}
/* The block's own chevron is positioned against the whole field, label
   included, so once the question wraps above the control the arrow floats up
   by the height of the label and sits on the border. Rather than chase that
   offset, the arrow is drawn on the select itself, where it stays centred
   however many lines the question needs. */
.wc-block-checkout__additional-information-fields .wc-block-components-select-input svg,
.wc-block-checkout__additional-information-fields .wc-block-components-select svg,
.wc-block-checkout__order-fields .wc-block-components-select-input svg,
.wc-block-checkout__order-fields .wc-block-components-select svg {
	display: none !important;
}
.wc-block-checkout__additional-information-fields .wc-block-components-select-input::after,
.wc-block-checkout__order-fields .wc-block-components-select-input::after {
	display: none !important;
}
.wc-block-checkout__additional-information-fields select,
.wc-block-checkout__order-fields select {
	-webkit-appearance: none !important;
	-moz-appearance: none !important;
	appearance: none !important;
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
	background-repeat: no-repeat !important;
	background-position: right 0.9em center !important;
	background-size: 0.85em !important;
	padding-right: 2.6em !important;
}
</style>
		<?php
	},
	20
);

/* ---------------------------------------------------------------------------
 * Hold both answers to the choices they were offered.
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
		$allowed = array(
			EFB_DOORS_FIELD_ID => array( 'YES', 'NO' ),
			EFB_STEPS_FIELD_ID => array_keys( efb_checkout_steps_options() ),
		);

		if ( ! isset( $allowed[ $field_key ] ) ) {
			return;
		}

		if ( in_array( (string) $field_value, $allowed[ $field_key ], true ) ) {
			return;
		}

		$errors->add(
			'efb_delivery_answer_invalid',
			EFB_DOORS_FIELD_ID === $field_key
				? 'Please answer YES or NO to the question about the size of your doors.'
				: 'Please choose how many steps there are at the delivery address.'
		);
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
