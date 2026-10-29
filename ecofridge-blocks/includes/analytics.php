<?php
/**
 * Analytics: extra ecommerce data for Google Tag Manager (GTM4WP), so GA4 /
 * Google Ads reports can be broken down by product, add-on, location and
 * traffic source.
 *
 * Items (every GTM4WP ecommerce event: lists, product, cart, checkout,
 * purchase) get, next to GTM4WP's item_id / item_name / price / quantity /
 * item_brand / item_category…item_category5:
 *  - wp_product_id, sku, item_condition (new / used), item_dimensions,
 *    item_categories (every category of the product), item_tags,
 *    price_inc_vat, regular_price, on_sale;
 *  - in the cart, checkout and purchase: item_variant = the add-ons chosen
 *    ("Removal & Disposal + 12 months parts warranty", or "Wholesale order"),
 *    addons_value (per unit, ex VAT), has_warranty / has_removal /
 *    has_castors.
 *
 * Purchase: GTM4WP's ecommerce object (transaction_id, value without VAT and
 * shipping, tax, shipping, currency, coupon, items) gets affiliation, and the
 * push gets an `order_details` object:
 *  - order: id, number, status, date, payment and shipping method, totals
 *    (subtotal, add-ons/fees, discount, tax, total inc VAT), item and unit
 *    counts, the add-ons with their amounts;
 *  - customer: guest / registered, business (company filled in), new or
 *    returning and the number of earlier orders (by billing email);
 *  - location: billing and delivery city, county, postcode area (outward
 *    code, e.g. "MK18" – not the full postcode) and country;
 *  - traffic: WooCommerce order attribution (source type, source, medium,
 *    campaign, content, term, referrer domain, entry page, device, pages and
 *    sessions before the order) – recorded only when the visitor allowed
 *    statistics cookies;
 * and `user_data` for Google Ads enhanced conversions (SHA-256 email and
 * phone, address), used by Google tags only with ad_user_data consent.
 * No plain email, phone, name or street address is put in the dataLayer (the
 * full postcode appears only inside user_data, as Google requires).
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add-ons chosen on a cart line or an order line.
 *
 * @param mixed $source Cart item array or WC_Order_Item_Product.
 * @return string[]|null Add-on keys, or null when the source carries none.
 */
function efb_an_chosen_addons( $source ) {
	if ( ! function_exists( 'ef_addon_defs' ) ) {
		return null;
	}
	$keys = array();
	foreach ( ef_addon_defs() as $key => $def ) {
		if ( is_array( $source ) && ! empty( $source[ 'ef_' . $key ] ) ) {
			$keys[] = $key;
		} elseif ( $source instanceof WC_Order_Item_Product && $source->get_meta( $def['fee'] ) ) {
			$keys[] = $key;
		}
	}
	return ( is_array( $source ) && isset( $source['key'] ) ) || $source instanceof WC_Order_Item_Product ? $keys : null;
}

/**
 * Term names of a product, joined.
 *
 * @param int    $product_id Product ID.
 * @param string $taxonomy   Taxonomy.
 * @return string
 */
function efb_an_terms( $product_id, $taxonomy ) {
	$names = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
	return is_wp_error( $names ) ? '' : html_entity_decode( implode( ' | ', $names ), ENT_QUOTES );
}

/**
 * GTM4WP item: the extra product fields.
 *
 * @param array  $item    Item.
 * @param string $context Where it is used (purchase, cart, productdetail…).
 * @param mixed  $source  Cart item / order item, when there is one.
 * @return array
 */
