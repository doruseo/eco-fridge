<?php
/**
 * Step 13: creates the menus for the custom two-row header and assigns them
 * to the `ef_header_primary` (Home/Blog/About/Contact) and
 * `ef_header_categories` (product categories) locations. Editable from Appearance → Menus.
 * Idempotent (rebuilds the items on every run). Run: wp eval-file _run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Creates/updates a menu with custom-link items and assigns it to a location.
 *
 * @param string $name     Menu name.
 * @param string $location Theme location.
 * @param array  $items    list of [ title, url ].
 */
function ef_build_menu( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );

	// empty the existing items (idempotent)
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) {
		wp_delete_post( $it->ID, true );
	}

	foreach ( $items as $it ) {
		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'   => $it[0],
			'menu-item-url'     => home_url( $it[1] ),
			'menu-item-status'  => 'publish',
			'menu-item-type'    => 'custom',
		) );
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	return $menu_id;
}

$primary = ef_build_menu( 'Header Primary', 'ef_header_primary', array(
	array( 'Home',       '/' ),
	array( 'Blog',       '/blog/' ),
	array( 'About Us',   '/about-us/' ),
	array( 'Contact Us', '/contact-us/' ),
) );

/**
 * Clones into menu $dst_name the product category branches of the source menu
 * $src_menu_id (top level with a /product-category/ URL + all their sub-items),
 * keeping the hierarchy -> dropdowns in header row 2. Idempotent.
 */
function ef_clone_category_branches( $src_menu_id, $dst_name, $location ) {
	$src = wp_get_nav_menu_items( $src_menu_id );
	if ( ! $src ) { return 0; }

	$menu = wp_get_nav_menu_object( $dst_name );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $dst_name );
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) {
		wp_delete_post( $it->ID, true );
	}

	// top level to keep = those with /product-category/ in the URL, in source order
	$keep_top = array();
	foreach ( $src as $it ) {
		if ( ( empty( $it->menu_item_parent ) || '0' === $it->menu_item_parent )
			&& false !== strpos( (string) $it->url, '/product-category/' ) ) {
			$keep_top[] = $it->ID;
		}
	}

	// recreate recursively (old ID -> new ID) for the top level and descendants
	$id_map = array();
	$create = function ( $old_id, $new_parent ) use ( &$create, &$id_map, $src, $menu_id ) {
		$item = null;
		foreach ( $src as $s ) { if ( (int) $s->ID === (int) $old_id ) { $item = $s; break; } }
		if ( ! $item ) { return; }
		$new_id = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'     => $item->title,
			'menu-item-url'       => $item->url,
			'menu-item-type'      => 'custom',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => (int) $new_parent,
		) );
		$id_map[ $old_id ] = $new_id;
		// children
		foreach ( $src as $c ) {
			if ( (int) $c->menu_item_parent === (int) $old_id ) {
				$create( $c->ID, $new_id );
			}
		}
	};
	foreach ( $keep_top as $tid ) { $create( $tid, 0 ); }

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	return $menu_id;
}

// Source = the full main menu (52 items, with the sub-categories).
$src_locations = get_nav_menu_locations();
$src_menu_id = isset( $src_locations['primary'] ) ? (int) $src_locations['primary'] : 0;
if ( ! $src_menu_id ) {
	$m = wp_get_nav_menu_object( 'Primary Menu' );
	$src_menu_id = $m ? (int) $m->term_id : 0;
}

$cats = $src_menu_id ? ef_clone_category_branches( $src_menu_id, 'Header Categories', 'ef_header_categories' ) : 0;

WP_CLI::log( "Header Primary menu = $primary, Header Categories menu = $cats (source $src_menu_id)" );
WP_CLI::success( 'Custom header menus created and assigned.' );
