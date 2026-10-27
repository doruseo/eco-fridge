<?php
/**
 * Category carousels, after the short description and before the product grid.
 *
 * Big categories (Display Cabinets, Storage Cabinets, Commercial Fridge,
 * Commercial Freezer):
 *   1. "Shop by category": their two types as wide banner cards
 *      (Display Fridge / Display Freezer, Display Fridge / Storage Fridge…);
 *   2. "Subcategories": a carousel with every category under them in the
 *      Commercial menu (the types themselves excluded);
 *   3. the product grid, listing products only.
 * Other commercial categories with subcategories (Display Fridge, Drinks
 * Fridge…): a "Shop by category" carousel of their direct subcategories, then
 * products only.
 * /shop: Commercial Fridge / Freezer banners, "Categories" (the main parent
 * categories), "More ranges" (other top-level categories), then products only.
 *
 * Every image is shown whole (fitted into its box, never cropped).
 * The data comes from the authoritative Commercial menu, so it stays in sync with it.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The commercial menu ID (`secondary_menu` location, fallback `ast-hf-menu-2`, then name). */
function ef_commercial_menu_id() {
	$loc = get_nav_menu_locations();
	foreach ( array( 'secondary_menu', 'ast-hf-menu-2' ) as $l ) {
		if ( ! empty( $loc[ $l ] ) ) { return (int) $loc[ $l ]; }
	}
	$m = wp_get_nav_menu_object( 'Commercial Menu' );
	return $m ? (int) $m->term_id : 0;
}

/**
 * The Commercial menu's items grouped by parent item, plus the item of each
 * product category (by slug). Built once per request.
 *
 * @return array{children:array<int,WP_Post[]>,by_slug:array<string,int>}
 */
function ef_commercial_menu_tree() {
	static $tree = null;
	if ( null !== $tree ) { return $tree; }
	$tree  = array( 'children' => array(), 'by_slug' => array() );
	$mid   = ef_commercial_menu_id();
	$items = $mid ? wp_get_nav_menu_items( $mid ) : array();
	foreach ( (array) $items as $it ) {
		$tree['children'][ (int) $it->menu_item_parent ][] = $it;
		if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
			$t = get_term( (int) $it->object_id, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) && ! isset( $tree['by_slug'][ $t->slug ] ) ) {
				$tree['by_slug'][ $t->slug ] = (int) $it->ID;
			}
		}
	}
	return $tree;
}

/** A category's direct child categories (terms), from the commercial menu. */
function ef_commercial_menu_children( $parent_slug ) {
	$tree = ef_commercial_menu_tree();
	$out  = array();
	if ( empty( $tree['by_slug'][ $parent_slug ] ) ) { return $out; }
	foreach ( $tree['children'][ $tree['by_slug'][ $parent_slug ] ] ?? array() as $it ) {
		if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
			$t = get_term( (int) $it->object_id, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) ) { $out[] = $t; }
		}
	}
	return $out;
}

/** A card (title, link, whole image) for a category. */
function ef_category_card( $term_id, $title, $img = '' ) {
	$t = get_term( (int) $term_id, 'product_cat' );
	if ( ! $t || is_wp_error( $t ) ) { return null; }
	$link = get_term_link( $t );
	if ( '' === $img && function_exists( 'ef_term_image_url' ) ) {
		// Uncropped size: the card shows the whole image.
		$img = ef_term_image_url( $t->term_id, 'woocommerce_single' );
	}
	return array(
		'title' => $title,
		'link'  => is_wp_error( $link ) ? '#' : $link,
		'img'   => $img,
	);
}

/**
 * Cards for every product category under a category in the Commercial menu,
 * in menu order, skipping the given slugs (the type categories).
 *
 * @param string   $parent_slug Category slug.
 * @param string[] $skip        Slugs left out (their own descendants are kept).
 * @return array[]
 */
function ef_menu_subcategory_cards( $parent_slug, $skip = array() ) {
	$tree  = ef_commercial_menu_tree();
	$cards = array();
	if ( empty( $tree['by_slug'][ $parent_slug ] ) ) { return $cards; }
	$collect = function ( $item_id ) use ( &$collect, &$cards, $tree, $skip ) {
		foreach ( $tree['children'][ $item_id ] ?? array() as $it ) {
			if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
				$t = get_term( (int) $it->object_id, 'product_cat' );
				if ( $t && ! is_wp_error( $t ) && ! in_array( $t->slug, $skip, true ) ) {
					$card = ef_category_card( $t->term_id, wp_strip_all_tags( $it->title ) );
					if ( $card ) { $cards[] = $card; }
				}
			}
			$collect( (int) $it->ID );
		}
	};
	$collect( $tree['by_slug'][ $parent_slug ] );
	return $cards;
}

