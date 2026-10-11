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
