<?php
/**
 * Eco-Fridge Child — child theme functions.
 *
 * Hosts ONLY the logic that doesn't express cleanly in Astra Pro / Gutenberg:
 *  - "from-price" / £POA pricing on products (product meta)
 *  - delivery notes + T&C + add-ons on the product page (Woo hook, in inc/woo-single.php)
 *  - legacy order number, [ef_subcats] shortcode
 *
 * Content blocks (sections, slider, offers, logos, tiles, search, forms, spec
 * tables…) live in the ECO-Fridge Blocks plugin, not in the theme.
 *
 * Header, mega menu, footer and the "Call us today" banner are now NATIVE Astra
 * (Header/Footer Builder + Nav Menu mega + Custom Layout), configured from
 * migration/16-header-footer-native.php and editable from the dashboard — there is
 * no header/footer code here anymore.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Single product page logic + add-ons (price +VAT/POA, notes, warranty, removal).
require_once get_stylesheet_directory() . '/inc/woo-single.php';
// Product page: price with VAT, Model, Brand, finance badge, feature icons, tabs.
require_once get_stylesheet_directory() . '/inc/product-details.php';

// Custom 2-row header (logo/utility/search + mega categories/icons/cart),
// driven 100% by the authoritative WP menu (Primary Menu #342). Footer/banner stay native.
require_once get_stylesheet_directory() . '/inc/header.php';

// Product filter bar (facets, counts, filtered URLs) + the shop sidebar's layout.
require_once get_stylesheet_directory() . '/inc/product-filters.php';
require_once get_stylesheet_directory() . '/inc/shop-filters.php';

// Categories' long-form content (below the product grid) + editing from the dashboard.
require_once get_stylesheet_directory() . '/inc/category-content.php';

// Subcategory carousel on Commercial Fridge / Commercial Freezer (from the WP menu).
require_once get_stylesheet_directory() . '/inc/category-carousel.php';

// Customize controls for the green bar: cart icon (native Astra) + sticky.
require_once get_stylesheet_directory() . '/inc/header-controls.php';

// Order statuses: the shop's fulfilment flow (Processed / Shipped / Returned).
require_once get_stylesheet_directory() . '/inc/order-statuses.php';

// Product grids: quantity − / + next to "Add to cart".
require_once get_stylesheet_directory() . '/inc/shop-loop.php';

// Cart and checkout: trust strip, free delivery wording, continue shopping.
require_once get_stylesheet_directory() . '/inc/cart-checkout.php';

// Blog: /blog/, topic categories, tags and articles (templates: home.php, category.php, tag.php, single-post.php).
require_once get_stylesheet_directory() . '/inc/blog.php';

// Content pages (home, About Us, legal pages): their stylesheet.
require_once get_stylesheet_directory() . '/inc/pages.php';

/**
 * Fonts 1:1 with the original: Oswald (headings) + Open Sans (body), self-hosted
 * (assets/fonts, the same variable woff2 files Google Fonts serves). Same-origin
 * files with a preload start downloading with the HTML instead of after a
 * render-blocking stylesheet on another domain, so they are usually ready by the
 * first paint (better LCP, no layout shift from the font swap).
 */
function ef_fonts_css() {
	$dir    = get_stylesheet_directory_uri() . '/assets/fonts/';
	$latin  = 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD';
	$ext    = 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C4, U+2113, U+2C60-2C7F, U+A720-A7FF';
	$faces  = array(
		array( 'Open Sans', '400 700', 'open-sans-latin', $latin ),
		array( 'Open Sans', '400 700', 'open-sans-latin-ext', $ext ),
		array( 'Oswald', '400 700', 'oswald-latin', $latin ),
		array( 'Oswald', '400 700', 'oswald-latin-ext', $ext ),
	);
	$css = '';
	foreach ( $faces as $f ) {
		$css .= "@font-face{font-family:'{$f[0]}';font-style:normal;font-weight:{$f[1]};font-display:swap;"
			. "src:url({$dir}{$f[2]}.woff2) format('woff2');unicode-range:{$f[3]}}";
	}
	return $css;
}

