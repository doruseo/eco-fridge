<?php
/**
 * 81 — the off-canvas menu, and the duplicate /eco-fridge/ page.
 *
 * Two problems found while comparing the crawl of the old site with the new
 * one (every page carried an internal link that redirected, and 49 links that
 * belong to no menu):
 *
 * 1. The Off-Canvas (mobile) menu location had no menu assigned, so Astra fell
 *    back to wp_page_menu() and printed an alphabetical list of every published
 *    page — Cart, Checkout, the legal pages, everything — inside the mobile
 *    drawer, on every page of the site. The Primary menu is assigned there, the
 *    same one the desktop header shows.
 *
 * 2. Page 7752 "ECO-Fridge" (/eco-fridge/) holds the same returns text as page
 *    7325 "Return Policy" (/returns/) — the old site served both. A Rank Math
 *    redirection already pointed /eco-fridge/ at /about-us/, which is the wrong
 *    page for someone looking for returns. The target becomes /returns/ and the
 *    duplicate page goes to the trash, so it leaves the sitemap and stops
 *    competing with the page that is kept.
 *
 * Safe to run again. Backups: ef_nav_locations_before_81.
 * Run: wp eval-file migration/81-mobile-menu-and-eco-fridge-page.php
 *
 * @package EcoFridgeMigration
 */

defined( 'ABSPATH' ) || exit;

// 1. The off-canvas menu.
$locations = (array) get_theme_mod( 'nav_menu_locations', array() );

if ( ! get_option( 'ef_nav_locations_before_81' ) ) {
	add_option( 'ef_nav_locations_before_81', $locations, '', false );
}

$primary = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;

if ( $primary && empty( $locations['mobile_menu'] ) ) {
	$locations['mobile_menu'] = $primary;
	set_theme_mod( 'nav_menu_locations', $locations );
	echo "Off-canvas menu = the Primary menu (#{$primary}).\n";
} else {
	echo "Off-canvas menu already set (#" . (int) ( $locations['mobile_menu'] ?? 0 ) . ").\n";
}

// 2. /eco-fridge/ — point the redirection at the returns page and retire the duplicate.
global $wpdb;

$returns = get_page_by_path( 'returns' );
$table   = $wpdb->prefix . 'rank_math_redirections';

if ( $returns && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
	$rows = $wpdb->get_results( "SELECT id, sources, url_to FROM {$table} WHERE sources LIKE '%eco-fridge/%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	foreach ( $rows as $row ) {
		foreach ( (array) maybe_unserialize( $row->sources ) as $source ) {
			if ( 'eco-fridge/' === ( $source['pattern'] ?? '' ) ) {
				$wpdb->update( $table, array( 'url_to' => get_permalink( $returns ) ), array( 'id' => $row->id ) );
				echo "Redirection #{$row->id}: /eco-fridge/ -> " . get_permalink( $returns ) . "\n";
			}
		}
	}
}

$duplicate = get_page_by_path( 'eco-fridge' );

if ( $duplicate && 'trash' !== $duplicate->post_status ) {
	wp_trash_post( $duplicate->ID );
	echo "Page #{$duplicate->ID} '{$duplicate->post_title}' moved to the trash (duplicate of /returns/).\n";
} else {
	echo "No published page at /eco-fridge/.\n";
}

if ( function_exists( 'rank_math' ) ) {
	delete_transient( 'rank_math_redirections' );
}
