<?php
/**
 * 37 — "Shop" in the top white bar menu (Landing Pages Menu, right of the
 * logo), right after "Home". Links to the WooCommerce Shop page.
 *
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$menu = wp_get_nav_menu_object( 'Landing Pages Menu' );
$shop = (int) wc_get_page_id( 'shop' );
if ( ! $menu || $shop <= 0 ) {
	WP_CLI::error( 'Landing Pages Menu or the Shop page not found.' );
}

$items  = wp_get_nav_menu_items( $menu->term_id );
$exists = null;
foreach ( $items as $it ) {
	if ( 'post_type' === $it->type && 'page' === $it->object && $shop === (int) $it->object_id ) {
		$exists = $it;
	}
}
if ( ! $exists ) {
	$id = wp_update_nav_menu_item(
		$menu->term_id,
		0,
		array(
			'menu-item-title'     => 'Shop',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $shop,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		)
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	$items = wp_get_nav_menu_items( $menu->term_id, array( 'post_status' => 'any' ) );
}

// Order: Home, Shop, then everything else as before.
$ordered = array();
$shop_it = null;
foreach ( $items as $it ) {
	if ( 'post_type' === $it->type && 'page' === $it->object && $shop === (int) $it->object_id ) {
		$shop_it = $it;
		continue;
	}
	$ordered[] = $it;
}
array_splice( $ordered, 1, 0, array( $shop_it ) );
foreach ( $ordered as $i => $it ) {
	if ( (int) $it->menu_order !== $i + 1 ) {
		wp_update_post( array( 'ID' => $it->ID, 'menu_order' => $i + 1 ) );
	}
}

WP_CLI::success( 'Shop is in the Landing Pages Menu, after Home.' );