/** Preload the two Latin font files and declare the faces inline, first thing in <head>. */
add_action( 'wp_head', function () {
	$dir = get_stylesheet_directory_uri() . '/assets/fonts/';
	foreach ( array( 'open-sans-latin', 'oswald-latin' ) as $file ) {
		echo '<link rel="preload" href="' . esc_url( $dir . $file . '.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
	echo '<style id="ef-fonts">' . ef_fonts_css() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- static CSS built above
}, 1 );

/**
 * No WordPress emoji script on the front end: browsers draw emoji themselves.
 * On Windows (no flag emoji) the script swapped every emoji for an image from
 * s.w.org — an extra script and a third-party request.
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );

/**
 * WooCommerce swaps `woocommerce-no-js` for `woocommerce-js` on <body> from the
 * footer, i.e. only after the whole page has been parsed and painted; its CSS
 * keys rules on that class (e.g. the 1em gap under the breadcrumbs), so the page
 * jumped once the swap ran (CLS). Run the same swap right after <body> opens.
 */
add_action( 'wp_body_open', function () {
	if ( function_exists( 'wc_no_js' ) ) {
		wc_no_js();
	}
}, 1 );

/**
 * Load the child theme style after the Astra parent's.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'eco-fridge-child',
		get_stylesheet_uri(),
		array( 'astra-theme-css' ),
		// Cache-bust on every edit, so browsers never keep a stale stylesheet.
		wp_get_theme()->get( 'Version' ) . '.' . filemtime( get_stylesheet_directory() . '/style.css' )
	);
}, 15 );

/**
 * Show the original order number (from the old site) instead of the new ID.
 * Migrated orders keep the legacy ID in the `_ef_legacy_order_id` meta.
 */
add_filter( 'woocommerce_order_number', function ( $number, $order ) {
	$legacy = is_a( $order, 'WC_Order' ) ? $order->get_meta( '_ef_legacy_order_id' ) : '';
	return $legacy ? $legacy : $number;
}, 10, 2 );

/** Sort a list of terms by the `order` meta (1:1 with the original), then by name. */
function ef_sort_terms_by_order( $terms ) {
	usort( $terms, function ( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, 'order', true );
		$ob = (int) get_term_meta( $b->term_id, 'order', true );
		if ( $oa === $ob ) { return strcasecmp( $a->name, $b->name ); }
		if ( 0 === $oa ) { return 1; }   // no order → to the end
		if ( 0 === $ob ) { return -1; }
		return $oa - $ob;
	} );
	return $terms;
}

/**
 * A product category's image: the category thumbnail, falling back to the
 * main image of the first product in the category. Used by [ef_subcats].
 */
function ef_term_image_url( $term_id, $size = 'woocommerce_thumbnail' ) {
	$tid = get_term_meta( $term_id, 'thumbnail_id', true );
	if ( $tid ) {
		$u = wp_get_attachment_image_url( (int) $tid, $size );
		if ( $u ) { return $u; }
	}
	$q = get_posts( array(
		'post_type'   => 'product',
		'numberposts' => 1,
		'fields'      => 'ids',
		'tax_query'   => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term_id ) ),
		'meta_key'    => '_thumbnail_id',
	) );
	if ( $q ) {
		$u = get_the_post_thumbnail_url( $q[0], $size );
		if ( $u ) { return $u; }
	}
	return '';
}

/**
 * Shortcode [ef_subcats parent="44" cols="5"] — a grid of tiles with a category's
 * subcategories (icon + name + link), as on the original homepage (Display /
 * Storage Cabinets). Skips subcategories with no products.
 */
function ef_subcats_shortcode( $atts ) {
	$a = shortcode_atts( array( 'parent' => 0, 'cols' => 5 ), $atts, 'ef_subcats' );
	$parent = (int) $a['parent'];
	if ( ! $parent ) { return ''; }

	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => $parent,
		'hide_empty' => true,
	) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) { return ''; }
	$terms = ef_sort_terms_by_order( $terms ); // order 1:1 with the original

	$out = '<div class="ef-subcat-grid" style="--ef-cols:' . (int) $a['cols'] . '">';
	foreach ( $terms as $t ) {
		$img  = ef_term_image_url( $t->term_id );
		$link = get_term_link( $t );
		if ( is_wp_error( $link ) ) { continue; }
		$out .= '<a class="ef-subcat-tile" href="' . esc_url( $link ) . '">'
			. '<span class="ef-subcat-img">' . ( $img ? '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $t->name ) . '" loading="lazy" />' : '' ) . '</span>'
			. '<span class="ef-subcat-name">' . esc_html( $t->name ) . '</span>'
			. '</a>';
	}
	$out .= '</div>';
	return $out;
}
add_shortcode( 'ef_subcats', 'ef_subcats_shortcode' );

/**
 * "From £X +VAT" price — normalized from the _ef_from_price meta (migrated from Types),
 * falling back to the Woo price. Reused in grids + single product.
 */
function ef_from_price_html( $product_id ) {
	$raw = get_post_meta( $product_id, '_ef_from_price', true );
	$raw = trim( (string) $raw );
	if ( '' !== $raw ) {
		// migrated values already contain £ and/or +VAT in various forms — keep them 1:1
		$label = ( stripos( $raw, 'from' ) === 0 ) ? $raw : 'From ' . $raw;
		return '<div class="ef-from-price">' . wp_kses_post( $label ) . '</div>';
	}
	$product = wc_get_product( $product_id );
	if ( $product ) {
		$price = $product->get_regular_price();
		if ( '' !== $price && is_numeric( $price ) ) {
			return '<div class="ef-from-price">From <span class="amount">&pound;' . number_format( (float) $price, 0 ) . '</span> +VAT</div>';
		}
	}
	return '';
}

/**
 * Same fonts inside the block editor, so editing looks like the front end.
 */
add_action( 'enqueue_block_assets', function () {
	if ( is_admin() ) {
		wp_register_style( 'eco-fridge-fonts', false, array(), null );
		wp_enqueue_style( 'eco-fridge-fonts' );
		wp_add_inline_style( 'eco-fridge-fonts', ef_fonts_css() );
	}
} );
