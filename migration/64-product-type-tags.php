<?php
/**
 * 64 — Product tags on every product, for the filter bar's Type and Features.
 *
 * The categories don't describe every product the same way (the used units sit
 * only in "Used", some display fridges miss "Display Cabinets", …), so the
 * filter bar also filters by tags:
 *
 *  1. Type tags (new): every visible product gets the tag(s) of what it is —
 *     Multideck Fridges, Patisserie Display Fridges, Under Counter Freezers, …
 *     Classified by hand from each product's title and categories; a product
 *     can be two types (e.g. a low-temperature chest freezer). The bakery fridge
 *     and freezer are upright storage ones; the retarder prover, ovens, grills
 *     and oven stand get tags of their own type (they also have the bakery
 *     child categories of migration 63, which are the pages that rank).
 *     These tag pages repeat category pages, so they are noindex (Rank Math)
 *     to keep the categories as the pages that rank.
 *  2. Feature tags (existing): added where the product's title says so —
 *     Black, Stainless Steel, Slimline, Double Door, Double Glazed, Sliding
 *     Door, with Castors. Nothing is removed.
 *
 * Products are matched by ID and checked against the start of their title, so
 * the script stops instead of tagging the wrong product on a database where
 * IDs differ. Safe to run again (terms are appended).
 *
 * Run: wp eval-file migration/64-product-type-tags.php
 *
 * @package EcoFridgeMigration
 */

$types = array(
	'multideck-fridges' => array( 'Multideck Fridges', array( 11, 27, 51, 52, 53, 54, 55, 56, 86, 87, 88, 89, 90, 92, 96, 97, 98, 99, 100, 101, 120, 122, 138 ) ),
	'upright-display-fridges' => array( 'Upright Display Fridges', array( 18, 32, 33, 76, 81, 112 ) ),
	'upright-cake-display-fridges' => array( 'Upright Cake Display Fridges', array( 85, 143 ) ),
	'back-bar-fridges' => array( 'Back Bar Fridges', array( 48, 63 ) ),
	'patisserie-display-fridges' => array( 'Patisserie Display Fridges', array( 10, 21, 22, 26, 57, 61, 62, 65, 71, 72, 102, 103, 104, 105, 107, 108, 109, 113, 114, 115, 125, 126, 135, 137, 147, 148, 149 ) ),
	'grab-and-go-fridges' => array( 'Grab and Go Fridges', array( 23, 25, 49, 58, 59, 60, 93, 94, 95, 144, 145, 146 ) ),
	'serve-over-counters' => array( 'Serve Over Counters', array( 64, 73, 74, 75, 117 ) ),
	'counter-display-fridges' => array( 'Counter & Countertop Display Fridges', array( 65, 70, 71, 72, 106, 110, 111 ) ),
	'heated-displays' => array( 'Heated Displays', array( 20, 24, 91, 122, 123, 124 ) ),
	'upright-display-freezers' => array( 'Upright Display Freezers', array( 19, 30, 31, 84 ) ),
	'chest-freezers' => array( 'Chest Freezers', array( 83, 128, 129, 130 ) ),
	'upright-storage-fridges' => array( 'Upright Storage Fridges', array( 38, 40, 47, 50, 77, 132, 134 ) ),
	'gastronorm-fridges' => array( 'Gastronorm & Meat Fridges', array( 66, 67, 121, 136 ) ),
	'upright-storage-freezers' => array( 'Upright Storage Freezers', array( 39, 41, 46, 78, 133, 139 ) ),
	'gastronorm-freezers' => array( 'Gastronorm Freezers', array( 68, 69 ) ),
	'counter-fridges' => array( 'Counter Fridges', array( 14, 16, 116 ) ),
	'counter-freezers' => array( 'Counter Freezers', array( 15, 17 ) ),
	'under-counter-fridges' => array( 'Under Counter Fridges', array( 28, 79, 118 ) ),
	'under-counter-freezers' => array( 'Under Counter Freezers', array( 29, 80 ) ),
	'low-temperature-freezers' => array( 'Low Temperature Freezers', array( 128, 129, 130, 139 ) ),
	'medical-fridges' => array( 'Medical Fridges', array( 134 ) ),
	'retarder-provers' => array( 'Retarder Provers', array( 131 ) ),
	'ovens' => array( 'Ovens', array( 140, 141, 150, 151 ) ),
	'grills' => array( 'Grills', array( 140, 142, 150 ) ),
	'oven-stands' => array( 'Oven Stands', array( 152 ) ),
);

