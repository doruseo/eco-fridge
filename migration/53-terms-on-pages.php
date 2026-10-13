<?php
/**
 * 53 — Terms quoted on other pages brought in line with /terms-conditions/.
 *
 *  - /contact-us/: the original site's terms (one long paragraph saying all goods
 *    are sold with no warranty and returns are never accepted) replaced by a
 *    corrected summary that opens with a link to the Terms & Conditions page:
 *    the optional 12-month parts warranty (+15%) and the current returns rule
 *    (page-copy/contact-terms.html);
 *  - /returns/ and /eco-fridge/ (the original "Returns" page): the returns rule
 *    reworded, with a link to the terms and the warranty option
 *    (page-copy/returns.html);
 *  - the article that says "terms and conditions apply" links them.
 *
 * Works on the current page content; the previous content is kept in the
 * `_ef_content_before_53` post meta. Idempotent. Run via source-dump/_run.php
 * after copying migration/page-copy/ to source-dump/_page-copy/.
 *
 * @package EcoFridgeMigration
 */

$dir = WP_CONTENT_DIR . '/source-dump/_page-copy';
if ( ! is_dir( $dir ) ) {
	WP_CLI::error( "Copy migration/page-copy to $dir first." );
}
$read = static fn( $file ) => trim( (string) file_get_contents( $dir . '/' . $file ) );

$save = static function ( WP_Post $post, $content ) {
	if ( $content === $post->post_content ) {
		WP_CLI::log( "Unchanged: {$post->post_name}" );
		return;
	}
	if ( '' === (string) get_post_meta( $post->ID, '_ef_content_before_53', true ) ) {
		update_post_meta( $post->ID, '_ef_content_before_53', wp_slash( $post->post_content ) );
	}
	wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $content ) ) );
	WP_CLI::log( "Updated: {$post->post_name}" );
};

// Contact: from the "TERMS & CONDITIONS" paragraph to the end of its section.
$contact = get_page_by_path( 'contact-us' );
if ( $contact ) {
	$content = $contact->post_content;
	$start   = strpos( $content, "<!-- wp:paragraph -->\n<p>TERMS &amp; CONDITIONS</p>" );
	if ( false === $start ) {
		$start = strpos( $content, '<!-- wp:heading {"className":"ef-contact-terms"} -->' );
	}
	$end = false === $start ? false : strpos( $content, '<!-- /wp:efb/section -->', $start );
	if ( false === $end ) {
		WP_CLI::warning( 'Contact page: terms not found – content changed?' );
	} else {
		$save( $contact, substr( $content, 0, $start ) . $read( 'contact-terms.html' ) . "\n" . substr( $content, $end ) );
	}
}

// Returns pages: the section's inner content.
foreach ( array( 'returns', 'eco-fridge' ) as $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		continue;
	}
	if ( ! preg_match( '~^(\s*<!-- wp:efb/section [^>]*-->)(.*)(<!-- /wp:efb/section -->\s*)$~s', $page->post_content, $m ) ) {
		WP_CLI::warning( "/$slug/: section not found – content changed?" );
		continue;
	}
	$save( $page, $m[1] . "\n" . $read( 'returns.html' ) . "\n" . $m[3] );
}

// "terms and conditions apply" in an article → a link to the terms.
$article = get_page_by_path( 'frost-tech-silver-steel-multideck-fridges', OBJECT, 'post' );
if ( $article ) {
	$save( $article, str_replace( '(terms and conditions apply)', '(<a href="/terms-conditions/">terms and conditions</a> apply)', $article->post_content ) );
}

WP_CLI::success( 'Terms on pages updated.' );
