<?php
/**
 * Pasul 22: mută rândul 2 al header-ului (meniul de categorii comerciale) în
 * ULTIMUL rând al Header Builder-ului Astra (Below Header), ca meniu NATIV editabil
 * din Customize, cu COȘUL STANDARD Astra.
 *
 *  - creează meniul INDIVIDUAL „Commercial Menu" cu cele 8 categorii top-level de
 *    produs + sub-categoriile lor (referințe TAXONOMY, NU custom links), clonate din
 *    meniul autoritar #342. Mega nativ Astra pe ramurile mari.
 *  - asignează „Commercial Menu" pe locația `ast-hf-menu-2` (componenta menu-2).
 *  - configurează Below Header: menu-2 (stânga) + woo-cart nativ (dreapta).
 *
 * Idempotent. Rulare: cp migration/22-below-header-menu.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---- sursa: meniul autoritar #342 ---- */
$src = wp_get_nav_menu_object( 'Primary Menu' );
$src_id = $src ? (int) $src->term_id : 0;
if ( ! $src_id ) { WP_CLI::error( 'Primary Menu (#342) negăsit.' ); }
$src_items = wp_get_nav_menu_items( $src_id );

/* ---- meniul individual „Commercial Menu" (idempotent) ---- */
$name = 'Commercial Menu';
$m = wp_get_nav_menu_object( $name );
$menu_id = $m ? (int) $m->term_id : (int) wp_create_nav_menu( $name );
foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) { wp_delete_post( $it->ID, true ); }

/* copiii pe părinte în sursă */
$by = array();
foreach ( (array) $src_items as $it ) { $by[ (int) $it->menu_item_parent ][] = $it; }

/* categoriile top-level de produs (cele 8) din #342, în ordine */
$mega_slugs = array( 'display-cabinets', 'storage-cabinets', 'commercial-fridge', 'commercial-freezer' );

$clone = function ( $src_item, $new_parent ) use ( &$clone, $by, $menu_id, $mega_slugs ) {
	if ( 'taxonomy' !== $src_item->type || 'product_cat' !== $src_item->object ) { return; }
	$new_id = wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-title'     => $src_item->title,
		'menu-item-object'    => 'product_cat',
		'menu-item-object-id' => (int) $src_item->object_id,
		'menu-item-type'      => 'taxonomy',
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => (int) $new_parent,
	) );
	if ( is_wp_error( $new_id ) || ! $new_id ) { return; }

	// mega nativ Astra pe ramurile mari (top-level)
	$t = get_term( (int) $src_item->object_id, 'product_cat' );
	if ( 0 === (int) $new_parent && $t && in_array( $t->slug, $mega_slugs, true ) ) {
		update_post_meta( $new_id, '_menu_item_megamenu', 'megamenu' );
		update_post_meta( $new_id, '_menu_item_megamenu_col', in_array( $t->slug, array( 'display-cabinets', 'storage-cabinets' ), true ) ? '4' : '2' );
	}
	// clonează recursiv copiii
	if ( ! empty( $by[ $src_item->ID ] ) ) {
		foreach ( $by[ $src_item->ID ] as $child ) { $clone( $child, $new_id ); }
	}
};

$count_top = 0;
foreach ( (array) ( $by[0] ?? array() ) as $top ) {
	if ( 'taxonomy' === $top->type && 'product_cat' === $top->object ) {
		$clone( $top, 0 );
		$count_top++;
	}
}

/* NB: „Commercial Menu" e afișat de header-ul CUSTOM pe rândul 2 (bară verde), cu
 * icon-mega — vezi astra-child/inc/header.php (îl citește după NUME). Rândul 1 =
 * „Landing Pages Menu" (locația primary). Header-ul Astra nativ rămâne ascuns prin CSS.
 * (Nu mai configurăm Below Header nativ.) */

$items = wp_get_nav_menu_items( $menu_id );
WP_CLI::log( '„Commercial Menu" #' . $menu_id . ' creat: ' . count( $items ) . ' iteme (' . $count_top . ' categorii top), citit de header-ul custom pe rândul 2.' );
WP_CLI::success( 'Commercial Menu creat (rândul 2 al header-ului custom).' );
