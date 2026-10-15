<?php
/**
 * 62 — Redirects for original URLs that answered 404 in the launch QA.
 *
 *  - /author/eco-fridge/ and its pages 2–3: author archives are closed for
 *    security (includes/security.php), so the indexed archive goes to the blog.
 *  - Two old attachment pages under a market page and one under a brochure page:
 *    to the page they belonged to.
 *
 * Added to Rank Math → Redirections (301, exact match) – where the original
 * site's redirects live (migration 52). The same entries are in
 * ecofridge-blocks/includes/legacy-redirect-map.php, used when the Rank Math
 * module is off. A source that already has a redirection is left alone, so the
 * migration can run again (or after 52) without duplicates.
 *
 * Run: wp eval-file migration/62-qa-redirects.php
 *
 * @package EcoFridgeMigration
 */

use RankMath\Redirections\Redirection as RM_Redirection;

$redirects = array(
	'/author/eco-fridge/'                                                     => '/blog/',
	'/author/eco-fridge/page/2/'                                              => '/blog/',
	'/author/eco-fridge/page/3/'                                              => '/blog/',
	'/brochure/retarder-prover-manual/retarder-proofer-controller-user-manual/' => '/brochure/retarder-prover-manual/',
	'/markets/sports-clubs/banner-no1-coffee-new/'                            => '/markets/sports-clubs/',
	'/markets/sports-clubs/eco-upright-mont-trans/'                           => '/markets/sports-clubs/',
);

if ( ! class_exists( '\RankMath\Helper' ) || ! \RankMath\Helper::is_module_active( 'redirections' ) ) {
	WP_CLI::log( 'Rank Math Redirections is off: the redirects are served from includes/legacy-redirect-map.php.' );
	return;
}

global $wpdb;
$table = $wpdb->prefix . 'rank_math_redirections';
$added = 0;
foreach ( $redirects as $from => $to ) {
	$pattern = ltrim( $from, '/' );
	$exists  = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE sources LIKE %s OR sources LIKE %s", '%"' . $wpdb->esc_like( $pattern ) . '"%', '%"' . $wpdb->esc_like( $from ) . '"%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( $exists ) {
		WP_CLI::log( "exists   $from (#$exists)" );
		continue;
	}
	$item = RM_Redirection::from(
		array(
			'sources'     => array(
				array(
					'pattern'    => $from,
					'comparison' => 'exact',
				),
			),
			'url_to'      => $to,
			'header_code' => 301,
			'status'      => 'active',
		)
	);
	$id = $item->save();
	WP_CLI::log( "added    $from -> $to (#$id)" );
	++$added;
}
WP_CLI::success( "$added redirect(s) added." );
