<?php
/**
 * Single Product Price
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/price.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

?>

<h1 class="desktop"><?php the_title() ?></h1>

<?php if( $product->is_type( 'simple' ) ) { ?>

	<div class="price-excluding-tax">Sale Price: <?php echo wc_price( wc_get_price_excluding_tax( $product ) ); ?></div>

<?php } ?>

<?php if( $product->is_type( 'simple' ) ) { ?>

	<?php if ( ((get_post_meta($post->ID, 'wpcf-model-dimensions', TRUE))=='')) { ?>
		
		<div class="sku-wrapper"><strong>Model:</strong>&nbsp; <?php echo(types_render_field( "model-dimensions", array() )); ?></div>
	
	<?php } else { ?>
		
		<div class="sku-wrapper"><strong>Model:</strong> <?php echo $product->get_sku(); ?></div>
				
	<?php } ?>

	
		
<?php } ?>

<!--<p class="price"><?php echo $product->get_price_html(); ?></p>-->

<?php if( $product->is_type( 'simple' ) ) { ?>
		
<?php } elseif( $product->is_type( 'variable' ) ) { ?>

	<div class="yith_wapo_group_total <?php echo ( get_option( 'yith_wapo_settings_show_add_ons_price_table' , 'no' ) == 'yes' ? 'yith_wapo_keep_show' : '' ); ?>" data-product-price="<?php echo esc_attr( $product_display_price ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
		<table>
			<tr class="ywapo_tr_order_totals">
				<td><div class="yith_wapo_group_final_total"><span class="price amount"></span></div></td>
			</tr>
		</table>
	</div>
	
<?php } ?>