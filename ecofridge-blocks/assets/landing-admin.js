/* global jQuery, efbLandingAdmin */
( function ( $ ) {
	'use strict';

	var i18n = efbLandingAdmin.i18n;

	// Section order.
	$( '#efbl-order' ).sortable( { handle: '.efbl-handle', axis: 'y' } );

	// Repeaters (FAQ, features): add, move, remove rows.
	function repeater( list, template, addButton ) {
		var $list = $( list );
		$( addButton ).on( 'click', function () {
			var row = $( $( template ).html() );
			$list.append( row );
			row.find( 'input[type="text"]' ).first().trigger( 'focus' );
		} );
		$list.on( 'click', '[data-efbl-remove]', function () {
			$( this ).closest( '.efbl-faq-item' ).remove();
		} );
		$list.on( 'click', '[data-efbl-move]', function () {
			var item = $( this ).closest( '.efbl-faq-item' );
			if ( $( this ).data( 'efbl-move' ) < 0 ) {
				item.prev().before( item );
			} else {
				item.next().after( item );
			}
		} );
	}
	repeater( '#efbl-faq', '#efbl-faq-tpl', '#efbl-faq-add' );
	repeater( '#efbl-features', '#efbl-features-tpl', '#efbl-features-add' );

	// Related articles and hand-picked spare parts: AJAX search boxes.
	function searchBox( selector, action, placeholder ) {
		$( selector ).selectWoo( {
			placeholder: placeholder,
			minimumInputLength: 2,
			ajax: {
				url: efbLandingAdmin.ajax,
				dataType: 'json',
				delay: 250,
				data: function ( params ) {
					return { action: action, nonce: efbLandingAdmin.nonce, q: params.term };
				},
				processResults: function ( data ) {
					return { results: data.results || [] };
				},
			},
		} );
	}
	searchBox( '#efbl-articles', 'efb_landing_search_posts', i18n.search );
	searchBox( '#efbl-parts-manual', 'efb_landing_search_parts', i18n.searchParts );

	// Comparison table editor. State lives in a hidden JSON input: { head: [], rows: [[]] }.
	var $json = $( '#efbl-table-json' );
	var $editor = $( '#efbl-table-editor' );
	var table;

	try {
		table = JSON.parse( $json.val() );
	} catch ( e ) {
		table = null;
	}
	if ( ! table || ! table.head || ! table.head.length ) {
		table = { head: [ '', '', '' ], rows: [ [ '', '', '' ] ] };
	}

	function sync() {
		$json.val( JSON.stringify( table ) );
	}

	function cellInput( value, row, col ) {
		return $( '<input type="text">' ).val( value ).attr( { 'data-row': row, 'data-col': col } );
	}

	function iconButton( icon, label, action, index ) {
		return $( '<button type="button" class="button-link">' )
			.attr( { 'aria-label': label, title: label, 'data-action': action, 'data-index': index } )
			.append( $( '<span class="dashicons">' ).addClass( 'dashicons-' + icon ) );
	}

	function render() {
		var $table = $( '<table class="efbl-table-edit">' );
		var $tools = $( '<tr class="efbl-col-tools">' );
		var $head = $( '<tr class="efbl-head-row">' );

		table.head.forEach( function ( value, c ) {
			$tools.append( $( '<td>' ).append( c > 0 ? iconButton( 'no-alt', i18n.remove, 'del-col', c ) : '' ) );
			$head.append( $( '<th>' ).append( cellInput( value, -1, c ) ) );
		} );
		$tools.append( '<td>' );
		$head.append( '<td>' );
		$table.append( $tools, $head );

		table.rows.forEach( function ( row, r ) {
			var $tr = $( '<tr>' );
			row.forEach( function ( value, c ) {
				$tr.append( $( '<td>' ).append( cellInput( value, r, c ) ) );
			} );
			$tr.append( $( '<td class="efbl-row-tools">' ).append( iconButton( 'no-alt', i18n.remove, 'del-row', r ) ) );
			$table.append( $tr );
		} );

		var $actions = $( '<p class="efbl-table-actions">' ).append(
			$( '<button type="button" class="button">' ).text( i18n.addRow ).attr( 'data-action', 'add-row' ),
			' ',
			$( '<button type="button" class="button">' ).text( i18n.addCol ).attr( 'data-action', 'add-col' ),
			' ',
			$( '<button type="button" class="button-link button-link-delete">' ).text( i18n.clear ).attr( 'data-action', 'clear' )
		);

		$editor.empty().append( $( '<div class="efbl-table-scroll">' ).append( $table ), $actions );
		sync();
	}

	$editor.on( 'input', 'input[data-row]', function () {
		var r = +this.getAttribute( 'data-row' );
		var c = +this.getAttribute( 'data-col' );
		if ( r < 0 ) {
			table.head[ c ] = this.value;
		} else {
			table.rows[ r ][ c ] = this.value;
		}
		sync();
	} );

	$editor.on( 'click', '[data-action]', function () {
		var index = +this.getAttribute( 'data-index' );
		switch ( this.getAttribute( 'data-action' ) ) {
			case 'add-row':
				table.rows.push( table.head.map( function () {
					return '';
				} ) );
				break;
			case 'add-col':
				table.head.push( '' );
				table.rows.forEach( function ( row ) {
					row.push( '' );
				} );
				break;
			case 'del-row':
				table.rows.splice( index, 1 );
				if ( ! table.rows.length ) {
					table.rows.push( table.head.map( function () {
						return '';
					} ) );
				}
				break;
			case 'del-col':
				table.head.splice( index, 1 );
				table.rows.forEach( function ( row ) {
					row.splice( index, 1 );
				} );
				break;
			case 'clear':
				if ( ! window.confirm( i18n.confirmClear ) ) { // eslint-disable-line no-alert
					return;
				}
				table = { head: [ '', '', '' ], rows: [ [ '', '', '' ] ] };
				break;
		}
		render();
	} );

	render();
} )( jQuery );