/**
 * Big categories => their two type categories, as wide banner cards.
 *
 * Labels are spelled out because the term names carry SEO suffixes that read
 * badly on a card ("Display Fridge - Commercial Display Fridge"). Each card names
 * its own banner (paths relative to the uploads folder, so they survive the move
 * off localhost).
 */
function ef_category_types_map() {
	$display_fridge   = array( 'label' => 'Display Fridge', 'img' => '2026/07/display-fridges.jpg' );
	$display_freezer  = array( 'label' => 'Display Freezer', 'img' => 'Display-Freezer.jpg' );
	$storage_fridge   = array( 'label' => 'Storage Fridge', 'img' => '2026/07/Commercial-Storage-Fridges.jpg' );
	$storage_freezer  = array( 'label' => 'Storage Freezer', 'img' => 'upright-storage-freezer.jpg' );
	return array(
		'display-cabinets'   => array( 'commercial-display-fridge' => $display_fridge, 'display-freezer' => $display_freezer ),
		'storage-cabinets'   => array( 'storage-fridge' => $storage_fridge, 'storage-freezer' => $storage_freezer ),
		'commercial-fridge'  => array( 'commercial-display-fridge' => $display_fridge, 'storage-fridge' => $storage_fridge ),
		'commercial-freezer' => array( 'display-freezer' => $display_freezer, 'storage-freezer' => $storage_freezer ),
	);
}

/** Banner cards for one big category's types, in the given order. */
function ef_category_type_cards( $wanted ) {
	$base  = trailingslashit( wp_upload_dir()['baseurl'] );
	$cards = array();
	foreach ( $wanted as $slug => $card ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $t || is_wp_error( $t ) ) { continue; }
		$c = ef_category_card( $t->term_id, $card['label'], empty( $card['img'] ) ? '' : $base . ltrim( $card['img'], '/' ) );
		if ( $c ) { $cards[] = $c; }
	}
	return $cards;
}

/**
 * What a category archive shows above its grid. Null when it gets no carousel.
 *
 * @param WP_Term $term Category.
 * @return array{types:array[],cards:array[],cards_heading:string}|null
 */
function ef_category_carousel_data( $term ) {
	static $cache = array();
	if ( array_key_exists( $term->term_id, $cache ) ) { return $cache[ $term->term_id ]; }

	$map  = ef_category_types_map();
	$data = null;
	if ( isset( $map[ $term->slug ] ) ) {
		$types = ef_category_type_cards( $map[ $term->slug ] );
		$cards = ef_menu_subcategory_cards( $term->slug, array_keys( $map[ $term->slug ] ) );
		if ( $types || $cards ) {
			$data = array( 'types' => $types, 'cards' => $cards, 'cards_heading' => 'Subcategories' );
		}
	} elseif ( function_exists( 'efb_landing_applies' ) && efb_landing_applies( $term->term_id ) ) {
		// Subcategories from the Commercial menu, then real child categories the menu
		// places elsewhere (e.g. Meat Fridge sits under Storage Fridge in the menu but
		// is a child of Upright Storage Fridge), so none is left out of the carousel.
		$children = efb_landing_children( $term->term_id );
		$real     = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => true, 'fields' => 'ids' ) );
		$children = array_values( array_unique( array_merge( $children, is_wp_error( $real ) ? array() : array_map( 'intval', $real ) ) ) );
		$cards    = array();
		foreach ( $children as $child_id ) {
			$card = ef_category_card( $child_id, efb_landing_label( $child_id ) );
			if ( $card ) { $cards[] = $card; }
		}
		if ( $cards ) {
			$data = array( 'types' => array(), 'cards' => $cards, 'cards_heading' => 'Shop by category' );
		}
	}
	$cache[ $term->term_id ] = $data;
	return $data;
}

/**
 * width/height + srcset/sizes for a card image given by URL, so the browser picks
 * a file close to the card's size (the cards used the 800px or full-size file even
 * on phones). Returns '' when the URL is not a Media Library file.
 *
 * @param string     $url     Image URL (original or a generated size).
 * @param bool|int[] $two_col true = the wide two-banner variant (width-bound); an
 *                            array( width, height ) = a box the whole image is fitted
 *                            into on every screen (e.g. the mega-menu tiles);
 *                            false = a carousel card.
 */
