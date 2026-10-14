<?php
/**
 * Content pages styled by assets/pages.css: home page (range carousel, market
 * buttons, blog carousel and closing guide), About Us, Privacy Policy, Cookie Policy and Terms & Conditions.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Whether the current page uses one of the ECO-Fridge content blocks whose
 * look lives in the theme (pages.css / blog.css).
 */
function ef_page_has_theme_blocks() {
	$post = is_singular() ? get_post() : null;
	if ( ! $post ) { return false; }
	foreach ( array( 'efb/highlights', 'efb/range-carousel', 'efb/market-buttons', 'efb/post-carousel', 'efb/help-box' ) as $block ) {
		if ( has_block( $block, $post ) ) { return true; }
	}
	return false;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! ( is_front_page() || is_page( array( 'about-us', 'privacy-cookies-policy', 'cookie-policy-uk', 'terms-conditions' ) ) || ef_page_has_theme_blocks() ) ) { return; }
	$file = get_stylesheet_directory() . '/assets/pages.css';
	wp_enqueue_style( 'ef-pages', get_stylesheet_directory_uri() . '/assets/pages.css', array( 'eco-fridge-child' ), (string) filemtime( $file ) );
}, 20 );

/**
 * [ef_home_markets] — buttons to every market landing page, by market name
 * (each links to its /markets/<slug>/ page, or /market/<slug>/ without one).
 */
add_shortcode( 'ef_home_markets', function () {
	$terms = get_terms( array( 'taxonomy' => 'market', 'hide_empty' => true, 'orderby' => 'name' ) );
	if ( is_wp_error( $terms ) || ! $terms ) { return ''; }
	$items = '';
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) { continue; }
		$items .= '<li><a class="ef-pill" href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a></li>';
	}
	return '<nav class="ef-home-markets" aria-label="' . esc_attr__( 'Refrigeration by market', 'eco-fridge-child' ) . '"><ul>' . $items . '</ul></nav>';
} );

/**
 * Meta description for the pages the blog's own description doesn't cover
 * (home, pages, shop, products, categories, tags, markets): the page excerpt
 * or first paragraph, the product's name, price and short description, or the
 * term's short description.
 *
 * @return string Plain text, at most 158 characters; '' when there is none.
 */
function ef_page_meta_description() {
	if ( is_home() || is_category() || is_singular( 'post' ) || is_paged() ) { return ''; }
	// Cart, checkout and account pages are noindex – and rendering their content here
	// (in <head>) would run the WooCommerce shortcodes early and use up their notices.
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) { return ''; }
	$desc = '';
	if ( is_front_page() ) {
		$desc = 'ECO-Fridge supplies commercial fridges, freezers and display cabinets for cafés, shops, restaurants and caterers across the UK – trade prices, free UK mainland delivery and over 30 years of expert advice.';
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			// Name and trade price first, then the short description without its
			// "Click here for energy specification" style link lines.
			$specs = preg_replace( '~click here[^.\n<]*~i', '', (string) ( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ) );
			$price = '' !== (string) $product->get_price() ? ' £' . number_format( (float) $product->get_price(), 0 ) . ' + VAT.' : '.';
			$desc  = $product->get_name() . $price . ' ' . wp_strip_all_tags( $specs ) . ' Free UK mainland delivery.';
		}
	} elseif ( is_tax() || is_tag() ) {
		$desc = term_description();
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$desc = 'Shop commercial fridges, freezers, display cabinets and storage cabinets online at trade prices, with free delivery to UK mainland addresses.';
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		$desc = has_excerpt( $post ) ? $post->post_excerpt : ( preg_match( '~<p[^>]*>(.*?)</p>~is', apply_filters( 'the_content', strip_shortcodes( $post->post_content ) ), $m ) ? $m[1] : '' );
	}
	$desc = trim( preg_replace( '/[\s\x{00A0}]+/u', ' ', html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $desc ) ), ENT_QUOTES, 'UTF-8' ) ) );
	return '' !== $desc ? wp_html_excerpt( $desc, 158, '…' ) : '';
}

/** Without an SEO plugin the theme prints that description itself. */
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) { return; }
	$desc = ef_page_meta_description();
	if ( '' !== $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
}, 2 );

/**
 * Whether the current page has its own SEO description in Rank Math (written
 * by hand or brought over from Yoast).
 *
 * @return bool
 */
function ef_has_own_seo_description() {
	$object = get_queried_object();
	if ( $object instanceof WP_Term ) {
		return '' !== (string) get_term_meta( $object->term_id, 'rank_math_description', true );
	}
	$id = get_queried_object_id();
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$id = (int) wc_get_page_id( 'shop' );
	}
	return $id > 0 && '' !== (string) get_post_meta( $id, 'rank_math_description', true );
}

/**
 * Rank Math: where a page has no description of its own, use the theme's
 * (the blog's on blog pages) instead of Rank Math's generic template – e.g.
 * products get name, trade price and specs rather than the raw spec list.
 */
add_filter( 'rank_math/frontend/description', function ( $desc ) {
	if ( ef_has_own_seo_description() ) { return $desc; }
	$own = ( is_home() || is_category() || is_singular( 'post' ) ) && function_exists( 'ef_blog_meta_description' ) ? ef_blog_meta_description() : ef_page_meta_description();
	return '' !== $own ? $own : $desc;
}, 20 );

/**
 * Rank Math: pages have no Article schema (as with Yoast), but still the site's
 * Organization, WebSite and WebPage entities.
 */
add_filter( 'rank_math/schema/add_global_entities', function ( $can_add ) {
	return $can_add || is_page();
} );

/** Rank Math's schema already has the breadcrumb trail: no second one from WooCommerce. */
add_filter( 'woocommerce_structured_data_breadcrumblist', function ( $markup ) {
	return defined( 'RANK_MATH_VERSION' ) ? array() : $markup;
} );

/**
 * Block editor: the theme styles of the content blocks (cards, carousels,
 * highlights, help box…), so their previews look like the site.
 */
add_action( 'enqueue_block_assets', function () {
	if ( ! is_admin() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'ef-editor-theme', get_stylesheet_uri(), array(), (string) filemtime( $dir . '/style.css' ) );
	wp_enqueue_style( 'ef-editor-blog', $uri . '/assets/blog.css', array( 'ef-editor-theme' ), (string) filemtime( $dir . '/assets/blog.css' ) );
	wp_enqueue_style( 'ef-editor-pages', $uri . '/assets/pages.css', array( 'ef-editor-blog' ), (string) filemtime( $dir . '/assets/pages.css' ) );
} );

