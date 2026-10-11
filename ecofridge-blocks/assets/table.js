/**
 * Spec table: click/Enter on a header to sort the column (numeric-aware).
 */
( function () {
	'use strict';

	function value( cell ) {
		var t = cell.textContent.trim();
		var num = t.replace( /[£,\s]/g, '' ).match( /^[-+]?\d+(\.\d+)?/ );
		return num ? parseFloat( num[ 0 ] ) : t.toLowerCase();
	}

	function init( wrap ) {
		var table = wrap.querySelector( 'table' );
		var body = table && table.tBodies[ 0 ];
		if ( ! body || body.rows.length < 2 ) {
			return;
		}
		table.querySelectorAll( 'thead th' ).forEach( function ( th, col ) {
			th.setAttribute( 'tabindex', '0' );
			th.setAttribute( 'aria-sort', 'none' );
			function sort() {
				var dir = th.getAttribute( 'aria-sort' ) === 'ascending' ? -1 : 1;
				table.querySelectorAll( 'thead th' ).forEach( function ( o ) {
					o.setAttribute( 'aria-sort', 'none' );
				} );
				th.setAttribute( 'aria-sort', dir === 1 ? 'ascending' : 'descending' );
				var rows = Array.prototype.slice.call( body.rows );
				rows.sort( function ( a, b ) {
					var va = a.cells[ col ] ? value( a.cells[ col ] ) : '';
					var vb = b.cells[ col ] ? value( b.cells[ col ] ) : '';
					if ( typeof va === 'number' && typeof vb === 'number' ) {
						return ( va - vb ) * dir;
					}
					return String( va ).localeCompare( String( vb ) ) * dir;
				} );
				rows.forEach( function ( r ) {
					body.appendChild( r );
				} );
			}
			th.addEventListener( 'click', sort );
			th.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					sort();
				}
			} );
		} );
	}

	function boot() {
		document.querySelectorAll( '.efb-table.is-sortable' ).forEach( init );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
