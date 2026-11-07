<?php
/**
 * Migration step 3: TablePress (186 tables) legacy -> new site.
 * Run: wp eval-file wp-content/source-dump/_run.php
 *
 * TablePress model: post_type `tablepress_table`, post_content = 2D JSON array,
 * meta _tablepress_table_options / _tablepress_table_visibility (JSON),
 * the `tablepress_tables` option = {"last_id":N,"table_post":{table_id:post_id}}.
 * The products' `[table id=X /]` shortcodes use table_id, so it must be kept.
 *
 * JSON care: post_content is written straight through $wpdb (no kses/unslash);
 * meta are written with wp_slash() (the Meta API unslashes internally).
 * Idempotent through meta _ef_legacy_id.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! post_type_exists( 'tablepress_table' ) ) { WP_CLI::error( 'TablePress is not active (post type missing).' ); }

global $wpdb;
$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Legacy DB: ' . mysqli_connect_error() ); }
mysqli_set_charset( $db, 'utf8mb4' );

// 1. the table_id -> legacy_post_id mapping option
$opt_res = mysqli_query( $db, "SELECT option_value FROM {$LP}options WHERE option_name='tablepress_tables'" );
$opt_row = $opt_res ? mysqli_fetch_row( $opt_res ) : null;
if ( ! $opt_row ) { WP_CLI::error( 'The tablepress_tables option is missing in the legacy DB.' ); }
$legacy_map = json_decode( $opt_row[0], true );
$last_id     = isset( $legacy_map['last_id'] ) ? (int) $legacy_map['last_id'] : 0;
$table_post  = isset( $legacy_map['table_post'] ) ? $legacy_map['table_post'] : array();
// inverted: legacy_post_id => table_id
$post_to_tableid = array();
foreach ( $table_post as $tid => $pid ) { $post_to_tableid[ (int) $pid ] = (string) $tid; }

// 2. every TablePress post + its meta
$res = mysqli_query( $db, "SELECT ID, post_title, post_name, post_content, post_excerpt, post_date, post_date_gmt
                           FROM {$LP}posts WHERE post_type='tablepress_table' AND post_status='publish' ORDER BY ID" );
$legacy_to_new = array();
$created = 0; $skipped = 0;

while ( $row = mysqli_fetch_assoc( $res ) ) {
	$old_id = (int) $row['ID'];

	// idempotency
	$exist = get_posts( array( 'post_type' => 'tablepress_table', 'post_status' => 'any', 'meta_key' => '_ef_legacy_id', 'meta_value' => $old_id, 'fields' => 'ids', 'posts_per_page' => 1 ) );
	if ( $exist ) { $legacy_to_new[ $old_id ] = (int) $exist[0]; $skipped++; continue; }

	// meta from the legacy DB
	$mr = mysqli_query( $db, "SELECT meta_key, meta_value FROM {$LP}postmeta WHERE post_id=$old_id AND meta_key IN ('_tablepress_table_options','_tablepress_table_visibility')" );
	$lmeta = array();
	while ( $m = mysqli_fetch_assoc( $mr ) ) { $lmeta[ $m['meta_key'] ] = $m['meta_value']; }

	$date = ( $row['post_date'] && '0000-00-00 00:00:00' !== $row['post_date'] ) ? $row['post_date'] : current_time( 'mysql' );

	// insert with empty content (written exactly through $wpdb afterwards)
	$new_id = wp_insert_post( array(
		'post_type'    => 'tablepress_table',
		'post_status'  => 'publish',
		'post_title'   => $row['post_title'],
		'post_name'    => $row['post_name'],
		'post_excerpt' => $row['post_excerpt'],
		'post_content' => '',
		'post_date'    => $date,
		'post_author'  => 1,
	), true );

	if ( is_wp_error( $new_id ) ) { WP_CLI::warning( "table $old_id: " . $new_id->get_error_message() ); continue; }
	$new_id = (int) $new_id;

	// post_content = the exact JSON (bypasses kses/unslash)
	$wpdb->update( $wpdb->posts, array( 'post_content' => $row['post_content'] ), array( 'ID' => $new_id ) );
	clean_post_cache( $new_id );

	// meta JSON (wp_slash so the Meta API keeps the escapes \/ \")
	foreach ( $lmeta as $k => $v ) { update_post_meta( $new_id, $k, wp_slash( $v ) ); }
	update_post_meta( $new_id, '_ef_legacy_id', $old_id );

	$legacy_to_new[ $old_id ] = $new_id;
	$created++;
}

// 3. rebuild the tablepress_tables option with the new IDs (table_id kept)
$new_table_post = array();
foreach ( $post_to_tableid as $legacy_pid => $tid ) {
	if ( isset( $legacy_to_new[ $legacy_pid ] ) ) { $new_table_post[ $tid ] = $legacy_to_new[ $legacy_pid ]; }
}
$new_option = wp_json_encode( array( 'last_id' => $last_id, 'table_post' => (object) $new_table_post ) );
update_option( 'tablepress_tables', $new_option );

WP_CLI::log( "Tables created: $created | skipped (already there): $skipped | table_id mapped: " . count( $new_table_post ) );
WP_CLI::success( 'TablePress migration finished.' );
