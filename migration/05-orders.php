<?php
/**
 * Pasul 5 migrare: comenzi (5931) legacy Woo 3.5.10 -> Woo modern (HPOS).
 * Rulare: wp eval-file wp-content/source-dump/_run.php
 *
 * Creează fiecare comandă prin WooCommerce CRUD (WC_Order) => scrie corect în HPOS.
 * Păstrează: billing/shipping, totaluri, plată, date, order_key, note client,
 * liniile de produs (mapate la produsele noi via _product-id-map.json, cu nume
 * păstrat oricum), shipping items, tax items, add-on-urile YITH (itemmeta),
 * check_doors (order meta). customer_id=0 (guest) + _ef_legacy_customer_user
 * pentru re-linkuire ulterioară dacă migrăm useri.
 *
 * Protecții: fără emailuri (pre_wp_mail), fără reducere stoc. Idempotent/reluabil
 * prin meta _ef_legacy_order_id. Numărul comenzii vechi se afișează via filtru în
 * tema copil (woocommerce_order_number).
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { WP_CLI::error( 'WooCommerce inactiv.' ); }

// protecții side-effects
add_filter( 'pre_wp_mail', '__return_true' );                        // nu trimite niciun email
add_filter( 'woocommerce_can_reduce_order_stock', '__return_false' ); // nu atinge stocul curent

global $wpdb;
$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Legacy DB: ' . mysqli_connect_error() ); }
mysqli_set_charset( $db, 'utf8mb4' );

// harta produse vechi->noi
$pmap_file = ABSPATH . 'wp-content/source-dump/_product-id-map.json';
$pmap = file_exists( $pmap_file ) ? json_decode( file_get_contents( $pmap_file ), true ) : array();
if ( ! $pmap ) { WP_CLI::warning( 'Harta produse goală — liniile vor păstra doar numele.' ); }

// set comenzi deja importate (reluare) — din stocarea activă (HPOS sau posts)
if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
	$done = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key='_ef_legacy_order_id'" );
} else {
	$done = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_ef_legacy_order_id'" );
}
$done_set = array_flip( $done );

// mapare postmeta -> setter WC_Order (billing/shipping)
$field_setters = array(
	'_billing_first_name' => 'set_billing_first_name', '_billing_last_name' => 'set_billing_last_name',
	'_billing_company' => 'set_billing_company', '_billing_address_1' => 'set_billing_address_1',
	'_billing_address_2' => 'set_billing_address_2', '_billing_city' => 'set_billing_city',
	'_billing_state' => 'set_billing_state', '_billing_postcode' => 'set_billing_postcode',
	'_billing_country' => 'set_billing_country', '_billing_email' => 'set_billing_email',
	'_billing_phone' => 'set_billing_phone',
	'_shipping_first_name' => 'set_shipping_first_name', '_shipping_last_name' => 'set_shipping_last_name',
	'_shipping_company' => 'set_shipping_company', '_shipping_address_1' => 'set_shipping_address_1',
	'_shipping_address_2' => 'set_shipping_address_2', '_shipping_city' => 'set_shipping_city',
	'_shipping_state' => 'set_shipping_state', '_shipping_postcode' => 'set_shipping_postcode',
	'_shipping_country' => 'set_shipping_country',
);
// itemmeta standard pe line_item (setate prin obiect, nu copiate ca meta)
$std_item_meta = array_flip( array( '_product_id', '_variation_id', '_qty', '_tax_class', '_line_subtotal', '_line_subtotal_tax', '_line_total', '_line_tax', '_line_tax_data' ) );

$res = mysqli_query( $db, "SELECT ID, post_status, post_date, post_date_gmt, post_excerpt FROM {$LP}posts WHERE post_type='shop_order' ORDER BY ID" );
$total = mysqli_num_rows( $res );
$count = 0; $skipped = 0; $errors = array(); $i = 0;

while ( $o = mysqli_fetch_assoc( $res ) ) {
	$i++;
	$old_id = (int) $o['ID'];
	if ( isset( $done_set[ (string) $old_id ] ) ) { $skipped++; continue; }

	// postmeta
	$meta = array();
	$mr = mysqli_query( $db, "SELECT meta_key, meta_value FROM {$LP}postmeta WHERE post_id=$old_id" );
	while ( $m = mysqli_fetch_assoc( $mr ) ) { $meta[ $m['meta_key'] ] = $m['meta_value']; }

	try {
		$order = new WC_Order();

		// billing/shipping
		foreach ( $field_setters as $mk => $fn ) {
			if ( isset( $meta[ $mk ] ) && '' !== $meta[ $mk ] ) { $order->$fn( $meta[ $mk ] ); }
		}

		// identitate / plată
		$order->set_currency( isset( $meta['_order_currency'] ) ? $meta['_order_currency'] : 'GBP' );
		$order->set_prices_include_tax( ( isset( $meta['_prices_include_tax'] ) ? $meta['_prices_include_tax'] : '' ) === 'yes' );
		$order->set_customer_id( 0 );
		if ( isset( $meta['_payment_method'] ) ) { $order->set_payment_method( $meta['_payment_method'] ); }
		if ( isset( $meta['_payment_method_title'] ) ) { $order->set_payment_method_title( $meta['_payment_method_title'] ); }
		if ( ! empty( $meta['_transaction_id'] ) ) { $order->set_transaction_id( $meta['_transaction_id'] ); }
		if ( ! empty( $meta['_customer_ip_address'] ) ) { $order->set_customer_ip_address( $meta['_customer_ip_address'] ); }
		if ( ! empty( $meta['_customer_user_agent'] ) ) { $order->set_customer_user_agent( $meta['_customer_user_agent'] ); }
		if ( ! empty( $meta['_created_via'] ) ) { $order->set_created_via( $meta['_created_via'] ); }
		if ( ! empty( $meta['_cart_hash'] ) ) { $order->set_cart_hash( $meta['_cart_hash'] ); }
		if ( ! empty( $meta['_order_key'] ) ) { $order->set_order_key( $meta['_order_key'] ); }
		if ( '' !== (string) $o['post_excerpt'] ) { $order->set_customer_note( $o['post_excerpt'] ); }

		// linii comandă
		$ir = mysqli_query( $db, "SELECT order_item_id, order_item_name, order_item_type FROM {$LP}woocommerce_order_items WHERE order_id=$old_id ORDER BY order_item_id" );
		while ( $it = mysqli_fetch_assoc( $ir ) ) {
			$iid  = (int) $it['order_item_id'];
			$im   = array();
			$imr  = mysqli_query( $db, "SELECT meta_key, meta_value FROM {$LP}woocommerce_order_itemmeta WHERE order_item_id=$iid" );
			while ( $x = mysqli_fetch_assoc( $imr ) ) { $im[ $x['meta_key'] ] = $x['meta_value']; }

			if ( 'line_item' === $it['order_item_type'] ) {
				$li = new WC_Order_Item_Product();
				$li->set_name( $it['order_item_name'] );
				$old_pid = isset( $im['_product_id'] ) ? (int) $im['_product_id'] : 0;
				if ( $old_pid && isset( $pmap[ (string) $old_pid ] ) ) { $li->set_product_id( (int) $pmap[ (string) $old_pid ] ); }
				if ( ! empty( $im['_variation_id'] ) && wc_get_product( (int) $im['_variation_id'] ) ) { $li->set_variation_id( (int) $im['_variation_id'] ); }
				$li->set_quantity( isset( $im['_qty'] ) ? (float) $im['_qty'] : 1 );
				if ( isset( $im['_line_subtotal'] ) ) { $li->set_subtotal( $im['_line_subtotal'] ); }
				if ( isset( $im['_line_total'] ) ) { $li->set_total( $im['_line_total'] ); }
				if ( isset( $im['_line_subtotal_tax'] ) ) { $li->set_subtotal_tax( $im['_line_subtotal_tax'] ); }
				if ( isset( $im['_line_tax'] ) ) { $li->set_total_tax( $im['_line_tax'] ); }
				if ( ! empty( $im['_tax_class'] ) ) { $li->set_tax_class( $im['_tax_class'] ); }
				if ( isset( $im['_line_tax_data'] ) ) { $t = maybe_unserialize( $im['_line_tax_data'] ); if ( is_array( $t ) ) { $li->set_taxes( $t ); } }
				// meta custom (add-on-uri YITH, pa_model, etichete Removal & Disposal etc.)
				foreach ( $im as $k => $v ) { if ( ! isset( $std_item_meta[ $k ] ) ) { $li->add_meta_data( $k, maybe_unserialize( $v ), true ); } }
				$order->add_item( $li );
			} elseif ( 'shipping' === $it['order_item_type'] ) {
				$si = new WC_Order_Item_Shipping();
				$si->set_method_title( $it['order_item_name'] );
				if ( isset( $im['method_id'] ) ) { $si->set_method_id( $im['method_id'] ); }
				if ( isset( $im['instance_id'] ) ) { $si->set_instance_id( $im['instance_id'] ); }
				if ( isset( $im['cost'] ) ) { $si->set_total( $im['cost'] ); }
				if ( isset( $im['taxes'] ) ) { $t = maybe_unserialize( $im['taxes'] ); if ( is_array( $t ) ) { $si->set_taxes( $t ); } }
				$order->add_item( $si );
			} elseif ( 'tax' === $it['order_item_type'] ) {
				$ti = new WC_Order_Item_Tax();
				$ti->set_name( $it['order_item_name'] );
				if ( isset( $im['rate_id'] ) ) { $ti->set_rate_id( (int) $im['rate_id'] ); }
				if ( isset( $im['label'] ) ) { $ti->set_label( $im['label'] ); }
				$ti->set_compound( ( isset( $im['compound'] ) ? $im['compound'] : '' ) === '1' );
				if ( isset( $im['tax_amount'] ) ) { $ti->set_tax_total( $im['tax_amount'] ); }
				if ( isset( $im['shipping_tax_amount'] ) ) { $ti->set_shipping_tax_total( $im['shipping_tax_amount'] ); }
				$order->add_item( $ti );
			}
		}

		// totaluri (setate explicit — fără recalcul)
		if ( isset( $meta['_cart_discount'] ) ) { $order->set_discount_total( $meta['_cart_discount'] ); }
		if ( isset( $meta['_cart_discount_tax'] ) ) { $order->set_discount_tax( $meta['_cart_discount_tax'] ); }
		if ( isset( $meta['_order_shipping'] ) ) { $order->set_shipping_total( $meta['_order_shipping'] ); }
		if ( isset( $meta['_order_shipping_tax'] ) ) { $order->set_shipping_tax( $meta['_order_shipping_tax'] ); }
		if ( isset( $meta['_order_tax'] ) ) { $order->set_cart_tax( $meta['_order_tax'] ); }
		if ( isset( $meta['_order_total'] ) ) { $order->set_total( $meta['_order_total'] ); }

		// date
		$created = ( $o['post_date_gmt'] && '0000-00-00 00:00:00' !== $o['post_date_gmt'] ) ? $o['post_date_gmt'] : $o['post_date'];
		$order->set_date_created( $created );
		if ( ! empty( $meta['_date_paid'] ) && is_numeric( $meta['_date_paid'] ) ) { $order->set_date_paid( (int) $meta['_date_paid'] ); }
		if ( ! empty( $meta['_date_completed'] ) && is_numeric( $meta['_date_completed'] ) ) { $order->set_date_completed( (int) $meta['_date_completed'] ); }

		// status
		$order->set_status( preg_replace( '/^wc-/', '', $o['post_status'] ) );

		// meta păstrate
		$order->update_meta_data( '_ef_legacy_order_id', $old_id );
		if ( isset( $meta['_customer_user'] ) ) { $order->update_meta_data( '_ef_legacy_customer_user', $meta['_customer_user'] ); }
		if ( isset( $meta['check_doors'] ) ) { $order->update_meta_data( 'check_doors', $meta['check_doors'] ); }
		if ( isset( $meta['_order_version'] ) ) { $order->update_meta_data( '_ef_legacy_order_version', $meta['_order_version'] ); }

		$order->save();
		$count++;
	} catch ( Exception $e ) {
		$errors[] = "comanda $old_id: " . $e->getMessage();
	}

	if ( 0 === $i % 500 ) {
		WP_CLI::log( "  ... $i/$total procesate (create=$count, sarite=$skipped, erori=" . count( $errors ) . ')' );
		wp_cache_flush();
	}
}

WP_CLI::log( "Comenzi: importate=$count | sarite(existau)=$skipped | erori=" . count( $errors ) . " | total legacy=$total" );
if ( $errors ) { foreach ( array_slice( $errors, 0, 20 ) as $e ) { WP_CLI::log( '  - ' . $e ); } }
WP_CLI::success( 'Migrare comenzi (pasul 5) finalizată.' );
