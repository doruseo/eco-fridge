<?php
/**
 * ECO-Fridge single product logic (Astra child theme).
 * Replicates elements from the original theme (single-product.php) + the YITH add-ons:
 *   - "+VAT" / £POA (from-price) pricing for products without a price
 *   - "Back to {category}" link
 *   - delivery/trade notes + T&C below the summary (global hook)
 *   - add-ons: 12 months extra warranty (+15%) under a toggle + Removal & Disposal (+£150)
 *     integrated into the cart, totals, checkout and order meta.
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const EF_REMOVAL_FEE   = 150;   // £ Removal & Disposal
const EF_WARRANTY_RATE = 0.15;  // +15% extra warranty

/* =========================================================================
 * 1. PRICE: "+VAT" on display + £POA / from-price for products without a price
 * ========================================================================= */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( is_admin() ) { return $html; }
	$id = $product->get_id();
	$has_price = '' !== $product->get_price() && null !== $product->get_price();
	if ( ! $has_price ) {
		// no price -> show the from-price (may be "£POA") from migrated meta
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
 * 2. "Back to {category}" above the product
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
 * 3. Delivery / trade notes + T&C — MOVED to the bottom of the page, after
 *    "Related products" and "Recently Viewed Products".
 * ========================================================================= */
/**
 * "Recently Viewed Products" — grid (like Related Products) from the
 * `woocommerce_recently_viewed` cookie (maintained by wc_track_product_view).
 * Excludes the current product. Empty on first visit → renders nothing.
 */
function ef_recently_viewed_html() {
	$raw    = ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ? (array) explode( '|', wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) ) : array(); // phpcs:ignore
	$viewed = array_reverse( array_filter( array_map( 'absint', $raw ) ) );
	global $product;
	$current = is_a( $product, 'WC_Product' ) ? $product->get_id() : (int) get_the_ID();
	$viewed  = array_values( array_diff( array_unique( $viewed ), array( $current ) ) );
	if ( empty( $viewed ) ) { return ''; }
	$viewed = array_slice( $viewed, 0, 4 );

	$q = new WP_Query( array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'post__in'            => $viewed,
		'orderby'             => 'post__in',
		'posts_per_page'      => 4,
		'ignore_sticky_posts' => true,
	) );
	if ( ! $q->have_posts() ) { wp_reset_postdata(); return ''; }

	ob_start();
	echo '<section class="ef-recently-viewed"><h2>Recently Viewed Products</h2>';
	$orig_cols = function_exists( 'wc_get_loop_prop' ) ? wc_get_loop_prop( 'columns' ) : 4;
	if ( function_exists( 'wc_set_loop_prop' ) ) { wc_set_loop_prop( 'columns', 4 ); }
	woocommerce_product_loop_start();
	while ( $q->have_posts() ) {
		$q->the_post();
		wc_get_template_part( 'content', 'product' );
	}
	woocommerce_product_loop_end();
	if ( function_exists( 'wc_set_loop_prop' ) ) { wc_set_loop_prop( 'columns', $orig_cols ); }
	echo '</section>';
	wp_reset_postdata();
	return ob_get_clean();
}

/**
 * Trade notes + delivery/payment policies + Terms & Conditions box.
 * Reused at the bottom of the single product page AND category archives.
 */
