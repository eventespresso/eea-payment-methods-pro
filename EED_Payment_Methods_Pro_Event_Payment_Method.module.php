<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/*
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		$VID:$
 *
 * ------------------------------------------------------------------------
 */
/**
 * Class  EED_Payment_Methods_Pro
 *
 * @package			Event Espresso
 * @subpackage		eea-payment-methods-pro
 * @author 				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_Payment_Methods_Pro_Event_Payment_Method extends EED_Module {

	/**
	 * postmeta meta_key that indicates extra payment methods to include for certain events.
	 * Now deprecated because we have a metabox for setting the payment methods on the event,
	 * instead of using teh wp custom fields area
	 * @deprecated since version 1.0.0
	 */
	const include_payment_method_postmeta_name_deprecated = 'include_payment_method';
	
	/**
	 * @return EED_Payment_Methods_Pro_Event_Payment_Method
	 */
	public static function instance() {
		return parent::get_instance( __CLASS__ );
	}



	/**
	 * 	set_hooks - for hooking into EE Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
		add_filter( 'FHEE__EEM_Payment_Method__get_all_for_transaction__payment_methods', array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'show_specific_payment_methods_for_events' ), 10, 3 );
		EED_Payment_Methods_Pro_Event_Payment_Method::set_hooks_both();
	}

	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {
		add_filter( 
			'FHEE__EEM_Payment_Method__get_all_for_transaction__payment_methods', 
			array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'show_specific_payment_methods_for_events' ), 
			10, 
			3 
		);
		EED_Payment_Methods_Pro_Event_Payment_Method::set_hooks_both();
	}

	public static function set_hooks_both() {
		add_action( 
			'AHEE__EE_Admin_Page_Loader___get_installed_pages_loaded',
			array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'remove_no_payment_method_notification' ),
			10,
			1
		);
	}
	 
	
	



	/**
	 * Gets all payment methods that we normally would, PLUS ones that are specifically related
	 * to the events on this transaction
	 * @param $payment_methods
	 * @param EE_Transaction $transaction
	 * @param string $scope
	 * @return \EE_Payment_Method[]
	 * @throws \EE_Error
	 * @internal param $EE_Payment_Method []
	 */
	public static function show_specific_payment_methods_for_events( $payment_methods, $transaction, $scope ) {
		//we will want to INCLUDE certain specific gateways
		//based on a list we acquire
		//from the transaction's event's postmeta for 'include_payment_method'
		if( ! $transaction instanceof EE_Transaction ) {
			if( WP_DEBUG ) {
				throw new EE_Error( sprintf( __( 'EED_Payment_Methods_Pro_Event_Payment_Method requires an EE_Transaction be pased in, there wasnt any.', 'event_espresso' )));
			}else{
				//meuh, forget about it. We don't have the info we need, but we don't want to blow up either
				//so just return what we would have before
				return $payment_methods;
			}
		}
		//we do NOT support Multi Event Registration, so its ok to assume a transaction is only for one event
		$event_id = EEM_Event::instance()->get_var( 
			array( 
				array( 'Registration.TXN_ID' => $transaction->ID() ),
				'limit' => 1
			) 
		);
		//use method from EEME_Payment_Methods_Pro_Payment_Method to get available payment methods
		return EEM_Payment_Method::instance()->get_payment_methods_available_for_event(  $event_id );
	}
	 
	/**
	 * Removes the default warning about there being no active payment methods
	 * @param Payments_Admin_Page_Init $admin_page_init_objects
	 */
	public static function remove_no_payment_method_notification( $admin_page_init_objects ) {
		if( isset( $admin_page_init_objects[ 'payments' ] ) 
			 && $admin_page_init_objects[ 'payments' ] instanceof Payments_Admin_Page_Init 
		) {
			remove_filter( 
				'admin_notices',
				array( 
					$admin_page_init_objects[ 'payments' ],
					'check_payment_gateway_setup'
				) 
			);
		}
	}













	/**
	 *    run - initial module setup
	 *
	 * @access    public
	 * @param  WP $WP
	 * @return    void
	 */
	public function run( $WP ) {
	}
 }
// End of file EED_Payment_Methods_Pro.module.php
// Location: /wp-content/plugins/eea-payment-methods-pro/EED_Payment_Methods_Pro.module.php
