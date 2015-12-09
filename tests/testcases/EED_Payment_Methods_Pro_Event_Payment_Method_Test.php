<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EED_Payment_Methods_Pro_Event_Payment_Method_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EED_Payment_Methods_Pro_Event_Payment_Method_Test extends EE_UnitTestCase{

	function test_show_payment_methods_indicated_by_event_postmetas() {
		//remove all payment methods by default, and dont allow blocking
		EEM_Payment_Method::instance()->delete( array(), false );
		$active_payment_method_in_backend = EE_Payment_Method_Manager::instance()->activate_a_payment_method_of_type( 'Invoice' );
		$active_payment_method_in_backend->set_scope( array( EEM_Payment_Method::scope_admin ) );
		$active_payment_method_in_backend->save();
		$active_payment_method_in_frontend = EE_Payment_Method_Manager::instance()->activate_a_payment_method_of_type( 'Bank' );
		$active_payment_method_in_frontend->save();
		$t = $this->new_typical_transaction();
		//because there are no active payment methods, none should be found
		$payment_methods_for_t =  EEM_Payment_Method::instance()->get_all_for_transaction( $t, EEM_Payment_Method::scope_cart );
		$this->assertEquals( $active_payment_method_in_frontend, reset( $payment_methods_for_t ) );
		$this->assertEquals( 1, count( $payment_methods_for_t ) );

		$primary_reg = $t->primary_registration();
		$event = $primary_reg->event();
		$event->set_related_payment_methods( array( $active_payment_method_in_backend->ID() ) );
		//now when we look for payment methods, we should find the one indicated by the postmeta
		$payment_methods_for_t = EEM_Payment_Method::instance()->get_all_for_transaction( $t, EEM_Payment_Method::scope_cart );
		$this->assertEEModelObjectsEquals( $active_payment_method_in_backend, reset( $payment_methods_for_t ) );
		$this->assertEEModelObjectsEquals( $active_payment_method_in_frontend, next( $payment_methods_for_t ) );
		$this->assertEquals( 2, count( $payment_methods_for_t ) );

	}
}

// End of file EED_Payment_Methods_Pro_Event_Payment_Method_Test.php