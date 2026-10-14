<?php
/**
 * Live search (replaces FiboSearch).
 *
 * REST: GET /wp-json/efb/v1/search?s=<term>
 * Results, in order of importance: commercial product categories (name or
 * their "Commercial Menu" label), product tags, then products (titles, SKUs
 * and model codes in the content; every word must match). Plural and
 * singular match each other. Results are cached briefly per search term.
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
		register_rest_route(
			'efb/v1',
			'/search-index',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => 'efb_search_index_rest',
			)
		);
	}
);

/*
 * Instant search: the browser downloads one small index of the categories,
 * tags and products (about 130 products) the first time the search box is
 * used, keeps it, and searches it locally with the same rules as
 * efb_search() — no server round trip per keystroke. The REST search below
 * is still used before the index has arrived and when the index finds no
 * product (it also looks through the full product descriptions).
 */

/**
 * Version of the search index; changes whenever a product, category, tag or
 * menu changes, so browsers fetch a fresh copy.
 *
 * @return string
 */
function efb_search_index_version() {
	$v = get_option( 'efb_search_index_ver' );
	if ( ! $v ) {
		$v = (string) time();
		update_option( 'efb_search_index_ver', $v, true );
	}
	return $v;
}

/** URL of the current search index. */
function efb_search_index_url() {
	return add_query_arg( 'v', efb_search_index_version(), rest_url( 'efb/v1/search-index' ) );
}

/**
 * The search index.
 *
 * @return array { cats: [], tags: [], products: [] }
 */
function efb_search_index() {
	$index = get_transient( 'efb_search_index' );
	if ( is_array( $index ) ) {
		return $index;
	}

	$cats = array();
	foreach ( efb_search_category_index() as $id => $c ) {
		$cats[] = array(
			'id'     => $id,
			'n'      => $c['name'],
			'pa'     => $c['parent'],
			'u'      => $c['url'],
			'k'      => $c['count'],
			'l'      => array_values( array_unique( array_map( 'html_entity_decode', $c['labels'] ) ) ),
			'm'      => $c['in_menu'] ? 1 : 0,
			'im'     => $c['image'],
		);
	}

	$tags = array();
	$all  = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true ) );
	foreach ( is_wp_error( $all ) ? array() : $all as $t ) {
		$tags[] = array(
			'id' => $t->term_id,
			'n'  => html_entity_decode( $t->name ),
			'u'  => get_term_link( $t ),
			'k'  => (int) $t->count,
		);
	}

	$products = array();
	$ids      = get_posts(
		array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
	foreach ( $ids as $id ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		// Categories with their parents, so a product counts for "Display Fridges"
		// when it sits in "Glass Door Fridges".
		$own     = array_map( 'intval', wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'ids' ) ) );
		$cat_ids = $own;
		foreach ( $own as $cid ) {
			$cat_ids = array_merge( $cat_ids, get_ancestors( $cid, 'product_cat', 'taxonomy' ) );
		}
		// The description, reduced to its distinct words and model codes
		// ("ECO-P75/60CC"), as the server search reads the full text.
		$text  = strtolower( html_entity_decode( wp_strip_all_tags( get_post_field( 'post_excerpt', $id ) . ' ' . get_post_field( 'post_content', $id ) ), ENT_QUOTES, 'UTF-8' ) );
		$parts = preg_split( '~[\s,;:!?()\[\]"]+~', $text, -1, PREG_SPLIT_NO_EMPTY );
		$parts = array_unique( array_filter( array_map( static function ( $w ) {
			return trim( $w, '.-' );
		}, $parts ), static function ( $w ) {
			return strlen( $w ) > 1 && strlen( $w ) < 40;
		} ) );
		$products[] = array(
			't' => html_entity_decode( get_the_title( $id ) ),
			'u' => get_permalink( $id ),
			'i' => (string) get_the_post_thumbnail_url( $id, 'thumbnail' ),
			's' => (string) $product->get_sku(),
			'p' => html_entity_decode( wp_strip_all_tags( efb_from_price_html( $id ) ), ENT_QUOTES, 'UTF-8' ),
			'a' => $own,
			'c' => array_values( array_unique( array_map( 'intval', $cat_ids ) ) ),
			'g' => array_map( 'intval', wp_get_post_terms( $id, 'product_tag', array( 'fields' => 'ids' ) ) ),
			'x' => implode( ' ', $parts ),
		);
	}

	$index = array(
		'cats'     => $cats,
		'tags'     => $tags,
		'products' => $products,
		'popular'  => efb_search_popular(),
	);
	set_transient( 'efb_search_index', $index, DAY_IN_SECONDS );
	return $index;
}