function ef_cc_img_attrs( $url, $two_col = false ) {
	$key = 'ef_cc_img_' . md5( $url . wp_json_encode( $two_col ) );
	$out = wp_cache_get( $key, 'eco-fridge' );
	if ( false !== $out ) { return $out; }

	$out  = '';
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$w    = 0;
	$h    = 0;
	if ( preg_match( '~-(\d{2,4})x(\d{2,4})(\.\w+)$~', $path, $m ) ) {
		$w   = (int) $m[1];
		$h   = (int) $m[2];
		$id  = attachment_url_to_postid( preg_replace( '~-\d{2,4}x\d{2,4}(\.\w+)$~', '$1', $url ) );
	} else {
		$id = attachment_url_to_postid( $url );
	}
	$meta = $id ? wp_get_attachment_metadata( $id ) : false;
	if ( $meta && ! empty( $meta['width'] ) ) {
		if ( ! $w ) {
			$w = (int) $meta['width'];
			$h = (int) $meta['height'];
		}
		$out    = ' width="' . $w . '" height="' . $h . '"';
		if ( is_array( $two_col ) ) {
			$sizes = (int) ceil( min( $two_col[0], $two_col[1] * $w / max( 1, $h ) ) ) . 'px';
		} elseif ( $two_col ) {
			$sizes = '(max-width: 640px) 100vw, 610px';
		} else {
			// A card fits the whole image in a fixed box (~350x208 on desktop, ~260x188
			// on phones), so tall product shots are shown much narrower than the card.
			$ratio = $w / max( 1, $h );
			$sizes = '(max-width: 520px) ' . (int) ceil( min( 260, 188 * $ratio ) ) . 'px, ' . (int) ceil( min( 350, 208 * $ratio ) ) . 'px';
		}
		$srcset = wp_calculate_image_srcset( array( $w, $h ), $url, $meta, $id );
		if ( $srcset ) {
			$out .= ' srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $sizes ) . '"';
		}
	}
	wp_cache_set( $key, $out, 'eco-fridge', DAY_IN_SECONDS );
	return $out;
}

/**
 * Prints one carousel of cards.
 *
 * @param array[]         $cards   Cards (title, link, img; optional meta = a sub-line under the title).
 * @param string|string[] $variant Modifier(s): '2col' (two banners), 'wide' (2:1 banner box), 'ranges' (home page ranges).
 */
function ef_render_category_carousel( $cards, $variant = '' ) {
	$classes = 'ef-cat-carousel';
	foreach ( array_filter( (array) $variant ) as $v ) {
		$classes .= ' ef-cat-carousel--' . sanitize_html_class( $v );
	}
	// On category pages and /shop the first carousel sits in the first screen (often
	// the LCP image on mobile): its first cards load eagerly, the first one with high priority.
	static $carousel_no = 0;
	$carousel_no++;
	$above_fold = 1 === $carousel_no && ( ef_is_shop_page() || ( function_exists( 'is_product_category' ) && is_product_category() ) );
	$two_col = in_array( '2col', (array) $variant, true );

	echo '<div class="' . esc_attr( $classes ) . '">';
	echo '<button type="button" class="ef-cc-arrow ef-cc-prev" aria-label="Previous">&lsaquo;</button>';
	echo '<div class="ef-cc-track" role="list">';
	foreach ( array_values( $cards ) as $i => $c ) {
		$load = ( $above_fold && $i < 3 ) ? ' loading="eager"' . ( 0 === $i ? ' fetchpriority="high"' : '' ) : ' loading="lazy"';
		echo '<a class="ef-cc-card" role="listitem" href="' . esc_url( $c['link'] ) . '">';
		echo '<span class="ef-cc-img">' . ( $c['img'] ? '<img src="' . esc_url( $c['img'] ) . '"' . ef_cc_img_attrs( $c['img'], $two_col ) . ' alt="' . esc_attr( $c['title'] ) . '"' . $load . ' decoding="async" />' : '' ) . '</span>';
		if ( isset( $c['meta'] ) ) {
			// footer with a sub-line (e.g. the number of models) and a "go" arrow
			echo '<span class="ef-cc-foot"><span class="ef-cc-text">';
			echo '<span class="ef-cc-name">' . esc_html( $c['title'] ) . '</span>';
			echo '<span class="ef-cc-meta">' . esc_html( $c['meta'] ) . '</span>';
			echo '</span><span class="ef-cc-go" aria-hidden="true"></span></span>';
		} else {
			echo '<span class="ef-cc-name">' . esc_html( $c['title'] ) . '</span>';
		}
		echo '</a>';
	}
	echo '</div>';
	echo '<button type="button" class="ef-cc-arrow ef-cc-next" aria-label="Next">&rsaquo;</button>';
	echo '</div>';
}

