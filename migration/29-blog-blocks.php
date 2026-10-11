<?php
/**
 * 29 — Blog posts: single "Classic" block → native Gutenberg blocks
 * (paragraphs, headings, lists, images with captions, quotes).
 *
 * The text was verified identical to the live site (migration/tools +
 * _live-content.json). Each post is converted only if the rendered text stays
 * the same (≥ 99.5% similar) — otherwise it is left untouched and reported.
 * Links to the live/legacy domains become root-relative.
 *
 * DRY RUN by default; set EFM_APPLY=1 to write:
 *   npx wp-env run cli env EFM_APPLY=1 wp eval-file wp-content/source-dump/_run.php
 *
 * @package EcoFridgeMigration
 */

require_once WP_CONTENT_DIR . '/source-dump/_lib-html-to-blocks.php';

$apply = (bool) getenv( 'EFM_APPLY' );
$norm  = function ( $html ) {
	$t = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$t = str_replace( array( "\xc2\xa0", '’', '‘', '“', '”', '–', '—', '″', '…' ), array( ' ', "'", "'", '"', '"', '-', '-', '"', '...' ), $t );
	return trim( preg_replace( '~\s+~u', ' ', $t ) );
};

$done   = 0;
$skip   = array();
$posts  = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1 ) );
foreach ( $posts as $p ) {
	if ( false !== strpos( $p->post_content, '<!-- wp:' ) ) {
		continue; // Already blocks.
	}
	$blocks = efm_html_to_blocks( wpautop( efm_captions_to_figures( $p->post_content ) ) );
	$old    = $norm( do_shortcode( wpautop( $p->post_content ) ) );
	$new    = $norm( do_blocks( $blocks ) );
	similar_text( $old, $new, $pct );
	if ( $pct < 99.5 ) {
		$skip[] = sprintf( '%s (%.1f%%)', $p->post_name, $pct );
		continue;
	}
	if ( $apply ) {
		wp_update_post( array( 'ID' => $p->ID, 'post_content' => wp_slash( $blocks ) ) );
	}
	$done++;
}
WP_CLI::log( ( $apply ? 'Converted' : 'Would convert' ) . ": $done / " . count( $posts ) );
if ( $skip ) {
	WP_CLI::warning( 'Skipped (text would change): ' . implode( ', ', $skip ) );
}
