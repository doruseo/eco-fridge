/**
 * Live product search: debounced fetch, keyboard navigation, ARIA listbox.
 */
( function () {
	'use strict';

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function highlight( text, term ) {
		var safe = esc( text );
		if ( ! term ) {
			return safe;
		}
		var re = new RegExp( '(' + term.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + ')', 'ig' );
		return safe.replace( re, '<mark>$1</mark>' );
	}

	function init( form ) {
		var input = form.querySelector( '.efb-search__input' );
		var box = form.querySelector( '.efb-search__results' );
		var endpoint = form.getAttribute( 'data-endpoint' );
		var timer = null;
		var controller = null;
		var active = -1;
		var cache = {};

		function close() {
			box.hidden = true;
			input.setAttribute( 'aria-expanded', 'false' );
			active = -1;
		}

		function items() {
			return box.querySelectorAll( '.efb-search__item' );
		}

		function setActive( i ) {
			var list = items();
			if ( ! list.length ) {
				return;
			}
			if ( active > -1 && list[ active ] ) {
				list[ active ].classList.remove( 'is-active' );
				list[ active ].setAttribute( 'aria-selected', 'false' );
			}
			active = ( i + list.length ) % list.length;
			list[ active ].classList.add( 'is-active' );
			list[ active ].setAttribute( 'aria-selected', 'true' );
			list[ active ].scrollIntoView( { block: 'nearest' } );
		}

		function render( data, term ) {
			var html = '';
			if ( data.categories && data.categories.length ) {
				html += '<div class="efb-search__group">Categories</div>';
				data.categories.forEach( function ( c ) {
					html += '<a class="efb-search__item efb-search__item--cat" role="option" href="' + esc( c.url ) + '"><span class="efb-search__title">' + highlight( c.name, term ) + '</span><span class="efb-search__count">' + c.count + '</span></a>';
				} );
			}
			if ( data.products && data.products.length ) {
				html += '<div class="efb-search__group">Products</div>';
				data.products.forEach( function ( p ) {
					html += '<a class="efb-search__item" role="option" href="' + esc( p.url ) + '">' +
						( p.image ? '<img src="' + esc( p.image ) + '" alt="" loading="lazy" width="48" height="48" />' : '<span class="efb-search__noimg"></span>' ) +
						'<span class="efb-search__meta"><span class="efb-search__title">' + highlight( p.title, term ) + '</span>' +
						( p.sku ? '<span class="efb-search__sku">' + highlight( p.sku, term ) + '</span>' : '' ) +
						( p.price ? '<span class="efb-search__price">' + esc( p.price ) + '</span>' : '' ) +
						'</span></a>';
				} );
				if ( data.total > data.products.length ) {
					html += '<a class="efb-search__item efb-search__all" role="option" href="' + esc( data.all_url ) + '">See all ' + data.total + ' products &rarr;</a>';
				}
			}
			if ( ! html ) {
				html = '<div class="efb-search__empty">No products found for “' + esc( term ) + '”.</div>';
			}
			box.innerHTML = html;
			box.hidden = false;
			input.setAttribute( 'aria-expanded', 'true' );
			active = -1;
		}

		function run() {
			var term = input.value.trim();
			if ( term.length < 2 ) {
				close();
				return;
			}
			if ( cache[ term ] ) {
				render( cache[ term ], term );
				return;
			}
			if ( controller ) {
				controller.abort();
			}
			controller = 'AbortController' in window ? new AbortController() : null;
			form.classList.add( 'is-loading' );
			fetch( endpoint + ( endpoint.indexOf( '?' ) > -1 ? '&' : '?' ) + 's=' + encodeURIComponent( term ), {
				signal: controller ? controller.signal : undefined,
				headers: { Accept: 'application/json' },
			} )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( data ) {
					cache[ term ] = data;
					if ( input.value.trim() === term ) {
						render( data, term );
					}
				} )
				.catch( function () {} )
				.then( function () {
					form.classList.remove( 'is-loading' );
				} );
		}

		input.addEventListener( 'input', function () {
			clearTimeout( timer );
			timer = setTimeout( run, 220 );
		} );
		input.addEventListener( 'focus', function () {
			if ( box.innerHTML && input.value.trim().length >= 2 ) {
				box.hidden = false;
				input.setAttribute( 'aria-expanded', 'true' );
			}
		} );
		input.addEventListener( 'keydown', function ( e ) {
			if ( box.hidden ) {
				return;
			}
			if ( e.key === 'ArrowDown' ) {
				e.preventDefault();
				setActive( active + 1 );
			} else if ( e.key === 'ArrowUp' ) {
				e.preventDefault();
				setActive( active - 1 );
			} else if ( e.key === 'Enter' && active > -1 ) {
				e.preventDefault();
				items()[ active ].click();
			} else if ( e.key === 'Escape' ) {
				close();
			}
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! form.contains( e.target ) ) {
				close();
			}
		} );
	}

	function boot() {
		document.querySelectorAll( 'form.efb-search' ).forEach( function ( f ) {
			if ( ! f.dataset.efbReady ) {
				f.dataset.efbReady = '1';
				init( f );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
