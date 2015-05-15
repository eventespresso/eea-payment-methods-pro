<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EEME_Mock_Attendee extends EEM_Attendee and adds a function named 'new_func' onto it
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEME_Payment_Methods_Pro_Attendee extends EEME_Base{
	function __construct() {
		$this->_model_name_extended = 'Attendee';
		$this->_extra_tables = array(
			'Payment_Methods_Pro_Attendee_Meta' => new EE_Secondary_Table('esp_payment_methods_pro_attendee_meta', 'NATT_ID', 'ATT_ID')
		);
		$this->_extra_fields = array('Payment_Methods_Pro_Attendee_Meta'=>array(
			'NATT_ID'=> new EE_DB_Only_Int_Field('NATT_ID', __('Payment Methods Pro Attendee Meta Row ID','event_espresso'), false),
			'NATT_ID_fk'=>new EE_DB_Only_Int_Field('ATT_ID', __("Foreign Key to Attendee in Post Table", "event_espresso"), false),
			'ATT_foobar'=>new EE_Foreign_Key_Int_Field('ATT_foobar', __("Foobar", 'event_espresso'), true,0,'Payment_Methods_Pro_Thing')));
		$this->_extra_relations = array('Payment_Methods_Pro_Thing'=>new EE_Belongs_To_Relation());
		parent::__construct();
	}
	function ext_get_all_new_things( $arg1 = FALSE ){
		return $this->_->get_all(array(array('Payment_Methods_Pro_Thing.NEW_ID'=>$arg1)));
	}
}

// End of file EEME_Mock_Attendee.model_ext.php