<?php
/**
 * CUSTOM 2-ROW HEADER — the eco-fridge design, driven by TWO WP menus:
 *   Row 1: "Landing Pages Menu" (`primary` location) — logo | page nav (Home/Blog/
 *          About/Contact + dropdowns) + FiboSearch (right)
 *   Row 2 (green brand bar): "Commercial Menu" — category nav with MEGA MENU + icons
 *          (Display/Storage Cabinets = icon grid; Commercial Fridge/Freezer =
 *          grouped columns) | cart (right)
 *
 * The native Astra header is hidden via CSS; footer + banner stay native. Icons come
 * from the category thumbnail (fallback: first product) via `ef_term_image_url()`.
 * Both menus are editable in Appearance → Menus (internal references, not custom links).
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * A menu's items, grouped by parent.
 * @param string $which 'landing' = Landing Pages Menu (primary location); 'commercial' = Commercial Menu.
 */
function ef_hdr_menu( $which = 'landing' ) {
	static $cache = array();
	if ( isset( $cache[ $which ] ) ) { return $cache[ $which ]; }

	if ( 'commercial' === $which ) {
		// row 2 = the menu assigned to the `secondary_menu` location (Astra "Secondary Menu",
		// also present in the Header Builder) → editable when needed; fallback `ast-hf-menu-2`,
		// then by name. The icon-mega look is built from the menu hierarchy.
		$loc = get_nav_menu_locations();
		$mid = 0;
		foreach ( array( 'secondary_menu', 'ast-hf-menu-2' ) as $l ) {
			if ( ! empty( $loc[ $l ] ) ) { $mid = (int) $loc[ $l ]; break; }
		}
		if ( ! $mid ) {
			$m   = wp_get_nav_menu_object( 'Commercial Menu' );
			$mid = $m ? (int) $m->term_id : 0;
		}
	} else {
		$loc = get_nav_menu_locations();
		$mid = isset( $loc['primary'] ) ? (int) $loc['primary'] : 0;
		if ( ! $mid ) {
			$m   = wp_get_nav_menu_object( 'Landing Pages Menu' );
			$mid = $m ? (int) $m->term_id : 0;
		}
	}
	$items = $mid ? wp_get_nav_menu_items( $mid ) : array();

	$by = array();
	foreach ( (array) $items as $it ) {
		$by[ (int) $it->menu_item_parent ][] = $it;
	}
	$cache[ $which ] = array( 'by' => $by, 'top' => isset( $by[0] ) ? $by[0] : array() );
	return $cache[ $which ];
}

/** A menu item's icon: category thumbnail or product image. */
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

/** Mega tile (icon + name) from a menu item. */
function ef_hdr_tile( $it ) {
	$icon = ef_hdr_icon( $it );
	return '<a class="ef-mega-tile" href="' . esc_url( $it->url ) . '">'
		. '<span class="ef-mega-tile-img">' . ( $icon ? '<img src="' . esc_url( $icon ) . '" alt="' . esc_attr( $it->title ) . '" loading="lazy" />' : '' ) . '</span>'
		. '<span class="ef-mega-tile-name">' . esc_html( $it->title ) . '</span></a>';
}

/**
 * Builds the mega panel for a top-level category, FROM ITS MENU DESCENDANTS.
 *   - flat children (no grandchildren) → icon grid (Display / Storage Cabinets)
 *   - children with grandchildren      → grouped columns with title + icon (Commercial Fridge/Freezer),
 *                                        tier-3 = tiles, tier-4 = indented sub-links.
 * Returns array( html, type ) where type = 'ef-mega-full' | 'ef-mega-compact' | ''.
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
			if ( ! empty( $by[ $g->ID ] ) ) { // tier-4 → indented sub-links
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

/** Recursively renders a menu item with a dropdown (row 1). */
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

/** Row 1 nav — "Landing Pages Menu" (the pages). */
function ef_hdr_render_row1_nav() {
	$m = ef_hdr_menu( 'landing' );
	echo '<nav class="ef-primary-nav"><ul class="ef-menu">';
	foreach ( $m['top'] as $it ) {
		ef_hdr_render_dropdown_item( $it, $m['by'] );
	}
	echo '</ul></nav>';
}

