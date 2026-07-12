<?php
/**
 * Eco-Fridge Child — funcții temă copil.
 *
 * Găzduiește logica custom care NU se exprimă curat în Gutenberg (vezi AUDIT.md §6):
 *  - preț „from-price" / £POA pe produse (meta produs)
 *  - note livrare + paragraf T&C global pe pagina de produs (hook Woo)
 *  - înlocuiri de string WooCommerce moștenite din tema originală
 *
 * Secțiunile sunt schelet; se completează pe măsură ce reconstruim design-ul.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Încarcă stilul temei copil după al părintelui Astra.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'eco-fridge-child',
		get_stylesheet_uri(),
		array( 'astra-theme-css' ),
		wp_get_theme()->get( 'Version' )
	);
}, 15 );

/**
 * Afișează numărul original al comenzii (din site-ul vechi) în loc de ID-ul nou.
 * Comenzile migrate păstrează ID-ul legacy în meta `_ef_legacy_order_id`.
 */
add_filter( 'woocommerce_order_number', function ( $number, $order ) {
	$legacy = is_a( $order, 'WC_Order' ) ? $order->get_meta( '_ef_legacy_order_id' ) : '';
	return $legacy ? $legacy : $number;
}, 10, 2 );

/* -------------------------------------------------------------------------
 * TODO Faza 2 — de completat după importul datelor:
 *  [ ] from-price / £POA:  meta produs `_ef_from_price` + afișare în arhive/single
 *  [ ] T&C + note livrare:  woocommerce_after_single_product_summary (pattern global)
 *  [ ] înlocuiri string Woo:  gettext / woocommerce_* (ex. „CLICK HERE TO ADD…")
 *  [ ] „Back to {range} index":  breadcrumb contextual pe single-product
 * ------------------------------------------------------------------------- */
