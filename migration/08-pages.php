<?php
/**
 * Pasul 8: migrează paginile interne (conținut) în blocuri Spectra.
 * Rulare: wp eval-file wp-content/source-dump/_run.php
 *
 * Fiecare pagină legacy -> pagină nouă cu același slug/titlu, conținut
 * (post_content + wpcf-content-2 + youtube + brochure) învelit într-un
 * container Spectra. Ierarhia „markets" păstrată. Woo pages excluse.
 * Idempotent (după slug). URL-uri vechi -> relative; shortcode CF7 scoase.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
if ( ! $db ) { WP_CLI::error( 'Legacy DB: ' . mysqli_connect_error() ); }
mysqli_set_charset( $db, 'utf8mb4' );

// pagini de migrat: slug legacy (excludem shop/cart/checkout/my-account/gateway/homepage-7)
$slugs = array(
	'about-us', 'markets', 'support', 'contact-us', 'privacy-cookies-policy',
	'graded-units', 'terms-conditions', 'cookie-policy-uk', 'we-are-open-for-business', 'returns',
	// sub-pagini markets
	'coffee-shops', 'sports-clubs', 'restaurants', 'airports', 'forecourts',
	'convenience-stores', 'delis-and-butchers', 'display-freezers',
);

$bid = function () { return substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyz0123456789' ), 0, 8 ); };

$clean = function ( $html ) {
	$html = str_replace( array( 'https://eco-fridge.co.uk', 'http://eco-fridge.co.uk', 'https://www.eco-fridge.co.uk' ), '', $html );
	$html = preg_replace( '/\[contact-form-7[^\]]*\]/', '', $html ); // CF7 scos (form separat ulterior)
	return $html;
};

$wrap = function ( $inner ) use ( $bid ) {
	$id = $bid();
	return '<!-- wp:uagb/container {"block_id":"' . $id . '","contentWidth":"full_width","innerContentWidth":"alignwide","directionDesktop":"column","topPaddingDesktop":45,"bottomPaddingDesktop":45,"leftPaddingDesktop":20,"rightPaddingDesktop":20} -->'
		. '<div class="wp-block-uagb-container uagb-block-' . $id . ' alignfull uagb-is-root-container"><div class="uagb-container-inner-blocks-wrap">'
		. '<!-- wp:html -->' . "\n" . '<div class="ef-page-content">' . $inner . '</div>' . "\n" . '<!-- /wp:html -->'
		. '</div></div><!-- /wp:uagb/container -->';
};

// map slug -> new page id (pt. parent markets)
$new_ids = array();
$created = 0; $updated = 0;

foreach ( $slugs as $slug ) {
	$res = mysqli_query( $db, "SELECT ID, post_title, post_content, post_excerpt FROM {$LP}posts WHERE post_type='page' AND post_name='" . mysqli_real_escape_string( $db, $slug ) . "' AND post_status='publish' LIMIT 1" );
	$row = $res ? mysqli_fetch_assoc( $res ) : null;
	if ( ! $row ) { WP_CLI::warning( "pagina '$slug' negăsită în legacy" ); continue; }
	$old_id = (int) $row['ID'];

	// meta
	$meta = array();
	$mr = mysqli_query( $db, "SELECT meta_key, meta_value FROM {$LP}postmeta WHERE post_id=$old_id AND meta_key IN ('wpcf-content-2','wpcf-content-3','wpcf-youtube-id-number','wpcf-brochure-link')" );
	while ( $m = mysqli_fetch_assoc( $mr ) ) { $meta[ $m['meta_key'] ] = $m['meta_value']; }

	// compunere conținut
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

	// contact-us: adaugă bloc de contact dacă rămâne subțire (form CF7 scos)
	if ( 'contact-us' === $slug ) {
		$c = ef_contact();
		$body .= '<div class="ef-contact-details"><h3>Get in touch</h3><p><strong>' . esc_html( $c['company'] ) . '</strong><br>' . esc_html( $c['address'] ) . '</p>'
			. '<p>Tel: <a href="tel:' . esc_attr( preg_replace( '/\s+/', '', $c['phone'] ) ) . '">' . esc_html( $c['phone'] ) . '</a><br>Email: <a href="mailto:' . esc_attr( $c['email'] ) . '">' . esc_html( $c['email'] ) . '</a></p></div>';
	}

	$body = call_user_func( $clean, $body );

	// markets landing (gol) — construiește hub către sub-pagini
	if ( 'markets' === $slug && '' === trim( $row['post_content'] ) ) {
		$body = '<p>ECO-Fridge supplies commercial refrigeration across many sectors. Choose your market:</p><div class="ef-markets-grid">'
			. '<a href="/coffee-shops/">Café / Coffee Shops</a><a href="/sports-clubs/">Sports Clubs</a><a href="/restaurants/">Restaurants &amp; Bars</a>'
			. '<a href="/airports/">Airports</a><a href="/forecourts/">Forecourts</a><a href="/convenience-stores/">Convenience Stores</a>'
			. '<a href="/delis-and-butchers/">Delis &amp; Butchers</a><a href="/display-freezers/">Display Freezers</a></div>';
	}

	$content = $wrap( $body );

	// creare / update
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

// ierarhie markets: sub-paginile sub 'markets'
$market_parent = isset( $new_ids['markets'] ) ? $new_ids['markets'] : 0;
if ( $market_parent ) {
	foreach ( array( 'coffee-shops', 'sports-clubs', 'restaurants', 'airports', 'forecourts', 'convenience-stores', 'delis-and-butchers', 'display-freezers' ) as $sub ) {
		if ( isset( $new_ids[ $sub ] ) ) { wp_update_post( array( 'ID' => $new_ids[ $sub ], 'post_parent' => $market_parent ) ); }
	}
}

WP_CLI::log( "Pagini: create=$created, actualizate=$updated (total " . count( $new_ids ) . ')' );
WP_CLI::success( 'Migrare pagini interne (Spectra) finalizată.' );
