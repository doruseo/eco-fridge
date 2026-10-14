<?php
/**
 * Single product page: details under the price, the Features tab and the
 * other description tabs.
 *
 *  - Under the price: the VAT-inclusive price (Google Merchant Center shows
 *    UK prices with VAT, so the page states it too), "Model:" (the product's
 *    Dimensions attribute, e.g. "ECO-P60/120 - 1200mm x 600mm x 1200mm (WxDxH)",
 *    as on the original site) and "Brand:" linked to the brand page.
 *  - Features (meta _ef_tab_features), under the gallery and the summary, above
 *    the tabs: the feature list in two columns, a relevant icon – emoji on a
 *    light green disc – instead of each bullet.
 *  - Tabs: Description, Technical Specs (meta _ef_tab_specs), Additional
 *    information, Brand (the brand's page content), Energy (meta _ef_tab_energy),
 *    Reviews – a tab with nothing to show is left out.
 *  - Admin: Features, Energy and Technical Specs boxes right after the product
 *    short description, and the Google product category.
 *  - The "Now available with Finance" image is not added by the theme: the shop
 *    manager puts it in the short description of the products that offer it.
 *
 * Styles: style.css → "PRODUCT DETAILS".
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The product's brand term (WooCommerce Brands), if any.
 *
 * @param int $product_id Product ID.
 * @return WP_Term|null
 */
function ef_product_brand( $product_id ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) { return null; }
	$terms = get_the_terms( $product_id, 'product_brand' );
	return $terms && ! is_wp_error( $terms ) ? $terms[0] : null;
}

/**
 * "Model:" text – the Dimensions attribute (model code and size).
 *
 * @param WC_Product $product Product.
 * @return string
 */
function ef_product_model( $product ) {
	return trim( (string) $product->get_attribute( 'dimensions' ) );
}

/* =========================================================================
 * 1. Under the price: price with VAT, Model, Brand.
 * ========================================================================= */
