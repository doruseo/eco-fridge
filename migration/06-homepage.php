<?php
/**
 * Pasul 6: construiește HOMEPAGE-ul în blocuri Spectra (uagb) — design 1:1 eco-fridge.co.uk.
 * Rulare: wp eval-file wp-content/source-dump/_run.php
 *
 * Structură (după original-site-live-audit.md):
 *   Hero → Brand intro → Product overview → Display Cabinets (grid) →
 *   Storage Cabinets (grid) → Graded Units → conținut SEO (pagina 7 legacy).
 *
 * Spectra: uagb/container, uagb/advanced-heading, uagb/post-grid (produse).
 * Core (fiabile + stilizate din tema copil): heading/paragraph/buttons/html.
 *
 * @package Eco-Fridge-Migration
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$LP = '43lz2ypq8_';
$db = @mysqli_connect( 'ef-legacy-db', 'root', 'root', 'legacy' );
$seo_html = '';
if ( $db ) {
	mysqli_set_charset( $db, 'utf8mb4' );
	$r = mysqli_query( $db, "SELECT post_content FROM {$LP}posts WHERE ID=7" );
	if ( $r && ( $row = mysqli_fetch_row( $r ) ) ) {
		$seo_html = $row[0];
		// re-scriere URL-uri absolute vechi -> relative (parametrizare domeniu)
		$seo_html = str_replace( array( 'https://eco-fridge.co.uk', 'http://eco-fridge.co.uk' ), '', $seo_html );
	}
}

$bid = function () { return substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyz0123456789' ), 0, 8 ); };

// —— helper: container Spectra cu conținut ——
$container = function ( $inner, $bg = '', $alt = false ) use ( $bid ) {
	$id = $bid();
	$bg_attr = $bg ? ',"backgroundType":"color","backgroundColor":"' . $bg . '"' : '';
	$cls = 'wp-block-uagb-container uagb-block-' . $id . ' alignfull uagb-is-root-container' . ( $alt ? ' ef-section-alt' : '' );
	return '<!-- wp:uagb/container {"block_id":"' . $id . '","contentWidth":"full_width","innerContentWidth":"alignwide","directionDesktop":"column","topPaddingDesktop":55,"bottomPaddingDesktop":55,"leftPaddingDesktop":20,"rightPaddingDesktop":20' . $bg_attr . '} -->'
		. '<div class="' . $cls . '"><div class="uagb-container-inner-blocks-wrap">' . $inner . '</div></div>'
		. '<!-- /wp:uagb/container -->';
};

// —— helper: Advanced Heading Spectra ——
$heading = function ( $text, $tag = 'h2', $center = true ) use ( $bid ) {
	$id = $bid();
	$align = $center ? ',"headingAlign":"center"' : '';
	return '<!-- wp:uagb/advanced-heading {"block_id":"' . $id . '","headingTitle":"' . esc_attr( $text ) . '","headingTag":"' . $tag . '","headingColor":"#333333"' . $align . '} -->'
		. '<div class="wp-block-uagb-advanced-heading uagb-block-' . $id . '"><' . $tag . ' class="uagb-heading-text ef-section-title">' . esc_html( $text ) . '</' . $tag . '></div>'
		. '<!-- /wp:uagb/advanced-heading -->';
};

// —— helper: paragraf core, centrat ——
$para = function ( $html, $center = true ) {
	$cls = $center ? ' class="has-text-align-center"' : '';
	$style = ' style="max-width:820px;margin-left:auto;margin-right:auto;"';
	return '<!-- wp:paragraph' . ( $center ? ' {"align":"center"}' : '' ) . ' --><p' . $cls . $style . '>' . $html . '</p><!-- /wp:paragraph -->';
};

// —— helper: buton core (stilizat brand din tema copil) ——
$button = function ( $label, $url, $center = true ) {
	$wrap_attr = $center ? ' {"layout":{"type":"flex","justifyContent":"center"}}' : '';
	return '<!-- wp:buttons' . $wrap_attr . ' --><div class="wp-block-buttons">'
		. '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></div><!-- /wp:button -->'
		. '</div><!-- /wp:buttons -->';
};

// —— helper: HERO slider 4 slide-uri (1:1 audit) — full-bleed wp:html ——
$hero_slider = function () {
	// eyebrow, titlu, descriere, label CTA, slug categorie, imagine reprezentativă
	$slides = array(
		array( 'Premium Range', 'Mirrored Stainless Steel Shutter Multidecks', 'Lockable roller-shutter multidecks with a premium mirrored stainless steel finish — secure overnight, striking on the shop floor.', 'Shop Shutter Multidecks', 'premium-mirrored-stainless-steel-shutter-multidecks', 'SD75120SH-Newweb-724x1024.jpg' ),
		array( 'Slimline', 'Slimline Patisserie Display Fridges', 'Space-saving double-glazed patisserie cabinets that show off cakes, desserts &amp; chilled treats.', 'Shop Slimline Patisseries', 'standard-patisserie-slimline-display-fridges', 'ECO-P60120B-web-984x1024.jpg' ),
		array( 'Slimline', 'Slimline Multideck Display Fridges', 'Black steel slimline multidecks — a big open display in a small footprint, ideal for grab &amp; go.', 'Shop Slimline Multidecks', 'black-steel-slimline-multideck-display-fridge', 'BD60150-web-874x1024.png' ),
		array( 'Bakery', 'Bakery Retarder Provers', 'Stainless steel upright bakery cabinets — retarder provers, fridges &amp; freezers built for the bakery.', 'Shop Bakery Cabinets', 'stainless-steel-upright-bakery-cabinets', 'BK600RP-web-scaled-556x1024.jpg' ),
	);
	$slides_html = '';
	$dots_html    = '';
	foreach ( $slides as $n => $s ) {
		list( $eyebrow, $title, $desc, $cta, $slug, $img ) = $s;
		$active = 0 === $n ? ' is-active' : '';
		$slides_html .= '<div class="ef-hero-slide' . $active . '"><div class="ef-hero-inner">'
			. '<div class="ef-hero-text">'
			. '<span class="ef-hero-eyebrow">' . esc_html( $eyebrow ) . '</span>'
			. '<h2 class="ef-hero-title">' . esc_html( $title ) . '</h2>'
			. '<p class="ef-hero-desc">' . $desc . '</p>'
			. '<a class="ef-hero-btn" href="/product-category/' . esc_attr( $slug ) . '/">' . esc_html( $cta ) . '</a>'
			. '</div>'
			. '<div class="ef-hero-media"><img src="/wp-content/uploads/' . esc_attr( $img ) . '" alt="' . esc_attr( $title ) . '" loading="eager" /></div>'
			. '</div></div>';
		$dots_html .= '<button class="ef-hero-dot' . $active . '" data-i="' . $n . '" aria-label="' . esc_attr( 'Slide ' . ( $n + 1 ) ) . '"></button>';
	}
	$html = '<div class="ef-hero" data-autoplay="6000">'
		. '<div class="ef-hero-track">' . $slides_html . '</div>'
		. '<button class="ef-hero-nav ef-hero-prev" aria-label="Previous slide">&lsaquo;</button>'
		. '<button class="ef-hero-nav ef-hero-next" aria-label="Next slide">&rsaquo;</button>'
		. '<div class="ef-hero-dots">' . $dots_html . '</div>'
		. '</div>';
	return '<!-- wp:html -->' . "\n" . $html . "\n" . '<!-- /wp:html -->';
};

// —— helper: bandă logo-uri clienți (1:1 cu originalul, pagina 7) ——
$clients = function () use ( $heading ) {
	// nume brand (alt) => fișier logo, în ordinea din originalul
	$logos = array(
		'Paul'                    => 'Paul-UK-web.jpg',
		'Costa Coffee'            => 'Costa-Coffee-web.jpg',
		"Wenzel's"                => 'logo-wenzels-web.jpg',
		'Caff&egrave; Nero'       => 'Caffe-Nero-web.jpg',
		'Coffee#1'                => 'Coffee1-web.jpg',
		'Moto'                    => 'Moto-web.jpg',
		'West Cornwall Pasty Co'  => 'West-Cornwall-web.jpg',
		'Hotel Chocolat'          => 'hotel-chocolat-logo-web.jpg',
		'Beyond Bread'            => 'Beyond-Bread.jpg',
		'Welcome Break'           => 'Welcome-Break.jpg',
		'Chopd'                   => 'Chopd.jpg',
		'Brains'                  => 'Brains.jpg',
		'Holiday Inn'             => 'Holiday-Inn.jpg',
		'Odeon'                   => 'Odeon.jpg',
		'Harris + Hoole'          => 'Harris-Hoole-web.jpg',
		'Esquires Coffee'         => 'ESQUIRES-OCC-primary-logo-solid-_color.jpg',
		'Bewiched Coffee'         => 'Bewiched-Coffee.jpg',
		'Bagel Factory'           => 'Bagel-Factory.jpg',
	);
	$items = '';
	foreach ( $logos as $brand => $file ) {
		$items .= '<div class="ef-client-logo"><img src="/wp-content/uploads/' . esc_attr( $file ) . '" alt="' . esc_attr( html_entity_decode( $brand ) ) . '" loading="lazy" /></div>';
	}
	return $heading( 'Some of Our Clients' )
		. '<!-- wp:html -->' . "\n"
		. '<p class="ef-clients-intro">As a manufacturer, we&rsquo;re always focused on our customers&rsquo; needs &mdash; and that&rsquo;s why some of the world&rsquo;s most recognisable brands trust ECO-Fridge.</p>'
		. '<div class="ef-clients-grid">' . $items . '</div>' . "\n"
		. '<!-- /wp:html -->';
};

// —— helper: grilă produse Spectra Post Grid ——
$grid = function ( $cat_id, $count = 6 ) use ( $bid ) {
	return '<!-- wp:uagb/post-grid {"block_id":"' . $bid() . '","postType":"product","postsToShow":' . $count . ',"columns":3,"tcolumns":2,"mcolumns":1,"taxonomyType":"product_cat","categories":"' . $cat_id . '","displayPostExcerpt":false,"displayPostAuthor":false,"displayPostComment":false,"displayPostDate":false,"displayPostTaxonomy":false,"imgSize":"woocommerce_thumbnail","paginationMarkup":"empty"} /-->';
};

/* ---------------- construcție pagină ---------------- */
$parts = array();

