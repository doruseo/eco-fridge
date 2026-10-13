<?php
/**
 * 45 — Markets get the category landing page (ecofridge-blocks/includes/markets.php).
 *
 *  - every /markets/<slug>/ page needs a `market` term with the same slug:
 *    "Sports Clubs" is created and given the drinks fridges, back bar chillers
 *    and lockable shutter multideck fridges its page describes;
 *  - the business tags created in migration 42 target the same searches as four
 *    original market pages, so they are merged into the markets (products added
 *    to the market, tag deleted) – one page per intent, on the original URL;
 *  - the /markets/ hub links point to the market pages (the originals pointed
 *    to /coffee-shops/ etc., which only worked through a redirect).
 *
 * Idempotent. Run before migration 44 (which writes the market content).
 *
 * @package EcoFridgeMigration
 */

// 1. Sports Clubs market.
$sports = get_term_by( 'slug', 'sports-clubs', 'market' );
if ( ! $sports ) {
	$made   = wp_insert_term( 'Sports Clubs', 'market', array( 'slug' => 'sports-clubs' ) );
	$sports = get_term( $made['term_id'], 'market' );
	WP_CLI::log( 'Created market: Sports Clubs' );
}
$ids = wc_get_products(
	array(
		'status'   => 'publish',
		'limit'    => -1,
		'return'   => 'ids',
		'category' => array( 'commercial-drinks-fridge', 'back-bar-display-chillers' ),
	)
);
foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'category' => array( 'multideck-fridge' ) ) ) as $p ) {
	if ( false !== stripos( $p->get_name(), 'shutter' ) ) {
		$ids[] = $p->get_id();
	}
}
foreach ( array_unique( $ids ) as $id ) {
	wp_set_object_terms( $id, $sports->term_id, 'market', true );
}
WP_CLI::log( 'Sports Clubs: ' . count( array_unique( $ids ) ) . ' products' );

// 2. Business tags → markets.
$merge = array(
	'cafe-coffee-shop-refrigeration'  => 'coffee-shops',
	'convenience-store-refrigeration' => 'convenience-stores',
	'butcher-shop-refrigeration'      => 'delis-and-butchers',
	'restaurant-kitchen-equipment'    => 'restaurants',
);
foreach ( $merge as $tag_slug => $market_slug ) {
	$tag    = get_term_by( 'slug', $tag_slug, 'product_tag' );
	$market = get_term_by( 'slug', $market_slug, 'market' );
	if ( ! $tag || ! $market ) {
		continue;
	}
	$objects = get_objects_in_term( $tag->term_id, 'product_tag' );
	foreach ( $objects as $id ) {
		wp_set_object_terms( (int) $id, $market->term_id, 'market', true );
	}
	wp_delete_term( $tag->term_id, 'product_tag' );
	WP_CLI::log( "Merged tag $tag_slug into market $market_slug (" . count( $objects ) . ' products)' );
}

// 3. /markets/ hub links.
$hub = get_page_by_path( 'markets' );
if ( $hub ) {
	$content = preg_replace_callback(
		'~href="/([a-z0-9-]+)/"~',
		static fn( $m ) => get_page_by_path( 'markets/' . $m[1] ) ? 'href="/markets/' . $m[1] . '/"' : $m[0],
		$hub->post_content
	);
	if ( $content !== $hub->post_content ) {
		wp_update_post( array( 'ID' => $hub->ID, 'post_content' => $content ) );
		WP_CLI::log( 'Fixed the /markets/ hub links.' );
	}
}

delete_option( 'rewrite_rules' );
wc_delete_product_transients();
WP_CLI::success( 'Market landings ready.' );
