<?php
/**
 * Plugin Name:       O2 Image Optimizer
 * Description:       Optimises your images by creating WebP and AVIF versions and serves each visitor the best format their browser supports (AVIF, then WebP, then the original). Works with any theme: <picture> elements or server rewrites, automatic conversion of new uploads, bulk conversion from the dashboard, per-image status and WP-CLI. Original images are never modified.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            O2 Digital
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       o2-image-optimizer
 *
 * @package O2_Image_Optimizer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'O2IO_VERSION', '1.0.0' );
define( 'O2IO_FILE', __FILE__ );
define( 'O2IO_DIR', plugin_dir_path( __FILE__ ) );
define( 'O2IO_URL', plugin_dir_url( __FILE__ ) );

require_once O2IO_DIR . 'includes/class-o2io-settings.php';
require_once O2IO_DIR . 'includes/class-o2io-converter.php';
require_once O2IO_DIR . 'includes/class-o2io-attachments.php';
require_once O2IO_DIR . 'includes/class-o2io-queue.php';
require_once O2IO_DIR . 'includes/class-o2io-delivery.php';

if ( is_admin() ) {
	require_once O2IO_DIR . 'includes/class-o2io-admin.php';
	O2IO_Admin::init();
}
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once O2IO_DIR . 'includes/class-o2io-cli.php';
	WP_CLI::add_command( 'o2io', 'O2IO_CLI' );
}

O2IO_Attachments::init();
O2IO_Queue::init();
O2IO_Delivery::init();

register_activation_hook( __FILE__, array( 'O2IO_Delivery', 'write_htaccess' ) );
register_deactivation_hook( __FILE__, array( 'O2IO_Delivery', 'remove_htaccess' ) );
register_deactivation_hook( __FILE__, array( 'O2IO_Queue', 'stop' ) );
