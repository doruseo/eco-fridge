<?php
/**
 * Product filter bar — the faceted filters in the shop sidebar.
 *
 * Facets (each one a "Filter: Product facet" widget in "astra-woo-shop-sidebar",
 * set up by migration/63-filter-bar.php; Price, first in the bar, stays
 * WooCommerce's own widget):
 *
 *   Categories  the product groups (Display Cabinets, Storage Cabinets, Fridges,
 *               Freezers, Heated Cabinets, Bakery Cabinets, Ovens & Grills,
 *               Medical Cabinets), each collapsed under a chevron with its
 *               ranges (Commercial menu) or child categories below it
 *   Type        product tags of what each product is (Multideck Fridges, Under
 *               Counter Freezers, …) — on every product, used ones included,
 *               so it works where the categories don't (migration 64)
 *   Condition   New / Used (Used = the graded-units category)
 *   Markets     the `market` taxonomy
 *   Features    the feature product tags (Black, Stainless Steel, Slimline, …)
 *   Width,      WooCommerce attributes, filtered by WooCommerce itself
 *   Height      (filter_width / filter_height)
 *
 * URLs: the page the visitor is on is the scope; every option they tick narrows
 * it and shows in the query string, so GA4 sees what was filtered —
 * `filter_category`, `filter_type`, `filter_condition`, `filter_market`,
 * `filter_feature` (WooCommerce ignores filter_* keys that are not
 * attributes), plus WooCommerce's own filter_width / filter_height /
 * min_price / max_price. Filtered URLs are noindex and disallowed in
 * robots.txt. A page that is itself an option (e.g. /range/display-cabinets/)
 * shows that option ticked; unticking it leaves the page for /shop/ with the
 * other filters kept. The ticked filters are also listed above the panel as
 * removable chips.
 *
 * Counts come from a cached index of the visible products (terms + prices), so
 * every option shows how many products it would leave given the other facets.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** URL parameter of each facet. */
const EF_PF_PARAMS = array(
	'categories' => 'filter_category',
	'types'      => 'filter_type',
	'condition'  => 'filter_condition',
	'markets'    => 'filter_market',
	'features'   => 'filter_feature',
	'width'      => 'filter_width',
	'height'     => 'filter_height',
);

/** Attribute facets: facet => attribute taxonomy (filtered by WooCommerce). */
const EF_PF_ATTRIBUTES = array( 'width' => 'pa_width', 'height' => 'pa_height' );

/** The Used category (Condition: Used). */
const EF_PF_USED_SLUG = 'graded-units';

/* =========================================================================
 * 1. OPTIONS — the category groups and the flat facets.
 * ========================================================================= */

/**
 * The category groups, in display order. A key is a product_cat slug unless
 * `term` is false (a group with no page of its own); `children` is either
 * "menu" (the category's items in the Commercial menu) or a list of
 * product_cat slugs / `tag:<product_tag slug>`.
 *
 * @return array[]
 */
function ef_pf_groups() {
	return array(
		array( 'key' => 'display-cabinets', 'label' => 'Display Cabinets', 'children' => 'menu' ),
		array( 'key' => 'storage-cabinets', 'label' => 'Storage Cabinets', 'children' => 'menu' ),
		array( 'key' => 'commercial-fridge', 'label' => 'Fridges', 'children' => 'menu' ),
		array( 'key' => 'commercial-freezer', 'label' => 'Freezers', 'children' => 'menu', 'extra' => array( 'low-temperature-chest-freezers' ) ),
		array( 'key' => 'heated-display', 'label' => 'Heated Cabinets', 'children' => array( 'heated-display-cabinets', 'dual-temperature-hot-and-cold-premium-mirrored-stainless-steel-multideck' ) ),
		array( 'key' => 'stainless-steel-upright-bakery-cabinets', 'label' => 'Bakery Cabinets', 'children' => array( 'bakery-fridges', 'bakery-freezers', 'retarder-provers' ) ),
		array( 'key' => 'ovens-grills', 'label' => 'Ovens & Grills', 'term' => false, 'children' => array( 'ovens', 'grills', 'oven-stands' ) ),
		array( 'key' => 'medical', 'label' => 'Medical Cabinets', 'children' => array( 'upright-medical-fridge' ) ),
	);
}

/**
 * Category options: `opts` = key => {label, tax, term_id, url, children (keys,
 * for groups without a term)}, `tree` = the nested nodes to render
 * ({key, children: nodes}). A category can sit in two groups (e.g. Premium
 * Heated Display Cabinets: Display Cabinets and Heated Cabinets) — one option,
 * two nodes. Built once per request.
 *
 * @return array{opts:array,tree:array}
 */