// 1. HERO SLIDER (4 slide-uri, full-bleed)
$parts[] = $hero_slider();

// 2. BRAND INTRO
$parts[] = $container(
	$heading( 'ECO-FRIDGE UK: Commercial Refrigeration Manufacturer' )
	. $para( 'ECO-Fridge Ltd has become one of the best commercial refrigeration equipment manufacturers and providers in the UK. An outstanding range of commercial fridge and freezer categories is available to buy online. With over 30 years of experience, we provide a variety of multideck, patisserie, serve-over, upright and storage models — built to be energy efficient and reliable.' )
	. $button( 'Read more about ECO-Fridge', '/about-us/' )
);

// 2b. CLIENT LOGOS („Some of Our Clients") — fundal alb (conținut curat)
$parts[] = $container( $clients() );

// 3. PRODUCT OVERVIEW
$parts[] = $container(
	$heading( 'Our commercial refrigeration equipment' )
	. $para( 'Browse our full range of display cabinets, storage cabinets and refurbished units — patisserie and multideck display fridges, grab &amp; go chillers, serve-over counters, upright and under-counter storage, bakery retarder provers and low-temperature freezers.' ),
	'', true
);

// —— helper: bloc shortcode sub-categorii (tile-uri cu iconițe) ——
$subcats = function ( $parent, $cols = 5 ) {
	return '<!-- wp:shortcode -->' . "\n" . '[ef_subcats parent="' . (int) $parent . '" cols="' . (int) $cols . '"]' . "\n" . '<!-- /wp:shortcode -->';
};

