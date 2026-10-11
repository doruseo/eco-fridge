<?php
/**
 * 33 — Product/category thumbnails shown whole (no square crop).
 *
 * WooCommerce crops the catalogue thumbnail ("woocommerce_thumbnail") to 1:1 by
 * default, inside the image file itself, so tall fridges lost their top and
 * bottom in the product grid and the category carousels. This switches the
 * setting to "Uncropped" (Customizer → WooCommerce → Product Images); the
 * theme then fits each image whole inside a fixed box.
 *
 * Existing thumbnails must be regenerated afterwards (one size only):
 *   npx wp-env run cli wp media regenerate --image_size=woocommerce_thumbnail --yes
 *
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

update_option( 'woocommerce_thumbnail_cropping', 'uncropped' );
WP_CLI::success( 'woocommerce_thumbnail_cropping = ' . get_option( 'woocommerce_thumbnail_cropping' ) . ' (now regenerate the woocommerce_thumbnail size).' );
