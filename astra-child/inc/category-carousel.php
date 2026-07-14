<?php
/**
 * Subcategory carousel on the "Commercial Fridge" and "Commercial Freezer" pages.
 *
 * Structure (after the short description, before the product grid):
 *   - "clouds" (text pills, no icon) with the tier-2 categories from the menu:
 *     Commercial Fridge → Display Fridge, Storage Fridge;
 *     Commercial Freezer → Display Freezer, Storage Freezer.
 *   - a carousel with their subcategories (dropdown descendants), NOT the two above.
 *
 * The data comes from the authoritative WP menu (#342), so it stays in sync with it.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Splits a parent category's menu dropdown into:
 *   - 'chips'  = direct children (tier-2: Display/Storage Fridge…), title + link only
 *   - 'cards'  = those children's descendants (tier-3/4), with image + title + link
 */
/** The commercial menu ID (`secondary_menu` location, fallback `ast-hf-menu-2`, then name). */
function ef_commercial_menu_id() {
	$loc = get_nav_menu_locations();
	foreach ( array( 'secondary_menu', 'ast-hf-menu-2' ) as $l ) {
		if ( ! empty( $loc[ $l ] ) ) { return (int) $loc[ $l ]; }
	}
	$m = wp_get_nav_menu_object( 'Commercial Menu' );
	return $m ? (int) $m->term_id : 0;
}

/** A category's direct child categories (terms), from the commercial menu. */
function ef_commercial_menu_children( $parent_slug ) {
	$mid   = ef_commercial_menu_id();
	$items = $mid ? wp_get_nav_menu_items( $mid ) : array();
	if ( ! $items ) { return array(); }
	$by = array(); $topid = 0;
	foreach ( $items as $it ) {
		$by[ (int) $it->menu_item_parent ][] = $it;
		if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
			$t = get_term( (int) $it->object_id, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) && $t->slug === $parent_slug ) { $topid = (int) $it->ID; }
		}
	}
	$out = array();
	if ( $topid && ! empty( $by[ $topid ] ) ) {
		foreach ( $by[ $topid ] as $it ) {
			if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
				$t = get_term( (int) $it->object_id, 'product_cat' );
				if ( $t && ! is_wp_error( $t ) ) { $out[] = $t; }
			}
		}
	}
	return $out;
}

function ef_cat_carousel_data( $parent_slug ) {
	$mid   = ef_commercial_menu_id();
	$items = $mid ? wp_get_nav_menu_items( $mid ) : array();
	if ( ! $items ) { return array( 'chips' => array(), 'cards' => array() ); }

	$by    = array();
	$topid = 0;
	foreach ( $items as $it ) {
		$by[ (int) $it->menu_item_parent ][] = $it;
		if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
			$t = get_term( (int) $it->object_id, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) && $t->slug === $parent_slug ) { $topid = (int) $it->ID; }
		}
	}
	if ( ! $topid || empty( $by[ $topid ] ) ) { return array( 'chips' => array(), 'cards' => array() ); }

	$chips = array();
	$cards = array();

	$collect_cards = function ( $pid ) use ( &$collect_cards, $by, &$cards ) {
		if ( empty( $by[ $pid ] ) ) { return; }
		foreach ( $by[ $pid ] as $it ) {
			if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
				$t = get_term( (int) $it->object_id, 'product_cat' );
				if ( $t && ! is_wp_error( $t ) ) {
					$link = get_term_link( $t );
					$cards[] = array(
						'title' => $it->title,
						'link'  => is_wp_error( $link ) ? '#' : $link,
						'img'   => function_exists( 'ef_term_image_url' ) ? ef_term_image_url( $t->term_id, 'woocommerce_thumbnail' ) : '',
					);
				}
			}
			$collect_cards( (int) $it->ID );
		}
	};

	foreach ( $by[ $topid ] as $it ) {
		if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
			$t = get_term( (int) $it->object_id, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) ) {
				$link    = get_term_link( $t );
				$chips[] = array( 'title' => $it->title, 'link' => is_wp_error( $link ) ? '#' : $link );
			}
		}
		$collect_cards( (int) $it->ID ); // this direct child's descendants → cards
	}

	return array( 'chips' => $chips, 'cards' => $cards );
}

