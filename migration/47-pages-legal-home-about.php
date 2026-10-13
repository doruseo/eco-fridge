<?php
/**
 * 47 — Privacy Policy, Cookie Policy (UK) and Terms & Conditions rewritten, home page main-range
 * carousel and shorter closing copy, About Us layout.
 *
 *  - /privacy-cookies-policy/, /cookie-policy-uk/ and /terms-conditions/: new copy from
 *    migration/page-copy/ (UK GDPR policy with a cookie table; clear B2B terms
 *    matching the shop: optional warranty, Removal & Disposal, returns page);
 *  - home page: a new top (page-copy/home-top.html): the H1 with four
 *    highlights (efb/highlights), the offers, an efb/range-carousel (Commercial
 *    Fridges / Freezers, Display / Storage Cabinets), the intro beside a photo
 *    and the client logos; then "Our commercial refrigeration equipment" and
 *    the rest kept, then market buttons and a blog carousel (efb/market-buttons,
 *    efb/post-carousel)
 *    (page-copy/home-markets-blog.html), and the long closing text replaced by
 *    a short guide (page-copy/home-bottom.html);
 *  - /about-us/: the same text, laid out in sections with local images
 *    (the original pointed at the old development domain).
 *
 * The previous content of each page is kept in the `_ef_content_before_47`
 * post meta. Idempotent. Run via source-dump/_run.php after copying
 * migration/page-copy/ to source-dump/_page-copy/.
 *
 * @package EcoFridgeMigration
 */

$dir = WP_CONTENT_DIR . '/source-dump/_page-copy';
if ( ! is_dir( $dir ) ) {
	WP_CLI::error( "Copy migration/page-copy to $dir first." );
}

$save = static function ( $slug, $content ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		WP_CLI::warning( "Page not found: $slug" );
		return;
	}
	if ( '' === (string) get_post_meta( $page->ID, '_ef_content_before_47', true ) ) {
		update_post_meta( $page->ID, '_ef_content_before_47', wp_slash( $page->post_content ) );
	}
	if ( $content !== $page->post_content ) {
		wp_update_post( array( 'ID' => $page->ID, 'post_content' => wp_slash( $content ) ) );
	}
	WP_CLI::log( "Updated /$slug/" );
};

$read = static fn( $file ) => trim( (string) file_get_contents( $dir . '/' . $file ) ) . "\n";

$save( 'privacy-cookies-policy', $read( 'privacy-cookies-policy.html' ) );
$save( 'terms-conditions', $read( 'terms-conditions.html' ) );
$save( 'cookie-policy-uk', $read( 'cookie-policy-uk.html' ) );
$save( 'about-us', $read( 'about-us.html' ) );

// Home page: start from the original content, so the migration can be re-run.
$home = get_post( (int) get_option( 'page_on_front' ) );
if ( $home ) {
	$original = (string) get_post_meta( $home->ID, '_ef_content_before_47', true );
	$source   = '' !== $original ? $original : $home->post_content;
	// Heading order: the section title under the H1 is an H2.
	$source = str_replace(
		"<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">Our commercial refrigeration equipment</h3>",
		"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Our commercial refrigeration equipment</h2>",
		$source
	);

	// The top part ends after the section with "Our commercial refrigeration equipment";
	// the "Shop by range" carousel is placed right under the offers (home-top.html).
	$marker = strpos( $source, 'Our commercial refrigeration equipment' );
	$end    = false === $marker ? false : strpos( $source, '<!-- /wp:efb/section -->', $marker );
	// The closing text is the last section (it starts with "Whatever your business needs").
	$tail = strpos( $source, 'Whatever your business needs' );
	$tail = false === $tail ? false : strrpos( substr( $source, 0, $tail ), '<!-- wp:efb/section' );
	if ( false === $end || false === $tail ) {
		WP_CLI::error( 'Home page markers not found – content changed?' );
	}
	$end     += strlen( '<!-- /wp:efb/section -->' );
	$carousel = "\n\n<!-- wp:efb/section {\"bgColor\":\"#eef7f0\",\"paddingTop\":36,\"paddingBottom\":20,\"className\":\"ef-home-ranges\"} -->\n"
		. "<!-- wp:heading {\"className\":\"ef-cc-heading\"} -->\n<h2 class=\"wp-block-heading ef-cc-heading\">Shop by range</h2>\n<!-- /wp:heading -->\n\n"
		. "<!-- wp:efb/range-carousel /-->\n<!-- /wp:efb/section -->";
	// Keep the "Shop by range" section as edited in the editor (e.g. images picked by the client).
	if ( preg_match( '~<!-- wp:efb/section \{[^}]*"className":"ef-home-ranges"\} -->.*?<!-- /wp:efb/section -->~s', $home->post_content, $edited ) ) {
		$carousel = "\n\n" . $edited[0];
	}
	// The top of the page (page-copy/home-top.html): a hero with the H1, the
	// special offers, the intro text beside a photo and the client logos, taken
	// from the original blocks; then "Our commercial refrigeration equipment".
	$head = substr( $source, 0, $end );
	if ( ! preg_match( '~<!-- wp:efb/offer-cards .*?/-->~s', $head, $offers ) || ! preg_match( '~<!-- wp:efb/logo-grid .*?/-->~s', $head, $logos ) ) {
		WP_CLI::error( 'Home page offers or logos not found – content changed?' );
	}
	$offers_end = strpos( $head, $offers[0] ) + strlen( $offers[0] );
	$intro      = trim( substr( $head, $offers_end, strpos( $head, '<!-- /wp:efb/section -->', $offers_end ) - $offers_end ) );
	$eq_start   = strrpos( substr( $head, 0, strpos( $head, 'Our commercial refrigeration equipment' ) ), '<!-- wp:heading' );
	$equipment  = trim( substr( $head, $eq_start, strrpos( $head, '<!-- /wp:efb/section -->' ) - $eq_start ) );
	$head       = strtr(
		$read( 'home-top.html' ),
		array(
			'{{OFFERS}}' => $offers[0],
			'{{INTRO}}'  => $intro,
			'{{LOGOS}}'  => $logos[0],
			'{{RANGES}}' => $carousel, // "Shop by range" right under the offers, on pale green
		)
	)
		. "\n<!-- wp:efb/section {\"paddingTop\":40,\"paddingBottom\":16,\"className\":\"ef-home-equipment\"} -->\n" . $equipment . "\n<!-- /wp:efb/section -->";

	$content = $head . substr( $source, $end, $tail - $end ) . $read( 'home-markets-blog.html' ) . $read( 'home-bottom.html' );
	$save( $home->post_name, $content );
}

WP_CLI::success( 'Pages updated.' );
