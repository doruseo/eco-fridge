<?php
/**
 * 26 — Homepage rebuilt 1:1 with the LIVE eco-fridge.co.uk homepage (Oct 2026),
 * using only ECO-Fridge Blocks + core blocks (no Spectra / wp:html).
 *
 * Live structure:
 *   H1 → 3 "Special Offer" cards → intro (3 paragraphs) → "Some of Our Clients"
 *   logos → "Our commercial refrigeration equipment" + list → Display Cabinets
 *   tiles → Storage Cabinets tiles → Graded Units tile → SEO text.
 *
 * Texts are read from the crawled live page (source-dump/_live/_home.html).
 * The previous hero slider is kept as a reusable block ("Homepage hero
 * slider") so it can be re-inserted from the editor in one click.
 *
 * Idempotent. Run: cp migration/26-homepage-live.php source-dump/_run.php &&
 * npx wp-env run cli wp eval-file wp-content/source-dump/_run.php
 *
 * @package EcoFridgeMigration
 */

require_once WP_CONTENT_DIR . '/source-dump/_lib-html-to-blocks.php';

$home_id = (int) get_option( 'page_on_front' );
$live    = file_get_contents( WP_CONTENT_DIR . '/source-dump/_live/_home.html' );
if ( ! $home_id || ! $live ) {
	WP_CLI::error( 'Front page or live homepage HTML missing.' );
}
$live = mb_convert_encoding( $live, 'UTF-8', 'UTF-8' );

$att = function ( $file ) {
	$id = attachment_url_to_postid( content_url( 'uploads/' . $file ) );
	if ( ! $id ) {
		WP_CLI::warning( "Attachment not found: $file" );
	}
	return (int) $id;
};
$between = function ( $start, $end, $from = 0 ) use ( $live ) {
	$i = strpos( $live, $start, $from );
	if ( false === $i ) {
		return '';
	}
	$j = strpos( $live, $end, $i + strlen( $start ) );
	return false === $j ? '' : substr( $live, $i, $j - $i );
};

/* 0. Archive the current hero slider as a reusable block ------------------ */
$current = get_post_field( 'post_content', $home_id );
if ( false !== strpos( $current, 'ef-hero' ) && ! get_page_by_path( 'homepage-hero-slider', OBJECT, 'wp_block' ) ) {
	$slides = array();
	$doc    = new DOMDocument();
	libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8"?>' . $current );
	$xp = new DOMXPath( $doc );
	foreach ( $xp->query( '//*[contains(@class,"ef-hero-slide")]' ) as $s ) {
		$q   = function ( $cls, $attr = null ) use ( $xp, $s ) {
			$n = $xp->query( './/*[contains(@class,"' . $cls . '")]', $s )->item( 0 );
			if ( ! $n ) {
				return '';
			}
			return $attr ? $n->getAttribute( $attr ) : trim( $n->textContent );
		};
		$img = $xp->query( './/img', $s )->item( 0 );
		$src = $img ? $img->getAttribute( 'src' ) : '';
		$slides[] = array(
			'eyebrow'    => $q( 'ef-hero-eyebrow' ),
			'title'      => $q( 'ef-hero-title' ),
			'text'       => $q( 'ef-hero-desc' ),
			'buttonText' => $q( 'ef-hero-btn' ),
			'buttonUrl'  => efm_relative_urls( $q( 'ef-hero-btn', 'href' ) ),
			'imageId'    => $src ? (int) attachment_url_to_postid( preg_replace( '~-\d+x\d+(\.[a-z]+)$~i', '$1', $src ) ) : 0,
			'imageUrl'   => efm_relative_urls( $src ),
			'imageAlt'   => $img ? $img->getAttribute( 'alt' ) : '',
		);
	}
	if ( $slides ) {
		wp_insert_post(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_name'    => 'homepage-hero-slider',
				'post_title'   => 'Homepage hero slider',
				'post_content' => wp_slash( efm_block( 'efb/hero-slider', array( 'slides' => $slides ) ) ),
			)
		);
		WP_CLI::log( 'Archived hero slider as reusable block (' . count( $slides ) . ' slides).' );
	}
}

/* 1. Offers ----------------------------------------------------------------- */
$offer_url = '/range/display-cabinets/standard-patisserie-slimline-display-fridges/';
$offers    = array();
preg_match_all( '~Special Offer\s*(?:&pound;|£|\x{FFFD}|&#163;)?\s*([0-9,]+)\s*\+VAT~u', $live, $prices );
$models = array( 'ECO-P60/90', 'ECO-P60/120', 'ECO-P60/150' );
$imgs   = array(
	array( 'ECO-P6090-Web.jpg', 'glass fronted fridge' ),
	array( 'ECO-P60120-Web.jpg', 'glass front fridge' ),
	array( 'ECO-P60150-Web.jpg', 'patisserie glass front fridge' ),
);
foreach ( $models as $k => $model ) {
	$price    = $prices[1][ $k ] ?? '';
	$offers[] = array(
		'label'    => 'Special Offer £' . $price . ' +VAT',
		'title'    => 'Patisserie Display',
		'subtitle' => $model,
		'url'      => $offer_url,
		'imageId'  => $att( $imgs[ $k ][0] ),
		'imageUrl' => '/wp-content/uploads/' . $imgs[ $k ][0],
		'imageAlt' => $imgs[ $k ][1],
	);
}

