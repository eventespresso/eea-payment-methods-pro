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
	 * @return EED_Payment_Methods_Pro
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
		 add_filter( 'FHEE__EEM_Payment_Method__get_all_for_transaction__override', array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'show_specific_payment_methods_for_events' ), 10, 3 );
	 }

	 /**
	  * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	  *
	  *  @access 	public
	  *  @return 	void
	  */
	 public static function set_hooks_admin() {
		 add_filter( 'FHEE__EEM_Payment_Method__get_all_for_transaction__override', array( 'EED_Payment_Methods_Pro_Event_Payment_Method', 'show_specific_payment_methods_for_events' ), 10, 3 );
	 }

	 /**
	  * Gets all payment methods that we normally would, PLUS ones that are indicated
	  * as ok on the events' postmetas named "include_payment_method"
	  * @param EE_Payment_Method[]
	  * @param EE_Transaction $transaction
	  * @param string $scope
	  * @return EE_Payment_Method[]
	  */
	 public static function show_specific_payment_methods_for_events( $payment_methods, $transaction, $scope ) {
		 //we will want to INCLUDE certain specific gateways
		 //based on a list we acquire
		 //from the transaction's event's postmetas for 'include_payment_method'
		 if( ! $transaction instanceof EE_Transaction ) {
			 throw new EE_Error( sprintf( __( 'We need a transaction foo!', 'event_espresso' )));
		 }
		 $event_ids_for_this_event = EEM_Event::instance()->get_col( array( array( 'Registration.TXN_ID' => $transaction->ID() ) ) );
		 //now grab each of those's postmeta with the key "include_payment_method"
		 $event_admin_names = array();
		 foreach( $event_ids_for_this_event as $event_id ){
			 $postmetas = get_post_meta( $event_id, self::include_payment_method_postmeta_name );
			 $event_admin_names = array_merge( $event_admin_names, $postmetas );
		}
		$query_params_for_all_active = EEM_Payment_Method::instance()->get_query_params_for_all_active( $scope );
		return EEM_Payment_Method::instance()->get_all( array( array( 'OR' => array(
			'AND*normal' => $query_params_for_all_active[0],
			'AND*indicated_by_postmeta' => array( 'PMD_admin_name' => array( 'IN', $event_admin_names ) )
		))));
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
