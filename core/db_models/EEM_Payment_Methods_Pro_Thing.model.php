<?php

if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');

/**
 *
 * EEM_Payment_Methods_Pro_Thing
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEM_Payment_Methods_Pro_Thing extends EEM_Base{
	// private instance of the EEM_Payment_Methods_Pro_Thing object
	protected static $_instance = NULL;

	protected function __construct($timezone = NULL) {
		$this->_tables = array(
			'Payment_Methods_Pro_Thing'=>new EE_Primary_Table('esp_payment_methods_pro_thing', 'NEW_ID')
		);
		$this->_fields = array(
			'Payment_Methods_Pro_Thing'=>array(
				'NEW_ID'=>new EE_Primary_Key_Int_Field('NEW_ID', __("Payment Methods Pro Thing ID", 'event_espresso')),
				'NEW_name' => new EE_Plain_Text_Field('NEW_name', __('Name', 'event_espresso'), FALSE)
			)
		);
		$this->_model_relations = array(
			'Attendee' => new EE_Has_Many_Relation(),
		);
		parent::__construct($timezone);
	}
}

// End of file EEM_Payment_Methods_Pro_Thing.model.php