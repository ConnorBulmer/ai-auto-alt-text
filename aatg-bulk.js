/* global aatg_bulk_ajax */

jQuery( function ( $ ) {

	let total     = null;
	let completed = 0;
	let running   = false;

	const DELAY = ( 'delay' in aatg_bulk_ajax )
	? parseInt( aatg_bulk_ajax.delay, 10 ) * 1000
	: 2000;   // fallback if localisation is missing

	const $btn       = $( '#aatg-bulk-start' );
	const $container = $( '#aatg-bulk-progress-container' );
	const $bar       = $( '#aatg-bulk-progress' );
	const $text      = $( '#aatg-bulk-progress-text' );
	const $log       = $( '#aatg-bulk-log' );
	const $logList   = $( '#aatg-bulk-log-entries' );
	const $logTitle  = $( '#aatg-bulk-log-summary' );
	let logCount     = 0;

	/* ------------------------------------------------------------------ */
	/*  Start button                                                      */
	/* ------------------------------------------------------------------ */
	$btn.on( 'click', function () {
		if ( running ) { return; }
		running = true;
		$btn.prop( 'disabled', true );     // disable while running

		completed = 0;
		total     = null;
		logCount  = 0;

		$( '#aatg-bulk-status' ).hide();
		$log.hide();
		$logList.empty();
		updateLogSummary();
		$container.show();
		$bar.val( 0 );
		$text.text( 'Initialising…' );

		processBatch();
	} );

	/* ------------------------------------------------------------------ */
	/*  Recursive batch processor                                         */
	/* ------------------------------------------------------------------ */
	function processBatch() {
		$.post( aatg_bulk_ajax.ajax_url, {
			action : 'aatg_bulk_update',
			nonce  : aatg_bulk_ajax.nonce
		} )
		.done( function ( res ) {

			if ( ! res.success ) {
				handleError( res.data );
				return;
			}

			const processed = res.data.processed;
			const remaining = res.data.remaining;
			const issues = Array.isArray( res.data.issues ) ? res.data.issues : [];

			if ( total === null ) {
				total = processed + remaining;
				$bar.attr( 'max', total );
			}

			if ( issues.length ) {
				appendIssues( issues );
			}

			completed += processed;
			$bar.val( completed );
			$text.text( `Optimised ${ completed } of ${ total } images…` );

			if ( remaining > 0 ) {
				setTimeout( processBatch, DELAY );
			} else {
				$text.text( `Bulk update complete — ${ total } images optimised.` );
				resetButton();
			}
		} )
		.fail( function ( _jqXHR, _status, err ) {
			handleError( err );
		} );
	}

	/* ------------------------------------------------------------------ */
	/*  Helpers                                                           */
	/* ------------------------------------------------------------------ */
	function handleError( msg ) {
		$text.text( 'Error: ' + msg );
		appendIssues( [ {
			attachment_id: null,
			type: 'error',
			message: msg
		} ] );
		resetButton();
	}

	function resetButton() {
		running = false;
		$btn.prop( 'disabled', false );
	}

	function appendIssues( issues ) {
		issues.forEach( ( issue ) => {
			const idText = issue.attachment_id ? `#${ issue.attachment_id }` : 'Batch';
			const label = issue.type === 'warning' ? 'Warning' : 'Error';
			const entry = $( '<div />' ).html(
				`<strong>${ label } ${ idText }:</strong> ${ issue.message }`
			);
			$logList.append( entry );
			logCount += 1;
		} );

		$log.show();
		updateLogSummary();
	}

	function updateLogSummary() {
		$logTitle.text( `Bulk update log (${ logCount } item${ logCount === 1 ? '' : 's' })` );
	}
} );
