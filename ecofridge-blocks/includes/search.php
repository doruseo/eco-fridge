<?php
/**
 * Live product + product category search (replaces FiboSearch).
 *
 * REST: GET /wp-json/efb/v1/search?s=<term>
 * Matches product titles, SKUs and model codes in the content, plus product
 * category names. Results are cached briefly per search term.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'efb/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					's' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'callback'            => 'efb_search_rest',
			)
		);
	}
);

/**
 * REST callback.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function efb_search_rest( WP_REST_Request $request ) {
	// Long strings are never product searches; capping them also keeps junk
	// requests from filling the cache with one entry each.
	$term = mb_substr( trim( (string) $request->get_param( 's' ) ), 0, 60 );
	if ( mb_strlen( $term ) < 2 ) {
		return rest_ensure_response( array( 'categories' => array(), 'products' => array(), 'total' => 0 ) );
	}
	$key    = 'efb_s_' . md5( strtolower( $term ) );
	$result = get_transient( $key );
	if ( false === $result ) {
		$result = efb_search( $term, 8 );
		set_transient( $key, $result, 10 * MINUTE_IN_SECONDS );
	}
	$response = rest_ensure_response( $result );
	$response->header( 'Cache-Control', 'public, max-age=300' );
	return $response;
}

/**
 * Run the search.
 *
 * @param string $term  Search term.
 * @param int    $limit Max products.
 * @return array
 */
function efb_search( $term, $limit = 8 ) {
	global $wpdb;

	// Categories whose name contains the term.
	$categories = array();
	$terms      = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'name__like' => $term,
			'hide_empty' => true,
			'number'     => 5,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$categories[] = array(
				'name'  => html_entity_decode( $t->name ),
				'url'   => get_term_link( $t ),
				'count' => (int) $t->count,
			);
		}
	}

	// Products: title / content search, plus exact SKU and model code matches.
	$like = '%' . $wpdb->esc_like( $term ) . '%';
	$ids  = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
			 WHERE p.post_type = 'product' AND p.post_status = 'publish'
			   AND ( p.post_title LIKE %s OR sku.meta_value LIKE %s OR p.post_excerpt LIKE %s OR p.post_content LIKE %s )
			 ORDER BY ( p.post_title LIKE %s ) DESC, ( sku.meta_value LIKE %s ) DESC, p.menu_order ASC, p.post_title ASC
			 LIMIT %d",
			$like,
			$like,
			$like,
			$like,
			$like,
			$like,
			60
		)
	);
	$ids   = array_values( array_unique( array_map( 'intval', $ids ) ) );
	$total = count( $ids );

	$products = array();
	foreach ( array_slice( $ids, 0, $limit ) as $id ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		$products[] = array(
			'title' => html_entity_decode( get_the_title( $id ) ),
			'url'   => get_permalink( $id ),
			'image' => (string) get_the_post_thumbnail_url( $id, 'thumbnail' ),
			'sku'   => (string) $product->get_sku(),
			'price' => wp_strip_all_tags( efb_from_price_html( $id ) ),
		);
	}

	return array(
		'categories' => $categories,
		'products'   => $products,
		'total'      => $total,
		'all_url'    => add_query_arg(
			array(
				's'         => rawurlencode( $term ),
				'post_type' => 'product',
			),
			home_url( '/' )
		),
	);
}

/**
 * Render the search form markup (used by the block and by the theme header).
 *
 * @param array $args { placeholder, button, className }.
 * @return string
 */
function efb_render_search_form( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'placeholder' => __( 'Search for products...', 'ecofridge-blocks' ),
			'button'      => __( 'Search', 'ecofridge-blocks' ),
			'showButton'  => true,
			'className'   => '',
		)
	);
	wp_enqueue_style( 'efb-search' );
	wp_enqueue_script( 'efb-search' );

	$uid = wp_unique_id( 'efb-search-' );
	ob_start();
	?>
	<form class="efb-search <?php echo esc_attr( $args['className'] ); ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-endpoint="<?php echo esc_url( rest_url( 'efb/v1/search' ) ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Search products', 'ecofridge-blocks' ); ?></label>
		<input id="<?php echo esc_attr( $uid ); ?>" class="efb-search__input" type="search" name="s" autocomplete="off" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="<?php echo esc_attr( $uid ); ?>-list" />
		<input type="hidden" name="post_type" value="product" />
		<?php if ( $args['showButton'] ) : ?>
			<button class="efb-search__submit" type="submit"><span><?php echo esc_html( $args['button'] ); ?></span></button>
		<?php endif; ?>
		<div class="efb-search__results" id="<?php echo esc_attr( $uid ); ?>-list" role="listbox" hidden></div>
	</form>
	<?php
	return (string) ob_get_clean();
}

add_shortcode(
	'efb_search',
	static function ( $atts ) {
		return efb_render_search_form( shortcode_atts( array( 'placeholder' => __( 'Search for products...', 'ecofridge-blocks' ) ), $atts ) );
	}
);