/* 2. Intro + equipment + SEO text (from the live HTML) --------------------- */
$article = $between( '<article', '</article>' );
$a_start = strpos( $article, '</table>' );
$intro   = substr( $article, $a_start + 8, strpos( $article, '<div class="desktop-only">' ) - $a_start - 8 );
$equip   = $between( '<h3><span style="font-size: 14pt;">Our commercial', '</ul>' ) . '</ul>';
$seo_i   = strpos( $live, 'Whatever your business needs' );
$seo_i   = strrpos( substr( $live, 0, $seo_i ), '<p' );
$seo_end = strpos( $live, '</div>', $seo_i );
$seo     = substr( $live, $seo_i, $seo_end - $seo_i );

/* 3. Logos (same order + names as the live strip) -------------------------- */
$logo_files = array(
	array( 'Paul-UK-web.jpg', 'Paul' ),
	array( 'Costa-Coffee-web.jpg', 'Costa' ),
	array( 'logo-wenzels-web.jpg', 'Wenzels' ),
	array( 'Caffe-Nero-web.jpg', 'Caffe Nero' ),
	array( 'Coffee1-web.jpg', 'Coffee #1' ),
	array( 'Moto-web.jpg', 'Moto' ),
	array( 'West-Cornwall-web.jpg', 'West Cornwall Pasty Co' ),
	array( 'hotel-chocolat-logo-web.jpg', 'Hotel Chocolat' ),
	array( 'Beyond-Bread.jpg', 'Beyond Bread' ),
	array( 'Welcome-Break.jpg', 'Welcome Break' ),
	array( 'Chopd.jpg', "Chop'd" ),
	array( 'Brains.jpg', 'Brains' ),
	array( 'Holiday-Inn.jpg', 'Holiday Inn' ),
	array( 'Odeon.jpg', 'Odeon' ),
	array( 'Harris-Hoole-web.jpg', 'Harris + Hoole' ),
	array( 'ESQUIRES-OCC-primary-logo-solid-_color.jpg', 'Esquires Coffee' ),
	array( 'Bewiched-Coffee.jpg', 'Bewiched Coffee' ),
	array( 'Bagel-Factory.jpg', 'Bagel Factory' ),
);
$logos      = array();
foreach ( $logo_files as $l ) {
	$logos[] = array(
		'id'   => $att( $l[0] ),
		'url'  => '/wp-content/uploads/' . $l[0],
		'alt'  => $l[1],
		'link' => '',
	);
}

/* 4. Category tiles ---------------------------------------------------------- */
$display = get_term_by( 'slug', 'display-cabinets', 'product_cat' );
$storage = get_term_by( 'slug', 'storage-cabinets', 'product_cat' );

/* 5. Assemble ---------------------------------------------------------------- */
$section = function ( $inner, $attrs = array() ) {
	return efm_block( 'efb/section', array_merge( array( 'paddingTop' => 24, 'paddingBottom' => 24 ), $attrs ), $inner );
};

$content  = $section(
	efm_block( 'heading', array( 'level' => 1, 'className' => 'efb-page-title' ), '<h1 class="wp-block-heading efb-page-title">ECO-Fridge UK: Commercial Refrigeration Manufacturer</h1>' )
	. efm_block( 'efb/offer-cards', array( 'items' => $offers, 'columns' => 3 ) )
	. efm_html_to_blocks( $intro ),
	array( 'paddingTop' => 32 )
);
$content .= $section(
	efm_block( 'paragraph', array(), '<p>Some of Our Clients</p>' )
	. efm_block( 'efb/logo-grid', array( 'logos' => $logos, 'columns' => 10 ) )
	. efm_html_to_blocks( $equip )
);
$content .= $section(
	efm_block( 'efb/category-tiles', array( 'parent' => $display ? $display->term_id : 0, 'heading' => 'Display Cabinets', 'columns' => 4, 'headingLink' => false ) )
);
$content .= $section(
	efm_block( 'efb/category-tiles', array( 'parent' => $storage ? $storage->term_id : 0, 'heading' => 'Storage Cabinets', 'columns' => 4, 'headingLink' => false ) )
);
$content .= $section(
	efm_block(
		'efb/category-tiles',
		array(
			'heading' => 'Graded Units',
			'columns' => 4,
			'items'   => array(
				array(
					'title'    => 'Refurbished & End of Line Cabinets',
					'url'      => '/graded-units/',
					'imageId'  => $att( 'Graded-Units-72dpi.jpg' ),
					'imageUrl' => '/wp-content/uploads/Graded-Units-72dpi.jpg',
				),
			),
		)
	)
);
$content .= $section( efm_html_to_blocks( $seo ), array( 'paddingBottom' => 48 ) );

wp_update_post(
	array(
		'ID'           => $home_id,
		'post_content' => wp_slash( $content ),
	)
);
update_post_meta( $home_id, '_efb_built_from_live', gmdate( 'c' ) );

$blocks = parse_blocks( $content );
WP_CLI::log( 'Top-level sections: ' . count( array_filter( $blocks, fn( $b ) => $b['blockName'] ) ) );
WP_CLI::log( 'Intro chars: ' . strlen( wp_strip_all_tags( $intro ) ) . ', equipment chars: ' . strlen( wp_strip_all_tags( $equip ) ) . ', SEO chars: ' . strlen( wp_strip_all_tags( $seo ) ) );
WP_CLI::success( "Homepage $home_id rebuilt from the live site." );
