<?php
/**
 * Category landing pages for the commercial categories (Commercial Fridge,
 * Commercial Freezer and every category under them): features, FAQ accordion,
 * comparison table, related articles, long description, spare parts, products
 * carousel, related categories, markets and an aggregated reviews wall after the
 * product grid, edited on Products > Categories > Edit.
 *
 * Also turns the category "Description" into a rich-text "Short description"
 * editor on every product category.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

require_once EFB_DIR . 'includes/category-landing/meta.php';
require_once EFB_DIR . 'includes/category-landing/features.php';
require_once EFB_DIR . 'includes/category-landing/product-cards.php';
require_once EFB_DIR . 'includes/category-landing/parts.php';
require_once EFB_DIR . 'includes/category-landing/reviews.php';
require_once EFB_DIR . 'includes/category-landing/frontend.php';
require_once EFB_DIR . 'includes/category-landing/admin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		new EFB_Landing_Frontend();
		new EFB_Landing_Reviews();
		if ( is_admin() ) {
			new EFB_Landing_Admin();
		}
	}
);
