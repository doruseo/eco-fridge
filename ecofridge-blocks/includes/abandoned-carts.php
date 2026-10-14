<?php
/**
 * Abandoned carts: remind customers who left products in their cart.
 *
 *  - A cart is recorded as soon as we know whose it is: a signed-in customer,
 *    or a guest who has typed their email address at checkout. Products, add-ons
 *    (Removal & Disposal, castors, warranty) and quantities are kept.
 *  - If no order follows, the "Abandoned cart" email (WooCommerce → Settings →
 *    Emails) reminds the customer – by default after 1 hour and again after 24
 *    hours – with a link that puts the same cart back and opens the checkout.
 *  - Every reminder has an unsubscribe link; unsubscribed addresses are never
 *    recorded again. An order (from that session or that email address) stops
 *    the reminders; carts are deleted after 60 days.
 *  - WooCommerce → Abandoned carts lists them; personal data export / erasure
 *    (Tools → Export / Erase Personal Data) include them.
 *
 * Runs on Action Scheduler (bundled with WooCommerce) every 15 minutes.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

const EFB_AC_DB_VERSION = '1';
const EFB_AC_KEEP_DAYS  = 60;

/**
 * The carts table.
 *
 * @return string
 */
function efb_ac_table() {
	global $wpdb;
	return $wpdb->prefix . 'efb_abandoned_carts';
}

/** Creates / updates the table. */
add_action(
	'init',
	static function () {
		if ( EFB_AC_DB_VERSION === get_option( 'efb_ac_db_version' ) ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			'CREATE TABLE ' . efb_ac_table() . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				session_key varchar(64) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				email varchar(190) NOT NULL DEFAULT '',
				first_name varchar(100) NOT NULL DEFAULT '',
				cart longtext NOT NULL,
				total decimal(18,2) NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'open',
				reminders tinyint(3) unsigned NOT NULL DEFAULT 0,
				token char(32) NOT NULL DEFAULT '',
				order_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created datetime NOT NULL,
				updated datetime NOT NULL,
				reminded datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY session_key (session_key),
				KEY email (email),
				KEY status_updated (status, updated),
				UNIQUE KEY token (token)
			) {$wpdb->get_charset_collate()};"
		);
		update_option( 'efb_ac_db_version', EFB_AC_DB_VERSION );
	}
);

/**
 * Whether an address has unsubscribed from cart reminders.
 *
 * @param string $email Email address.
 * @return bool
 */
function efb_ac_opted_out( $email ) {
	return in_array( md5( strtolower( trim( $email ) ) ), (array) get_option( 'efb_ac_optout', array() ), true );
}

/**
 * The open cart row of the current session, if any.
 *
 * @param string $session_key WooCommerce session customer ID.
 * @return object|null
 */
