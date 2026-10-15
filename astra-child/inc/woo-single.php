<?php
/**
 * ECO-Fridge single product logic (Astra child theme).
 * Replicates elements from the original theme (single-product.php) + the YITH add-ons:
 *   - "+VAT" / £POA (from-price) pricing for products without a price
 *   - "Back to {category}" link
 *   - delivery/trade notes + T&C below the summary (global hook)
 *   - add-ons per product, as on live: Removal & Disposal (+£150), 12 months parts
 *     warranty (+15% of the current price) under a toggle, Castors (+£100) —
 *     integrated into the cart, totals, checkout and order meta. Every product
 *     shows the Warranty toggle; without the parts warranty it offers only
 *     "Wholesale order".
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const EF_REMOVAL_FEE   = 150;   // £ Removal & Disposal
const EF_WARRANTY_RATE = 0.15;  // 12 months parts warranty: 15% of the current price
const EF_CASTORS_FEE   = 100;   // £ Castors

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
 * 3. Delivery / trade notes + T&C — at the very bottom of the page content,
 *    after "Related products", "Recently Viewed Products" and the category sections.
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

// On the single product page: after "Related products".
add_action( 'woocommerce_after_single_product', function () {
	echo ef_recently_viewed_html(); // phpcs:ignore
}, 5 );

// The trade notes + T&C box closes the page content on product pages and
// product category archives: after everything else (product grid, category
// texts and landing sections, related / recently viewed products).
add_action( 'woocommerce_after_main_content', function () {
	if ( is_product() || is_product_category() ) {
		ef_trade_terms_box();
	}
}, 5 );

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
 * 4. Product ADD-ONS — 1:1 with the original YITH add-ons on the live site:
 *      - Removal & Disposal: £150 per unit (fixed)
 *      - 12 months parts warranty: 15% of the product's CURRENT price per unit
 *        (the sale price when the product is on offer, as YITH calculated it)
 *      - Castors: £100 per unit (fixed)
 *    Each product offers only its own add-ons (product meta `_ef_addons`,
 *    synced from live by migration/32-addons-live.php, editable in the
 *    product's General tab). Products without any show none.
 * ========================================================================= */

/**
 * Add-on definitions: label shown to the customer, fee line name, price rule.
 * The order is the display order (product page, cart, fee lines).
 *
 * @return array<string,array{label:string,fee:string,type:string,amount:float}>
 */
function ef_addon_defs() {
	return array(
		'removal'  => array(
			'label'  => 'Removal &amp; Disposal',
			'fee'    => 'Removal & Disposal',
			'type'   => 'fixed',
			'amount' => (float) EF_REMOVAL_FEE,
		),
		'castors'  => array(
			'label'  => 'Castors',
			'fee'    => 'Castors',
			'type'   => 'fixed',
			'amount' => (float) EF_CASTORS_FEE,
		),
		'warranty' => array(
			'label'  => '12 months parts warranty',
			'fee'    => '12 months parts warranty',
			'type'   => 'percent',
			'amount' => (float) EF_WARRANTY_RATE,
		),
	);
}

/**
 * Add-on keys a product offers.
 *
 * @param int $product_id Product ID.
 * @return string[]
 */
function ef_product_addons( $product_id ) {
	$keys = get_post_meta( $product_id, '_ef_addons', true );
	return array_values( array_intersect( array_keys( ef_addon_defs() ), is_array( $keys ) ? $keys : array() ) );
}

/**
 * Price of one add-on for one unit of a product (ex-VAT).
 *
 * @param string     $key     Add-on key.
 * @param WC_Product $product Product.
 * @return float
 */
function ef_addon_unit_price( $key, $product ) {
	$def = ef_addon_defs()[ $key ];
	if ( 'percent' === $def['type'] ) {
		return round( (float) $product->get_price() * $def['amount'], 2 );
	}
	return $def['amount'];
}

/**
 * Checkbox label, e.g. "12 months parts warranty + 15% (+£97.50)".
 *
 * @param string          $key     Add-on key.
 * @param WC_Product|null $product Product (for the calculated amount).
 * @return string HTML.
 */
function ef_addon_label( $key, $product = null ) {
	$def   = ef_addon_defs()[ $key ];
	$label = '<strong>' . $def['label'] . '</strong>';
	if ( 'percent' === $def['type'] ) {
		$label .= ' + ' . ( $def['amount'] * 100 ) . '%';
	}
	if ( $product && '' !== (string) $product->get_price() ) {
		$label .= ' <em>(+' . wc_price( ef_addon_unit_price( $key, $product ) ) . ')</em>';
	} elseif ( 'fixed' === $def['type'] ) {
		$label .= ' <em>(+&pound;' . esc_html( $def['amount'] ) . ')</em>';
	}
	return $label;
}

