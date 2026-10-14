<?php
/**
 * 57 — Product pages: Features tab; finance image back in the short description.
 *
 *  - The "Now available with Finance" image is not shown by the theme any more:
 *    the shop manager adds it to a product's short description when wanted.
 *    Where migration 56 took it out, the short description is restored from
 *    _ef_excerpt_before_56 (the image comes back exactly where it was).
 *  - The feature list (the bullet list of the short description) moves to the
 *    product's Features field (meta _ef_tab_features): the Features tab shows it
 *    in two columns under the gallery and the summary.
 *
 * The short description before this migration is kept in _ef_excerpt_before_57.
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

global $wpdb;

$products = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status IN ('publish','private','draft','pending','future')" );
$restored = 0;
$features = 0;
foreach ( $products as $id ) {
	$id      = (int) $id;
	$excerpt = (string) get_post_field( 'post_excerpt', $id );

	// 1. Finance image back where migration 56 removed it (only if the features
	//    have not been moved yet – after that the backup would bring them back).
	$before_56 = (string) get_post_meta( $id, '_ef_excerpt_before_56', true );
	if ( '' !== $before_56 && false === stripos( $excerpt, 'Now-on-Finance' ) && '' === (string) get_post_meta( $id, '_ef_tab_features', true ) ) {
		$excerpt = $before_56;
		++$restored;
	}

	// 2. The first bullet list → Features.
	if ( '' === (string) get_post_meta( $id, '_ef_tab_features', true ) && preg_match( '~<ul\b[^>]*>.*?</ul>~is', $excerpt, $m ) ) {
		if ( '' === (string) get_post_meta( $id, '_ef_excerpt_before_57', true ) ) {
			update_post_meta( $id, '_ef_excerpt_before_57', wp_slash( (string) get_post_field( 'post_excerpt', $id ) ) );
		}
		update_post_meta( $id, '_ef_tab_features', wp_slash( trim( $m[0] ) ) );
		$excerpt = trim( preg_replace( "~\n{3,}~", "\n\n", str_replace( $m[0], '', $excerpt ) ) );
		++$features;
	}

	if ( $excerpt !== (string) get_post_field( 'post_excerpt', $id ) ) {
		$wpdb->update( $wpdb->posts, array( 'post_excerpt' => $excerpt ), array( 'ID' => $id ) );
		clean_post_cache( $id );
	}
}

WP_CLI::success( "Finance image restored on $restored products; feature lists moved to the Features tab on $features products." );
