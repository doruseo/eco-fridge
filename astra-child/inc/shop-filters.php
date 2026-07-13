<?php
/**
 * Filtre sidebar pentru arhivele de categorii de produse.
 *
 * Widget custom „Product Subcategories (contextual)" — listează sub-categoriile
 * CATEGORIEI CURENTE (nu global). Celelalte filtre (preț, lățime, înălțime) sunt
 * widget-uri native WooCommerce (Price Filter + Layered Nav pe `pa_width`/`pa_height`),
 * contextuale automat la categoria curentă. Toate stau în sidebar-ul Astra
 * „astra-woo-shop-sidebar" (apare doar pe magazin/arhive), în stânga.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Widget: sub-categoriile categoriei de produs curente (contextual).
 * - pe o categorie cu copii → listează copiii;
 * - pe o categorie fără copii → listează categoriile-surori (copiii părintelui);
 * - pe shop → categoriile top-level.
 */
class EF_Subcat_Filter_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'ef_subcat_filter',
			'Filtru: Sub-categorii (contextual)',
			array( 'description' => 'Sub-categoriile categoriei curente de produse (aferente contextului, nu global).' )
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

		// categorie-frunză → arată surorile (copiii părintelui), ca filtrul să nu fie gol
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
 * Pe mobil/tabletă: filtrele din sidebar devin butoane-dropdown inline (vezi CSS).
 * JS-ul face doar toggle-ul panourilor; se încarcă doar pe arhivele de magazin.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_product_category' ) ) { return; }
	if ( ! ( is_product_category() || is_shop() || is_product_taxonomy() ) ) { return; }
	?>
<script>
(function(){
	function isMobile(){ return window.matchMedia('(max-width: 921px)').matches; }
	var titles = document.querySelectorAll('#secondary .widget-title, #secondary .widgettitle');
	if(!titles.length){ return; }
	function closeAll(){ document.querySelectorAll('#secondary .widget.ef-open').forEach(function(o){ o.classList.remove('ef-open'); }); }
	titles.forEach(function(t){
		t.addEventListener('click', function(e){
			if(!isMobile()){ return; }
			var w = t.closest('.widget'); if(!w){ return; }
			var open = w.classList.contains('ef-open');
			closeAll();
			if(!open){ w.classList.add('ef-open'); window.dispatchEvent(new Event('resize')); }
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
