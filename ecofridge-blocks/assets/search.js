/**
 * Live search: keyboard navigation, ARIA listbox.
 * Categories and tags on the left, products on the right.
 *
 * Instant: the first time a search box is touched, the small search index
 * (categories, tags, products) is downloaded once and kept in the browser;
 * every keystroke is then answered locally with the same rules as the PHP
 * efb_search(). The REST search is used until the index has arrived, and
 * when the index finds no product (it also reads the full descriptions).
 */
( function () {
	'use strict';

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* —— local search (mirrors includes/search.php) —— */

	var index = null;
	var indexLoading = null;

	function singular( w ) {
		if ( w.length > 4 && w.slice( -3 ) === 'ies' ) {
			return w.slice( 0, -3 ) + 'y';
		}
		if ( w.length > 3 && w.slice( -1 ) === 's' && w.slice( -2 ) !== 'ss' ) {
			return w.slice( 0, -1 );
		}
		return w;
	}

	function words( text ) {
		return String( text ).toLowerCase().replace( /&/g, ' and ' ).split( /[^a-z0-9]+/ ).filter( Boolean ).map( singular );
	}

	function score( q, name ) {
		var w = words( name );
		if ( ! q.length || ! w.length ) {
			return 0;
		}
		var total = 0;
		for ( var i = 0; i < q.length; i++ ) {
			var best = 0;
			for ( var j = 0; j < w.length; j++ ) {
				if ( w[ j ] === q[ i ] ) {
					best = 20;
					break;
				}
				if ( w[ j ].indexOf( q[ i ] ) === 0 ) {
					best = 12;
				}
			}
			if ( ! best ) {
				return 0;
			}
			total += best;
		}
		var phrase = q.join( ' ' );
		var joined = w.join( ' ' );
		if ( joined.indexOf( phrase ) === 0 ) {
			total += 40;
		} else if ( ( ' ' + joined ).indexOf( ' ' + phrase ) > -1 ) {
			total += 20;
		}
		return total + Math.max( 0, 10 - 2 * ( w.length - q.length ) );
	}

	function prepare( data ) {
		data.catById = {};
		data.cats.forEach( function ( c ) {
			data.catById[ c.id ] = c;
		} );
		data.products.forEach( function ( p ) {
			p.hay = ( p.t + ' ' + p.s + ' ' + p.x ).toLowerCase();
			p.title = p.t.toLowerCase();
		} );
		return data;
	}

	function loadIndex( url ) {
		if ( index || indexLoading || ! url || ! window.fetch ) {
			return;
		}
		var key = 'efb-search-index';
		try {
			// Same version and less than an hour old (a page served from a
			// cache may still name an older version).
			var saved = JSON.parse( window.localStorage.getItem( key ) || 'null' );
			if ( saved && saved.url === url && Date.now() - ( saved.at || 0 ) < 36e5 ) {
				index = prepare( saved.data );
				return;
			}
		} catch ( e ) {}
		var fresh = url + ( saved && saved.url === url ? '&t=' + Math.floor( Date.now() / 36e5 ) : '' );
		indexLoading = fetch( fresh, { headers: { Accept: 'application/json' } } )
			.then( function ( r ) {
				return r.ok ? r.json() : null;
			} )
			.then( function ( data ) {
				if ( ! data || ! data.products ) {
					return;
				}
				try {
					window.localStorage.setItem( key, JSON.stringify( { url: url, at: Date.now(), data: data } ) );
				} catch ( e ) {}
				index = prepare( data );
			} )
			.catch( function () {} )
			.then( function () {
				indexLoading = null;
			} );
	}

	function localSearch( term, limit ) {
		var q = words( term );
		var d = index;

		// 1. Categories.
		var scored = [];
		d.cats.forEach( function ( c ) {
			var s = 0;
			c.l.forEach( function ( label ) {
				s = Math.max( s, score( q, label ) );
			} );
			if ( s ) {
				scored.push( { c: c, s: s + ( c.m ? 15 : 0 ) + ( c.pa === 0 ? 10 : 0 ) + Math.min( 10, Math.round( 2 * Math.log( 1 + c.k ) ) ) } );
			}
		} );
		scored.sort( function ( a, b ) {
			return b.s - a.s;
		} );

		// 2. Tags.
		var tags = [];
		d.tags.forEach( function ( t ) {
			var s = score( q, t.n );
			if ( s ) {
				tags.push( { t: t, s: s } );
			}
		} );
		tags.sort( function ( a, b ) {
			return b.s - a.s || b.t.k - a.t.k;
		} );
		tags = tags.slice( 0, 6 );

		// 3. Products: every word somewhere; every word in the title first,
		// then SKU matches, then the shop order.
		var stems = ( q.length ? q : [ term.toLowerCase() ] ).map( function ( w ) {
			return w.length > 4 && w.slice( -1 ) === 'y' ? w.slice( 0, -1 ) : w;
		} );
		var whole = term.toLowerCase();
		var found = [];
		d.products.forEach( function ( p, i ) {
			for ( var k = 0; k < stems.length; k++ ) {
				if ( p.hay.indexOf( stems[ k ] ) < 0 ) {
					return;
				}
			}
			var inTitle = stems.every( function ( w ) {
				return p.title.indexOf( w ) > -1;
			} );
			found.push( { p: p, r: ( inTitle ? 0 : 2 ) + ( p.s.toLowerCase().indexOf( whole ) > -1 ? 0 : 1 ), i: i } );
		} );
		found.sort( function ( a, b ) {
			return a.r - b.r || a.i - b.i;
		} );
		var list = found.map( function ( f ) {
			return f.p;
		} );

		// Few text matches: add the products of the best categories and tag.
		if ( list.length < limit && ( scored.length || tags.length ) ) {
			var catIds = scored.slice( 0, 2 ).map( function ( x ) {
				return x.c.id;
			} );
			var tagId = tags.length ? tags[ 0 ].t.id : 0;
			d.products.forEach( function ( p ) {
				if ( list.indexOf( p ) < 0 && ( p.c.some( function ( id ) {
					return catIds.indexOf( id ) > -1;
				} ) || ( tagId && p.g.indexOf( tagId ) > -1 ) ) ) {
					list.push( p );
				}
			} );
		}
		list = list.slice( 0, 60 );

		// No category name matched: the menu categories of the products found.
		if ( ! scored.length && list.length ) {
			var counts = {};
			list.slice( 0, 30 ).forEach( function ( p ) {
				p.a.forEach( function ( id ) {
					var c = d.catById[ id ];
					if ( c && c.m ) {
						counts[ id ] = ( counts[ id ] || 0 ) + 1;
					}
				} );
			} );
			// Ties: the bigger category, then by name (as the PHP search).
			scored = Object.keys( counts ).map( function ( id ) {
				return { c: d.catById[ id ], s: counts[ id ] };
			} ).sort( function ( a, b ) {
				return b.s - a.s || b.c.k - a.c.k || ( a.c.n < b.c.n ? -1 : a.c.n > b.c.n ? 1 : 0 );
			} );
		}

		return {
			categories: scored.slice( 0, 6 ).map( function ( x ) {
				var parent = x.c.pa && d.catById[ x.c.pa ];
				return { name: x.c.n, parent: parent ? parent.n : '', url: x.c.u, count: x.c.k, image: x.c.im };
			} ),
			tags: tags.map( function ( x ) {
				return { name: x.t.n, url: x.t.u, count: x.t.k };
			} ),
			products: list.slice( 0, limit ).map( function ( p ) {
				return { title: p.t, url: p.u, image: p.i, sku: p.s, price: p.p };
			} ),
			total: list.length,
			popular: scored.length || list.length ? [] : ( d.popular || [] ),
		};
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

		// Highlight each typed word (not only the whole phrase).
		function marks( text, term ) {
			var words = term.split( /\s+/ ).filter( function ( w ) {
				return w.length > 1;
			} );
			var safe = esc( text );
			if ( ! words.length ) {
				return safe;
			}
			var re = new RegExp( '(' + words.map( function ( w ) {
				return esc( w ).replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
			} ).join( '|' ) + ')', 'ig' );
			return safe.replace( re, '<mark>$1</mark>' );
		}

		var ICON_GRID = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M4 4h7v7H4zm9 0h7v7h-7zM4 13h7v7H4zm9 0h7v7h-7z"/></svg>';
		var ICON_TAG = '<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8zM7 7h.01"/></svg>';

		function models( n ) {
			return n + ( n === 1 ? ' model' : ' models' );
		}

		/*
		 * Top: "N results for …" + "See all products". Left column: the best
		 * matching category as a card, the other categories, then tags as
		 * filter chips. Right column: products. One column on narrow screens;
		 * nothing found: the main ranges and "Ask our team".
		 */
		function render( data, term ) {
			var side = '';
			var main = '';
			var cats = data.categories || [];
			if ( cats.length ) {
				var best = cats[ 0 ];
				side += '<div class="efb-search__group">Best match</div>' +
					'<a class="efb-search__item efb-search__best" role="option" href="' + esc( best.url ) + '">' +
					( best.image ? '<span class="efb-search__best-img"><img src="' + esc( best.image ) + '" alt="" loading="lazy" width="64" height="64" /></span>' : '<span class="efb-search__best-img efb-search__best-img--icon">' + ICON_GRID + '</span>' ) +
					'<span class="efb-search__meta"><span class="efb-search__title">' + marks( best.name, term ) + '</span>' +
					'<span class="efb-search__parent">' + models( best.count ) + ( best.parent ? ' · in ' + esc( best.parent ) : '' ) + '</span></span>' +
					'<span class="efb-search__go" aria-hidden="true">&rarr;</span></a>';
				if ( cats.length > 1 ) {
					side += '<div class="efb-search__group">More categories</div>';
					cats.slice( 1 ).forEach( function ( c ) {
						side += '<a class="efb-search__item efb-search__item--cat" role="option" href="' + esc( c.url ) + '">' +
							'<span class="efb-search__ico">' + ICON_GRID + '</span>' +
							'<span class="efb-search__meta"><span class="efb-search__title">' + marks( c.name, term ) + '</span>' +
							( c.parent ? '<span class="efb-search__parent">in ' + esc( c.parent ) + '</span>' : '' ) + '</span>' +
							'<span class="efb-search__count">' + c.count + '</span></a>';
					} );
				}
			}
			if ( data.tags && data.tags.length ) {
				side += '<div class="efb-search__group">Popular filters</div><div class="efb-search__tags">';
				data.tags.forEach( function ( t ) {
					side += '<a class="efb-search__item efb-search__tag" role="option" href="' + esc( t.url ) + '">' + ICON_TAG + '<span>' + marks( t.name, term ) + '</span></a>';
				} );
				side += '</div>';
			}
			if ( data.products && data.products.length ) {
				main += '<div class="efb-search__group">Products</div>';
				data.products.forEach( function ( p ) {
					main += '<a class="efb-search__item" role="option" href="' + esc( p.url ) + '">' +
						( p.image ? '<img src="' + esc( p.image ) + '" alt="" loading="lazy" width="48" height="48" />' : '<span class="efb-search__noimg"></span>' ) +
						'<span class="efb-search__meta"><span class="efb-search__title">' + marks( p.title, term ) + '</span>' +
						( p.sku ? '<span class="efb-search__sku">' + marks( p.sku, term ) + '</span>' : '' ) +
						( p.price ? '<span class="efb-search__price">' + esc( p.price ) + '</span>' : '' ) +
						'</span></a>';
				} );
				if ( data.total > data.products.length ) {
					main += '<a class="efb-search__item efb-search__all" role="option" href="' + esc( data.all_url ) + '">See all ' + ( data.total >= 60 ? '60+' : data.total ) + ' products &rarr;</a>';
				}
			}
			var html = '';
			if ( side || main ) {
				var total = data.total || 0;
				html = '<div class="efb-search__bar"><span class="efb-search__summary">' +
					// The search stops counting at 60.
					( total ? '<strong>' + ( total >= 60 ? '60+' : total ) + '</strong> ' + ( total === 1 ? 'product' : 'products' ) + ' for ' : 'Results for ' ) +
					'“' + esc( term ) + '”</span>' +
					( total ? '<a class="efb-search__item efb-search__allbtn" role="option" href="' + esc( data.all_url ) + '">See all products <span aria-hidden="true">&rarr;</span></a>' : '' ) +
					'</div>';
				html += side && main ?
					'<div class="efb-search__cols"><div class="efb-search__col efb-search__col--side">' + side + '</div><div class="efb-search__col">' + main + '</div></div>' :
					'<div class="efb-search__single">' + ( side || main ) + '</div>';
			} else {
				html = '<div class="efb-search__empty">' +
					'<p class="efb-search__empty-title">No results for “' + esc( term ) + '”</p>' +
					'<p class="efb-search__empty-text">Check the spelling, try a more general word, or browse our ranges:</p>';
				if ( data.popular && data.popular.length ) {
					html += '<div class="efb-search__tags efb-search__tags--ranges">';
					data.popular.forEach( function ( c ) {
						html += '<a class="efb-search__item efb-search__tag" role="option" href="' + esc( c.url ) + '">' + ICON_GRID + '<span>' + esc( c.name ) + '</span></a>';
					} );
					html += '</div>';
				}
				html += '<div class="efb-search__help"><span>Can’t find what you need? We supply and source equipment for every food business.</span>' +
					'<a class="efb-search__item efb-search__cta" role="option" href="' + esc( form.getAttribute( 'data-contact' ) || '/contact-us/' ) + '">Ask our team <span aria-hidden="true">&rarr;</span></a></div>' +
					'</div>';
			}
			box.classList.toggle( 'efb-search__results--wide', !! ( side && main ) );
			box.classList.toggle( 'efb-search__results--empty', ! ( side || main ) );
			box.innerHTML = html;
			box.hidden = false;
			place();
			input.setAttribute( 'aria-expanded', 'true' );
			active = -1;
		}

		// The wide panel opens towards the side of the page with more room.
		function place() {
			box.style.left = '';
			box.style.right = '';
			if ( window.innerWidth < 700 || ! ( box.classList.contains( 'efb-search__results--wide' ) || box.classList.contains( 'efb-search__results--empty' ) ) ) {
				return;
			}
			var r = form.getBoundingClientRect();
			if ( r.right - box.offsetWidth < 8 ) {
				box.style.left = '0';
				box.style.right = 'auto';
			} else {
				box.style.left = 'auto';
				box.style.right = '0';
			}
		}

		function allUrl( term ) {
			return form.getAttribute( 'action' ) + '?s=' + encodeURIComponent( term ) + '&post_type=product';
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
			// Index in the browser: answer at once. Only when it finds no
			// product does the server look through the full descriptions.
			if ( index ) {
				var local = localSearch( term, 8 );
				local.all_url = allUrl( term );
				if ( local.products.length ) {
					cache[ term ] = local;
					render( local, term );
					return;
				}
				render( local, term );
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
					var found = ( data.products && data.products.length ) || ( data.categories && data.categories.length );
					// The browser already answered: replace it only with more.
					if ( index && ! found ) {
						return;
					}
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
			timer = setTimeout( run, index ? 30 : 200 );
		} );
		// Fetch the index as soon as the visitor heads for the search box.
		[ 'pointerenter', 'touchstart', 'focusin' ].forEach( function ( type ) {
			form.addEventListener( type, function () {
				loadIndex( form.getAttribute( 'data-index' ) );
			}, { passive: true } );
		} );
		input.addEventListener( 'focus', function () {
			openFull();
			if ( box.innerHTML && input.value.trim().length >= 2 ) {
				box.hidden = false;
				input.setAttribute( 'aria-expanded', 'true' );
			}
		} );

		// Phones: the search takes the whole screen while in use.
		var phone = window.matchMedia ? window.matchMedia( '(max-width: 699px)' ) : null;
		function openFull() {
			if ( phone && phone.matches && ! form.classList.contains( 'is-full' ) ) {
				form.classList.add( 'is-full' );
				document.documentElement.classList.add( 'efb-search-open' );
			}
		}
		function closeFull() {
			form.classList.remove( 'is-full' );
			document.documentElement.classList.remove( 'efb-search-open' );
		}
		var closeBtn = form.querySelector( '.efb-search__close' );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function () {
				close();
				closeFull();
				input.blur();
			} );
		}
		input.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && form.classList.contains( 'is-full' ) ) {
				close();
				closeFull();
				input.blur();
				return;
			}
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
				closeFull();
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
