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
	 * New scope that indicates these payment methods should appear in the payment methods
	 * metabox on the events page
	 */
	const scope_specific_events = 'SPECIFIC_EVENTS';


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
		add_action(  
			'AHEE__EE_Base_Class__save__begin', 
			array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'ensure_frontend_or_event_specific_scope' )
		);
		EED_Payment_Methods_Pro_Event_Payment_Method::set_hooks_both();
	}

	public static function set_hooks_both() {
		add_filter( 
			'FHEE__EEM_Payment_Method__scopes', 
			array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'add_other_scope' ) 
		);
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
		$event_ids_for_this_event = EEM_Event::instance()->get_col( array( array( 'Registration.TXN_ID' => $transaction->ID() ) ) );
		//now grab each of the postmeta with the key "include_payment_method"
		$event_specific_payment_method_ids = array();
		foreach( $event_ids_for_this_event as $event_id ){
			$event_specific_payment_method_ids = array_merge( 
					$event_specific_payment_method_ids, 
					EED_Payment_Methods_Pro_Event_Payment_Method::get_payment_methods_for_event( $event_id ) );
		}
		//if no event-specific payment method were found, just return the original list of payment methods
		if( empty( $event_specific_payment_method_ids ) ) {
			return $payment_methods;
		}
		$query_params_for_all_active = EEM_Payment_Method::instance()->get_query_params_for_all_active( $scope );
		return EEM_Payment_Method::instance()->get_all( array(
			array(
				'OR' => array(
					'AND*normal' => $query_params_for_all_active[ 0 ],
					'AND*indicated_by_postmeta_IDs' => array( 'PMD_ID' => array( 'IN', $event_specific_payment_method_ids ) ),
				)
			)
		) );
	}
	 
	/**
	 * Adds another scope which is handy for payment methods that are only for specific events
	 * @param array $scopes
	 * @return array
	 */
	public static function add_other_scope( $scopes ) {
		$scopes[ self::scope_specific_events ] = __( 'Only Specific Events', 'event_espresso' );
		return $scopes;
	}
	 
	/**
	 * Gets the IDs of teh paymetn methods which are specific to this event
	 * @param string $event_id
	 * @return array of payment method IDs
	 */
	public static function get_payment_methods_for_event( $event_id ) {
		$event_specific_pms = get_post_meta( $event_id, EED_Payment_Methods_Pro_Event_Payment_Method::include_payment_method_postmeta_name_deprecated, false );
		if( empty( $event_specific_pms ) ) {
			$event_specific_pms = EEM_Payment_Method::instance()->get_col( array( array( 'Event.EVT_ID' => $event_id ) ) );
		} else { 
			//ok so we got the old postmeta which had who knows what in it. Swithc it to IDs
			$event_specific_pms = EEM_Payment_Method::instance()->get_col( 
					array(
						array(
							'OR' => array(
								'AND*indicated_by_postmeta_admin_names' => array( 'PMD_admin_name' => array( 'IN', $event_specific_pms ) ),
								'AND*indicated_by_postmeta_frontend_names' => array( 'PMD_name' => array( 'IN', $event_specific_pms ) ),
								'AND*indicated_by_postmeta_slugs' => array( 'PMD_slug' => array( 'IN', $event_specific_pms ) ),
								'AND*indicated_by_postmeta_IDs' => array( 'PMD_ID' => array( 'IN', $event_specific_pms ) ),
							)
						)
					),
					'PMD_ID' 
				);
			delete_post_meta( $event_id, EED_Payment_Methods_Pro_Event_Payment_Method::include_payment_method_postmeta_name_deprecated );
			$event = EEM_Event::instance()->get_one_by_ID( $event_id );
			//use method from EEE_Payment_Methods_Pro_Event to add relation to all specified events
			$event->set_related_payment_methods( $event_specific_pms );
			//and make sure the payment method is scoped properly (before 
			foreach( $event_specific_pms as $payment_method_id ) {
				$pm = EEM_Payment_Method::instance()->get_one_by_ID( $payment_method_id );
				$scopes = $pm->scope();
				$scopes[] = EED_Payment_Methods_Pro_Event_Payment_Method::scope_specific_events;
				$pm->set_scope( $scopes );
				$pm->save();
			}
		}
		return $event_specific_pms;
	}
	 
	/**
	 * Just before a payment method is saved, verify they haven't set
	 * the scope to both cart AND event-specific
	 * @param EE_Payment_Method $pm
	 * @return void
	 */
	public static function ensure_frontend_or_event_specific_scope( $pm ) {
		if( $pm instanceof EE_Payment_Method
			&& in_array( EEM_Payment_Method::scope_cart, $pm->scope() )
			&& in_array( EED_Payment_Methods_Pro_Event_Payment_Method::scope_specific_events, $pm->scope() ) ) {
			$new_scope = $pm->scope();
			$index_of_event_scope = array_search( EED_Payment_Methods_Pro_Event_Payment_Method::scope_specific_events, $new_scope );
			//we know we'll find it because we just asserted it was in_array
			unset( $new_scope[ $index_of_event_scope ] );
			//no need to save because we hooked into JUST before the save
			$pm->set_scope( $new_scope );
		}
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
