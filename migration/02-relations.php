<?php
/**
 * Product migration step 2: remaps upsells/cross-sells (old IDs -> new).
 * Run: wp eval-file wp-content/source-dump/_run.php
 * Needs the map written by 01-products.php (source-dump/_product-id-map.json).
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$map_file = ABSPATH . 'wp-content/source-dump/_product-id-map.json';
if ( ! file_exists( $map_file ) ) { WP_CLI::error( '_product-id-map.json is missing (run 01-products.php first).' ); }
$id_map = json_decode( file_get_contents( $map_file ), true );
if ( ! $id_map ) { WP_CLI::error( 'The ID map is empty or invalid.' ); }

$remap = function ( $csv ) use ( $id_map ) {
	$out = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) $csv ) ) ) as $old ) {
		if ( isset( $id_map[ $old ] ) ) { $out[] = (int) $id_map[ $old ]; }
	}
	return array_values( array_unique( $out ) );
};

$products = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
$up = 0; $cross = 0;
foreach ( $products as $pid ) {
	$product = wc_get_product( $pid );
	if ( ! $product ) { continue; }
	$changed = false;
	$legacy_up = get_post_meta( $pid, '_ef_legacy_upsell', true );
	if ( $legacy_up ) {
		$ids = $remap( maybe_unserialize( $legacy_up ) ? implode( ',', (array) maybe_unserialize( $legacy_up ) ) : $legacy_up );
		if ( $ids ) { $product->set_upsell_ids( $ids ); $up++; $changed = true; }
	}
	$legacy_cross = get_post_meta( $pid, '_ef_legacy_crosssell', true );
	if ( $legacy_cross ) {
		$ids = $remap( maybe_unserialize( $legacy_cross ) ? implode( ',', (array) maybe_unserialize( $legacy_cross ) ) : $legacy_cross );
		if ( $ids ) { $product->set_cross_sell_ids( $ids ); $cross++; $changed = true; }
	}
	if ( $changed ) { $product->save(); }
}

WP_CLI::log( "Upsells set on $up products | cross-sells on $cross products." );
WP_CLI::success( 'Step 2 (relations) finished.' );
