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
				. ' <span class="ef-count">(' . (int) $t->count . ')</span></li>';
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
 * On mobile/tablet: the sidebar filters become inline dropdown buttons (see CSS).
 * The JS only toggles the panels; it loads only on shop archives.
 */
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

	// Dropdown toggle on mobile.
	function closeAll(){ document.querySelectorAll('#secondary .widget.ef-open').forEach(function(o){ o.classList.remove('ef-open'); }); }
	document.querySelectorAll('#secondary .widget-title, #secondary .widgettitle').forEach(function(t){
		t.addEventListener('click', function(e){
			if(!isMobile()){ return; }
			var w = t.closest('.widget'); if(!w){ return; }
			var open = w.classList.contains('ef-open');
			closeAll();
			if(!open){ w.classList.add('ef-open'); }
			e.stopPropagation();
		});
	});
	document.addEventListener('click', function(ev){
		if(ev.target.closest('#secondary .widget')){ return; }
		closeAll();
	});
})();
</script>
	<?php
}, 25 );
