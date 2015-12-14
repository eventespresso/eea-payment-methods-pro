/**
 * Prevent users from accidentally deleting a payment method.
 * Requires that wp_localize_script be called, creating an object 
 * called ee_pmp_i18n with a property delete_pm_confirm
 */
jQuery(document).ready(function($) {
	jQuery( '.delete-payment-method' ).click( function() {
		return confirm( ee_pmp_i18n.delete_pm_confirm );
	})
});