/**
 * Categories with a carousel list only products in the grid: the subcategories
 * are in the carousel, not mixed in among the products.
 */
add_action( 'wp', function () {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) { return; }
	$term = get_queried_object();
	if ( $term && ! is_wp_error( $term ) && ef_category_carousel_data( $term ) ) {
		remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories' );
	}
} );

/** Types + subcategories carousels on category archives. */
add_action( 'woocommerce_archive_description', function () {
	if ( ! is_product_category() ) { return; }
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) { return; }
	$data = ef_category_carousel_data( $term );
	if ( ! $data ) { return; }

	echo '<section class="ef-cat-carousel-wrap">';
	if ( $data['types'] ) {
		echo '<h2 class="ef-cc-heading">Shop by category</h2>';
		ef_render_category_carousel( $data['types'], '2col' );
	}
	if ( $data['cards'] ) {
		echo '<h2 class="ef-cc-heading">' . esc_html( $data['cards_heading'] ) . '</h2>';
		ef_render_category_carousel( $data['cards'] );
	}
	echo '</section>';
}, 15 );

/**
 * /shop page — same format as the big categories:
 *   1. "Shop by category": Commercial Fridge and Commercial Freezer as wide banners;
 *   2. "Categories": the main parent categories — the types under Commercial
 *      Fridge / Freezer (Display Fridge, Storage Fridge…), then the other main
 *      categories of the green menu bar (Display Cabinets, Storage Cabinets…);
 *   3. "More ranges": the other top-level categories with products that the menu
 *      doesn't reach (legacy ranges such as Deli displays), so no link is lost;
 *   4. the product grid, listing products only.
 */
function ef_shop_type_cards() {
	return ef_category_type_cards(
		array(
			'commercial-fridge'  => array( 'label' => 'Commercial Fridge', 'img' => 'commercial-fridge.jpg' ),
			'commercial-freezer' => array( 'label' => 'Commercial Freezer', 'img' => 'commercial-freezer.jpg' ),
		)
	);
}

function ef_shop_carousel_cards() {
	$tree  = ef_commercial_menu_tree();
	$roots = array( 'commercial-fridge', 'commercial-freezer' );
	$first = array();
	$rest  = array();
	foreach ( $tree['children'][0] ?? array() as $it ) {
		if ( 'taxonomy' !== $it->type || 'product_cat' !== $it->object ) { continue; }
		$t = get_term( (int) $it->object_id, 'product_cat' );
		if ( ! $t || is_wp_error( $t ) ) { continue; }
		if ( in_array( $t->slug, $roots, true ) ) {
			// Their direct children in the menu: the parent types (Display Fridge…).
			foreach ( $tree['children'][ (int) $it->ID ] ?? array() as $child ) {
				if ( 'taxonomy' === $child->type && 'product_cat' === $child->object ) {
					$card = ef_category_card( (int) $child->object_id, wp_strip_all_tags( $child->title ) );
					if ( $card ) { $first[] = $card; }
				}
			}
			continue;
		}
		$card = ef_category_card( $t->term_id, wp_strip_all_tags( $it->title ) );
		if ( $card ) { $rest[] = $card; }
	}
	return array_merge( $first, $rest );
}

/** Top-level categories with products that the Commercial menu doesn't link to. */
function ef_shop_more_cards() {
	$in_menu = array();
	foreach ( ef_commercial_menu_tree()['children'] as $items ) {
		foreach ( $items as $it ) {
			if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) { $in_menu[] = (int) $it->object_id; }
		}
	}
	$skip  = (array) apply_filters( 'ef_shop_more_skip', array( 'uncategorized' ) );
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true ) );
	$cards = array();
	foreach ( is_wp_error( $terms ) ? array() : ef_sort_terms_by_order( $terms ) as $t ) {
		if ( in_array( (int) $t->term_id, $in_menu, true ) || in_array( $t->slug, $skip, true ) ) { continue; }
		$card = ef_category_card( $t->term_id, $t->name );
		if ( $card ) { $cards[] = $card; }
	}
	return $cards;
}