function efb_ac_open_row( $session_key ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . efb_ac_table() . " WHERE session_key = %s AND status IN ('open','reminded') ORDER BY id DESC LIMIT 1", $session_key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Records (or updates, or forgets) the current visitor's cart.
 */
function efb_ac_capture() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session || ! WC()->customer ) {
		return;
	}
	global $wpdb;
	$session_key = (string) WC()->session->get_customer_id();
	if ( '' === $session_key ) {
		return;
	}
	$row = efb_ac_open_row( $session_key );

	// An emptied cart is forgotten.
	if ( WC()->cart->is_empty() ) {
		if ( $row ) {
			$wpdb->delete( efb_ac_table(), array( 'id' => $row->id ) );
		}
		return;
	}

	$user  = wp_get_current_user();
	$email = sanitize_email( (string) WC()->customer->get_billing_email() );
	if ( ! $email && $user->exists() ) {
		$email = $user->user_email;
	}
	if ( ! is_email( $email ) || efb_ac_opted_out( $email ) ) {
		return;
	}
	// One open cart per address: someone typing another person's email in many
	// sessions must not make the shop send that person a reminder per session.
	if ( ! $row ) {
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . efb_ac_table() . " WHERE email = %s AND status IN ('open','reminded') ORDER BY id DESC LIMIT 1", $email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $row ) {
			$wpdb->update( efb_ac_table(), array( 'session_key' => $session_key ), array( 'id' => $row->id ) );
		}
	}

	$items = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$extra = array();
		foreach ( $item as $key => $value ) {
			if ( 0 === strpos( (string) $key, 'ef_' ) ) {
				$extra[ $key ] = $value; // the theme's add-ons (Removal & Disposal, castors, warranty).
			}
		}
		$items[] = array(
			'product_id'   => (int) $item['product_id'],
			'variation_id' => (int) $item['variation_id'],
			'variation'    => (array) $item['variation'],
			'quantity'     => (int) $item['quantity'],
			'extra'        => $extra,
		);
	}

	// The first name goes into the reminder ("Hi …"). Anything that is not a plain
	// name (a link, a domain, an address) is dropped, so the field cannot be used to
	// send someone a message from the shop's address.
	$first_name = sanitize_text_field( current( array_filter( array( WC()->customer->get_billing_first_name(), WC()->customer->get_shipping_first_name(), $user->first_name ) ) ) ?: '' );
	if ( mb_strlen( $first_name ) > 40 || preg_match( '~https?:|www\.|[@/<>:\\\\]|\.[a-z]{2,}\b|\d{3,}~i', $first_name ) ) {
		$first_name = '';
	}

	$data = array(
		'user_id'    => (int) $user->ID,
		'email'      => $email,
		'first_name' => $first_name,
		'cart'       => wp_json_encode( $items ),
		'total'      => (float) WC()->cart->get_total( 'edit' ),
		'updated'    => current_time( 'mysql', true ),
	);
	if ( $row ) {
		// The same cart and address: nothing new, keep the reminder clock running.
		if ( $row->cart === $data['cart'] && $row->email === $data['email'] && (float) $row->total === $data['total'] ) {
			return;
		}
		$wpdb->update( efb_ac_table(), $data, array( 'id' => $row->id ) );
		return;
	}
	$wpdb->insert(
		efb_ac_table(),
		$data + array(
			'session_key' => $session_key,
			'status'      => 'open',
			'token'       => wp_generate_password( 32, false ),
			'created'     => current_time( 'mysql', true ),
		)
	);
}
add_action( 'woocommerce_cart_updated', 'efb_ac_capture', 20 );
// The block checkout sends the email address through the Store API.
add_action( 'woocommerce_store_api_cart_update_customer_from_request', static fn() => add_action( 'shutdown', 'efb_ac_capture' ), 20 );
// The classic checkout refreshes the order review with the address fields.
add_action( 'woocommerce_checkout_update_order_review', static fn() => add_action( 'shutdown', 'efb_ac_capture' ), 20 );

/**
 * An order stops the reminders: the session's cart (and any open cart of the
 * same address) becomes "ordered", or "recovered" when it followed a reminder.
 *
 * @param WC_Order|int $order Order.
 */
function efb_ac_order_placed( $order ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	if ( ! $order ) {
		return;
	}
	global $wpdb;
	$session_key = function_exists( 'WC' ) && WC()->session ? (string) WC()->session->get_customer_id() : '';
	$rows        = $wpdb->get_results( $wpdb->prepare( 'SELECT id, status FROM ' . efb_ac_table() . " WHERE status IN ('open','reminded') AND ( session_key = %s OR email = %s )", $session_key, $order->get_billing_email() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $rows as $row ) {
		$wpdb->update(
			efb_ac_table(),
			array(
				'status'   => 'reminded' === $row->status ? 'recovered' : 'ordered',
				'order_id' => $order->get_id(),
				'updated'  => current_time( 'mysql', true ),
			),
			array( 'id' => $row->id )
		);
	}
}
add_action( 'woocommerce_checkout_order_created', 'efb_ac_order_placed' );
add_action( 'woocommerce_store_api_checkout_order_processed', 'efb_ac_order_placed' );

/**
 * The reminder email (registered with WooCommerce's emails).
 */
add_filter(
	'woocommerce_email_classes',
	static function ( $emails ) {
		require_once EFB_DIR . 'includes/class-efb-email-abandoned-cart.php';
		$emails['EFB_Email_Abandoned_Cart'] = new EFB_Email_Abandoned_Cart();
		return $emails;
	}
);

/** Every 15 minutes: send the reminders that are due, delete old carts. */
add_action(
	'init',
	static function () {
		if ( function_exists( 'as_has_scheduled_action' ) && ! as_has_scheduled_action( 'efb_ac_process', array(), 'ecofridge' ) ) {
			as_schedule_recurring_action( time() + 300, 15 * MINUTE_IN_SECONDS, 'efb_ac_process', array(), 'ecofridge' );
		}
	}
);

/**
 * Sends the reminders that are due.
 */
function efb_ac_process() {
	global $wpdb;
	$email = WC()->mailer()->get_emails()['EFB_Email_Abandoned_Cart'] ?? null;
	$table = efb_ac_table();

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE updated < %s", gmdate( 'Y-m-d H:i:s', time() - EFB_AC_KEEP_DAYS * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( ! $email || ! $email->is_enabled() ) {
		return;
	}

	$steps = array(
		1 => (float) $email->get_option( 'delay_first', 1 ),
		2 => (float) $email->get_option( 'delay_second', 24 ),
	);
	foreach ( $steps as $number => $hours ) {
		if ( $hours <= 0 ) {
			continue;
		}
		$since = gmdate( 'Y-m-d H:i:s', time() - (int) round( $hours * HOUR_IN_SECONDS ) );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status IN ('open','reminded') AND reminders = %d AND updated <= %s LIMIT 50", $number - 1, $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $rows as $row ) {
			// Ordered meanwhile (another device, by phone…): no reminder.
			$orders = wc_get_orders(
				array(
					'billing_email' => $row->email,
					'date_created'  => '>=' . strtotime( $row->created . ' UTC' ),
					'status'        => array_diff( array_keys( wc_get_order_statuses() ), array( 'wc-failed', 'wc-checkout-draft', 'wc-cancelled' ) ),
					'limit'         => 1,
					'return'        => 'ids',
				)
			);
			if ( $orders || efb_ac_opted_out( $row->email ) ) {
				$wpdb->update( $table, array( 'status' => $orders ? 'ordered' : 'unsubscribed', 'order_id' => $orders ? (int) $orders[0] : 0 ), array( 'id' => $row->id ) );
				continue;
			}
			if ( $email->trigger( $row, $number ) ) {
				$wpdb->update(
					$table,
					array(
						'status'    => 'reminded',
						'reminders' => $number,
						'reminded'  => current_time( 'mysql', true ),
					),
					array( 'id' => $row->id )
				);
			}
		}
	}
}
add_action( 'efb_ac_process', 'efb_ac_process' );

