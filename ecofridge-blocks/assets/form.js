/**
 * Contact form block: AJAX submit with inline validation messages, and the
 * GA4 recommended form events in the dataLayer (form_start, form_submit,
 * generate_lead) for Google Tag Manager.
 */
( function () {
	'use strict';

	// GA4 form event in the dataLayer. user_data (email, phone) is only sent
	// with generate_lead, for Google Ads enhanced conversions; Google tags use
	// it only when the visitor allowed marketing (Consent Mode ad_user_data).
	function track( form, event, extra ) {
		window.dataLayer = window.dataLayer || [];
		var idField = form.querySelector( 'input[name="_efb_form"]' );
		var data = {
			event: event,
			form_id: ( idField && idField.value ) || 'contact-form',
			form_name: document.title.split( /\s[|–-]\s/ )[ 0 ],
			form_destination: window.location.pathname,
		};
		Object.keys( extra || {} ).forEach( function ( k ) {
			data[ k ] = extra[ k ];
		} );
		window.dataLayer.push( data );
	}

	function init( form ) {
		var status = form.querySelector( '.efb-form__status' );
		var startedTracking = false;
		form.addEventListener( 'focusin', function () {
			if ( ! startedTracking ) {
				startedTracking = true;
				track( form, 'form_start' );
			}
		} );
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
			var emailField = form.querySelector( 'input[type="email"]' );
			var phoneField = form.querySelector( 'input[type="tel"]' );
			var userData = {};
			if ( emailField && emailField.value ) {
				userData.email = emailField.value.trim().toLowerCase();
			}
			if ( phoneField && phoneField.value ) {
				userData.phone_number = phoneField.value.replace( /[^\d+]/g, '' );
			}
			track( form, 'form_submit' );
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
						track( form, 'generate_lead', { lead_source: 'contact_form', user_data: userData } );
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
