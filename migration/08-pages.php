<?php
/**
 * Step 8: migrates the inner pages (content) into Spectra blocks.
 * Run: wp eval-file wp-content/source-dump/_run.php
 *
 * Each legacy page -> a new page with the same slug/title, its content
 * (post_content + wpcf-content-2 + youtube + brochure) wrapped in a
 * Spectra container. The "markets" hierarchy is kept. Woo pages are excluded.
 * Idempotent (by slug). Old URLs -> relative; CF7 shortcodes removed.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Legacy DB: ' . mysqli_connect_error() ); }
mysqli_set_charset( $db, 'utf8mb4' );

// pages to migrate: legacy slug (shop/cart/checkout/my-account/gateway/homepage-7 excluded)
$slugs = array(
	'about-us', 'markets', 'support', 'contact-us', 'privacy-cookies-policy',
	'graded-units', 'terms-conditions', 'cookie-policy-uk', 'we-are-open-for-business', 'returns',
	// markets sub-pages
	'coffee-shops', 'sports-clubs', 'restaurants', 'airports', 'forecourts',
	'convenience-stores', 'delis-and-butchers', 'display-freezers',
);

$bid = function () { return substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyz0123456789' ), 0, 8 ); };

$clean = function ( $html ) {
	$html = str_replace( array( 'https://eco-fridge.co.uk', 'http://eco-fridge.co.uk', 'https://www.eco-fridge.co.uk' ), '', $html );
	$html = preg_replace( '/\[contact-form-7[^\]]*\]/', '', $html ); // CF7 removed (a separate form comes later)
	return $html;
};

$wrap = function ( $inner ) use ( $bid ) {
	$id = $bid();
	return '<!-- wp:uagb/container {"block_id":"' . $id . '","contentWidth":"full_width","innerContentWidth":"alignwide","directionDesktop":"column","topPaddingDesktop":45,"bottomPaddingDesktop":45,"leftPaddingDesktop":20,"rightPaddingDesktop":20} -->'
		. '<div class="wp-block-uagb-container uagb-block-' . $id . ' alignfull uagb-is-root-container"><div class="uagb-container-inner-blocks-wrap">'
		. '<!-- wp:html -->' . "\n" . '<div class="ef-page-content">' . $inner . '</div>' . "\n" . '<!-- /wp:html -->'
		. '</div></div><!-- /wp:uagb/container -->';
};

// map slug -> new page id (for the markets parent)
$new_ids = array();
$created = 0; $updated = 0;

foreach ( $slugs as $slug ) {
	$res = mysqli_query( $db, "SELECT ID, post_title, post_content, post_excerpt FROM {$LP}posts WHERE post_type='page' AND post_name='" . mysqli_real_escape_string( $db, $slug ) . "' AND post_status='publish' LIMIT 1" );
	$row = $res ? mysqli_fetch_assoc( $res ) : null;
	if ( ! $row ) { WP_CLI::warning( "page '$slug' not found in the legacy DB" ); continue; }
	$old_id = (int) $row['ID'];

	// meta
	$meta = array();
	$mr = mysqli_query( $db, "SELECT meta_key, meta_value FROM {$LP}postmeta WHERE post_id=$old_id AND meta_key IN ('wpcf-content-2','wpcf-content-3','wpcf-youtube-id-number','wpcf-brochure-link')" );
	while ( $m = mysqli_fetch_assoc( $mr ) ) { $meta[ $m['meta_key'] ] = $m['meta_value']; }

	// assemble the content
	$body = trim( $row['post_content'] );
	if ( ! empty( $meta['wpcf-content-2'] ) ) { $body .= "\n\n" . $meta['wpcf-content-2']; }
	if ( ! empty( $meta['wpcf-content-3'] ) ) { $body .= "\n\n" . $meta['wpcf-content-3']; }
	if ( ! empty( $meta['wpcf-youtube-id-number'] ) ) {
		$yt = esc_attr( $meta['wpcf-youtube-id-number'] );
		$body .= "\n\n" . '<div class="ef-video"><iframe width="100%" height="480" src="https://www.youtube.com/embed/' . $yt . '" frameborder="0" allowfullscreen></iframe></div>';
	}
	if ( ! empty( $meta['wpcf-brochure-link'] ) ) {
		$body .= "\n\n" . '<p><a class="wp-block-button__link wp-element-button" href="' . esc_url( $meta['wpcf-brochure-link'] ) . '" target="_blank" rel="noopener">Download Brochure</a></p>';
	}

	// contact-us: add a contact block if it is left thin (CF7 form removed)
	if ( 'contact-us' === $slug ) {
		$c = ef_contact();
		$body .= '<div class="ef-contact-details"><h3>Get in touch</h3><p><strong>' . esc_html( $c['company'] ) . '</strong><br>' . esc_html( $c['address'] ) . '</p>'
			. '<p>Tel: <a href="tel:' . esc_attr( preg_replace( '/\s+/', '', $c['phone'] ) ) . '">' . esc_html( $c['phone'] ) . '</a><br>Email: <a href="mailto:' . esc_attr( $c['email'] ) . '">' . esc_html( $c['email'] ) . '</a></p></div>';
	}

	$body = call_user_func( $clean, $body );

	// markets landing (empty): build a hub linking to the sub-pages
	if ( 'markets' === $slug && '' === trim( $row['post_content'] ) ) {
		$body = '<p>ECO-Fridge supplies commercial refrigeration across many sectors. Choose your market:</p><div class="ef-markets-grid">'
			. '<a href="/coffee-shops/">Café / Coffee Shops</a><a href="/sports-clubs/">Sports Clubs</a><a href="/restaurants/">Restaurants &amp; Bars</a>'
			. '<a href="/airports/">Airports</a><a href="/forecourts/">Forecourts</a><a href="/convenience-stores/">Convenience Stores</a>'
			. '<a href="/delis-and-butchers/">Delis &amp; Butchers</a><a href="/display-freezers/">Display Freezers</a></div>';
	}

	$content = $wrap( $body );

	// create / update
	$existing = get_page_by_path( $slug );
	$postarr = array(
		'post_type'    => 'page',
		'post_title'   => $row['post_title'],
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_content' => $content,
	);
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
		wp_update_post( $postarr );
		$id = $existing->ID; $updated++;
	} else {
		$id = wp_insert_post( $postarr );
		$created++;
	}
	$new_ids[ $slug ] = $id;
	update_post_meta( $id, '_ef_legacy_id', $old_id );
	update_post_meta( $id, 'site-sidebar-layout', 'no-sidebar' );
	update_post_meta( $id, 'site-content-layout', 'plain-container' );
}

// markets hierarchy: the sub-pages under 'markets'
$market_parent = isset( $new_ids['markets'] ) ? $new_ids['markets'] : 0;
if ( $market_parent ) {
	foreach ( array( 'coffee-shops', 'sports-clubs', 'restaurants', 'airports', 'forecourts', 'convenience-stores', 'delis-and-butchers', 'display-freezers' ) as $sub ) {
		if ( isset( $new_ids[ $sub ] ) ) { wp_update_post( array( 'ID' => $new_ids[ $sub ], 'post_parent' => $market_parent ) ); }
	}
}

WP_CLI::log( "Pages: created=$created, updated=$updated (total " . count( $new_ids ) . ')' );
WP_CLI::success( 'Inner page migration (Spectra) finished.' );