function efb_an_item( $item, $context = '', $source = null ) {
	$id      = (int) ( $item['internal_id'] ?? 0 );
	$product = $id ? wc_get_product( $id ) : null;
	if ( ! $product ) {
		return $item;
	}
	$parent_id = $product->get_parent_id() ? $product->get_parent_id() : $id;

	$item['wp_product_id']   = (string) $parent_id;
	$item['sku']             = (string) $product->get_sku();
	$item['item_condition']  = function_exists( 'efb_mc_condition' ) ? efb_mc_condition( $product ) : 'new';
	$item['item_dimensions'] = html_entity_decode( (string) $product->get_attribute( 'Dimensions' ), ENT_QUOTES );
	$item['item_categories'] = efb_an_terms( $parent_id, 'product_cat' );
	$item['item_tags']       = efb_an_terms( $parent_id, 'product_tag' );
	$item['regular_price']   = round( (float) $product->get_regular_price(), 2 );
	$item['on_sale']         = $product->is_on_sale();
	if ( isset( $item['price'] ) ) {
		$item['price_inc_vat'] = round( (float) wc_get_price_including_tax( $product, array( 'price' => (float) $item['price'] ) ), 2 );
	}

	$addons = efb_an_chosen_addons( $source );
	if ( null !== $addons && function_exists( 'ef_addon_unit_price' ) ) {
		$labels = array();
		$value  = 0.0;
		foreach ( $addons as $key ) {
			$labels[] = ef_addon_defs()[ $key ]['fee'];
			$value   += ef_addon_unit_price( $key, $product );
		}
		$item['item_variant'] = $labels ? implode( ' + ', $labels ) : 'Wholesale order';
		$item['addons_value'] = round( $value, 2 );
		foreach ( array_keys( ef_addon_defs() ) as $key ) {
			$item[ 'has_' . $key ] = in_array( $key, $addons, true );
		}
	}
	return $item;
}
add_filter( 'gtm4wp_eec_item_with_source', 'efb_an_item', 30, 3 );

/**
 * UK postcode area (outward code): "MK18 1AB" → "MK18".
 *
 * @param string $postcode Postcode.
 * @param string $country  Country code.
 * @return string
 */
function efb_an_postcode_area( $postcode, $country ) {
	$postcode = strtoupper( preg_replace( '/\s+/', '', (string) $postcode ) );
	if ( 'GB' === $country && strlen( $postcode ) > 3 ) {
		return substr( $postcode, 0, -3 );
	}
	return '' !== $postcode ? substr( $postcode, 0, 3 ) : '';
}

/**
 * Normalised SHA-256 (lower case, trimmed).
 *
 * @param string $value Value.
 * @return string
 */
function efb_an_hash( $value ) {
	$value = strtolower( trim( (string) $value ) );
	return '' === $value ? '' : hash( 'sha256', $value );
}

/**
 * Phone number in E.164 (UK numbers: 07… → +447…).
 *
 * @param string $phone   Phone.
 * @param string $country Country code.
 * @return string
 */
function efb_an_e164( $phone, $country ) {
	$digits = preg_replace( '/[^\d+]/', '', (string) $phone );
	if ( '' === $digits ) {
		return '';
	}
	if ( 0 === strpos( $digits, '00' ) ) {
		return '+' . substr( $digits, 2 );
	}
	if ( '+' === $digits[0] ) {
		return $digits;
	}
	if ( 'GB' === $country && '0' === $digits[0] ) {
		return '+44' . substr( $digits, 1 );
	}
	return $digits;
}

/**
 * Earlier paid orders with the same billing email.
 *
 * @param WC_Order $order Order.
 * @return int
 */
function efb_an_previous_orders( $order ) {
	$email = $order->get_billing_email();
	if ( '' === (string) $email ) {
		return 0;
	}
	$ids = wc_get_orders(
		array(
			'billing_email' => $email,
			'status'        => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-shipped', 'wc-processed' ),
			'exclude'       => array( $order->get_id() ),
			'limit'         => 50,
			'return'        => 'ids',
		)
	);
	return count( $ids );
}

/**
 * GTM4WP purchase push: order, customer, location, traffic and enhanced
 * conversions data.
 *
 * @param array    $data_layer Purchase push.
 * @param WC_Order $order      Order.
 * @return array
 */