// 4a. fields on the product page: at the top of the cart form, which Astra shows
//     right under the price, in the order Removal & Disposal, Castors, Warranty
//     (above the quantity and Add to cart). Removal and Castors are visible
//     checkboxes; the warranty is a collapsed "Warranty" panel (arrow down /
//     up) with two exclusive options: "Wholesale order" (default, no warranty)
//     or the 12 months parts warranty. The radio posts ef_warranty = 0 / 1, so
//     the cart logic below is unchanged.
//     Every product shows the Warranty panel: a product without the parts
//     warranty add-on shows "Wholesale order" as its only option.
add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) || ! $product->is_purchasable() ) { return; }
	$keys         = ef_product_addons( $product->get_id() );
	$has_warranty = in_array( 'warranty', $keys, true );
	echo '<div class="ef-addons">';
	foreach ( $keys as $key ) {
		if ( 'warranty' === $key ) {
			continue;
		}
		echo '<label class="ef-addon ef-addon--' . esc_attr( $key ) . '"><input type="checkbox" name="ef_' . esc_attr( $key ) . '" value="1"> <span>Add ' . ef_addon_label( $key, $product ) . '</span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
	}
	?>
	<details class="ef-addon-group ef-addon-group--warranty">
		<summary>
			<svg class="ef-addon-group__chevron" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span class="ef-addon-group__title">Warranty</span>
			<span class="ef-addon-group__value" data-ef-warranty-value>Wholesale order</span>
		</summary>
		<div class="ef-addon-group__body">
			<label class="ef-addon ef-addon--radio">
				<input type="radio" name="ef_warranty" value="0" checked data-ef-label="Wholesale order">
				<span><strong>Wholesale order</strong> - Sold without warranty, as per our terms and conditions</span>
			</label>
			<?php if ( $has_warranty ) : ?>
				<label class="ef-addon ef-addon--radio">
					<input type="radio" name="ef_warranty" value="1" data-ef-label="12 months parts warranty">
					<span>Add <?php echo ef_addon_label( 'warranty', $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts. ?></span>
				</label>
			<?php endif; ?>
		</div>
	</details>
	<?php if ( $has_warranty ) : ?>
		<script>
		(function(){
			var g = document.currentScript.previousElementSibling;
			var v = g.querySelector('[data-ef-warranty-value]');
			g.addEventListener('change', function(e){
				if (e.target.name === 'ef_warranty') { v.textContent = e.target.getAttribute('data-ef-label'); g.classList.toggle('is-added', e.target.value === '1'); }
			});
		})();
		</script>
	<?php endif; ?>
	<?php
	echo '</div>';
}, 5 );

// 4b. capture the options into the cart item data (only those the product offers).
add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	foreach ( ef_product_addons( $product_id ) as $key ) {
		if ( ! empty( $_POST[ 'ef_' . $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC add-to-cart request.
			$data[ 'ef_' . $key ] = 1;
		}
	}
	return $data;
}, 10, 2 );

// 4c. add-ons as SEPARATE fees in the cart — distinct lines, with VAT applied.
//     The product price stays untouched (ex-VAT); each add-on appears as its own
//     row (per unit × quantity), and VAT is calculated across everything.
add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; }
	$totals = array();
	foreach ( $cart->get_cart() as $item ) {
		$qty = max( 1, (int) $item['quantity'] );
		foreach ( ef_product_addons( $item['product_id'] ) as $key ) {
			if ( ! empty( $item[ 'ef_' . $key ] ) ) {
				$totals[ $key ] = ( $totals[ $key ] ?? 0 ) + ef_addon_unit_price( $key, $item['data'] ) * $qty;
			}
		}
	}
	// add_fee( name, amount, taxable=true, tax_class='' → Standard 20% )
	foreach ( ef_addon_defs() as $key => $def ) {
		if ( ! empty( $totals[ $key ] ) ) {
			$cart->add_fee( $def['fee'], $totals[ $key ], true, '' );
		}
	}
}, 20 );

// 4d. mark on the product line which add-ons were chosen (no price — the amount is on the fee line)
add_filter( 'woocommerce_get_item_data', function ( $items, $cart_item ) {
	foreach ( ef_addon_defs() as $key => $def ) {
		if ( ! empty( $cart_item[ 'ef_' . $key ] ) ) {
			$items[] = array( 'key' => $def['label'], 'value' => 'Added' );
		}
	}
	return $items;
}, 10, 2 );

// 4e. save the selection as meta on the order line item (the amount stays on the order fees)
add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	foreach ( ef_addon_defs() as $addon => $def ) {
		if ( ! empty( $values[ 'ef_' . $addon ] ) ) {
			$item->add_meta_data( $def['fee'], 'Added', true );
		}
	}
}, 10, 3 );

