<?php
/**
 * Pasul 9: migrează blogul (107 postări) din legacy în posturi native.
 * Rulare: cp migration/09-blog.php source-dump/_run.php
 *         npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * Fiecare post legacy -> post nativ cu același slug/titlu/dată/conținut.
 * Categoria „Blog" + taguri recreate. Featured image remapat prin
 * _attachment-id-map.json. Yoast metadesc -> excerpt. URL-uri o2dev/co.uk
 * -> relative (imaginile există deja local din migrarea media). Idempotent
 * după _ef_legacy_id. Autor unic „ECO-Fridge". Test post gol exclus.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Legacy DB: ' . mysqli_connect_error() ); }
mysqli_set_charset( $db, 'utf8mb4' );

// admin -> unfiltered_html, ca să nu se filtreze iframe/style/caption la insert
wp_set_current_user( 1 );

// harta atașamentelor (legacy id -> new id)
$map_file = WP_CONTENT_DIR . '/source-dump/_attachment-id-map.json';
$att_map  = file_exists( $map_file ) ? json_decode( file_get_contents( $map_file ), true ) : array();
if ( ! is_array( $att_map ) ) { $att_map = array(); }

// autor unic „ECO-Fridge"
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
	if ( is_wp_error( $author_id ) ) { WP_CLI::error( 'Autor: ' . $author_id->get_error_message() ); }
}

// categoria Blog
$blog_cat = get_term_by( 'slug', 'blog', 'category' );
if ( ! $blog_cat ) {
	$t = wp_insert_term( 'Blog', 'category', array( 'slug' => 'blog' ) );
	$blog_cat_id = is_wp_error( $t ) ? 0 : (int) $t['term_id'];
} else {
	$blog_cat_id = (int) $blog_cat->term_id;
}

// curăță conținutul: domenii -> relativ (imaginile există local), fără trailing gunk
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

// posturi publicate, cu conținut (exclude test post gol)
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

	// taguri legacy (slug + name)
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

	// idempotent după _ef_legacy_id
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

	// categorie Blog
	if ( $blog_cat_id ) { wp_set_object_terms( $new_id, array( $blog_cat_id ), 'category', false ); }

	// taguri
	if ( $tags ) { wp_set_object_terms( $new_id, $tags, 'post_tag', false ); $tag_count += count( $tags ); }

	// featured image remapat
	if ( $thumb_old && isset( $att_map[ (string) $thumb_old ] ) ) {
		$new_thumb = (int) $att_map[ (string) $thumb_old ];
		if ( get_post( $new_thumb ) ) { set_post_thumbnail( $new_id, $new_thumb ); $with_thumb++; }
	}

	// layout Astra ca la pagini
	update_post_meta( $new_id, 'site-sidebar-layout', 'no-sidebar' );
	update_post_meta( $new_id, 'site-content-layout', 'plain-container' );
}

WP_CLI::log( "Blog: create=$created, actualizate=$updated, cu featured image=$with_thumb, relații taguri=$tag_count" );
WP_CLI::success( 'Migrare blog finalizată.' );
