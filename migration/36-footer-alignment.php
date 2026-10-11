<?php
/**
 * 36 — Footer columns: left-aligned on desktop and tablet, centred on mobile.
 *
 * Set in Astra's Footer Builder (Customizer → Footer Builder → each element →
 * Alignment), so it stays editable there: the two HTML columns (company,
 * contact) and the "Information" menu widget. The copyright row stays centred.
 *
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$s     = get_option( 'astra-settings', array() );
$align = array(
	'desktop' => 'left',
	'tablet'  => 'left',
	'mobile'  => 'center',
);
foreach ( array( 'footer-html-1-alignment', 'footer-html-2-alignment', 'footer-widget-alignment-1' ) as $key ) {
	$s[ $key ] = $align;
}
update_option( 'astra-settings', $s );

if ( function_exists( 'astra_clear_all_assets_cache' ) ) {
	astra_clear_all_assets_cache();
}
WP_CLI::success( 'Footer columns: left on desktop/tablet, centred on mobile.' );