function ef_trade_terms_box() {
	?>
	<div class="ef-product-notes">
		<ul>
			<li><strong>Trade prices</strong> — shown excluding VAT.</li>
			<li><strong>Free delivery</strong> to UK mainland.</li>
			<li><strong>Manufacturer direct</strong> — B2B prices.</li>
		</ul>
		<p class="ef-tc-note">Prices and specifications are subject to change.</p>
		<ul class="ef-delivery-lines">
			<li>Trade prices. Free delivery into your store if possible (T&amp;C apply).</li>
			<li>Bank transfer estimated 5 to 8 working days from cleared payment (T&amp;C apply).</li>
			<li>Card payments estimated 8 to 10 working days from cleared payment (T&amp;C apply).</li>
			<li>Delivery times above York, Leeds &amp; Blackpool can take approximately 2 to 4 Weeks (T&amp;C apply).</li>
		</ul>
		<p class="ef-tc-note">Full terms apply — see our <a href="/terms-conditions/">Terms &amp; Conditions</a>.</p>

		<div class="ef-trade-notes">
			<p>All cabinets above come with free delivery into your store if possible (t&amp;c apply). Deliveries above York, Leeds, Blackpool &amp; outside UK mainland will encur an extra charge.</p>
			<p>Removal &amp; Disposal of old equal size unit = &pound;150 +VAT (t&amp;c apply)</p>
			<p>Restocking charge of &pound;600 +VAT for cancelled orders</p>
			<p>Finance Available (Kennet Leasing) - Please call for details</p>
			<p>1 Year Parts &amp; Labour Warranty available at extra cost - Please call</p>
			<p>Noise level 68dBA one metre from cabinet</p>
			<p>All cabinets will become curb side delivery if there are too many steps, barriers or small doors, it will be the customers responsibility from this point onwards</p>
			<p>In the interest of continual product development we reserve the right to change any of the details and specification without notice</p>
			<p>We will terminate any Warranty/Guarantee if our staff are subjected to physical or verbal abuse in the course of carrying out their duties</p>
			<p>Every sale is considered a Business to Business Contract, so a personal purchase will not be covered under the Consumer Rights Act</p>
			<p>Train Stations, Shopping centres and out of working hour deliveries may incur a charge of &pound;300 +VAT</p>
			<p>Before purchasing, please check the user manual to ensure the fridge is suitable for your store environment</p>
		</div>

		<div class="ef-terms">
			<h3>Terms &amp; Conditions</h3>
			<p><strong>1. ORDERS:</strong> Buyer and Supplier, no orders submitted by the Buyer shall be deemed to be accepted by the Seller unless and until payment is received in full. The Buyer responsibility to ensure that the equipment will be fit for the purpose for which the Buyer intend to use the equipment. By submitting an order the customer agrees to apply the terms and condition of this agreement in priority over any other agreement. This agreement is the only form of contract between the parties and any customer terms and condition will not apply. Every sale is considered a business to business contract, So a personal purchase will not be covered under the consumer rights act. <strong>2. PRICE:</strong> the Company reserves the right to amend prices at any time consequent upon increased costs of raw materials, labour, transport, market conditions or for any other reason whatsoever. <strong>3. WEEE Directive.</strong> The Buyer will take responsibility for the finance of future collection and disposal of the cabinet, in accordance with the regulations. If the producer retains that responsibility then an extra charge will be incurred. The cost will be negotiated and invoiced separately. <strong>4. DELIVERY/COLLECTION:</strong> dates are given as estimates only and the Company cannot accept liability for any delays howsoever caused. All cabinets are delivered to the front door of ground floor locations only. The delivery person/driver may at his discretion assist with delivering the cabinets to a location within the premises nominated by the customer, at the customer&rsquo;s sole risk. <strong>5. INSTALLATION:</strong> it is the Buyer&rsquo;s responsibility to ensure that the equipment is installed and commissioned by a qualified refrigeration engineer and all costs will be borne by the Buyer. Do not install where maximum room temperature is more than 25&deg;C and humidity over 60%. Install as described in here and the manufacturer manual. The refrigerated cabinet air temperature -1&deg;C to +7&deg;C. Freezer -18&deg;C to -22&deg;C. <strong>6. TERMS OF PAYMENT:</strong> unless otherwise agreed by the Company in writing, payment for goods is strictly on a pro forma basis and the goods will not be reserved until payment is received. <strong>7. TITLE TO GOODS:</strong> The risk in the goods shall however pass to the Buyer at the time of delivery. <strong>8. CHECKING GOODS/CLAIMS FOR DAMAGE IN TRANSIT:</strong> it is the Buyer&rsquo;s responsibility to inspect the goods at the time of taking delivery/collection. Should equipment arrive damaged/missing parts, the delivery/part delivery must be refused. If signed for &ldquo;unexamined/examined&rdquo; the Buyer/Person shall be deemed to have accepted the goods and no claim for damage/missing parts will be entertained by the Seller. Refrigerators noise level 68dBA one metre from cabinet. <strong>9. CANCELLATION/RETURN OF GOODS:</strong> requests for return of goods for whatever reason will not be entertained by the Seller. Defects in quality or dimension of any delivery shall not be grounds for cancellation of the remainder of the contract. <strong>10. WARRANTY:</strong> All goods are sold without Warranty. Different warranty requirements may be negotiated on a market and country basis. We will terminate any Warranty/Guarantee if our staff are subjected to physical or verbal abuse in the course of carrying out their duties. It is the Buyer&rsquo;s legal responsibility to ensure that preventative maintenance is carried out and all electrical and mechanical parts tested every three months by a qualified refrigeration engineer as described in here and the manufacturer manual. Failure to provide satisfactory maintenance will invalidate any Warranty. All goods are sold with NO Warranty. Any Warranty will exclude any glass, light fittings, paintwork/rust, condensation, cosmetic damage or customer mis-use. The Seller shall be under no liability in respect of any defect arising from fair wear and tear: abnormal site conditions or failure to have the equipment serviced as described in here and the manufacturer manual. The Company shall not accept any claim for any loss of profit or economic loss resulting from defective parts. All parts must be paid for in full prior to despatch. Labour and transport costs for replacing faulty parts will be charged at the current rate. Following replacement of a part, the Company cannot guarantee that other parts will not fail in the future. The refrigerated cabinets must be maintained by a qualified refrigeration engineer every three months as described above and the manufacturer manual. Install where maximum room temperature is not over 25&deg;C, and Relative humidity not over 60%. <strong>11. VARIANCE:</strong> All dimensions given are to be regarded as being for guidance only and are not binding on the Company. It is the responsibility of the Buyer to allow sufficient clearance for installation: and to ensure that the ventilation grill panels are kept free for adequate air circulation.: also to ensure that cabinets are positioned away from direct sunlight or source of hot or cold air. Cabinet noise level 68dBA <strong>12. MODIFICATIONS:</strong> if any alterations have been undertaken by the buyer or a contractor the Company shall not be held responsible for any defects or problems which may occur: any such alterations will invalidate the warranty on the equipment. All costs incurred by the Supplier to diagnose problems arising from such modifications will be borne by the Buyer.</p>
		</div>
	</div>
	<?php
}

