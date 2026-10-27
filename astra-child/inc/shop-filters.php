<?php
/**
 * Sidebar filters for product archives: layout, panel header, mobile bar.
 *
 * The filters themselves are in inc/product-filters.php (Categories, Condition,
 * Markets, Width, Height) plus WooCommerce's own Price widget, all in the Astra
 * sidebar "astra-woo-shop-sidebar", on the left.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Filter panel header ("Filter products") with a "Clear all" link once any
 * filter is applied (facets, price, width, height…): back to the page itself.
 * Printed at the top of the shop sidebar, inside Astra's filter wrapper.
 */
add_action( 'dynamic_sidebar_before', function ( $index ) {
	if ( 'astra-woo-shop-sidebar' !== $index || ! ef_has_filter_sidebar() ) {
		return;
	}
	echo '<div class="ef-filters-head"><span class="ef-filters-head__title">'
		. '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M3 5h18l-7 8v5l-4 2v-7L3 5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>'
		. 'Filter products</span>';
	if ( ef_pf_is_filtered() ) {
		echo '<a class="ef-filters-clear" href="' . esc_url( ef_pf_context()['url'] ) . '">Clear all</a>';
	}
	echo '</div>';
} );

/**
 * Pages with the filter sidebar: product category, tag and market archives, and
 * /shop/ once filtered (the multi-filter results live there; plain /shop/ stays
 * the full-width landing with its carousels).
 */
function ef_has_filter_sidebar() {
	if ( is_admin() || ! function_exists( 'is_product_category' ) ) { return false; }
	return is_product_category() || is_tax( array( 'product_tag', 'market' ) )
		|| ( function_exists( 'ef_is_shop_page' ) && ef_is_shop_page() && ef_pf_is_filtered() );
}

/**
 * Show the filter sidebar ONLY where ef_has_filter_sidebar() says so.
 * Everywhere else — shop, cart, checkout, account, single product —
 * force a no-sidebar layout so the filter widgets never leak onto those pages
 * (and the cart/checkout get the full content width).
 */
add_filter( 'astra_page_layout', function ( $layout ) {
	if ( is_admin() ) { return $layout; }
	if ( ef_has_filter_sidebar() ) {
		return 'left-sidebar';
	}
	if ( ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
		|| ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() ) ) {
		return 'no-sidebar';
	}
	return $layout;
}, 99 );

/**
 * On mobile/tablet: the sidebar filters become inline dropdown buttons (see CSS).
 * The JS only toggles the panels; it loads only on shop archives.
 */

/**
 * Mobile: move the filter bar above the product grid as soon as the sidebar
 * has been parsed (inline, right after it), before the page is first painted –
 * moving it from the footer script shifted the whole grid (layout shift).
 */
add_action( 'astra_content_bottom', function () {
	if ( ! function_exists( 'is_product_category' ) || ! ( is_product_category() || is_shop() || is_product_taxonomy() ) ) { return; }
	?>
<script>
(function(){
	if(!window.matchMedia('(max-width: 921px)').matches){ return; }
	var sec = document.getElementById('secondary'), primary = document.getElementById('primary');
	if(!sec || !primary){ return; }
	var anchor = primary.querySelector('.woocommerce-result-count') || primary.querySelector('.woocommerce-ordering') || primary.querySelector('ul.products');
	if(anchor){ anchor.parentNode.insertBefore(sec, anchor); }
})();
</script>
	<?php
}, 1 );
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_product_category' ) ) { return; }
	if ( ! ( is_product_category() || is_shop() || is_product_taxonomy() ) ) { return; }
	?>
<script>
(function(){
	function isMobile(){ return window.matchMedia('(max-width: 921px)').matches; }
	var sec = document.getElementById('secondary');
	var primary = document.getElementById('primary');
	if(!sec || !primary){ return; }

	// On mobile: move the filter bar right BEFORE the product grid (after the description).
	// On desktop: put it back in the sidebar position (before #primary).
	function place(){
		// anchor = right after the category description (result-count) or, if missing, the grid
		var anchor = primary.querySelector('.woocommerce-result-count')
			|| primary.querySelector('.woocommerce-ordering')
			|| primary.querySelector('ul.products');
		if(isMobile()){
			if(anchor && sec.parentNode !== anchor.parentNode){ anchor.parentNode.insertBefore(sec, anchor); }
		} else if(sec.nextElementSibling !== primary){
			primary.parentNode.insertBefore(sec, primary);
		}
	}
	place();
	var rt; window.addEventListener('resize', function(){ clearTimeout(rt); rt = setTimeout(place, 150); });

	// Dropdown toggle on mobile. Delegated from the document, so it keeps working
	// when Astra re-renders or moves the widgets.
	function closeAll(except){ document.querySelectorAll('#secondary .widget.ef-open').forEach(function(o){ if(o !== except){ o.classList.remove('ef-open'); } }); }
	document.addEventListener('click', function(ev){
		var t = ev.target.closest('#secondary .widget-title, #secondary .widgettitle');
		if(t && isMobile()){
			var w = t.closest('.widget');
			closeAll(w);
			if(w){ w.classList.toggle('ef-open'); }
			return;
		}
		if(ev.target.closest('#secondary .widget')){ return; }
		closeAll(null);
	});
})();
</script>
	<?php
}, 25 );
