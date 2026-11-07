<?php
/**
 * Step 15: rebuilds ONE authoritative menu, "Primary Menu" (#342), 1:1 with
 * the eco-fridge.co.uk hierarchy (original labels), where EVERY item is an
 * INTERNAL REFERENCE (product_cat category / page / product), never a custom link.
 * The whole tier 1→4 structure lives in the menu (editable in Appearance → Menus), not in code.
 *
 * Also turns on the NATIVE Astra Pro mega menu (post meta `_menu_item_megamenu`) on the large
 * branches, then assigns the menu to the Astra locations and deletes the old clone menus
 * (Header Primary #400, Header Categories #401).
 *
 * Idempotent. Run: cp migration/15-menu-rebuild.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Menu node: (original) label + internal reference + children + mega options. */
function efm_n( $label, $ref, $children = array(), $opts = array() ) {
	return array( 'label' => $label, 'ref' => $ref, 'children' => $children, 'opts' => $opts );
}

/**
 * Resolves a reference ('home' | 'cat:slug' | 'page:path' | 'product:slug') into the right
 * wp_update_nav_menu_item arguments (internal object, NOT a custom link). Returns null if missing.
 */
function efm_resolve_ref( $ref ) {
	if ( 'home' === $ref ) {
		$fp = (int) get_option( 'page_on_front' );
		if ( $fp ) {
			return array( 'menu-item-object' => 'page', 'menu-item-object-id' => $fp, 'menu-item-type' => 'post_type' );
		}
		// fallback: link to home (only if there is no front page)
		return array( 'menu-item-url' => home_url( '/' ), 'menu-item-type' => 'custom' );
	}
	list( $kind, $slug ) = array_pad( explode( ':', $ref, 2 ), 2, '' );
	switch ( $kind ) {
		case 'cat':
			$t = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) ) {
				return array( 'menu-item-object' => 'product_cat', 'menu-item-object-id' => (int) $t->term_id, 'menu-item-type' => 'taxonomy' );
			}
			break;
		case 'page':
			$p = get_page_by_path( $slug );
			if ( $p ) {
				return array( 'menu-item-object' => 'page', 'menu-item-object-id' => (int) $p->ID, 'menu-item-type' => 'post_type' );
			}
			break;
		case 'product':
			$p = get_page_by_path( $slug, OBJECT, 'product' );
			if ( $p ) {
				return array( 'menu-item-object' => 'product', 'menu-item-object-id' => (int) $p->ID, 'menu-item-type' => 'post_type' );
			}
			break;
	}
	return null;
}

/**
 * Inserts the tree into the menu recursively. Sets the title explicitly (the original label
 * overrides the object name) + the Astra mega meta. Collects warnings for missing references.
 */
function efm_insert( $nodes, $menu_id, $parent, &$warnings ) {
	foreach ( $nodes as $node ) {
		$args = efm_resolve_ref( $node['ref'] );
		if ( null === $args ) {
			$warnings[] = $node['ref'] . '  ("' . $node['label'] . '")';
			continue;
		}
		$args = array_merge( $args, array(
			'menu-item-title'     => $node['label'],
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => (int) $parent,
		) );
		$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
		if ( is_wp_error( $item_id ) || ! $item_id ) {
			$warnings[] = 'INSERT FAIL: ' . $node['label'];
			continue;
		}

		// native Astra mega menu on this item (if requested)
		$o = $node['opts'];
		if ( ! empty( $o['mega'] ) ) {
			update_post_meta( $item_id, '_menu_item_megamenu', 'megamenu' );
			update_post_meta( $item_id, '_menu_item_megamenu_col', (string) $o['mega'] );
			if ( ! empty( $o['width'] ) ) {
				update_post_meta( $item_id, '_menu_item_megamenu_width', $o['width'] );
			}
		} else {
			delete_post_meta( $item_id, '_menu_item_megamenu' );
		}
		// item = column heading in the mega menu (tier 2 of the Commercial branches)
		if ( ! empty( $o['heading'] ) ) {
			update_post_meta( $item_id, '_menu_item_megamenu_enable_heading', 'enable-heading' );
		} else {
			delete_post_meta( $item_id, '_menu_item_megamenu_enable_heading' );
		}

		if ( ! empty( $node['children'] ) ) {
			efm_insert( $node['children'], $menu_id, $item_id, $warnings );
		}
	}
}

/* ------------------------------------------------------------------ TREE 1:1 */

$H = array( 'heading' => true ); // marks a tier-2 item as a column heading in the mega menu

