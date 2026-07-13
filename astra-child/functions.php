<?php
/**
 * Eco-Fridge Child — funcții temă copil.
 *
 * Găzduiește DOAR logica ce nu se exprimă curat în Astra Pro / Gutenberg:
 *  - preț „from-price" / £POA pe produse + în grilele Spectra (meta produs)
 *  - note livrare + T&C + add-on-uri pe pagina de produs (hook Woo, în inc/woo-single.php)
 *  - număr comandă legacy, imaginea featured pe blog, shortcode [ef_subcats] pt. homepage
 *  - rotația hero-slider-ului de pe homepage
 *
 * Header, mega-meniu, footer și banner-ul „Call us today" sunt acum NATIVE Astra
 * (Header/Footer Builder + Nav Menu mega + Custom Layout), configurate din
 * migration/16-header-footer-native.php și editabile din dashboard — nu mai există
 * cod de header/footer aici.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Logica paginii de produs single + add-on-uri (preț +VAT/POA, note, garanție, removal).
require_once get_stylesheet_directory() . '/inc/woo-single.php';

// Header custom pe 2 rânduri (logo/utilitar/search + categorii mega/iconițe/coș),
// condus 100% din meniul WP autoritar (Primary Menu #342). Footer/banner rămân native.
require_once get_stylesheet_directory() . '/inc/header.php';

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

/** Sortează o listă de termeni după meta `order` (1:1 cu originalul), apoi nume. */
function ef_sort_terms_by_order( $terms ) {
	usort( $terms, function ( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, 'order', true );
		$ob = (int) get_term_meta( $b->term_id, 'order', true );
		if ( $oa === $ob ) { return strcasecmp( $a->name, $b->name ); }
		if ( 0 === $oa ) { return 1; }   // fără ordine → la coadă
		if ( 0 === $ob ) { return -1; }
		return $oa - $ob;
	} );
	return $terms;
}

/**
 * Hero slider homepage — logica de rotație (crossfade, autoplay, dots, arrows,
 * pauză la hover). Markup + CSS sunt în homepage (wp:html) + tema copil;
 * scriptul e injectat inline doar pe front page ca să nu fie filtrat de kses.
 */
add_action( 'wp_footer', function () {
	if ( ! is_front_page() ) {
		return;
	}
	?>
<script>
(function(){
	var hero = document.querySelector('.ef-hero');
	if(!hero){ return; }
	var slides = hero.querySelectorAll('.ef-hero-slide');
	var dots   = hero.querySelectorAll('.ef-hero-dot');
	var n = slides.length, i = 0, timer = null;
	var delay = parseInt(hero.getAttribute('data-autoplay'), 10) || 6000;
	if(n < 2){ return; }
	function go(k){
		slides[i].classList.remove('is-active');
		if(dots[i]){ dots[i].classList.remove('is-active'); }
		i = (k + n) % n;
		slides[i].classList.add('is-active');
		if(dots[i]){ dots[i].classList.add('is-active'); }
	}
	function next(){ go(i + 1); }
	function prev(){ go(i - 1); }
	function start(){ stop(); timer = setInterval(next, delay); }
	function stop(){ if(timer){ clearInterval(timer); timer = null; } }
	var nx = hero.querySelector('.ef-hero-next'), pv = hero.querySelector('.ef-hero-prev');
	if(nx){ nx.addEventListener('click', function(){ next(); start(); }); }
	if(pv){ pv.addEventListener('click', function(){ prev(); start(); }); }
	Array.prototype.forEach.call(dots, function(d, k){ d.addEventListener('click', function(){ go(k); start(); }); });
	hero.addEventListener('mouseenter', stop);
	hero.addEventListener('mouseleave', start);
	start();
})();
</script>
	<?php
}, 20 );

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
 * Imaginea unei categorii de produs: thumbnail-ul categoriei, cu fallback pe
 * imaginea principală a primului produs din categorie. Folosit de [ef_subcats].
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
 * Shortcode [ef_subcats parent="44" cols="5"] — grilă de tile-uri cu sub-categoriile
 * unei categorii (iconiță + nume + link), ca pe homepage-ul original (Display /
 * Storage Cabinets). Sare peste sub-categoriile fără produse.
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
	$terms = ef_sort_terms_by_order( $terms ); // ordine 1:1 cu originalul

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
