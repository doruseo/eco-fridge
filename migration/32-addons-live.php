<?php
/**
 * 32 — Product add-ons 1:1 with the live site + "Warranty" toggle title.
 *
 * The live site offers its YITH add-ons per product: Removal & Disposal (£150),
 * 12 months parts warranty (+15% of the current price) and Castors (£100).
 * Which product offers which is read from the crawled live pages
 * (source-dump/_live/product__<slug>.html) and stored in the `_ef_addons`
 * product meta used by astra-child/inc/woo-single.php.
 *
 * Also renames the warranty price-table toggle (migration 31) to "Warranty".
 *
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$labels = array(
	'removal'  => 'Removal & Disposal: £150',
	'warranty' => '12 months parts warranty + 15%',
	'castors'  => 'Castors + £100',
);

$stats = array( 'products' => 0, 'missing' => 0, 'removal' => 0, 'warranty' => 0, 'castors' => 0, 'formula' => 0 );
foreach ( glob( WP_CONTENT_DIR . '/source-dump/_live/product__*.html' ) as $file ) {
	$slug = substr( basename( $file, '.html' ), strlen( 'product__' ) );
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( ! $post ) {
		$stats['missing']++;
		continue;
	}
	$html = (string) file_get_contents( $file );
	$keys = array();
	foreach ( $labels as $key => $label ) {
		if ( false !== strpos( $html, '>' . $label . '<' ) ) {
			$keys[] = $key;
			$stats[ $key ]++;
		}
	}
	update_post_meta( $post->ID, '_ef_addons', $keys );
	$stats['products']++;

	// Cross-check the warranty amount YITH showed on live with the local formula.
	if ( in_array( 'warranty', $keys, true ) && preg_match( '~data-price="([\d.]+)" data-pricetype="percentage"~', $html, $m ) ) {
		$local = ef_addon_unit_price( 'warranty', wc_get_product( $post->ID ) );
		if ( abs( $local - (float) $m[1] ) > 0.01 ) {
			$stats['formula']++;
			WP_CLI::warning( "Warranty differs on $slug: live £{$m[1]}, local £$local" );
		}
	}

	// Warranty price-table toggle title.
	if ( false !== strpos( $post->post_content, 'wp:efb/toggle' ) ) {
		$content = preg_replace_callback(
			'~<!-- wp:efb/toggle (\{[^\n]*?\}) -->~',
			function ( $m ) {
				$attrs = json_decode( $m[1], true );
				if ( ! $attrs || false === strpos( (string) ( $attrs['className'] ?? '' ), 'efb-toggle--warranty' ) ) {
					return $m[0];
				}
				$attrs['title'] = 'Warranty';
				return '<!-- wp:efb/toggle ' . serialize_block_attributes( $attrs ) . ' -->';
			},
			$post->post_content
		);
		if ( $content !== $post->post_content ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $content ) ) );
		}
	}
}
WP_CLI::success(
	sprintf(
		'Add-ons set on %d products (removal %d, warranty %d, castors %d); %d not found locally; %d warranty amounts differ from live.',
		$stats['products'],
		$stats['removal'],
		$stats['warranty'],
		$stats['castors'],
		$stats['missing'],
		$stats['formula']
	)
);