$tree = array(
	efm_n( 'Home', 'home' ),

	// —— DISPLAY CABINETS (22 flat sub-categories) → 4-column mega, full width ——
	efm_n( 'Display Cabinets', 'cat:display-cabinets', array(
		efm_n( 'Standard Silver Steel Multideck Display Fridges', 'cat:silver-steel-multideck-display-chillers' ),
		efm_n( 'Premium Mirrored Stainless Steel Multidecks', 'cat:premium-mirrored-stainless-steel-multidecks' ),
		efm_n( 'Standard Black Steel Slimline Multideck Display Fridge', 'cat:black-steel-slimline-multideck-display-fridge' ),
		efm_n( 'Premium Mirrored Stainless Steel Shutter Multidecks', 'cat:premium-mirrored-stainless-steel-shutter-multidecks' ),
		efm_n( 'Dual Temperature Hot & Cold Premium Stainless Steel Multideck', 'cat:dual-temperature-hot-and-cold-premium-mirrored-stainless-steel-multideck' ),
		efm_n( 'Standard Upright Display Chiller', 'cat:upright-display-chiller' ),
		efm_n( 'Premium Upright Display Fridge', 'cat:upright-display-chillers' ),
		efm_n( 'Premium Upright Display Freezer with Glass Doors', 'cat:upright-display-freezers' ),
		efm_n( 'Premium Heated Display Cabinets', 'cat:heated-display-cabinets' ),
		efm_n( 'Standard Patisserie Display Chillers', 'cat:patisserie-display-fridges' ),
		efm_n( 'Standard Patisserie Slimline Display Fridges', 'cat:standard-patisserie-slimline-display-fridges' ),
		efm_n( 'Premium Patisserie Display Fridges', 'cat:premium-patisserie-display-fridges' ),
		efm_n( 'Standard Serve Over Display', 'cat:serve-over-display' ),
		efm_n( 'Standard Grab and Go Display Chillers', 'cat:standard-grab-and-go-display-fridges' ),
		efm_n( 'Premium Grab & Go Display Chillers', 'cat:premium-grab-and-go-display-fridges' ),
		efm_n( 'Premium Double Glazed UV Bonded Counter Display Chiller', 'cat:double-glazed-counter-display-chiller' ),
		efm_n( 'Standard Double Glazed Counter', 'cat:standard-counter-display-chiller' ),
		efm_n( 'Standard Upright Display Freezers', 'cat:standard-upright-display-freezers' ),
		efm_n( 'Standard Sliding Glass Lid Chest Freezers', 'cat:glass-lid-chest-freezer' ),
		efm_n( 'Premium Back Bar Display Chillers', 'cat:back-bar-display-chillers' ),
		efm_n( 'Premium Upright Cake Display Fridge', 'cat:upright-cake-displays' ),
		efm_n( 'Premium Double Glazed Counter Top Display', 'cat:double-glazed-counter-top-display' ),
	), array( 'mega' => 4, 'width' => 'full' ) ),

	// —— STORAGE CABINETS (13 categories + 3 products + bakery retarder) → 4-column mega ——
	efm_n( 'Storage Cabinets', 'cat:storage-cabinets', array(
		efm_n( 'Standard Upright Stainless Steel Gastronorm Chiller & Fish/Meat Cabinet', 'cat:upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet' ),
		efm_n( 'Standard Upright Stainless Steel Gastronorm Freezers', 'cat:standard-upright-stainless-steel-gastronorm-freezers' ),
		efm_n( 'Standard Upright Storage Chiller', 'cat:upright-storage-chiller' ),
		efm_n( 'Standard Upright Storage Freezer', 'cat:standard-upright-storage-freezer' ),
		efm_n( 'Premium Upright Storage Chillers', 'cat:upright-storage-chillers' ),
		efm_n( 'Premium Upright Storage Freezers', 'cat:upright-storage-freezers' ),
		efm_n( 'Standard Stainless Steel Under Counter Chiller', 'cat:standard-stainless-steel-under-counter-chiller' ),
		efm_n( 'Standard Stainless Steel Under Counter Freezer', 'cat:standard-stainless-steel-under-counter-freezer' ),
		efm_n( 'Premium Stainless Steel Under Counter Fridge', 'cat:stainless-steel-under-counter-chiller' ),
		efm_n( 'Premium Stainless Steel Under Counter Freezer', 'cat:premium-stainless-steel-under-counter-freezer' ),
		efm_n( 'Premium Stainless Steel Counter Chillers', 'cat:stainless-steel-counter-chillers' ),
		efm_n( 'Premium Stainless Steel Counter Freezers', 'cat:stainless-steel-counter-freezers' ),
		efm_n( 'Low Temperature Chest Freezer -45', 'product:low-temperature-chest-freezer-45' ),
		efm_n( 'Low Temperature Chest Freezer -65', 'product:low-temperature-chest-freezer-65' ),
		efm_n( 'Ultra Low Temperature Chest Freezer -86', 'product:ultra-low-temperature-chest-freezer-86' ),
		efm_n( 'Upright Bakery Fridges & Freezers – Stainless Steel Retarder Provers', 'cat:stainless-steel-upright-bakery-cabinets' ),
	), array( 'mega' => 4, 'width' => 'full' ) ),

	efm_n( 'Bakery & Ovens', 'cat:bakery-cabinets' ),
	efm_n( 'Medical', 'cat:medical' ),
	efm_n( 'Low Temperature Freezers', 'cat:low-temperature-chest-freezers' ),
	efm_n( 'Used', 'cat:graded-units' ),

	// —— COMMERCIAL FRIDGE (tier 2→4) → grouped 2-column mega with headings ——
	efm_n( 'Commercial Fridge', 'cat:commercial-fridge', array(
		efm_n( 'Display Fridge', 'cat:commercial-display-fridge', array(
			efm_n( 'Open Fridge for Shop', 'cat:multideck-fridge' ),
			efm_n( 'Grab & Go Fridge', 'cat:grab-and-go' ),
			efm_n( 'Patisserie Fridge', 'cat:patisserie-fridge' ),
			efm_n( 'Drinks Fridge', 'cat:commercial-drinks-fridge', array(
				efm_n( 'Bar Fridge – Mini Fridge', 'cat:bar-fridge' ),
				efm_n( 'Beer Fridge', 'cat:beer-fridge' ),
				efm_n( 'Wine Fridge', 'cat:wine-fridge' ),
			) ),
			efm_n( 'Glass Front Fridge', 'cat:glass-front-fridge' ),
			efm_n( 'Glass Door Fridge', 'cat:upright-fridge' ),
			efm_n( 'Deli Counter Serve Over Fridge', 'cat:serve-over' ),
			efm_n( 'Counter Fridge', 'cat:counter-fridge', array(
				efm_n( 'Countertop Fridge', 'cat:top-counter' ),
			) ),
		), $H ),
		efm_n( 'Storage Fridge', 'cat:storage-fridge', array(
			efm_n( 'Under Counter Fridge', 'cat:under-counter-fridge' ),
			efm_n( 'Upright Storage Fridge', 'cat:upright-fridge-storage-fridge' ),
			efm_n( 'Meat Fridge', 'cat:gastronorm-fridge' ),
		), $H ),
	), array( 'mega' => 2 ) ),

	// —— COMMERCIAL FREEZER (tier 2→3) → grouped 2-column mega with headings ——
	efm_n( 'Commercial Freezer', 'cat:commercial-freezer', array(
		efm_n( 'Display Freezer', 'cat:display-freezer', array(
			efm_n( 'Chest Freezer', 'cat:chest-freezer' ),
			efm_n( 'Upright Display Freezer', 'cat:upright-freezer' ),
		), $H ),
		efm_n( 'Storage Freezer', 'cat:storage-freezer', array(
			efm_n( 'Under Counter Freezer', 'cat:under-counter-freezer' ),
			efm_n( 'Upright Storage Freezer', 'cat:upright-freezer-storage-freezer' ),
			efm_n( 'Meat Freezer', 'cat:gastronorm-freezer' ),
		), $H ),
	), array( 'mega' => 2 ) ),

	efm_n( 'Blog', 'page:blog' ),

	// —— CONTACT US (classic dropdown, with Markets nested) ——
	efm_n( 'Contact Us', 'page:contact-us', array(
		efm_n( 'About Us', 'page:about-us' ),
		efm_n( 'Support', 'page:support' ),
		efm_n( 'Cookie policy', 'page:cookie-policy-uk' ),
		efm_n( 'Markets', 'page:markets', array(
			efm_n( 'Display Cabinets', 'cat:display-cabinets' ),
			efm_n( 'Storage cabinets', 'cat:storage-cabinets' ),
			efm_n( 'Cafe/Coffee Shops', 'page:markets/coffee-shops' ),
			efm_n( 'Convenience Stores', 'page:markets/convenience-stores' ),
			efm_n( 'Restaurants & Bars', 'page:markets/restaurants' ),
			efm_n( 'Delis & Butchers', 'page:markets/delis-and-butchers' ),
		) ),
	) ),
);

