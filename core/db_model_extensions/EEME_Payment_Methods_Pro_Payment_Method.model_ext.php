<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EEME_Payment_Methods_Pro_Payment_Method extends EEM_Event so it's related to payment methods
 * over the flexible extra join table
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEME_Payment_Methods_Pro_Payment_Method extends EEME_Base{
	function __construct() {
		$this->_model_name_extended = 'Payment_Method';
		
		$this->_extra_relations = array(
			'Event' => new EE_HABTM_Any_Relation()
		);
		parent::__construct();
	}
	/**
	 * Gets all payment methods which are available for use on these events, factoring
	 * in which are normally available and which aren't, and for which ones to make an exception
	 * @param int $event_id
	 * @param EE_Payment_Methods[] $payment_methods the list of default payment methods; if not provided we'll find it ourselves
	 * (so providing this only improves efficiency)
	 * @return EE_Payment_Method[]
	 */
	public function ext_get_payment_methods_available_for_event( $event_id, $payment_methods = array() ) {
		if( empty( $payment_methods ) ) {
			$payment_methods = EEM_Payment_Method::instance()->get_all_active( EEM_Payment_Method::scope_cart );
		}
		//remove payment methods which shouldn't be available by default
		foreach( $payment_methods as $key => $payment_method ) {
			if( ! $payment_method->is_available_by_default() ) {
				unset( $payment_methods[ $key ] );
			}
		}
		//now let's find all the exceptions to normal payment method availability.
		//by "exception" I mean a payment method that's normally available on all events, but
		//isn't for one of these events; or a payment method that's normally NOT available,
		//but IS for one of these events.
		$payment_method_availability_exceptions = $this->_->get_payment_method_availability_exceptions( $event_id );
		//if no payment method availability exceptions were found, just return the original list of payment methods
		if( empty( $payment_method_availability_exceptions ) ) {
			return $payment_methods;
		}
		//ok so if a paymetn method is normally available, but it's an exception, then it's now NOT available. Remove it.
		foreach( $payment_method_availability_exceptions as $payment_method_id => $on_by_default ) {
			//assume $payment_methods is indexed by primary keys, which currently it is (the only time
			//it isn't is when the model has no primary key)
			if( $on_by_default ) {
				//it's normally available, but we're making an exception. So it shouldn't be available
				unset( $payment_methods[ $payment_method_id ] );
			} else {
				//it's normally NOT available, and we're making an exception. So it SHOULD be available
				//so add it back in. This isn't actually that terribly inefficient because we 
				//already fetched it from the DB (it was in $payment_methods at the start of this method,
				//but it got removed) so we're just fetching it from the entity map, not making 
				//another trip to the DB
				$payment_methods[ $payment_method_id ] = EEM_Payment_Method::instance()->get_one_by_ID( $payment_method_id );
			}
		}
		return $payment_methods;
	}
	
	/**
	 * Gets a mapping array, where keys are payment method IDs, and values
	 * are whether or not they're available by default on all events
	 * @param array $query_params
	 * @return array keys are payment method IDs, and values are whether or not they're available
	 * by default on all events
	 */
	public function ext_get_payment_method_default_availabilities( $query_params ) {
		//make sure we're also grabbing the availability extra meta
		$query_params[0]['Extra_Meta.EXM_key'] = EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key;
		$results = $this->_->get_all_wpdb_results(
			$query_params,
			ARRAY_A,
			'Payment_Method.PMD_ID as PMD_ID, Extra_Meta.EXM_value as available_by_default'
		);
		$mapping = array();
		foreach( $results as $row ) {
			$mapping[ $row['PMD_ID'] ] = $row['available_by_default'] === '1' ? true : false;
		}
		return $mapping;
	}
	
	/**
	 * Gets the IDs of teh paymetn methods which are specific to this event
	 * @param string $event_id
	 * @return array keys are payment method IDs, keys are whether or not they should be on by default
	 */
	public function ext_get_payment_method_availability_exceptions( $event_id ) {
		//no post meta. That's fine. So let's look for rows in the extra_join table, indicating they're an exception
		//(by "exception" I mean a payment method that's normally available but isn't for this event, or
		//a payment method which normally is NOT available, but is for this event).
		$pm_exceptions = $this->_->get_all_wpdb_results( 
			array( 
				array( 
					'Event.EVT_ID' => $event_id,
					//we also want to know whether they're normally available or not
					'Extra_Meta.EXM_key' => EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key,
				),
			),
			ARRAY_A,
			'Payment_Method.PMD_ID as PMD_ID, Extra_Meta.EXM_value AS on_by_default'
		);
		$pm_ids_on_by_default = array();
		foreach( $pm_exceptions as $pm_exception ) {
			$pm_ids_on_by_default[ $pm_exception[ 'PMD_ID' ] ] = $pm_exception['on_by_default'];
		}
		return $pm_ids_on_by_default;
	}
}

// End of file EEME_Payment_Methods_Pro_Payment_Method.model_ext.php