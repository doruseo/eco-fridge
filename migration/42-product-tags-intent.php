<?php
/**
 * 42 — Product tags: intent tags that do not compete with the categories.
 *
 * The original tags were synonyms of the categories ("multideck-fridge-tag",
 * "multideck-chiller-tag", "beer-cooler-tag"…), so every tag page competed with
 * a category for the same search. This migration:
 *  1. keeps a one-time backup of every product's old tags (option
 *     `ef_legacy_product_tags`, product ID → slugs);
 *  2. deletes the old tags – their URLs 301 to the matching category
 *     (ecofridge-blocks/includes/tag-redirects.php);
 *  3. creates tags for searches no category targets – the type of business
 *     and the product's features – and assigns them product by product.
 * "ice-cream-freezer-tag" is kept (same URL as live): no category targets it.
 * Idempotent: re-running re-applies the same assignments.
 *
 * @package EcoFridgeMigration
 */

$tags = array(
	// Type of business.
	'cafe-coffee-shop-refrigeration'   => array(
		'Café & Coffee Shop Refrigeration',
		'Display fridges, cake displays, heated pastry cabinets, under counter fridges and ovens for cafés, coffee shops and sandwich bars.',
		array( 10, 21, 22, 23, 24, 25, 26, 28, 49, 57, 58, 59, 60, 61, 62, 65, 70, 71, 72, 79, 85, 91, 93, 94, 95, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 113, 114, 115, 122, 123, 124, 125, 126, 135, 137, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151 ),
	),
	'convenience-store-refrigeration'  => array(
		'Convenience Store Refrigeration',
		'Multideck fridges, glass door drinks chillers, display freezers and hot food displays for convenience stores, forecourts and food-to-go retail.',
		array( 11, 18, 19, 20, 27, 30, 31, 32, 33, 51, 52, 53, 54, 55, 56, 76, 81, 83, 84, 86, 87, 88, 89, 90, 96, 97, 98, 99, 100, 101, 112, 122 ),
	),
	'butcher-shop-refrigeration'       => array(
		'Butcher Shop Refrigeration',
		'Meat fridges and gastronorm chillers holding −5 to +5 °C, meat freezers, refrigerated counters and serve over counters for butchers and fishmongers.',
		array( 14, 16, 64, 66, 67, 68, 69, 73, 74, 75, 121, 136 ),
	),
	'restaurant-kitchen-equipment'     => array(
		'Restaurant Kitchen Refrigeration & Ovens',
		'Upright storage fridges and freezers, refrigerated prep counters, gastronorm cabinets and commercial ovens for restaurant and catering kitchens.',
		array( 14, 15, 16, 17, 28, 29, 38, 39, 40, 41, 46, 47, 66, 67, 68, 69, 77, 78, 79, 80, 116, 118, 121, 136, 140, 141, 150, 151, 152 ),
	),
	'hotel-buffet-display-fridges'     => array(
		'Hotel & Buffet Display Fridges',
		'Countertop chillers, cake display towers and premium glass displays for hotel breakfasts, buffets, conference catering and restaurants.',
		array( 25, 70, 85, 102, 103, 106, 110, 111, 143 ),
	),
	'ice-cream-freezer-tag'            => array(
		'Ice Cream Freezers',
		'Glass lid chest freezers and glass door display freezers that keep ice cream, lollies and frozen desserts at −18 to −24 °C.',
		array( 19, 30, 31, 83, 84 ),
	),
	// Features.
	'stainless-steel-fridges-freezers' => array(
		'Stainless Steel Fridges & Freezers',
		'Commercial fridges, freezers and display cabinets in stainless steel and mirrored 304 stainless steel – hygienic, durable and easy to clean.',
		array( 14, 15, 16, 17, 21, 22, 23, 25, 26, 27, 28, 29, 49, 51, 66, 67, 68, 69, 70, 79, 80, 85, 86, 87, 88, 89, 90, 93, 94, 95, 100, 101, 102, 103, 104, 105, 106, 110, 111, 116, 118, 120, 121, 122, 125, 126, 131, 132, 133, 136, 137, 138, 143 ),
	),
	'black-commercial-fridges'         => array(
		'Black Commercial Fridges',
		'Display fridges in black steel – multidecks, grab and go, patisserie and back bar chillers that suit modern and darker shop fits.',
		array( 10, 11, 48, 63, 96, 97, 98, 99, 113, 114, 115, 144, 145, 146, 147, 148, 149 ),
	),
	'fridges-freezers-with-castors'    => array(
		'Fridges & Freezers with Castors',
		'Commercial fridges and freezers supplied with castors fitted, so they can be moved for cleaning and floor maintenance.',
		array( 14, 15, 16, 17, 18, 19, 21, 27, 31, 49, 32, 33, 39, 41, 66, 67, 68, 69, 83, 85, 86, 87, 88, 89, 90, 93, 94, 95, 100, 101, 104, 105, 106, 121, 125, 126, 128, 129, 130, 131, 132, 133, 134, 143 ),
	),
	'sliding-door-fridges-freezers'    => array(
		'Sliding Door Fridges & Freezers',
		'Glass door chillers with sliding doors and chest freezers with sliding glass lids – no door swing, ideal for narrow aisles and behind counters.',
		array( 32, 33, 83 ),
	),
	'double-glazed-display-cabinets'   => array(
		'Double Glazed Display Cabinets',
		'Display fridges and heated displays with double-glazed glass that reduces condensation and heat gain, keeping products clearly visible.',
		array( 22, 24, 27, 48, 63, 65, 70, 71, 72, 91, 100, 101, 102, 103, 106, 107, 108, 109, 110, 111, 113, 114, 115, 123, 124, 137 ),
	),
	'lockable-fridges-freezers'        => array(
		'Lockable Fridges & Freezers',
		'Commercial fridges and freezers with locks or lockable shutters, to secure stock out of hours and in shared spaces.',
		array( 14, 15, 27, 83, 100, 101, 128, 129, 130 ),
	),
	'high-ambient-fridges-freezers'    => array(
		'High Ambient Fridges & Freezers',
		'Refrigerated counters and bakery cabinets designed to operate in room temperatures up to 43 °C, for hot kitchens and bakeries.',
		array( 14, 15, 16, 17, 131, 132, 133 ),
	),
	'slimline-display-fridges'         => array(
		'Slimline Display Fridges',
		'Space-saving slimline multidecks, serve overs and patisserie fridges for small shops, cafés and narrow aisles.',
		array( 11, 52, 53, 54, 55, 56, 64, 73, 74, 75, 86, 87, 88, 89, 90, 92, 96, 97, 98, 99, 107, 108, 109, 113, 114, 115, 117, 120, 122, 138 ),
	),
	'double-door-fridges-freezers'     => array(
		'Double Door Fridges & Freezers',
		'High-capacity double door commercial fridges and freezers – glass door display chillers and freezers and solid door storage cabinets.',
		array( 30, 31, 32, 33, 38, 39, 50, 63, 66, 69, 81, 112, 136 ),
	),
);