$titles = array(
	10 => 'Black Patisserie Display',
	11 => 'Multideck Fridge – Black',
	14 => 'Eco Premium Three Door C',
	15 => 'Eco Premium Three Door C',
	16 => 'Eco Premium Two Door Cou',
	17 => 'Eco Premium Two Door Cou',
	18 => 'High-Spec Single Door Up',
	19 => 'Upright Display Freezer ',
	20 => 'Stainless Steel Hot Mult',
	21 => 'Stainless Mirrored Steel',
	22 => 'Stainless Steel Double G',
	23 => 'Stainless Mirrored Steel',
	24 => 'Stainless Mirrored Steel',
	25 => 'Stainless Steel Open Fla',
	26 => 'Stainless Mirrored Steel',
	27 => 'Stainless Mirrored Steel',
	28 => 'Stainless Steel Under Co',
	29 => 'Stainless Steel Under Co',
	30 => 'Double Door Upright Disp',
	31 => 'Upright Display Freezer ',
	32 => 'White Double Sliding Doo',
	33 => 'Upright Display Chiller ',
	38 => 'White Heavy Duty Double ',
	39 => 'White Heavy Duty Double ',
	40 => 'White Heavy Duty Single ',
	41 => 'White Heavy Duty Single ',
	46 => 'White Heavy Duty Large D',
	47 => 'White Heavy Duty Large D',
	48 => 'Half-Height Glass Door C',
	49 => 'Stainless Steel Grab & G',
	50 => 'White Upright High Capac',
	51 => 'Stainless Steel R290 Mul',
	52 => 'Multideck Display Fridge',
	53 => 'Multideck Display Fridge',
	54 => 'Multideck Display Fridge',
	55 => 'Multideck Display Fridge',
	56 => 'Multideck Display Fridge',
	57 => 'Patisserie Display Fridg',
	58 => 'Grab and Go Display Frid',
	59 => 'Grab and Go Display Chil',
	60 => 'Grab and Go Display Chil',
	61 => 'Patisserie Display Fridg',
	62 => 'Patisserie Display Fridg',
	63 => 'Back Bar Double Glass Do',
	64 => 'Slim Serve Over – Standa',
	65 => 'Standard Silver Steel Do',
	66 => 'Upright Double Door Chil',
	67 => 'Large Upright Gastronorm',
	68 => 'Large Gastronorm Freezer',
	69 => 'Gastronorm Freezer – Sta',
	70 => 'Counter Top Chiller (150',
	71 => 'Standard Silver Steel Do',
	72 => 'Standard Silver Steel Do',
	73 => 'Slim Serve Over Standard',
	74 => 'Slim Serve Over 1500mm –',
	75 => 'Slim Silver Steel Serve ',
	76 => 'Standard White Steel Upr',
	77 => 'Standard White Upright S',
	78 => 'Standard White Upright S',
	79 => 'Under Counter Chiller – ',
	80 => 'Under Counter Freezer – ',
	81 => 'White Double Glass Door ',
	83 => 'Chest Display Freezer Sl',
	84 => 'Upright Display Freezer ',
	85 => 'Upright Mirrored Stainle',
	86 => 'Premium Mirrored Stainle',
	87 => 'Premium Mirrored Stainle',
	88 => 'Premium Mirrored Stainle',
	89 => 'Premium Mirrored Stainle',
	90 => 'Premium Mirrored Stainle',
	91 => 'Silver Steel Heated Doub',
	92 => 'Silver Steel Slimline Mu',
	93 => 'Stainless Mirrored Steel',
	94 => 'Stainless Mirrored Steel',
	95 => 'Stainless Mirrored Steel',
	96 => 'Multideck Fridge – Black',
	97 => 'Multideck Fridge – Black',
	98 => 'Multideck Fridge – Black',
	99 => 'Multideck Fridge – Black',
	100 => 'Stainless Mirrored Steel',
	101 => 'Stainless Mirrored Steel',
	102 => 'Stainless Mirrored Steel',
	103 => 'Stainless Steel Double G',
	104 => 'Stainless Mirrored Steel',
	105 => 'Stainless Mirrored Steel',
	106 => 'Premium Glass Counter Di',
	107 => 'Patisserie Slimline Doub',
	108 => 'Patisserie Slimline Doub',
	109 => 'Patisserie Slimline Doub',
	110 => 'Counter Top Chiller (120',
	111 => 'Counter Top Chiller (100',
	112 => 'White Double Glass Door ',
	113 => 'Patisserie Slimline Doub',
	114 => 'Patisserie Slimline Doub',
	115 => 'Patisserie Slimline Doub',
	116 => 'Stainless Steel Two Door',
	117 => 'Silver Steel Slimline Se',
	118 => 'Stainless Steel Dual Tem',
	120 => 'Premium Stainless Steel ',
	121 => 'Large Upright Gastronorm',
	122 => 'Dual Temperature Hot and',
	123 => 'Stainless Mirrored Steel',
	124 => 'Stainless Mirrored Steel',
	125 => 'Stainless Mirrored Steel',
	126 => 'Stainless Mirrored Steel',
	128 => 'Low Temperature Chest Fr',
	129 => 'Low Temperature Chest Fr',
	130 => 'Ultra Low Temperature Ch',
	131 => 'Stainless Steel Upright ',
	132 => 'Stainless Steel Upright ',
	133 => 'Stainless Steel Upright ',
	134 => 'Medical White Upright St',
	135 => 'Silver Steel Patisserie ',
	136 => 'Double Door Upright Gast',
	137 => 'Stainless Steel Double G',
	138 => 'Premium Stainless Steel ',
	139 => 'Low Temperature Upright ',
	140 => 'Commercial Stainless Ste',
	141 => 'Commercial Stainless Ste',
	142 => 'Commercial Stainless Ste',
	150 => 'Commercial Stainless Ste',
	151 => 'Commercial Stainless Ste',
	152 => 'Commercial Stainless Ste',
	143 => 'Upright Mirrored Stainle',
	144 => 'Grab and Go Display Chil',
	145 => 'Grab and Go Display Chil',
	146 => 'Grab and Go Display Frid',
	147 => 'Patisserie Display Fridg',
	148 => 'Patisserie Display Fridg',
	149 => 'Patisserie Display Fridg',
);
foreach ( $titles as $id => $start ) {
	$title = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	if ( 'product' !== get_post_type( $id ) || 0 !== strpos( $title, $start ) ) {
		WP_CLI::error( "Product $id is \"$title\", expected a title starting \"$start\". Nothing changed." );
	}
}

