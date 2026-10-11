/**
 * Contact form block: AJAX submit with inline validation messages.
 */
( function () {
	'use strict';

	function init( form ) {
		var status = form.querySelector( '.efb-form__status' );
		var button = form.querySelector( '.efb-form__submit' );
		var started = Date.now();
		var elapsed = form.querySelector( 'input[name="_efb_elapsed"]' );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			form.querySelectorAll( '.efb-form__error' ).forEach( function ( n ) {
				n.remove();
			} );
			form.querySelectorAll( '[aria-invalid]' ).forEach( function ( n ) {
				n.removeAttribute( 'aria-invalid' );
			} );
			if ( ! form.checkValidity() ) {
				form.reportValidity();
				return;
			}
			if ( elapsed ) {
				elapsed.value = Math.round( ( Date.now() - started ) / 1000 );
			}
			button.disabled = true;
			form.classList.add( 'is-sending' );
			status.className = 'efb-form__status';
			status.textContent = '';

			fetch( form.getAttribute( 'action' ), {
				method: 'POST',
				body: new FormData( form ),
				headers: { Accept: 'application/json' },
			} )
				.then( function ( r ) {
					return r.json().then( function ( d ) {
						return { ok: r.ok, data: d };
					} );
				} )
				.then( function ( res ) {
					if ( res.ok && res.data.ok ) {
						form.reset();
						form.classList.add( 'is-sent' );
						status.className = 'efb-form__status is-success';
						status.textContent = res.data.message;
						return;
					}
					var d = res.data || {};
					var fields = ( d.data && d.data.fields ) || {};
					Object.keys( fields ).forEach( function ( name ) {
						var input = form.querySelector( '[name="' + name + '"]' );
						if ( input ) {
							input.setAttribute( 'aria-invalid', 'true' );
							var msg = document.createElement( 'span' );
							msg.className = 'efb-form__error';
							msg.textContent = fields[ name ];
							input.insertAdjacentElement( 'afterend', msg );
						}
					} );
					status.className = 'efb-form__status is-error';
					status.textContent = d.message || 'Something went wrong. Please call us on 01280 811411.';
				} )
				.catch( function () {
					status.className = 'efb-form__status is-error';
					status.textContent = 'Something went wrong. Please call us on 01280 811411.';
				} )
				.then( function () {
					button.disabled = false;
					form.classList.remove( 'is-sending' );
				} );
		} );
	}

	function boot() {
		document.querySelectorAll( 'form.efb-form' ).forEach( init );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