/**
 * Link that restores a cart (or unsubscribes from the reminders).
 *
 * @param object $row    Cart row.
 * @param string $action "restore" or "unsubscribe".
 * @return string
 */
function efb_ac_link( $row, $action = 'restore' ) {
	return add_query_arg( 'restore' === $action ? 'efb_cart' : 'efb_cart_unsubscribe', $row->token, home_url( '/' ) );
}

/** Restore / unsubscribe links. */
add_action(
	'wp_loaded',
	static function () {
		// Tokens are letters and digits; keep their case (sanitize_key() would lower it).
		$restore = isset( $_GET['efb_cart'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['efb_cart'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- token link from an email.
		$leave   = isset( $_GET['efb_cart_unsubscribe'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['efb_cart_unsubscribe'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ( '' === $restore && '' === $leave ) || 32 !== strlen( $restore ? $restore : $leave ) ) {
			return;
		}
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . efb_ac_table() . ' WHERE BINARY token = %s', $restore ? $restore : $leave ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row || ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		if ( $leave ) {
			$optout   = (array) get_option( 'efb_ac_optout', array() );
			$optout[] = md5( strtolower( trim( $row->email ) ) );
			update_option( 'efb_ac_optout', array_values( array_unique( $optout ) ), false );
			$wpdb->update( efb_ac_table(), array( 'status' => 'unsubscribed' ), array( 'email' => $row->email ) );
			wp_safe_redirect( add_query_arg( 'efb_unsubscribed', '1', wc_get_cart_url() ) );
			exit;
		}

		// Put the products back and continue at the checkout.
		WC()->session->set_customer_session_cookie( true );
		WC()->cart->empty_cart();
		foreach ( (array) json_decode( $row->cart, true ) as $item ) {
			$product = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
			if ( $product && $product->is_purchasable() ) {
				WC()->cart->add_to_cart( $item['product_id'], max( 1, (int) $item['quantity'] ), $item['variation_id'], (array) $item['variation'], (array) $item['extra'] );
			}
		}
		WC()->customer->set_billing_email( $row->email );
		if ( $row->first_name && ! WC()->customer->get_billing_first_name() ) {
			WC()->customer->set_billing_first_name( $row->first_name );
		}
		WC()->customer->save();
		// This browser's session now owns the cart (no second record).
		if ( in_array( $row->status, array( 'open', 'reminded' ), true ) ) {
			$wpdb->update( efb_ac_table(), array( 'session_key' => (string) WC()->session->get_customer_id() ), array( 'id' => $row->id ) );
		}
		wp_safe_redirect( WC()->cart->is_empty() ? wc_get_page_permalink( 'shop' ) : wc_get_checkout_url() );
		exit;
	},
	30
);

/** Confirmation on the cart page after unsubscribing (a visitor may have no session). */
$efb_ac_unsubscribed_notice = static function () {
	static $done = false;
	if ( ! $done && isset( $_GET['efb_unsubscribed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$done = true;
		wc_print_notice( 'You have been unsubscribed – we will not send you any more cart reminders.', 'success' );
	}
};
add_action( 'woocommerce_before_cart', $efb_ac_unsubscribed_notice, 1 );
add_action( 'woocommerce_cart_is_empty', $efb_ac_unsubscribed_notice, 1 );

/** WooCommerce → Abandoned carts. */
add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'woocommerce', 'Abandoned carts', 'Abandoned carts', 'manage_woocommerce', 'efb-abandoned-carts', 'efb_ac_admin_page' );
	},
	60
);

/**
 * The admin list.
 */
function efb_ac_admin_page() {
	global $wpdb;
	$table  = efb_ac_table();
	$counts = $wpdb->get_results( "SELECT status, COUNT(*) n, SUM(total) t FROM {$table} GROUP BY status", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows   = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY updated DESC LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$labels = array(
		'open'         => 'Waiting',
		'reminded'     => 'Reminded',
		'recovered'    => 'Recovered (ordered after a reminder)',
		'ordered'      => 'Ordered',
		'unsubscribed' => 'Unsubscribed',
	);
	$email  = WC()->mailer()->get_emails()['EFB_Email_Abandoned_Cart'] ?? null;
	echo '<div class="wrap"><h1>Abandoned carts</h1>';
	echo '<p>Carts of signed-in customers and of guests who typed their email address at checkout. Reminders: ';
	echo $email && $email->is_enabled() ? 'after ' . esc_html( $email->get_option( 'delay_first', 1 ) ) . ' h and ' . esc_html( $email->get_option( 'delay_second', 24 ) ) . ' h' : '<strong>off</strong>';
	echo ' – <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=efb_email_abandoned_cart' ) ) . '">email settings</a>. Carts are kept for ' . (int) EFB_AC_KEEP_DAYS . ' days.</p>';
	echo '<ul class="subsubsub" style="float:none;margin-bottom:12px">';
	foreach ( $labels as $status => $label ) {
		$n = isset( $counts[ $status ] ) ? (int) $counts[ $status ]->n : 0;
		$t = isset( $counts[ $status ] ) ? (float) $counts[ $status ]->t : 0;
		echo '<li>' . esc_html( $label ) . ': <strong>' . (int) $n . '</strong> (' . wp_kses_post( wc_price( $t ) ) . ') &nbsp;</li>';
	}
	echo '</ul><table class="widefat striped"><thead><tr><th>Last activity</th><th>Customer</th><th>Products</th><th>Total</th><th>Status</th><th>Reminders</th><th>Order</th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="7">No carts yet.</td></tr>';
	}
	foreach ( $rows as $row ) {
		$names = array();
		foreach ( (array) json_decode( $row->cart, true ) as $item ) {
			$product = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
			$names[] = (int) $item['quantity'] . ' × ' . ( $product ? $product->get_name() : '#' . (int) $item['product_id'] );
		}
		printf(
			'<tr><td>%s</td><td>%s<br><small>%s</small></td><td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%s</td></tr>',
			esc_html( get_date_from_gmt( $row->updated, 'j M Y H:i' ) ),
			esc_html( $row->first_name ),
			esc_html( $row->email ),
			esc_html( implode( ', ', $names ) ),
			wp_kses_post( wc_price( $row->total ) ),
			esc_html( $labels[ $row->status ] ?? $row->status ),
			(int) $row->reminders,
			$row->order_id ? '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $row->order_id . '&action=edit' ) ) . '">#' . (int) $row->order_id . '</a>' : '–'
		);
	}
	echo '</tbody></table></div>';
}

/** Personal data export / erasure. */
add_filter(
	'wp_privacy_personal_data_exporters',
	static function ( $exporters ) {
		$exporters['efb-abandoned-carts'] = array(
			'exporter_friendly_name' => 'Abandoned carts',
			'callback'               => static function ( $email ) {
				global $wpdb;
				$data = array();
				foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . efb_ac_table() . ' WHERE email = %s', $email ) ) as $row ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$data[] = array(
						'group_id'    => 'efb-abandoned-carts',
						'group_label' => 'Abandoned carts',
						'item_id'     => 'efb-cart-' . $row->id,
						'data'        => array(
							array( 'name' => 'Email', 'value' => $row->email ),
							array( 'name' => 'Name', 'value' => $row->first_name ),
							array( 'name' => 'Cart', 'value' => $row->cart ),
							array( 'name' => 'Last activity', 'value' => $row->updated ),
						),
					);
				}
				return array( 'data' => $data, 'done' => true );
			},
		);
		return $exporters;
	}
);
add_filter(
	'wp_privacy_personal_data_erasers',
	static function ( $erasers ) {
		$erasers['efb-abandoned-carts'] = array(
			'eraser_friendly_name' => 'Abandoned carts',
			'callback'             => static function ( $email ) {
				global $wpdb;
				$removed = (int) $wpdb->delete( efb_ac_table(), array( 'email' => $email ) );
				return array( 'items_removed' => $removed > 0, 'items_retained' => false, 'messages' => array(), 'done' => true );
			},
		);
		return $erasers;
	}
);
