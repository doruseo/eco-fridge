<?php
/**
 * 31 — Warranty price tables, hidden behind a click.
 *
 * On the original site every product with an optional warranty had a second
 * Types field `spec-table-2` pointing to a TablePress "… Warranty" table
 * (prices with the warranty included). Warranty is only offered on request,
 * so the table must be available but collapsed: it is added to the product
 * description as an efb/toggle block (closed by default) holding an
 * efb/spec-table, right after the product's last spec table.
 *
 * Source: the legacy DB (`ef-legacy-db`), so the data is the original table
 * with TablePress' hidden rows/columns and all-empty spacer columns removed.
 *
 * Idempotent: a previously inserted warranty toggle is replaced.
 * Run via source-dump/_run.php (needs _lib-html-to-blocks.php).
 *
 * @package EcoFridgeMigration
 */

require_once WP_CONTENT_DIR . '/source-dump/_lib-html-to-blocks.php';

$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
if ( ! $db ) {
	WP_CLI::error( 'Legacy DB not reachable (container ef-legacy-db).' );
}
mysqli_set_charset( $db, 'utf8mb4' );
$px = '43lz2ypq8_';

$q = function ( $sql ) use ( $db ) {
	$res = mysqli_query( $db, $sql );
	return $res ? mysqli_fetch_all( $res, MYSQLI_ASSOC ) : array();
};

$tp     = $q( "SELECT option_value FROM {$px}options WHERE option_name = 'tablepress_tables'" );
$tp_map = json_decode( $tp[0]['option_value'] ?? '', true )['table_post'] ?? array();

$rows = $q(
	"SELECT p.post_name, m.meta_value FROM {$px}postmeta m
	 JOIN {$px}posts p ON p.ID = m.post_id
	 WHERE m.meta_key = 'wpcf-spec-table-2' AND p.post_type = 'product' AND m.meta_value LIKE '%[table id=%'"
);

/**
 * Legacy TablePress table → rows of cells (visible only, no empty columns).
 */
$load_table = function ( $tid ) use ( $q, $px, $tp_map ) {
	$pid = (int) ( $tp_map[ $tid ] ?? 0 );
	if ( ! $pid ) {
		return array();
	}
	$post = $q( "SELECT post_title, post_content FROM {$px}posts WHERE ID = $pid" );
	$vis  = $q( "SELECT meta_value FROM {$px}postmeta WHERE post_id = $pid AND meta_key = '_tablepress_table_visibility'" );
	$data = json_decode( $post[0]['post_content'] ?? '', true );
	$vis  = json_decode( $vis[0]['meta_value'] ?? '', true );
	if ( ! $data || ! preg_match( '~warranty$~i', trim( $post[0]['post_title'] ) ) ) {
		return array();
	}
	$out = array();
	foreach ( $data as $r => $row ) {
		if ( isset( $vis['rows'][ $r ] ) && ! $vis['rows'][ $r ] ) {
			continue;
		}
		$cells = array();
		foreach ( $row as $c => $cell ) {
			if ( isset( $vis['columns'][ $c ] ) && ! $vis['columns'][ $c ] ) {
				continue;
			}
			$cells[ $c ] = efm_relative_urls( trim( $cell ) );
		}
		$out[] = $cells;
	}
	// Drop spacer columns that are empty in every row.
	foreach ( array_keys( $out[0] ?? array() ) as $c ) {
		if ( '' === implode( '', array_column( $out, $c ) ) ) {
			foreach ( $out as &$cells ) {
				unset( $cells[ $c ] );
			}
			unset( $cells );
		}
	}
	return array_map( 'array_values', $out );
};

$title = 'Warranty';
$stats = array( 'added' => 0, 'missing' => 0, 'empty' => 0 );
foreach ( $rows as $r ) {
	if ( ! preg_match( '~\[table id=(\d+)~', $r['meta_value'], $m ) ) {
		continue;
	}
	$post = get_page_by_path( $r['post_name'], OBJECT, 'product' );
	if ( ! $post ) {
		$stats['missing']++;
		WP_CLI::warning( "Missing locally: {$r['post_name']}" );
		continue;
	}
	$table = $load_table( (int) $m[1] );
	if ( count( $table ) < 2 ) {
		$stats['empty']++;
		WP_CLI::warning( "No warranty table data: {$r['post_name']} (table {$m[1]})" );
		continue;
	}
	$head   = array_shift( $table );
	$toggle = efm_block(
		'efb/toggle',
		array( 'title' => $title, 'className' => 'efb-toggle--warranty' ),
		efm_block( 'efb/spec-table', array( 'head' => $head, 'rows' => $table ) )
	);

	// Replace an earlier run's toggle, then insert after the last spec table.
	// ("--" in the class is stored unicode-escaped by serialize_block_attributes().)
	$content = preg_replace( '~<!-- wp:efb/toggle \{[^}]*efb-toggle(?:--|\\\\u002d\\\\u002d)warranty.*?<!-- /wp:efb/toggle -->\n?~s', '', $post->post_content );
	$last    = strrpos( $content, '<!-- wp:efb/spec-table ' );
	if ( false !== $last ) {
		$end     = strpos( $content, '/-->', $last ) + 4;
		$content = substr( $content, 0, $end ) . "\n\n" . $toggle . substr( $content, $end );
	} else {
		$content = rtrim( $content ) . "\n\n" . $toggle;
	}
	wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $content ) ) );
	$stats['added']++;
}
mysqli_close( $db );
WP_CLI::success( sprintf( 'Warranty toggles: %d added, %d products missing locally, %d empty tables.', $stats['added'], $stats['missing'], $stats['empty'] ) );
