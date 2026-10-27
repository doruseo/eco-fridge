<?php
/**
 * 63 — Filter bar: bakery / oven product tags + the shop sidebar's widgets.
 *
 *  1. Product tags for the bakery and oven products, so the filter bar can
 *     offer them (ovens have no category of their own; the user chose tags
 *     over a new category):
 *       Ovens, Grills, Oven Stands          → the "Ovens & Grills" group
 *       Bakery Fridges, Bakery Freezers,
 *       Retarder Provers                    → under "Bakery Cabinets"
 *     Products are matched by ID and checked by title, so the script stops
 *     instead of tagging the wrong product on a database where IDs differ.
 *  2. The "astra-woo-shop-sidebar" widgets become the filter bar, in order:
 *     Categories, Type, Price (WooCommerce's own widget, kept as it is),
 *     Condition, Markets, Features, Width, Height (Type and Features read the
 *     tags of migration 64). The old contextual subcategory widget and the two
 *     WooCommerce layered-nav widgets (Width, Height) are dropped: the new
 *     widget (astra-child/inc/product-filters.php) renders those facets.
 *
 * Safe to run again: existing tags are reused, terms are appended, and the
 * sidebar is rebuilt from scratch each time.
 *
 * Run: wp eval-file migration/63-filter-bar.php
 *
 * @package EcoFridgeMigration
 */

$tags = array(
	'ovens'            => array( 'Ovens', array( 140 => 'Oven and Grill', 141 => 'Large Oven', 150 => 'Large Oven with Grill', 151 => 'Stainless Steel Oven' ) ),
	'grills'           => array( 'Grills', array( 140 => 'Grill', 142 => 'Contact Grill', 150 => 'Grill' ) ),
	'oven-stands'      => array( 'Oven Stands', array( 152 => 'Oven Stand' ) ),
	'bakery-fridges'   => array( 'Bakery Fridges', array( 132 => 'Bakery Fridge' ) ),
	'bakery-freezers'  => array( 'Bakery Freezers', array( 133 => 'Bakery Freezer' ) ),
	'retarder-provers' => array( 'Retarder Provers', array( 131 => 'Retarder Prover' ) ),
);

// Check every product first: tag nothing unless all of them are the expected ones.
foreach ( $tags as $slug => $def ) {
	foreach ( $def[1] as $id => $needle ) {
		$title = get_the_title( $id );
		if ( 'product' !== get_post_type( $id ) || false === stripos( $title, $needle ) ) {
			WP_CLI::error( "Product $id is \"$title\", expected a title containing \"$needle\" (tag $slug). Nothing changed." );
		}
	}
}

foreach ( $tags as $slug => $def ) {
	$term = get_term_by( 'slug', $slug, 'product_tag' );
	if ( ! $term ) {
		$res = wp_insert_term( $def[0], 'product_tag', array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( "Tag $slug: " . $res->get_error_message() );
		}
		$term_id = (int) $res['term_id'];
		WP_CLI::log( "Created tag $slug ($term_id)." );
	} else {
		$term_id = (int) $term->term_id;
		WP_CLI::log( "Tag $slug exists ($term_id)." );
	}
	foreach ( array_keys( $def[1] ) as $id ) {
		wp_set_object_terms( $id, $term_id, 'product_tag', true );
	}
	WP_CLI::log( '  tagged: ' . implode( ', ', array_keys( $def[1] ) ) );
}

// Sidebar: Categories, Type, Price, Condition, Markets, Features, Width, Height.
$facets = array(
	2 => array( 'facet' => 'categories', 'title' => 'Categories' ),
	3 => array( 'facet' => 'condition', 'title' => 'Condition' ),
	4 => array( 'facet' => 'markets', 'title' => 'Markets' ),
	5 => array( 'facet' => 'width', 'title' => 'Width' ),
	6 => array( 'facet' => 'height', 'title' => 'Height' ),
	7 => array( 'facet' => 'types', 'title' => 'Type' ),
	8 => array( 'facet' => 'features', 'title' => 'Features' ),
);
update_option( 'widget_ef_product_filter', $facets + array( '_multiwidget' => 1 ) );

$price = get_option( 'widget_woocommerce_price_filter', array() );
if ( empty( $price[2] ) ) {
	$price[2]             = array( 'title' => 'Price' );
	$price['_multiwidget'] = 1;
	update_option( 'widget_woocommerce_price_filter', $price );
}

$sidebars = get_option( 'sidebars_widgets', array() );
$sidebars['astra-woo-shop-sidebar'] = array(
	'ef_product_filter-2',
	'ef_product_filter-7',
	'woocommerce_price_filter-2',
	'ef_product_filter-3',
	'ef_product_filter-4',
	'ef_product_filter-8',
	'ef_product_filter-5',
	'ef_product_filter-6',
);
update_option( 'sidebars_widgets', $sidebars );

delete_transient( 'ef_pf_index' );
WP_CLI::success( 'Filter bar tags and sidebar set.' );