$ef_under_price = function () {
	global $product;
	if ( ! $product instanceof WC_Product ) { return; }
	$rows = array();
	if ( '' !== (string) $product->get_price() && wc_tax_enabled() ) {
		$rows[] = '<p class="ef-price-inc-vat">' . wp_kses_post( wc_price( wc_get_price_including_tax( $product ) ) ) . ' <span>inc. VAT</span></p>';
	}
	$model = ef_product_model( $product );
	$brand = ef_product_brand( $product->get_id() );
	$meta  = '';
	if ( '' !== $model ) {
		$meta .= '<li><span class="ef-product-specs__label">Model:</span> ' . esc_html( $model ) . '</li>';
	}
	if ( $brand ) {
		$link  = get_term_link( $brand );
		$meta .= '<li><span class="ef-product-specs__label">Brand:</span> ' . ( is_wp_error( $link ) ? esc_html( $brand->name ) : '<a href="' . esc_url( $link ) . '">' . esc_html( $brand->name ) . '</a>' ) . '</li>';
	}
	if ( '' !== $meta ) {
		$rows[] = '<ul class="ef-product-specs">' . $meta . '</ul>';
	}
	echo implode( '', $rows ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
};

// WooCommerce Brands also prints "Brand:" in the product meta – it is under the price already.
add_action( 'init', function () {
	if ( class_exists( 'WC_Brands' ) && isset( $GLOBALS['WC_Brands'] ) ) {
		remove_action( 'woocommerce_product_meta_end', array( $GLOBALS['WC_Brands'], 'show_brand' ) );
	}
}, 20 );

// Astra builds the product summary with its own hooks (WooCommerce's otherwise).
// Decided on "wp": the child theme loads before Astra defines its constant.
add_action( 'wp', function () use ( $ef_under_price ) {
	if ( defined( 'ASTRA_THEME_VERSION' ) ) {
		add_action( 'astra_woo_single_price_after', $ef_under_price );
	} else {
		add_action( 'woocommerce_single_product_summary', $ef_under_price, 11 );
	}
} );

/* =========================================================================
 * 2. Feature list: a relevant icon instead of each bullet.
 * ========================================================================= */

/**
 * Icon for one feature line (first matching keyword wins).
 *
 * @param string $text Feature text.
 * @return string Emoji.
 */
function ef_feature_icon( $text ) {
	$map = array(
		'🌡️' => '~temp|°c|thermostat~i',
		'❄️' => '~defrost|frost|freez|chill|cold~i',
		'💡' => '~\bled\b|light|illuminat~i',
		'💧' => '~drip|drain|evaporat|condensat(?!ing)|humid|water~i',
		'🔧' => '~slide out|condensing|clean|service|maintenance~i',
		'🗄️' => '~shel|tier|basket|drawer|gastronorm~i',
		'🪟' => '~glass|glaz|visib|panoramic|mirror~i',
		'🖥️' => '~digital|controller|control~i',
		'⚡' => '~energy|compressor|power|watt|\bkw\b|\bamps?\b|-amp\b|plug|suppl(y|ies)|volt~i',
		'🌀' => '~\bfan\b|air ?flow|ventilat~i',
		'♻️' => '~cfc|hfc|r290|r600|refrigerant|insulat|\beco~i',
		'🛞' => '~castor|wheel~i',
		'🦶' => '~feet|foot|\blegs?\b~i',
		'🔒' => '~lock|\bkey~i',
		'🚪' => '~door|\blid\b|sliding|hinged|shutter|blind~i',
		'✨' => '~steel|finish|colour|color|black|silver|white|stainless~i',
		'📏' => '~\d\s?mm\b|width|height|depth|size|capacity|litre~i',
	);
	foreach ( $map as $icon => $pattern ) {
		if ( preg_match( $pattern, $text ) ) {
			return $icon;
		}
	}
	return '✅';
}

/**
 * A feature list with an icon instead of each bullet.
 *
 * @param string $html HTML with one or more lists.
 * @return string
 */
function ef_feature_list( $html ) {
	return preg_replace_callback(
		'~<ul\b([^>]*)>(.*?)</ul>~is',
		function ( $ul ) {
			$items = preg_replace_callback(
				'~<li\b([^>]*)>(.*?)</li>~is',
				function ( $li ) {
					$icon = ef_feature_icon( wp_strip_all_tags( $li[2] ) );
					return '<li' . $li[1] . '><span class="ef-feature-icon" aria-hidden="true">' . $icon . '</span><span class="ef-feature-text">' . $li[2] . '</span></li>';
				},
				$ul[2]
			);
			$attrs = preg_match( '~class="~', $ul[1] ) ? preg_replace( '~class="~', 'class="ef-features ', $ul[1], 1 ) : $ul[1] . ' class="ef-features"';
			return '<ul' . $attrs . '>' . $items . '</ul>';
		},
		(string) $html
	);
}

/* =========================================================================
 * 3. Tabs.
 * ========================================================================= */

/**
 * Whether some HTML has anything to show (text, a table, an image, a video).
 *
 * @param string $html HTML.
 * @return bool
 */
function ef_has_content( $html ) {
	$html = (string) $html;
	return '' !== trim( wp_strip_all_tags( strip_shortcodes( $html ), true ) ) || (bool) preg_match( '~<(table|img|iframe|video)\b|\[efb_toggle~i', $html );
}

/**
 * Features: its own section above the tabs (under the gallery and the summary),
 * the list in two columns. Nothing when the Features field is empty.
 */
add_action( 'woocommerce_after_single_product_summary', function () {
	global $product;
	if ( ! $product instanceof WC_Product ) { return; }
	$features = (string) get_post_meta( $product->get_id(), '_ef_tab_features', true );
	if ( ! ef_has_content( $features ) ) { return; }
	echo '<section class="ef-features-section"><h2 class="ef-features-section__title">Features</h2>';
	echo '<div class="ef-features-tab">' . wp_kses_post( ef_feature_list( do_shortcode( $features ) ) ) . '</div></section>';
}, 5 ); // the tabs are at 10.

add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	global $product;
	if ( ! $product instanceof WC_Product ) { return $tabs; }
	$id = $product->get_id();

	if ( isset( $tabs['description'] ) && ! ef_has_content( get_post_field( 'post_content', $id ) ) ) {
		unset( $tabs['description'] );
	}
	$specs = (string) get_post_meta( $id, '_ef_tab_specs', true );
	if ( ef_has_content( $specs ) ) {
		$tabs['ef_specs'] = array(
			'title'    => 'Technical Specs',
			'priority' => 15,
			'callback' => function () use ( $specs ) {
				echo '<h2 class="ef-tab-heading">Technical specifications</h2>';
				echo apply_filters( 'the_content', $specs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- product content; the_content turns tables into spec tables.
			},
		);
	}
	$brand = ef_product_brand( $id );
	if ( $brand && ef_has_content( $brand->description ) ) {
		$tabs['ef_brand'] = array(
			'title'    => 'Brand',
			'priority' => 25,
			'callback' => function () use ( $brand ) {
				$logo = (int) get_term_meta( $brand->term_id, 'thumbnail_id', true );
				$link = get_term_link( $brand );
				echo '<div class="ef-brand-tab">';
				if ( $logo ) {
					echo '<div class="ef-brand-tab__logo">' . wp_get_attachment_image( $logo, 'medium', false, array( 'alt' => esc_attr( $brand->name ) ) ) . '</div>';
				}
				echo '<div class="ef-brand-tab__text"><h2 class="ef-tab-heading">' . esc_html( 'About ' . $brand->name ) . '</h2>' . wp_kses_post( wpautop( $brand->description ) );
				if ( ! is_wp_error( $link ) ) {
					echo '<p><a class="ef-brand-tab__more" href="' . esc_url( $link ) . '">' . esc_html( sprintf( 'All %s products', $brand->name ) ) . ' &rarr;</a></p>';
				}
				echo '</div></div>';
			},
		);
	}
	$energy = (string) get_post_meta( $id, '_ef_tab_energy', true );
	if ( ef_has_content( $energy ) ) {
		$tabs['ef_energy'] = array(
			'title'    => 'Energy',
			'priority' => 27, // before Reviews (30).
			'callback' => function () use ( $energy ) {
				echo '<h2 class="ef-tab-heading">Energy</h2>';
				echo wp_kses_post( wpautop( do_shortcode( $energy ) ) );
			},
		);
	}
	return $tabs;
}, 98 );

