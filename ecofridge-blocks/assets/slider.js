/**
 * Hero slider: crossfade, autoplay, dots, arrows, swipe, pause on hover/focus,
 * respects prefers-reduced-motion.
 */
( function () {
	'use strict';

	function init( hero ) {
		var slides = hero.querySelectorAll( '.efb-hero__slide' );
		var dots = hero.querySelectorAll( '.efb-hero__dot' );
		var n = slides.length;
		var i = 0;
		var timer = null;
		var delay = parseInt( hero.getAttribute( 'data-autoplay' ), 10 ) || 0;
		var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( n < 2 ) {
			return;
		}

		function go( k ) {
			slides[ i ].classList.remove( 'is-active' );
			slides[ i ].setAttribute( 'aria-hidden', 'true' );
			slides[ i ].querySelectorAll( 'a' ).forEach( function ( a ) {
				a.setAttribute( 'tabindex', '-1' );
			} );
			if ( dots[ i ] ) {
				dots[ i ].classList.remove( 'is-active' );
			}
			i = ( k + n ) % n;
			slides[ i ].classList.add( 'is-active' );
			slides[ i ].removeAttribute( 'aria-hidden' );
			slides[ i ].querySelectorAll( 'a' ).forEach( function ( a ) {
				a.removeAttribute( 'tabindex' );
			} );
			if ( dots[ i ] ) {
				dots[ i ].classList.add( 'is-active' );
			}
		}
		function stop() {
			if ( timer ) {
				clearInterval( timer );
				timer = null;
			}
		}
		function start() {
			stop();
			if ( delay && ! reduced ) {
				timer = setInterval( function () {
					go( i + 1 );
				}, delay );
			}
		}

		var next = hero.querySelector( '.efb-hero__next' );
		var prev = hero.querySelector( '.efb-hero__prev' );
		if ( next ) {
			next.addEventListener( 'click', function () {
				go( i + 1 );
				start();
			} );
		}
		if ( prev ) {
			prev.addEventListener( 'click', function () {
				go( i - 1 );
				start();
			} );
		}
		dots.forEach( function ( d, k ) {
			d.addEventListener( 'click', function () {
				go( k );
				start();
			} );
		} );

		var x0 = null;
		hero.addEventListener( 'touchstart', function ( e ) {
			x0 = e.touches[ 0 ].clientX;
		}, { passive: true } );
		hero.addEventListener( 'touchend', function ( e ) {
			if ( x0 === null ) {
				return;
			}
			var dx = e.changedTouches[ 0 ].clientX - x0;
			if ( Math.abs( dx ) > 40 ) {
				go( dx < 0 ? i + 1 : i - 1 );
				start();
			}
			x0 = null;
		} );

		hero.addEventListener( 'mouseenter', stop );
		hero.addEventListener( 'mouseleave', start );
		hero.addEventListener( 'focusin', stop );
		hero.addEventListener( 'focusout', start );
		start();
	}

	function boot() {
		document.querySelectorAll( '.efb-hero' ).forEach( init );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
