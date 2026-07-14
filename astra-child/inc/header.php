<?php
/**
 * HEADER — RÂNDUL 1 (custom): logo (stânga) | nav utilitar (Home/Blog/Contact +
 * dropdowns) + FiboSearch (dreapta). Condus din meniul WP #342 (referințe interne).
 *
 * RÂNDUL 2 (categorii comerciale + coș) e acum NATIV Astra — în ultimul rând al
 * Header Builder-ului (Below Header), cu meniul „Commercial Menu" + coșul standard
 * Astra (vezi migration/22-below-header-menu.php). Header-ul Astra e afișat DOAR pe
 * rândul Below (primary/above ascunse prin CSS); footer/banner rămân native.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Itemele meniului principal (#342 de pe locația `primary`), grupate după părinte. */
function ef_hdr_menu() {
	static $cache = null;
	if ( null !== $cache ) { return $cache; }

	$loc = get_nav_menu_locations();
	$mid = isset( $loc['primary'] ) ? (int) $loc['primary'] : 0;
	if ( ! $mid ) {
		$m   = wp_get_nav_menu_object( 'Primary Menu' );
		$mid = $m ? (int) $m->term_id : 0;
	}
	$items = $mid ? wp_get_nav_menu_items( $mid ) : array();

	$by = array();
	foreach ( (array) $items as $it ) {
		$by[ (int) $it->menu_item_parent ][] = $it;
	}
	$cache = array( 'by' => $by, 'top' => isset( $by[0] ) ? $by[0] : array() );
	return $cache;
}

/** true dacă itemul de meniu țintește o categorie de produs (merge pe rândul 2, nativ). */
function ef_hdr_is_cat( $it ) {
	return 'taxonomy' === $it->type && 'product_cat' === $it->object;
}

/** Randează recursiv un item de meniu cu dropdown (rând 1: utilitar, non-categorii). */
function ef_hdr_render_dropdown_item( $it, $by ) {
	$kids = isset( $by[ $it->ID ] ) ? $by[ $it->ID ] : array();
	$has  = $kids ? ' menu-item-has-children' : '';
	echo '<li class="ef-menu-item' . esc_attr( $has ) . '">';
	echo '<a href="' . esc_url( $it->url ) . '">' . esc_html( $it->title ) . '</a>';
	if ( $kids ) {
		echo '<ul class="sub-menu">';
		foreach ( $kids as $k ) { ef_hdr_render_dropdown_item( $k, $by ); }
		echo '</ul>';
	}
	echo '</li>';
}

/** Nav rând 1 — itemele top-level care NU sunt categorii (Home, Blog, Contact...). */
function ef_hdr_render_row1_nav() {
	$m = ef_hdr_menu();
	echo '<nav class="ef-primary-nav"><ul class="ef-menu">';
	foreach ( $m['top'] as $it ) {
		if ( ef_hdr_is_cat( $it ) ) { continue; }
		ef_hdr_render_dropdown_item( $it, $m['by'] );
	}
	echo '</ul></nav>';
}

/** Randare rând 1 custom, înainte de header-ul Astra (care afișează doar Below Header). */
add_action( 'astra_header_before', function () {
	$home = esc_url( home_url( '/' ) );
	echo '<div class="ef-header" id="ef-header">';
	echo '<div class="ef-header-row ef-header-row1"><div class="ef-header-inner">';
	echo '<a class="ef-logo" href="' . $home . '" rel="home">';
	if ( has_custom_logo() ) {
		$lid = get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( $lid, 'full', false, array( 'class' => 'ef-logo-img', 'alt' => get_bloginfo( 'name' ) ) );
	} else {
		echo esc_html( get_bloginfo( 'name' ) );
	}
	echo '</a>';
	echo '<button class="ef-menu-toggle" aria-label="Toggle menu" aria-expanded="false"><span></span><span></span><span></span></button>';
	echo '<div class="ef-row1-right">';
	ef_hdr_render_row1_nav();
	if ( shortcode_exists( 'fibosearch' ) ) {
		echo '<div class="ef-header-search">' . do_shortcode( '[fibosearch]' ) . '</div>';
	}
	echo '</div></div></div>';
	echo '</div>'; // .ef-header
}, 5 );

/** Toggle meniu mobil pentru rândul 1. */
add_action( 'wp_footer', function () {
	?>
<script>
(function(){
	var t = document.querySelector('.ef-menu-toggle'), h = document.getElementById('ef-header');
	if(!t || !h){ return; }
	t.addEventListener('click', function(){
		var open = h.classList.toggle('ef-nav-open');
		t.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
})();
</script>
	<?php
}, 20 );