function ef_pf_category_options() {
	static $out = null;
	if ( null !== $out ) { return $out; }
	$out = array( 'opts' => array(), 'tree' => array() );

	$add = function ( $ref, $label = '' ) use ( &$out ) {
		$tax  = 0 === strpos( $ref, 'tag:' ) ? 'product_tag' : 'product_cat';
		$slug = 'product_tag' === $tax ? substr( $ref, 4 ) : $ref;
		if ( isset( $out['opts'][ $slug ] ) ) {
			return $out['opts'][ $slug ]['tax'] === $tax ? $slug : '';
		}
		$term = get_term_by( 'slug', $slug, $tax );
		if ( ! $term ) { return ''; }
		$link = get_term_link( $term );
		$out['opts'][ $slug ] = array(
			'label'   => $label ? $label : html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
			'tax'     => $tax,
			'term_id' => (int) $term->term_id,
			'url'     => is_wp_error( $link ) ? '' : $link,
		);
		return $slug;
	};

	// Nodes under a Commercial menu item (product categories only, nested).
	$menu  = function_exists( 'ef_commercial_menu_tree' ) ? ef_commercial_menu_tree() : array( 'children' => array(), 'by_slug' => array() );
	$under = function ( $item_id ) use ( &$under, $menu, $add ) {
		$nodes = array();
		foreach ( $menu['children'][ $item_id ] ?? array() as $it ) {
			if ( 'taxonomy' !== $it->type || 'product_cat' !== $it->object ) { continue; }
			$term = get_term( (int) $it->object_id, 'product_cat' );
			$key  = ( $term && ! is_wp_error( $term ) ) ? $add( $term->slug ) : '';
			if ( $key ) {
				$nodes[] = array( 'key' => $key, 'children' => $under( (int) $it->ID ) );
			}
		}
		return $nodes;
	};

	foreach ( ef_pf_groups() as $g ) {
		$children = array();
		if ( 'menu' === $g['children'] ) {
			$children = isset( $menu['by_slug'][ $g['key'] ] ) ? $under( $menu['by_slug'][ $g['key'] ] ) : array();
		} else {
			foreach ( $g['children'] as $ref ) {
				$key = $add( $ref );
				if ( $key ) { $children[] = array( 'key' => $key, 'children' => array() ); }
			}
		}
		foreach ( $g['extra'] ?? array() as $ref ) {
			$key = $add( $ref );
			if ( $key ) { $children[] = array( 'key' => $key, 'children' => array() ); }
		}

		if ( isset( $g['term'] ) && false === $g['term'] ) {
			if ( ! $children ) { continue; }
			$out['opts'][ $g['key'] ] = array(
				'label'    => $g['label'],
				'tax'      => '',
				'term_id'  => 0,
				'url'      => '',
				'children' => wp_list_pluck( $children, 'key' ),
			);
			$key = $g['key'];
		} else {
			$key = $add( $g['key'], $g['label'] );
			if ( ! $key ) { continue; }
		}
		$out['tree'][] = array( 'key' => $key, 'children' => $children );
	}
	return $out;
}

/**
 * Type tags, in display order (migration 64). The bakery fridge / freezer are
 * Upright Storage Fridges / Freezers; ovens, grills, stands and the retarder
 * prover have type tags of their own (as well as their categories).
 *
 * @return string[]
 */
function ef_pf_type_tags() {
	return array(
		'multideck-fridges', 'upright-display-fridges', 'upright-cake-display-fridges', 'back-bar-fridges',
		'patisserie-display-fridges', 'grab-and-go-fridges', 'serve-over-counters', 'counter-display-fridges',
		'heated-displays', 'upright-display-freezers', 'chest-freezers', 'upright-storage-fridges',
		'gastronorm-fridges', 'upright-storage-freezers', 'gastronorm-freezers', 'counter-fridges',
		'counter-freezers', 'under-counter-fridges', 'under-counter-freezers', 'low-temperature-freezers',
		'medical-fridges', 'retarder-provers', 'ovens', 'grills', 'oven-stands',
	);
}

/**
 * Feature tags => the short label shown in the filter (the tag names carry
 * "Fridges & Freezers" for their own pages).
 *
 * @return array
 */
function ef_pf_feature_tags() {
	return array(
		'stainless-steel-fridges-freezers' => 'Stainless Steel',
		'black-commercial-fridges'         => 'Black',
		'slimline-display-fridges'         => 'Slimline',
		'double-door-fridges-freezers'     => 'Double Door',
		'double-glazed-display-cabinets'   => 'Double Glazed',
		'sliding-door-fridges-freezers'    => 'Sliding Door',
		'lockable-fridges-freezers'        => 'Lockable',
		'fridges-freezers-with-castors'    => 'With Castors',
		'high-ambient-fridges-freezers'    => 'High Ambient',
		'hotel-buffet-display-fridges'     => 'Hotel & Buffet',
		'ice-cream-freezer-tag'            => 'Ice Cream',
	);
}

/**
 * The options of a flat facet: value => {label, url}.
 *
 * @param string $facet condition|markets|width|height.
 * @return array
 */
