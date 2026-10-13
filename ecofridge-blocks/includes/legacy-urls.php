<?php
/**
 * URL compatibility with the original eco-fridge.co.uk site.
 *
 * The original site had several product taxonomies (`range`, `ranges`,
 * `condition`, `product_cat`, `market`). They were consolidated into WooCommerce
 * product categories, but every original URL must keep working unchanged:
 *
 *   /range/<parent>/<child>/   /ranges/<slug>/   /condition/<slug>/
 *   /product-category/<path>/  /market/<slug>/   /product/   /uncategorized/
 *
 * Each product category stores its original base in the `_efb_base` term meta
 * (range | ranges | condition; empty = product-category). Links are generated
 * with that base, the bases are routed to the product category archive, and
 * a canonical link is printed so duplicate paths don't split SEO signals.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allowed legacy bases for product categories.
 *
 * @return string[]
 */
function efb_legacy_bases() {
	return array( 'range', 'ranges', 'condition' );
}

/**
 * `market` taxonomy on products (original /market/<slug>/ archives).
 */
add_action(
	'init',
	static function () {
		register_taxonomy(
			'market',
			array( 'product' ),
			array(
				'labels'            => array(
					'name'          => __( 'Markets', 'ecofridge-blocks' ),
					'singular_name' => __( 'Market', 'ecofridge-blocks' ),
					'menu_name'     => __( 'Markets', 'ecofridge-blocks' ),
					'all_items'     => __( 'All Markets', 'ecofridge-blocks' ),
					'edit_item'     => __( 'Edit Market', 'ecofridge-blocks' ),
					'view_item'     => __( 'View Market', 'ecofridge-blocks' ),
					'update_item'   => __( 'Update Market', 'ecofridge-blocks' ),
					'add_new_item'  => __( 'Add New Market', 'ecofridge-blocks' ),
					'search_items'  => __( 'Search Markets', 'ecofridge-blocks' ),
					'back_to_items' => __( '&larr; Go to Markets', 'ecofridge-blocks' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'market',
					'with_front' => false,
				),
			)
		);
	},
	9
);

/**
 * Routing for the legacy bases (+ pagination) and /product/ + root category URLs.
 */
add_action(
	'init',
	static function () {
		$bases = implode( '|', efb_legacy_bases() );
		add_rewrite_rule( '^(' . $bases . ')/(.+?)/page/?([0-9]{1,})/?$', 'index.php?product_cat=$matches[2]&paged=$matches[3]', 'top' );
		add_rewrite_rule( '^(' . $bases . ')/(.+?)/?$', 'index.php?product_cat=$matches[2]', 'top' );
		// The original product post type archive.
		add_rewrite_rule( '^product/?$', 'index.php?post_type=product', 'top' );
		add_rewrite_rule( '^product/page/?([0-9]{1,})/?$', 'index.php?post_type=product&paged=$matches[1]', 'top' );
		// Post categories without the /category/ base (original: /blog/ and /uncategorized/).
		// /blog/ itself is served by the Blog page (page_for_posts), so it is skipped here.
		foreach ( array( 'uncategorized' ) as $efb_cat ) {
			add_rewrite_rule( '^' . $efb_cat . '/?$', 'index.php?category_name=' . $efb_cat, 'top' );
			add_rewrite_rule( '^' . $efb_cat . '/page/?([0-9]{1,})/?$', 'index.php?category_name=' . $efb_cat . '&paged=$matches[1]', 'top' );
		}
	},
	20
);

/**
 * Product category links use the original base.
 */
add_filter(
	'term_link',
	static function ( $url, $term, $taxonomy ) {
		if ( 'product_cat' === $taxonomy ) {
			$base = get_term_meta( $term->term_id, '_efb_base', true );
			if ( $base && in_array( $base, efb_legacy_bases(), true ) ) {
				$path = array( $term->slug );
				$p    = (int) $term->parent;
				while ( $p ) {
					$parent = get_term( $p, 'product_cat' );
					if ( ! $parent || is_wp_error( $parent ) ) {
						break;
					}
					array_unshift( $path, $parent->slug );
					$p = (int) $parent->parent;
				}
				return home_url( user_trailingslashit( $base . '/' . implode( '/', $path ) ) );
			}
		} elseif ( 'category' === $taxonomy && 'uncategorized' === $term->slug ) {
			return home_url( '/uncategorized/' );
		} elseif ( 'category' === $taxonomy && 'blog' === $term->slug ) {
			return home_url( '/blog/' );
		}
		return $url;
	},
	10,
	3
);

/**
 * The original site's redirects and 410 Gone URLs (includes/legacy-redirect-map.php).
 *
 * @return array{redirect:array<string,string>, gone:string[]}
 */
function efb_legacy_redirect_map() {
	static $map = null;
	if ( null === $map ) {
		$map = (array) require EFB_DIR . 'includes/legacy-redirect-map.php';
	}
	return $map;
}

/**
 * 301 old/duplicate paths to the canonical one:
 *  - /product-category/<path>/ of a category whose original base is /range/ etc.
 *  - /category/blog/ and /category/uncategorized/ (linked from the original theme).
 */
add_action(
	'template_redirect',
	static function () {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		/**
		 * Original URLs with no content worth keeping: a test post and the old
		 * card gateway's callback page. They stay reachable and redirect.
		 */
		$redirects = apply_filters(
			'efb_legacy_redirects',
			array(
				'/add-media-test/'       => '/blog/',
				'/woo_lloydscardnetcw/' => '/checkout/',
			)
		);
		if ( isset( $redirects[ trailingslashit( $path ) ] ) && is_404() ) {
			wp_safe_redirect( home_url( $redirects[ trailingslashit( $path ) ] ), 301 );
			exit;
		}
		// The original site's own redirects (and its 410 Gone URLs), for paths
		// that don't exist here or only match a media file by name (e.g. /multidecks/
		// would open Multidecks.jpg) – backlinks and bookmarks keep working.
		if ( is_404() || is_attachment() ) {
			$legacy = efb_legacy_redirect_map();
			$key    = false !== strpos( basename( $path ), '.' ) ? $path : trailingslashit( $path );
			if ( isset( $legacy['redirect'][ $key ] ) ) {
				wp_safe_redirect( home_url( $legacy['redirect'][ $key ] ), 301 );
				exit;
			}
			if ( is_404() && in_array( $key, $legacy['gone'], true ) ) {
				status_header( 410 ); // the 404 template still renders
			}
		}
		if ( preg_match( '~^/category/(blog|uncategorized)/?$~', $path, $m ) ) {
			wp_safe_redirect( home_url( '/' . $m[1] . '/' ), 301 );
			exit;
		}
		if ( is_product_category() && 0 === strpos( $path, '/product-category/' ) && ! is_paged() ) {
			$term = get_queried_object();
			$base = $term ? get_term_meta( $term->term_id, '_efb_base', true ) : '';
			if ( $base ) {
				wp_safe_redirect( get_term_link( $term ), 301 );
				exit;
			}
		}
	},
	5
);

/**
 * Canonical link on product category / market archives (WordPress core only
 * prints one for singular views). Keeps /ranges/storage-cabinets/ and
 * /range/storage-cabinets/ from competing with each other.
 */
add_action(
	'wp_head',
	static function () {
		if ( ! ( is_product_category() || is_tax( 'market' ) ) || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$link = get_term_link( get_queried_object() );
		if ( is_wp_error( $link ) ) {
			return;
		}
		$paged = (int) get_query_var( 'paged' );
		if ( $paged > 1 ) {
			$link = trailingslashit( $link ) . user_trailingslashit( 'page/' . $paged, 'paged' );
		}
		echo '<link rel="canonical" href="' . esc_url( $link ) . '" />' . "\n";
	},
	1
);

/**
 * Keep WordPress from "guessing" a different URL for legacy paths it can't
 * find (e.g. /market/airports/ → /markets/airports/): legacy URLs must resolve
 * to themselves.
 */
add_filter(
	'do_redirect_guess_404_permalink',
	static function ( $guess ) {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		return preg_match( '~^/(market|range|ranges|condition|brochure|video)/~', $path ) ? false : $guess;
	}
);
