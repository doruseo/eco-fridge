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

/* -------------------------------------------------------------------------
 * HEADER STICKY PE 2 RÂNDURI (custom, înlocuiește header-ul Astra)
 *   Rând 1: logo (stânga) | nav principal (Home/Blog/About/Contact) + search (dreapta)
 *   Rând 2: nav categorii de produse (stânga) | coș (dreapta)
 * Astra-ul nativ e ascuns prin CSS; nav-urile vin din meniuri (editabile).
 * ------------------------------------------------------------------------- */

// Locații de meniu pentru header-ul custom (editabile din Appearance → Menus).
add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'ef_header_primary'    => 'Header — rând 1 (Home/Blog/About/Contact)',
		'ef_header_categories' => 'Header — rând 2 (categorii de produse)',
	) );
} );

// Link coș (server-side; se actualizează prin fragmente AJAX la add-to-cart).
function ef_cart_link() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return ''; }
	$count = WC()->cart->get_cart_contents_count();
	$total = WC()->cart->get_cart_total();
	return '<a class="ef-cart" href="' . esc_url( wc_get_cart_url() ) . '">'
		. '<span class="ef-cart-ico" aria-hidden="true"></span>'
		. '<span class="ef-cart-txt"><span class="ef-cart-count">' . (int) $count . ' items</span> <span class="ef-cart-total">' . wp_kses_post( $total ) . '</span></span>'
		. '</a>';
}
// Actualizare coș fără reload (fragmente WooCommerce).
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['a.ef-cart'] = ef_cart_link();
	return $fragments;
} );

// Randare header custom, înainte de header-ul Astra (ascuns prin CSS).
add_action( 'astra_header_before', function () {
	$home = esc_url( home_url( '/' ) );
	echo '<div class="ef-header" id="ef-header">';

	// —— rând 1: logo | nav principal + search ——
	echo '<div class="ef-header-row ef-header-row1"><div class="ef-header-inner">';
	echo '<a class="ef-logo" href="' . $home . '" rel="home">';
	if ( has_custom_logo() ) {
		$lid = get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( $lid, 'full', false, array( 'class' => 'ef-logo-img', 'alt' => get_bloginfo( 'name' ) ) );
	} else {
		echo esc_html( get_bloginfo( 'name' ) );
	}
	echo '</a>';
	echo '<button class="ef-menu-toggle" aria-label="Toggle menu" aria-expanded="false"><span></span><span></span><span></span></button>';
	echo '<div class="ef-row1-right">';
	if ( has_nav_menu( 'ef_header_primary' ) ) {
		wp_nav_menu( array( 'theme_location' => 'ef_header_primary', 'container' => 'nav', 'container_class' => 'ef-primary-nav', 'menu_class' => 'ef-menu', 'fallback_cb' => false, 'depth' => 2 ) );
	}
	if ( shortcode_exists( 'fibosearch' ) ) {
		echo '<div class="ef-header-search">' . do_shortcode( '[fibosearch]' ) . '</div>';
	}
	echo '</div></div></div>';

	// —— rând 2: nav categorii | coș ——
	echo '<div class="ef-header-row ef-header-row2"><div class="ef-header-inner">';
	if ( has_nav_menu( 'ef_header_categories' ) ) {
		wp_nav_menu( array( 'theme_location' => 'ef_header_categories', 'container' => 'nav', 'container_class' => 'ef-cat-nav', 'menu_class' => 'ef-menu', 'fallback_cb' => false, 'depth' => 2 ) );
	}
	echo '<div class="ef-header-cart">' . ef_cart_link() . '</div>';
	echo '</div></div>';

	echo '</div>'; // .ef-header
}, 5 );

// Toggle meniu mobil pentru header-ul custom.
add_action( 'wp_footer', function () {
	?>
<script>
(function(){
	var t = document.querySelector('.ef-menu-toggle'), h = document.getElementById('ef-header');
	if(!t || !h){ return; }
	t.addEventListener('click', function(){
		var open = h.classList.toggle('ef-nav-open');
		t.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
})();
</script>
	<?php
}, 20 );

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
 * imaginea principală a primului produs din categorie.
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
		'orderby'    => 'name',
	) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) { return ''; }

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

/* -------------------------------------------------------------------------
 * TODO Faza 2 — de completat după importul datelor:
 *  [ ] from-price / £POA:  meta produs `_ef_from_price` + afișare în arhive/single
 *  [ ] T&C + note livrare:  woocommerce_after_single_product_summary (pattern global)
 *  [ ] înlocuiri string Woo:  gettext / woocommerce_* (ex. „CLICK HERE TO ADD…")
 *  [ ] „Back to {range} index":  breadcrumb contextual pe single-product
 * ------------------------------------------------------------------------- */
