<?php
/**
 * 25 — URL parity with the live site + brochures/videos/markets.
 *
 * Requires: plugin ecofridge-blocks active, legacy DB container `ef-legacy-db`
 * running, and source-dump/_tax-urls.txt (the /range|/ranges|/condition|
 * /product-category URLs from the live sitemaps).
 *
 * 1. `_efb_base` term meta on product categories (range | ranges | condition),
 *    taken from the live sitemap URLs → term links = live URLs.
 * 2. `market` taxonomy terms + product relations from the legacy DB.
 * 3. Brochure + video posts (legacy Types fields → `_efb_file_url` / `_efb_youtube_id`).
 * 4. Author slug `eco-fridge` (live /author/eco-fridge/).
 *
 * Idempotent. Run: cp migration/25-legacy-urls-cpts.php source-dump/_run.php &&
 * npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 * then flush rewrite rules in a NEW process (wp rewrite flush).
 *
 * @package EcoFridgeMigration
 */

$db = mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) {
	WP_CLI::error( 'Legacy DB not reachable.' );
}
mysqli_set_charset( $db, 'utf8mb4' );
$LP = '43lz2ypq8_';
$Q  = function ( $sql ) use ( $db ) {
	$r = mysqli_query( $db, $sql );
	if ( ! $r ) {
		WP_CLI::error( mysqli_error( $db ) );
	}
	return mysqli_fetch_all( $r, MYSQLI_ASSOC );
};
$rel = function ( $url ) {
	return preg_replace( '~^https?://(www\.)?(eco-fridge\.co\.uk|eco-fridge\.o2dev\.de)~', '', (string) $url );
};
$att_map = json_decode( (string) file_get_contents( WP_CONTENT_DIR . '/source-dump/_attachment-id-map.json' ), true ) ?: array();
$att     = function ( $legacy_id ) use ( $att_map ) {
	return isset( $att_map[ $legacy_id ] ) ? (int) $att_map[ $legacy_id ] : 0;
};

/* 1. Product category bases ------------------------------------------------ */
$urls  = file( WP_CONTENT_DIR . '/source-dump/_tax-urls.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
$bases = array();
foreach ( $urls as $u ) {
	$parts = array_values( array_filter( explode( '/', $u ) ) );
	$base  = array_shift( $parts );
	$slug  = end( $parts );
	if ( 'product-category' === $base ) {
		$bases[ $slug ] = $bases[ $slug ] ?? '';
		continue;
	}
	// A slug present under several bases (storage-cabinets: range + ranges)
	// keeps the first one listed (range) as canonical; the others still resolve.
	if ( empty( $bases[ $slug ] ) ) {
		$bases[ $slug ] = $base;
	}
}
$set = 0;
foreach ( $bases as $slug => $base ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $t ) {
		WP_CLI::warning( "No product_cat for $slug" );
		continue;
	}
	if ( $base ) {
		update_term_meta( $t->term_id, '_efb_base', $base );
	} else {
		delete_term_meta( $t->term_id, '_efb_base' );
	}
	$set++;
}
WP_CLI::log( "Category bases set: $set" );

/* 2. Markets --------------------------------------------------------------- */
$product_map = array();
foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $pid ) {
	$legacy = get_post_meta( $pid, '_ef_legacy_id', true );
	if ( $legacy ) {
		$product_map[ (int) $legacy ] = $pid;
	}
}
$markets = $Q( "SELECT t.term_id, t.name, t.slug, tt.description FROM {$LP}term_taxonomy tt JOIN {$LP}terms t ON t.term_id = tt.term_id WHERE tt.taxonomy = 'market'" );
$linked  = 0;
foreach ( $markets as $m ) {
	$ex = get_term_by( 'slug', $m['slug'], 'market' );
	$id = $ex ? $ex->term_id : wp_insert_term( $m['name'], 'market', array( 'slug' => $m['slug'], 'description' => $m['description'] ) )['term_id'];
	$objs = $Q( "SELECT tr.object_id FROM {$LP}term_relationships tr JOIN {$LP}term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.term_id = " . (int) $m['term_id'] . " AND tt.taxonomy = 'market'" );
	foreach ( $objs as $o ) {
		if ( isset( $product_map[ (int) $o['object_id'] ] ) ) {
			wp_set_object_terms( $product_map[ (int) $o['object_id'] ], (int) $id, 'market', true );
			$linked++;
		}
	}
}
WP_CLI::log( 'Markets: ' . count( $markets ) . ", product links: $linked" );

