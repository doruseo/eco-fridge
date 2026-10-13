<?php
/**
 * 48 — Accessible brand green (Lighthouse colour contrast).
 *
 * The brand green #228B22 has a 4.39:1 contrast on white, just under the
 * WCAG AA minimum of 4.5:1 for text (links, green labels, white text on green
 * buttons). #1f821f looks the same and passes (4.92:1). Set in the Astra
 * global palette (Customizer → Global → Colors) and Astra's link and theme
 * colours, so the whole site follows. Idempotent.
 *
 * @package EcoFridgeMigration
 */

$green    = '#1f821f';
$settings = (array) get_option( 'astra-settings', array() );
if ( isset( $settings['global-color-palette']['palette'][0] ) ) {
	$settings['global-color-palette']['palette'][0] = $green;
}
foreach ( array( 'link-color', 'theme-color' ) as $key ) {
	if ( isset( $settings[ $key ] ) && in_array( strtolower( (string) $settings[ $key ] ), array( '#228b22', $green ), true ) ) {
		$settings[ $key ] = $green;
	}
}
update_option( 'astra-settings', $settings );
WP_CLI::success( 'Brand green set to ' . $green . '.' );
