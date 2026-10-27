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

	// What the visitor ticked, each one removable on its own.
	$chips = ef_pf_active_filters();
	if ( $chips ) {
		echo '<div class="ef-pf-active"><span class="ef-pf-active__label">Selected:</span>';
		foreach ( $chips as $chip ) {
			echo '<a class="ef-pf-chip" href="' . esc_url( $chip['url'] ) . '" rel="nofollow" aria-label="' . esc_attr( 'Remove filter: ' . $chip['label'] ) . '">'
				. esc_html( $chip['label'] ) . '<span class="ef-pf-chip__x" aria-hidden="true">&times;</span></a>';
		}
		echo '</div>';
	}
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
 * Mobile/tablet (≤921px): our own top bar replaces Astra Pro's bottom toolbar —
 * Astra's script rebuilds that bar with innerHTML (which drops the filters'
 * event listeners) and only on phones, so it is not loaded on product archives.
 * WooCommerce's own script still submits the sorting on change.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) && ! is_search() ) {
		wp_dequeue_script( 'astra-modern-shop-view' );
	}
}, 100 );

/**
 * The bar (Filters button + sorting) at the top of the content and the
 * "Filters" panel, built as soon as the content has been parsed (inline, before
 * the first paint, so nothing jumps): the sidebar moves into the panel, the
 * sorting form into the bar. Back on desktop, both return to their places.
 */
add_action( 'astra_content_bottom', function () {
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_taxonomy() ) || is_search() ) { return; }
	$cfg = array(
		'active' => function_exists( 'ef_pf_active_filters' ) ? count( ef_pf_active_filters() ) : 0,
		'total'  => (int) $GLOBALS['wp_query']->found_posts,
	);
	?>
