<?php
/**
 * Plugin Name:       EF Cookie Consent
 * Description:       UK cookie consent (PECR / UK GDPR): banner with Accept all / Reject all / Manage options, per-category and per-service choices, Google Consent Mode v2 for Google Tag Manager, script and iframe blocking, cookie scanner, automatic cookie tables for the cookie policy, consent log.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            O2 Digital
 * License:           GPL-2.0-or-later
 * Text Domain:       ef-cookie-consent
 *
 * @package EF_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'EFCC_VERSION', '1.0.0' );
define( 'EFCC_FILE', __FILE__ );
define( 'EFCC_DIR', plugin_dir_path( __FILE__ ) );
define( 'EFCC_URL', plugin_dir_url( __FILE__ ) );

require_once EFCC_DIR . 'includes/class-efcc-library.php';
require_once EFCC_DIR . 'includes/class-efcc-settings.php';
require_once EFCC_DIR . 'includes/class-efcc-cookies.php';
require_once EFCC_DIR . 'includes/class-efcc-log.php';
require_once EFCC_DIR . 'includes/class-efcc-frontend.php';

if ( is_admin() ) {
	require_once EFCC_DIR . 'includes/class-efcc-admin.php';
	EFCC_Admin::init();
}

EFCC_Cookies::init();
EFCC_Log::init();
EFCC_Frontend::init();

register_activation_hook( __FILE__, array( 'EFCC_Log', 'install' ) );
register_deactivation_hook( __FILE__, array( 'EFCC_Log', 'unschedule' ) );
