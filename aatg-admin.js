( function ( $ ) {

	/**
	 * Minimal HTML-escape for values echoed into the result span.
	 */
	function aatgEsc( text ) {
		return $( '<div/>' ).text( text == null ? '' : String( text ) ).html();
	}

	/**
	 * Write the generated values straight into the visible WordPress fields so no
	 * page refresh is needed. Covers the grid media modal (Backbone fields with
	 * data-setting), the Edit Media screen (#attachment_alt / #title), and the
	 * legacy list modal. Triggering `change` keeps the modal's model in sync.
	 */
	function aatgApplyToFields( button, newAlt, newTitle ) {
		var scope = button.closest( '.attachment-details, .media-sidebar, .media-modal, .media-frame, #poststuff, body' );
		if ( ! scope.length ) {
			scope = $( document );
		}

		if ( typeof newAlt !== 'undefined' && newAlt !== null ) {
			var altSel   = 'textarea[data-setting="alt"], input[data-setting="alt"], #attachment_alt';
			var altField = scope.find( altSel );
			if ( ! altField.length ) {
				altField = $( altSel );
			}
			altField.val( newAlt ).trigger( 'change' );
		}

		if ( newTitle ) {
			var titleSel   = 'input[data-setting="title"], #title';
			var titleField = scope.find( titleSel );
			if ( ! titleField.length ) {
				titleField = $( titleSel );
			}
			titleField.val( newTitle ).trigger( 'change' );
		}
	}

	$( document ).on( 'click', '.aatg-generate-alt', function () {
		var button       = $( this );
		var attachmentId = button.data( 'attachment-id' );
		var original     = 'Generate Alt Text & Title';

		button.prop( 'disabled', true ).text( 'Generating…' );

		$.ajax( {
			url: aatg_ajax.ajax_url,
			method: 'POST',
			data: {
				action: 'aatg_generate_alt_text_ajax',
				attachment_id: attachmentId,
				nonce: aatg_ajax.nonce
			},
			success: function ( response ) {
				if ( response && response.success ) {
					var newAlt   = response.data.alt_text;
					var newTitle = response.data.image_title;
					var warning  = response.data.warning;

					// Update the live WordPress fields (no refresh required).
					aatgApplyToFields( button, newAlt, newTitle );

					var message = 'Alt text updated: <em>' + aatgEsc( newAlt ) + '</em>';
					if ( newTitle ) {
						message += '<br />Title updated: <em>' + aatgEsc( newTitle ) + '</em>';
					}
					if ( warning ) {
						message += '<br /><strong>Note:</strong> ' + aatgEsc( warning );
					}
					button.siblings( '.aatg-result' ).html( message );
					if ( newTitle ) {
						button.siblings( '.aatg-title-result' ).text( newTitle );
					}
				} else {
					var errorMessage = ( response && response.data ) ? response.data : 'Unknown error.';
					button.siblings( '.aatg-result' ).html( '<strong>Error:</strong> ' + aatgEsc( errorMessage ) );
					if ( window.console ) { console.log( response ); }
				}
				button.prop( 'disabled', false ).text( original );
			},
			error: function ( xhr, status, error ) {
				button.prop( 'disabled', false ).text( original );
				button.siblings( '.aatg-result' ).html( '<strong>Error:</strong> ' + aatgEsc( error || 'Request failed.' ) );
				if ( window.console ) { console.log( error ); }
			}
		} );
	} );

} )( jQuery );
