<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit('NO direct script access allowed'); }
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for Wordpress
 *
 * @package		Event Espresso
 * @author			Seth Shoultes
 * @copyright		(c)2009-2012 Event Espresso All Rights Reserved.
 * @license			http://eventespresso.com/support/terms-conditions/  ** see Plugin Licensing **
 * @link				http://www.eventespresso.com
 * @version			4.0
 *
 * ------------------------------------------------------------------------
 *
 * espresso_events_Registration_Form_Hooks
 * Hooks various messages logic so that it runs on indicated Events Admin Pages.
 * Commenting/docs common to all children classes is found in the EE_Admin_Hooks parent.
 *
 *
 * @package			espresso_events_Payment_Methods_Pro_Hooks
 * @subpackage		wp-content/plugins/eea-payment-methods-pro/admin/payment_methods_pro/espresso_events_Payment_Methods_Pro_Hooks.class.php
 * @author				Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class espresso_events_Payment_Methods_Pro_Hooks extends EE_Admin_Hooks {

	protected function _set_hooks_properties() {
		$this->_name = 'payment_methods_pro';
		$this->_metaboxes = array(
			0 => array(
				'page_route' => array( 'edit', 'create_new' ),
				'func' => 'event_specific_payment_methods',
				'label' => __('Event-Specific Payment Methods', 'event_espresso'),
				'priority' => 'default',
				'context' => 'side'
				)
		);

		//hook into the handler for saving question groups
		add_filter( 'FHEE__Events_Admin_Page___insert_update_cpt_item__event_update_callbacks', array( $this, 'modify_callbacks'), 10 );

		//hook into revision restores (we're hooking into the global action because EE_Admin_Hooks classes are already restricted by page)
		add_action( 'AHEE_EE_Admin_Page_CPT__restore_revision', array($this, 'restore_revision' ), 10, 2 );
	}





	public function modify_callbacks( $callbacks ) {
		//now let's add the question group callback
		$callbacks[] = array( $this, 'update_event_specific_payment_methods' );
		return $callbacks;
	}
	
	public function event_specific_payment_methods( $post ) {
		$form = $this->_get_event_specific_payment_methods_form( $post->ID );
		$input = $form->get_input( 'payment_methods' );
		$form_input_html = $input->get_html_for_input();
		echo EEH_Template::locate_template( 
			EE_PAYMENT_METHODS_PRO_ADMIN_TEMPLATE_PATH . 'payment_methods_for_event_metabox.template.php', 
			array(
				'form_input_html' => $form_input_html,
			)
		);
		
	}
	
	protected function _get_event_specific_payment_methods_form( $post_id ) {
		$payment_methods = EEM_Payment_Method::instance()->get_all( 
				array(
					array(
						'PMD_scope' => array( 'LIKE', '%' . EED_Payment_Methods_Pro_Event_Payment_Method::scope_specific_events . '%' )
					)
				)
			);
		
		$options = array();
		foreach( $payment_methods as $payment_method ) {
			$options[ $payment_method->ID() ] = $payment_method->admin_name();
		}
		$form = new EE_Form_Section_Proper( 
					array(
						'subsections' => array(
							'payment_methods' => new EE_Checkbox_Multi_Input( $options,
							array(
								'default' => EED_Payment_Methods_Pro_Event_Payment_Method::get_payment_methods_for_event( $post_id )
							))
						),
					)
				);
		$form->_construct_finalize( null, 'event_specific_payment_methods');
//		$form->
		return $form;
	}
	
	/**
	 * 
	 * @param EE_Event $event_obj
	 * @param type $data
	 */
	public function update_event_specific_payment_methods( $event_obj, $data ) {
		
		$form = $this->_get_event_specific_payment_methods_form( $event_obj->ID() );
		$form->receive_form_submission( $data );
		if( $form->is_valid() ) {
			$input = $form->get_input( 'payment_methods' );
			//use method from EEE_Payment_Methods_Pro_Event to add relation to all specified events
			$event_obj->set_related_payment_methods( $input->normalized_value() );
			if( 
				empty( $input->normalized_value() )
				&& ! EEM_Payment_Method::instance()->get_all_active( EEM_Payment_Method::scope_cart ) 
			) {
				EE_Error::add_attention( 
					__( 'There are no payment methods activated for this event. Even if it\'s a free event, it\'s still a good idea to have a payment method on it. Please activate a payment method for "Front-End Registration Page" on the payments admin page, or select one in the "Event-Specific Payment Methods" section below.', 'event_espresso' ),
					__FILE__,
					__FUNCTION__,
					__LINE__ 
				);
			}
		}
	}

}
// End of file espresso_events_Payment_Methods_Pro_Hooks.class.php
// Location: /wp-content/plugins/eea-payment-methods-pro/admin/payment_methods_pro/espresso_events_Payment_Methods_Pro_Hooks.class.php