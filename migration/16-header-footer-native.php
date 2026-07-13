<?php
/**
 * Pasul 16: HEADER / FOOTER / BANNER 100% NATIVE Astra Pro (înlocuiesc codul custom
 * din tema copil, acum șters). Totul editabil din Customizer / Appearance.
 *
 *  - Header Builder (desktop + mobil): logo | meniu (Primary Menu, cu mega nativ) |
 *    FiboSearch (element HTML) | coș WooCommerce. Sticky pe rândul principal.
 *  - Footer Builder: 3 coloane HTML (adresă | contact | informații) + copyright.
 *  - Banner „Call us today": Astra Custom Layout (advanced-hooks) pe
 *    `astra_content_before`, afișat pe tot site-ul MAI PUȚIN homepage.
 *
 * Idempotent. Rulare: cp migration/16-header-footer-native.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$s = get_option( 'astra-settings', array() );
if ( ! is_array( $s ) ) { $s = array(); }

/* ------------------------------------------------------------------ HEADER */

// FiboSearch într-un element HTML de header (Astra rulează do_shortcode pe conținut).
$s['header-html-1'] = '[fibosearch]';

$s['header-desktop-items'] = array(
	'popup'   => array( 'popup_content' => array( 'mobile-menu' ) ),
	'above'   => array(
		'above_left' => array(), 'above_left_center' => array(), 'above_center' => array(),
		'above_right_center' => array(), 'above_right' => array(),
	),
	'primary' => array(
		'primary_left'         => array( 'logo' ),
		'primary_left_center'  => array(),
		'primary_center'       => array(),
		'primary_right_center' => array(),
		'primary_right'        => array( 'menu-1', 'html-1', 'woo-cart' ),
	),
	'below'   => array(
		'below_left' => array(), 'below_left_center' => array(), 'below_center' => array(),
		'below_right_center' => array(), 'below_right' => array(),
	),
);

$s['header-mobile-items'] = array(
	'popup'   => array( 'popup_content' => array( 'mobile-menu' ) ),
	'above'   => array( 'above_left' => array(), 'above_center' => array(), 'above_right' => array() ),
	'primary' => array(
		'primary_left'   => array( 'logo' ),
		'primary_center' => array(),
		'primary_right'  => array( 'woo-cart', 'mobile-trigger' ),
	),
	'below'   => array( 'below_left' => array(), 'below_center' => array(), 'below_right' => array() ),
);

// Sticky pe rândul principal de header.
$s['header-main-stick'] = true;
// Aliniere meniu inline (default), lățime header „content".
$s['hb-header-main-menu-align'] = 'inline';

/* ------------------------------------------------------------------ FOOTER */

$c = array(
	'company' => 'ECO-Fridge Ltd',
	'address' => '18 Top Angel, Buckingham Industrial Park, Buckingham, Buckinghamshire MK18 1TH',
	'phone'   => '01280 811411',
	'tel'     => '01280811411',
	'email'   => 'sales@eco-fridge.co.uk',
);

$s['footer-desktop-items'] = array(
	'above'   => array( 'above_1' => array(), 'above_2' => array(), 'above_3' => array(), 'above_4' => array(), 'above_5' => array() ),
	'primary' => array(
		'primary_1' => array( 'html-1' ),
		'primary_2' => array( 'html-2' ),
		'primary_3' => array( 'html-3' ),
		'primary_4' => array(),
		'primary_5' => array(),
	),
	'below'   => array( 'below_1' => array( 'copyright' ), 'below_2' => array(), 'below_3' => array(), 'below_4' => array(), 'below_5' => array() ),
);
// 3 coloane pe rândul principal de footer.
$s['hb-footer-column']        = 3;
$s['hbb-footer-column']       = 3;
$s['footer-desktop-column']   = 3;

$s['footer-html-1'] = '<h3>' . $c['company'] . '</h3><p>' . $c['address'] . '</p>';
$s['footer-html-2'] = '<h3>Contact</h3><p>Tel: <a href="tel:' . $c['tel'] . '">' . $c['phone'] . '</a><br>Email: <a href="mailto:' . $c['email'] . '">' . $c['email'] . '</a></p>';
$s['footer-html-3'] = '<h3>Information</h3><ul>'
	. '<li><a href="/privacy-cookies-policy/">Privacy &amp; Cookies Policy</a></li>'
	. '<li><a href="/returns/">Returns Policy</a></li>'
	. '<li><a href="/terms-conditions/">Terms &amp; Conditions</a></li>'
	. '<li><a href="/contact-us/">Contact Us</a></li>'
	. '</ul>';

$s['footer-copyright-editor'] = '<p>&copy; [current_year] <a href="/">' . $c['company'] . '</a>. All rights reserved.</p>'
	. '<p class="ef-footer-credit">Designed by <a href="https://seo2agency.com/" rel="nofollow noopener" target="_blank">O2 Digital</a></p>';

update_option( 'astra-settings', $s );
WP_CLI::log( 'Astra Header/Footer Builder configurat (logo|menu|search|cart; footer 3 coloane + copyright).' );

/* ------------------------------------------------------------------ BANNER (Custom Layout) */

$banner_slug    = 'ef-call-banner';
$banner_content = '<!-- wp:html --><div class="ef-call-banner"><div class="ef-call-inner">'
	. '<span class="ef-call-text">Call us today <a href="tel:' . $c['tel'] . '">' . $c['phone'] . '</a></span>'
	. '<span class="ef-call-sub">Trade prices &bull; Free delivery &bull; Manufacturer direct</span>'
	. '</div></div><!-- /wp:html -->';

$existing = get_posts( array(
	'post_type'   => 'astra-advanced-hook',
	'name'        => $banner_slug,
	'post_status' => 'any',
	'numberposts' => 1,
) );
$banner_id = $existing ? (int) $existing[0]->ID : 0;

$banner_args = array(
	'ID'           => $banner_id,
	'post_type'    => 'astra-advanced-hook',
	'post_title'   => 'Call us today banner',
	'post_name'    => $banner_slug,
	'post_status'  => 'publish',
	'post_content' => $banner_content,
);
$banner_id = wp_insert_post( $banner_args );

if ( $banner_id && ! is_wp_error( $banner_id ) ) {
	update_post_meta( $banner_id, 'ast-advanced-hook-layout', 'hooks' );
	update_post_meta( $banner_id, 'ast-advanced-hook-action', 'astra_content_before' );
	update_post_meta( $banner_id, 'ast-advanced-hook-priority', '10' );
	update_post_meta( $banner_id, 'ast-advanced-hook-enabled', 'enabled' );
	// afișare: tot site-ul, EXCEPTÂND homepage
	update_post_meta( $banner_id, 'ast-advanced-hook-location', array( 'rule' => array( 'basic-global' ) ) );
	update_post_meta( $banner_id, 'ast-advanced-hook-exclusion', array( 'rule' => array( 'special-front' ) ) );
	update_post_meta( $banner_id, 'ast-advanced-hook-users', array( 'all' ) );
	WP_CLI::log( 'Banner „Call us today" = Astra Custom Layout #' . $banner_id . ' (astra_content_before, tot site-ul mai puțin homepage).' );
} else {
	WP_CLI::warning( 'Nu s-a putut crea Custom Layout-ul pentru banner.' );
}

WP_CLI::success( 'Header / Footer / Banner native Astra finalizate.' );
