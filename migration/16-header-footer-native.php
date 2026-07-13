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

/* ------------------------------------------------------------------ BANNER — ȘTERS */
// Banner-ul „Call us today" a fost eliminat de pe tot site-ul (la cererea userului).
// Ștergem definitiv Custom Layout-ul dacă mai există (idempotent).
$old_banners = get_posts( array(
	'post_type'   => 'astra-advanced-hook',
	'name'        => 'ef-call-banner',
	'post_status' => 'any',
	'numberposts' => -1,
) );
foreach ( $old_banners as $b ) {
	wp_delete_post( $b->ID, true );
	WP_CLI::log( 'Banner „Call us today" (Custom Layout #' . $b->ID . ') șters.' );
}

WP_CLI::success( 'Header / Footer native Astra finalizate (fără banner).' );