// 1. One-time backup of the old assignments.
if ( ! get_option( 'ef_legacy_product_tags' ) ) {
	$backup = array();
	foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $pid ) {
		$slugs = wp_get_post_terms( $pid, 'product_tag', array( 'fields' => 'slugs' ) );
		if ( $slugs && ! is_wp_error( $slugs ) ) {
			$backup[ $pid ] = $slugs;
		}
	}
	update_option( 'ef_legacy_product_tags', $backup, false );
	WP_CLI::log( 'Backed up the old tags of ' . count( $backup ) . ' products.' );
}

// 2. Delete every tag that is not in the new set.
$deleted = 0;
foreach ( get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => false ) ) as $old ) {
	if ( ! isset( $tags[ $old->slug ] ) ) {
		wp_delete_term( $old->term_id, 'product_tag' );
		$deleted++;
	}
}

// 3. Create / update the intent tags and assign them (replacing any others).
$by_product = array();
foreach ( $tags as $slug => $def ) {
	list( $name, $desc, $ids ) = $def;
	$term = get_term_by( 'slug', $slug, 'product_tag' );
	if ( $term ) {
		wp_update_term( $term->term_id, 'product_tag', array( 'name' => $name, 'description' => $desc ) );
	} else {
		$res = wp_insert_term( $name, 'product_tag', array( 'slug' => $slug, 'description' => $desc ) );
		if ( is_wp_error( $res ) ) {
			WP_CLI::warning( "$slug: " . $res->get_error_message() );
			continue;
		}
	}
	foreach ( $ids as $pid ) {
		$by_product[ $pid ][] = $slug;
	}
}
$assigned = 0;
foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $pid ) {
	wp_set_object_terms( $pid, $by_product[ $pid ] ?? array(), 'product_tag', false );
	$assigned += ! empty( $by_product[ $pid ] );
}
wc_delete_product_transients();

WP_CLI::success( sprintf( '%d old tags deleted; %d intent tags; %d products tagged.', $deleted, count( $tags ), $assigned ) );
