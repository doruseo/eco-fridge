<?php
/**
 * XML sitemap: only pages worth indexing.
 *
 *  - no author archives (the users sitemap would also expose user names);
 *  - no cart, checkout or account pages (noindex anyway);
 *  - the /markets/<slug>/ pages are listed once, by the market taxonomy
 *    sitemap (they render the market archive – includes/markets.php).
 *
 * Rank Math's sitemap (/sitemap_index.xml) replaces WordPress core's
 * (/wp-sitemap.xml) when it is active; both get the same exclusions.
 *
 * @package EcoFridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pages kept out of the sitemap: cart, checkout, account and the market pages
 * under /markets/ (listed by the market taxonomy instead).
 *
 * @return int[]
 */
function efb_sitemap_excluded_page_ids() {
	$exclude = array();
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
			$exclude[] = (int) wc_get_page_id( $page );
		}
	}
	$hub = get_page_by_path( 'markets' );
	if ( $hub && function_exists( 'efb_market_term_of_page' ) ) {
		foreach ( get_pages( array( 'parent' => $hub->ID ) ) as $page ) {
			if ( efb_market_term_of_page( $page ) ) {
				$exclude[] = (int) $page->ID;
			}
		}
	}
	return array_values( array_filter( $exclude, static fn( $id ) => $id > 0 ) );
}

add_filter(
	'wp_sitemaps_add_provider',
	static fn( $provider, $name ) => 'users' === $name ? false : $provider,
	10,
	2
);

add_filter(
	'wp_sitemaps_posts_query_args',
	static function ( $args, $post_type ) {
		if ( 'page' !== $post_type ) {
			return $args;
		}
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), efb_sitemap_excluded_page_ids() );
		return $args;
	},
	10,
	2
);

add_filter(
	'rank_math/sitemap/posts_to_exclude',
	static fn( $ids ) => array_merge( (array) $ids, efb_sitemap_excluded_page_ids() )
);
