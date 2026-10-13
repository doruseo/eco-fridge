<?php
/**
 * Market landing pages: /markets/<slug>/.
 *
 * The original site had two URLs per market: a page under /markets/ (intro
 * text + products) and the `market` taxonomy archive at /market/<slug>/. Both
 * keep working, but each /markets/<slug>/ URL now renders the market archive
 * itself – the same landing page as the commercial categories (filters,
 * product grid, FAQ, comparison table, articles, long description, related
 * categories, reviews). The content is edited on the market term
 * (Products → Markets); the market's term link and canonical URL is the
 * /markets/<slug>/ page, so /market/<slug>/ does not compete with it.
 *
 * @package EcoFridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * The /markets/<slug>/ page of a market, if there is one.
 *
 * @param WP_Term|int $term Market term or ID.
 * @return WP_Post|null
 */
function efb_market_page( $term ) {
	$term = get_term( $term, 'market' );
	if ( ! $term || is_wp_error( $term ) ) {
		return null;
	}
	$page = get_page_by_path( 'markets/' . $term->slug );
	return $page && 'publish' === $page->post_status ? $page : null;
}

/**
 * Route /markets/<slug>/ (and its /page/N/) to the market archive.
 */
add_filter(
	'request',
	static function ( $vars ) {
		if ( is_admin() || empty( $vars['pagename'] ) || ! preg_match( '~^markets/([^/]+)$~', $vars['pagename'], $m ) ) {
			return $vars;
		}
		$term = get_term_by( 'slug', $m[1], 'market' );
		if ( ! $term ) {
			return $vars;
		}
		$routed = array( 'market' => $term->slug );
		if ( ! empty( $vars['paged'] ) ) {
			$routed['paged'] = (int) $vars['paged'];
		}
		foreach ( array( 'orderby', 'order', 'preview' ) as $keep ) {
			if ( isset( $vars[ $keep ] ) ) {
				$routed[ $keep ] = $vars[ $keep ];
			}
		}
		return $routed;
	}
);

/**
 * A market with a /markets/<slug>/ page links there (filters, canonical, menus).
 */
add_filter(
	'term_link',
	static function ( $link, $term, $taxonomy ) {
		if ( 'market' !== $taxonomy || ! efb_market_page( $term ) ) {
			return $link;
		}
		return home_url( user_trailingslashit( 'markets/' . $term->slug ) );
	},
	10,
	3
);

/**
 * Don't let WordPress redirect /market/<slug>/ to the term link: the original
 * URLs must keep resolving (their canonical points to /markets/<slug>/).
 */
add_filter(
	'redirect_canonical',
	static function ( $redirect ) {
		return is_tax( 'market' ) ? false : $redirect;
	}
);

/**
 * The market's heading: the title of its /markets/ page (as on the original
 * site, e.g. "Restaurant & Bar Display Fridges"), falling back to the term name.
 *
 * @return string
 */
function efb_market_title() {
	$term = get_queried_object();
	$page = $term instanceof WP_Term ? efb_market_page( $term ) : null;
	return $page ? get_the_title( $page ) : ( $term instanceof WP_Term ? $term->name : '' );
}

add_filter(
	'woocommerce_page_title',
	static function ( $title ) {
		return is_tax( 'market' ) ? efb_market_title() : $title;
	}
);
add_filter(
	'get_the_archive_title',
	static function ( $title ) {
		return is_tax( 'market' ) ? efb_market_title() : $title;
	},
	20
);
add_filter(
	'document_title_parts',
	static function ( $parts ) {
		if ( is_tax( 'market' ) ) {
			$parts['title'] = efb_market_title();
		}
		return $parts;
	}
);

/**
 * Breadcrumbs: "Markets" (the taxonomy label) links to the /markets/ hub page,
 * and the market crumb uses the page title.
 */
add_filter(
	'woocommerce_get_breadcrumb',
	static function ( $crumbs ) {
		$hub = is_tax( 'market' ) ? get_page_by_path( 'markets' ) : null;
		if ( ! $hub ) {
			return $crumbs;
		}
		$last = count( $crumbs ) - 1;
		foreach ( $crumbs as $i => $crumb ) {
			if ( 'Markets' === $crumb[0] && empty( $crumb[1] ) ) {
				$crumbs[ $i ] = array( get_the_title( $hub ), get_permalink( $hub ) );
			}
		}
		$crumbs[ $last ][0] = efb_market_title();
		return $crumbs;
	}
);

/**
 * Pages list: flag the market pages and link to where their content is edited.
 */
add_filter(
	'display_post_states',
	static function ( $states, $post ) {
		if ( 'page' === $post->post_type && efb_market_term_of_page( $post ) ) {
			$states['efb_market'] = __( 'Market landing (edit under Products → Markets)', 'ecofridge-blocks' );
		}
		return $states;
	},
	10,
	2
);
add_filter(
	'page_row_actions',
	static function ( $actions, $post ) {
		$term = efb_market_term_of_page( $post );
		if ( $term && current_user_can( 'manage_product_terms' ) ) {
			$actions['efb_market'] = '<a href="' . esc_url( get_edit_term_link( $term->term_id, 'market', 'product' ) ) . '">' . esc_html__( 'Edit market landing', 'ecofridge-blocks' ) . '</a>';
		}
		return $actions;
	},
	10,
	2
);

/**
 * Block editor: tell editors of a market page that its content lives on the term.
 */
add_action(
	'enqueue_block_editor_assets',
	static function () {
		$post = get_post();
		$term = $post ? efb_market_term_of_page( $post ) : null;
		if ( ! $term ) {
			return;
		}
		$message = __( 'This URL shows the market landing page (filters, products, FAQ, guides). Its content is edited under Products → Markets.', 'ecofridge-blocks' );
		wp_add_inline_script(
			'wp-notices',
			'wp.domReady&&wp.domReady(function(){wp.data.dispatch("core/notices").createWarningNotice(' . wp_json_encode( $message ) . ',{isDismissible:false,actions:[{label:' . wp_json_encode( __( 'Edit market landing', 'ecofridge-blocks' ) ) . ',url:' . wp_json_encode( get_edit_term_link( $term->term_id, 'market', 'product' ) ) . '}]});});',
			'after'
		);
		wp_enqueue_script( 'wp-dom-ready' );
	}
);

/**
 * The market a /markets/<slug>/ page renders.
 *
 * @param WP_Post $post Page.
 * @return WP_Term|null
 */
function efb_market_term_of_page( $post ) {
	if ( ! $post->post_parent || 'markets' !== get_post_field( 'post_name', $post->post_parent ) ) {
		return null;
	}
	$term = get_term_by( 'slug', $post->post_name, 'market' );
	return $term ? $term : null;
}
