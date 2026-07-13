<?php
/**
 * Pasul 13: creează meniurile pentru header-ul custom pe 2 rânduri și le asignează
 * la locațiile `ef_header_primary` (Home/Blog/About/Contact) și
 * `ef_header_categories` (categoriile de produse). Editabile din Appearance → Menus.
 * Idempotent (reface itemele la fiecare rulare). Rulare: wp eval-file _run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Creează/actualizează un meniu cu iteme custom-link și îl asignează la o locație.
 *
 * @param string $name     Numele meniului.
 * @param string $location Locația de temă.
 * @param array  $items    listă de [ titlu, url ].
 */
function ef_build_menu( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );

	// golește itemele existente (idempotent)
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
 * Clonează în meniul $dst_name ramurile de categorii de produse din meniul sursă
 * $src_menu_id (top-level cu URL /product-category/ + toate sub-itemele lor),
 * păstrând ierarhia → dropdowns în header rând 2. Idempotent.
 */
function ef_clone_category_branches( $src_menu_id, $dst_name, $location ) {
	$src = wp_get_nav_menu_items( $src_menu_id );
	if ( ! $src ) { return 0; }

	$menu = wp_get_nav_menu_object( $dst_name );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $dst_name );
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) {
		wp_delete_post( $it->ID, true );
	}

	// top-level de păstrat = cele cu /product-category/ în URL, în ordinea din sursă
	$keep_top = array();
	foreach ( $src as $it ) {
		if ( ( empty( $it->menu_item_parent ) || '0' === $it->menu_item_parent )
			&& false !== strpos( (string) $it->url, '/product-category/' ) ) {
			$keep_top[] = $it->ID;
		}
	}

	// recreează recursiv (old ID -> new ID) pentru top + descendenți
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
		// copii
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

// Sursa = meniul principal complet (52 iteme, cu sub-categoriile).
$src_locations = get_nav_menu_locations();
$src_menu_id = isset( $src_locations['primary'] ) ? (int) $src_locations['primary'] : 0;
if ( ! $src_menu_id ) {
	$m = wp_get_nav_menu_object( 'Primary Menu' );
	$src_menu_id = $m ? (int) $m->term_id : 0;
}

$cats = $src_menu_id ? ef_clone_category_branches( $src_menu_id, 'Header Categories', 'ef_header_categories' ) : 0;

WP_CLI::log( "Header Primary menu = $primary, Header Categories menu = $cats (sursa $src_menu_id)" );
WP_CLI::success( 'Meniuri header custom create + asignate.' );