// 1. Type tags.
foreach ( $types as $slug => $def ) {
	$term = get_term_by( 'slug', $slug, 'product_tag' );
	if ( ! $term ) {
		$res = wp_insert_term( $def[0], 'product_tag', array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( "Tag $slug: " . $res->get_error_message() );
		}
		$term_id = (int) $res['term_id'];
	} else {
		$term_id = (int) $term->term_id;
	}
	update_term_meta( $term_id, 'rank_math_robots', array( 'noindex' ) );
	foreach ( $def[1] as $id ) {
		wp_set_object_terms( $id, $term_id, 'product_tag', true );
	}
	WP_CLI::log( sprintf( '%-30s %2d products', $slug, count( $def[1] ) ) );
}

// 2. Feature tags from the titles.
$features = array(
	'black-commercial-fridges'        => '/\bBlack\b/i',
	'stainless-steel-fridges-freezers' => '/Stainless/i',
	'slimline-display-fridges'        => '/\bSlim(line)?\b/i',
	'double-door-fridges-freezers'    => '/\b(Double|Two)\s+(Sliding\s+|Glass\s+|Hinged\s+Glass\s+)?Door/i',
	'double-glazed-display-cabinets'  => '/Double\s+Glazed/i',
	'sliding-door-fridges-freezers'   => '/Sliding/i',
	'fridges-freezers-with-castors'   => '/with\s+Castors/i',
);
$ids = wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids' ) );
foreach ( $features as $slug => $re ) {
	$term = get_term_by( 'slug', $slug, 'product_tag' );
	if ( ! $term ) {
		WP_CLI::warning( "Feature tag $slug not found, skipped." );
		continue;
	}
	$added = array();
	foreach ( $ids as $id ) {
		if ( preg_match( $re, html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) ) && ! has_term( $term->term_id, 'product_tag', $id ) ) {
			wp_set_object_terms( $id, (int) $term->term_id, 'product_tag', true );
			$added[] = $id;
		}
	}
	WP_CLI::log( sprintf( '%-34s +%d %s', $slug, count( $added ), $added ? '(' . implode( ', ', $added ) . ')' : '' ) );
}

delete_transient( 'ef_pf_index' );
WP_CLI::success( 'Type and feature tags set.' );
