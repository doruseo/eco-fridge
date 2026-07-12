<?php
/**
 * Pasul 7a: logo site + meniu principal bogat (1:1 cu eco-fridge.co.uk).
 * Rulare: wp eval-file wp-content/source-dump/_run.php
 *
 * Meniu top: Home, Display Cabinets (+sub-categorii), Storage Cabinets (+sub),
 * Bakery & Ovens, Medical, Low Temp Freezers, Used/Graded, Commercial Fridge,
 * Commercial Freezer, Blog, Contact Us (+sub pagini). Asignat pe locația Astra.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
global $wpdb;

/* ---- LOGO ---- */
$logo_id = (int) $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta}
	WHERE meta_key='_wp_attached_file' AND meta_value LIKE '%ECO-Fridge-Logo-side%'
	AND meta_value NOT LIKE '%cropped%' ORDER BY post_id ASC LIMIT 1" );
if ( $logo_id ) {
	set_theme_mod( 'custom_logo', $logo_id );
	update_option( 'site_logo', $logo_id );
	WP_CLI::log( "Logo setat: attachment #$logo_id" );
} else {
	WP_CLI::warning( 'Logo ECO-Fridge-Logo-side negăsit în media.' );
}

/* ---- MENIU ---- */
$menu_name = 'Primary Menu';
$menu = wp_get_nav_menu_object( $menu_name );
if ( $menu ) {
	// idempotent: golește itemele existente
	foreach ( wp_get_nav_menu_items( $menu->term_id ) as $it ) { wp_delete_post( $it->ID, true ); }
	$menu_id = $menu->term_id;
} else {
	$menu_id = wp_create_nav_menu( $menu_name );
}

$add = function ( $title, $args, $parent = 0 ) use ( $menu_id ) {
	return wp_update_nav_menu_item( $menu_id, 0, array_merge( array(
		'menu-item-title'     => $title,
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => $parent,
	), $args ) );
};
$add_cat = function ( $term_id, $parent = 0, $title = '' ) use ( $add ) {
	$t = get_term( $term_id, 'product_cat' );
	if ( ! $t || is_wp_error( $t ) ) { return 0; }
	return $add( $title ?: $t->name, array(
		'menu-item-object'    => 'product_cat',
		'menu-item-object-id' => $term_id,
		'menu-item-type'      => 'taxonomy',
	), $parent );
};
$add_url = function ( $title, $url, $parent = 0 ) use ( $add ) {
	return $add( $title, array( 'menu-item-url' => $url, 'menu-item-type' => 'custom' ), $parent );
};
$add_page = function ( $slug, $title, $parent = 0 ) use ( $add ) {
	$p = get_page_by_path( $slug );
	if ( ! $p ) { return 0; }
	return $add( $title, array( 'menu-item-object' => 'page', 'menu-item-object-id' => $p->ID, 'menu-item-type' => 'post_type' ), $parent );
};

// Home
$add_url( 'Home', home_url( '/' ) );

// Display Cabinets + copii
$display = $add_cat( 44, 0, 'Display Cabinets' );
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 44 ) ) as $c ) {
	if ( ! is_wp_error( $c ) ) { $add_cat( $c->term_id, $display ); }
}
// Storage Cabinets + copii
$storage = $add_cat( 45, 0, 'Storage Cabinets' );
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 45 ) ) as $c ) {
	if ( ! is_wp_error( $c ) ) { $add_cat( $c->term_id, $storage ); }
}
// Alte categorii top
$add_cat( 85, 0, 'Bakery & Ovens' );
$add_cat( 86, 0, 'Medical' );
$add_cat( 83, 0, 'Low Temperature Freezers' );
$add_cat( 73, 0, 'Used / Graded Units' );
$cf = $add_cat( 90, 0, 'Commercial Fridge' );
$add_cat( 18, $cf, 'Display Fridge' );
$add_cat( 23, $cf, 'Storage Fridge' );
$cfr = $add_cat( 91, 0, 'Commercial Freezer' );
$add_cat( 22, $cfr, 'Display Freezer' );
$add_cat( 24, $cfr, 'Storage Freezer' );

// Blog
$add_url( 'Blog', home_url( '/blog/' ) );

// Contact Us + sub
$contact = $add_page( 'contact-us', 'Contact Us' );
if ( ! $contact ) { $contact = $add_url( 'Contact Us', home_url( '/contact-us/' ) ); }
$add_page( 'about-us', 'About Us', $contact );
$add_page( 'support', 'Support', $contact );
$add_page( 'markets', 'Markets', $contact );

/* ---- asignare locație Astra ---- */
$locations = get_theme_mod( 'nav_menu_locations', array() );
$locations['primary'] = $menu_id;         // Astra primary
$locations['ast-hf-menu-1'] = $menu_id;   // Astra Header Builder primary menu component
set_theme_mod( 'nav_menu_locations', $locations );

$items = wp_get_nav_menu_items( $menu_id );
WP_CLI::log( 'Meniu „Primary Menu" construit: ' . count( $items ) . ' iteme, asignat pe primary + ast-hf-menu-1.' );
WP_CLI::success( 'Logo + meniu finalizate.' );
