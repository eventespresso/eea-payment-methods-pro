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
 * @ version		1.0.0.rc.004
 *
 * ------------------------------------------------------------------------
 */
/**
 * Class  EED_Payment_Methods_Pro
 * This class adds hooks for filtering which payment methods are available
 * when registering for an event.
 *
 * @package			Event Espresso
 * @subpackage		eea-payment-methods-pro
 * @author 				Mike Nelson
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
		EED_Payment_Methods_Pro_Event_Payment_Method::set_hooks_both();
	}

	public static function set_hooks_both() {
		//filter what payment methods are available for certain events
		add_filter( 
			'FHEE__EEM_Payment_Method__get_all_for_transaction__payment_methods', 
			array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'show_specific_payment_methods_for_events' ), 
			10, 
			3 
		);
		//make sure that, regardless of how an admin goofs up, there is always at least
		//one payment method available when registering for an event
		add_filter( 
			'FHEE__EEME_Payment_Methods_Pro_Payment_Method__ext_get_payment_methods_available_for_event',
			array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'ensure_event_have_at_least_one_payment_method' ),
			10,
			2
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
	 * Callback for when we fetch the payment methods on an event. If none are available, 
	 * we activate invoice and make sure its available.
	 * @param EE_Payment_Method[] $payment_methods
	 * @param int $event_id
	 */
	public static function ensure_event_have_at_least_one_payment_method( $payment_methods, $event_id ) {
		if( 
				empty( $payment_methods )
		) {
			$event_obj = EEM_Event::instance()->get_one_by_ID( $event_id );
			if( ! $event_obj instanceof EE_Event ) {
				return $payment_methods;
			}
			$invoice_payment_method = EEM_Payment_Method::instance()->get_one(
				EEM_Payment_Method::instance()->get_query_params_for_all_active(
					EEM_Payment_Method::scope_cart,
					array(
						array(
							'PMD_type' => 'Invoice',
						)
					)
				)
			);
			//ok did we find one already active?
			if( ! $invoice_payment_method ) {
				//there's none active currently, let's activate one
				//ok let's just activate one. By default invoice
				$manager = EE_Registry::instance()->load_lib('EE_Payment_Method_Manager');
				//ensure an  invoice payment method is active
				$invoice_payment_method = $manager->activate_a_payment_method_of_type( 'Invoice' );
				//let's not make it active for other events (we want to make the smallest change
				//but still have a paymetn method on this event
				$invoice_payment_method->set_available_by_default( false );
			}
			
			//if it's available by default, ensure we're not making an exception for it on this event
			if( $invoice_payment_method->is_available_by_default() ) {
				$deletions = EEM_Extra_Join::instance()->delete( 
					array(
						array(
							'EXJ_first_model_name' => 'Event',
							'EXJ_first_model_ID' => $event_id,
							'EXJ_second_model_name' => 'Payment_Method',
							'EXJ_second_model_ID' => $invoice_payment_method->ID(),
						)
					)
				);
			} else {
				//if it's unavailable by default, make an exception for it on this event
				$event_obj->_add_relation_to( $invoice_payment_method->ID(), 'Payment_Method' );
			}
			//let folks know we've activated invoice. 
			//see EED_Payment_Methods_Pro_Event_Payment_Method::ensure_event_have_at_least_one_payment_method()
			//which enforces that (but enforces it too late to show a message, and possibly enforces it
			//on the frontend, when we'd really rather not tell site visitors 
			//that the admin made an oupsie)
			EE_Error::add_persistent_admin_notice( 
				'no_payment_methods_on_event',
				sprintf(
					__( 'No payment methods were active for the event "%1$s" (event ID %2$s). Even if it\'s a free event, there should always be a payment method available for it. We activated the Invoice Payment Method on the event, on your behalf.', 'event_espresso' ),
					$event_obj->name(),
					$event_obj->ID()
				),
				true 
			);
			$payment_methods[ $invoice_payment_method->ID() ] = $invoice_payment_method;
		}
		return $payment_methods;
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