function ef_pf_flat_options( $facet ) {
	static $cache = array();
	if ( isset( $cache[ $facet ] ) ) { return $cache[ $facet ]; }
	$opts = array();
	if ( 'condition' === $facet ) {
		$used = get_term_by( 'slug', EF_PF_USED_SLUG, 'product_cat' );
		$link = $used ? get_term_link( $used ) : '';
		$opts = array(
			'new'  => array( 'label' => 'New', 'url' => '' ),
			'used' => array( 'label' => 'Used', 'url' => is_wp_error( $link ) ? '' : $link ),
		);
	} elseif ( 'types' === $facet || 'features' === $facet ) {
		$labels = 'types' === $facet ? array_fill_keys( ef_pf_type_tags(), '' ) : ef_pf_feature_tags();
		foreach ( $labels as $slug => $label ) {
			$t = get_term_by( 'slug', $slug, 'product_tag' );
			if ( ! $t || ! $t->count ) { continue; }
			$link          = get_term_link( $t );
			$opts[ $slug ] = array(
				'label'   => $label ? $label : html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ),
				'url'     => is_wp_error( $link ) ? '' : $link,
				'term_id' => (int) $t->term_id,
			);
		}
	} else {
		$tax   = 'markets' === $facet ? 'market' : ( EF_PF_ATTRIBUTES[ $facet ] ?? '' );
		$terms = $tax && taxonomy_exists( $tax ) ? get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) ) : array();
		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			$link = 'market' === $tax ? get_term_link( $t ) : '';
			$opts[ $t->slug ] = array(
				'label'   => html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ),
				'url'     => is_wp_error( $link ) ? '' : $link,
				'term_id' => (int) $t->term_id,
			);
		}
	}
	return $cache[ $facet ] = $opts;
}

/* =========================================================================
 * 2. INDEX — every visible product's terms and price, cached.
 * ========================================================================= */

/**
 * product ID => {cat: term IDs incl. ancestors, tag, market: term IDs,
 * width, height: slugs, min, max: price}.
 *
 * @return array
 */
function ef_pf_index() {
	static $index = null;
	if ( null !== $index ) { return $index; }
	$index = get_transient( 'ef_pf_index' );
	if ( is_array( $index ) ) { return $index; }

	global $wpdb;
	$index = array();
	$args  = array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids', 'visibility' => 'catalog' );
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$args['stock_status'] = array( 'instock', 'onbackorder' );
	}
	$ids = array_map( 'intval', wc_get_products( $args ) );
	if ( $ids ) {
		foreach ( $ids as $id ) {
			$index[ $id ] = array( 'cat' => array(), 'tag' => array(), 'market' => array(), 'width' => array(), 'height' => array(), 'min' => 0.0, 'max' => 0.0 );
		}
		$taxes = array_filter( array_merge( array( 'product_cat', 'product_tag', 'market' ), array_values( EF_PF_ATTRIBUTES ) ), 'taxonomy_exists' );
		$terms = wp_get_object_terms( $ids, $taxes, array( 'fields' => 'all_with_object_id' ) );
		$lines = array();
		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			$id = (int) $t->object_id;
			if ( 'product_cat' === $t->taxonomy ) {
				if ( ! isset( $lines[ $t->term_id ] ) ) {
					$lines[ $t->term_id ] = array_merge( array( (int) $t->term_id ), array_map( 'intval', get_ancestors( $t->term_id, 'product_cat', 'taxonomy' ) ) );
				}
				$index[ $id ]['cat'] = array_merge( $index[ $id ]['cat'], $lines[ $t->term_id ] );
			} elseif ( 'product_tag' === $t->taxonomy ) {
				$index[ $id ]['tag'][] = (int) $t->term_id;
			} elseif ( 'market' === $t->taxonomy ) {
				$index[ $id ]['market'][] = (int) $t->term_id;
			} else {
				$index[ $id ][ array_search( $t->taxonomy, EF_PF_ATTRIBUTES, true ) ][] = $t->slug;
			}
		}
		foreach ( $index as $id => $p ) {
			$index[ $id ]['cat'] = array_values( array_unique( $p['cat'] ) );
		}
		// The prices WooCommerce's own price filter uses.
		$rows = $wpdb->get_results( "SELECT product_id, min_price, max_price FROM {$wpdb->wc_product_meta_lookup} WHERE product_id IN (" . implode( ',', $ids ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- integer IDs.
		foreach ( $rows as $r ) {
			$index[ (int) $r->product_id ]['min'] = (float) $r->min_price;
			$index[ (int) $r->product_id ]['max'] = (float) $r->max_price;
		}
	}
	set_transient( 'ef_pf_index', $index, DAY_IN_SECONDS );
	return $index;
}

