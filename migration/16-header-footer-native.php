<?php
/**
 * Step 16: HEADER / FOOTER / BANNER 100% NATIVE Astra Pro (replacing the custom code
 * of the child theme, now deleted). All editable from the Customizer / Appearance.
 *
 *  - Header Builder (desktop + mobile): logo | menu (Primary Menu, with the native mega) |
 *    FiboSearch (HTML element) | WooCommerce cart. Sticky main row.
 *  - Footer Builder: 3 HTML columns (address | contact | information) + copyright.
 *  - "Call us today" banner: Astra Custom Layout (advanced-hooks) on
 *    `astra_content_before`, shown on the whole site EXCEPT the homepage.
 *
 * Idempotent. Run: cp migration/16-header-footer-native.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$s = get_option( 'astra-settings', array() );
if ( ! is_array( $s ) ) { $s = array(); }

/* ------------------------------------------------------------------ HEADER */

// FiboSearch in a header HTML element (Astra runs do_shortcode on the content).
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

// Native Astra sticky header OFF: the header is custom (the green bar) and its sticky
// behaviour is set by theme_mod `ef_greenbar_sticky` (CSS position:sticky, no shrink).
// The native Astra module added an unwanted "shrink" effect on scroll, so it is off.
$s['header-main-stick']  = false;
$s['header-below-stick'] = false;
$s['header-above-stick'] = false;
// Inline menu alignment (default), "content" header width.
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
// 3 columns on the main footer row.
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
WP_CLI::log( 'Astra Header/Footer Builder configured (logo|menu|search|cart; 3-column footer + copyright).' );

/* ------------------------------------------------------------------ BANNER: DELETED */
// The "Call us today" banner was removed from the whole site (at the user's request).
// Delete the Custom Layout for good if it still exists (idempotent).
$old_banners = get_posts( array(
	'post_type'   => 'astra-advanced-hook',
	'name'        => 'ef-call-banner',
	'post_status' => 'any',
	'numberposts' => -1,
) );
foreach ( $old_banners as $b ) {
	wp_delete_post( $b->ID, true );
	WP_CLI::log( 'Banner "Call us today" (Custom Layout #' . $b->ID . ') deleted.' );
}

WP_CLI::success( 'Native Astra header and footer finished (no banner).' );
