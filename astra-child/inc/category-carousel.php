<?php
/**
 * Carousel de sub-categorii pe paginile „Commercial Fridge" și „Commercial Freezer".
 *
 * Structură (după descrierea scurtă, înainte de grila de produse):
 *   - „nori" (pastile text, fără iconiță) cu categoriile tier-2 din meniu:
 *     Commercial Fridge → Display Fridge, Storage Fridge;
 *     Commercial Freezer → Display Freezer, Storage Freezer.
 *   - carousel cu sub-categoriile lor (descendenții din dropdown), NU cu cele două de sus.
 *
 * Datele vin din meniul WP autoritar (#342), deci rămân sincronizate cu meniul.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Împarte dropdown-ul de meniu al unei categorii-părinte în:
 *   - 'chips'  = copiii direcți (tier-2: Display/Storage Fridge…), doar titlu + link
 *   - 'cards'  = descendenții acelor copii (tier-3/4), cu imagine + titlu + link
 */
/** ID-ul meniului comercial (locația `secondary_menu`, fallback `ast-hf-menu-2`, apoi nume). */
function ef_commercial_menu_id() {
	$loc = get_nav_menu_locations();
	foreach ( array( 'secondary_menu', 'ast-hf-menu-2' ) as $l ) {
		if ( ! empty( $loc[ $l ] ) ) { return (int) $loc[ $l ]; }
	}
	$m = wp_get_nav_menu_object( 'Commercial Menu' );
	return $m ? (int) $m->term_id : 0;
}

/** Categoriile-copil directe (termeni) ale unei categorii, din meniul comercial. */
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
		$collect_cards( (int) $it->ID ); // descendenții acestui copil direct → carduri
	}

	return array( 'chips' => $chips, 'cards' => $cards );
}

/** Randează norii + carouselul, după descrierea scurtă, înainte de grila de produse. */
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

/** JS de navigare a carouselului (doar pe cele două arhive). */
add_action( 'wp_footer', function () {
	if ( ! is_product_category() ) { return; }
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) || ! in_array( $term->slug, array( 'commercial-fridge', 'commercial-freezer' ), true ) ) { return; }
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
