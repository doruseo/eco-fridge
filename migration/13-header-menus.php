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

$cats = ef_build_menu( 'Header Categories', 'ef_header_categories', array(
	array( 'Display Cabinets',         '/product-category/display-cabinets/' ),
	array( 'Storage Cabinets',         '/product-category/storage-cabinets/' ),
	array( 'Bakery & Ovens',           '/product-category/bakery-cabinets/' ),
	array( 'Medical',                  '/product-category/medical/' ),
	array( 'Low Temperature Freezers', '/product-category/low-temperature-chest-freezers/' ),
	array( 'Used / Graded Units',      '/product-category/graded-units/' ),
	array( 'Commercial Fridge',        '/product-category/commercial-fridge/' ),
	array( 'Commercial Freezer',       '/product-category/commercial-freezer/' ),
) );

WP_CLI::log( "Header Primary menu = $primary, Header Categories menu = $cats" );
WP_CLI::success( 'Meniuri header custom create + asignate.' );
