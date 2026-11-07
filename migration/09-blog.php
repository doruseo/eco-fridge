<?php
/**
 * Step 9: migrates the blog (107 posts) from the legacy DB into native posts.
 * Run: cp migration/09-blog.php source-dump/_run.php
 *         npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * Each legacy post -> a native post with the same slug/title/date/content.
 * The "Blog" category and tags are recreated. Featured image remapped through
 * _attachment-id-map.json. Yoast metadesc -> excerpt. o2dev/co.uk URLs
 * -> relative (the images already exist locally from the media migration). Idempotent
 * by _ef_legacy_id. Single author "ECO-Fridge". The empty test post is excluded.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Legacy DB: ' . mysqli_connect_error() ); }
mysqli_set_charset( $db, 'utf8mb4' );

// admin -> unfiltered_html, so iframe/style/caption are not filtered on insert
wp_set_current_user( 1 );

// attachment map (legacy id -> new id)
$map_file = WP_CONTENT_DIR . '/source-dump/_attachment-id-map.json';
$att_map  = file_exists( $map_file ) ? json_decode( file_get_contents( $map_file ), true ) : array();
if ( ! is_array( $att_map ) ) { $att_map = array(); }

// single author "ECO-Fridge"
$author_id = username_exists( 'ecofridge' );
if ( ! $author_id ) {
	$author_id = wp_insert_user( array(
		'user_login'   => 'ecofridge',
		'user_pass'    => wp_generate_password( 24 ),
		'display_name' => 'ECO-Fridge',
		'nickname'     => 'ECO-Fridge',
		'first_name'   => 'ECO-Fridge',
		'role'         => 'author',
	) );
	if ( is_wp_error( $author_id ) ) { WP_CLI::error( 'Author: ' . $author_id->get_error_message() ); }
}

// the Blog category
$blog_cat = get_term_by( 'slug', 'blog', 'category' );
if ( ! $blog_cat ) {
	$t = wp_insert_term( 'Blog', 'category', array( 'slug' => 'blog' ) );
	$blog_cat_id = is_wp_error( $t ) ? 0 : (int) $t['term_id'];
} else {
	$blog_cat_id = (int) $blog_cat->term_id;
}

// clean the content: domains -> relative (images exist locally), no trailing junk
$clean = function ( $html ) {
	$html = str_replace(
		array(
			'https://eco-fridge.o2dev.de', 'http://eco-fridge.o2dev.de', 'https://www.eco-fridge.o2dev.de', 'http://www.eco-fridge.o2dev.de',
			'https://eco-fridge.co.uk', 'http://eco-fridge.co.uk', 'https://www.eco-fridge.co.uk', 'http://www.eco-fridge.co.uk',
		),
		'',
		$html
	);
	return $html;
};

// published posts with content (the empty test post is excluded)
$res = mysqli_query( $db, "SELECT ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_name, post_modified, post_modified_gmt
	FROM {$LP}posts
	WHERE post_type='post' AND post_status='publish' AND TRIM(post_content) <> ''
	ORDER BY post_date ASC" );

$created = 0; $updated = 0; $with_thumb = 0; $tag_count = 0;

while ( $row = mysqli_fetch_assoc( $res ) ) {
	$old_id = (int) $row['ID'];

	// meta: thumbnail + yoast metadesc
	$thumb_old = 0; $metadesc = '';
	$mr = mysqli_query( $db, "SELECT meta_key, meta_value FROM {$LP}postmeta WHERE post_id=$old_id AND meta_key IN ('_thumbnail_id','_yoast_wpseo_metadesc')" );
	while ( $m = mysqli_fetch_assoc( $mr ) ) {
		if ( '_thumbnail_id' === $m['meta_key'] ) { $thumb_old = (int) $m['meta_value']; }
		if ( '_yoast_wpseo_metadesc' === $m['meta_key'] ) { $metadesc = $m['meta_value']; }
	}

	// legacy tags (slug + name)
	$tags = array();
	$tr = mysqli_query( $db, "SELECT t.name FROM {$LP}term_relationships r
		JOIN {$LP}term_taxonomy tt ON tt.term_taxonomy_id=r.term_taxonomy_id AND tt.taxonomy='post_tag'
		JOIN {$LP}terms t ON t.term_id=tt.term_id
		WHERE r.object_id=$old_id" );
	while ( $t = mysqli_fetch_assoc( $tr ) ) { $tags[] = $t['name']; }

	$content = call_user_func( $clean, $row['post_content'] );
	$excerpt = trim( $row['post_excerpt'] );
	if ( '' === $excerpt && '' !== trim( (string) $metadesc ) ) { $excerpt = trim( $metadesc ); }

	$postarr = array(
		'post_type'         => 'post',
		'post_status'       => 'publish',
		'post_title'        => $row['post_title'],
		'post_name'         => $row['post_name'],
		'post_content'      => $content,
		'post_excerpt'      => $excerpt,
		'post_author'       => $author_id,
		'post_date'         => $row['post_date'],
		'post_date_gmt'     => $row['post_date_gmt'],
		'post_modified'     => $row['post_modified'],
		'post_modified_gmt' => $row['post_modified_gmt'],
		'comment_status'    => 'closed',
		'ping_status'       => 'closed',
	);

	// idempotent by _ef_legacy_id
	$existing = get_posts( array(
		'post_type'   => 'post',
		'post_status' => 'any',
		'numberposts' => 1,
		'fields'      => 'ids',
		'meta_key'    => '_ef_legacy_id',
		'meta_value'  => $old_id,
	) );

	if ( $existing ) {
		$postarr['ID'] = $existing[0];
		$new_id = wp_update_post( wp_slash( $postarr ), true );
		$updated++;
	} else {
		$new_id = wp_insert_post( wp_slash( $postarr ), true );
		$created++;
	}
	if ( is_wp_error( $new_id ) ) { WP_CLI::warning( "post $old_id: " . $new_id->get_error_message() ); continue; }

	update_post_meta( $new_id, '_ef_legacy_id', $old_id );

	// Blog category
	if ( $blog_cat_id ) { wp_set_object_terms( $new_id, array( $blog_cat_id ), 'category', false ); }

	// tags
	if ( $tags ) { wp_set_object_terms( $new_id, $tags, 'post_tag', false ); $tag_count += count( $tags ); }

	// remapped featured image
	if ( $thumb_old && isset( $att_map[ (string) $thumb_old ] ) ) {
		$new_thumb = (int) $att_map[ (string) $thumb_old ];
		if ( get_post( $new_thumb ) ) { set_post_thumbnail( $new_id, $new_thumb ); $with_thumb++; }
	}

	// Astra layout as on pages
	update_post_meta( $new_id, 'site-sidebar-layout', 'no-sidebar' );
	update_post_meta( $new_id, 'site-content-layout', 'plain-container' );
}

WP_CLI::log( "Blog: created=$created, updated=$updated, with featured image=$with_thumb, tag relations=$tag_count" );
WP_CLI::success( 'Blog migration finished.' );
