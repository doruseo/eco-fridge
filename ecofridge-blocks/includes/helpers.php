<?php
/**
 * Shared helpers used by the blocks (term images, term ordering, "From £X +VAT").
 *
 * Every helper is guarded with function_exists() so the child theme can keep
 * defining the same functions without a fatal "cannot redeclare" error.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'efb_sort_terms_by_order' ) ) {
	/**
	 * Sort terms by the `order` term meta (1:1 with the original site), then by name.
	 * Terms without an order go to the end.
	 *
	 * @param WP_Term[] $terms Terms.
	 * @return WP_Term[]
	 */
	function efb_sort_terms_by_order( $terms ) {
		usort(
			$terms,
			static function ( $a, $b ) {
				$oa = (int) get_term_meta( $a->term_id, 'order', true );
				$ob = (int) get_term_meta( $b->term_id, 'order', true );
				if ( $oa === $ob ) {
					return strcasecmp( $a->name, $b->name );
				}
				if ( 0 === $oa ) {
					return 1;
				}
				if ( 0 === $ob ) {
					return -1;
				}
				return $oa - $ob;
			}
		);
		return $terms;
	}
}

if ( ! function_exists( 'efb_term_image_url' ) ) {
	/**
	 * A product category's image: its thumbnail, falling back to the main image
	 * of the first product in the category.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $size    Image size.
	 * @return string
	 */
	function efb_term_image_url( $term_id, $size = 'woocommerce_thumbnail' ) {
		$tid = (int) get_term_meta( $term_id, 'thumbnail_id', true );
		if ( $tid ) {
			$url = wp_get_attachment_image_url( $tid, $size );
			if ( $url ) {
				return $url;
			}
		}
		$ids = get_posts(
			array(
				'post_type'   => 'product',
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
				'tax_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $term_id,
					),
				),
			)
		);
		return $ids ? (string) get_the_post_thumbnail_url( $ids[0], $size ) : '';
	}
}

if ( ! function_exists( 'efb_from_price_html' ) ) {
	/**
	 * "From £X +VAT" label — from the migrated `_ef_from_price` meta, falling back
	 * to the WooCommerce regular price. Empty string when neither exists (POA).
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	function efb_from_price_html( $product_id ) {
		$raw = trim( (string) get_post_meta( $product_id, '_ef_from_price', true ) );
		if ( '' !== $raw ) {
			$label = ( 0 === stripos( $raw, 'from' ) ) ? $raw : 'From ' . $raw;
			return '<span class="efb-from-price">' . wp_kses_post( $label ) . '</span>';
		}
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product && is_numeric( $product->get_price() ) ) {
				return '<span class="efb-from-price">From <span class="amount">&pound;' . esc_html( number_format( (float) $product->get_price(), 0 ) ) . '</span> +VAT</span>';
			}
		}
		return '';
	}
}

/**
 * Make an absolute URL that points to this site (or to the legacy domains)
 * root-relative, so links survive a domain change.
 *
 * @param string $url URL.
 * @return string
 */
function efb_relative_url( $url ) {
	$hosts = array( home_url(), 'https://www.eco-fridge.co.uk', 'https://eco-fridge.co.uk', 'https://eco-fridge.o2dev.de', 'http://www.eco-fridge.co.uk' );
	foreach ( $hosts as $host ) {
		if ( 0 === strpos( $url, $host . '/' ) ) {
			return substr( $url, strlen( $host ) );
		}
	}
	return $url;
}

/**
 * width/height attributes for an image URL whose file name carries its size
 * (WordPress' "-300x630.jpg" thumbnails), so the browser reserves the space
 * before the image loads. Empty string when the name has no size.
 *
 * @param string $url Image URL.
 * @return string
 */
function efb_img_size_attrs( $url ) {
	if ( preg_match( '~-(\d{2,4})x(\d{2,4})\.(?:jpe?g|png|gif|webp|avif)$~i', (string) wp_parse_url( $url, PHP_URL_PATH ), $m ) ) {
		return ' width="' . (int) $m[1] . '" height="' . (int) $m[2] . '"';
	}
	return '';
}

/**
 * Icons of the Highlights block (24×24 SVG paths), by key.
 *
 * @return array<string, array{label:string, path:string}>
 */
function efb_highlight_icons() {
	return array(
		'truck'  => array( 'label' => 'Delivery truck', 'path' => 'M3 6h11v9h2.2l2.3-4H21v6h-1.3a2.5 2.5 0 0 1-4.9 0H9.7a2.5 2.5 0 0 1-4.9 0H3zm14.3 6.5L16 15h4v-2.5zM7.3 16.3a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm10 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z' ),
		'star'   => array( 'label' => 'Star', 'path' => 'M12 2l2.9 6.1 6.6.8-4.9 4.6 1.3 6.5L12 16.8 6.1 20l1.3-6.5L2.5 8.9l6.6-.8z' ),
		'tag'    => array( 'label' => 'Price tag', 'path' => 'M4 4h9l7 7-9 9-7-7zm4.5 3a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z' ),
		'shield' => array( 'label' => 'Shield (trust)', 'path' => 'M12 2l8 3v6c0 5-3.4 9.4-8 11-4.6-1.6-8-6-8-11V5zm-1 13.6l6-6-1.4-1.4-4.6 4.6-2.1-2.1L7.5 12z' ),
		'check'  => array( 'label' => 'Tick', 'path' => 'M9 16.2l-3.5-3.5L4 14.2l5 5 11-11-1.4-1.4z' ),
		'phone'  => array( 'label' => 'Phone', 'path' => 'M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.6 3.6a1 1 0 0 1-.25 1z' ),
		'clock'  => array( 'label' => 'Clock', 'path' => 'M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm1 5h-2v6l5 3 1-1.7-4-2.3z' ),
		'leaf'   => array( 'label' => 'Leaf (eco)', 'path' => 'M17 8C8 10 5.9 16.2 3.8 21.3l1.9.7 1-2.3c.5.2 1 .3 1.3.3C19 20 22 3 22 3c-1 2-8 2.3-13 3.3S2 11.5 2 13.5 3.8 17.3 3.8 17.3C7 8 17 8 17 8z' ),
		'tools'  => array( 'label' => 'Tools (service)', 'path' => 'M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z' ),
	);
}
