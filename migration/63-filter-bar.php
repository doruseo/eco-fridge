<?php
/**
 * 63 — Filter bar: bakery / oven child categories, the Commercial menu, and the
 * shop sidebar's widgets.
 *
 *  1. Child categories of "Bakery Ovens & Refrigeration" (/range/bakery-cabinets/),
 *     so every bakery / oven product type has its own page, menu entry and
 *     filter option:
 *       Ovens, Grills, Oven Stands           → the "Ovens & Grills" filter group
 *       Bakery Fridges, Bakery Freezers,
 *       Retarder Provers                     → the "Bakery Cabinets" filter group
 *     URLs follow the parent: /range/bakery-cabinets/<slug>/ (`_efb_base` =
 *     range). Each one's image (menu tile, carousel card) is the first
 *     product's image. Products keep their other categories.
 *     Products are matched by ID and checked by title, so the script stops
 *     instead of filing the wrong product on a database where IDs differ.
 *  2. The six categories go under "Bakery & Ovens" in the Commercial menu.
 *  3. The product tags "Bakery Fridges" and "Bakery Freezers" of an earlier
 *     version of this migration are deleted: the categories replace them (the
 *     products are Upright Storage Fridges / Freezers in the Type filter,
 *     migration 64). Ovens, Grills, Oven Stands and Retarder Provers stay as
 *     Type tags (migration 64).
 *  4. The "astra-woo-shop-sidebar" widgets become the filter bar, in order:
 *     Price (WooCommerce's own widget, kept as it is; first, as the owner
 *     asked), Categories, Type, Condition, Markets, Features, Width, Height (Type and Features read the
 *     tags of migration 64). The old contextual subcategory widget and the two
 *     WooCommerce layered-nav widgets (Width, Height) are dropped: the new
 *     widget (astra-child/inc/product-filters.php) renders those facets.
 *
 * Safe to run again: existing categories and menu entries are reused, terms
 * are appended, and the sidebar is rebuilt from scratch each time.
 *
 * Run: wp eval-file migration/63-filter-bar.php
 *
 * @package EcoFridgeMigration
 */

$parent_slug = 'bakery-cabinets';
$categories  = array(
	'ovens'            => array( 'Ovens', array( 141 => 'Large Oven', 151 => 'Stainless Steel Oven', 140 => 'Oven and Grill', 150 => 'Large Oven with Grill' ) ),
	'grills'           => array( 'Grills', array( 142 => 'Contact Grill', 140 => 'Grill', 150 => 'Grill' ) ),
	'oven-stands'      => array( 'Oven Stands', array( 152 => 'Oven Stand' ) ),
	'bakery-fridges'   => array( 'Bakery Fridges', array( 132 => 'Bakery Fridge' ) ),
	'bakery-freezers'  => array( 'Bakery Freezers', array( 133 => 'Bakery Freezer' ) ),
	'retarder-provers' => array( 'Retarder Provers', array( 131 => 'Retarder Prover' ) ),
);

$parent = get_term_by( 'slug', $parent_slug, 'product_cat' );
if ( ! $parent ) {
	WP_CLI::error( "Category $parent_slug not found. Nothing changed." );
}

// Check every product first: change nothing unless all of them are the expected ones.
foreach ( $categories as $slug => $def ) {
	foreach ( $def[1] as $id => $needle ) {
		$title = get_the_title( $id );
		if ( 'product' !== get_post_type( $id ) || false === stripos( $title, $needle ) ) {
			WP_CLI::error( "Product $id is \"$title\", expected a title containing \"$needle\" ($slug). Nothing changed." );
		}
	}
}

// 1. The child categories.
$cat_ids = array();
foreach ( $categories as $slug => $def ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && (int) $term->parent !== (int) $parent->term_id ) {
		WP_CLI::error( "Category $slug exists under another parent. Nothing more changed." );
	}
	if ( ! $term ) {
		$res = wp_insert_term( $def[0], 'product_cat', array( 'slug' => $slug, 'parent' => (int) $parent->term_id ) );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( "Category $slug: " . $res->get_error_message() );
		}
		$term_id = (int) $res['term_id'];
		WP_CLI::log( "Created category $slug ($term_id)." );
	} else {
		$term_id = (int) $term->term_id;
		WP_CLI::log( "Category $slug exists ($term_id)." );
	}
	update_term_meta( $term_id, '_efb_base', 'range' );
	if ( ! get_term_meta( $term_id, 'thumbnail_id', true ) ) {
		$image = get_post_thumbnail_id( array_key_first( $def[1] ) );
		if ( $image ) {
			update_term_meta( $term_id, 'thumbnail_id', $image );
		}
	}
	foreach ( array_keys( $def[1] ) as $id ) {
		wp_set_object_terms( $id, $term_id, 'product_cat', true );
	}
	$cat_ids[ $slug ] = $term_id;
	WP_CLI::log( '  products: ' . implode( ', ', array_keys( $def[1] ) ) );
}

// 2. Commercial menu: under the parent category's entry ("Bakery & Ovens").
$locations = get_nav_menu_locations();
$menu_id   = (int) ( $locations['secondary_menu'] ?? 0 );
$items     = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
$under     = 0;
foreach ( (array) $items as $it ) {
	if ( 'taxonomy' === $it->type && 'product_cat' === $it->object && (int) $it->object_id === (int) $parent->term_id && ! $it->menu_item_parent ) {
		$under = (int) $it->ID;
	}
}
if ( ! $under ) {
	WP_CLI::warning( 'No top-level Commercial menu entry for the bakery category: menu left as it is.' );
} else {
	$have = array();
	foreach ( $items as $it ) {
		if ( (int) $it->menu_item_parent === $under ) {
			$have[] = (int) $it->object_id;
		}
	}
	$position = 0;
	foreach ( $cat_ids as $slug => $term_id ) {
		++$position;
		if ( in_array( $term_id, $have, true ) ) {
			continue;
		}
		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'     => $categories[ $slug ][0],
			'menu-item-object'    => 'product_cat',
			'menu-item-object-id' => $term_id,
			'menu-item-type'      => 'taxonomy',
			'menu-item-parent-id' => $under,
			'menu-item-position'  => $position,
			'menu-item-status'    => 'publish',
		) );
		WP_CLI::log( "Menu: added {$categories[ $slug ][0]} under Bakery & Ovens." );
	}
}

// 3. The tags these categories replace.
foreach ( array( 'bakery-fridges', 'bakery-freezers' ) as $slug ) {
	$tag = get_term_by( 'slug', $slug, 'product_tag' );
	if ( $tag ) {
		wp_delete_term( $tag->term_id, 'product_tag' );
		WP_CLI::log( "Deleted product tag $slug (replaced by the category)." );
	}
}

// 4. Sidebar: Price, Categories, Type, Condition, Markets, Features, Width, Height.
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
	$price[2]              = array( 'title' => 'Price' );
	$price['_multiwidget'] = 1;
	update_option( 'widget_woocommerce_price_filter', $price );
}

$sidebars = get_option( 'sidebars_widgets', array() );
$sidebars['astra-woo-shop-sidebar'] = array(
	'woocommerce_price_filter-2',
	'ef_product_filter-2',
	'ef_product_filter-7',
	'ef_product_filter-3',
	'ef_product_filter-4',
	'ef_product_filter-8',
	'ef_product_filter-5',
	'ef_product_filter-6',
);
update_option( 'sidebars_widgets', $sidebars );

delete_transient( 'ef_pf_index' );
WP_CLI::success( 'Bakery / oven categories, menu and filter sidebar set.' );