// On the single product page: after "Related products" + "Recently Viewed Products".
add_action( 'woocommerce_after_single_product', function () {
	echo ef_recently_viewed_html(); // phpcs:ignore
	ef_trade_terms_box();
}, 5 );

// On product category archives: at the bottom of the page, after the product grid
// and after the category long-form content (which is on after_shop_loop prio 20).
add_action( 'woocommerce_after_shop_loop', function () {
	if ( ! is_product_category() ) { return; }
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) { return; }
	ef_trade_terms_box();
}, 30 );

/* =========================================================================
 * 3b. WooCommerce breadcrumbs: 🏢 instead of "Home", ">>" separator
 *     (the green links are styled in style.css)
 * ========================================================================= */
add_filter( 'woocommerce_breadcrumb_defaults', function ( $args ) {
	$args['home']      = '🏢';
	$args['delimiter'] = ' <span class="ef-crumb-sep">&gt;&gt;</span> ';
	return $args;
} );

/* =========================================================================
 * 4. Product ADD-ONS: extra warranty (+15%) + Removal & Disposal (+£150)
 * ========================================================================= */

// 4a. fields on the product page (warranty under a toggle, as in the original)
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

// 4b. capture the options into the cart item data
add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	if ( ! empty( $_POST['ef_removal'] ) ) { $data['ef_removal'] = 1; }
	if ( ! empty( $_POST['ef_warranty'] ) ) { $data['ef_warranty'] = 1; }
	return $data;
}, 10, 2 );

// 4c. add-ons as SEPARATE fees in the cart — distinct lines, with VAT applied.
//     The product price stays untouched (ex-VAT); Removal & Disposal and Warranty
//     appear as their own rows, and VAT is calculated across everything (product + fees).
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
	// add_fee( name, amount, taxable=true, tax_class='' → Standard 20% )
	if ( $removal_total > 0 ) {
		$cart->add_fee( __( 'Removal & Disposal', 'eco-fridge-child' ), $removal_total, true, '' );
	}
	if ( $warranty_total > 0 ) {
		$cart->add_fee( __( 'Extended warranty (12 months)', 'eco-fridge-child' ), $warranty_total, true, '' );
	}
}, 20 );

// 4d. mark on the product line which add-ons were chosen (no price — the amount is on the fee line)
add_filter( 'woocommerce_get_item_data', function ( $items, $cart_item ) {
	if ( ! empty( $cart_item['ef_removal'] ) ) {
		$items[] = array( 'key' => 'Removal &amp; Disposal', 'value' => 'Added' );
	}
	if ( ! empty( $cart_item['ef_warranty'] ) ) {
		$items[] = array( 'key' => 'Extended warranty', 'value' => '12 months' );
	}
	return $items;
}, 10, 2 );

