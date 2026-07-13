<?php
/**
 * Eco-Fridge Child — funcții temă copil.
 *
 * Găzduiește logica custom care NU se exprimă curat în Gutenberg (vezi AUDIT.md §6):
 *  - preț „from-price" / £POA pe produse (meta produs)
 *  - note livrare + paragraf T&C global pe pagina de produs (hook Woo)
 *  - înlocuiri de string WooCommerce moștenite din tema originală
 *
 * Secțiunile sunt schelet; se completează pe măsură ce reconstruim design-ul.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Logica paginii de produs single + add-on-uri (preț +VAT/POA, note, garanție, removal).
require_once get_stylesheet_directory() . '/inc/woo-single.php';

/**
 * Fonturi Google 1:1 cu originalul: Oswald (titluri) + Open Sans (body).
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
 * Încarcă stilul temei copil după al părintelui Astra.
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
 * Afișează numărul original al comenzii (din site-ul vechi) în loc de ID-ul nou.
 * Comenzile migrate păstrează ID-ul legacy în meta `_ef_legacy_order_id`.
 */
add_filter( 'woocommerce_order_number', function ( $number, $order ) {
	$legacy = is_a( $order, 'WC_Order' ) ? $order->get_meta( '_ef_legacy_order_id' ) : '';
	return $legacy ? $legacy : $number;
}, 10, 2 );

/**
 * Bară de căutare AJAX (FiboSearch) — strip dedicat produse+categorii sub header,
 * ca în originalul. Live results, interactiv.
 */
add_action( 'astra_header_after', function () {
	if ( ! shortcode_exists( 'fibosearch' ) ) { return; }
	echo '<div class="ef-search-strip"><div class="ef-search-inner">' . do_shortcode( '[fibosearch]' ) . '</div></div>';
}, 5 );

/**
 * Date de contact ECO-Fridge (1:1 cu originalul) — folosite în footer + banner.
 */
function ef_contact() {
	return array(
		'company' => 'ECO-Fridge Ltd',
		'address' => '18 Top Angel, Buckingham Industrial Park, Buckingham, Buckinghamshire MK18 1TH',
		'phone'   => '01280 811411',
		'email'   => 'sales@eco-fridge.co.uk',
	);
}

/**
 * Banner „Call us today" pe paginile interne (nu pe homepage), ca în originalul.
 */
add_action( 'astra_content_before', function () {
	if ( is_front_page() ) { return; }
	$c = ef_contact();
	echo '<div class="ef-call-banner"><div class="ef-call-inner">'
		. '<span class="ef-call-text">Call us today <a href="tel:' . esc_attr( preg_replace( '/\s+/', '', $c['phone'] ) ) . '">' . esc_html( $c['phone'] ) . '</a></span>'
		. '<span class="ef-call-sub">Trade prices &bull; Free delivery &bull; Manufacturer direct</span>'
		. '</div></div>';
}, 5 );

/**
 * Footer branded 3 coloane (adresă | contact | linkuri) + copyright, 1:1 cu originalul.
 * Injectat în footer-ul Astra; footer-ul builder implicit e ascuns prin CSS.
 */
add_action( 'astra_footer_before', function () {
	$c = ef_contact();
	$tel = preg_replace( '/\s+/', '', $c['phone'] );
	?>
	<div class="ef-footer">
		<div class="ef-footer-grid">
			<div class="ef-footer-col">
				<h3 class="ef-footer-title"><?php echo esc_html( $c['company'] ); ?></h3>
				<p><?php echo esc_html( $c['address'] ); ?></p>
			</div>
			<div class="ef-footer-col">
				<h3 class="ef-footer-title">Contact</h3>
				<p>Tel: <a href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( $c['phone'] ); ?></a><br>
				Email: <a href="mailto:<?php echo esc_attr( $c['email'] ); ?>"><?php echo esc_html( $c['email'] ); ?></a></p>
			</div>
			<div class="ef-footer-col">
				<h3 class="ef-footer-title">Information</h3>
				<ul class="ef-footer-links">
					<li><a href="/privacy-cookies-policy/">Privacy &amp; Cookies Policy</a></li>
					<li><a href="/returns/">Returns Policy</a></li>
					<li><a href="/terms-conditions/">Terms &amp; Conditions</a></li>
					<li><a href="/contact-us/">Contact Us</a></li>
				</ul>
			</div>
		</div>
		<div class="ef-footer-bottom">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $c['company'] ); ?>. All rights reserved.</div>
	</div>
	<?php
}, 5 );

/**
 * Featured image pe articolele de blog (single post), afișată DUPĂ titlu +
 * detaliile meta, înainte de conținut. Astra modern nu o randează implicit
 * în structura `ast-dynamic-single-post-structure` (doar title+meta), iar
 * varianta nativă „single-image" o pune ca fundal — de aceea o randăm noi
 * aici, la `astra_single_post_banner_after` (fix după title+meta, în header).
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
 * Preț „From £X +VAT" — normalizare din meta _ef_from_price (migrat din Types),
 * cu fallback pe prețul Woo. Reutilizat în grile + single product.
 */
function ef_from_price_html( $product_id ) {
	$raw = get_post_meta( $product_id, '_ef_from_price', true );
	$raw = trim( (string) $raw );
	if ( '' !== $raw ) {
		// valorile migrate conțin deja £ și/sau +VAT în forme variate — le păstrăm 1:1
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
 * Afișează „From £X +VAT" sub titlul fiecărui produs din grilele Spectra Post Grid.
 * Post-procesăm HTML-ul blocului (robust, independent de interne Spectra): după
 * fiecare titlu de produs injectăm prețul, identificând produsul din permalink.
 */
add_filter( 'render_block', function ( $content, $block ) {
	if ( empty( $block['blockName'] ) || 'uagb/post-grid' !== $block['blockName'] ) {
		return $content;
	}
	if ( strpos( $content, 'uagb-post__title' ) === false ) {
		return $content;
	}
	return preg_replace_callback(
		'/(<(h[1-6])[^>]*class="[^"]*uagb-post__title[^"]*"[^>]*>.*?href="([^"]+)".*?<\/\2>)/is',
		function ( $m ) {
			$pid = url_to_postid( $m[3] );
			if ( $pid && 'product' === get_post_type( $pid ) ) {
				return $m[1] . ef_from_price_html( $pid );
			}
			return $m[1];
		},
		$content
	);
}, 10, 2 );

/* -------------------------------------------------------------------------
 * TODO Faza 2 — de completat după importul datelor:
 *  [ ] from-price / £POA:  meta produs `_ef_from_price` + afișare în arhive/single
 *  [ ] T&C + note livrare:  woocommerce_after_single_product_summary (pattern global)
 *  [ ] înlocuiri string Woo:  gettext / woocommerce_* (ex. „CLICK HERE TO ADD…")
 *  [ ] „Back to {range} index":  breadcrumb contextual pe single-product
 * ------------------------------------------------------------------------- */
