<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EEE_Mock_Attendee extends EE_Attendee
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEE_Payment_Methods_Pro_Event extends EEE_Base_Class{

	public function __construct(){
		$this->_model_name_extended = 'Event';
		parent::__construct();
	}

	/**
	 * Sets these payment method IDs to be the only paymen tmethods related to this event
	 * (ie overwrites previous relations)
	 * @param array $payment_method_ids IDs of related payment methods
	 * @return boolean
	 */
	function ext_set_payment_methods_available_on_event( $payment_method_ids ){
		//In the DB, we store a row in the extra join
		//table for each "availability exception", meaning if a payment method is
		//normally available by default, then we store a row to remember its an exception,and
		//so it should NOT be available on this event; likewise, if the payment method
		//is normally NOT available by default on all events, then we store a row
		//in the join table to show its an exception, meaning it is available
		//for this specific event.
		//In order to do this, we need to remember which payment methods are available by default
		$payment_method_availabilities = EEM_Payment_Method::instance()->get_payment_method_default_availabilities( 
			EEM_Payment_Method::instance()->get_query_params_for_all_active( 
				EEM_Payment_Method::scope_cart 
			)
		);
		$availability_exceptions = array();
		foreach( $payment_method_availabilities as $PMD_ID => $available_by_default ) {
			if( ( $available_by_default 
					&& ! in_array( $PMD_ID, $payment_method_ids )
				) 
				||
				( ! $available_by_default
					&& in_array( $PMD_ID, $payment_method_ids )
					)
			) {
				$availability_exceptions[] = $PMD_ID;
			}
		}
		EEM_Extra_Join::instance()->delete( 
			array(
				array(
					'EXJ_first_model_ID' => $this->_->ID(),
					'EXJ_first_model_name' => 'Event',
					'EXJ_second_model_name' => 'Payment_Method'
				)
			)
		);
		$result = true;
		foreach( $availability_exceptions as $payment_method_id ) {
			$result = $this->_->_add_relation_to( $payment_method_id, 'Payment_Method' );
		}
		return $result;
	}
}

// End of file EEE_Mock_Attendee.php