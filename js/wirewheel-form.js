/**
 * WordPress Custom Js for the Wirewheel Connector Plugin.
 *
 * @version 1.0
 *
 * @package    WordPress
 * @subpackage Privacy
 * @output     wp-content/plugins/wirewheel-connector/js/wirewheel-form.js
 */

(function ($) {
	$( document ).ready(
		function () {

			$( '.request-types' ).find( 'input' ).change(
				function () {
					if ($( this ).is( ":checked" )) {
						showFormFields( $( this ).attr( 'name' ) );
					} else {
						hideFormFields( $( this ).attr( 'name' ) );
					}
							showFormFieldsWrapper();
				}
			);

			function showFormFieldsWrapper()
			{
				let selectedRequestTypes = getSelectedRequestTypes();
				if (selectedRequestTypes.length > 0) {
					$( '.wirewheel-form-wrapper details' ).removeClass( 'hidden' );
					$( 'input[name="requests"]' ).val( selectedRequestTypes.toString() );
				} else {
					$( '.wirewheel-form-wrapper details' ).addClass( 'hidden' );
				}
			}
			function showFormFields(requestType)
			{
				$( '.wirewheel-form-wrapper .form-element-wrapper' ).each(
					function () {
						let visibleForRequests = $( this ).data( 'visible-for' );
						visibleForRequests     = visibleForRequests.split( ',' );
						if (visibleForRequests.indexOf( requestType ) !== -1) {
							$( this ).removeClass( 'hidden' );
							if ($( this ).find( 'input' ).data( 'required' ) == 'required') {
								$( this ).find( 'input' ).attr( 'required', true );
							}
						} else {
							// if not applicable to selected request type.
							// Check for other choosen rquest type fields exists and don't hide them.
							let selectedRequests           = getSelectedRequestTypes();
							let applicableForOtherRequests = visibleForRequests.filter( x => selectedRequests.includes( x ) );
							if (applicableForOtherRequests.length == 0) {
								$( this ).addClass( 'hidden' );
								$( this ).find( 'input' ).attr( 'required', false );
							}
						}
					}
				);
			}
			function hideFormFields(requestType)
			{
				$( '.wirewheel-form-wrapper .form-element-wrapper' ).each(
					function () {
						let visibleForRequests = $( this ).data( 'visible-for' );
						visibleForRequests     = visibleForRequests.split( ',' );
						// Check for other choosen rquest type fields exists and don't hide them.
						let selectedRequests           = getSelectedRequestTypes();
						let applicableForOtherRequests = visibleForRequests.filter( x => selectedRequests.includes( x ) );
						if (visibleForRequests.indexOf( requestType ) !== -1 && applicableForOtherRequests.length == 0) {
							$( this ).addClass( 'hidden' );
							$( this ).find( 'input' ).attr( 'required', false );
						} else {
							$( this ).removeClass( 'hidden' );
							if ($( this ).find( 'input' ).data( 'required' ) == 'required') {
								$( this ).find( 'input' ).attr( 'required', true );
							}
						}
					}
				);
			}

			function getSelectedRequestTypes()
			{
				let selectedRequests = [];
				$( '.request-types' ).find( 'input' ).each(
					function (index) {
						if ($( this ).is( ':checked' )) {
							selectedRequests[index] = $( this ).attr( 'name' );
						}
					}
				);
				// return  selectedRequests.
				return selectedRequests.filter(
					function (el) {
						return el != "";
					}
				);
			}

				// Halt submission if captcha required but not selected.
				$( "form[class^=wirewheel-form-]" ).submit(
					function (e) {
						if ($( '#recaptcha-setup' ).length > 0 && ! reCaptchaFilled) {
							$( '#recaptcha-setup' ).append( '<span>Please select captcha</span>' );
							return false;
						} else {
							return true;
						}
					}
				);

			// Handling checkbox validation.
			var requiredCheckboxes = $( '.form-checkboxes :checkbox[required]' );
			requiredCheckboxes.change(
				function () {
					if (requiredCheckboxes.is( ':checked' )) {
						requiredCheckboxes.removeAttr( 'required' );
					} else {
						requiredCheckboxes.attr( 'required', 'required' );
					}
				}
			);

		}
	);
})( jQuery );

// Captcha Callbacks.
var reCaptchaFilled = false;
function recaptcha_filled()
{
	reCaptchaFilled = true;
}
function recaptcha_expired()
{
	reCaptchaFilled = false;
}