/* ------------------------------------------------------------------ BUILD */

$menu_name = 'Primary Menu';
$menu = wp_get_nav_menu_object( $menu_name );
$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $menu_name );

// idempotent: empties the existing items
foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) {
	wp_delete_post( $it->ID, true );
}

$warnings = array();
efm_insert( $tree, $menu_id, 0, $warnings );

// assign to the Astra locations (native header + primary)
$locations = get_theme_mod( 'nav_menu_locations', array() );
$locations['primary']      = $menu_id;
$locations['ast-hf-menu-1'] = $menu_id;
// unassign the retired custom locations
unset( $locations['ef_header_primary'], $locations['ef_header_categories'] );
set_theme_mod( 'nav_menu_locations', $locations );

// delete the old clone menus
foreach ( array( 'Header Primary', 'Header Categories' ) as $old ) {
	$m = wp_get_nav_menu_object( $old );
	if ( $m ) { wp_delete_nav_menu( $m->term_id ); }
}

$items = wp_get_nav_menu_items( $menu_id );
WP_CLI::log( 'Menu "Primary Menu" (#' . $menu_id . ') rebuilt: ' . count( $items ) . ' items, assigned to primary + ast-hf-menu-1.' );
if ( $warnings ) {
	WP_CLI::warning( 'UNresolved references (' . count( $warnings ) . '):' );
	foreach ( $warnings as $w ) { WP_CLI::log( '   - ' . $w ); }
} else {
	WP_CLI::log( 'All references resolved (0 custom links, except Home if there were no front page).' );
}
WP_CLI::success( 'Authoritative menu and native Astra mega menu finished.' );
