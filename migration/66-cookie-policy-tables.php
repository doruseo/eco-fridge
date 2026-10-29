<?php
/**
 * 66 — Cookie policy: the cookie tables come from EF Cookie Consent.
 *
 * On "Cookie policy (UK)" (/cookie-policy-uk/):
 *  - section 3 ("The cookies we use"): the two hand-written tables (Strictly
 *    necessary, Analytics) and their headings become the plugin's
 *    [efcc_cookie_table] – every cookie found by the scan and declared by the
 *    enabled services, grouped by category, kept up to date automatically.
 *    The card-payment and Google Analytics paragraphs stay.
 *  - section 5 ("Your choices"): choices are changed with the cookie button /
 *    [efcc_settings_button], no longer by clearing browser cookies.
 *
 * The previous content is kept once in post meta `_ef_content_before_66`.
 * Stops without changing anything if the page doesn't look as expected; safe
 * to run again.
 *
 * Run: wp eval-file migration/66-cookie-policy-tables.php
 *
 * @package EcoFridgeMigration
 */

$page = get_page_by_path( 'cookie-policy-uk' );
if ( ! $page ) {
	WP_CLI::error( 'Page cookie-policy-uk not found.' );
}
$content = $page->post_content;
if ( false !== strpos( $content, '[efcc_cookie_table' ) ) {
	WP_CLI::success( 'Already uses [efcc_cookie_table]: nothing to do.' );
	return;
}

// Section 3: from the "Strictly necessary" heading to the end of the second table.
$tables = '#<!-- wp:heading \{"level":3\} -->\s*<h3 class="wp-block-heading">Strictly necessary</h3>\s*<!-- /wp:heading -->\s*'
	. '(<!-- wp:table \{"className":"ef-legal-table"\} -->.*?<!-- /wp:table -->)\s*'
	. '(<!-- wp:paragraph -->\s*<p>When you pay by card.*?<!-- /wp:paragraph -->)\s*'
	. '<!-- wp:heading \{"level":3\} -->\s*<h3 class="wp-block-heading">Analytics \(only with your consent\)</h3>\s*<!-- /wp:heading -->\s*'
	. '<!-- wp:table \{"className":"ef-legal-table"\} -->.*?<!-- /wp:table -->#s';
if ( 1 !== preg_match( $tables, $content, $m ) ) {
	WP_CLI::error( 'Section 3 does not match the expected tables: nothing changed.' );
}
$new3 = "<!-- wp:paragraph -->\n<p>The tables below list every cookie used on this website, grouped by category: who sets it, what it is for and how long it lasts. They are generated from a regular scan of the website, so they stay up to date.</p>\n<!-- /wp:paragraph -->\n\n"
	. "<!-- wp:shortcode -->\n[efcc_cookie_table]\n<!-- /wp:shortcode -->\n\n"
	. $m[2];

// Section 5: how to change your mind.
$old5 = 'We ask for your consent before setting analytics cookies, and you can change your mind at any time by clearing the cookies for our site in your browser – we will then ask again on your next visit.';
if ( false === strpos( $content, $old5 ) ) {
	WP_CLI::error( 'Section 5 text not found: nothing changed.' );
}
$new5 = 'We ask for your consent before setting any cookies that are not strictly necessary. You can change or withdraw your consent at any time with the cookie button in the bottom-left corner of every page, or with this button:';

$updated = preg_replace( $tables, $new3, $content, 1 );
$updated = str_replace(
	'<p>' . $old5,
	'<p>' . $new5,
	$updated
);
// The button right after that paragraph.
$updated = preg_replace(
	'#(<p>' . preg_quote( $new5, '#' ) . '.*?</p>\s*<!-- /wp:paragraph -->)#s',
	"$1\n\n<!-- wp:shortcode -->\n[efcc_settings_button]\n<!-- /wp:shortcode -->",
	$updated,
	1
);

if ( '' === (string) get_post_meta( $page->ID, '_ef_content_before_66', true ) ) {
	update_post_meta( $page->ID, '_ef_content_before_66', wp_slash( $content ) );
}
global $wpdb;
$wpdb->update( $wpdb->posts, array( 'post_content' => $updated, 'post_modified' => current_time( 'mysql' ), 'post_modified_gmt' => current_time( 'mysql', true ) ), array( 'ID' => $page->ID ) );
clean_post_cache( $page->ID );
WP_CLI::success( 'Cookie policy now uses the plugin\'s cookie tables and settings button.' );