<script>
(function(){
	var cfg = <?php echo wp_json_encode( $cfg ); ?>;
	var mq = window.matchMedia('(max-width: 921px)');
	var primary = document.getElementById('primary'), sec = document.getElementById('secondary');
	if(!primary){ return; }
	var order = primary.querySelector('.woocommerce-ordering');
	if(!sec && !order){ return; }
	var orderHome = order && order.parentNode, secHome = sec && sec.parentNode;

	var bar = document.createElement('div');
	bar.className = 'ef-mbar';
	bar.innerHTML = (sec ? '<button type="button" class="ef-mbar__filters" aria-controls="ef-drawer" aria-expanded="false">'
		+ '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M3 5h18l-7 8v5l-4 2v-7L3 5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>'
		+ '<span>Filters</span>' + (cfg.active ? '<span class="ef-pf-n">' + cfg.active + '</span>' : '') + '</button>' : '')
		+ '<div class="ef-mbar__sort"></div>';
	var sortSlot = bar.querySelector('.ef-mbar__sort'), btn = bar.querySelector('.ef-mbar__filters');

	var drawer = null, body = null;
	if(sec){
		drawer = document.createElement('div');
		drawer.id = 'ef-drawer';
		drawer.className = 'ef-drawer';
		drawer.hidden = true;
		drawer.innerHTML = '<div class="ef-drawer__backdrop" data-close></div>'
			+ '<div class="ef-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="ef-drawer-title">'
			+ '<div class="ef-drawer__head"><span id="ef-drawer-title">Filters</span>'
			+ '<button type="button" class="ef-drawer__close" data-close aria-label="Close filters">&times;</button></div>'
			+ '<div class="ef-drawer__body"></div>'
			+ '<div class="ef-drawer__foot"><button type="button" class="ef-drawer__done" data-close>Show '
			+ cfg.total + (1 === cfg.total ? ' product' : ' products') + '</button></div></div>';
		body = drawer.querySelector('.ef-drawer__body');
		document.body.appendChild(drawer);
	}

	// Section titles: buttons where they open and close a section (all of them in
	// the panel, the collapsible ones — Categories — on desktop).
	function titles(){
		if(!sec){ return; }
		sec.querySelectorAll('.widget').forEach(function(w){
			var t = w.querySelector(':scope > .widget-title, :scope > .widgettitle');
			if(!t){ return; }
			var on = mq.matches || !!w.querySelector(':scope > .ef-pf[data-collapsible]');
			if(on){
				t.setAttribute('role', 'button');
				t.setAttribute('tabindex', '0');
				t.setAttribute('aria-expanded', w.classList.contains('ef-open') ? 'true' : 'false');
			} else {
				t.removeAttribute('role'); t.removeAttribute('tabindex'); t.removeAttribute('aria-expanded');
			}
		});
	}

	function place(){
		if(mq.matches){
			if(!bar.parentNode){ primary.insertBefore(bar, primary.firstChild); }
			if(order && order.parentNode !== sortSlot){ sortSlot.appendChild(order); }
			if(sec && sec.parentNode !== body){
				// In the panel, the sections with a filter on start open.
				sec.querySelectorAll('.widget').forEach(function(w){
					var on = w.querySelector('.ef-pf-n') || (w.classList.contains('widget_price_filter') && /[?&](min|max)_price=/.test(location.search));
					w.classList.toggle('ef-open', !!on);
				});
				body.appendChild(sec);
			}
		} else {
			close();
			if(bar.parentNode){ bar.parentNode.removeChild(bar); }
			if(order && order.parentNode !== orderHome){ orderHome.appendChild(order); }
			if(sec && sec.parentNode !== secHome){
				secHome.insertBefore(sec, primary);
				sec.querySelectorAll('.widget').forEach(function(w){ w.classList.remove('ef-open'); });
			}
		}
		titles();
	}

	// The bar sticks right under the sticky header.
	function stick(){
		var h = document.querySelector('.ef-header');
		var top = h && 'sticky' === getComputedStyle(h).position ? h.getBoundingClientRect().height : 0;
		document.documentElement.style.setProperty('--ef-mbar-top', top + 'px');
	}

	var last = null;
	function open(){
		if(!drawer){ return; }
		last = document.activeElement;
		drawer.hidden = false;
		document.documentElement.classList.add('ef-drawer-open');
		if(btn){ btn.setAttribute('aria-expanded', 'true'); }
		drawer.querySelector('.ef-drawer__close').focus();
	}
	function close(){
		if(!drawer || drawer.hidden){ return; }
		drawer.hidden = true;
		document.documentElement.classList.remove('ef-drawer-open');
		if(btn){ btn.setAttribute('aria-expanded', 'false'); }
		if(last && last.focus){ last.focus(); }
	}

	if(btn){ btn.addEventListener('click', open); }
	if(drawer){
		drawer.addEventListener('click', function(ev){
			if(ev.target.closest('[data-close]')){ close(); }
			if(ev.target.closest('.ef-pf-chip')){ try { sessionStorage.setItem('efDrawer', '1'); } catch(e){} }
		});
		// A filter ticked in the panel reloads the page: open the panel again there,
		// so several filters can be picked in a row.
		drawer.addEventListener('change', function(ev){
			if(ev.target.closest('.ef-pf')){ try { sessionStorage.setItem('efDrawer', '1'); } catch(e){} }
		});
		drawer.addEventListener('submit', function(){ try { sessionStorage.setItem('efDrawer', '1'); } catch(e){} });
	}
	document.addEventListener('keydown', function(ev){
		if('Escape' === ev.key){ close(); return; }
		var t = ev.target.closest && ev.target.closest('#secondary [role="button"].widget-title, #secondary [role="button"].widgettitle');
		if(t && ('Enter' === ev.key || ' ' === ev.key)){ ev.preventDefault(); t.click(); }
	});
	document.addEventListener('click', function(ev){
		var t = ev.target.closest('#secondary .widget-title, #secondary .widgettitle');
		if(!t || 'button' !== t.getAttribute('role')){ return; }
		var w = t.closest('.widget');
		w.classList.toggle('ef-open');
		t.setAttribute('aria-expanded', w.classList.contains('ef-open') ? 'true' : 'false');
	});

	place();
	stick();
	mq.addEventListener('change', function(){ place(); stick(); });
	window.addEventListener('resize', stick);
	try {
		if(mq.matches && sessionStorage.getItem('efDrawer')){ open(); }
		sessionStorage.removeItem('efDrawer');
	} catch(e){}
})();
</script>
	<?php
}, 1 );
