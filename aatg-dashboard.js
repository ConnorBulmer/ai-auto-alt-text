document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.querySelector( '.aatg-dashboard' );
	if ( ! root ) {
		return;
	}

	const tabs = Array.from( root.querySelectorAll( '[data-aatg-tab]' ) );
	const panels = Array.from( root.querySelectorAll( '[data-aatg-panel]' ) );
	const defaultTab = root.dataset.defaultTab || 'settings';
	const noticeContainer = root.querySelector( '.aatg-notices' );

	if ( noticeContainer ) {
		const notices = Array.from( root.querySelectorAll( '.notice' ) );
		notices.forEach( ( notice ) => {
			noticeContainer.appendChild( notice );
		} );
	}

	const activateTab = ( tabName ) => {
		tabs.forEach( ( tab ) => {
			tab.classList.toggle( 'is-active', tab.dataset.aatgTab === tabName );
		} );

		panels.forEach( ( panel ) => {
			panel.classList.toggle( 'is-active', panel.dataset.aatgPanel === tabName );
		} );
	};

	const initialTab = window.location.hash.replace( '#', '' ) || defaultTab;
	activateTab( initialTab );

	tabs.forEach( ( tab ) => {
		tab.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			const tabName = tab.dataset.aatgTab;
			activateTab( tabName );
			if ( tabName ) {
				history.replaceState( null, '', `#${ tabName }` );
			}
		} );
	} );
} );
