<?php
/**
 * 46 — Blog categories.
 *
 * All 106 posts were in the single "Blog" category. This creates topic
 * categories under it (served at /blog/<slug>/ by ecofridge-blocks/includes/blog.php),
 * each with an SEO heading (H1), an SEO description and an order for the category buttons on /blog/,
 * and files every post under exactly one of them (the post URLs don't change:
 * permalinks are /%postname%/).
 *
 * Idempotent.
 *
 * @package EcoFridgeMigration
 */

$parent = get_term_by( 'slug', 'blog', 'category' );
if ( ! $parent ) {
	WP_CLI::error( 'The "blog" category is missing.' );
}

$cats = array(
	'buying-guides'         => array(
		'Commercial Refrigeration Buying Guides',
		'Buying Guides',
		'Buying guides to commercial fridges, freezers and display cabinets – how to choose the right type, size and specification for your business, and what to check before you order.',
		array( 7448, 7447, 7445, 7443, 7440, 7439, 7438, 7434, 7428, 7424, 7421, 7419, 7413, 7412, 7407, 7406, 7404, 7402, 7400, 7398, 7386, 7385, 7373, 7371, 7368, 7364, 7363, 7362, 7361, 7360, 7353, 7345 ),
	),
	'how-to-maintenance'    => array(
		'Commercial Fridge & Freezer How-To and Maintenance Guides',
		'How-To & Maintenance',
		'Practical how-to guides for commercial refrigeration – cleaning, defrosting, levelling, troubleshooting and the everyday habits that keep fridges and freezers efficient and reliable for longer.',
		array( 7432, 7425, 7423, 7422, 7420, 7417, 7415, 7414, 7410, 7405, 7396, 7394, 7393, 7391, 7387, 7382, 7374, 7372, 7359 ),
	),
	'business-advice'       => array(
		'Food Business Advice: Starting and Growing Your Business',
		'Business Advice',
		'Advice for starting and growing a food business – coffee shops, restaurants, pubs, takeaways, butchers, patisseries and ice cream businesses – and the equipment each one needs.',
		array( 7442, 7441, 7436, 7435, 7429, 7427, 7411, 7397, 7395, 7379, 7378, 7377, 7376, 7375, 7370, 7369, 7366, 7365, 7352, 7351, 7347 ),
	),
	'display-merchandising' => array(
		'Display & Merchandising Tips for Chilled and Frozen Products',
		'Display & Merchandising',
		'How to present chilled and frozen products so they sell – display fridge layout, merchandising ideas and the cabinets that turn browsing into impulse purchases.',
		array( 7446, 7437, 7433, 7426, 7418, 7416, 7409, 7408, 7403, 7401, 7392, 7383, 7380, 7358, 7357, 7356, 7355 ),
	),
	'food-safety-storage'   => array(
		'Food Safety & Storage Guides for Caterers and Retailers',
		'Food Safety & Storage',
		'Food safety and storage guidance for caterers and retailers – safe fridge and freezer temperatures, storing food correctly and following UK food hygiene regulations.',
		array( 7444, 7431, 7430, 7399, 7388, 7381, 7367, 7349 ),
	),
	'news-innovation'       => array(
		'Commercial Refrigeration News & Innovation',
		'News & Innovation',
		'ECO-Fridge news, new product ranges and the technology shaping commercial refrigeration – energy-efficient cabinets, greener refrigerants and industry trends.',
		array( 7390, 7389, 7384, 7354, 7350, 7348, 7346, 7344, 7343 ),
	),
);

$assigned = array();
$order    = 0;
foreach ( $cats as $slug => list( $h1, $name, $description, $posts ) ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( $term ) {
		wp_update_term( $term->term_id, 'category', array( 'name' => $name, 'description' => $description, 'parent' => $parent->term_id ) );
		$term_id = $term->term_id;
	} else {
		$made    = wp_insert_term( $name, 'category', array( 'slug' => $slug, 'description' => $description, 'parent' => $parent->term_id ) );
		$term_id = $made['term_id'];
	}
	update_term_meta( $term_id, 'ef_order', ++$order );
	update_term_meta( $term_id, 'ef_h1', $h1 );
	foreach ( $posts as $id ) {
		if ( 'post' !== get_post_type( $id ) ) {
			WP_CLI::warning( "Not a post: $id" );
			continue;
		}
		wp_set_post_categories( $id, array( $term_id ), false );
		$assigned[] = $id;
	}
	WP_CLI::log( sprintf( '%-24s %d posts', $name, count( $posts ) ) );
}

$all  = get_posts( array( 'numberposts' => -1, 'post_status' => 'publish', 'fields' => 'ids' ) );
$left = array_diff( $all, $assigned );
if ( $left ) {
	WP_CLI::warning( 'Posts without a topic category: ' . implode( ', ', $left ) );
}
if ( count( $assigned ) !== count( array_unique( $assigned ) ) ) {
	WP_CLI::warning( 'Some posts are listed twice.' );
}

delete_option( 'rewrite_rules' );
WP_CLI::success( count( array_unique( $assigned ) ) . ' posts filed under ' . count( $cats ) . ' blog categories.' );
