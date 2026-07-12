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

// —— helper: grilă produse Spectra Post Grid ——
$grid = function ( $cat_id, $count = 6 ) use ( $bid ) {
	return '<!-- wp:uagb/post-grid {"block_id":"' . $bid() . '","postType":"product","postsToShow":' . $count . ',"columns":3,"tcolumns":2,"mcolumns":1,"taxonomyType":"product_cat","categories":"' . $cat_id . '","displayPostExcerpt":false,"displayPostAuthor":false,"displayPostComment":false,"displayPostDate":false,"displayPostTaxonomy":false,"imgSize":"woocommerce_thumbnail","paginationMarkup":"empty"} /-->';
};

/* ---------------- construcție pagină ---------------- */
$parts = array();

// 1. HERO
$parts[] = $container(
	$heading( 'ECO-Fridge UK — Commercial Refrigeration Manufacturer', 'h1' )
	. $para( 'Display and Catering Commercial Refrigeration Equipment. Market-leading, energy efficient and sustainable commercial fridges &amp; freezers — buy online with trade prices and free delivery.' )
	. '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons">'
	. '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/product-category/display-cabinets/">Shop Display Cabinets</a></div><!-- /wp:button -->'
	. '<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/product-category/graded-units/">Used / Graded Units</a></div><!-- /wp:button -->'
	. '</div><!-- /wp:buttons -->',
	'#eef4e7'
);

// 2. BRAND INTRO
$parts[] = $container(
	$heading( 'ECO-FRIDGE UK: Commercial Refrigeration Manufacturer' )
	. $para( 'ECO-Fridge Ltd has become one of the best commercial refrigeration equipment manufacturers and providers in the UK. An outstanding range of commercial fridge and freezer categories is available to buy online. With over 30 years of experience, we provide a variety of multideck, patisserie, serve-over, upright and storage models — built to be energy efficient and reliable.' )
	. $button( 'Read more about ECO-Fridge', '/about-us/' )
);

// 3. PRODUCT OVERVIEW
$parts[] = $container(
	$heading( 'Our commercial refrigeration equipment' )
	. $para( 'Browse our full range of display cabinets, storage cabinets and refurbished units — patisserie and multideck display fridges, grab &amp; go chillers, serve-over counters, upright and under-counter storage, bakery retarder provers and low-temperature freezers.' ),
	'', true
);

// 4. DISPLAY CABINETS grid
$parts[] = $container( $heading( 'Display Cabinets' ) . $grid( 44, 6 ) );

// 5. STORAGE CABINETS grid (alt bg)
$parts[] = $container( $heading( 'Storage Cabinets' ) . $grid( 45, 6 ), '#f8f9fa', true );

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
