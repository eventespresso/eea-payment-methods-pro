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
		
		$this->_extra_relations = array('Event'=>new EE_HABTM_Any_Relation());
		parent::__construct();
	}
}

// End of file EEME_Payment_Methods_Pro_Payment_Method.model_ext.php