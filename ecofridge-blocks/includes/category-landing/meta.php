<?php
/**
 * Category landing pages — scope and stored data.
 *
 * Applies to the commercial categories: the main categories of the Commercial
 * menu (Display Cabinets, Storage Cabinets, Bakery, Medical, Low Temperature,
 * Used, Commercial Fridge, Commercial Freezer), the legacy /ranges/ categories,
 * and every category under them. On this site that hierarchy lives in the
 * Commercial menu (the categories themselves are mostly flat), so the tree is
 * read from the menus, plus any real child categories.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Root categories of the commercial tree.
 *
 * @return string[] Slugs.
 */
function efb_landing_root_slugs() {
	return (array) apply_filters(
		'efb_landing_root_slugs',
		array(
			// The main categories of the Commercial menu (green bar)…
			'display-cabinets',
			'storage-cabinets',
			'bakery-cabinets',
			'medical',
			'low-temperature-chest-freezers',
			'graded-units',
			'commercial-fridge',
			'commercial-freezer',
			// …and the legacy /ranges/ categories outside the menu.
			'deli-displays',
			'upright-display-cabinets',
			'heated-display',
		)
	);
}

/**
 * The commercial category tree: term ID => parent term ID (0 for a root).
 * Built once per request from the menus and the category hierarchy.
 *
 * @return array<int,int>
 */
function efb_landing_tree() {
	return efb_landing_tree_data()['parent'];
}

/**
 * Label of a category in the commercial menu (shorter than the term name, which
 * often carries an SEO suffix), falling back to the term name.
 *
 * @param int $term_id Category ID.
 * @return string
 */
function efb_landing_label( $term_id ) {
	$labels = efb_landing_tree_data()['label'];
	if ( isset( $labels[ (int) $term_id ] ) ) {
		return $labels[ (int) $term_id ];
	}
	$term = get_term( (int) $term_id );
	return $term && ! is_wp_error( $term ) ? $term->name : '';
}

/**
 * Builds the tree once per request.
 *
 * @return array{parent:array<int,int>,label:array<int,string>}
 */
function efb_landing_tree_data() {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}
	$tree   = array();
	$labels = array();
	$roots  = array();
	foreach ( efb_landing_root_slugs() as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$roots[ (int) $term->term_id ] = true;
			$tree[ (int) $term->term_id ]  = 0;
		}
	}
	if ( ! $roots ) {
		$data = array( 'parent' => $tree, 'label' => $labels );
		return $data;
	}

	// Menu hierarchy: every product category item below a root item.
	foreach ( array_unique( array_filter( array_map( 'intval', get_nav_menu_locations() ) ) ) as $menu_id ) {
		$items = wp_get_nav_menu_items( $menu_id );
		if ( ! $items ) {
			continue;
		}
		$children = array();
		foreach ( $items as $item ) {
			$children[ (int) $item->menu_item_parent ][] = $item;
		}
		$walk = static function ( $item_id, $parent_term ) use ( &$walk, &$tree, &$labels, $children ) {
			foreach ( $children[ $item_id ] ?? array() as $child ) {
				$term_id = 'taxonomy' === $child->type && 'product_cat' === $child->object ? (int) $child->object_id : 0;
				if ( $term_id && ! isset( $tree[ $term_id ] ) ) {
					$tree[ $term_id ]   = $parent_term;
					$labels[ $term_id ] = wp_strip_all_tags( $child->title );
				}
				$walk( (int) $child->ID, $term_id ? $term_id : $parent_term );
			}
		};
		foreach ( $items as $item ) {
			if ( 'taxonomy' === $item->type && 'product_cat' === $item->object && isset( $roots[ (int) $item->object_id ] ) ) {
				$walk( (int) $item->ID, (int) $item->object_id );
			}
		}
	}

	// Real child categories that are not in the menu.
	foreach ( array_keys( $tree ) as $term_id ) {
		foreach ( get_term_children( $term_id, 'product_cat' ) as $child ) {
			if ( ! isset( $tree[ (int) $child ] ) ) {
				$child_term           = get_term( (int) $child, 'product_cat' );
				$tree[ (int) $child ] = $child_term && ! is_wp_error( $child_term ) ? (int) $child_term->parent : $term_id;
			}
		}
	}

	$data = array( 'parent' => $tree, 'label' => $labels );
	return $data;
}

