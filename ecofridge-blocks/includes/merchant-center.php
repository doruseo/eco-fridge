<?php
/**
 * Google Merchant Center: product structured data and a product feed.
 *
 * Structured data (Rank Math's Product schema, completed here):
 *  - name = the product name (not the SEO title), brand from WooCommerce
 *    Brands, mpn = SKU (the manufacturer's model code);
 *  - price including VAT (Merchant Center shows UK prices with VAT; the page
 *    states it under the price), condition (used for ex-display / used stock),
 *    free UK mainland delivery with delivery times, and the returns policy.
 *
 * Feed: /feed/google-merchant/ – RSS 2.0 with the g: namespace, every visible
 * product with a price. Item IDs keep the original site's feed IDs
 * (woocommerce_gpf_<original ID>), so Merchant Center keeps each product's
 * history; products created here use woocommerce_gpf_ef<ID>.
 *
 * @package EcofridgeBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Merchant Center condition of a product: new, used or refurbished.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function efb_mc_condition( $product ) {
	if ( has_term( 'graded-units', 'product_cat', $product->get_id() ) || preg_match( '~ex[- ]?(exhibition|showroom|display)|\bused\b|second[- ]hand~i', $product->get_name() ) ) {
		return 'used';
	}
	return 'new';
}

/**
 * Price including VAT.
 *
 * @param WC_Product $product Product.
 * @param string     $which   'price' (current), 'regular' or 'sale'.
 * @return float
 */
function efb_mc_price( $product, $which = 'price' ) {
	$amount = 'regular' === $which ? $product->get_regular_price() : ( 'sale' === $which ? $product->get_sale_price() : $product->get_price() );
	if ( '' === (string) $amount ) {
		return 0.0;
	}
	return (float) wc_get_price_including_tax( $product, array( 'price' => (float) $amount ) );
}

/**
 * Product brand name.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function efb_mc_brand( $product ) {
	$terms = taxonomy_exists( 'product_brand' ) ? get_the_terms( $product->get_id(), 'product_brand' ) : false;
	return $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
}

/**
 * The store's delivery and returns, as schema.org objects.
 *
 * @return array{0:array,1:array} [ OfferShippingDetails, MerchantReturnPolicy ]
 */
function efb_mc_shipping_and_returns() {
	$returns_page = get_page_by_path( 'returns' );
	$shipping     = array(
		'@type'               => 'OfferShippingDetails',
		'shippingRate'        => array(
			'@type'    => 'MonetaryAmount',
			'value'    => 0,
			'currency' => get_woocommerce_currency(),
		),
		'shippingDestination' => array(
			'@type'          => 'DefinedRegion',
			'addressCountry' => 'GB',
		),
		// "5 to 8 working days from cleared payment" (bank transfer), up to 10 by card.
		'deliveryTime'        => array(
			'@type'        => 'ShippingDeliveryTime',
			'handlingTime' => array( '@type' => 'QuantitativeValue', 'minValue' => 3, 'maxValue' => 7, 'unitCode' => 'DAY' ),
			'transitTime'  => array( '@type' => 'QuantitativeValue', 'minValue' => 2, 'maxValue' => 3, 'unitCode' => 'DAY' ),
		),
	);
	// Returns only before delivery (restocking fee); none once the delivery note is signed.
	$returns = array(
		'@type'                => 'MerchantReturnPolicy',
		'applicableCountry'    => 'GB',
		'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
	);
	if ( $returns_page ) {
		$returns['merchantReturnLink'] = get_permalink( $returns_page );
	}
	return array( $shipping, $returns );
}

/*
 * Structured data: Rank Math's Product entity.
 */
add_filter(
	'rank_math/snippet/rich_snippet_product_entity',
	static function ( $entity ) {
		$product = is_singular( 'product' ) ? wc_get_product( get_queried_object_id() ) : null;
		if ( ! $product ) {
			return $entity;
		}
		$entity['name'] = wp_strip_all_tags( $product->get_name() );
		$brand          = efb_mc_brand( $product );
		if ( '' !== $brand ) {
			$entity['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand,
			);
		}
		if ( $product->get_sku() ) {
			$entity['sku'] = $product->get_sku();
			$entity['mpn'] = $product->get_sku();
		}
		if ( ! empty( $entity['offers'] ) && is_array( $entity['offers'] ) && '' !== (string) $product->get_price() ) {
			$offer          = $entity['offers'];
			$price          = wc_format_decimal( efb_mc_price( $product ), 2 );
			$offer['price'] = $price;
			$offer['priceSpecification'] = array(
				'@type'                 => 'UnitPriceSpecification',
				'price'                 => $price,
				'priceCurrency'         => get_woocommerce_currency(),
				'valueAddedTaxIncluded' => 'true', // a string: Rank Math stringifies values (true would become "1").
			);
			$offer['itemCondition'] = 'used' === efb_mc_condition( $product ) ? 'https://schema.org/UsedCondition' : 'https://schema.org/NewCondition';
			list( $offer['shippingDetails'], $offer['hasMerchantReturnPolicy'] ) = efb_mc_shipping_and_returns();
			$entity['offers'] = $offer;
		}
		return $entity;
	},
	20
);

/**
 * Google product category of a product (its own, else from its category).
 *
 * @param WC_Product $product Product.
 * @return string
 */