/** Whether this request is the /shop page itself (not a category archive). */
function ef_is_shop_page() {
	return function_exists( 'is_shop' ) && is_shop() && ! ( function_exists( 'is_product_category' ) && is_product_category() ) && ! is_search();
}

// /shop lists only products in its grid: the categories are in the carousels.
add_action( 'wp', function () {
	if ( ef_is_shop_page() ) {
		remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories' );
	}
} );

add_action( 'woocommerce_archive_description', function () {
	// Filtered /shop/ = search results from the filter bar: straight to the products.
	if ( ! ef_is_shop_page() || ef_pf_is_filtered() ) { return; }

	$types = ef_shop_type_cards();
	$cards = ef_shop_carousel_cards();
	$more  = ef_shop_more_cards();
	if ( ! $types && ! $cards && ! $more ) { return; }

	echo '<section class="ef-cat-carousel-wrap">';
	if ( $types ) {
		echo '<h2 class="ef-cc-heading">Shop by category</h2>';
		// Taller banner box: these two overview images are about 2:1.
		ef_render_category_carousel( $types, array( '2col', 'wide' ) );
	}
	if ( $cards ) {
		echo '<h2 class="ef-cc-heading">Categories</h2>';
		ef_render_category_carousel( $cards );
	}
	if ( $more ) {
		echo '<h2 class="ef-cc-heading">More ranges</h2>';
		ef_render_category_carousel( $more );
	}
	echo '</section>';
}, 15 );

/**
 * [ef_home_categories] — the four main ranges as a carousel (home page):
 * Commercial Fridges, Commercial Freezers, Display Cabinets, Storage Cabinets.
 * Each card shows the category's own image, whole.
 */
add_shortcode( 'ef_home_categories', function () {
	$cards = array();
	foreach ( array(
		'commercial-fridge'  => 'Commercial Fridges',
		'commercial-freezer' => 'Commercial Freezers',
		'display-cabinets'   => 'Display Cabinets',
		'storage-cabinets'   => 'Storage Cabinets',
	) as $slug => $title ) {
		$t    = get_term_by( 'slug', $slug, 'product_cat' );
		$card = $t ? ef_category_card( $t->term_id, $title ) : null;
		if ( $card ) { $cards[] = $card; }
	}
	if ( ! $cards ) { return ''; }
	$GLOBALS['ef_cc_used'] = true; // the footer prints the carousel script
	ob_start();
	echo '<div class="ef-cat-carousel-wrap ef-home-cats">';
	ef_render_category_carousel( $cards );
	echo '</div>';
	return ob_get_clean();
} );

/** Carousel navigation JS (category archives with a carousel, /shop and [ef_home_categories]). */
add_action( 'wp_footer', function () {
	$ok = false;
	if ( ef_is_shop_page() || ! empty( $GLOBALS['ef_cc_used'] ) ) {
		$ok = true;
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		$ok   = $term && ! is_wp_error( $term ) && ef_category_carousel_data( $term );
	}
	if ( ! $ok ) { return; }
	?>
<script>
(function(){
	document.querySelectorAll('.ef-cat-carousel').forEach(function(cc){
		var track = cc.querySelector('.ef-cc-track');
		var prev = cc.querySelector('.ef-cc-prev'), next = cc.querySelector('.ef-cc-next');
		if(!track){ return; }
		function step(){
			var card = track.querySelector('.ef-cc-card, .ef-cc-slide');
			var gap = parseInt(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 16, 10) || 16;
			return card ? (card.offsetWidth + gap) : 260;
		}
		function update(){
			var overflow = track.scrollWidth > track.clientWidth + 2;
			cc.classList.toggle('is-static', !overflow);
			if(prev){ prev.disabled = track.scrollLeft <= 8; }   // scroll-snap may rest a few px in
			if(next){ next.disabled = (track.scrollLeft + track.clientWidth) >= (track.scrollWidth - 8); }
		}
		if(prev){ prev.addEventListener('click', function(){ track.scrollBy({left: -step(), behavior:'smooth'}); }); }
		if(next){ next.addEventListener('click', function(){ track.scrollBy({left: step(), behavior:'smooth'}); }); }
		track.addEventListener('scroll', update, {passive:true});
		window.addEventListener('resize', update);
		update();
	});
})();
</script>
	<?php
}, 22 );
