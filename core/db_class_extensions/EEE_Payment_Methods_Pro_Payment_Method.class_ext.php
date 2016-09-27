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
	 * @param boolean $on_by_default IDs of related payment methods
	 * @return boolean
	 */
	function ext_set_available_by_default( $on_by_default ){
		//if this payment method is being set to being on by default, 
		//we need to ensure there are no other payment methods are on by default
		//(we think folks will only want one payment method of a given type
		//active on an event at a time; but they're allowed to have lots of 
		//inactive ones of course)
		if( $on_by_default ) {
			EEM_Extra_Meta::instance()->update(
				array(
					'EXM_value' => '0'
				),
				array(
					array(
						'EXM_value' => '1',
						'EXM_key' => EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key,
						//for all payment methods of the same type
						'Payment_Method.PMD_type' => $this->_->type(),
						//that aren't this one
						'OBJ_ID' => array( '!=', $this->_->ID() ),
					)
				)
			);
			//@todo: when we change a payment method's default availability,
			//we should probably remove its availability exceptions.
		}
		$this->_->update_extra_meta( EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key, $on_by_default );
	}
	
	/**
	 * Whether or not this payment method is available by default on all events
	 * @return boolean
	 */
	public function ext_is_available_by_default(){
		$string_val = $this->_->get_extra_meta( EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key, true, '1' );
		if( $string_val === '1' ) {
			return true;
		} else {
			return false;
		}
	}
}

// End of file EEE_Mock_Attendee.php