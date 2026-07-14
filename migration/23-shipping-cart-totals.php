<?php
/**
 * Shipping setup for the cart totals.
 *
 * Adds a WooCommerce shipping method so the /cart "Cart totals" shows a
 * "Shipping" (Delivery) row. The cost is a placeholder (0) and is fully
 * editable later from WooCommerce → Settings → Shipping. Combined with the
 * child template override (woocommerce/cart/cart-totals.php), the totals read:
 *   Subtotal (ex-VAT) → Removal & Disposal → Warranty → Shipping → VAT → Total.
 *
 * Idempotent: re-running does not create duplicate zones or methods.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }

// Store base country (drives the default shipping/tax location) = United Kingdom.
$base = (string) get_option( 'woocommerce_default_country' );
if ( 0 !== strpos( $base, 'GB' ) ) {
	update_option( 'woocommerce_default_country', 'GB' );
	WP_CLI::log( 'Set store base country to GB.' );
}

// Show a shipping row on the cart even before the customer enters a full address.
update_option( 'woocommerce_enable_shipping_calc', 'yes' );
update_option( 'woocommerce_shipping_cost_requires_address', 'no' );

/**
 * Ensure a zone has a Flat Rate method (placeholder cost 0, taxable) titled $title.
 * Returns the instance id.
 */
function ef_ensure_flat_rate( WC_Shipping_Zone $zone, $title ) {
	foreach ( $zone->get_shipping_methods() as $m ) {
		if ( 'flat_rate' === $m->id ) {
			return $m->get_instance_id();
		}
	}
	$iid = $zone->add_shipping_method( 'flat_rate' );
	update_option(
		'woocommerce_flat_rate_' . $iid . '_settings',
		array(
			'title'      => $title,
			'tax_status' => 'taxable',
			'cost'       => '0', // placeholder — set the real delivery price later in WooCommerce.
		)
	);
	return $iid;
}

// Primary market zone: United Kingdom.
$gb = null;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'UK Mainland' === $z['zone_name'] ) {
		$gb = new WC_Shipping_Zone( $z['zone_id'] );
		break;
	}
}
if ( ! $gb ) {
	$gb = new WC_Shipping_Zone();
	$gb->set_zone_name( 'UK Mainland' );
	$gb->add_location( 'GB', 'country' );
	$gb->save();
	WP_CLI::log( 'Created shipping zone "UK Mainland" (GB).' );
}
ef_ensure_flat_rate( $gb, 'Delivery' );

// Fallback zone "Rest of the World" (id 0) so every location shows a shipping row.
$z0 = new WC_Shipping_Zone( 0 );
ef_ensure_flat_rate( $z0, 'Delivery' );

WP_CLI::success( 'Shipping methods ensured (Delivery, placeholder cost 0 — editable in WooCommerce).' );
