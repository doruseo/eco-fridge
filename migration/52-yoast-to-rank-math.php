<?php
/**
 * 52 — Yoast SEO Premium (+ Yoast WooCommerce SEO, Simple 301 Redirects) → Rank Math.
 *
 * The original site ran Yoast; this site never had it, so the Yoast data is read
 * from the legacy database (container ef-legacy-db) and handed to Rank Math's
 * own Yoast importer, which converts it the way Rank Math expects:
 *
 *  - settings: wpseo, wpseo_titles, wpseo_social (titles & separator, knowledge
 *    graph, social profiles, webmaster verification, breadcrumbs, sitemap …),
 *    with the old domains' URLs pointed at this site and its own image IDs;
 *  - post meta (_yoast_wpseo_*: SEO title, meta description, focus keyphrases,
 *    cornerstone, breadcrumb title, primary category …), moved from the legacy
 *    post IDs to this site's IDs (meta _ef_legacy_id, else post type + slug);
 *  - term meta (wpseo_taxonomy_meta): the old "range" and "ranges" taxonomies
 *    are product categories here (same slug), the rest maps by taxonomy + slug;
 *  - redirects: the original Yoast Premium and Simple 301 redirects as Rank Math
 *    redirections – resolved to their final page here, exactly as served until
 *    now by ecofridge-blocks (includes/legacy-redirect-map.php), 410 for removed
 *    pages; old redirects whose source is a live page here are kept inactive.
 *
 * The temporary Yoast copies are removed afterwards, so the site keeps only
 * Rank Math data. Idempotent (redirections made by this migration are replaced).
 * Run via source-dump/_run.php with Rank Math active and ef-legacy-db running.
 *
 * @package EcoFridgeMigration
 */

use RankMath\Helper as RM_Helper;
use RankMath\Redirections\Redirection as RM_Redirection;

if ( ! defined( 'RANK_MATH_VERSION' ) ) {
	WP_CLI::error( 'Activate Rank Math (seo-by-rank-math) first.' );
}
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
if ( ! $db ) {
	WP_CLI::error( 'Legacy DB not reachable (container ef-legacy-db).' );
}
mysqli_set_charset( $db, 'utf8mb4' );
global $wpdb;
$lp = '43lz2ypq8_';

$rows = static function ( $sql ) use ( $db ) {
	$res = mysqli_query( $db, $sql );
	return $res ? mysqli_fetch_all( $res, MYSQLI_ASSOC ) : array();
};
$legacy_option = static function ( $name ) use ( $rows, $lp ) {
	$r = $rows( "SELECT option_value FROM {$lp}options WHERE option_name = '" . esc_sql( $name ) . "'" );
	return $r ? maybe_unserialize( $r[0]['option_value'] ) : null;
};

// URLs of the old domains → this site.
$fix_urls = static function ( $value ) use ( &$fix_urls ) {
	if ( is_array( $value ) ) {
		return array_map( $fix_urls, $value );
	}
	if ( ! is_string( $value ) ) {
		return $value;
	}
	return preg_replace( '~https?://(?:www\.)?(?:eco-fridge\.o2dev\.de|eco-fridge\.co\.uk)~i', untrailingslashit( home_url() ), $value );
};
// This site's attachment for an image URL (matched by file name).
$attachment_by_url = static function ( $url ) use ( $wpdb ) {
	$file = wp_basename( (string) wp_parse_url( (string) $url, PHP_URL_PATH ) );
	if ( '' === $file ) {
		return 0;
	}
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND ( meta_value = %s OR meta_value LIKE %s ) LIMIT 1", $file, '%/' . $wpdb->esc_like( $file ) ) );
};

// The importer only writes meta keys WordPress sees as protected; Rank Math
// registers its keys as such in wp-admin only, not under WP-CLI.
add_filter( 'is_protected_meta', static fn( $protected, $key ) => $protected || 0 === strpos( (string) $key, 'rank_math_' ), 10, 2 );

$importer = new \RankMath\Admin\Importers\Yoast( 'wordpress-seo-premium/wp-seo-premium.php' );
$run      = static function ( $method ) use ( $importer ) {
	return ( function () use ( $method ) {
		return $this->$method();
	} )->call( $importer );
};

