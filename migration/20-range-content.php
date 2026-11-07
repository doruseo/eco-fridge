<?php
/**
 * Step 20: imports the LONG-FORM COPY of the categories/ranges from the original
 * eco-fridge (Toolset Types field `wpcf-range-content-2` on the legacy terms), 1:1 with
 * the live site. NO crawl needed: the content is exactly in the legacy DB, and all
 * 127 images it references already exist in the synced media.
 *
 * Stores it in term meta `_ef_range_content` on our product_cat categories
 * (mapped by slug), with absolute URLs rewritten as relative (portable to any domain).
 * Rendering (below the product grid) and editing are done by the child theme (inc/category-content.php).
 *
 * Idempotent. Needs the `ef-legacy-db` container running (see the resume notes).
 * Run: cp migration/20-range-content.php source-dump/_run.php &&
 *   npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Cannot connect to the legacy DB (start the ef-legacy-db container).' ); }
mysqli_set_charset( $db, 'utf8mb4' );

$res = mysqli_query( $db,
	"SELECT t.slug, tm.meta_value
	 FROM {$LP}termmeta tm
	 JOIN {$LP}terms t ON t.term_id = tm.term_id
	 WHERE tm.meta_key = 'wpcf-range-content-2' AND CHAR_LENGTH(tm.meta_value) > 50"
);
if ( ! $res ) { WP_CLI::error( 'Legacy query failed: ' . mysqli_error( $db ) ); }

/** Rewrites absolute URLs (live + copy) as relative ones → portable to any domain. */
function ef_rewrite_urls( $html ) {
	return str_replace(
		array(
			'https://www.eco-fridge.co.uk', 'http://www.eco-fridge.co.uk',
			'https://eco-fridge.co.uk', 'http://eco-fridge.co.uk',
			'https://eco-fridge.o2dev.de', 'http://eco-fridge.o2dev.de',
		),
		'',
		(string) $html
	);
}

$imported = 0; $skipped = array(); $chars = 0;
while ( $row = mysqli_fetch_assoc( $res ) ) {
	$slug    = $row['slug'];
	$content = trim( (string) $row['meta_value'] );
	if ( '' === $content ) { continue; }

	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) { $skipped[] = $slug; continue; }

	$content = ef_rewrite_urls( $content );
	update_term_meta( $term->term_id, '_ef_range_content', $content );
	$imported++;
	$chars += strlen( $content );
}
mysqli_close( $db );

WP_CLI::log( "Category copy imported: $imported terms (" . number_format( $chars ) . ' characters in total).' );
if ( $skipped ) {
	WP_CLI::warning( 'Legacy slugs without a matching product_cat (' . count( $skipped ) . '): ' . implode( ', ', $skipped ) );
}
WP_CLI::success( 'Long-form category copy imported (term meta _ef_range_content).' );
