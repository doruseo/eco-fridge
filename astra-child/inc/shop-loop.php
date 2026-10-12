<?php
/**
 * Product grids: a quantity box with − / + next to "Add to cart".
 *
 * Wraps WooCommerce's loop "Add to cart" button (simple products that can be
 * bought now) with a quantity control; the AJAX add-to-cart reads the
 * quantity from the button's data-quantity, which the script keeps in sync.
 * Astra's on-image cart icon is left alone (it adds one).
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
	if ( false !== strpos( $html, 'ast-on-card-button' )
		|| ! $product->is_type( 'simple' )
		|| ! $product->is_purchasable()
		|| ! $product->is_in_stock()
		|| $product->is_sold_individually() ) {
		return $html;
	}
	$max   = $product->get_max_purchase_quantity();
	$label = sprintf( /* translators: %s: product name */ __( 'Quantity of %s', 'eco-fridge-child' ), $product->get_name() );
	$qty   = '<div class="ef-qty">'
		. '<button type="button" class="ef-qty__btn" data-ef-step="-1" aria-label="' . esc_attr__( 'Decrease quantity', 'eco-fridge-child' ) . '">&minus;</button>'
		. '<input type="number" class="ef-qty__input" value="1" min="1" step="1"' . ( $max > 0 ? ' max="' . (int) $max . '"' : '' ) . ' inputmode="numeric" aria-label="' . esc_attr( $label ) . '">'
		. '<button type="button" class="ef-qty__btn" data-ef-step="1" aria-label="' . esc_attr__( 'Increase quantity', 'eco-fridge-child' ) . '">+</button>'
		. '</div>';
	return '<div class="ef-loop-buy">' . $qty . $html . '</div>';
}, 20, 2 );

/** Keeps the button's quantity in sync with the box (AJAX and no-JS links). */
add_action( 'wp_footer', function () {
	if ( ! ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() || is_product() || is_search() ) ) && ! has_block( 'woocommerce/product-collection' ) ) {
		return;
	}
	?>
<script>
(function(){
	function clamp(input, value){
		var min = parseInt(input.min, 10) || 1, max = parseInt(input.max, 10) || Infinity;
		value = parseInt(value, 10);
		return Math.min(max, Math.max(min, isNaN(value) ? min : value));
	}
	function sync(input){
		var wrap = input.closest('.ef-loop-buy'), btn = wrap && wrap.querySelector('.add_to_cart_button');
		if(!btn){ return; }
		var qty = String(input.value);
		btn.setAttribute('data-quantity', qty);
		if(window.jQuery){ window.jQuery(btn).data('quantity', qty); }
		try { var u = new URL(btn.href, location.href); u.searchParams.set('quantity', qty); btn.href = u.toString(); } catch(e){}
	}
	document.addEventListener('click', function(e){
		var b = e.target.closest('.ef-loop-buy .ef-qty__btn');
		if(!b){ return; }
		var input = b.parentNode.querySelector('.ef-qty__input');
		input.value = clamp(input, (parseInt(input.value, 10) || 0) + parseInt(b.getAttribute('data-ef-step'), 10));
		sync(input);
	});
	document.addEventListener('change', function(e){
		if(e.target.matches && e.target.matches('.ef-loop-buy .ef-qty__input')){ e.target.value = clamp(e.target, e.target.value); sync(e.target); }
	});
	document.addEventListener('input', function(e){
		if(e.target.matches && e.target.matches('.ef-loop-buy .ef-qty__input') && e.target.value !== ''){ sync(e.target); }
	});
})();
</script>
	<?php
}, 30 );