/** Renders the clouds + the carousel, after the short description, before the product grid. */
add_action( 'woocommerce_archive_description', function () {
	if ( ! is_product_category() ) { return; }
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) { return; }
	if ( ! in_array( $term->slug, array( 'commercial-fridge', 'commercial-freezer' ), true ) ) { return; }

	$data = ef_cat_carousel_data( $term->slug );
	if ( empty( $data['chips'] ) && empty( $data['cards'] ) ) { return; }

	echo '<section class="ef-cat-carousel-wrap">';
	echo '<h2 class="ef-cc-heading">Shop by category</h2>';

	if ( ! empty( $data['chips'] ) ) {
		echo '<div class="ef-cc-chips">';
		foreach ( $data['chips'] as $ch ) {
			echo '<a class="ef-cc-chip" href="' . esc_url( $ch['link'] ) . '">' . esc_html( $ch['title'] ) . '</a>';
		}
		echo '</div>';
	}

	if ( ! empty( $data['cards'] ) ) {
		echo '<div class="ef-cat-carousel">';
		echo '<button type="button" class="ef-cc-arrow ef-cc-prev" aria-label="Previous">&lsaquo;</button>';
		echo '<div class="ef-cc-track" role="list">';
		foreach ( $data['cards'] as $c ) {
			echo '<a class="ef-cc-card" role="listitem" href="' . esc_url( $c['link'] ) . '">';
			echo '<span class="ef-cc-img">' . ( $c['img'] ? '<img src="' . esc_url( $c['img'] ) . '" alt="' . esc_attr( $c['title'] ) . '" loading="lazy" />' : '' ) . '</span>';
			echo '<span class="ef-cc-name">' . esc_html( $c['title'] ) . '</span>';
			echo '</a>';
		}
		echo '</div>';
		echo '<button type="button" class="ef-cc-arrow ef-cc-next" aria-label="Next">&rsaquo;</button>';
		echo '</div>';
	}

	echo '</section>';
}, 15 );

/**
 * /shop page: a carousel with the top product categories, in the requested order.
 * Same design as on Commercial Fridge/Freezer (cards with image + title),
 * after the short description, before the product grid. No "clouds" here.
 */
function ef_shop_carousel_cards() {
	// slug => label (labels provided explicitly, in the desired order)
	$wanted = array(
		'commercial-fridge'               => 'Commercial Fridge',
		'commercial-freezer'              => 'Commercial Freezer',
		'bakery-cabinets'                 => 'Bakery Ovens & Retarders',
		'medical'                         => 'Medical Fridges & Freezers',
		'low-temperature-chest-freezers'  => 'Low Temperature Freezers',
		'graded-units'                    => 'Used Refrigeration Cabinets',
	);
	$cards = array();
	foreach ( $wanted as $slug => $label ) {
		$t = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $t || is_wp_error( $t ) ) { continue; }
		$link = get_term_link( $t );
		$cards[] = array(
			'title' => $label,
			'link'  => is_wp_error( $link ) ? '#' : $link,
			'img'   => function_exists( 'ef_term_image_url' ) ? ef_term_image_url( $t->term_id, 'woocommerce_thumbnail' ) : '',
		);
	}
	return $cards;
}

add_action( 'woocommerce_archive_description', function () {
	if ( ! ( function_exists( 'is_shop' ) && is_shop() ) ) { return; }
	if ( function_exists( 'is_product_category' ) && is_product_category() ) { return; }

	$cards = ef_shop_carousel_cards();
	if ( empty( $cards ) ) { return; }

	echo '<section class="ef-cat-carousel-wrap">';
	echo '<h2 class="ef-cc-heading">Shop by category</h2>';
	echo '<div class="ef-cat-carousel">';
	echo '<button type="button" class="ef-cc-arrow ef-cc-prev" aria-label="Previous">&lsaquo;</button>';
	echo '<div class="ef-cc-track" role="list">';
	foreach ( $cards as $c ) {
		echo '<a class="ef-cc-card" role="listitem" href="' . esc_url( $c['link'] ) . '">';
		echo '<span class="ef-cc-img">' . ( $c['img'] ? '<img src="' . esc_url( $c['img'] ) . '" alt="' . esc_attr( $c['title'] ) . '" loading="lazy" />' : '' ) . '</span>';
		echo '<span class="ef-cc-name">' . esc_html( $c['title'] ) . '</span>';
		echo '</a>';
	}
	echo '</div>';
	echo '<button type="button" class="ef-cc-arrow ef-cc-next" aria-label="Next">&rsaquo;</button>';
	echo '</div>';
	echo '</section>';
}, 15 );

/** Carousel navigation JS (the two commercial archives + the /shop page). */
add_action( 'wp_footer', function () {
	$ok = false;
	if ( function_exists( 'is_shop' ) && is_shop() && ! ( function_exists( 'is_product_category' ) && is_product_category() ) ) {
		$ok = true;
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		$ok = ( $term && ! is_wp_error( $term ) && in_array( $term->slug, array( 'commercial-fridge', 'commercial-freezer' ), true ) );
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
			var card = track.querySelector('.ef-cc-card');
			var gap = parseInt(getComputedStyle(track).columnGap || getComputedStyle(track).gap || 16, 10) || 16;
			return card ? (card.offsetWidth + gap) : 260;
		}
		function update(){
			if(prev){ prev.disabled = track.scrollLeft <= 2; }
			if(next){ next.disabled = (track.scrollLeft + track.clientWidth) >= (track.scrollWidth - 2); }
		}
		if(prev){ prev.addEventListener('click', function(){ track.scrollBy({left: -step()*2, behavior:'smooth'}); }); }
		if(next){ next.addEventListener('click', function(){ track.scrollBy({left: step()*2, behavior:'smooth'}); }); }
		track.addEventListener('scroll', update, {passive:true});
		window.addEventListener('resize', update);
		update();
	});
})();
</script>
	<?php
}, 22 );
