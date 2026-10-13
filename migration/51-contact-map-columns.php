<?php
/**
 * 51 — /contact-us/: the company details (heading, address, opening hours,
 * phone / fax / e-mail) on the left and the Google map on the right, as a
 * core Columns block (stacks on mobile).
 *
 * Works on the current page content (it may have been edited in the editor).
 * Idempotent: does nothing once the columns are in place.
 *
 * @package EcoFridgeMigration
 */

$page = get_page_by_path( 'contact-us' );
if ( ! $page ) {
	WP_CLI::error( 'Page not found: contact-us' );
}
$content = $page->post_content;
if ( false !== strpos( $content, 'ef-contact-details' ) ) {
	WP_CLI::success( 'Already in columns.' );
	return;
}

// From the "ECO-Fridge Ltd" heading to the end of the map (Custom HTML block).
$start = strpos( $content, '<!-- wp:heading -->' . "\n" . '<h2 class="wp-block-heading"><a href="/">ECO-Fridge Ltd</a></h2>' );
$map   = false === $start ? false : strpos( $content, '<!-- wp:html -->', $start );
$end   = false === $map ? false : strpos( $content, '<!-- /wp:html -->', $map );
if ( false === $end || false === strpos( substr( $content, $map, $end - $map ), 'google.com/maps' ) ) {
	WP_CLI::error( 'Contact details or map not found – content changed?' );
}
$end += strlen( '<!-- /wp:html -->' );

$details = trim( substr( $content, $start, $map - $start ) );
$map     = trim( substr( $content, $map, $end - $map ) );

$columns = "<!-- wp:columns {\"className\":\"ef-contact-details\"} -->\n"
	. "<div class=\"wp-block-columns ef-contact-details\"><!-- wp:column -->\n<div class=\"wp-block-column\">" . $details . "</div>\n<!-- /wp:column -->\n\n"
	. "<!-- wp:column -->\n<div class=\"wp-block-column\">" . $map . "</div>\n<!-- /wp:column --></div>\n"
	. "<!-- /wp:columns -->";

$content = substr( $content, 0, $start ) . $columns . substr( $content, $end );
wp_update_post( array( 'ID' => $page->ID, 'post_content' => wp_slash( $content ) ) );
WP_CLI::success( 'Contact details and map placed side by side.' );
