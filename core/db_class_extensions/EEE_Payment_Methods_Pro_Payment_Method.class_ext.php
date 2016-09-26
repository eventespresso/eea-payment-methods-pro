<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * Adds functionality to payment methods
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEE_Payment_Methods_Pro_Payment_Method extends EEE_Base_Class{

	public function __construct(){
		$this->_model_name_extended = 'Payment_Method';
		parent::__construct();
	}

	/**
	 * Sets these payment method IDs to be the only paymen tmethods related to this event
	 * (ie overwrites previous relations)
	 * @param array $payment_method_ids IDs of related payment methods
	 * @return boolean
	 */
	function ext_set_primary( $primary ){
		//if this payment method is being set to primary, 
		//we need to ensure there are no other primary payment methods
		//(we think folks will only want one payment method of a given type
		//active on an event at a time; but they're allowed to have lots of 
		//inactive ones of course)
		if( $primary ) {
			EEM_Extra_Meta::instance()->update(
				array(
					'EXM_value' => false
				),
				array(
					'EXM_value' => true,
					'EXM_key' => 'PMD_primary',
					//for all payment methods of the same type
					'Payment_Method.PMD_type' => $this->_->type(),
					//that aren't this one
					'EXM_ID' => array( '!=', $this->_->ID() ),
				)
			);
		}
		$this->_->update_extra_meta( 'PMD_primary', $primary );
	}
}

// End of file EEE_Mock_Attendee.php