/**
 * Taxonomies whose every term gets the landing page too (product tags: the
 * intent pages, e.g. "Stainless Steel Fridges & Freezers") and markets
 * (/markets/<slug>/, e.g. "Café/Coffee Shop").
 *
 * @return string[]
 */
function efb_landing_tag_taxonomies() {
	return (array) apply_filters( 'efb_landing_tag_taxonomies', array( 'product_tag', 'market' ) );
}

/**
 * Whether a term is a landing tag (not a category).
 *
 * @param int $term_id Term ID.
 * @return bool
 */
function efb_landing_is_tag( $term_id ) {
	$term = get_term( (int) $term_id );
	return $term && ! is_wp_error( $term ) && in_array( $term->taxonomy, efb_landing_tag_taxonomies(), true );
}

/**
 * Whether the current request is a product category or landing tag archive.
 *
 * @return bool
 */
function efb_landing_is_archive() {
	return is_product_category() || ( is_tax( efb_landing_tag_taxonomies() ) );
}

/**
 * Whether a category (or tag) gets the landing page sections.
 *
 * @param int $term_id Category or tag ID.
 * @return bool
 */
function efb_landing_applies( $term_id ) {
	$applies = isset( efb_landing_tree()[ (int) $term_id ] ) || efb_landing_is_tag( $term_id );
	return (bool) apply_filters( 'efb_landing_applies', $applies, (int) $term_id );
}

/**
 * Categories of a tag's products, the most relevant first, so a tag page links
 * to the categories instead of competing with them. Root categories are left
 * out (too broad); a category ranks higher the more of the tag's products it
 * holds and the larger the share of its own products that carry the tag – so
 * "Serve Over Counters" (4 of 4) beats "Glass Front Fridges" (4 of 43).
 *
 * @param int $term_id Tag ID.
 * @param int $limit   Maximum number of categories.
 * @return int[]
 */
function efb_landing_tag_categories( $term_id, $limit = 8 ) {
	$tree   = efb_landing_tree();
	$counts = array();
	foreach ( EFB_Landing_Reviews::product_ids( (int) $term_id ) as $pid ) {
		foreach ( wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'ids' ) ) as $id ) {
			if ( ! empty( $tree[ (int) $id ] ) ) { // In the commercial tree and not a root.
				$counts[ (int) $id ] = ( $counts[ (int) $id ] ?? 0 ) + 1;
			}
		}
	}
	$scores = array();
	foreach ( $counts as $id => $n ) {
		$total         = (int) get_term_meta( $id, 'product_count_product_cat', true );
		$total         = max( $n, $total ? $total : (int) get_term( $id, 'product_cat' )->count );
		$scores[ $id ] = $n * $n / $total;
		if ( efb_landing_children( $id ) ) {
			$scores[ $id ] /= 4; // Parent categories after the specific ones.
		}
	}
	arsort( $scores );
	return array_slice( array_keys( $scores ), 0, $limit );
}

/**
 * Child categories of a category in the commercial tree, in menu order.
 *
 * @param int $term_id Category ID.
 * @return int[]
 */
function efb_landing_children( $term_id ) {
	return array_keys( array_filter( efb_landing_tree(), static fn( $parent ) => (int) $parent === (int) $term_id ) );
}

/**
 * Stored data: one term meta array per category.
 */
final class EFB_Landing_Meta {

	const KEY = '_efb_landing';

	/** Placeholder for the category name in section headings. */
	const CATEGORY_TOKEN = '{category}';

	/**
	 * Term meta holding the long text shown below the products (the theme's
	 * "Category content (below products)" field).
	 *
	 * @return string
	 */
	public static function long_desc_key() {
		return (string) apply_filters( 'efb_landing_long_desc_key', '_ef_range_content' );
	}

