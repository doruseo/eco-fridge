<?php
/**
 * 56 — Product pages: Frost-Tech brand, Technical Specs / Energy tabs, finance
 * badge, Google product categories.
 *
 *  - Brand: every product is made by Frost-Tech (the only brand). A WooCommerce
 *    brand "Frost-Tech" (/brand/frost-tech/) with its logo and an introduction
 *    is created and given to every product (the product schema's brand).
 *  - Technical Specs tab: the spec tables and the [efb_toggle] Warranty panel
 *    move from the long description to the product's Technical Specs field
 *    (meta _ef_tab_specs); the description keeps its text.
 *  - Energy tab: the "Click Here For Energy Specification" document of the
 *    short description also goes to the Energy field (meta _ef_tab_energy).
 *  - Finance: the "Now available with Finance" image is taken out of 91 short
 *    descriptions (undone by migration 57: the shop manager places it).
 *  - Google Merchant Center: each product's Google product category from the
 *    original site's feed plugin (meta _ef_google_category).
 *
 * Previous description / short description are kept in _ef_content_before_56 /
 * _ef_excerpt_before_56. Idempotent. Run via source-dump/_run.php (needs the
 * ef-legacy-db container for the Google categories).
 *
 * @package EcoFridgeMigration
 */

global $wpdb;

/* -------------------------------------------------------------------------
 * 1. Brand.
 * ----------------------------------------------------------------------- */
// Paragraphs are separated by blank lines: term descriptions lose <p> tags when saved.
$brand_description = <<<'HTML'
<strong>Frost-Tech</strong> is a commercial refrigeration manufacturer that designs and builds its own display and storage equipment for food retail and food service. Every cabinet sold by ECO-Fridge is a Frost-Tech product, so you deal directly with the range, its specifications and its spare parts.

The range covers display fridges – multidecks, patisserie and cake displays, grab and go, counter, serve over, upright and drinks fridges – storage fridges and freezers for kitchens, chest and upright display freezers, low temperature freezers down to around −86 °C, and heated displays. Finishes run from black and silver steel to 304 stainless steel.

Frost-Tech builds with quality European components and puts energy efficiency first, with an ongoing programme to move its cabinets to cleaner refrigerants. For more than 30 years its equipment has served cafés, bakeries, shops, restaurants and well-known coffee chains.
HTML;

$brand = get_term_by( 'slug', 'frost-tech', 'product_brand' );
if ( ! $brand ) {
	$made  = wp_insert_term( 'Frost-Tech', 'product_brand', array( 'slug' => 'frost-tech', 'description' => $brand_description ) );
	$brand = is_wp_error( $made ) ? null : get_term( $made['term_id'], 'product_brand' );
} elseif ( '' === trim( $brand->description ) || ! preg_match( '~\R\s*\R~', $brand->description ) ) { // empty, or saved without paragraph breaks.
	wp_update_term( $brand->term_id, 'product_brand', array( 'description' => $brand_description ) );
}
if ( ! $brand ) {
	WP_CLI::error( 'Could not create the Frost-Tech brand.' );
}
$logo = (int) $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%logo-frost-tech.png' LIMIT 1" );
if ( $logo ) {
	update_term_meta( $brand->term_id, 'thumbnail_id', $logo );
}

$products = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status IN ('publish','private','draft','pending','future')" );
foreach ( $products as $id ) {
	if ( ! has_term( '', 'product_brand', (int) $id ) ) {
		wp_set_object_terms( (int) $id, (int) $brand->term_id, 'product_brand' );
	}
}
WP_CLI::log( sprintf( 'Brand Frost-Tech (#%d) on %d products.', $brand->term_id, count( $products ) ) );

// Rank Math's product schema takes the brand from this taxonomy.
$general                  = (array) get_option( 'rank-math-options-general', array() );
$general['product_brand'] = 'product_brand';
update_option( 'rank-math-options-general', $general );
// The brand page (/brand/frost-tech/) is listed in the sitemap like the categories.
$sitemap                              = (array) get_option( 'rank-math-options-sitemap', array() );
$sitemap['tax_product_brand_sitemap'] = 'on';
update_option( 'rank-math-options-sitemap', $sitemap );

/* -------------------------------------------------------------------------
 * 2–4. Technical Specs, Energy, finance badge.
 * ----------------------------------------------------------------------- */
