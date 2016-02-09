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
	function ext_set_related_payment_methods( $payment_method_ids ){
		EEM_Extra_Join::instance()->delete( 
			array(
				array(
					'EXJ_first_model_ID' => $this->_->ID(),
					'EXJ_first_model_name' => 'Event',
					'EXJ_second_model_name' => 'Payment_Method'
				)
			));
		$result = true;
		foreach( $payment_method_ids as $payment_method_id ) {
			$result = $this->_->_add_relation_to( $payment_method_id, 'Payment_Method' );
		}
		return $result;
	}
}

// End of file EEE_Mock_Attendee.php