<?php
/**
 * 30 — "From £X +VAT" labels synced with the live listings.
 *
 * Source: source-dump/_live-from-prices.json (slug → label scraped from every
 * crawled live listing page). Labels are normalised to "£X +VAT" (some live
 * values miss the £ sign or "+VAT"). Products without a live label keep theirs.
 *
 * @package EcoFridgeMigration
 */

$live    = json_decode( (string) file_get_contents( WP_CONTENT_DIR . '/source-dump/_live-from-prices.json' ), true );
$changed = array();
foreach ( $live as $slug => $label ) {
	$label = trim( preg_replace( '~^from\s*~i', '', (string) $label ) );
	if ( '' === $label || ! preg_match( '~([0-9][0-9,]*(?:\.[0-9]+)?)~', $label, $m ) ) {
		continue;
	}
	$amount = (float) str_replace( ',', '', $m[1] );
	$norm   = '£' . number_format( $amount, ( floor( $amount ) == $amount ) ? 0 : 2 ) . ' +VAT';
	$post   = get_page_by_path( $slug, OBJECT, 'product' );
	if ( ! $post ) {
		continue;
	}
	$old = (string) get_post_meta( $post->ID, '_ef_from_price', true );
	if ( $old !== $norm ) {
		update_post_meta( $post->ID, '_ef_from_price', $norm );
		$changed[] = "$slug: '$old' -> '$norm'";
	}
}
WP_CLI::log( implode( "\n", $changed ) );
WP_CLI::success( count( $changed ) . ' from-prices updated' );
