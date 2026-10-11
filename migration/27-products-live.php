<?php
/**
 * 27 — Products synced 1:1 with the LIVE site and converted to blocks.
 *
 * Source: source-dump/_live-products.json (migration/tools/extract_live_products.py
 * over the crawled live pages).
 *
 * Per product:
 *  - description = live description → core blocks (links made relative, hidden
 *    white "keyword" text removed) + the spec tables actually SHOWN on the live
 *    page → efb/spec-table blocks (replaces TablePress shortcodes; drops the
 *    warranty tables, which 31-warranty-toggle.php adds as a click-to-show toggle);
 *  - short description (features) = live list;
 *  - price: live price; when lower than the regular price it becomes the sale
 *    price (special offers show the saving);
 *  - stock status + SKU = live.
 *
 * Idempotent. Run via source-dump/_run.php (needs _lib-html-to-blocks.php).
 *
 * @package EcoFridgeMigration
 */

require_once WP_CONTENT_DIR . '/source-dump/_lib-html-to-blocks.php';

$live = json_decode( (string) file_get_contents( WP_CONTENT_DIR . '/source-dump/_live-products.json' ), true );
if ( ! $live ) {
	WP_CLI::error( 'Live products JSON missing.' );
}

$strip_hidden = function ( $html ) {
	// Hidden keyword text (white on white) — a search-engine guideline violation.
	return preg_replace( '~<span style="color:\s*#(?:fff|ffffff);?">.*?</span>~i', '', $html );
};

$stats = array( 'updated' => 0, 'tables' => 0, 'price' => 0, 'stock' => 0, 'sku' => 0, 'missing' => 0 );
foreach ( $live as $slug => $l ) {
	$post = get_page_by_path( $slug, OBJECT, 'product' );
	if ( ! $post ) {
		$stats['missing']++;
		WP_CLI::warning( "Missing locally: $slug" );
		continue;
	}
	$product = wc_get_product( $post->ID );

	// Description + spec tables.
	$content = efm_html_to_blocks( $strip_hidden( $l['description'] ) );
	foreach ( $l['spec_tables'] as $table_html ) {
		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?>' . efm_relative_urls( $table_html ) );
		libxml_clear_errors();
		$table = $doc->getElementsByTagName( 'table' )->item( 0 );
		if ( $table ) {
			$content .= efm_table_block( $table );
			$stats['tables']++;
		}
	}
	// The description is written with wp_update_post() after save(): WC's CRUD
	// unslashes post_content, which would break the unicode-escaped block JSON.

	// Features (short description).
	if ( $l['features'] ) {
		$product->set_short_description( efm_relative_urls( $l['features'] ) );
	}

	// Price.
	$live_price = (float) $l['price'];
	if ( $live_price > 0 && abs( $live_price - (float) $product->get_price() ) > 0.001 ) {
		$regular = (float) $product->get_regular_price();
		if ( $regular > $live_price ) {
			$product->set_sale_price( wc_format_decimal( $live_price ) );
		} else {
			$product->set_regular_price( wc_format_decimal( $live_price ) );
			$product->set_sale_price( '' );
		}
		$stats['price']++;
	}

	// Stock status.
	if ( $l['stock'] && $l['stock'] !== $product->get_stock_status() ) {
		$product->set_stock_status( $l['stock'] );
		$stats['stock']++;
	}

	// SKU.
	if ( $l['product_code'] && $l['product_code'] !== $product->get_sku() ) {
		try {
			$product->set_sku( $l['product_code'] );
			$stats['sku']++;
		} catch ( Exception $e ) {
			WP_CLI::warning( "$slug SKU: " . $e->getMessage() );
		}
	}

	$product->update_meta_data( '_efb_live_synced', gmdate( 'c' ) );
	$product->update_meta_data( '_efb_live_model_line', $l['model_line'] );
	$product->save();
	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => wp_slash( $content ),
		)
	);
	$stats['updated']++;
}

wc_delete_product_transients();
WP_CLI::success( wp_json_encode( $stats ) );
