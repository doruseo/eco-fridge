<?php
/**
 * Content pages styled by assets/pages.css: home page (range carousel, market
 * buttons, blog carousel and closing guide), About Us, Privacy Policy, Cookie Policy and Terms & Conditions.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_enqueue_scripts', function () {
	if ( ! ( is_front_page() || is_page( array( 'about-us', 'privacy-cookies-policy', 'cookie-policy-uk', 'terms-conditions' ) ) ) ) { return; }
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
 * (home, pages, shop, products, categories, tags, markets), until an SEO plugin
 * is installed: the page excerpt or first paragraph, the product's short
 * description, or the term's short description.
 */
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || is_home() || is_category() || is_singular( 'post' ) || is_paged() ) { return; }
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
		$desc = has_excerpt( $post ) ? $post->post_excerpt : ( preg_match( '~<p[^>]*>(.*?)</p>~is', apply_filters( 'the_content', $post->post_content ), $m ) ? $m[1] : '' );
	}
	$desc = trim( preg_replace( '/[\s\x{00A0}]+/u', ' ', html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $desc ) ), ENT_QUOTES, 'UTF-8' ) ) );
	if ( '' !== $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_html_excerpt( $desc, 158, '…' ) ) . '">' . "\n";
	}
}, 2 );
