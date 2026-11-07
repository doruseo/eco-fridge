<?php
/**
 * Step 22: moves header row 2 (the commercial category menu) into
 * the LAST row of the Astra Header Builder (Below Header), as a NATIVE menu editable
 * from Customize, with the STANDARD Astra cart.
 *
 *  - creates the SEPARATE "Commercial Menu" with the 8 top-level product
 *    categories + their sub-categories (TAXONOMY references, NOT custom links), cloned from
 *    the authoritative menu #342. Native Astra mega menu on the large branches.
 *  - assigns "Commercial Menu" to the `ast-hf-menu-2` location (menu-2 component).
 *  - configures Below Header: menu-2 (left) + native woo-cart (right).
 *
 * Idempotent. Run: cp migration/22-below-header-menu.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---- source: the authoritative menu #342 ---- */
$src = wp_get_nav_menu_object( 'Primary Menu' );
$src_id = $src ? (int) $src->term_id : 0;
if ( ! $src_id ) { WP_CLI::error( 'Primary Menu (#342) not found.' ); }
$src_items = wp_get_nav_menu_items( $src_id );

/* ---- the separate "Commercial Menu" (idempotent) ---- */
$name = 'Commercial Menu';
$m = wp_get_nav_menu_object( $name );
$menu_id = $m ? (int) $m->term_id : (int) wp_create_nav_menu( $name );
foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) { wp_delete_post( $it->ID, true ); }

/* children by parent in the source */
$by = array();
foreach ( (array) $src_items as $it ) { $by[ (int) $it->menu_item_parent ][] = $it; }

/* the top-level product categories (the 8 of them) from #342, in order */
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

	// native Astra mega menu on the large (top-level) branches
	$t = get_term( (int) $src_item->object_id, 'product_cat' );
	if ( 0 === (int) $new_parent && $t && in_array( $t->slug, $mega_slugs, true ) ) {
		update_post_meta( $new_id, '_menu_item_megamenu', 'megamenu' );
		update_post_meta( $new_id, '_menu_item_megamenu_col', in_array( $t->slug, array( 'display-cabinets', 'storage-cabinets' ), true ) ? '4' : '2' );
	}
	// clone the children recursively
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

/* NB: "Commercial Menu" is shown by the CUSTOM header on row 2 (the green bar), with
 * icon-mega: see astra-child/inc/header.php (it reads the menu by NAME). Row 1 =
 * "Landing Pages Menu" (primary location). The native Astra header stays hidden by CSS.
 * (The native Below Header is no longer configured.) */

$items = wp_get_nav_menu_items( $menu_id );
WP_CLI::log( '"Commercial Menu" #' . $menu_id . ' created: ' . count( $items ) . ' items (' . $count_top . ' top categories), read by the custom header on row 2.' );
WP_CLI::success( 'Commercial Menu created (row 2 of the custom header).' );