/** Rebuild the index whenever products, their terms or prices change. */
function ef_pf_flush_index() {
	delete_transient( 'ef_pf_index' );
}
foreach ( array( 'save_post_product', 'deleted_post', 'woocommerce_update_product', 'woocommerce_product_set_stock_status', 'set_object_terms', 'edited_term', 'delete_term' ) as $ef_pf_hook ) {
	add_action( $ef_pf_hook, 'ef_pf_flush_index' );
}
unset( $ef_pf_hook );

/* =========================================================================
 * 3. STATE — the page's own option plus the URL's filters.
 * ========================================================================= */

/** Whether the request carries any filter (facets, attributes, price). */
function ef_pf_is_filtered() {
	foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter state.
		if ( 0 === strpos( $key, 'filter_' ) || in_array( $key, array( 'min_price', 'max_price' ), true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether the URL is a parameter variant of a listing for search engines: any
 * filter, a price range or a sort order (?orderby= repeats the same products).
 */
function ef_pf_is_parameterised() {
	if ( ef_pf_is_filtered() ) {
		return true;
	}
	foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
		if ( 'orderby' === $key || 0 === strpos( $key, 'query_type_' ) ) {
			return true;
		}
	}
	return false;
}

/** Whether the filter bar runs on this request (shop + product archives). */
function ef_pf_applies() {
	return ! is_admin() && function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) && ! is_search();
}

/**
 * A comma-separated URL parameter as a list of slugs.
 *
 * @param string $param Parameter.
 * @return string[]
 */
function ef_pf_param_list( $param ) {
	$raw = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter state.
	return '' === $raw ? array() : array_values( array_unique( array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) ) ) );
}

/**
 * The page context: `scope` = the archive's term every filter narrows
 * ({tax, term_id}; none on /shop/), `implicit` = the option the page itself
 * stands for (shown ticked), `url` = the page's own URL.
 *
 * @return array
 */
function ef_pf_context() {
	static $ctx = null;
	if ( null !== $ctx ) { return $ctx; }
	$shop = wc_get_page_permalink( 'shop' );
	$ctx  = array( 'implicit' => array(), 'scope' => null, 'url' => $shop );
	if ( ! is_product_taxonomy() ) { return $ctx; }

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) { return $ctx; }
	$link       = get_term_link( $term );
	$ctx['url'] = is_wp_error( $link ) ? $shop : $link;
	$opts       = ef_pf_category_options()['opts'];

	if ( 'product_cat' === $term->taxonomy && EF_PF_USED_SLUG === $term->slug ) {
		$ctx['implicit'] = array( 'condition' => array( 'used' ) );
	} elseif ( isset( $opts[ $term->slug ] ) && $opts[ $term->slug ]['term_id'] === (int) $term->term_id ) {
		$ctx['implicit'] = array( 'categories' => array( $term->slug ) );
	} elseif ( 'market' === $term->taxonomy ) {
		$ctx['implicit'] = array( 'markets' => array( $term->slug ) );
	} elseif ( 'product_tag' === $term->taxonomy && isset( ef_pf_flat_options( 'types' )[ $term->slug ] ) ) {
		$ctx['implicit'] = array( 'types' => array( $term->slug ) );
	} elseif ( 'product_tag' === $term->taxonomy && isset( ef_pf_flat_options( 'features' )[ $term->slug ] ) ) {
		$ctx['implicit'] = array( 'features' => array( $term->slug ) );
	}
	$ctx['scope'] = array( 'tax' => $term->taxonomy, 'term_id' => (int) $term->term_id );
	return $ctx;
}

/**
 * The filters in the URL: facet => values, 'price' => [min|null, max|null].
 * The page's own option is not one of them (it is the scope).
 *
 * @return array
 */
