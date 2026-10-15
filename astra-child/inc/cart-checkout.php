<?php
/**
 * Cart and checkout: clearer and more reassuring.
 *
 *  - the cart says plainly that UK mainland delivery is free, instead of
 *    "Shipping options will be updated during checkout";
 *  - "Continue shopping" under the cart table;
 *  - the shipping calculator asks for the postcode only, and the cart shows no
 *    shipping price until there is one;
 *  - postcodes without delivery (the "Exclusions" zone) get a clear message:
 *    collection from the warehouse, or call for a delivery quote.
 * The look (cards, green accents) is in style.css → "CART & CHECKOUT".
 *
 * @package Eco-Fridge-Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// No trust strip on the cart or the checkout: it distracted from the order.

// Cart: "Continue shopping" under the table.
add_action( 'woocommerce_after_cart_table', function () {
	echo '<p class="ef-continue-shopping"><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">&larr; ' . esc_html__( 'Continue shopping', 'eco-fridge-child' ) . '</a></p>';
} );

/*
 * Cart "Calculate shipping": the delivery zones and prices go by postcode only
 * (UK Mainland, £150 / £400 areas, Exclusions), so the calculator asks for the
 * postcode alone. With a City field above it, a postcode typed there left the
 * real postcode empty – and an empty postcode matched UK Mainland, so the cart
 * offered free shipping to excluded areas such as ZE.
 */
add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_false' );
add_filter( 'woocommerce_shipping_calculator_enable_state', '__return_false' );

// No prices in the cart until there is a postcode (UK): the estimate text and the
// calculator are shown instead. Cart page only (and its AJAX refreshes): the
// checkout asks for the postcode itself, and its Store API requests (pickup
// orders included) are left alone.
add_filter( 'woocommerce_cart_ready_to_calc_shipping', function ( $ready ) {
	if ( ! $ready || ! WC()->customer || WC()->is_rest_api_request() || is_checkout() ) { return $ready; }
	$ajax = isset( $_GET['wc-ajax'] ) ? sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! is_cart() && ! in_array( $ajax, array( 'update_shipping_method', 'get_cart_totals' ), true ) ) { return $ready; }
	$country = WC()->customer->get_shipping_country();
	return ! ( ( '' === $country || 'GB' === $country ) && '' === trim( (string) WC()->customer->get_shipping_postcode() ) );
} );

/*
 * Postcodes we do not deliver to (the "Exclusions" zone, or any zone without a
 * delivery method): only collection from the warehouse is offered, so say so
 * plainly – in the cart totals and at the checkout (block, see the script below).
 */
function ef_no_delivery_message() {
	return __( 'We don’t deliver to this postcode. You can collect your order from our warehouse, or call us on 01280 811411 for a delivery quote.', 'eco-fridge-child' );
}

/**
 * Whether a shipping package has a postcode but no delivery rate (pickup only, or nothing).
 *
 * @param array $package Shipping package (with its calculated rates).
 * @return bool
 */
function ef_package_without_delivery( $package ) {
	if ( '' === trim( (string) ( $package['destination']['postcode'] ?? '' ) ) ) { return false; }
	foreach ( (array) ( $package['rates'] ?? array() ) as $rate ) {
		if ( ! in_array( $rate->get_method_id(), array( 'pickup_location', 'local_pickup' ), true ) ) { return false; }
	}
	return true;
}

add_action( 'woocommerce_cart_totals_after_shipping', function () {
	foreach ( WC()->shipping()->get_packages() as $package ) {
		if ( ef_package_without_delivery( $package ) ) {
			echo '<tr class="ef-no-delivery"><td colspan="2"><p class="ef-no-delivery__msg">' . esc_html( ef_no_delivery_message() ) . '</p></td></tr>';
			return;
		}
	}
} );

// Checkout (block): WooCommerce's notice when an address has no delivery rate
// says plainly that we do not deliver there (instead of "Please verify the
// address is correct or try a different address"). The block's texts are
// translated in the browser, so the wording is changed through wp.i18n.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }
	$texts = array(
		'No shipping options are available for this address. Please verify the address is correct or try a different address.' => __( "No shipping options are available for this address. We don't deliver to this address.", 'eco-fridge-child' ),
	);
	wp_add_inline_script(
		'wp-i18n',
		'( function () { var t = ' . wp_json_encode( $texts ) . '; wp.hooks.addFilter( "i18n.gettext_woocommerce", "eco-fridge/no-delivery-text", function ( translation, text ) { return Object.prototype.hasOwnProperty.call( t, text ) ? t[ text ] : translation; } ); } )();'
	);
}, 20 );

