<?php
/**
 * HEADER CUSTOM PE 2 RÂNDURI — designul original eco-fridge, DAR condus 100% din
 * meniul WP autoritar (Primary Menu #342, ierarhie tier 1→4 cu referințe interne),
 * NU din interogări de taxonomie ascunse. Editabil din Appearance → Menus.
 *
 *   Rând 1: logo (stânga) | nav utilitar (Home/Blog/Contact + dropdowns) + FiboSearch (dreapta)
 *   Rând 2 (bară verde brand): nav categorii de produse cu MEGA-MENIU + iconițe (stânga) | coș (dreapta)
 *
 * Header-ul Astra nativ e ascuns prin CSS; footer + banner rămân native (Astra Builder
 * + Custom Layout). Iconițele vin din thumbnail-ul categoriei (fallback: primul produs)
 * prin `ef_term_image_url()`, iar pt. iteme-produs din imaginea produsului.
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

/** true dacă itemul de meniu țintește o categorie de produs (merge pe rândul 2). */
function ef_hdr_is_cat( $it ) {
	return 'taxonomy' === $it->type && 'product_cat' === $it->object;
}

/** Iconița unui item de meniu: thumbnail categorie sau imagine produs. */
function ef_hdr_icon( $it ) {
	if ( 'taxonomy' === $it->type ) {
		return ef_term_image_url( (int) $it->object_id );
	}
	if ( 'post_type' === $it->type && 'product' === $it->object ) {
		$u = get_the_post_thumbnail_url( (int) $it->object_id, 'woocommerce_thumbnail' );
		return $u ? $u : '';
	}
	return '';
}

/** Tile mega (iconiță + nume) dintr-un item de meniu. */
function ef_hdr_tile( $it ) {
	$icon = ef_hdr_icon( $it );
	return '<a class="ef-mega-tile" href="' . esc_url( $it->url ) . '">'
		. '<span class="ef-mega-tile-img">' . ( $icon ? '<img src="' . esc_url( $icon ) . '" alt="' . esc_attr( $it->title ) . '" loading="lazy" />' : '' ) . '</span>'
		. '<span class="ef-mega-tile-name">' . esc_html( $it->title ) . '</span></a>';
}

/**
 * Construiește panoul mega pentru o categorie top-level, DIN DESCENDENȚII EI DE MENIU.
 *   - copii plați (fără nepoți) → grilă cu iconițe (Display / Storage Cabinets)
 *   - copii cu nepoți           → coloane grupate cu titlu + iconiță (Commercial Fridge/Freezer),
 *                                 tier-3 = tile-uri, tier-4 = sub-linkuri indentate.
 * Întoarce array( html, type ) unde type = 'ef-mega-full' | 'ef-mega-compact' | ''.
 */
function ef_hdr_mega( $top, $by ) {
	$children = isset( $by[ $top->ID ] ) ? $by[ $top->ID ] : array();
	if ( ! $children ) { return array( '', '' ); }

	$grouped = false;
	foreach ( $children as $c ) {
		if ( ! empty( $by[ $c->ID ] ) ) { $grouped = true; break; }
	}

	if ( ! $grouped ) {
		$tiles = '';
		foreach ( $children as $c ) { $tiles .= ef_hdr_tile( $c ); }
		return array( '<div class="ef-mega ef-mega-grid">' . $tiles . '</div>', 'ef-mega-full' );
	}

	$out = '<div class="ef-mega ef-mega-groups">';
	foreach ( $children as $c ) {
		$icon = ef_hdr_icon( $c );
		$out .= '<div class="ef-mega-col">';
		$out .= '<a class="ef-mega-head" href="' . esc_url( $c->url ) . '">';
		if ( $icon ) { $out .= '<span class="ef-mega-head-img"><img src="' . esc_url( $icon ) . '" alt="" loading="lazy" /></span>'; }
		$out .= '<span class="ef-mega-head-txt">' . esc_html( $c->title ) . '</span>';
		$out .= '</a>';

		$grandkids = isset( $by[ $c->ID ] ) ? $by[ $c->ID ] : array();
		$out .= '<div class="ef-mega-col-items">';
		foreach ( $grandkids as $g ) {
			$out .= ef_hdr_tile( $g );
			if ( ! empty( $by[ $g->ID ] ) ) { // tier-4 → sub-linkuri indentate
				$out .= '<div class="ef-mega-subitems">';
				foreach ( $by[ $g->ID ] as $gg ) {
					$out .= '<a class="ef-mega-subitem" href="' . esc_url( $gg->url ) . '">' . esc_html( $gg->title ) . '</a>';
				}
				$out .= '</div>';
			}
		}
		$out .= '</div></div>';
	}
	$out .= '</div>';
	return array( $out, 'ef-mega-compact' );
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

/** Nav rând 2 — categoriile de produse, cu mega-meniuri + iconițe. */
function ef_hdr_render_cat_nav() {
	$m = ef_hdr_menu();
	echo '<nav class="ef-cat-nav"><ul class="ef-menu">';
	foreach ( $m['top'] as $it ) {
		if ( ! ef_hdr_is_cat( $it ) ) { continue; }
		list( $mega, $type ) = ef_hdr_mega( $it, $m['by'] );
		$cls = $mega ? ' class="ef-has-mega menu-item-has-children ' . $type . '"' : '';
		echo '<li' . $cls . '>';
		echo '<a href="' . esc_url( $it->url ) . '">' . esc_html( $it->title ) . '</a>';
		echo $mega; // deja escapat în ef_hdr_mega
		echo '</li>';
	}
	echo '</ul></nav>';
}

/** Link coș (server-side; actualizat prin fragmente AJAX la add-to-cart). */
function ef_cart_link() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return ''; }
	$count = WC()->cart->get_cart_contents_count();
	$total = WC()->cart->get_cart_total();
	return '<a class="ef-cart" href="' . esc_url( wc_get_cart_url() ) . '">'
		. '<span class="ef-cart-ico" aria-hidden="true"></span>'
		. '<span class="ef-cart-txt"><span class="ef-cart-count">' . (int) $count . ' items</span> <span class="ef-cart-total">' . wp_kses_post( $total ) . '</span></span>'
		. '</a>';
}
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['a.ef-cart'] = ef_cart_link();
	return $fragments;
} );

/** Randare header custom, înainte de header-ul Astra (ascuns prin CSS). */
add_action( 'astra_header_before', function () {
	$home = esc_url( home_url( '/' ) );
	echo '<div class="ef-header" id="ef-header">';

	// —— rând 1: logo | nav utilitar + search ——
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

	// —— rând 2: nav categorii (mega) | coș ——
	echo '<div class="ef-header-row ef-header-row2"><div class="ef-header-inner">';
	ef_hdr_render_cat_nav();
	echo '<div class="ef-header-cart">' . ef_cart_link() . '</div>';
	echo '</div></div>';

	echo '</div>'; // .ef-header
}, 5 );

/** Toggle meniu mobil pentru header-ul custom. */
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