// 4. DISPLAY CABINETS — tile-uri sub-categorii (iconițe, 5 coloane) ca originalul
$parts[] = $container( $heading( 'Display Cabinets' ) . $subcats( 44, 5 ) );

// 5. STORAGE CABINETS — tile-uri sub-categorii (fundal alb)
$parts[] = $container( $heading( 'Storage Cabinets' ) . $subcats( 45, 5 ) );

// 6. GRADED UNITS
$parts[] = $container(
	$heading( 'Graded Units' )
	. $para( 'Refurbished &amp; end-of-line cabinets at reduced prices — fully checked and ready to work. Limited stock, updated regularly.' )
	. $button( 'View Refurbished &amp; End of Line Cabinets', '/product-category/graded-units/' )
);

// 7. SEO long-form (conținut original pagina 7)
if ( $seo_html ) {
	$parts[] = $container(
		'<!-- wp:html -->' . "\n" . '<div class="ef-seo-content">' . $seo_html . '</div>' . "\n" . '<!-- /wp:html -->',
		'', false
	);
}

$content = implode( "\n\n", $parts );

$home = get_page_by_path( 'home' );
if ( ! $home ) {
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Home', 'post_name' => 'home', 'post_status' => 'publish', 'post_content' => $content ) );
} else {
	$id = $home->ID;
	wp_update_post( array( 'ID' => $id, 'post_content' => $content ) );
}
// pagină fără titlu vizibil + lățime completă (Astra)
update_post_meta( $id, 'site-post-title', 'disabled' );
update_post_meta( $id, 'ast-title-bar-display', 'disabled' );
update_post_meta( $id, 'site-content-layout', 'page-builder' );
update_post_meta( $id, 'ast-global-header-display', '' );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $id );

WP_CLI::log( 'Homepage construit: ' . strlen( $content ) . ' bytes, ' . count( $parts ) . ' secțiuni. Page ID=' . $id );
WP_CLI::success( 'Homepage Spectra finalizat.' );