function efb_mc_google_category( $product ) {
	// Ovens, grills and stands are not fridges, whatever the old feed said.
	if ( preg_match( '~\b(oven|grill|stand)\b~i', $product->get_name() ) ) {
		return 'Business & Industrial > Food Service';
	}
	$own = trim( (string) get_post_meta( $product->get_id(), '_ef_google_category', true ) );
	if ( '' !== $own ) {
		return $own;
	}
	$names = strtolower( implode( ' ', wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ) ) . ' ' . $product->get_name() );
	if ( false !== strpos( $names, 'display' ) && false === strpos( $names, 'freezer' ) ) {
		return 'Business & Industrial > Retail > Retail Display Cases';
	}
	if ( false !== strpos( $names, 'freezer' ) ) {
		return 'Home & Garden > Kitchen & Dining > Kitchen Appliances > Freezers';
	}
	return 'Home & Garden > Kitchen & Dining > Kitchen Appliances > Refrigerators';
}

/**
 * Category path for product_type, e.g. "Display Cabinets > Standard Patisserie Display Fridges".
 *
 * @param WC_Product $product Product.
 * @return string
 */
function efb_mc_product_type( $product ) {
	$primary = (int) get_post_meta( $product->get_id(), 'rank_math_primary_product_cat', true );
	$term    = $primary ? get_term( $primary, 'product_cat' ) : null;
	if ( ! $term || is_wp_error( $term ) ) {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );
		$term  = $terms && ! is_wp_error( $terms ) ? $terms[0] : null;
	}
	if ( ! $term ) {
		return '';
	}
	$path = array_reverse( array_map( static fn( $id ) => get_term( $id, 'product_cat' )->name, get_ancestors( $term->term_id, 'product_cat' ) ) );
	$path[] = $term->name;
	return implode( ' > ', $path );
}

/*
 * Feed: /feed/google-merchant/.
 */
add_action(
	'init',
	static function () {
		add_feed( 'google-merchant', 'efb_mc_feed' );
	}
);

/**
 * Plain text for the feed (no tags, no shortcodes, single spaces).
 *
 * @param string $html HTML.
 * @return string
 */
function efb_mc_text( $html ) {
	$text = wp_strip_all_tags( strip_shortcodes( (string) $html ), true );
	return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );
}

/**
 * Prints the feed.
 */
function efb_mc_feed() {
	$ids = wc_get_products(
		array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'limit'      => -1,
			'return'     => 'ids',
		)
	);
	header( 'Content-Type: application/xml; charset=UTF-8' );
	$x = static fn( $s ) => esc_xml( (string) $s );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>' . "\n";
	echo '<title>' . $x( get_bloginfo( 'name' ) ) . '</title><link>' . $x( home_url( '/' ) ) . '</link><description>' . $x( get_bloginfo( 'description' ) ) . '</description>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$currency = get_woocommerce_currency();
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || '' === (string) $product->get_price() || ! $product->is_visible() ) {
			continue;
		}
		$legacy = (int) get_post_meta( $id, '_ef_legacy_id', true );
		$image  = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
		if ( ! $image ) {
			continue; // Merchant Center rejects items without an image.
		}
		$description = efb_mc_text( $product->get_description() . ' ' . $product->get_short_description() );
		$fields      = array(
			'g:id'                      => 'woocommerce_gpf_' . ( $legacy ? $legacy : 'ef' . $id ),
			'g:title'                   => mb_substr( efb_mc_text( $product->get_name() ), 0, 150 ),
			'g:description'             => mb_substr( '' !== $description ? $description : efb_mc_text( $product->get_name() ), 0, 5000 ),
			'g:link'                    => get_permalink( $id ),
			'g:image_link'              => $image,
			'g:availability'            => $product->is_in_stock() ? ( 'onbackorder' === $product->get_stock_status() ? 'backorder' : 'in_stock' ) : 'out_of_stock',
			'g:price'                   => wc_format_decimal( efb_mc_price( $product, $product->is_on_sale() ? 'regular' : 'price' ), 2 ) . ' ' . $currency,
			'g:brand'                   => efb_mc_brand( $product ),
			'g:mpn'                     => $product->get_sku(),
			'g:condition'               => efb_mc_condition( $product ),
			'g:google_product_category' => efb_mc_google_category( $product ),
			'g:product_type'            => efb_mc_product_type( $product ),
		);
		if ( $product->is_on_sale() && '' !== (string) $product->get_sale_price() ) {
			$fields['g:sale_price'] = wc_format_decimal( efb_mc_price( $product, 'sale' ), 2 ) . ' ' . $currency;
		}
		echo '<item>';
		foreach ( $fields as $tag => $value ) {
			if ( '' !== (string) $value ) {
				echo '<' . $tag . '>' . $x( $value ) . '</' . $tag . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		foreach ( array_slice( $product->get_gallery_image_ids(), 0, 10 ) as $gallery ) {
			$url = wp_get_attachment_image_url( $gallery, 'full' );
			if ( $url ) {
				echo '<g:additional_image_link>' . $x( $url ) . '</g:additional_image_link>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		echo '<g:shipping><g:country>GB</g:country><g:price>0.00 ' . $x( $currency ) . '</g:price></g:shipping>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "</item>\n";
	}
	echo '</channel></rss>';
}