// 4e. save the selection as meta on the order line item (the amount stays on the order fees)
add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	if ( ! empty( $values['ef_removal'] ) ) { $item->add_meta_data( 'Removal & Disposal', 'Added', true ); }
	if ( ! empty( $values['ef_warranty'] ) ) { $item->add_meta_data( 'Extended warranty', '12 months', true ); }
}, 10, 3 );

/* =========================================================================
 * 4f. Add-on checkboxes on the /cart page, so a customer who forgot to add
 *     Removal & Disposal or the 12-month warranty on the product page can add
 *     them here. Rendered inside the cart form; a change auto-submits the form
 *     and the selection is applied to every cart item.
 * ========================================================================= */

// Render the checkboxes below the cart items (inside the cart form).
add_action( 'woocommerce_after_cart_table', function () {
	if ( ! WC()->cart || WC()->cart->is_empty() ) { return; }
	$items = WC()->cart->get_cart();
	$total = count( $items );
	$removal_on  = 0;
	$warranty_on = 0;
	foreach ( $items as $it ) {
		if ( ! empty( $it['ef_removal'] ) ) { $removal_on++; }
		if ( ! empty( $it['ef_warranty'] ) ) { $warranty_on++; }
	}
	$removal_checked  = ( $total > 0 && $removal_on === $total ) ? ' checked' : '';
	$warranty_checked = ( $total > 0 && $warranty_on === $total ) ? ' checked' : '';
	?>
	<div class="ef-cart-addons">
		<input type="hidden" name="_ef_cart_addons" value="1">
		<h3><?php esc_html_e( 'Optional extras', 'eco-fridge-child' ); ?></h3>
		<label class="ef-addon ef-addon--removal">
			<input type="checkbox" name="ef_cart_removal" value="1"<?php echo esc_attr( $removal_checked ); ?>>
			<span>Add <strong>Removal &amp; Disposal</strong> of your old unit <em>(+&pound;<?php echo esc_html( EF_REMOVAL_FEE ); ?> each)</em></span>
		</label>
		<label class="ef-addon ef-addon--warranty">
			<input type="checkbox" name="ef_cart_warranty" value="1"<?php echo esc_attr( $warranty_checked ); ?>>
			<span>Add <strong>12 months extra warranty</strong> <em>(+15%)</em></span>
		</label>
	</div>
	<?php
}, 20 );

// Apply the checkbox selection to every cart item — only when the customer
// actually toggled a checkbox (marker `ef_addons_submit`), so a normal quantity
// "Update cart" never clobbers add-ons set on the product page.
add_action( 'woocommerce_cart_updated', function () {
	if ( empty( $_POST['ef_addons_submit'] ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! isset( $_POST['woocommerce-cart-nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-cart-nonce'] ) ), 'woocommerce-cart' ) ) {
		return;
	}
	if ( ! WC()->cart ) { return; }
	$removal  = ! empty( $_POST['ef_cart_removal'] ) ? 1 : 0;
	$warranty = ! empty( $_POST['ef_cart_warranty'] ) ? 1 : 0;
	foreach ( WC()->cart->get_cart() as $key => $item ) {
		WC()->cart->cart_contents[ $key ]['ef_removal']  = $removal;
		WC()->cart->cart_contents[ $key ]['ef_warranty'] = $warranty;
	}
	WC()->cart->set_session();
} );

// Auto-submit the cart form when an add-on checkbox is toggled.
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) { return; }
	?>
<script>
(function(){
	document.querySelectorAll('.ef-cart-addons input[type="checkbox"]').forEach(function(cb){
		cb.addEventListener('change', function(){
			var form = cb.closest('form.woocommerce-cart-form');
			if(!form){ return; }
			['update_cart','ef_addons_submit'].forEach(function(name){
				if(form.querySelector('input[name="'+name+'"][type="hidden"]')){ return; }
				var h = document.createElement('input');
				h.type = 'hidden'; h.name = name; h.value = '1';
				form.appendChild(h);
			});
			form.submit();
		});
	});
})();
</script>
	<?php
}, 30 );
