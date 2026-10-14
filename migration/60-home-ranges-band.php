<?php
/**
 * 60 — Home page: "Special offers" and "Shop by range" read as two sections.
 *
 *  - Special offers stays without a heading (white, red offer labels), with
 *    more room below the cards (60px, 48px on mobile).
 *  - Shop by range becomes a dark green band (the category menu bar colour)
 *    with a left-aligned heading and a
 *    "View all products" link on the right. The cards (white, no green border,
 *    number of models, arrow) are styled in astra-child/assets/pages.css.
 *
 * The page before this migration is kept in the option ef_home_before_60.
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

$home_id = (int) get_option( 'page_on_front' );
$home    = $home_id ? get_post( $home_id ) : null;
if ( ! $home ) {
	echo "No front page.\n";
	return;
}
$content = $home->post_content;

if ( false === get_option( 'ef_home_before_60' ) ) {
	update_option( 'ef_home_before_60', $content, false );
}

// Special offers: no heading of its own (removed on request after a first
// version added "Special offers" + a sub-line).
$content = preg_replace(
	'~<!-- wp:heading \{"className":"ef-home-offers__title"\} -->.*?<!-- /wp:heading -->\s*<!-- wp:paragraph \{"className":"ef-home-offers__sub"\} -->.*?<!-- /wp:paragraph -->\s*~s',
	'',
	$content
);

// Special offers: more room below the cards.
$content = preg_replace(
	'~(<!-- wp:efb/section \{[^}]*)"paddingBottom":\d+,"paddingTopMobile":(\d+),"paddingBottomMobile":\d+(,"className":"ef-home-section ef-home-offers"\} -->)~',
	'$1"paddingBottom":60,"paddingTopMobile":$2,"paddingBottomMobile":48$3',
	$content,
	1
);

// Shop by range: no lead line under the heading (removed on request).
$content = preg_replace(
	'~<!-- /wp:heading -->\s*<!-- wp:paragraph \{"className":"ef-home-ranges__lead"\} -->.*?<!-- /wp:paragraph --></div>~s',
	'<!-- /wp:heading --></div>',
	$content
);

// Shop by range: no eyebrow above the heading (removed on request).
$content = preg_replace(
	'~<!-- wp:paragraph \{"className":"ef-home-ranges__eyebrow"\} -->.*?<!-- /wp:paragraph -->\s*~s',
	'',
	$content
);

// Shop by range: dark green band + header.
$section_open = '<!-- wp:efb/section {"bgColor":"#18673a","paddingTop":60,"paddingBottom":40,"paddingTopMobile":44,"paddingBottomMobile":28,"className":"ef-home-ranges"} -->';
$header       = '<!-- wp:group {"className":"ef-home-ranges__head","layout":{"type":"default"}} -->' . "\n"
	. '<div class="wp-block-group ef-home-ranges__head"><!-- wp:group {"className":"ef-home-ranges__intro","layout":{"type":"default"}} -->' . "\n"
	. '<div class="wp-block-group ef-home-ranges__intro"><!-- wp:heading {"className":"ef-cc-heading"} -->' . "\n"
	. '<h2 class="wp-block-heading ef-cc-heading">Shop by range</h2>' . "\n"
	. '<!-- /wp:heading --></div>' . "\n"
	. '<!-- /wp:group -->' . "\n\n"
	. '<!-- wp:paragraph {"className":"ef-home-ranges__all"} -->' . "\n"
	. '<p class="ef-home-ranges__all"><a href="/shop/">View all products</a></p>' . "\n"
	. '<!-- /wp:paragraph --></div>' . "\n"
	. '<!-- /wp:group -->';

if ( false === strpos( $content, 'ef-home-ranges__head' ) ) {
	$content = preg_replace(
		'~<!-- wp:efb/section \{[^}]*"className":"ef-home-ranges"\} -->\n<!-- wp:heading \{"className":"ef-cc-heading"\} -->\n<h2 class="wp-block-heading ef-cc-heading">Shop by range</h2>\n<!-- /wp:heading -->~',
		$section_open . "\n" . $header,
		$content,
		1,
		$count
	);
	echo $count ? "Shop by range header added\n" : "Shop by range section NOT FOUND\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

if ( $content !== $home->post_content ) {
	wp_update_post( array( 'ID' => $home_id, 'post_content' => wp_slash( $content ) ) );
	echo "Home page updated\n";
} else {
	echo "No change\n";
}
