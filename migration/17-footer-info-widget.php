<?php
/**
 * Step 17: the footer "Information" column = a "Navigation Menu" WIDGET (Astra Footer
 * Builder → Widget 1), driven by a WP menu editable from Appearance → Menus, and
 * the widget from Customize → Footer Builder → Widget 1 (or Appearance → Widgets).
 * Replaces the `footer-html-3` HTML element.
 *
 * Idempotent. Run: cp migration/17-footer-info-widget.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* 1) "Footer Information" menu: internal references (pages), not custom links. */
$menu_name = 'Footer Information';
$m         = wp_get_nav_menu_object( $menu_name );
$menu_id   = $m ? (int) $m->term_id : (int) wp_create_nav_menu( $menu_name );
foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) {
	wp_delete_post( $it->ID, true );
}
$pages = array(
	array( 'privacy-cookies-policy', 'Privacy & Cookies Policy' ),
	array( 'returns',                'Returns Policy' ),
	array( 'terms-conditions',       'Terms & Conditions' ),
	array( 'contact-us',             'Contact Us' ),
);
$added = 0;
foreach ( $pages as $pg ) {
	$p = get_page_by_path( $pg[0] );
	if ( ! $p ) { WP_CLI::warning( 'Missing page: ' . $pg[0] ); continue; }
	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-title'     => $pg[1],
		'menu-item-object'    => 'page',
		'menu-item-object-id' => (int) $p->ID,
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
	) );
	$added++;
}

/* 2) "Navigation Menu" widget → the footer-widget-1 sidebar. Idempotent (reuses
 *    the instance marked `ef_footer_info`). */
$inst = get_option( 'widget_nav_menu', array() );
if ( ! is_array( $inst ) ) { $inst = array(); }

$widget_idx = null;
foreach ( $inst as $k => $v ) {
	if ( is_array( $v ) && ! empty( $v['ef_footer_info'] ) ) { $widget_idx = (int) $k; break; }
}
if ( null === $widget_idx ) {
	$nums       = array_filter( array_keys( $inst ), 'is_int' );
	$widget_idx = $nums ? ( max( $nums ) + 1 ) : 2;
}
$inst[ $widget_idx ] = array(
	'title'          => 'Information',
	'nav_menu'       => $menu_id,
	'ef_footer_info' => 1,
);
$inst['_multiwidget'] = 1;
update_option( 'widget_nav_menu', $inst );

$sw = get_option( 'sidebars_widgets', array() );
if ( ! is_array( $sw ) ) { $sw = array(); }
$sw['footer-widget-1'] = array( 'nav_menu-' . $widget_idx ); // replaces the area's content
update_option( 'sidebars_widgets', $sw );

/* 3) Footer Builder: column 3 = widget-1 (instead of html-3). */
$s = get_option( 'astra-settings', array() );
if ( ! is_array( $s ) ) { $s = array(); }
if ( ! isset( $s['footer-desktop-items']['primary'] ) ) {
	$s['footer-desktop-items']['primary'] = array();
}
$s['footer-desktop-items']['primary']['primary_3'] = array( 'widget-1' );
$s['footer-html-3'] = ''; // no longer used
update_option( 'astra-settings', $s );

WP_CLI::log( 'Menu "Footer Information" #' . $menu_id . ' (' . $added . ' items) → widget nav_menu-' . $widget_idx . ' in footer-widget-1; column 3 = widget-1.' );
WP_CLI::success( 'Information column = menu widget (editable from Customize).' );
