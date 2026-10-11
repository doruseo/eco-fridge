<?php
/**
 * 35 — Footer in brand green (like the commercial menu bar and the
 * scroll-to-top button) instead of the light green.
 *
 * The colours live in Astra's Footer Builder (Customizer → Footer Builder →
 * Primary / Below Footer → Background), so they stay editable there:
 *  - primary footer: global colour 0 (#228B22, brand green);
 *  - below footer (copyright): global colour 1 (#1b6f1b, darker green);
 *  - copyright text: white.
 * Text, headings and links in the footer are white (astra-child/style.css).
 *
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$s = get_option( 'astra-settings', array() );

$set_bg = function ( $key, $color ) use ( &$s ) {
	$obj = isset( $s[ $key ] ) && is_array( $s[ $key ] ) ? $s[ $key ] : array();
	foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
		$obj[ $device ]                     = isset( $obj[ $device ] ) && is_array( $obj[ $device ] ) ? $obj[ $device ] : array();
		$obj[ $device ]['background-color'] = 'desktop' === $device ? $color : '';
		$obj[ $device ]['background-type']  = 'desktop' === $device ? 'color' : ( $obj[ $device ]['background-type'] ?? '' );
	}
	$s[ $key ] = $obj;
};

$set_bg( 'hb-footer-bg-obj-responsive', 'var(--ast-global-color-0)' );
$set_bg( 'hbb-footer-bg-obj-responsive', 'var(--ast-global-color-1)' );
$s['footer-copyright-color'] = 'var(--ast-global-color-4)';

update_option( 'astra-settings', $s );

// Astra caches its generated CSS; rebuild it.
if ( class_exists( 'Astra_Cache_Base' ) ) {
	delete_option( 'astra-theme-dynamic-css-time' );
}
do_action( 'astra_post_meta_updated' );
if ( function_exists( 'astra_clear_all_assets_cache' ) ) {
	astra_clear_all_assets_cache();
}

WP_CLI::success( 'Footer: primary = brand green, below footer = dark green, copyright text white.' );