/* =========================================================================
 * 4. Admin: Features, Energy and Technical Specs boxes right after the short description.
 * ========================================================================= */

/**
 * The tab boxes: id => [ title, meta key, editor rows, help ].
 *
 * @return array
 */
function ef_product_tab_boxes() {
	return array(
		'ef-product-features' => array( 'Features', '_ef_tab_features', 10, 'A bullet list, shown above the tabs in two columns – each line gets an icon. Empty = no Features section.' ),
		'ef-product-energy'   => array( 'Energy (tab)', '_ef_tab_energy', 6, 'E.g. a link to the energy test report (PDF). Empty = no Energy tab.' ),
		'ef-product-specs'    => array( 'Technical Specs (tab)', '_ef_tab_specs', 12, 'Tables become green spec tables; [efb_toggle title="Warranty"]…[/efb_toggle] makes a panel that opens on click. Empty = no Technical Specs tab.' ),
	);
}

add_action( 'add_meta_boxes_product', function () {
	foreach ( ef_product_tab_boxes() as $box => $def ) {
		add_meta_box(
			$box,
			$def[0],
			function ( $post ) use ( $def ) {
				wp_nonce_field( 'ef_product_tabs', 'ef_product_tabs_nonce', false );
				echo '<p class="description">' . esc_html( $def[3] ) . '</p>';
				wp_editor( (string) get_post_meta( $post->ID, $def[1], true ), ltrim( $def[1], '_' ), array( 'textarea_name' => ltrim( $def[1], '_' ), 'textarea_rows' => $def[2] ) );
			},
			'product',
			'normal',
			'high'
		);
	}
	add_meta_box(
		'ef-product-google',
		'Google product category',
		function ( $post ) {
			wp_nonce_field( 'ef_product_tabs', 'ef_product_tabs_nonce', false );
			echo '<p><input type="text" class="widefat" name="ef_google_category" value="' . esc_attr( (string) get_post_meta( $post->ID, '_ef_google_category', true ) ) . '" placeholder="Business &amp; Industrial &gt; Retail &gt; Retail Display Cases"></p>';
			echo '<p class="description">For the Google Merchant Center feed; empty = chosen from the product category.</p>';
		},
		'product',
		'side',
		'low'
	);
} );

/**
 * The tab boxes sit right after "Product short description" – also for users
 * whose box order WooCommerce has already saved.
 */
add_filter( 'get_user_option_meta-box-order_product', function ( $order ) {
	if ( ! is_array( $order ) || empty( $order['normal'] ) ) { return $order; }
	$ours   = array_keys( ef_product_tab_boxes() );
	$normal = array_values( array_diff( array_filter( explode( ',', $order['normal'] ) ), $ours ) );
	$at     = array_search( 'postexcerpt', $normal, true );
	array_splice( $normal, false === $at ? count( $normal ) : $at + 1, 0, $ours );
	foreach ( array( 'side', 'advanced' ) as $context ) {
		if ( ! empty( $order[ $context ] ) ) {
			$order[ $context ] = implode( ',', array_diff( explode( ',', $order[ $context ] ), $ours ) );
		}
	}
	$order['normal'] = implode( ',', $normal );
	return $order;
} );

add_action( 'save_post_product', function ( $post_id ) {
	if ( ! isset( $_POST['ef_product_tabs_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ef_product_tabs_nonce'] ), 'ef_product_tabs' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	foreach ( ef_product_tab_boxes() as $def ) {
		$field = ltrim( $def[1], '_' );
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $def[1], wp_kses_post( wp_unslash( $_POST[ $field ] ) ) );
		}
	}
	if ( isset( $_POST['ef_google_category'] ) ) {
		update_post_meta( $post_id, '_ef_google_category', sanitize_text_field( wp_unslash( $_POST['ef_google_category'] ) ) );
	}
} );
