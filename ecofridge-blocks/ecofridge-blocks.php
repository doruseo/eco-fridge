<?php
/**
 * Plugin Name:       ECO-Fridge Blocks
 * Description:       In-house Gutenberg blocks and site features for ECO-Fridge (sections, hero slider, offer cards, client logos, category tiles, product grid, live product search, contact form, spec tables, brochures and videos, commercial category landing pages). Replaces third-party block, search, form and table plugins.
 * Version:           1.1.7
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            O2 Digital
 * Author URI:        https://seo2agency.com
 * License:           GPL-2.0-or-later
 * Text Domain:       ecofridge-blocks
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

define( 'EFB_VERSION', '1.5.8' );
define( 'EFB_FILE', __FILE__ );
define( 'EFB_DIR', plugin_dir_path( __FILE__ ) );
define( 'EFB_URL', plugin_dir_url( __FILE__ ) );

require_once EFB_DIR . 'includes/helpers.php';
require_once EFB_DIR . 'includes/security.php';
require_once EFB_DIR . 'includes/post-types.php';
require_once EFB_DIR . 'includes/search.php';
require_once EFB_DIR . 'includes/forms.php';
require_once EFB_DIR . 'includes/blocks.php';
require_once EFB_DIR . 'includes/legacy-urls.php';
require_once EFB_DIR . 'includes/tag-redirects.php';
require_once EFB_DIR . 'includes/markets.php';
require_once EFB_DIR . 'includes/blog.php';
require_once EFB_DIR . 'includes/sitemaps.php';
require_once EFB_DIR . 'includes/woocommerce.php';
require_once EFB_DIR . 'includes/abandoned-carts.php';
require_once EFB_DIR . 'includes/reviews.php';
require_once EFB_DIR . 'includes/no-accounts.php';
require_once EFB_DIR . 'includes/brands.php';
require_once EFB_DIR . 'includes/merchant-center.php';
require_once EFB_DIR . 'includes/category-landing.php';

register_activation_hook(
	__FILE__,
	static function () {
		efb_register_post_types();
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