$moved_specs = 0;
$energy_set  = 0;
$finance_off = 0;
foreach ( $products as $id ) {
	$id   = (int) $id;
	$post = get_post( $id );

	// Technical Specs: tables and the Warranty panel, in their order.
	if ( '' === (string) get_post_meta( $id, '_ef_tab_specs', true ) && ( false !== stripos( $post->post_content, '<table' ) || false !== strpos( $post->post_content, '[efb_toggle' ) ) ) {
		if ( preg_match_all( '~\[efb_toggle\b.*?\[/efb_toggle\]|<table\b.*?</table>~is', $post->post_content, $parts ) ) {
			$specs = implode( "\n\n", array_map( 'trim', $parts[0] ) );
			$rest  = preg_replace( '~\[efb_toggle\b.*?\[/efb_toggle\]|<table\b.*?</table>~is', '', $post->post_content );
			$rest  = trim( preg_replace( "~\n{3,}~", "\n\n", $rest ) );
			if ( '' === (string) get_post_meta( $id, '_ef_content_before_56', true ) ) {
				update_post_meta( $id, '_ef_content_before_56', wp_slash( $post->post_content ) );
			}
			update_post_meta( $id, '_ef_tab_specs', wp_slash( $specs ) );
			$wpdb->update( $wpdb->posts, array( 'post_content' => $rest ), array( 'ID' => $id ) );
			++$moved_specs;
		}
	}

	// Energy: the energy specification document of the short description.
	if ( '' === (string) get_post_meta( $id, '_ef_tab_energy', true ) && preg_match( '~<a\s[^>]*href="([^"]+)"[^>]*>(?:(?!</a>).)*Energy Spec(?:(?!</a>).)*</a>~is', $post->post_excerpt, $m ) ) {
		$energy = '<p>The energy test report for this cabinet gives its measured energy consumption and performance.</p>' . "\n"
			. '<p><a class="ef-energy-doc" href="' . esc_url( $m[1] ) . '" target="_blank" rel="noopener">Download the energy specification (PDF)</a></p>';
		update_post_meta( $id, '_ef_tab_energy', wp_slash( $energy ) );
		++$energy_set;
	}

	// Finance badge: shown by the theme; remove the copy in the short description.
	if ( false !== stripos( $post->post_excerpt, 'Now-on-Finance' ) ) {
		$excerpt = preg_replace( '~<(h[1-6]|p)\b[^>]*>\s*(?:<a\b[^>]*>\s*)?<img\b[^>]*Now-on-Finance[^>]*>\s*(?:</a>\s*)?</\1>\s*~is', '', $post->post_excerpt );
		$excerpt = preg_replace( '~<img\b[^>]*Now-on-Finance[^>]*>\s*~is', '', $excerpt ); // any other wrapper.
		if ( $excerpt !== $post->post_excerpt ) {
			if ( '' === (string) get_post_meta( $id, '_ef_excerpt_before_56', true ) ) {
				update_post_meta( $id, '_ef_excerpt_before_56', wp_slash( $post->post_excerpt ) );
			}
			$wpdb->update( $wpdb->posts, array( 'post_excerpt' => $excerpt ), array( 'ID' => $id ) );
			++$finance_off;
		}
	}
	clean_post_cache( $id );
}
WP_CLI::log( "Technical Specs moved on $moved_specs products, Energy tab on $energy_set, finance image removed from $finance_off short descriptions." );

/* -------------------------------------------------------------------------
 * 5. Google product categories from the original site's feed plugin.
 * ----------------------------------------------------------------------- */
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
if ( $db ) {
	mysqli_set_charset( $db, 'utf8mb4' );
	$res   = mysqli_query( $db, "SELECT post_id, meta_value FROM 43lz2ypq8_postmeta WHERE meta_key = '_woocommerce_gpf_data'" );
	$count = 0;
	while ( $res && ( $row = mysqli_fetch_assoc( $res ) ) ) {
		$data     = maybe_unserialize( $row['meta_value'] );
		$category = is_array( $data ) ? trim( (string) ( $data['google_product_category'] ?? '' ) ) : '';
		// Only real Google taxonomy paths (a few products had free text, e.g. "Upright Chiller").
		if ( '' === $category || false === strpos( $category, ' > ' ) ) {
			continue;
		}
		$local = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ef_legacy_id' AND meta_value = %s LIMIT 1", $row['post_id'] ) );
		if ( $local && 'product' === get_post_type( $local ) ) {
			update_post_meta( $local, '_ef_google_category', $category );
			++$count;
		}
	}
	mysqli_close( $db );
	WP_CLI::log( "Google product category on $count products." );
} else {
	WP_CLI::warning( 'Legacy DB not reachable – Google categories skipped (the feed falls back to the category default).' );
}

WP_CLI::success( 'Product pages updated.' );
