/* global aatg_bulk_ajax */

jQuery( function ( $ ) {

	let total       = null;
	let offset      = 0;
	let scanned     = 0;
	let regenerated = 0;
	let running     = false;

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

		total       = null;
		offset      = 0;
		scanned     = 0;
		regenerated = 0;
		logCount    = 0;

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
			nonce  : aatg_bulk_ajax.nonce,
			offset : offset
		} )
		.done( function ( res ) {

			if ( ! res.success ) {
				handleError( res.data );
				return;
			}

			const data   = res.data || {};
			const issues = Array.isArray( data.issues ) ? data.issues : [];

			if ( total === null ) {
				total = parseInt( data.total, 10 ) || 0;
				$bar.attr( 'max', total > 0 ? total : 1 );
			}

			if ( issues.length ) {
				appendIssues( issues );
			}

			const batchScanned = parseInt( data.scanned, 10 ) || 0;
			offset       = parseInt( data.next_offset, 10 ) || ( offset + batchScanned );
			scanned      = offset;
			regenerated += parseInt( data.processed, 10 ) || 0;

			$bar.val( scanned );
			$text.text( `Scanned ${ scanned } of ${ total } images · regenerated ${ regenerated }` );

			const remaining = parseInt( data.remaining, 10 ) || 0;

			if ( remaining > 0 && batchScanned > 0 ) {
				setTimeout( processBatch, DELAY );
			} else {
				$text.text( `Bulk update complete — scanned ${ total } images, regenerated ${ regenerated }.` );
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