/**
 * REST callback for the index. The URL carries the version; browsers keep
 * the answer for an hour (a cached page may still point to an older version).
 *
 * @return WP_REST_Response
 */
function efb_search_index_rest() {
	$response = rest_ensure_response( efb_search_index() + array( 'v' => efb_search_index_version() ) );
	$response->header( 'Cache-Control', 'public, max-age=3600' );
	return $response;
}

/** Product, category, tag or menu changed: rebuild the index and the cached results. */
function efb_search_index_flush() {
	delete_transient( 'efb_search_index' );
	delete_transient( 'efb_search_cat_index' );
	update_option( 'efb_search_index_ver', (string) time(), true );
}
add_action( 'save_post_product', 'efb_search_index_flush' );
add_action( 'deleted_post', 'efb_search_index_flush' );
add_action( 'woocommerce_product_set_stock_status', 'efb_search_index_flush' );
foreach ( array( 'product_cat', 'product_tag' ) as $efb_tax ) {
	add_action( "created_{$efb_tax}", 'efb_search_index_flush' );
	add_action( "edited_{$efb_tax}", 'efb_search_index_flush' );
	add_action( "delete_{$efb_tax}", 'efb_search_index_flush' );
}
add_action( 'wp_update_nav_menu', 'efb_search_index_flush' );

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
		return rest_ensure_response( array( 'categories' => array(), 'tags' => array(), 'products' => array(), 'total' => 0 ) );
	}
	$key    = 'efb_s2_' . md5( strtolower( $term ) );
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
 * Lower-case words of a text, each reduced to a rough singular
 * ("fridges" → "fridge", "batteries" → "battery"), so plural and singular match.
 *
 * @param string $text Text.
 * @return string[]
 */
function efb_search_words( $text ) {
	$text  = strtolower( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) );
	$text  = str_replace( '&', ' and ', $text );
	$words = preg_split( '~[^a-z0-9]+~', $text, -1, PREG_SPLIT_NO_EMPTY );
	return array_map( 'efb_search_singular', $words );
}

/**
 * Rough English singular of one lower-case word.
 *
 * @param string $w Word.
 * @return string
 */
function efb_search_singular( $w ) {
	if ( strlen( $w ) > 4 && 'ies' === substr( $w, -3 ) ) {
		return substr( $w, 0, -3 ) . 'y';
	}
	if ( strlen( $w ) > 3 && 's' === substr( $w, -1 ) && 'ss' !== substr( $w, -2 ) ) {
		return substr( $w, 0, -1 );
	}
	return $w;
}

/**
 * How well a name matches the query words (0 = no match). Every query word
 * must start one of the name's words; the whole phrase at the start of the
 * name, or anywhere in it, ranks higher, and so do names with fewer extra
 * words ("Display Fridges" before "Premium Upright Display Fridges").
 *
 * @param string[] $q    Query words.
 * @param string   $name Name.
 * @return int
 */
function efb_search_score( $q, $name ) {
	$words = efb_search_words( $name );
	if ( ! $q || ! $words ) {
		return 0;
	}
	$score = 0;
	foreach ( $q as $qw ) {
		$best = 0;
		foreach ( $words as $w ) {
			if ( $w === $qw ) {
				$best = 20;
				break;
			}
			if ( 0 === strpos( $w, $qw ) ) {
				$best = 12;
			}
		}
		if ( ! $best ) {
			return 0;
		}
		$score += $best;
	}
	$phrase = implode( ' ', $q );
	$joined = implode( ' ', $words );
	if ( 0 === strpos( $joined, $phrase ) ) {
		$score += 40;
	} elseif ( false !== strpos( ' ' . $joined, ' ' . $phrase ) ) {
		$score += 20;
	}
	return $score + max( 0, 10 - 2 * ( count( $words ) - count( $q ) ) );
}

/**
 * Product categories, each with the labels it carries in the "Commercial
 * Menu" (e.g. "Standard Upright Display Chiller"), which are matched too.
 *
 * @return array[] term_id => { name, parent, count, url, labels, in_menu }
 */