/** Row 2 nav — "Commercial Menu" (categories), with mega menus + icons. */
function ef_hdr_render_cat_nav() {
	$m = ef_hdr_menu( 'commercial' );
	echo '<nav class="ef-cat-nav"><ul class="ef-menu">';
	foreach ( $m['top'] as $it ) {
		list( $mega, $type ) = ef_hdr_mega( $it, $m['by'] );
		$cls = $mega ? ' class="ef-has-mega menu-item-has-children ' . $type . '"' : '';
		echo '<li' . $cls . '>';
		echo '<a href="' . esc_url( $it->url ) . '">' . esc_html( $it->title ) . '</a>';
		echo $mega; // already escaped in ef_hdr_mega
		echo '</li>';
	}
	echo '</ul></nav>';
}

/** Cart info: "N items  £Total" (updated via AJAX fragments). */
function ef_hdr_cart_info() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return ''; }
	$count = (int) WC()->cart->get_cart_contents_count();
	$total = WC()->cart->get_cart_total();
	return '<span class="ef-cart-info">'
		. '<span class="ef-cart-count">' . $count . ' ' . _n( 'item', 'items', $count, 'eco-fridge-child' ) . '</span> '
		. '<span class="ef-cart-total">' . wp_kses_post( $total ) . '</span>'
		. '</span>';
}
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['span.ef-cart-info'] = ef_hdr_cart_info();
	return $fragments;
} );

/** The NATIVE Astra WP Pro cart (icon + flyout) + info (item count + total). */
function ef_hdr_astra_cart() {
	$cart = '';
	if ( class_exists( 'Astra_Woocommerce' ) && method_exists( 'Astra_Woocommerce', 'get_instance' ) ) {
		$woo = Astra_Woocommerce::get_instance();
		if ( method_exists( $woo, 'woo_mini_cart_markup' ) ) {
			$cart = $woo->woo_mini_cart_markup( 'desktop' );
		}
	}
	return $cart . ef_hdr_cart_info();
}

/** Render the custom header, before the Astra header (hidden via CSS). */
add_action( 'astra_header_before', function () {
	$home       = esc_url( home_url( '/' ) );
	$sticky_cls = ( function_exists( 'ef_header_is_sticky' ) && ! ef_header_is_sticky() ) ? ' ef-nosticky' : '';
	echo '<div class="ef-header' . $sticky_cls . '" id="ef-header">';

	// —— row 1: logo | page nav + search ——
	echo '<div class="ef-header-row ef-header-row1"><div class="ef-header-inner">';
	echo '<a class="ef-logo" href="' . $home . '" rel="home">';
	if ( has_custom_logo() ) {
		$lid = get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( $lid, 'full', false, array( 'class' => 'ef-logo-img', 'alt' => get_bloginfo( 'name' ) ) );
	} else {
		echo esc_html( get_bloginfo( 'name' ) );
	}
	echo '</a>';
	echo '<div class="ef-row1-right">';
	ef_hdr_render_row1_nav();
	if ( shortcode_exists( 'fibosearch' ) ) {
		echo '<div class="ef-header-search">' . do_shortcode( '[fibosearch]' ) . '</div>';
	}
	echo '</div>';
	// mobile/tablet actions (right): cart (moved here from row 2 via JS) + hamburger
	echo '<div class="ef-row1-actions">';
	echo '<span class="ef-row1-cart-slot"></span>';
	echo '<button class="ef-menu-toggle" aria-label="Toggle menu" aria-expanded="false"><span></span><span></span><span></span></button>';
	echo '</div>';
	echo '</div></div>';

	// —— row 2: category nav (mega) | cart (native Astra + info) ——
	echo '<div class="ef-header-row ef-header-row2"><div class="ef-header-inner">';
	ef_hdr_render_cat_nav();
	echo '<div class="ef-header-cart">' . ef_hdr_astra_cart() . '</div>';
	echo '</div></div>';

	echo '<div class="ef-nav-overlay" aria-hidden="true"></div>';
	echo '</div>'; // .ef-header
}, 5 );