function efb_an_purchase( $data_layer, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $data_layer;
	}
	if ( isset( $data_layer['ecommerce'] ) ) {
		$data_layer['ecommerce']['affiliation'] = 'eco-fridge.co.uk';
	}

	$addons     = array();
	$fees_total = 0.0;
	foreach ( $order->get_fees() as $fee ) {
		$addons[]    = $fee->get_name() . ': ' . wc_format_decimal( $fee->get_total(), 2 );
		$fees_total += (float) $fee->get_total();
	}
	$units = 0;
	foreach ( $order->get_items() as $line ) {
		$units += (int) $line->get_quantity();
	}
	$previous = efb_an_previous_orders( $order );
	$created  = $order->get_date_created();
	$meta     = static function ( $key ) use ( $order ) {
		return (string) $order->get_meta( '_wc_order_attribution_' . $key );
	};
	$referrer = $meta( 'referrer' );
	$entry    = $meta( 'session_entry' );

	$data_layer['order_details'] = array(
		'order_id'             => (string) $order->get_id(),
		'order_number'         => (string) $order->get_order_number(),
		'order_status'         => $order->get_status(),
		'order_date'           => $created ? $created->date( 'c' ) : '',
		'payment_method'       => $order->get_payment_method(),
		'payment_method_title' => $order->get_payment_method_title(),
		'shipping_method'      => $order->get_shipping_method(),
		'currency'             => $order->get_currency(),
		'subtotal'             => round( (float) $order->get_subtotal(), 2 ),
		'addons_total'         => round( $fees_total, 2 ),
		'addons'               => implode( ' | ', $addons ),
		'discount'             => round( (float) $order->get_total_discount(), 2 ),
		'shipping'             => round( (float) $order->get_shipping_total(), 2 ),
		'tax'                  => round( (float) $order->get_total_tax(), 2 ),
		'total_inc_vat'        => round( (float) $order->get_total(), 2 ),
		'value_ex_vat'         => round( (float) $order->get_total() - (float) $order->get_total_tax() - (float) $order->get_shipping_total(), 2 ),
		'items_count'          => count( $order->get_items() ),
		'units_count'          => $units,
		'coupon'               => implode( ', ', $order->get_coupon_codes() ),
		// Customer.
		'customer_id'          => (string) $order->get_customer_id(),
		'customer_account'     => $order->get_customer_id() ? 'registered' : 'guest',
		'customer_business'    => '' !== trim( (string) $order->get_billing_company() ),
		'customer_type'        => $previous ? 'returning' : 'new',
		'customer_orders_before' => $previous,
		// Location.
		'billing_city'         => $order->get_billing_city(),
		'billing_county'       => $order->get_billing_state(),
		'billing_postcode_area' => efb_an_postcode_area( $order->get_billing_postcode(), $order->get_billing_country() ),
		'billing_country'      => $order->get_billing_country(),
		'shipping_city'        => $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city(),
		'shipping_county'      => $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state(),
		'shipping_postcode_area' => efb_an_postcode_area( $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(), $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country() ),
		'shipping_country'     => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country(),
		// Traffic source (WooCommerce order attribution).
		'traffic_source_type'  => $meta( 'source_type' ),
		'traffic_source'       => $meta( 'utm_source' ),
		'traffic_medium'       => $meta( 'utm_medium' ),
		'traffic_campaign'     => $meta( 'utm_campaign' ),
		'traffic_content'      => $meta( 'utm_content' ),
		'traffic_term'         => $meta( 'utm_term' ),
		'traffic_referrer'     => $referrer ? (string) wp_parse_url( $referrer, PHP_URL_HOST ) : '',
		'traffic_entry_page'   => $entry ? (string) wp_parse_url( $entry, PHP_URL_PATH ) : '',
		'device_type'          => $meta( 'device_type' ),
		'session_pages'        => (int) $meta( 'session_pages' ),
		'session_count'        => (int) $meta( 'session_count' ),
	);

	// Google Ads enhanced conversions (Google's user_data format).
	$country   = $order->get_billing_country();
	$user_data = array_filter(
		array(
			'sha256_email_address' => efb_an_hash( $order->get_billing_email() ),
			'sha256_phone_number'  => efb_an_hash( efb_an_e164( $order->get_billing_phone(), $country ) ),
			'address'              => array_filter(
				array(
					'sha256_first_name' => efb_an_hash( $order->get_billing_first_name() ),
					'sha256_last_name'  => efb_an_hash( $order->get_billing_last_name() ),
					'city'              => $order->get_billing_city(),
					'region'            => $order->get_billing_state(),
					'postal_code'       => $order->get_billing_postcode(),
					'country'           => $country,
				)
			),
		)
	);
	if ( $user_data && empty( $data_layer['user_data'] ) ) {
		$data_layer['user_data'] = $user_data;
	}
	return $data_layer;
}
add_filter( 'gtm4wp_purchase_datalayer', 'efb_an_purchase', 20, 2 );