// "Order again" restores the add-ons chosen on the original order.
add_filter( 'woocommerce_order_again_cart_item_data', function ( $data, $item ) {
	foreach ( ef_addon_defs() as $addon => $def ) {
		if ( $item->get_meta( $def['fee'] ) ) {
			$data[ 'ef_' . $addon ] = 1;
		}
	}
	return $data;
}, 10, 2 );

// Admin: choose which add-ons a product offers (General tab).
add_action( 'woocommerce_product_options_general_product_data', function () {
	global $post;
	$keys = ef_product_addons( $post->ID );
	echo '<div class="options_group"><p class="form-field"><label>' . esc_html__( 'Add-ons offered', 'eco-fridge-child' ) . '</label>';
	foreach ( ef_addon_defs() as $key => $def ) {
		printf(
			'<label style="display:block;width:auto;float:none;margin:0 0 4px"><input type="checkbox" name="ef_addons[]" value="%1$s" %2$s> %3$s</label>',
			esc_attr( $key ),
			checked( in_array( $key, $keys, true ), true, false ),
			wp_kses_post( ef_addon_label( $key ) )
		);
	}
	echo '</p></div>';
} );
add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies the product save nonce.
	$keys = isset( $_POST['ef_addons'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['ef_addons'] ) ) : array();
	$product->update_meta_data( '_ef_addons', array_values( array_intersect( array_keys( ef_addon_defs() ), $keys ) ) );
} );

/* =========================================================================
 * 4f. PER-ITEM add-on checkboxes on the /cart page, so a customer who forgot an
 *     add-on on the product page can add it here — to a single item or to
 *     several. Rendered inside each cart row (inside the cart form); a change
 *     auto-submits and applies per item.
 * ========================================================================= */

// Render the product's add-on checkboxes under each cart item's name (inside the cart form).
add_action( 'woocommerce_after_cart_item_name', function ( $cart_item, $cart_item_key ) {
	$keys = ef_product_addons( $cart_item['product_id'] );
	if ( ! $keys ) { return; }
	echo '<div class="ef-cart-item-addons">';
	foreach ( $keys as $key ) {
		printf(
			'<label class="ef-addon ef-addon--%1$s"><input type="checkbox" name="ef_item_%1$s[%2$s]" value="1" %3$s> <span>Add %4$s</span></label>',
			esc_attr( $key ),
			esc_attr( $cart_item_key ),
			checked( ! empty( $cart_item[ 'ef_' . $key ] ), true, false ),
			ef_addon_label( $key, $cart_item['data'] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
		);
	}
	echo '</div>';
}, 10, 2 );

/**
 * Apply the per-item checkbox selection when the cart form is submitted.
 *
 * IMPORTANT: `WC_Cart::set_session()` itself fires `woocommerce_cart_updated`,
 * so a naive handler that calls set_session() here would recurse infinitely
 * (that was the "critical error"). A one-shot static guard set BEFORE the call
 * prevents both the recursion and any reprocessing later in the request.
 */
add_action( 'woocommerce_cart_updated', function () {
	static $done = false;
	if ( $done ) { return; }
	if ( empty( $_POST['ef_addons_submit'] ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$nonce = isset( $_POST['woocommerce-cart-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce-cart-nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'woocommerce-cart' ) ) { return; }
	if ( ! WC()->cart ) { return; }

	$done   = true; // set before set_session() to break the cart_updated recursion.
	$chosen = array();
	foreach ( array_keys( ef_addon_defs() ) as $addon ) {
		$chosen[ $addon ] = isset( $_POST[ 'ef_item_' . $addon ] ) ? array_map( 'absint', (array) wp_unslash( $_POST[ 'ef_item_' . $addon ] ) ) : array(); // phpcs:ignore
	}
	foreach ( WC()->cart->get_cart() as $key => $item ) {
		$allowed = ef_product_addons( $item['product_id'] );
		foreach ( array_keys( ef_addon_defs() ) as $addon ) {
			WC()->cart->cart_contents[ $key ][ 'ef_' . $addon ] = ( in_array( $addon, $allowed, true ) && ! empty( $chosen[ $addon ][ $key ] ) ) ? 1 : 0;
		}
	}
	WC()->cart->set_session();
}, 10 );

// Auto-submit the cart form when a per-item add-on checkbox is toggled.
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) { return; }
	?>
<script>
(function(){
	document.querySelectorAll('.ef-cart-item-addons input[type="checkbox"]').forEach(function(cb){
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

/* =========================================================================
 * 4g. Cart cross-sells ("You may be interested in…"): removed from /cart.
 *     Emptying the id list kills the block at the source, so it stays gone no
 *     matter who calls woocommerce_cross_sell_display() (hook or template).
 *     The add-on products (Removal & Disposal, warranty) were surfacing here
 *     as suggestions, which made no sense next to the items they attach to.
 * ========================================================================= */
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
add_filter( 'woocommerce_cart_crosssell_ids', '__return_empty_array', 99 );
