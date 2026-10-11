<?php
/**
 * Eco-Fridge Child — child theme functions.
 *
 * Hosts ONLY the logic that doesn't express cleanly in Astra Pro / Gutenberg:
 *  - "from-price" / £POA pricing on products (product meta)
 *  - delivery notes + T&C + add-ons on the product page (Woo hook, in inc/woo-single.php)
 *  - legacy order number, featured image on the blog, [ef_subcats] shortcode
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

// Custom 2-row header (logo/utility/search + mega categories/icons/cart),
// driven 100% by the authoritative WP menu (Primary Menu #342). Footer/banner stay native.
require_once get_stylesheet_directory() . '/inc/header.php';

// Custom widget for the (contextual) subcategory filter in the shop sidebar.
require_once get_stylesheet_directory() . '/inc/shop-filters.php';

// Categories' long-form content (below the product grid) + editing from the dashboard.
require_once get_stylesheet_directory() . '/inc/category-content.php';

// Subcategory carousel on Commercial Fridge / Commercial Freezer (from the WP menu).
require_once get_stylesheet_directory() . '/inc/category-carousel.php';

// Customize controls for the green bar: cart icon (native Astra) + sticky.
require_once get_stylesheet_directory() . '/inc/header-controls.php';

// Order statuses: the shop's fulfilment flow (Processed / Shipped / Returned).
require_once get_stylesheet_directory() . '/inc/order-statuses.php';

/**
 * Google fonts 1:1 with the original: Oswald (headings) + Open Sans (body).
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'eco-fridge-fonts',
		'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Oswald:wght@400;500;600;700&display=swap',
		array(),
		null
	);
}, 5 );

/**
 * Load the child theme style after the Astra parent's.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'eco-fridge-child',
		get_stylesheet_uri(),
		array( 'astra-theme-css' ),
		wp_get_theme()->get( 'Version' )
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
 * Featured image on blog posts (single post), shown AFTER the title +
 * meta details, before the content. Modern Astra doesn't render it by default
 * in the `ast-dynamic-single-post-structure` structure (only title+meta), and
 * the native "single-image" variant sets it as a background — so we render it
 * here, on `astra_single_post_banner_after` (right after title+meta, in the header).
 */
add_action( 'astra_single_post_banner_after', function () {
	if ( ! is_singular( 'post' ) || ! has_post_thumbnail() ) {
		return;
	}
	echo '<div class="ef-single-featured">'
		. get_the_post_thumbnail( get_the_ID(), 'large', array( 'loading' => 'eager' ) )
		. '</div>';
}, 10 );

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
		wp_enqueue_style(
			'eco-fridge-fonts',
			'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Oswald:wght@400;500;600;700&display=swap',
			array(),
			null
		);
	}
} );
