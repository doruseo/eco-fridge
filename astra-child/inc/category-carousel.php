<?php
/**
 * Carousel de sub-categorii pe paginile de categorie „Commercial Fridge" și
 * „Commercial Freezer" — categoriile din dropdown-ul lor de meniu (meniul WP #342),
 * afișat DUPĂ descrierea scurtă și ÎNAINTE de grila de produse.
 *
 * Aceste două categorii nu au copii în taxonomie (sunt legate doar prin meniu),
 * deci carouselul oferă navigarea către sub-categoriile relevante.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Categoriile din dropdown-ul de meniu (subtree-ul din #342) al unei categorii-părinte. */
function ef_cat_carousel_items( $parent_slug ) {
	$loc = get_nav_menu_locations();
	$mid = isset( $loc['primary'] ) ? (int) $loc['primary'] : 0;
	if ( ! $mid ) {
		$m   = wp_get_nav_menu_object( 'Primary Menu' );
		$mid = $m ? (int) $m->term_id : 0;
	}
	$items = $mid ? wp_get_nav_menu_items( $mid ) : array();
	if ( ! $items ) { return array(); }

	$by = array();
	$topid = 0;
	foreach ( $items as $it ) {
		$by[ (int) $it->menu_item_parent ][] = $it;
		if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
			$t = get_term( (int) $it->object_id, 'product_cat' );
			if ( $t && ! is_wp_error( $t ) && $t->slug === $parent_slug ) { $topid = (int) $it->ID; }
		}
	}
	if ( ! $topid ) { return array(); }

	$out = array();
	$walk = function ( $pid ) use ( &$walk, $by, &$out ) {
		if ( empty( $by[ $pid ] ) ) { return; }
		foreach ( $by[ $pid ] as $it ) {
			if ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) {
				$t = get_term( (int) $it->object_id, 'product_cat' );
				if ( $t && ! is_wp_error( $t ) ) {
					$link = get_term_link( $t );
					$out[] = array(
						'title' => $it->title,
						'link'  => is_wp_error( $link ) ? '#' : $link,
						'img'   => function_exists( 'ef_term_image_url' ) ? ef_term_image_url( $t->term_id, 'woocommerce_thumbnail' ) : '',
					);
				}
			}
			$walk( (int) $it->ID );
		}
	};
	$walk( $topid );
	return $out;
}

/** Randează carouselul, după descrierea scurtă, înainte de grila de produse. */
add_action( 'woocommerce_archive_description', function () {
	if ( ! is_product_category() ) { return; }
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) { return; }
	if ( ! in_array( $term->slug, array( 'commercial-fridge', 'commercial-freezer' ), true ) ) { return; }

	$items = ef_cat_carousel_items( $term->slug );
	if ( empty( $items ) ) { return; }

	echo '<section class="ef-cat-carousel-wrap">';
	echo '<h2 class="ef-cc-heading">Shop by category</h2>';
	echo '<div class="ef-cat-carousel">';
	echo '<button type="button" class="ef-cc-arrow ef-cc-prev" aria-label="Previous">&lsaquo;</button>';
	echo '<div class="ef-cc-track" role="list">';
	foreach ( $items as $c ) {
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
