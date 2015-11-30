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
	 * postmeta meta_key that indicates extra payment methods to include for certain events
	 */
	const include_payment_method_postmeta_name = 'include_payment_method';
	
	/**
	 * postmeta which should be hidden, which mentions which event-specific payment
	 * methods to include
	 */
	const include_payment_method_postmeta_name_private = '_include_payment_methods';
	
	const specific_events_scope = 'SPECIFIC_EVENTS';


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
		 add_filter( 'FHEE__EEM_Payment_Method__get_all_for_transaction__payment_methods', array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'show_specific_payment_methods_for_events' ), 10, 3 );
		 EED_Payment_Methods_Pro_Event_Payment_Method::set_hooks_both();
	 }
	 
	 public static function set_hooks_both() {
		 add_filter( 
			'FHEE__EEM_Payment_Method__scopes', 
			array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'add_other_scope' ) );
	 }
	 
	
	



	/**
	 * Gets all payment methods that we normally would, PLUS ones that are indicated
	 * as ok on the events' postmeta named "include_payment_method"
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
		 $payment_method_names = array();
		 foreach( $event_ids_for_this_event as $event_id ){
			 $old_postmeta = get_post_meta( $event_id, self::include_payment_method_postmeta_name );
			 $new_postmeta = get_post_meta( $event_id, self::include_payment_method_postmeta_name_private, true );
			 $payment_method_names = array_merge( 
					 $payment_method_names, 
					 $old_postmeta, 
					 is_array( $new_postmeta ) ? $new_postmeta : array() );
		}
		//if no event-specific payment method were found, just return the original list of payment methods
		if( empty( $payment_method_names ) ) {
			return $payment_methods;
		}
		$query_params_for_all_active = EEM_Payment_Method::instance()->get_query_params_for_all_active( $scope );
		 return EEM_Payment_Method::instance()->get_all( array(
			 array(
				 'OR' => array(
					 'AND*normal' => $query_params_for_all_active[ 0 ],
					 'AND*indicated_by_postmeta_admin_names' => array( 'PMD_admin_name' => array( 'IN', $payment_method_names ) ),
					 'AND*indicated_by_postmeta_frontend_names' => array( 'PMD_name' => array( 'IN', $payment_method_names ) ),
					 'AND*indicated_by_postmeta_slugs' => array( 'PMD_slug' => array( 'IN', $payment_method_names ) ),
					 'AND*indicated_by_postmeta_IDs' => array( 'PMD_ID' => array( 'IN', $payment_method_names ) ),
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
		 $scopes[ self::specific_events_scope ] = __( 'Only Specific Events', 'event_espresso' );
		 return $scopes;
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




	/**
	 *		@ override magic methods
	 *		@ return void
	 */
	public function __set($a,$b) { return FALSE; }
	public function __get($a) { return FALSE; }
	public function __isset($a) { return FALSE; }
	public function __unset($a) { return FALSE; }
	public function __clone() { return FALSE; }
	public function __wakeup() { return FALSE; }
	public function __destruct() { return FALSE; }

 }
// End of file EED_Payment_Methods_Pro.module.php
// Location: /wp-content/plugins/eea-payment-methods-pro/EED_Payment_Methods_Pro.module.php
