<?php
/**
 * 49 — Clean the links in the old content (posts, pages, products, term texts).
 *
 * The 2026-10-13 audit found links that end on a 404/410 (pages and products
 * removed long ago, also missing on the original site) or go through a
 * redirect first (old paths, missing trailing slash). Each one is pointed
 * straight at the page that replaced it. Only whole attribute values are
 * replaced (href/src), relative or with the site's own domain.
 *
 * Idempotent. Run via source-dump/_run.php.
 *
 * @package EcoFridgeMigration
 */

global $wpdb;

$map = array(
	// Broken (404 / 410).
	'/product/frost-tech-low-temperature-chest-freezer-gle-range/'       => '/product/low-temperature-chest-freezer-45/',
	'/product/frost-tech-low-temperature-chest-freezer-glk-range/'       => '/product/low-temperature-chest-freezer-65/',
	'/product/frost-tech-ultra-low-temperature-chest-freezer-gle-range/' => '/product/ultra-low-temperature-chest-freezer-86/',
	'/product-category/storage-freezer/low-temp-freezer/'                => '/range/low-temperature-chest-freezers/',
	'/range/display-cabinets/premium-multideck-display-chillers/'        => '/range/display-cabinets/premium-mirrored-stainless-steel-multidecks/',
	'/product/stainless-steel-grab-go-display-fridge/'                    => '/range/display-cabinets/premium-grab-and-go-display-fridges/',
	'/wp-content/uploads/meat.png'                                        => '/wp-content/uploads/ECO-GN700-FISH-WEB-300x360.png',
	// Through a redirect.
	'/display-fridge/'                                                    => '/product-category/commercial-display-fridge/',
	'/product-category/display-freezer/ice-cream-freezer/'               => '/product-category/display-freezer/',
	'/range/display-cabinets/patisserie-display-chillers/'               => '/product-category/commercial-display-fridge/patisserie-fridge/',
	'/range/display-cabinets/standard-serve-over-display/'               => '/range/display-cabinets/serve-over-display/',
	'/range/display-cabinets/multideck-display-chillers/'                => '/range/display-cabinets/silver-steel-multideck-display-chillers/',
	'/chest-freezer-and-upright-freezer-maintenance-guide/'              => '/commercial-freezer-maintenance-guide/',
	'/start-a-takeaway-business/'                                         => '/how-to-start-a-takeaway-business/',
	'/range/storage-cabinets/standard-upright-stainless-steel-gastronorm-chillers/' => '/range/storage-cabinets/upright-stainless-steel-gastronorm-chiller-fish-meat-cabinet/',
	'/ranges/commercial-fridge'                                           => '/ranges/commercial-fridge/',
	'/product-category/commercial-display-fridge/upright-fridge'         => '/product-category/commercial-display-fridge/upright-fridge/',
	'/product-category/commercial-display-fridge/commercial-drinks-fridge' => '/product-category/commercial-display-fridge/commercial-drinks-fridge/',
);

/** Replaces whole href/src values from the map; returns the new HTML. */
$clean = static function ( $html ) use ( $map ) {
	return preg_replace_callback(
		'~\b(href|src)=(["\'])(?:https?://(?:www\.)?(?:eco-fridge\.co\.uk|localhost:8888))?([^"\']*)\2~i',
		static function ( $m ) use ( $map ) {
			return isset( $map[ $m[3] ] ) ? $m[1] . '=' . $m[2] . $map[ $m[3] ] . $m[2] : $m[0];
		},
		(string) $html
	);
};

/** The same, inside arrays (serialized term meta). */
$clean_deep = static function ( $value ) use ( &$clean_deep, $clean ) {
	if ( is_array( $value ) ) {
		return array_map( $clean_deep, $value );
	}
	return is_string( $value ) ? $clean( $value ) : $value;
};

$changed = 0;

$posts = $wpdb->get_results( "SELECT ID, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_status IN ('publish','draft','private','future') AND post_type IN ('post','page','product','brochure','video')" );
foreach ( $posts as $p ) {
	$content = $clean( $p->post_content );
	$excerpt = $clean( $p->post_excerpt );
	if ( $content !== $p->post_content || $excerpt !== $p->post_excerpt ) {
		// Direct update: no revision, no modified-date change for old articles.
		$wpdb->update( $wpdb->posts, array( 'post_content' => $content, 'post_excerpt' => $excerpt ), array( 'ID' => $p->ID ) );
		clean_post_cache( $p->ID );
		$changed++;
		WP_CLI::log( 'Post ' . $p->ID . ' ' . get_the_title( $p->ID ) );
	}
}

foreach ( $wpdb->get_results( "SELECT term_taxonomy_id, term_id, taxonomy, description FROM {$wpdb->term_taxonomy} WHERE description <> ''" ) as $t ) {
	$desc = $clean( $t->description );
	if ( $desc !== $t->description ) {
		$wpdb->update( $wpdb->term_taxonomy, array( 'description' => $desc ), array( 'term_taxonomy_id' => $t->term_taxonomy_id ) );
		clean_term_cache( (int) $t->term_id, $t->taxonomy );
		$changed++;
		WP_CLI::log( 'Term ' . $t->term_id . ' description' );
	}
}

foreach ( $wpdb->get_results( "SELECT term_id, meta_key FROM {$wpdb->termmeta} WHERE meta_key IN ('_ef_range_content','_efb_landing')" ) as $row ) {
	$old = get_term_meta( (int) $row->term_id, $row->meta_key, true );
	$new = $clean_deep( $old );
	if ( $new !== $old ) {
		update_term_meta( (int) $row->term_id, $row->meta_key, $new );
		$changed++;
		WP_CLI::log( 'Term ' . $row->term_id . ' ' . $row->meta_key );
	}
}

if ( class_exists( 'EFB_Landing_Meta' ) ) {
	EFB_Landing_Meta::bump_cache();
}
WP_CLI::success( "$changed items updated." );
