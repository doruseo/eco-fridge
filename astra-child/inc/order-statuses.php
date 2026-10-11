<?php
/**
 * Eco-Fridge order statuses — the fulfilment flow used by the shop.
 *
 *   Pending    → order placed, not yet picked up
 *   Processing → order picked up
 *   Processed  → order processed, ready for delivery
 *   Shipped    → order out for delivery
 *   Complete   → order delivered
 *   Cancelled  → order cancelled
 *   Failed     → payment failed / error placing the order (items go back to the cart)
 *   Returned   → order returned
 *   Refunded   → order refunded
 *
 * Processed / Shipped / Returned are registered here; the rest are core WooCommerce
 * statuses, relabelled and reordered to match the flow. On hold is removed — it has
 * no place in this flow.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The custom statuses, without the `wc-` prefix, in flow order. */
const EF_CUSTOM_ORDER_STATUSES = array(
	'processed' => 'Processed',
	'shipped'   => 'Shipped',
	'returned'  => 'Returned',
);

/* =========================================================================
 * 1. REGISTER — post statuses (needed for the legacy CPT order storage and
 *    for the status counts in the admin list table).
 * ========================================================================= */
add_action( 'init', function () {
	foreach ( EF_CUSTOM_ORDER_STATUSES as $slug => $label ) {
		register_post_status( 'wc-' . $slug, array(
			'label'                     => $label,
			'public'                    => false,
			'internal'                  => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: order count */
			'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ),
		) );
	}
}, 9 );

/* =========================================================================
 * 2. THE LIST — order the statuses along the flow, relabel, drop On hold.
 * ========================================================================= */
add_filter( 'wc_order_statuses', function ( $statuses ) {
	$flow = array(
		'wc-pending'    => 'Pending',    // placed, not picked up yet
		'wc-processing' => 'Processing', // picked up
		'wc-processed'  => 'Processed',  // ready for delivery
		'wc-shipped'    => 'Shipped',    // out for delivery
		'wc-completed'  => 'Complete',   // delivered
		'wc-cancelled'  => 'Cancelled',
		'wc-failed'     => 'Failed',
		'wc-returned'   => 'Returned',
		'wc-refunded'   => 'Refunded',
	);

	// Keep any status a plugin added that we don't know about, at the end.
	foreach ( $statuses as $key => $label ) {
		if ( ! isset( $flow[ $key ] ) && 'wc-on-hold' !== $key ) {
			$flow[ $key ] = $label;
		}
	}

	return $flow;
}, 20 );

/* =========================================================================
 * 3. SEMANTICS — where the new statuses sit in WooCommerce's own logic.
 * ========================================================================= */

/**
 * Processed and Shipped come after payment, so they count as paid: the order keeps
 * its paid date, stays out of "needs payment", and shows up in the sales reports.
 */
add_filter( 'woocommerce_order_is_paid_statuses', function ( $statuses ) {
	return array_merge( $statuses, array( 'processed', 'shipped' ) );
} );

/** Reports count the fulfilled statuses alongside processing/completed. */
add_filter( 'woocommerce_reports_order_statuses', function ( $statuses ) {
	return array_merge( $statuses, array( 'processed', 'shipped' ) );
} );

/* =========================================================================
 * 4. ADMIN — bulk actions + colours for the new statuses.
 * ========================================================================= */

/**
 * "Change status to: Processed / Shipped / Returned" in the bulk actions dropdown.
 * WooCommerce's bulk handler resolves any `mark_<status>` action generically, so
 * registering the entries is all that's needed. Both list tables (HPOS + legacy).
 */
$ef_bulk = function ( $actions ) {
	foreach ( EF_CUSTOM_ORDER_STATUSES as $slug => $label ) {
		$actions[ 'mark_' . $slug ] = 'Change status to ' . strtolower( $label );
	}
	return $actions;
};
add_filter( 'bulk_actions-woocommerce_page_wc-orders', $ef_bulk );
add_filter( 'bulk_actions-edit-shop_order', $ef_bulk );

/**
 * Status pills in the orders list: WooCommerce styles only its own statuses, so the
 * new ones would otherwise all render the same grey.
 */
add_action( 'admin_head', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order' ), true ) ) {
		return;
	}
	?>
<style>
	.order-status.status-processed { background: #d7e8f5; color: #1c4f76; }
	.order-status.status-shipped   { background: #cfe6d4; color: #1d5c2e; }
	.order-status.status-returned  { background: #f8dda7; color: #94660c; }
</style>
	<?php
} );

/* =========================================================================
 * 5. EMAILS — "Order shipped" for the customer.
 * ========================================================================= */

/**
 * Route the Shipped transition through WC_Emails, so the email is queued and sent in
 * the background exactly like the core ones instead of blocking the request that
 * changed the status.
 */
add_filter( 'woocommerce_email_actions', function ( $actions ) {
	$actions[] = 'woocommerce_order_status_shipped';
	return $actions;
} );

/** Register the email so it appears under WooCommerce → Settings → Emails. */
add_filter( 'woocommerce_email_classes', function ( $emails ) {
	require_once __DIR__ . '/emails/class-ef-email-customer-shipped-order.php';
	if ( class_exists( 'EF_Email_Customer_Shipped_Order' ) ) {
		$emails['EF_Email_Customer_Shipped_Order'] = new EF_Email_Customer_Shipped_Order();
	}
	return $emails;
} );

/* =========================================================================
 * 6. FAILED — put the products back in the customer's cart.
 *
 * A failed payment or a failed checkout shouldn't cost the customer their basket:
 * the order's items go straight back into the cart so they can retry.
 *
 * Only ever touches the cart of the customer who owns the order. Everything else —
 * an admin flipping an order to Failed from the dashboard, a gateway webhook, cron,
 * WP-CLI — leaves the cart alone. Note that WC()->cart is NOT null in those contexts,
 * so the ownership check below is what does the work, not a cart existence check.
 * ========================================================================= */
add_action( 'woocommerce_order_status_failed', function ( $order_id ) {
	if ( ! function_exists( 'WC' ) || is_null( WC()->cart ) || is_null( WC()->session ) ) {
		return;
	}

	// No real customer session (cron, webhook, CLI, admin request) → not our cart.
	if ( ! WC()->session->has_session() ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$customer_id = $order->get_customer_id();
	if ( $customer_id ) {
		// Registered customer: the request must be made by them.
		if ( $customer_id !== get_current_user_id() ) { return; }
	} else {
		// Guest: only the session that actually placed this order may be repopulated.
		if ( (int) WC()->session->get( 'order_awaiting_payment' ) !== (int) $order_id ) { return; }
	}

	// One-shot: a retry that fails again shouldn't duplicate lines in the cart.
	if ( $order->get_meta( '_ef_cart_restored' ) ) {
		return;
	}
	$order->update_meta_data( '_ef_cart_restored', current_time( 'mysql' ) );
	$order->save();

	WC()->cart->empty_cart();

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product || ! $product->is_purchasable() ) {
			continue;
		}

		$variations = array();
		foreach ( $item->get_meta_data() as $meta ) {
			$key = $meta->key;
			if ( taxonomy_is_product_attribute( $key ) || meta_is_product_attribute( $key, $meta->value, $product->get_id() ) ) {
				$variations[ 'attribute_' . $key ] = $meta->value;
			}
		}

		// Lets the add-ons (removal & disposal, extra warranty) survive the round-trip.
		$item_data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );

		WC()->cart->add_to_cart(
			$item->get_product_id(),
			$item->get_quantity(),
			$item->get_variation_id(),
			$variations,
			$item_data
		);
	}
}, 10 );