function efb_search_category_index() {
	$index = get_transient( 'efb_search_cat_index' );
	if ( is_array( $index ) ) {
		return $index;
	}
	$index = array();
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
		if ( 'uncategorized' === $t->slug ) {
			continue;
		}
		// Picture: the category's own, else the first product's in it.
		$thumb = (int) get_term_meta( $t->term_id, 'thumbnail_id', true );
		$image = $thumb ? (string) wp_get_attachment_image_url( $thumb, 'thumbnail' ) : '';
		if ( ! $image ) {
			$first = get_posts(
				array(
					'post_type'   => 'product',
					'post_status' => 'publish',
					'numberposts' => 1,
					'fields'      => 'ids',
					'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
					'meta_key'    => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
					'tax_query'   => array( array( 'taxonomy' => 'product_cat', 'terms' => $t->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			$image = $first ? (string) get_the_post_thumbnail_url( $first[0], 'thumbnail' ) : '';
		}
		$index[ $t->term_id ] = array(
			'name'    => html_entity_decode( $t->name ),
			'parent'  => (int) $t->parent,
			'count'   => (int) $t->count,
			'url'     => get_term_link( $t ),
			'image'   => $image,
			'labels'  => array( $t->name ),
			'in_menu' => false,
			'top'     => 0, // position in the menu's first row (1, 2, …), 0 = not there
			'short'   => '', // label in the menu's first row ("Medical")
		);
	}
	$menu = wp_get_nav_menu_object( 'Commercial Menu' );
	$pos  = 0;
	foreach ( $menu ? (array) wp_get_nav_menu_items( $menu->term_id ) : array() as $item ) {
		$id = (int) $item->object_id;
		if ( 'product_cat' === $item->object && isset( $index[ $id ] ) ) {
			$index[ $id ]['labels'][] = $item->title;
			$index[ $id ]['in_menu']  = true;
			if ( ! (int) $item->menu_item_parent ) {
				$index[ $id ]['top']   = ++$pos;
				$index[ $id ]['short'] = html_entity_decode( $item->title );
			}
		}
	}
	set_transient( 'efb_search_cat_index', $index, HOUR_IN_SECONDS );
	return $index;
}

/**
 * The main ranges, as in the first row of the "Commercial Menu" — offered
 * when a search finds nothing.
 *
 * @return array[] Each: name, url.
 */
function efb_search_popular() {
	$top = array_filter(
		efb_search_category_index(),
		static function ( $c ) {
			return $c['top'] > 0;
		}
	);
	uasort(
		$top,
		static function ( $a, $b ) {
			return $a['top'] <=> $b['top'];
		}
	);
	return array_values(
		array_map(
			static function ( $c ) {
				return array( 'name' => $c['short'] ? $c['short'] : $c['name'], 'url' => $c['url'] );
			},
			$top
		)
	);
}

/** Forget the category index after a category or menu change. */
function efb_search_flush() {
	delete_transient( 'efb_search_cat_index' );
}
add_action( 'created_product_cat', 'efb_search_flush' );
add_action( 'edited_product_cat', 'efb_search_flush' );
add_action( 'delete_product_cat', 'efb_search_flush' );
add_action( 'wp_update_nav_menu', 'efb_search_flush' );

/**
 * Run the search: commercial categories first, then tags, then products.
 *
 * @param string $term  Search term.
 * @param int    $limit Max products.
 * @return array
 */
function efb_search( $term, $limit = 8 ) {
	global $wpdb;

	$q     = efb_search_words( $term );
	$index = efb_search_category_index();

	// 1. Commercial categories (name or menu label); categories in the menu
	// and main categories rank higher, then the ones with more products.
	$scored = array();
	foreach ( $index as $id => $c ) {
		$score = 0;
		foreach ( $c['labels'] as $label ) {
			$score = max( $score, efb_search_score( $q, $label ) );
		}
		if ( $score ) {
			$scored[ $id ] = $score + ( $c['in_menu'] ? 15 : 0 ) + ( 0 === $c['parent'] ? 10 : 0 ) + min( 10, (int) round( 2 * log( 1 + $c['count'] ) ) );
		}
	}

	// 2. Tags.
	$tags = array();
	$all  = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true ) );
	foreach ( is_wp_error( $all ) ? array() : $all as $t ) {
		$score = efb_search_score( $q, $t->name );
		if ( $score ) {
			$tags[] = array(
				'name'  => html_entity_decode( $t->name ),
				'url'   => get_term_link( $t ),
				'count' => (int) $t->count,
				'score' => $score,
			);
		}
	}
	usort(
		$tags,
		static function ( $a, $b ) {
			return $b['score'] <=> $a['score'] ?: $b['count'] <=> $a['count'];
		}
	);
	$tags = array_map(
		static function ( $t ) {
			unset( $t['score'] );
			return $t;
		},
		array_slice( $tags, 0, 6 )
	);

	// 3. Products: every word must appear in the title, SKU, excerpt or
	// content; products with every word in the title first, then SKU matches.
	$where  = array();
	$args   = array();
	$titles = array();
	foreach ( $q ? $q : array( strtolower( $term ) ) as $w ) {
		// "battery" (from "batteries") is searched as "batter" to match both forms.
		$stem     = strlen( $w ) > 4 && 'y' === substr( $w, -1 ) ? substr( $w, 0, -1 ) : $w;
		$like     = '%' . $wpdb->esc_like( $stem ) . '%';
		$where[]  = '( p.post_title LIKE %s OR sku.meta_value LIKE %s OR p.post_excerpt LIKE %s OR p.post_content LIKE %s )';
		$args     = array_merge( $args, array( $like, $like, $like, $like ) );
		$titles[] = $like;
	}
	$title_sql = implode( ' AND ', array_fill( 0, count( $titles ), 'p.post_title LIKE %s' ) );
	$ids       = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- only placeholder lists are built in.
			"SELECT p.ID FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
			 WHERE p.post_type = 'product' AND p.post_status = 'publish'
			   AND " . implode( ' AND ', $where ) . "
			 ORDER BY ( $title_sql ) DESC, ( COALESCE( sku.meta_value, '' ) LIKE %s ) DESC, p.menu_order ASC, p.post_title ASC
			 LIMIT %d",
			array_merge( $args, $titles, array( '%' . $wpdb->esc_like( $term ) . '%', 60 ) )
		)
	);
	$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );

	// Few text matches ("beer" – the beer fridges are not called that): add
	// the products of the best matching categories and tag.
	if ( count( $ids ) < $limit && ( $scored || $tags ) ) {
		arsort( $scored );
		$tax = array( 'relation' => 'OR' );
		if ( $scored ) {
			$tax[] = array( 'taxonomy' => 'product_cat', 'terms' => array_slice( array_keys( $scored ), 0, 2 ) );
		}
		if ( $tags ) {
			$tax[] = array( 'taxonomy' => 'product_tag', 'field' => 'name', 'terms' => array( $tags[0]['name'] ) );
		}
		$more = get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => 60,
				'fields'      => 'ids',
				'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'tax_query'   => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$ids  = array_slice( array_values( array_unique( array_merge( $ids, array_map( 'intval', $more ) ) ) ), 0, 60 );
	}
	$total = count( $ids );

	// No category name matched: suggest the menu categories the products are in.
	if ( ! $scored && $ids ) {
		$found = wp_get_object_terms( array_slice( $ids, 0, 30 ), 'product_cat', array( 'fields' => 'all_with_object_id' ) );
		foreach ( is_wp_error( $found ) ? array() : $found as $t ) {
			if ( isset( $index[ $t->term_id ] ) && $index[ $t->term_id ]['in_menu'] ) {
				$scored[ $t->term_id ] = ( $scored[ $t->term_id ] ?? 0 ) + 1;
			}
		}
		// Ties: the bigger category, then by name (the browser search does the same).
		uksort(
			$scored,
			static function ( $a, $b ) use ( $scored, $index ) {
				return $scored[ $b ] <=> $scored[ $a ] ?: $index[ $b ]['count'] <=> $index[ $a ]['count'] ?: strcmp( $index[ $a ]['name'], $index[ $b ]['name'] );
			}
		);
	} else {
		arsort( $scored );
	}
	$categories = array();
	foreach ( array_slice( array_keys( $scored ), 0, 6 ) as $id ) {
		$c            = $index[ $id ];
		$categories[] = array(
			'name'   => $c['name'],
			'parent' => $c['parent'] && isset( $index[ $c['parent'] ] ) ? $index[ $c['parent'] ]['name'] : '',
			'url'    => $c['url'],
			'count'  => $c['count'],
			'image'  => $c['image'],
		);
	}

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
			'price' => html_entity_decode( wp_strip_all_tags( efb_from_price_html( $id ) ), ENT_QUOTES, 'UTF-8' ),
		);
	}

	return array(
		'categories' => $categories,
		'tags'       => $tags,
		'products'   => $products,
		'total'      => $total,
		'popular'    => $categories || $products ? array() : efb_search_popular(),
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
	<form class="efb-search <?php echo esc_attr( $args['className'] ); ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-endpoint="<?php echo esc_url( rest_url( 'efb/v1/search' ) ); ?>" data-index="<?php echo esc_url( efb_search_index_url() ); ?>" data-contact="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">
		<button class="efb-search__close" type="button" aria-label="<?php esc_attr_e( 'Close search', 'ecofridge-blocks' ); ?>"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg></button>
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
