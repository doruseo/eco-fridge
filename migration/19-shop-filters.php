<?php
/**
 * Pasul 19: SIDEBAR DE FILTRE pe arhivele de categorii de produse (stânga).
 *
 * A) Dimensiuni → atribute filtrabile: parsează atributul local „dimensions"
 *    (format „W x D x H mm") și creează atributele globale `pa_width` / `pa_height`
 *    cu INTERVALE (bucket-uri), pe care le asignează produselor.
 * B) Layout + widget-uri: setează arhivele Woo pe „left-sidebar" și populează
 *    sidebar-ul „astra-woo-shop-sidebar" (apare doar pe magazin) cu:
 *      - Sub-categorii (widget custom contextual)  - Filter by price (nativ)
 *      - Width (layered nav pa_width)              - Height (layered nav pa_height)
 *    Toate contextuale la categoria curentă.
 *
 * Idempotent. Rulare: cp migration/19-shop-filters.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- A) ATRIBUTE pa_width / pa_height din dimensiuni ---------- */

/** Creează (dacă lipsește) atributul global + înregistrează taxonomia în request-ul curent. */
function ef_ensure_attribute( $label, $slug ) {
	$tax = wc_attribute_taxonomy_name( $slug ); // pa_<slug>
	if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
		$r = wc_create_attribute( array(
			'name'         => $label,
			'slug'         => $slug,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );
		if ( is_wp_error( $r ) ) { WP_CLI::warning( "wc_create_attribute($slug): " . $r->get_error_message() ); return null; }
	}
	if ( ! taxonomy_exists( $tax ) ) {
		register_taxonomy( $tax, array( 'product' ), array(
			'hierarchical' => false, 'label' => $label, 'query_var' => true, 'rewrite' => false, 'show_ui' => false,
		) );
	}
	return $tax;
}

/** Creează termenul (bucket) și îi setează ordinea (menu_order) în layered nav. */
function ef_ensure_term( $name, $tax, $order ) {
	$t = get_term_by( 'name', $name, $tax );
	if ( $t && ! is_wp_error( $t ) ) {
		$tid = (int) $t->term_id;
	} else {
		$res = wp_insert_term( $name, $tax );
		if ( is_wp_error( $res ) ) { return 0; }
		$tid = (int) $res['term_id'];
	}
	update_term_meta( $tid, 'order_' . $tax, (int) $order );
	return $tid;
}

/** Găsește bucket-ul (low, high] pentru o valoare. */
function ef_bucket( $value, $buckets ) {
	foreach ( $buckets as $b ) {
		if ( $value > $b['low'] && $value <= $b['high'] ) { return $b['tid']; }
	}
	return 0;
}

$tax_w = ef_ensure_attribute( 'Width', 'width' );
$tax_h = ef_ensure_attribute( 'Height', 'height' );
if ( ! $tax_w || ! $tax_h ) { WP_CLI::error( 'Nu s-au putut crea atributele pa_width/pa_height.' ); }

$width_defs = array(
	array( 'Up to 600 mm', 0, 600 ),
	array( '600–900 mm', 600, 900 ),
	array( '900–1200 mm', 900, 1200 ),
	array( '1200–1500 mm', 1200, 1500 ),
	array( 'Over 1500 mm', 1500, PHP_INT_MAX ),
);
$height_defs = array(
	array( 'Up to 900 mm', 0, 900 ),
	array( '900–1400 mm', 900, 1400 ),
	array( '1400–1800 mm', 1400, 1800 ),
	array( 'Over 1800 mm', 1800, PHP_INT_MAX ),
);

$width_buckets = array();
foreach ( $width_defs as $i => $d ) {
	$width_buckets[] = array( 'tid' => ef_ensure_term( $d[0], $tax_w, $i + 1 ), 'low' => $d[1], 'high' => $d[2] );
}
$height_buckets = array();
foreach ( $height_defs as $i => $d ) {
	$height_buckets[] = array( 'tid' => ef_ensure_term( $d[0], $tax_h, $i + 1 ), 'low' => $d[1], 'high' => $d[2] );
}

$assigned = 0; $skipped = 0;
$products = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) );
foreach ( $products as $id ) {
	$p = wc_get_product( $id );
	if ( ! $p ) { continue; }
	$attrs = $p->get_attributes();
	if ( empty( $attrs['dimensions'] ) || ! is_object( $attrs['dimensions'] ) ) { $skipped++; continue; }
	$opts = $attrs['dimensions']->get_options();
	$str  = $opts ? (string) reset( $opts ) : '';
	if ( ! preg_match_all( '/(\d{2,5})\s*mm/i', $str, $nm ) || count( $nm[1] ) < 3 ) { $skipped++; continue; }
	$nums = array_map( 'intval', $nm[1] );
	$w    = $nums[0]; // W x D x H
	$h    = $nums[2];
	$wt   = ef_bucket( $w, $width_buckets );
	$ht   = ef_bucket( $h, $height_buckets );
	if ( $wt ) { wp_set_object_terms( $id, array( (int) $wt ), $tax_w, false ); }
	if ( $ht ) { wp_set_object_terms( $id, array( (int) $ht ), $tax_h, false ); }
	$assigned++;
}
WP_CLI::log( "Dimensiuni: $assigned produse etichetate cu pa_width/pa_height ($skipped sărite)." );

/* ---------- B) LAYOUT + WIDGET-URI în sidebar-ul de shop ---------- */

$s = get_option( 'astra-settings', array() );
if ( ! is_array( $s ) ) { $s = array(); }
$s['archive-product-sidebar-layout'] = 'left-sidebar'; // arhive produse (categorii) = sidebar stânga
$s['woocommerce-sidebar-layout']     = 'left-sidebar'; // fallback global shop
$s['site-sidebar-width']             = 20;             // sidebar mai îngust (implicit Astra = 30%)
update_option( 'astra-settings', $s );

// instanțe widget
$pf = get_option( 'widget_woocommerce_price_filter', array() );
$pf[2] = array( 'title' => 'Price' );
$pf['_multiwidget'] = 1;
update_option( 'widget_woocommerce_price_filter', $pf );

$ln = get_option( 'widget_woocommerce_layered_nav', array() );
$ln[2] = array( 'title' => 'Width', 'attribute' => 'width', 'display_type' => 'list', 'query_type' => 'or' );
$ln[3] = array( 'title' => 'Height', 'attribute' => 'height', 'display_type' => 'list', 'query_type' => 'or' );
$ln['_multiwidget'] = 1;
update_option( 'widget_woocommerce_layered_nav', $ln );

$sc = get_option( 'widget_ef_subcat_filter', array() );
$sc[2] = array( 'title' => 'Categories' );
$sc['_multiwidget'] = 1;
update_option( 'widget_ef_subcat_filter', $sc );

// asignează în sidebar-ul de shop (apare doar pe magazin/arhive)
$sw = get_option( 'sidebars_widgets', array() );
if ( ! is_array( $sw ) ) { $sw = array(); }
$sw['astra-woo-shop-sidebar'] = array(
	'ef_subcat_filter-2',
	'woocommerce_price_filter-2',
	'woocommerce_layered_nav-2',
	'woocommerce_layered_nav-3',
);
update_option( 'sidebars_widgets', $sw );

WP_CLI::success( 'Sidebar de filtre (sub-categorii + preț + lățime + înălțime) configurat pe arhivele de categorii.' );