/* ---------------------------------------------------------------------------
 * 1. Settings.
 * ------------------------------------------------------------------------- */
// Rank Math only runs on the front end once its registration is done or skipped
// (the wizard's "Skip" button); the wizard is not needed – settings come from Yoast.
update_option( 'rank_math_registration_skip', true );
update_option( 'rank_math_wizard_completed', true );
$social = $fix_urls( (array) $legacy_option( 'wpseo_social' ) );
if ( ! empty( $social['og_default_image'] ) ) {
	$social['og_default_image_id'] = $attachment_by_url( $social['og_default_image'] );
	if ( $social['og_default_image_id'] ) {
		$social['og_default_image'] = wp_get_attachment_url( $social['og_default_image_id'] );
	}
}
$titles = $fix_urls( (array) $legacy_option( 'wpseo_titles' ) );
if ( ! empty( $titles['company_logo'] ) ) {
	$titles['company_logo_id'] = $attachment_by_url( $titles['company_logo'] );
	if ( $titles['company_logo_id'] ) {
		$titles['company_logo'] = wp_get_attachment_url( $titles['company_logo_id'] );
	}
}
update_option( 'wpseo', $fix_urls( (array) $legacy_option( 'wpseo' ) ) );
update_option( 'wpseo_titles', $titles );
update_option( 'wpseo_social', $social );
$run( 'settings' );
WP_CLI::log( 'Settings imported.' );

// What Yoast did by default and Rank Math does differently.
$all     = rank_math()->settings->all_raw();
$general = $all['general'];
$rm_tit  = $all['titles'];
$sitemap = $all['sitemap'];

// Yoast listed every public taxonomy in the sitemap (wpseo_xml was never saved).
foreach ( array( 'category', 'post_tag', 'product_cat', 'product_tag', 'market' ) as $tax ) {
	if ( taxonomy_exists( $tax ) ) {
		$sitemap[ "tax_{$tax}_sitemap" ] = 'on';
	}
}
$sitemap['authors_sitemap'] = 'off'; // no author archives (as before: includes/sitemaps.php).
// Attachment pages are off on this site (they redirect to the file), so their
// sitemap would only list redirects.
if ( ! get_option( 'wp_attachment_pages_enabled' ) ) {
	$sitemap['pt_attachment_sitemap'] = 'off';
}
// Yoast kept attachment pages and indexed empty archives; so did this site.
$general['attachment_redirect_urls'] = 'off';
$rm_tit['noindex_empty_taxonomies']  = 'off';
// What the importer misses: the default Open Graph image (it sets it on a copy
// of the settings) and Yoast 9's separate social profile fields.
if ( ! empty( $social['og_default_image_id'] ) ) {
	$rm_tit['open_graph_image']    = $social['og_default_image'];
	$rm_tit['open_graph_image_id'] = (int) $social['og_default_image_id'];
}
$profiles = array();
foreach ( array( 'linkedin_url', 'pinterest_url', 'youtube_url', 'instagram_url', 'myspace_url', 'wikipedia_url', 'google_plus_url', 'plus-publisher' ) as $field ) {
	if ( ! empty( $social[ $field ] ) ) {
		$profiles[] = $social[ $field ];
	}
}
if ( ! empty( $social['other_social_urls'] ) ) {
	$profiles = array_merge( $profiles, (array) $social['other_social_urls'] );
}
if ( $profiles ) {
	$rm_tit['social_additional_profiles'] = implode( PHP_EOL, array_unique( $profiles ) );
}
// Yoast marked pages up as WebPage only; Rank Math's default adds an Article.
$rm_tit['pt_page_default_rich_snippet'] = 'off';
// Yoast WooCommerce SEO: brand / manufacturer in the product schema = product category.
$woo = (array) $legacy_option( 'wpseo_woo' );
if ( ! empty( $woo['schema_brand'] ) && taxonomy_exists( $woo['schema_brand'] ) ) {
	$general['product_brand'] = $woo['schema_brand'];
}
// The old "range" / "ranges" taxonomies are product categories here: same templates.
foreach ( array( 'title', 'description' ) as $field ) {
	if ( empty( $rm_tit[ "tax_product_cat_{$field}" ] ) && ! empty( $rm_tit[ "tax_market_{$field}" ] ) ) {
		$rm_tit[ "tax_product_cat_{$field}" ] = $rm_tit[ "tax_market_{$field}" ];
	}
}
update_option( 'rank-math-options-general', $general );
update_option( 'rank-math-options-titles', $rm_tit );
update_option( 'rank-math-options-sitemap', $sitemap );

