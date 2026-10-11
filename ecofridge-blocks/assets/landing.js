( function () {
	'use strict';

	function init( root ) {
		var list = root.querySelector( '.efbl-reviews__list' );
		var more = root.querySelector( '.efbl-reviews__more' );
		var sort = root.querySelector( '.efbl-reviews__sort' );
		var status = root.querySelector( '.efbl-reviews__status' );
		var bars = root.querySelectorAll( '.efbl-bar' );
		var state = { page: 1, rating: 0, sort: 'newest' };
		var request = 0;

		function load( append ) {
			var id = ++request;
			var params = new URLSearchParams( {
				action: 'efb_landing_reviews',
				term: root.dataset.term,
				page: state.page,
				rating: state.rating,
				sort: state.sort,
			} );

			root.classList.add( 'is-loading' );
			more.disabled = true;

			fetch( root.dataset.ajax + '?' + params, { credentials: 'same-origin' } )
				.then( function ( res ) {
					return res.json();
				} )
				.then( function ( res ) {
					if ( id !== request ) {
						return; // A newer filter/sort request superseded this one.
					}
					if ( ! res.success ) {
						throw new Error();
					}
					if ( append ) {
						list.insertAdjacentHTML( 'beforeend', res.data.html );
					} else {
						list.innerHTML = res.data.html;
					}
					more.hidden = ! res.data.has_more;
					status.textContent = res.data.status;
				} )
				.catch( function () {
					if ( id === request ) {
						status.textContent = root.dataset.error;
					}
				} )
				.finally( function () {
					if ( id === request ) {
						root.classList.remove( 'is-loading' );
						more.disabled = false;
					}
				} );
		}

		more.addEventListener( 'click', function () {
			state.page++;
			load( true );
		} );

		sort.addEventListener( 'change', function () {
			state.sort = sort.value;
			state.page = 1;
			load( false );
		} );

		bars.forEach( function ( bar ) {
			bar.addEventListener( 'click', function () {
				var rating = +bar.dataset.rating;
				state.rating = state.rating === rating ? 0 : rating;
				state.page = 1;
				bars.forEach( function ( b ) {
					b.setAttribute( 'aria-pressed', String( +b.dataset.rating === state.rating ) );
				} );
				load( false );
			} );
		} );
	}

	document.querySelectorAll( '.efbl-reviews' ).forEach( init );

	// FAQ: slide answers open/closed in 300 ms, like the homepage accordion.
	// Without JavaScript the <details> elements still open and close instantly.
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	if ( ! reduceMotion ) {
		document.querySelectorAll( '.efbl-faq__item' ).forEach( function ( item ) {
			var summary = item.querySelector( '.efbl-faq__q' );
			var answer = item.querySelector( '.efbl-faq__a' );

			function finish() {
				item.classList.remove( 'is-animating' );
				answer.style.height = '';
			}

			summary.addEventListener( 'click', function ( e ) {
				if ( item.classList.contains( 'is-animating' ) ) {
					e.preventDefault();
					return;
				}
				e.preventDefault();
				item.classList.add( 'is-animating' );

				if ( item.open ) {
					answer.style.height = answer.offsetHeight + 'px';
					requestAnimationFrame( function () {
						answer.style.height = '0px';
					} );
					setTimeout( function () {
						item.open = false;
						finish();
					}, 300 );
				} else {
					item.open = true;
					var target = answer.offsetHeight;
					answer.style.height = '0px';
					requestAnimationFrame( function () {
						answer.style.height = target + 'px';
					} );
					setTimeout( finish, 300 );
				}
			} );
		} );
	}
} )();

/* Product carousels: previous / next buttons, and the quantity box next to Add to cart. */
( function () {
	'use strict';

	function setupCarousel( root ) {
		var track = root.querySelector( '.efbl-carousel__track' );
		var prev = root.querySelector( '[data-efbl-dir="-1"]' );
		var next = root.querySelector( '[data-efbl-dir="1"]' );
		var frame = 0;

		function update() {
			frame = 0;
			var overflow = track.scrollWidth > track.clientWidth + 1;
			prev.hidden = next.hidden = ! overflow;
			// A few pixels of slack: the track's padding and scroll-snap leave it at 1–3px, not 0.
			prev.disabled = track.scrollLeft <= 4;
			next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
		}
		function schedule() {
			if ( ! frame ) {
				frame = window.requestAnimationFrame( update );
			}
		}

		[ prev, next ].forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				// One "page" of cards at a time; scroll-snap lines the first card up.
				track.scrollBy( { left: +button.dataset.efblDir * track.clientWidth, behavior: 'smooth' } );
			} );
		} );
		track.addEventListener( 'scroll', schedule, { passive: true } );
		window.addEventListener( 'resize', schedule );
		update();
	}

	// WooCommerce's AJAX add-to-cart reads the quantity from the button's data, so keep it in sync.
	function syncButton( input ) {
		var card = input.closest( '.efbl-card' );
		var button = card && card.querySelector( '.add_to_cart_button' );
		if ( ! button ) {
			return;
		}
		var qty = String( input.value );
		button.setAttribute( 'data-quantity', qty );
		if ( window.jQuery ) {
			window.jQuery( button ).data( 'quantity', qty );
		}
		try {
			var url = new URL( button.href, window.location.href );
			url.searchParams.set( 'quantity', qty ); // Used when JavaScript add-to-cart is off.
			button.href = url.toString();
		} catch ( e ) {}
	}

	function clamp( input, value ) {
		var min = parseInt( input.min, 10 ) || 1;
		var max = parseInt( input.max, 10 ) || Infinity;
		value = parseInt( value, 10 );
		return Math.min( max, Math.max( min, isNaN( value ) ? min : value ) );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.efbl-qty__btn' );
		if ( ! button ) {
			return;
		}
		var input = button.parentNode.querySelector( '.efbl-qty__input' );
		input.value = clamp( input, ( parseInt( input.value, 10 ) || 0 ) + +button.dataset.efblStep );
		syncButton( input );
	} );

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.classList && event.target.classList.contains( 'efbl-qty__input' ) ) {
			event.target.value = clamp( event.target, event.target.value );
			syncButton( event.target );
		}
	} );

	document.addEventListener( 'input', function ( event ) {
		if ( event.target.classList && event.target.classList.contains( 'efbl-qty__input' ) && '' !== event.target.value ) {
			syncButton( event.target );
		}
	} );

	function init() {
		document.querySelectorAll( '[data-efbl-carousel]' ).forEach( setupCarousel );
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
