<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit('NO direct script access allowed'); }
/**
 * espresso_events_Registration_Form_Hooks
 * Adds hooks for adding a payment methods metabox on the EE admin event editing pages,
 * and for updating what payment methods should be available on each event.
 *
 *
 * @package			espresso_events_Payment_Methods_Pro_Hooks
 * @subpackage		wp-content/plugins/eea-payment-methods-pro/admin/payment_methods_pro/espresso_events_Payment_Methods_Pro_Hooks.class.php
 * @author			Mike Nelson
 */
class espresso_events_Payment_Methods_Pro_Hooks extends EE_Admin_Hooks {

	protected function _set_hooks_properties() {
		$this->_name = 'payment_methods_pro';
		$this->_metaboxes = array(
			0 => array(
				'page_route' => array( 'edit', 'create_new' ),
				'func' => 'event_specific_payment_methods',
				'label' => __('Payment Methods', 'event_espresso'),
				'priority' => 'default',
				'context' => 'side'
            )
		);

		//hook into the handler for saving question groups
		add_filter( 'FHEE__Events_Admin_Page___insert_update_cpt_item__event_update_callbacks', array( $this, 'modify_callbacks'), 10 );

		//hook into revision restores (we're hooking into the global action because EE_Admin_Hooks classes are already restricted by page)
		add_action( 'AHEE_EE_Admin_Page_CPT__restore_revision', array($this, 'restore_revision' ), 10, 2 );
	}



    /**
     * @param array $callbacks
     * @return array
     */
    public function modify_callbacks( array $callbacks ) {
		//now let's add the question group callback
		$callbacks[] = array( $this, 'update_event_specific_payment_methods' );
		return $callbacks;
	}



    /**
     * @param WP_Post $post
     * @throws EE_Error
     */
    public function event_specific_payment_methods( WP_Post $post ) {
		$form = $this->_get_event_specific_payment_methods_form( $post->ID );
		$form_input_html = $form->get_html_and_js();
		echo EEH_Template::locate_template(
			EE_PAYMENT_METHODS_PRO_ADMIN . 'templates' . DS . 'payment_methods_for_event_metabox.template.php',
			array(
				'form_input_html' => $form_input_html,
			)
		);

	}



    /**
     * Gets the form for selecting an event's payment methods
     *
     * @param int $post_id
     * @return EE_Form_Section_Proper
     * @throws EE_Error
     */
	protected function _get_event_specific_payment_methods_form( $post_id ) {
		$payment_methods = EEM_Payment_Method::instance()->get_all_active(
			EEM_Payment_Method::scope_cart,
			array(
				'order_by' => array(
					'PMD_admin_name' => 'ASC'
				)
			)
		);
		$payment_methods_available_for_event = EEM_Payment_Method::instance()->get_payment_methods_available_for_event( $post_id );
		$payment_methods_grouped_by_type = array();
		foreach( $payment_methods as $payment_method ) {
			$payment_methods_grouped_by_type[ $payment_method->type() ][ $payment_method->ID()  ] = $payment_method->admin_name();
		}
		$subsections = array();
		foreach( $payment_methods_grouped_by_type as $type => $payment_methods_of_type ) {
			$options = array(
				0 => __( 'do not use', 'event_espresso' )
			);
			foreach( $payment_methods_of_type as $PMD_ID => $name ) {
				$options[ $PMD_ID ] = $name;
			}
			$available_for_this_type = array_intersect_key( $payment_methods_available_for_event, $payment_methods_of_type );
			reset( $available_for_this_type );
			$subsections[ $type ] = new EE_Radio_Button_Input(
				$options,
				array(
					'default' => ! empty( $available_for_this_type ) ? key( $available_for_this_type ) : 0
				)
			);
		}
		$form = new EE_Form_Section_Proper(
            array(
                'subsections' => $subsections,
            )
        );
		$form->_construct_finalize( null, 'event_specific_payment_methods');
		return $form;
	}



    /**
     * @param EE_Event $event_obj
     * @param array    $data
     * @throws EE_Error
     */
	public function update_event_specific_payment_methods( $event_obj, $data ) {

		$form = $this->_get_event_specific_payment_methods_form( $event_obj->ID() );
		$form->receive_form_submission( $data );
		if( $form->is_valid() ) {
			$selected_payment_methods = $this->_get_active_payment_methods_from_form( $form );
			//use method from EEE_Payment_Methods_Pro_Event to add relation to all specified events
			$event_obj->set_payment_methods_available_on_event( $selected_payment_methods );
			//ok and if no payment methods are usable on this event, let the admin know
			EED_Payment_Methods_Pro_Event_Payment_Method::ensure_event_have_at_least_one_payment_method(
				EEM_Payment_Method::instance()->get_all(
					array(
						array(
							'PMD_ID' => array( 'IN', $selected_payment_methods )
						)
					)
				),
				$event_obj->ID()
			);
		}
	}

	/**
	 * Determines which payment methods should be active on this event,
	 * based on the form's data. receive_form_submission() should have already
	 * been called on it
	 * @param EE_Form_Section_Proper $form.
	 * @return array of payment method IDs which should be active for this event
	 */
	protected function _get_active_payment_methods_from_form( EE_Form_Section_Proper $form ) {
		$payment_method_ids = array();
		foreach( $form->inputs() as $input ) {
			$value = $input->normalized_value();
			if( $value !== 0 ) {
				$payment_method_ids[] = $value;
			}
		}
		return $payment_method_ids;
	}

	protected function _set_page_object() {

	}

}
// End of file espresso_events_Payment_Methods_Pro_Hooks.class.php
// Location: /wp-content/plugins/eea-payment-methods-pro/admin/payment_methods_pro/espresso_events_Payment_Methods_Pro_Hooks.class.php