/* ---------------------------------------------------------------------------
 * 2. Post meta.
 * ------------------------------------------------------------------------- */
$legacy_ids = $wpdb->get_results( "SELECT meta_value AS legacy, post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ef_legacy_id'", OBJECT_K );
$post_map   = array();
// The home page and the blog page were rebuilt here: match them by role.
foreach ( array( 'page_on_front', 'page_for_posts' ) as $role ) {
	$legacy_page = (int) $legacy_option( $role );
	if ( $legacy_page && (int) get_option( $role ) ) {
		$post_map[ $legacy_page ] = (int) get_option( $role );
	}
}
foreach ( $rows( "SELECT ID, post_type, post_name FROM {$lp}posts WHERE ID IN ( SELECT DISTINCT post_id FROM {$lp}postmeta WHERE meta_key LIKE '\\_yoast\\_wpseo\\_%' )" ) as $p ) {
	if ( isset( $post_map[ (int) $p['ID'] ] ) ) {
		continue;
	}
	if ( isset( $legacy_ids[ $p['ID'] ] ) ) {
		$post_map[ (int) $p['ID'] ] = (int) $legacy_ids[ $p['ID'] ]->post_id;
	} elseif ( '' !== $p['post_name'] ) {
		$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s LIMIT 1", $p['post_name'], $p['post_type'] ) );
		if ( $id ) {
			$post_map[ (int) $p['ID'] ] = $id;
		} else {
			WP_CLI::log( "  no post here for legacy {$p['post_type']} #{$p['ID']} ({$p['post_name']})" );
		}
	}
}

// Legacy term ID → this site's term (taxonomy + slug; range / ranges → product_cat).
$legacy_terms = array();
foreach ( $rows( "SELECT t.term_id, t.slug, tt.taxonomy FROM {$lp}terms t JOIN {$lp}term_taxonomy tt ON tt.term_id = t.term_id" ) as $t ) {
	$legacy_terms[ (int) $t['term_id'] ] = $t;
}
$tax_here = static fn( $tax ) => in_array( $tax, array( 'range', 'ranges' ), true ) ? 'product_cat' : $tax;
$term_map = static function ( $legacy_id, $taxonomy = '' ) use ( $legacy_terms, $tax_here ) {
	$t = $legacy_terms[ (int) $legacy_id ] ?? null;
	if ( ! $t || ( $taxonomy && $t['taxonomy'] !== $taxonomy ) ) {
		return null;
	}
	$tax = $tax_here( $t['taxonomy'] );
	if ( ! taxonomy_exists( $tax ) ) {
		return null;
	}
	$term = get_term_by( 'slug', $t['slug'], $tax );
	return $term ? $term : null;
};

$skip = array( '_yoast_wpseo_content_score', '_yoast_wpseo_linkdex', '_yoast_wpseo_focuskw_text_input', '_yoast_wpseo_keywordsynonyms' );
$meta = array();
foreach ( $rows( "SELECT post_id, meta_key, meta_value FROM {$lp}postmeta WHERE meta_key LIKE '\\_yoast\\_wpseo\\_%' AND meta_value <> '' AND meta_value <> '[]'" ) as $m ) {
	if ( in_array( $m['meta_key'], $skip, true ) || ! isset( $post_map[ (int) $m['post_id'] ] ) ) {
		continue;
	}
	$meta[ $post_map[ (int) $m['post_id'] ] ][ $m['meta_key'] ] = $m['meta_value'];
}