/** Mobile menu toggle for the custom header. */
add_action( 'wp_footer', function () {
	?>
<script>
(function(){
	var h = document.getElementById('ef-header');
	if(!h){ return; }
	var t = h.querySelector('.ef-menu-toggle');
	var overlay = h.querySelector('.ef-nav-overlay');
	var cart = h.querySelector('.ef-header-cart');
	var slot = h.querySelector('.ef-row1-cart-slot');
	var row2inner = h.querySelector('.ef-header-row2 .ef-header-inner');

	function closeNav(){ h.classList.remove('ef-nav-open'); if(t){ t.setAttribute('aria-expanded','false'); } }
	if(t){ t.addEventListener('click', function(e){ e.stopPropagation(); var open = h.classList.toggle('ef-nav-open'); t.setAttribute('aria-expanded', open ? 'true' : 'false'); }); }
	if(overlay){ overlay.addEventListener('click', closeNav); }
	document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closeNav(); } });

	// the cart: on mobile/tablet → row 1 (next to the hamburger); on desktop → row 2 (green bar)
	function isMobile(){ return window.matchMedia('(max-width: 992px)').matches; }
	function placeCart(){
		if(!cart || !slot || !row2inner){ return; }
		if(isMobile()){ if(cart.parentNode !== slot){ slot.appendChild(cart); } }
		else if(cart.parentNode !== row2inner){ row2inner.appendChild(cart); }
	}
	placeCart();
	var rt; window.addEventListener('resize', function(){ clearTimeout(rt); rt = setTimeout(function(){ placeCart(); if(!isMobile()){ closeNav(); } }, 150); });
})();
</script>
	<?php
}, 20 );

/**
 * +/− quantity steppers in the mini-cart flyout (green bar).
 * The native WooCommerce cart has no quantity controls — we inject them and wire
 * them to the WooCommerce Store API (`cart/update-item`), then refresh the
 * fragments. The remove (×) button stays the native one (recolored via CSS).
 */
add_action( 'wp_footer', function () {
	$rest = esc_url_raw( rest_url() );
	?>
<script>
(function(){
	var REST = <?php echo wp_json_encode( $rest ); ?>;
	var storeNonce = null;

	function getNonce(cb){
		if(storeNonce){ cb(storeNonce); return; }
		fetch(REST + 'wc/store/v1/cart', { credentials: 'same-origin' })
			.then(function(r){ storeNonce = r.headers.get('Nonce') || r.headers.get('X-WC-Store-API-Nonce'); cb(storeNonce); })
			.catch(function(){ cb(null); });
	}

	function updateQty(key, qty, btns){
		btns.forEach(function(b){ b.disabled = true; });
		getNonce(function(nonce){
			var headers = { 'Content-Type': 'application/json' };
			if(nonce){ headers['Nonce'] = nonce; }
			fetch(REST + 'wc/store/v1/cart/update-item', {
				method: 'POST', credentials: 'same-origin', headers: headers,
				body: JSON.stringify({ key: key, quantity: qty })
			}).then(function(r){
				var n = r.headers.get('Nonce'); if(n){ storeNonce = n; }
				return r.json();
			}).then(function(){
				if(window.jQuery){ jQuery(document.body).trigger('wc_fragment_refresh'); }
			}).catch(function(){ btns.forEach(function(b){ b.disabled = false; }); });
		});
	}

	function inject(){
		var items = document.querySelectorAll('.ef-header-cart .woocommerce-mini-cart-item');
		items.forEach(function(li){
			if(li.querySelector('.ef-mini-qty')){ return; }
			var rem = li.querySelector('a.remove_from_cart_button');
			if(!rem){ return; }
			var key = rem.getAttribute('data-cart_item_key');
			if(!key){ return; }
			var qEl = li.querySelector('.quantity');
			var qty = 1;
			if(qEl){ var m = qEl.textContent.match(/\d+/); if(m){ qty = parseInt(m[0], 10) || 1; } }

			var wrap  = document.createElement('div'); wrap.className = 'ef-mini-qty';
			var minus = document.createElement('button'); minus.type = 'button'; minus.textContent = '−'; minus.setAttribute('aria-label', 'Decrease quantity');
			var val   = document.createElement('span'); val.className = 'ef-mini-qty-val'; val.textContent = qty;
			var plus  = document.createElement('button'); plus.type = 'button'; plus.textContent = '+'; plus.setAttribute('aria-label', 'Increase quantity');
			if(qty <= 1){ minus.disabled = true; }

			minus.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); var nq = Math.max(1, qty - 1); if(nq !== qty){ updateQty(key, nq, [minus, plus]); } });
			plus.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); updateQty(key, qty + 1, [minus, plus]); });

			wrap.appendChild(minus); wrap.appendChild(val); wrap.appendChild(plus);
			li.appendChild(wrap);
		});
	}

	if(document.readyState !== 'loading'){ inject(); }
	else { document.addEventListener('DOMContentLoaded', inject); }
	if(window.jQuery){
		jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart removed_from_cart', inject);
	}
})();
</script>
	<?php
}, 21 );