/*
 * Checkout (block): an address we do not deliver to – a postcode whose zone has
 * no delivery method, or a country other than the UK – switches the order to
 * "Pickup" (collection from the warehouse), marks "Ship" as not available and
 * shows the message. On the server those addresses get no delivery rate at all
 * (Exclusions and "Rest of the world" have no delivery method), so a delivery
 * order cannot be placed whatever the browser does. "Ship" stays clickable so
 * the customer can correct the address (the address fields only show there);
 * the switch to Pickup happens again only when the address changes.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) { return; }
	?>
<script>
( function () {
	if ( ! window.wp || ! wp.data ) { return; }
	var msgPostcode = <?php echo wp_json_encode( ef_no_delivery_message() ); ?>;
	var msgCountry = <?php echo wp_json_encode( __( 'We only deliver within the UK. You can collect your order from our warehouse, or call us on 01280 811411.', 'eco-fridge-child' ) ); ?>;
	var notAvailable = <?php echo wp_json_encode( __( 'Not available for this address', 'eco-fridge-child' ) ); ?>;
	var pickupIds = [ 'pickup_location', 'local_pickup' ];
	var state = { key: '', reason: '' }; // last address checked in "Ship" mode, and why it has no delivery
	var busy = false;

	function isPickup( r ) { return pickupIds.indexOf( r.method_id ) !== -1; }

	function check() {
		if ( busy ) { return; }
		var cartStore = wp.data.select( 'wc/store/cart' );
		var checkoutStore = wp.data.select( 'wc/store/checkout' );
		if ( ! cartStore || ! cartStore.getCartData || ! checkoutStore ) { return; }
		if ( cartStore.isCustomerDataUpdating && cartStore.isCustomerDataUpdating() ) { return; } // rates not final yet
		var cart = cartStore.getCartData();
		var prefersPickup = checkoutStore.prefersCollection && checkoutStore.prefersCollection();
		var packages = cart.shippingRates || [];

		// Judge the address only in "Ship" mode (in "Pickup" mode WooCommerce uses the
		// warehouse) – and the address the rates were worked out for (the package
		// destination), not the field being typed in: the rates arrive a moment later.
		if ( ! prefersPickup ) {
			var a = ( packages[0] && packages[0].destination ) || cart.shippingAddress || {};
			var country = ( a.country || '' ).toUpperCase();
			var postcode = ( a.postcode || '' ).trim();
			var key = country + '|' + postcode.toUpperCase().replace( /\s+/g, '' );
			var reason = '';
			if ( country && 'GB' !== country ) {
				reason = 'country';
			} else if ( postcode && packages.length && packages.every( function ( p ) { return ( p.shipping_rates || [] ).every( isPickup ); } ) ) {
				reason = 'postcode';
			}
			if ( key !== state.key ) {
				state.key = key;
				state.reason = reason;
				if ( reason ) { switchToPickup( packages ); }
			} else {
				state.reason = reason;
			}
		}
		render();
	}

	function switchToPickup( packages ) {
		var rate = null, pkg = 0;
		packages.forEach( function ( p ) {
			( p.shipping_rates || [] ).forEach( function ( r ) { if ( ! rate && isPickup( r ) ) { rate = r; pkg = p.package_id; } } );
		} );
		if ( ! rate ) { return; } // no collection point configured: nothing to switch to
		busy = true;
		wp.data.dispatch( 'wc/store/checkout' ).setPrefersCollection( true );
		var done = function () { busy = false; check(); };
		var res = wp.data.dispatch( 'wc/store/cart' ).selectShippingRate( rate.rate_id, pkg );
		if ( res && res.then ) { res.then( done, done ); } else { done(); }
	}

	function render() {
		var toggle = document.querySelector( '.wc-block-checkout__shipping-method-container' );
		var options = toggle ? toggle.querySelectorAll( '.wc-block-checkout__shipping-method-option' ) : [];
		var ship = null;
		// "Ship" is the option that is not the collection one.
		Array.prototype.forEach.call( options, function ( o ) {
			if ( /pickup|collection/i.test( o.textContent ) ) { return; }
			ship = o;
		} );
		if ( ship ) {
			ship.classList.toggle( 'ef-ship-unavailable', !! state.reason );
			ship.setAttribute( 'aria-disabled', state.reason ? 'true' : 'false' );
			var note = ship.querySelector( '.ef-ship-unavailable__note' );
			if ( state.reason && ! note ) {
				note = document.createElement( 'span' );
				note.className = 'ef-ship-unavailable__note';
				note.textContent = notAvailable;
				ship.appendChild( note );
			} else if ( ! state.reason && note ) {
				note.remove();
			}
		}
		var host = document.querySelector( '.wp-block-woocommerce-checkout-shipping-method-block' ) || document.querySelector( '.wp-block-woocommerce-checkout-shipping-methods-block' );
		var box = document.getElementById( 'ef-no-delivery' );
		if ( state.reason && host ) {
			if ( ! box ) {
				box = document.createElement( 'p' );
				box.id = 'ef-no-delivery';
				box.className = 'ef-no-delivery__msg';
				box.setAttribute( 'role', 'status' );
			}
			box.textContent = 'country' === state.reason ? msgCountry : msgPostcode;
			if ( box.parentNode !== host ) { host.appendChild( box ); }
		} else if ( box ) {
			box.remove();
		}
	}

	wp.data.subscribe( check );
} )();
</script>
	<?php
}, 100 );

// Cart totals before an address is known: say that UK mainland delivery is free.
add_filter( 'woocommerce_shipping_estimate_html', function () {
	return '<span class="ef-free-delivery">' . esc_html__( 'Free delivery to UK mainland', 'eco-fridge-child' ) . '</span><br><small>' . esc_html__( 'Confirmed at checkout. Deliveries above York, Leeds, Blackpool and outside UK mainland incur an extra charge.', 'eco-fridge-child' ) . '</small>';
} );

/*
 * Checkout (block): an empty optional County no longer blocks the order.
 * WooCommerce Blocks first renders the County field with the default rules
 * ("State/County", required), records a hidden "Please enter a valid
 * state/county" error, and keeps it when the UK rules (County, optional) apply –
 * so "Place order" stopped with an empty County. The stale error is cleared
 * whenever the field is optional and empty.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) { return; }
	?>
<script>
( function () {
	if ( ! window.wp || ! wp.data ) { return; }
	var ids = [ 'shipping-state', 'billing-state' ];
	wp.data.subscribe( function () {
		var store = wp.data.select( 'wc/store/validation' );
		if ( ! store || ! store.getValidationErrors ) { return; }
		var errors = store.getValidationErrors();
		ids.forEach( function ( id ) {
			var field = document.getElementById( id );
			if ( errors[ id ] && field && ! field.required && ! field.value ) {
				wp.data.dispatch( 'wc/store/validation' ).clearValidationError( id );
			}
		} );
	} );
} )();
</script>
	<?php
}, 100 );