$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_yoast\\_wpseo\\_%'" );
$primary_count = 0;
foreach ( $meta as $post_id => $values ) {
	// Primary terms: only for a taxonomy this site has, and a term the post really has.
	$primary = array();
	foreach ( array( 'product_cat', 'range', 'ranges', 'category', 'market' ) as $tax ) {
		if ( empty( $values[ "_yoast_wpseo_primary_{$tax}" ] ) ) {
			continue;
		}
		$term = $term_map( $values[ "_yoast_wpseo_primary_{$tax}" ], $tax );
		if ( $term && ! isset( $primary[ $term->taxonomy ] ) && has_term( $term->term_id, $term->taxonomy, $post_id ) ) {
			$primary[ $term->taxonomy ] = $term->term_id;
		}
	}
	foreach ( $values as $key => $value ) {
		if ( 0 === strpos( $key, '_yoast_wpseo_primary_' ) ) {
			continue;
		}
		if ( '_yoast_wpseo_opengraph-image-id' === $key || '_yoast_wpseo_twitter-image-id' === $key ) {
			continue; // re-derived from the image URL below.
		}
		update_post_meta( $post_id, $key, wp_slash( $fix_urls( $value ) ) );
	}
	foreach ( array( 'opengraph', 'twitter' ) as $net ) {
		if ( ! empty( $values[ "_yoast_wpseo_{$net}-image" ] ) ) {
			$att = $attachment_by_url( $values[ "_yoast_wpseo_{$net}-image" ] );
			if ( $att ) {
				update_post_meta( $post_id, "_yoast_wpseo_{$net}-image", wp_get_attachment_url( $att ) );
				update_post_meta( $post_id, "_yoast_wpseo_{$net}-image-id", $att );
			}
		}
	}
	foreach ( $primary as $tax => $term_id ) {
		update_post_meta( $post_id, "_yoast_wpseo_primary_{$tax}", $term_id );
		++$primary_count;
	}
}
WP_CLI::log( sprintf( 'Post meta: %d posts (%d primary terms).', count( $meta ), $primary_count ) );

// Rank Math's importer reads the request's page number.
$importer_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('" . implode( "','", array_map( 'esc_sql', RM_Helper::get_accessible_post_types() ) ) . "')" );
for ( $page = 1; $page <= (int) ceil( $importer_items / 100 ); $page++ ) {
	$_REQUEST['paged'] = $page;
	$run( 'postmeta' );
}
unset( $_REQUEST['paged'] );
// A second keyphrase list with no main keyphrase leaves a leading comma.
$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = TRIM( LEADING ', ' FROM meta_value ) WHERE meta_key = 'rank_math_focus_keyword' AND meta_value LIKE ', %'" );
WP_CLI::log( 'Post meta imported.' );

/* ---------------------------------------------------------------------------
 * 3. Term meta.
 * ------------------------------------------------------------------------- */
$tax_meta = array();
// "range" first: where one term here was both /range/x/ and /ranges/x/, the
// /range/ copy (this site's URL) wins and the other only fills empty fields.
$legacy_tax_meta = (array) $legacy_option( 'wpseo_taxonomy_meta' );
uksort( $legacy_tax_meta, static fn( $a, $b ) => ( 'range' === $b ) <=> ( 'range' === $a ) );
$term_count = 0;
foreach ( $legacy_tax_meta as $tax => $terms ) {
	foreach ( (array) $terms as $legacy_id => $data ) {
		$term = $term_map( $legacy_id, $tax );
		if ( ! $term ) {
			continue;
		}
		$data = array_filter( (array) $fix_urls( $data ), static fn( $v ) => '' !== $v && null !== $v );
		unset( $data['wpseo_linkdex'], $data['wpseo_content_score'], $data['wpseo_keywordsynonyms'] );
		if ( ! $data ) {
			continue;
		}
		$current = $tax_meta[ $term->taxonomy ][ $term->term_id ] ?? array();
		$tax_meta[ $term->taxonomy ][ $term->term_id ] = $current + $data;
		++$term_count;
	}
}
update_option( 'wpseo_taxonomy_meta', $tax_meta );
$run( 'termmeta' );
// Extra Yoast keyphrases of a term (JSON list) join the focus keyword, as for posts.
foreach ( $tax_meta as $terms ) {
	foreach ( $terms as $term_id => $data ) {
		$extra = json_decode( (string) ( $data['wpseo_focuskeywords'] ?? '' ), true );
		if ( is_array( $extra ) && $extra ) {
			$keys = array_filter( array_merge( array( (string) get_term_meta( $term_id, 'rank_math_focus_keyword', true ) ), array_column( $extra, 'keyword' ) ) );
			update_term_meta( $term_id, 'rank_math_focus_keyword', implode( ', ', array_unique( $keys ) ) );
		}
	}
}
WP_CLI::log( sprintf( 'Term meta: %d legacy terms → %d terms here.', $term_count, array_sum( array_map( 'count', $tax_meta ) ) ) );

