<?php
/**
 * 75 — every optional SMS feature off; only the order statuses text the customer.
 *
 * Doru asked for the abandoned-cart texts to be switched off for now. They
 * already were (migration 70), but checking the rest of the plugin turned up
 * three more features that were quietly live on their defaults:
 *
 *  1. **Review request.** `smsalert_or_general.customer_notify` was "on", with a
 *     message asking for a Google review and `review_status = pending`,
 *     `schedule_day = 1`. One day after ANY order entered Pending - which, with
 *     the block checkout, includes every abandoned checkout - the customer would
 *     have been texted a review request.
 *  2. **Review posted.** An SMS to the reviewer and one to the company whenever a
 *     review is left.
 *  3. **Back in stock.** The "notify me" texts, on by default.
 *
 * All three are off now, and the texts of the remaining templates lose the
 * plugin's "Powered by www.smsalert.co.in" footer, so nothing embarrassing is
 * sent if a feature is ever switched back on.
 *
 * What still texts the customer: the order statuses with waiting days
 * (Processing, Preparing, Processed, On hold, refunds) - see migrations 70-74.
 *
 * Safe to run again. Backup: ef_smsalert_extras_before_75.
 * Run: wp eval-file migration/75-sms-extras-off.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

$groups = array(
	// Review request after an order ("can you take 1 minute to leave a review").
	'smsalert_or_general'     => array( 'customer_notify' => 'off' ),
	// SMS when a review is posted, to the reviewer and to the company.
	'smsalert_review'         => array(
		'review_added_user_msg'  => 'off',
		'review_added_admin_msg' => 'off',
	),
	// "Notify me when back in stock" texts.
	'smsalert_bis_general'    => array(
		'customer_bis_notify'   => 'off',
		'subscribed_bis_notify' => 'off',
	),
	// Abandoned cart: already off in migration 70, kept here so one run sets
	// everything and the state cannot drift.
	'smsalert_abandoned_cart' => array(
		'customer_notify'         => 'off',
		'cart_exit_intent_status' => '',
	),
);

$before = array();
foreach ( array_keys( $groups ) as $option ) {
	$before[ $option ] = get_option( $option, array() );
}
$before['smsalert_or_message'] = get_option( 'smsalert_or_message', array() );

if ( ! get_option( 'ef_smsalert_extras_before_75' ) ) {
	add_option( 'ef_smsalert_extras_before_75', $before, '', false );
	echo "Backup saved in ef_smsalert_extras_before_75.\n";
}

$off = 0;
foreach ( $groups as $option => $values ) {
	$settings = (array) get_option( $option, array() );
	foreach ( $values as $key => $value ) {
		if ( ! isset( $settings[ $key ] ) || $settings[ $key ] !== $value ) {
			$settings[ $key ] = $value;
			++$off;
		}
	}
	update_option( $option, $settings );
}

echo "Optional SMS features switched off: {$off} settings.\n";

// The plugin's footer out of the texts that are left in these groups.
$cleaned = 0;
foreach ( array( 'smsalert_or_message', 'smsalert_review' ) as $option ) {
	$settings = (array) get_option( $option, array() );
	foreach ( $settings as $key => $text ) {
		if ( ! is_string( $text ) || '' === $text ) {
			continue;
		}
		$clean = preg_replace( '/\s*Powered by\s*\R*\s*www\.smsalert\.co\.in\s*/i', '', $text );
		$clean = trim( $clean );
		if ( $clean !== $text ) {
			$settings[ $key ] = $clean;
			++$cleaned;
		}
	}
	update_option( $option, $settings );
}

echo "Plugin footer removed from {$cleaned} texts.\n";

// What is left on, for the record.
$general  = (array) get_option( 'smsalert_general', array() );
$statuses = array();
foreach ( (array) ( $general['order_status'] ?? array() ) as $slug => $value ) {
	if ( '' !== $value ) {
		$statuses[] = $slug;
	}
}
echo 'Customer texts still on: ' . implode( ', ', $statuses ) . "\n";