	/** Content sections shown after the product grid, in their default order. */
	public static function sections() {
		return array(
			'features'   => __( 'Features', 'ecofridge-blocks' ),
			'faq'        => __( 'FAQ accordion', 'ecofridge-blocks' ),
			'comparison' => __( 'Comparison table', 'ecofridge-blocks' ),
			'articles'   => __( 'Related articles', 'ecofridge-blocks' ),
			'long_desc'  => __( 'Long description', 'ecofridge-blocks' ),
			'parts'      => __( 'Spare parts', 'ecofridge-blocks' ),
		);
	}

	public static function default_titles() {
		return array(
			'features'          => __( 'Key Features of Our {category}', 'ecofridge-blocks' ),
			'faq'               => __( 'Frequently asked questions', 'ecofridge-blocks' ),
			'comparison'        => __( 'Compare at a glance', 'ecofridge-blocks' ),
			'articles'          => __( 'Buying guides & articles', 'ecofridge-blocks' ),
			'parts'             => __( 'Spare parts for this range', 'ecofridge-blocks' ),
			'long_desc'         => '', // The long description carries its own headings.
			'products_carousel' => __( 'All products in this range', 'ecofridge-blocks' ),
			'related_cats'      => __( 'Related categories', 'ecofridge-blocks' ),
			'markets'           => __( 'Refrigeration for your industry', 'ecofridge-blocks' ),
			'reviews'           => __( 'What our customers say', 'ecofridge-blocks' ),
		);
	}

	public static function defaults() {
		return array(
			'features'          => array(),
			'faq'               => array(),
			'table'             => array(
				'head' => array(),
				'rows' => array(),
			),
			'articles'          => array(),
			'parts'             => true,
			'parts_auto'        => true,
			'parts_manual'      => array(),
			'order'             => array_keys( self::sections() ),
			'titles'            => array(),
			'products_carousel' => true,
			'related_cats'      => true,
			'markets'           => true,
			'reviews'           => true,
		);
	}

	/**
	 * Data of one category, completed with defaults.
	 *
	 * @param int $term_id Category ID.
	 * @return array
	 */
	public static function get( $term_id ) {
		$data = get_term_meta( $term_id, self::KEY, true );
		$data = is_array( $data ) ? array_merge( self::defaults(), $data ) : self::defaults();

		// Keep the order list complete: a missing section goes back to its default position.
		$known = array_keys( self::sections() );
		$order = array_values( array_intersect( (array) $data['order'], $known ) );
		foreach ( array_diff( $known, $order ) as $key ) {
			array_splice( $order, min( array_search( $key, $known, true ), count( $order ) ), 0, array( $key ) );
		}
		$data['order']     = $order;
		$data['titles']    = array_merge( self::default_titles(), (array) $data['titles'] );
		$data['long_desc'] = (string) get_term_meta( $term_id, self::long_desc_key(), true );
		if ( efb_landing_is_tag( $term_id ) ) {
			// Tags have no subcategories and no spare parts range of their own.
			$data['products_carousel'] = false;
			$data['parts']             = false;
		}

		return $data;
	}

	public static function save( $term_id, array $data ) {
		unset( $data['long_desc'] );
		update_term_meta( $term_id, self::KEY, $data );
		self::bump_cache();
	}

	public static function has_section( array $data, $section ) {
		switch ( $section ) {
			case 'long_desc':
				return '' !== trim( $data['long_desc'] );
			case 'comparison':
				return ! empty( $data['table']['rows'] );
			case 'features':
			case 'faq':
			case 'articles':
			case 'parts':
			case 'products_carousel':
			case 'related_cats':
			case 'markets':
			case 'reviews':
				return ! empty( $data[ $section ] );
		}
		return false;
	}

	/** Review stats and product lists are cached under this version; bumping it invalidates everything. */
	public static function cache_version() {
		return (int) get_option( 'efb_landing_cache_ver', 1 );
	}

	public static function bump_cache() {
		update_option( 'efb_landing_cache_ver', self::cache_version() + 1, true );
	}
}
