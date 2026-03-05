/* global woocommerce_xero_settings_params */
( function ( $ ) {
	$( function () {
		// Edit prompt - warn user if they try to navigate away with unsaved changes
		function editPrompt() {
			var changed = false;
			var $prevent_change_elements = $( '.wp-list-table .check-column, .wc-settings-prevent-change-event' );

			$( '#mainform input, #mainform textarea, #mainform select' ).on( 'change input', function ( event ) {
				// Prevent change event on specific elements, that don't change the form. E.g.:
				// - WP List Table checkboxes that only (un)select rows
				// - Changing email type in email preview
				if (
					$prevent_change_elements.length &&
					$prevent_change_elements.has( event.target ).length
				) {
					return;
				}

				if ( ! changed ) {
					window.onbeforeunload = function () {
						return woocommerce_xero_settings_params.i18n_nav_warning;
					};
					changed = true;
				}
			} );

			$( '.submit :input, input#search-submit' ).on( 'click', function () {
				window.onbeforeunload = '';
			} );
		}

		$( editPrompt );
	} );
} )( jQuery );

