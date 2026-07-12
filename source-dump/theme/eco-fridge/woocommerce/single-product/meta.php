<?php
/**
 * Single Product Meta
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $product;
?>
<div class="product_meta">

	<?php do_action( 'woocommerce_product_meta_start' ); ?>
																		
		<div class="product-specs">
			<p>Brand: Frost-Tech</p>
			<p>
				<?php if ( wc_product_sku_enabled() && ( $product->is_type( 'variable' ) ) ) : ?>
				
					<div class="sku-wrapper"><?php _e( 'Product Code:', 'woocommerce' ); ?> <span class="sku" itemprop="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : __( 'N/A', 'woocommerce' ); ?></span></div>
				
				<?php endif; ?>
				<?php if( $product->is_type( 'simple' ) ) { ?>
				
					<div class="sku-wrapper">Product Code: <?php echo $product->get_sku(); ?></div>
				
				<?php } ?>
				
			</p>
			<p>Availability: In stock</p>
			
		
		</div>
	
		<div class="product-features"><?php echo (types_render_field("features", array( ))); ?></div>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>

</div>
