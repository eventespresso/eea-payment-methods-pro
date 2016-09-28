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
	public function ext_set_available_by_default( $on_by_default ){
		//if this payment method is being set to being on by default, 
		//we need to ensure there are no other payment methods are on by default
		//(we think folks will only want one payment method of a given type
		//active on an event at a time; but they're allowed to have lots of 
		//inactive ones of course)
		//get the previous value
		$previous_default_availability = $this->_->is_available_by_default();
		if( ! $previous_default_availability
			&& $on_by_default ) {
			//ok get the previous default payment method of this type (there should only be one at a time)
			$previous_default_payment_methods = EEM_Payment_Method::instance()->get_all(
				array(
					array(
						'Extra_Meta.EXM_key' => EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key,
						'Extra_Meta.EXM_value' => '1',
						'PMD_type' => $this->_->type(),
						'Extra_Meta.OBJ_ID' => array( '!=', $this->_->ID() )
					)
				)
			);
			foreach( $previous_default_payment_methods as $previous_default_payment_method ) {
				//we'd like to just call $previous_default_payment_method->set_available_by_default( false );
				//but model extensions don't allow for recursion currently. So do the gist of it:
				//update its extra meta
				$previous_default_payment_method->update_extra_meta( EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key, false );
				//and remove any exceptions for it
				$deletions = EEM_Extra_Join::instance()->delete( 
					array(
						array(
							'EXJ_first_model_name' => 'Event',
							'EXJ_second_model_name' => 'Payment_Method',
							'EXJ_second_model_ID' => $previous_default_payment_method->ID(),
						)
					)
				);
			}
		}
		//have they changed the payment method's default availability?
		//if so, they probably want it to take effect now, so remove all the exceptions
		if( $previous_default_availability != $on_by_default ) {
			$this->_->remove_availability_exceptions();
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
	
	/**
	 * Removes all the payment methods' availability exceptions. So if this payme
	 * method should be available on all events by default, it will be. Or if
	 * it should NOT be available on events by default, then it won't be.
	 * @return int the number of availability exceptions that were deleted
	 */
	public function ext_remove_availability_exceptions() {
		return EEM_Extra_Join::instance()->delete( 
			array(
				array(
					'EXJ_first_model_name' => 'Event',
					'EXJ_second_model_name' => 'Payment_Method',
					'EXJ_second_model_ID' => $this->_->ID(),
				)
			)
		);
	}
}

// End of file EEE_Mock_Attendee.php