<?php
/**
 * Blog topic categories at /blog/<slug>/.
 *
 * The posts page is /blog/ and the "Blog" category links there (legacy-urls.php).
 * Its child categories (Buying Guides, How-To & Maintenance…) live under it:
 * /blog/buying-guides/, /blog/buying-guides/page/2/. The old WordPress paths
 * (/category/blog/<slug>/) redirect there. Post URLs are not affected.
 *
 * @package EcoFridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Child categories of "Blog".
 *
 * @return WP_Term[]
 */
function efb_blog_categories() {
	$parent = get_term_by( 'slug', 'blog', 'category' );
	if ( ! $parent ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => $parent->term_id,
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort(
		$terms,
		static fn( $a, $b ) => (int) get_term_meta( $a->term_id, 'ef_order', true ) <=> (int) get_term_meta( $b->term_id, 'ef_order', true )
	);
	return $terms;
}

add_action(
	'init',
	static function () {
		$slugs = wp_list_pluck( efb_blog_categories(), 'slug' );
		if ( ! $slugs ) {
			return;
		}
		$re = '(' . implode( '|', array_map( 'preg_quote', $slugs ) ) . ')';
		add_rewrite_rule( '^blog/' . $re . '/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( '^blog/' . $re . '/?$', 'index.php?category_name=$matches[1]', 'top' );
	},
	20
);

// New or renamed topic categories need the rules above.
foreach ( array( 'created_category', 'edited_category', 'delete_category' ) as $efb_hook ) {
	add_action( $efb_hook, static fn() => delete_option( 'rewrite_rules' ) );
}

add_filter(
	'term_link',
	static function ( $url, $term, $taxonomy ) {
		if ( 'category' !== $taxonomy || ! $term->parent ) {
			return $url;
		}
		$parent = get_term( $term->parent, 'category' );
		if ( $parent && ! is_wp_error( $parent ) && 'blog' === $parent->slug ) {
			return home_url( user_trailingslashit( 'blog/' . $term->slug ) );
		}
		return $url;
	},
	10,
	3
);

/**
 * /category/blog/<slug>/ (WordPress' own path) → /blog/<slug>/.
 */
add_action(
	'template_redirect',
	static function () {
		if ( ! is_category() ) {
			return;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		if ( 0 !== strpos( $path, '/category/blog/' ) ) {
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
		wp_safe_redirect( $link, 301 );
		exit;
	},
	4
);