/* 3. Brochures + videos ---------------------------------------------------- */
$author = get_user_by( 'login', 'ecofridge' );
$rows   = $Q( "SELECT ID, post_type, post_name, post_title, post_content, post_excerpt, post_date, post_date_gmt FROM {$LP}posts WHERE post_type IN ('brochure','video') AND post_status = 'publish'" );
foreach ( $rows as $r ) {
	$exists = get_posts( array( 'post_type' => $r['post_type'], 'name' => $r['post_name'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	$data   = array(
		'post_type'     => $r['post_type'],
		'post_status'   => 'publish',
		'post_name'     => $r['post_name'],
		'post_title'    => $r['post_title'],
		'post_content'  => $r['post_content'] ? "<!-- wp:paragraph -->\n<p>" . trim( $r['post_content'] ) . "</p>\n<!-- /wp:paragraph -->" : '',
		'post_excerpt'  => $r['post_excerpt'],
		'post_date'     => $r['post_date'],
		'post_date_gmt' => $r['post_date_gmt'],
		'post_author'   => $author ? $author->ID : 1,
	);
	if ( $exists ) {
		$data['ID'] = $exists[0];
	}
	$id = wp_insert_post( wp_slash( $data ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		continue;
	}
	$meta = $Q( "SELECT meta_key, meta_value FROM {$LP}postmeta WHERE post_id = " . (int) $r['ID'] . " AND meta_key IN ('wpcf-brochure-link','wpcf-youtube-id-number','_thumbnail_id','_yoast_wpseo_metadesc')" );
	foreach ( $meta as $mm ) {
		if ( 'wpcf-brochure-link' === $mm['meta_key'] ) {
			update_post_meta( $id, '_efb_file_url', $rel( $mm['meta_value'] ) );
		} elseif ( 'wpcf-youtube-id-number' === $mm['meta_key'] ) {
			update_post_meta( $id, '_efb_youtube_id', $mm['meta_value'] );
		} elseif ( '_thumbnail_id' === $mm['meta_key'] && $att( $mm['meta_value'] ) ) {
			set_post_thumbnail( $id, $att( $mm['meta_value'] ) );
		} elseif ( '_yoast_wpseo_metadesc' === $mm['meta_key'] && ! $r['post_excerpt'] ) {
			wp_update_post( array( 'ID' => $id, 'post_excerpt' => $mm['meta_value'] ) );
		}
	}
	update_post_meta( $id, '_ef_legacy_id', (int) $r['ID'] );
	WP_CLI::log( "{$r['post_type']}: {$r['post_name']} -> $id" );
}

// Live-only brochure (added after the backup): Retarder Prover Manual.
$live_only = array(
	array(
		'slug'  => 'retarder-prover-manual',
		'title' => 'Retarder Prover Manual',
		'date'  => '2026-10-07 09:00:00',
		'image' => 'BK600RP-Open-web-1-scaled.jpg',
	),
);
foreach ( $live_only as $b ) {
	$exists = get_posts( array( 'post_type' => 'brochure', 'name' => $b['slug'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	$id     = $exists ? $exists[0] : wp_insert_post(
		array(
			'post_type'   => 'brochure',
			'post_status' => 'publish',
			'post_name'   => $b['slug'],
			'post_title'  => $b['title'],
			'post_date'   => $b['date'],
			'post_author' => $author ? $author->ID : 1,
		)
	);
	$aid = attachment_url_to_postid( content_url( 'uploads/' . $b['image'] ) );
	if ( ! $aid && file_exists( WP_CONTENT_DIR . '/source-dump/_live-media/' . $b['image'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( $b['image'] );
		copy( WP_CONTENT_DIR . '/source-dump/_live-media/' . $b['image'], $tmp );
		$aid = media_handle_sideload( array( 'name' => $b['image'], 'tmp_name' => $tmp ), $id, $b['title'] );
	}
	if ( $aid && ! is_wp_error( $aid ) ) {
		set_post_thumbnail( $id, $aid );
	}
	WP_CLI::log( "brochure (live-only): {$b['slug']} -> $id" );
}

/* 4. Author slug ------------------------------------------------------------ */
if ( $author && 'eco-fridge' !== $author->user_nicename ) {
	wp_update_user( array( 'ID' => $author->ID, 'user_nicename' => 'eco-fridge' ) );
	WP_CLI::log( 'Author nicename -> eco-fridge' );
}

WP_CLI::success( 'Done. Now run: wp rewrite flush' );
