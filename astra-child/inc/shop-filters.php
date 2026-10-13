<?php
/**
 * Sidebar filters for product category archives.
 *
 * Custom widget "Product Subcategories (contextual)" — lists the subcategories of
 * the CURRENT CATEGORY (not global). The other filters (price, width, height) are
 * native WooCommerce widgets (Price Filter + Layered Nav on `pa_width`/`pa_height`),
 * automatically contextual to the current category. All live in the Astra sidebar
 * "astra-woo-shop-sidebar" (shown only on shop/archives), on the left.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Widget: the current product category's subcategories (contextual).
 * - on a category with children → lists the children;
 * - on a category without children → lists the sibling categories (the parent's children);
 * - on the shop → the top-level categories.
 */
class EF_Subcat_Filter_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'ef_subcat_filter',
			'Filter: Subcategories (contextual)',
			array( 'description' => 'Subcategories of the current product category (context-aware, not global).' )
		);
	}

	public function widget( $args, $instance ) {
		$title   = ! empty( $instance['title'] ) ? $instance['title'] : 'Categories';
		$current = null;
		$parent  = 0;

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$current = get_queried_object();
			if ( $current && ! is_wp_error( $current ) ) { $parent = (int) $current->term_id; }
		}

		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $parent, 'hide_empty' => true ) );

		// product tag / market page → the categories its products belong to
		if ( is_tax( array( 'product_tag', 'market' ) ) && function_exists( 'efb_landing_tag_categories' ) ) {
			$ids   = efb_landing_tag_categories( get_queried_object_id(), 12 );
			$terms = $ids ? get_terms( array( 'taxonomy' => 'product_cat', 'include' => $ids, 'hide_empty' => true ) ) : array();
		}

		// no taxonomy children (e.g. Commercial Fridge/Freezer) → the child categories from
		// the COMMERCIAL MENU (secondary_menu), same as the carousel
		if ( ( is_wp_error( $terms ) || empty( $terms ) ) && $current && function_exists( 'ef_commercial_menu_children' ) ) {
			$mc = ef_commercial_menu_children( $current->slug );
			if ( $mc ) { $terms = $mc; }
		}
		// leaf category → show the siblings (the parent's children) so the filter isn't empty
		if ( ( is_wp_error( $terms ) || empty( $terms ) ) && $current && $current->parent ) {
			$parent = (int) $current->parent;
			$terms  = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $parent, 'hide_empty' => true ) );
		}
		if ( is_wp_error( $terms ) || empty( $terms ) ) { return; }

		if ( function_exists( 'ef_sort_terms_by_order' ) ) { $terms = ef_sort_terms_by_order( $terms ); }

		echo $args['before_widget']; // phpcs:ignore
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore
		echo '<ul class="ef-filter-cats">';
		foreach ( $terms as $t ) {
			$is_cur = ( $current && (int) $current->term_id === (int) $t->term_id );
			echo '<li class="' . ( $is_cur ? 'ef-current' : '' ) . '">'
				. '<a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>'
				. ' <span class="ef-count">' . (int) $t->count . '</span></li>';
		}
		echo '</ul>';
		echo $args['after_widget']; // phpcs:ignore
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : 'Categories';
		echo '<p><label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">Title:</label>'
			. '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $title ) . '"></p>';
	}

	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : '' );
		return $instance;
	}
}

add_action( 'widgets_init', function () {
	register_widget( 'EF_Subcat_Filter_Widget' );
} );

/**
 * Layered nav (Width, Height) counts as plain numbers – the CSS shows them as
 * badges, like the subcategory counts.
 */
add_filter( 'woocommerce_layered_nav_term_html', function ( $html, $term, $link, $count ) {
	$inner = '<span class="title">' . esc_html( $term->name ) . '</span> <span class="count">' . absint( $count ) . '</span>';
	return $link ? '<a rel="nofollow" href="' . esc_url( $link ) . '">' . $inner . '</a>' : '<span>' . $inner . '</span>';
}, PHP_INT_MAX, 4 );

/**
 * Filter panel header ("Filter products") with a "Clear all" link once any
 * filter is applied (price, width, height…). Printed at the top of the shop
 * sidebar, inside Astra's filter wrapper.
 */
add_action( 'dynamic_sidebar_before', function ( $index ) {
	if ( 'astra-woo-shop-sidebar' !== $index || is_admin() || ! function_exists( 'is_product_category' ) || ! ( is_product_category() || is_tax( array( 'product_tag', 'market' ) ) ) ) {
		return;
	}
	$active = false;
	foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter state.
		if ( 0 === strpos( $key, 'filter_' ) || in_array( $key, array( 'min_price', 'max_price' ), true ) ) {
			$active = true;
			break;
		}
	}
	echo '<div class="ef-filters-head"><span class="ef-filters-head__title">'
		. '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M3 5h18l-7 8v5l-4 2v-7L3 5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>'
		. 'Filter products</span>';
	if ( $active ) {
		echo '<a class="ef-filters-clear" href="' . esc_url( get_term_link( get_queried_object() ) ) . '">Clear all</a>';
	}
	echo '</div>';
} );

/**
 * Show the filter sidebar ONLY on product category, product tag and market archives.
 * Everywhere else — shop, cart, checkout, account, single product —
 * force a no-sidebar layout so the filter widgets never leak onto those pages
 * (and the cart/checkout get the full content width).
 */
add_filter( 'astra_page_layout', function ( $layout ) {
	if ( is_admin() ) { return $layout; }
	if ( function_exists( 'is_product_category' ) && ( is_product_category() || is_tax( array( 'product_tag', 'market' ) ) ) ) {
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