function ef_pf_state() {
	static $state = null;
	if ( null !== $state ) { return $state; }
	$state = array();
	foreach ( EF_PF_PARAMS as $facet => $param ) {
		$valid = 'categories' === $facet ? ef_pf_category_options()['opts'] : ef_pf_flat_options( $facet );
		$state[ $facet ] = array_values( array_filter( ef_pf_param_list( $param ), fn( $v ) => isset( $valid[ $v ] ) ) );
	}
	$state['price'] = array(
		isset( $_GET['min_price'] ) ? (float) wp_unslash( $_GET['min_price'] ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		isset( $_GET['max_price'] ) ? (float) wp_unslash( $_GET['max_price'] ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	);
	return $state;
}

/* =========================================================================
 * 4. MATCHING + COUNTS (from the index).
 * ========================================================================= */

/**
 * Whether a product belongs to a category option.
 *
 * @param array  $p   Index entry.
 * @param string $key Option key.
 * @return bool
 */
function ef_pf_in_category( $p, $key ) {
	$o = ef_pf_category_options()['opts'][ $key ] ?? null;
	if ( ! $o ) { return false; }
	if ( ! empty( $o['children'] ) ) {
		foreach ( $o['children'] as $child ) {
			if ( ef_pf_in_category( $p, $child ) ) { return true; }
		}
		return false;
	}
	return in_array( $o['term_id'], 'product_tag' === $o['tax'] ? $p['tag'] : $p['cat'], true );
}

/**
 * Whether a product belongs to an option of a flat facet.
 *
 * @param array  $p     Index entry.
 * @param string $facet Facet.
 * @param string $value Option value.
 * @return bool
 */
function ef_pf_in_option( $p, $facet, $value ) {
	switch ( $facet ) {
		case 'categories':
			return ef_pf_in_category( $p, $value );
		case 'condition':
			$used = get_term_by( 'slug', EF_PF_USED_SLUG, 'product_cat' );
			$is   = $used && in_array( (int) $used->term_id, $p['cat'], true );
			return 'used' === $value ? $is : ! $is;
		case 'markets':
			$o = ef_pf_flat_options( 'markets' )[ $value ] ?? null;
			return $o && in_array( $o['term_id'], $p['market'], true );
		case 'types':
		case 'features':
			$o = ef_pf_flat_options( $facet )[ $value ] ?? null;
			return $o && in_array( $o['term_id'], $p['tag'], true );
		default:
			return in_array( $value, $p[ $facet ], true );
	}
}

/**
 * Whether a product passes the page scope and every facet but $skip.
 *
 * @param array  $p     Index entry.
 * @param array  $state State.
 * @param string $skip  Facet left out (to count its own options).
 * @return bool
 */
function ef_pf_matches( $p, $state, $skip = '' ) {
	$scope = ef_pf_context()['scope'];
	if ( $scope ) {
		$field = array( 'product_cat' => 'cat', 'product_tag' => 'tag', 'market' => 'market' )[ $scope['tax'] ] ?? '';
		if ( ! $field || ! in_array( $scope['term_id'], $p[ $field ], true ) ) { return false; }
	}
	foreach ( array_keys( EF_PF_PARAMS ) as $facet ) {
		if ( $facet === $skip || empty( $state[ $facet ] ) ) { continue; }
		$any = false;
		foreach ( $state[ $facet ] as $value ) {
			if ( ef_pf_in_option( $p, $facet, $value ) ) { $any = true; break; }
		}
		if ( ! $any ) { return false; }
	}
	if ( 'price' !== $skip ) {
		list( $min, $max ) = $state['price'];
		if ( ( null !== $min && $p['max'] < $min ) || ( null !== $max && $p['min'] > $max ) ) { return false; }
	}
	return true;
}

/**
 * How many products each option of a facet would show, given the other facets.
 *
 * @param string $facet Facet.
 * @return array value => count
 */
function ef_pf_counts( $facet ) {
	static $cache = array();
	if ( isset( $cache[ $facet ] ) ) { return $cache[ $facet ]; }
	$state  = ef_pf_state();
	$values = array_keys( 'categories' === $facet ? ef_pf_category_options()['opts'] : ef_pf_flat_options( $facet ) );
	$counts = array_fill_keys( $values, 0 );
	foreach ( ef_pf_index() as $p ) {
		if ( ! ef_pf_matches( $p, $state, $facet ) ) { continue; }
		foreach ( $values as $v ) {
			if ( ef_pf_in_option( $p, $facet, $v ) ) { $counts[ $v ]++; }
		}
	}
	return $cache[ $facet ] = $counts;
}

/**
 * Price range of the products the other filters leave: [min, max], or null.
 *
 * @return float[]|null
 */
function ef_pf_price_range() {
	static $range = false;
	if ( false !== $range ) { return $range; }
	$range = null;
	$state = ef_pf_state();
	foreach ( ef_pf_index() as $p ) {
		if ( ! ef_pf_matches( $p, $state, 'price' ) ) { continue; }
		$range = $range ? array( min( $range[0], $p['min'] ), max( $range[1], $p['max'] ) ) : array( $p['min'], $p['max'] );
	}
	return $range;
}

/* =========================================================================
 * 5. QUERY — the URL's facets applied to the product archive's main query
 *    (width, height and price are WooCommerce's own).
 * ========================================================================= */
add_filter( 'woocommerce_product_query_tax_query', function ( $tax_query ) {
	if ( ! ef_pf_applies() ) { return $tax_query; }
	$state = ef_pf_state();

	$cats = $state['categories'];
	if ( $cats ) {
		$opts  = ef_pf_category_options()['opts'];
		$terms = array( 'product_cat' => array(), 'product_tag' => array() );
		$walk  = function ( $key ) use ( &$walk, &$terms, $opts ) {
			$o = $opts[ $key ];
			if ( ! empty( $o['children'] ) ) {
				array_map( $walk, $o['children'] );
			} else {
				$terms[ $o['tax'] ][] = $o['term_id'];
			}
		};
		array_map( $walk, $cats );
		$any = array( 'relation' => 'OR' );
		foreach ( array_filter( $terms ) as $tax => $ids ) {
			$any[] = array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => array_unique( $ids ), 'include_children' => true );
		}
		$tax_query[] = $any;
	}

	if ( 1 === count( $state['condition'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( EF_PF_USED_SLUG ),
			'operator' => 'used' === $state['condition'][0] ? 'IN' : 'NOT IN',
		);
	}

	if ( $state['markets'] ) {
		$tax_query[] = array( 'taxonomy' => 'market', 'field' => 'slug', 'terms' => $state['markets'] );
	}

	// Type and Features: each an OR of its tags, the two ANDed like every facet.
	foreach ( array( 'types', 'features' ) as $facet ) {
		if ( $state[ $facet ] ) {
			$tax_query[] = array( 'taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => $state[ $facet ] );
		}
	}
	return $tax_query;
} );

/** The price slider spans the products the other filters leave. */
add_filter( 'woocommerce_price_filter_widget_min_amount', function ( $amount ) {
	$range = ef_pf_applies() ? ef_pf_price_range() : null;
	$step  = max( apply_filters( 'woocommerce_price_filter_widget_step', 10 ), 1 );
	return $range ? floor( $range[0] / $step ) * $step : $amount;
} );
add_filter( 'woocommerce_price_filter_widget_max_amount', function ( $amount ) {
	$range = ef_pf_applies() ? ef_pf_price_range() : null;
	$step  = max( apply_filters( 'woocommerce_price_filter_widget_step', 10 ), 1 );
	return $range ? ceil( $range[1] / $step ) * $step : $amount;
} );

/**
 * The URL's filters as removable chips: [{label, url}] — url = this page
 * without that one filter (other filters, sorting kept; back to page 1).
 *
 * @return array[]
 */
function ef_pf_active_filters() {
	$state   = ef_pf_state();
	$current = preg_replace( '%/page/\d+/?%', '/', add_query_arg( array() ) );
	$current = remove_query_arg( 'paged', $current );
	$chips   = array();
	foreach ( EF_PF_PARAMS as $facet => $param ) {
		$opts = 'categories' === $facet ? ef_pf_category_options()['opts'] : ef_pf_flat_options( $facet );
		foreach ( $state[ $facet ] as $value ) {
			$rest = array_values( array_diff( $state[ $facet ], array( $value ) ) );
			$url  = $rest ? add_query_arg( $param, implode( ',', $rest ), $current ) : remove_query_arg( $param, $current );
			if ( count( $rest ) < 2 ) {
				$url = remove_query_arg( 'query_type_' . $facet, $url );
			}
			// Keep the lists readable (a,b, not a%2Cb), like the filter bar builds them.
			$chips[] = array( 'label' => $opts[ $value ]['label'], 'url' => str_replace( '%2C', ',', $url ) );
		}
	}
	list( $min, $max ) = $state['price'];
	if ( null !== $min || null !== $max ) {
		$money   = fn( $v ) => html_entity_decode( wp_strip_all_tags( wc_price( $v, array( 'decimals' => 0 ) ) ), ENT_QUOTES, 'UTF-8' );
		$chips[] = array(
			'label' => ( null !== $min ? $money( $min ) : '' ) . ' – ' . ( null !== $max ? $money( $max ) : '' ),
			'url'   => remove_query_arg( array( 'min_price', 'max_price' ), $current ),
		);
	}
	return $chips;
}

/* =========================================================================
 * 6. SEO — filtered and sorted URLs are noindex and disallowed in robots.txt.
 * ========================================================================= */
add_filter( 'rank_math/frontend/robots', function ( $robots ) {
	if ( ef_pf_applies() && ef_pf_is_parameterised() ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
} );
add_filter( 'wp_robots', function ( $robots ) {
	if ( ef_pf_applies() && ef_pf_is_parameterised() ) {
		unset( $robots['index'] );
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
} );

add_filter( 'robots_txt', function ( $output ) {
	$rules = '';
	foreach ( array( 'filter_', 'query_type_', 'min_price=', 'max_price=', 'orderby=' ) as $param ) {
		$rules .= "Disallow: /*?$param\nDisallow: /*&$param\n";
	}
	if ( preg_match( '/^User-agent:\s*\*\s*$/mi', $output, $m, PREG_OFFSET_CAPTURE ) ) {
		$at = $m[0][1] + strlen( $m[0][0] );
		return substr( $output, 0, $at ) . "\n" . rtrim( $rules ) . substr( $output, $at );
	}
	return "User-agent: *\n" . $rules . "\n" . $output;
}, 99 );

/* =========================================================================
 * 7. WIDGET — one facet per widget, so each one is its own panel (desktop)
 *    or dropdown button (mobile).
 * ========================================================================= */
class EF_Product_Filter_Widget extends WP_Widget {

	/** Facets this widget can show. */
	const FACETS = array(
		'categories' => 'Categories',
		'types'      => 'Type',
		'condition'  => 'Condition',
		'markets'    => 'Markets',
		'features'   => 'Features',
		'width'      => 'Width',
		'height'     => 'Height',
	);

	/** Unique IDs for the collapsible lists. */
	private static $uid = 0;

	/** The page's own option(s) in the facet being rendered. */
	private $page = array();

	public function __construct() {
		parent::__construct(
			'ef_product_filter',
			'Filter: Product facet',
			array( 'description' => 'One facet of the product filter bar (categories, type, condition, markets, features, width or height).' )
		);
	}

	public function widget( $args, $instance ) {
		if ( ! ef_pf_applies() ) { return; }
		$facet = $instance['facet'] ?? 'categories';
		if ( ! isset( self::FACETS[ $facet ] ) ) { return; }

		$counts     = ef_pf_counts( $facet );
		$selected   = ef_pf_state()[ $facet ];
		$this->page = ef_pf_context()['implicit'][ $facet ] ?? array();
		$checked    = array_merge( $selected, $this->page );
		if ( 'categories' === $facet ) {
			$html = $this->nodes( ef_pf_category_options()['tree'], $counts, $checked );
		} else {
			$html = '';
			foreach ( ef_pf_flat_options( $facet ) as $value => $o ) {
				$html .= $this->item( $facet, $value, $o['label'], $counts[ $value ] ?? 0, in_array( $value, $checked, true ) );
			}
		}
		if ( '' === $html ) { return; }

		$title = ! empty( $instance['title'] ) ? $instance['title'] : self::FACETS[ $facet ];
		// How many of this facet's filters are on (the mobile buttons show it too).
		$badge = $selected ? ' <span class="ef-pf-n" aria-label="' . count( $selected ) . ' selected">' . count( $selected ) . '</span>' : '';
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_title'] . esc_html( $title ) . $badge . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// Categories is a long list: on desktop it starts closed under its title (shop-filters.php).
		$collapsible = 'categories' === $facet ? ' data-collapsible="1"' : '';
		echo '<div class="ef-pf" data-facet="' . esc_attr( $facet ) . '"' . $collapsible . '><ul class="ef-pf-list">' . $html . '</ul></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * The category tree: an option is shown when it has products (or is
	 * checked, or has a child shown); a branch opens when it holds a checked option.
	 *
	 * @param array    $nodes   Nodes.
	 * @param array    $counts  Counts.
	 * @param string[] $checked Checked keys.
	 * @return string
	 */
	private function nodes( $nodes, $counts, $checked ) {
		$opts = ef_pf_category_options()['opts'];
		$html = '';
		foreach ( $nodes as $node ) {
			$key   = $node['key'];
			$sub   = $node['children'] ? $this->nodes( $node['children'], $counts, $checked ) : '';
			$count = $counts[ $key ] ?? 0;
			$is    = in_array( $key, $checked, true );
			if ( ! $count && ! $is && '' === $sub ) { continue; }
			$open  = $is || ( '' !== $sub && false !== strpos( $sub, ' checked' ) );
			$html .= $this->item( 'categories', $key, $opts[ $key ]['label'], $count, $is, $sub, $open );
		}
		return $html;
	}

	/**
	 * One option row (+ its collapsible children).
	 *
	 * @param string $facet   Facet.
	 * @param string $value   Option value.
	 * @param string $label   Label.
	 * @param int    $count   Products.
	 * @param bool   $checked Checked.
	 * @param string $sub     Children's HTML.
	 * @param bool   $open    Children shown.
	 * @return string
	 */
	private function item( $facet, $value, $label, $count, $checked, $sub = '', $open = false ) {
		if ( ! $count && ! $checked && '' === $sub ) { return ''; }
		$html = '<li class="ef-pf-item"><div class="ef-pf-row">'
			. '<label class="ef-pf-opt"><input type="checkbox" value="' . esc_attr( $value ) . '"' . ( $checked ? ' checked' : '' )
			. ( in_array( $value, $this->page, true ) ? ' data-page="1"' : '' ) . '>'
			. '<span class="ef-pf-box" aria-hidden="true"></span>'
			. '<span class="ef-pf-name">' . esc_html( $label ) . '</span>'
			. '<span class="ef-pf-count">' . (int) $count . '</span></label>';
		if ( '' !== $sub ) {
			$id    = 'ef-pf-sub-' . ( ++self::$uid );
			$html .= '<button type="button" class="ef-pf-toggle" aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-controls="' . $id . '"'
				. ' aria-label="' . esc_attr( ( $open ? 'Hide ' : 'Show ' ) . $label . ' ranges' ) . '">'
				. '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
				. '</button></div><ul class="ef-pf-sub" id="' . $id . '"' . ( $open ? '' : ' hidden' ) . '>' . $sub . '</ul>';
		} else {
			$html .= '</div>';
		}
		return $html . '</li>';
	}

	public function form( $instance ) {
		$facet = $instance['facet'] ?? 'categories';
		$title = $instance['title'] ?? '';
		echo '<p><label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">Title:</label>'
			. '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $title ) . '"></p>';
		echo '<p><label for="' . esc_attr( $this->get_field_id( 'facet' ) ) . '">Facet:</label>'
			. '<select class="widefat" id="' . esc_attr( $this->get_field_id( 'facet' ) ) . '" name="' . esc_attr( $this->get_field_name( 'facet' ) ) . '">';
		foreach ( self::FACETS as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $facet, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p>';
	}

	public function update( $new_instance, $old_instance ) {
		$facet = $new_instance['facet'] ?? 'categories';
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
			'facet' => isset( self::FACETS[ $facet ] ) ? $facet : 'categories',
		);
	}
}

add_action( 'widgets_init', function () {
	register_widget( 'EF_Product_Filter_Widget' );
} );

/* =========================================================================
 * 8. SCRIPT — a click builds the URL of the new selection and goes there.
 * ========================================================================= */
add_action( 'wp_footer', function () {
	if ( ! ef_pf_applies() ) { return; }
	$keep = array();
	foreach ( array( 'orderby', 'min_price', 'max_price' ) as $k ) {
		if ( isset( $_GET[ $k ] ) ) { $keep[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) ); } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$cfg = array(
		'shop'   => wc_get_page_permalink( 'shop' ),
		'base'   => ef_pf_context()['url'],
		'params' => EF_PF_PARAMS,
		'keep'   => $keep,
	);
	?>
<script>
(function(){
	var cfg = <?php echo wp_json_encode( $cfg ); ?>;
	var sec = document.getElementById('secondary');
	if(!sec){ return; }

	// The ticked filters of every facet (the page's own option is the page, not a filter).
	function state(){
		var s = {};
		Object.keys(cfg.params).forEach(function(f){ s[f] = []; });
		sec.querySelectorAll('.ef-pf input:checked:not([data-page])').forEach(function(i){
			var f = i.closest('.ef-pf').dataset.facet;
			if(s[f] && s[f].indexOf(i.value) < 0){ s[f].push(i.value); }
		});
		return s;
	}
	// This page (or /shop/ once its own option is unticked) + every ticked filter in
	// the query string.
	function build(s){
		var base = sec.querySelector('.ef-pf input[data-page]:not(:checked)') ? cfg.shop : cfg.base;
		var q = [];
		Object.keys(cfg.params).forEach(function(f){
			if(!s[f].length){ return; }
			q.push(cfg.params[f] + '=' + s[f].map(encodeURIComponent).join(','));
			if((f === 'width' || f === 'height') && s[f].length > 1){ q.push('query_type_' + f + '=or'); }
		});
		Object.keys(cfg.keep).forEach(function(k){ q.push(k + '=' + encodeURIComponent(cfg.keep[k])); });
		return base + (q.length ? (base.indexOf('?') < 0 ? '?' : '&') + q.join('&') : '');
	}

	sec.addEventListener('change', function(ev){
		var input = ev.target;
		if(!input.matches('.ef-pf input[type="checkbox"]')){ return; }
		var box = input.closest('.ef-pf');
		// Same option in two groups: keep the copies in step.
		box.querySelectorAll('input[value="' + CSS.escape(input.value) + '"]').forEach(function(i){ i.checked = input.checked; });
		if(input.checked && box.dataset.facet === 'categories'){
			// A range narrows its group and a group replaces its ranges: one level per line.
			box.querySelectorAll('input[value="' + CSS.escape(input.value) + '"]').forEach(function(i){
				var li = i.closest('.ef-pf-item');
				li.querySelectorAll('.ef-pf-sub input:not([data-page])').forEach(function(d){ d.checked = false; });
				for(var up = li.parentElement.closest('.ef-pf-item'); up; up = up.parentElement.closest('.ef-pf-item')){
					var a = up.querySelector(':scope > .ef-pf-row input');
					if(!a.dataset.page){ a.checked = false; }
				}
			});
		}
		document.body.classList.add('ef-pf-loading');
		window.location.assign(build(state()));
	});

	sec.addEventListener('click', function(ev){
		var btn = ev.target.closest('.ef-pf-toggle');
		if(!btn){ return; }
		var list = document.getElementById(btn.getAttribute('aria-controls'));
		var open = btn.getAttribute('aria-expanded') !== 'true';
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace(/^(Show|Hide)/, open ? 'Hide' : 'Show'));
		if(list){ list.hidden = !open; }
	});

	// Back/forward from the next page: drop the loading state.
	window.addEventListener('pageshow', function(){ document.body.classList.remove('ef-pf-loading'); });
})();
</script>
	<?php
}, 30 );