// /markets/<slug>/ were pages on the original site (with their own Yoast title
// and description); here they show the market archive, so the market term
// gets the page's SEO fields where it has none of its own.
$hub = get_page_by_path( 'markets' );
if ( $hub && function_exists( 'efb_market_term_of_page' ) ) {
	foreach ( get_pages( array( 'parent' => $hub->ID ) ) as $page ) {
		$term = efb_market_term_of_page( $page );
		if ( ! $term ) {
			continue;
		}
		foreach ( array( 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword' ) as $field ) {
			$value = (string) get_post_meta( $page->ID, $field, true );
			if ( '' !== $value && '' === (string) get_term_meta( $term->term_id, $field, true ) ) {
				update_term_meta( $term->term_id, $field, str_replace( '%title%', '%term%', $value ) );
			}
		}
	}
}

/* ---------------------------------------------------------------------------
 * 4. Author meta (Yoast author titles / descriptions), matched by user login.
 * ------------------------------------------------------------------------- */
$user_count = 0;
foreach ( $rows( "SELECT u.user_login, m.meta_key, m.meta_value FROM {$lp}usermeta m JOIN {$lp}users u ON u.ID = m.user_id WHERE m.meta_key IN ('wpseo_title','wpseo_metadesc','wpseo_noindex_author') AND m.meta_value <> ''" ) as $m ) {
	$user = get_user_by( 'login', $m['user_login'] );
	if ( $user ) {
		update_user_meta( $user->ID, $m['meta_key'], $m['meta_value'] );
		++$user_count;
	}
}
if ( $user_count ) {
	$run( 'usermeta' );
}
WP_CLI::log( "Author meta: $user_count values." );

/* ---------------------------------------------------------------------------
 * 5. Redirections.
 * ------------------------------------------------------------------------- */
RM_Helper::update_modules( array( 'redirections' => 'on' ) );
$table = $wpdb->prefix . 'rank_math_redirections';
$old   = array_map( 'intval', (array) get_option( 'ef_rank_math_redirect_ids', array() ) );
if ( $old ) {
	$wpdb->query( "DELETE FROM {$table} WHERE id IN (" . implode( ',', $old ) . ')' );
	$wpdb->query( "DELETE FROM {$table}_cache WHERE redirection_id IN (" . implode( ',', $old ) . ')' );
}

$add = static function ( $from, $to, $code, $status = 'active' ) {
	$item = RM_Redirection::from(
		array(
			'sources'     => array(
				array(
					'pattern'    => $from,
					'comparison' => 'exact',
				),
			),
			'url_to'      => $to,
			'header_code' => $code,
			'status'      => $status,
		)
	);
	return $item->save();
};

$ids  = array();
$done = array();
$key  = static fn( $path ) => strtolower( untrailingslashit( ltrim( $path, '/' ) ) );
$map  = efb_legacy_redirect_map();
foreach ( $map['redirect'] as $from => $to ) {
	$ids[]              = $add( $from, $to, 301 );
	$done[ $key( $from ) ] = true;
}
foreach ( $map['gone'] as $from ) {
	$ids[]              = $add( $from, '', 410 );
	$done[ $key( $from ) ] = true;
}

/*
 * Original redirects the map above left out: their targets no longer existed
 * on the original site either (it answered 404), so they point at today's
 * equivalent page (same choices as the map) or say 410 like their siblings.
 */
$extra = array(
	'/product/black-curved-multideck-display'                                 => '/product/frost-tech-black-slimline-multideck/',
	'/product/black-slimline-multideck-display'                               => '/product/frost-tech-black-slimline-multideck/',
	'/multidecks/black-slimeline-display-fridge/'                             => '/product/frost-tech-black-slimline-multideck/',
	'/multidecks/shutter-display-fridge/'                                     => '/range/display-cabinets/silver-steel-multideck-display-chillers/',
	'/multidecks/s-steel-costa-multideck/'                                    => '/range/display-cabinets/premium-mirrored-stainless-steel-multidecks/',
	'/ranges/low-temp-chest-freezer'                                          => '/range/storage-cabinets/low-temperature-chest-freezers/',
	'/ranges/low-temp-chest-freezers/'                                        => '/range/storage-cabinets/low-temperature-chest-freezers/',
	'/serve-overs/double-glazed-serve-over/'                                  => '/range/display-cabinets/serve-over-display/',
	'/storage-units/stainless-steel-under-counter-chiller/'                   => '/range/storage-cabinets/stainless-steel-under-counter-chiller/',
	'/storage-units/catering-storage-counter-chillers/'                       => '/range/storage-cabinets/stainless-steel-under-counter-chiller/',
	'/patisseries/costa-grab-go/'                                             => '/product-category/commercial-display-fridge/grab-and-go/',
	'/patisseries/curved-glass-patisserie/'                                   => '/product-category/commercial-display-fridge/patisserie-fridge/',
	'/product/frost-tech-low-front-white-multideck-3'                         => '',
	'/product/black-steel-patisserie-display-chiller-ex-exhibition-used-copy' => '',
	'/product/stainless-steel-grabgo-display-fridge'                          => '',
);
foreach ( $extra as $from => $to ) {
	$ids[]              = $add( $from, $to, '' === $to ? 410 : 301 );
	$done[ $key( $from ) ] = true;
}

// Every other original redirect is kept, inactive: its source is a live page
// (or already redirects) here, so turning it on would hide that page.
$originals = array();
foreach ( (array) $legacy_option( 'wpseo-premium-redirects-base' ) as $r ) {
	$originals[] = array( '/' . ltrim( (string) $r['origin'], '/' ), (string) ( $r['url'] ?? '' ), (int) ( $r['type'] ?? 301 ) );
}
foreach ( (array) $legacy_option( '301_redirects' ) as $from => $to ) {
	$originals[] = array( (string) $from, (string) $to, 301 );
}
$inactive = 0;
foreach ( $originals as list( $from, $to, $code ) ) {
	if ( isset( $done[ $key( $from ) ] ) || 0 === strpos( $from, '/http' ) ) {
		continue; // done above, or not a path of this site (a reCAPTCHA URL).
	}
	$to = $fix_urls( $to );
	if ( '' !== $to && ! preg_match( '~^(https?:)?/~', $to ) ) {
		$to = '/' . $to;
	}
	$ids[]              = $add( $from, in_array( $code, array( 410, 451 ), true ) ? '' : $to, $code, 'inactive' );
	$done[ $key( $from ) ] = true;
	++$inactive;
}
$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
update_option( 'ef_rank_math_redirect_ids', $ids, false );
WP_CLI::log( sprintf( 'Redirections: %d (%d inactive).', count( $ids ), $inactive ) );

/* ---------------------------------------------------------------------------
 * 6. Clean up: only Rank Math data stays.
 * ------------------------------------------------------------------------- */
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_yoast\\_wpseo\\_%'" );
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('wpseo_title','wpseo_metadesc','wpseo_noindex_author')" );
foreach ( array( 'wpseo', 'wpseo_titles', 'wpseo_social', 'wpseo_taxonomy_meta' ) as $option ) {
	delete_option( $option );
}
mysqli_close( $db );

WP_CLI::success( 'Yoast data moved to Rank Math.' );
