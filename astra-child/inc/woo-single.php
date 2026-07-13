<?php
/**
 * Logica paginii de produs single ECO-Fridge (temă copil Astra).
 * Replică elementele din tema originală (single-product.php) + add-on-urile YITH:
 *   - preț „+VAT" / £POA (from-price) pentru produse fără preț
 *   - link „Back to {category}"
 *   - note livrare/trade + T&C sub sumar (hook global)
 *   - add-on-uri: garanție 12 luni extra (+15%) sub toggle + Removal & Disposal (+£150)
 *     cu integrare în coș, totaluri, checkout și meta comandă.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const EF_REMOVAL_FEE   = 150;   // £ Removal & Disposal
const EF_WARRANTY_RATE = 0.15;  // +15% garanție extra

/* =========================================================================
 * 1. PREȚ: „+VAT" pe afișare + £POA / from-price pentru produse fără preț
 * ========================================================================= */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( is_admin() ) { return $html; }
	$id = $product->get_id();
	$has_price = '' !== $product->get_price() && null !== $product->get_price();
	if ( ! $has_price ) {
		// fără preț -> afișează from-price (poate fi „£POA") din meta migrat
		$from = trim( (string) get_post_meta( $id, '_ef_from_price', true ) );
		if ( '' !== $from ) {
			$label = ( stripos( $from, 'from' ) === 0 ) ? $from : 'From ' . $from;
			return '<span class="ef-price ef-price--poa">' . wp_kses_post( $label ) . '</span>';
		}
		return '<span class="ef-price ef-price--poa">POA</span>';
	}
	return '<span class="ef-price">' . $html . ' <span class="ef-vat">+VAT</span></span>';
}, 20, 2 );

/* =========================================================================
 * 2. „Back to {category}" deasupra produsului
 * ========================================================================= */
add_action( 'woocommerce_before_single_product', function () {
	global $product;
	$product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product( get_the_ID() );
	if ( ! $product ) { return; }
	$terms = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );
	if ( empty( $terms ) ) { return; }
	$t = $terms[0];
	echo '<div class="ef-back-link"><a href="' . esc_url( get_term_link( $t ) ) . '">&larr; Back to ' . esc_html( $t->name ) . '</a></div>';
}, 5 );

/* =========================================================================
 * 3. Note livrare / trade + T&C sub sumarul produsului
 * ========================================================================= */
add_action( 'woocommerce_after_single_product_summary', function () {
	?>
	<div class="ef-product-notes">
		<ul>
			<li><strong>Trade prices</strong> — shown excluding VAT.</li>
			<li><strong>Free delivery</strong> to UK mainland.</li>
			<li><strong>Manufacturer direct</strong> — full warranty included.</li>
		</ul>
		<p class="ef-tc-note">Prices and specifications are subject to change. Delivery is to ground-floor, kerbside UK mainland unless otherwise agreed. Full terms apply — see our <a href="/terms-conditions/">Terms &amp; Conditions</a>.</p>
	</div>
	<?php
}, 25 );

/* =========================================================================
 * 3b. Breadcrumbs WooCommerce: 🏢 în loc de „Home", separator „>>"
 *     (linkurile verzi se stilizează în style.css)
 * ========================================================================= */
add_filter( 'woocommerce_breadcrumb_defaults', function ( $args ) {
	$args['home']      = '🏢';
	$args['delimiter'] = ' <span class="ef-crumb-sep">&gt;&gt;</span> ';
	return $args;
} );

/* =========================================================================
 * 4. ADD-ON-URI produs: garanție extra (+15%) + Removal & Disposal (+£150)
 * ========================================================================= */

// 4a. câmpuri pe pagina produsului (garanția sub un toggle, ca în original)
add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) || ! $product->is_purchasable() ) { return; }
	?>
	<div class="ef-addons">
		<label class="ef-addon ef-addon--removal">
			<input type="checkbox" name="ef_removal" value="1">
			<span>Add <strong>Removal &amp; Disposal</strong> of your old unit <em>(+&pound;<?php echo esc_html( EF_REMOVAL_FEE ); ?>)</em></span>
		</label>
		<details class="ef-addon-toggle">
			<summary>Extended warranty options</summary>
			<label class="ef-addon ef-addon--warranty">
				<input type="checkbox" name="ef_warranty" value="1">
				<span>Add <strong>12 months extra warranty</strong> <em>(+15%)</em></span>
			</label>
		</details>
	</div>
	<?php
}, 15 );

// 4b. captează opțiunile în datele item-ului din coș
add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	if ( ! empty( $_POST['ef_removal'] ) ) { $data['ef_removal'] = 1; }
	if ( ! empty( $_POST['ef_warranty'] ) ) { $data['ef_warranty'] = 1; }
	return $data;
}, 10, 2 );

// 4c. add-on-urile ca TAXE (fee-uri) SEPARATE în coș — linii distincte, cu TVA aplicat.
//     Prețul produsului rămâne neatins (fără TVA); Removal & Disposal și Warranty apar
//     ca rânduri proprii, iar TVA se calculează peste tot (produs + fee-uri).
add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; }
	$removal_total  = 0.0;
	$warranty_total = 0.0;
	foreach ( $cart->get_cart() as $item ) {
		$qty = max( 1, (int) $item['quantity'] );
		if ( ! empty( $item['ef_removal'] ) ) {
			$removal_total += EF_REMOVAL_FEE * $qty;
		}
		if ( ! empty( $item['ef_warranty'] ) ) {
			$base            = (float) $item['data']->get_regular_price();
			$warranty_total += $base * EF_WARRANTY_RATE * $qty;
		}
	}
	// add_fee( nume, sumă, taxabil=true, clasă_taxă='' → Standard 20% )
	if ( $removal_total > 0 ) {
		$cart->add_fee( __( 'Removal & Disposal', 'eco-fridge-child' ), $removal_total, true, '' );
	}
	if ( $warranty_total > 0 ) {
		$cart->add_fee( __( 'Extended warranty (12 months)', 'eco-fridge-child' ), $warranty_total, true, '' );
	}
}, 20 );

// 4d. marchează pe linia de produs ce add-on-uri au fost alese (fără preț — suma e pe linia de fee)
add_filter( 'woocommerce_get_item_data', function ( $items, $cart_item ) {
	if ( ! empty( $cart_item['ef_removal'] ) ) {
		$items[] = array( 'key' => 'Removal &amp; Disposal', 'value' => 'Added' );
	}
	if ( ! empty( $cart_item['ef_warranty'] ) ) {
		$items[] = array( 'key' => 'Extended warranty', 'value' => '12 months' );
	}
	return $items;
}, 10, 2 );

// 4e. salvează selecția ca meta pe linia de comandă (suma rămâne pe fee-urile comenzii)
add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	if ( ! empty( $values['ef_removal'] ) ) { $item->add_meta_data( 'Removal & Disposal', 'Added', true ); }
	if ( ! empty( $values['ef_warranty'] ) ) { $item->add_meta_data( 'Extended warranty', '12 months', true ); }
}, 10, 3 );
