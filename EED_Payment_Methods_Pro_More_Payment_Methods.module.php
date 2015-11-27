<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/*
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		$VID:$
 *
 * ------------------------------------------------------------------------
 */
/**
 * Class  EED_Payment_Methods_Pro_More_Payment_Methods
 * 
 * Modifies the payment methods admin page to allow an admin to
 * activate more payment methods of the same type
 *
 * @package			Event Espresso
 * @subpackage		eea-payment-methods-pro
 * @author 				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_Payment_Methods_Pro_More_Payment_Methods extends EED_Module {

	
	/**
	 * @return EED_Payment_Methods_Pro_more_Payment_Methods
	 */
	public static function instance() {
		return parent::get_instance( __CLASS__ );
	}



	 /**
	  * 	set_hooks - for hooking into EE Core, other modules, etc
	  *
	  *  @access 	public
	  *  @return 	void
	  */
	 public static function set_hooks() {
	 }

	 /**
	  * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	  *
	  *  @access 	public
	  *  @return 	void
	  */
	 public static function set_hooks_admin() {
		add_filter( 
				'FHEE__Payments_Admin_Page__page_setup__page_routes', 
				array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'add_admin_routes' ) );
		add_filter( 
				'FHEE__Payments_Admin_Page___payment_methods_list__payment_methods', 
				array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'add_other_payment_methods' ) );
		add_filter( 
				'FHEE__Payments_Admin_Page___generate_payment_method_settings_form__form_subsections', 
				array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'add_buttons_onto_payment_settings_forms'), 10, 2 );
	 }



	 /**
	  * Adds buttons onto the end of each payment method settings form to allow
	  * activating a new payment method of the same type, and permanently delete them too
	  * @param array $subsections
	  * @param EE_Payment_Method $payment_method
	  * @return \EE_Form_Section_HTML
	  */
	public static function add_buttons_onto_payment_settings_forms( $subsections, $payment_method ) {
		$activate_another_text = sprintf( __( 'Activate Another %1$s Payment Method', 'event_espresso'), $payment_method->type() );
		$delete_text = sprintf( __( 'Permanently Delete %1$s Payment Method', 'event_espresso'), $payment_method->admin_name() );
		if( defined( 'EE_PAYMENTS_ADMIN_URL' ) ) {
			$url = EE_PAYMENTS_ADMIN_URL;
		} else { 
			$url = '';
		}
		$subsections[ 'activate_another' ] = new EE_Form_Section_HTML(
				EEH_HTML::tr(
					EEH_HTML::th( sprintf( __( 'Advanced', 'event_espresso'), $payment_method->type() ) ) .
					EEH_HTML::td(
						EEH_HTML::link(
							EE_Admin_Page::add_query_args_and_nonce(
								array(
									'action'=>'activate_another_payment_method',
									'payment_method_type'=>$payment_method->type()
								),
								$url
							),
							$activate_another_text,
							$activate_another_text,
							'activate_another_' . $payment_method->slug(),
							'espresso-button button-secondary'
						) . 
						EEH_HTML::br() . 
						EEH_HTML::link(
							EE_Admin_Page::add_query_args_and_nonce(
								array(
									'action'=>'delete_payment_method',
									'payment_method'=>$payment_method->slug()
								),
								$url
							),
							$delete_text,
							$delete_text,
							'delete_' . $payment_method->slug(),
							'espresso-button button-secondary'
						)
					)
				)
			);
		return $subsections;
	}

	/**
	 * Adds other payment methods of the same type, which weren't picked up
	 * by EE core
	 * @param EE_Payment_Method[] $payment_methods
	 * @return EE_Payment_Method[]
	 */
	public static function add_other_payment_methods( $payment_methods ) {
		$other_payment_methods = EEM_Payment_Method::instance()->get_all( 
				array(
					array(
						'PMD_type' => array( '!=', 'Admin_Only' ),
						'PMD_slug' => array( 'NOT_IN', array_keys( $payment_methods ) )
					)
				));
		return array_merge( $payment_methods, $other_payment_methods );
	}


	/**
	 * Adds other routes for the payments admin page.
	 * @param array $routes
	 * @return array
	 */
	public static function add_admin_routes( $routes ) {
		$routes[ 'activate_another_payment_method' ] = array(
			'func'=>'ee_payment_methods_pro_activate_another_payment_method',
			'noheader'=>TRUE,
			'capability' => 'ee_edit_payment_methods'
			);
		$routes[ 'delete_payment_method' ] = array(
			'func'=>'ee_payment_methods_pro_delete_payment_method',
			'noheader'=>TRUE,
			'capability' => 'ee_delete_payment_methods'
			);
		return $routes;
	}


	 /**
	  *    run - initial module setup
	  *
	  * @access    public
	  * @param  WP $WP
	  * @return    void
	  */
	 public function run( $WP ) {
	 }




	/**
	 *		@ override magic methods
	 *		@ return void
	 */
	public function __set($a,$b) { return FALSE; }
	public function __get($a) { return FALSE; }
	public function __isset($a) { return FALSE; }
	public function __unset($a) { return FALSE; }
	public function __clone() { return FALSE; }
	public function __wakeup() { return FALSE; }
	public function __destruct() { return FALSE; }

 }
 
 /**
  * This needs to be a separate function because of how admin page routes are called.
  * This route activates another payment method of the requested type
  * @param Payments_Admin_Page $payment_methods_page
  */
 function ee_payment_methods_pro_activate_another_payment_method( Payments_Admin_Page $payment_methods_page ) {
		$req_data = $payment_methods_page->get_request_data();
		$pm_slug = null;
		if( isset( $req_data[ 'payment_method_type' ] ) ) {
			EE_Registry::instance()->load_lib('Payment_Method_Manager');
			$payment_methods_manager = EE_Payment_Method_Manager::instance();
			$pm_type_class = $payment_methods_manager->payment_method_class_from_type( $req_data[ 'payment_method_type' ] );
			if( class_exists( $pm_type_class )){
				/** @var $pm_type_obj EE_PMT_Base */
				$pm_type_obj = new $pm_type_class;
				$pm = $payment_methods_manager->create_payment_method_of_type( $pm_type_obj );
				$admin_name_base = $pm->admin_name();
				$slug_base = $pm->slug();
				$count = 2;
				while( EEM_Payment_Method::instance()->exists( array( array( 'PMD_slug' => $pm->slug() ) ) ) ) {
					$pm->set_slug( $slug_base . '-' . $count );
					$pm->set_admin_name( $admin_name_base . ' ' . $count ++ );
				}
				$payment_methods_manager->activate_and_initialize_payment_method( $pm );
				$payment_methods_manager->set_usable_currencies_on_payment_method( $pm );
				$pm_slug = $pm->slug();
				$success = true;
			} else {
				$success = false;
			}
		}
		$payment_methods_page->redirect_after_action( $success, 'Payment Method', 'activated', array('action' => 'default','payment_method'=>$pm_slug ) );
}

/**
 * Route that deletes the specified payment method. 
 * Yes this orphans all the payments and transactions for this payment method,
 * but ee core should be able to handle that because it's quite common for a payment
 * method from an addon to be deactivated and would similarly orphan them
 * @param Payments_Admin_Page $payment_methods_page
 */
function ee_payment_methods_pro_delete_payment_method( Payments_Admin_Page $payment_methods_page ) { 
	$req_data = $payment_methods_page->get_request_data();
	$success = false;
	if( isset( $req_data[ 'payment_method' ] ) ) {
		$payment_method = EEM_Payment_Method::instance()->get_one_by_slug( $req_data[ 'payment_method' ] );
		if( $payment_method instanceof EE_Payment_Method ) {
			EEM_Currency_Payment_Method::instance()->delete( 
					array( 
						array(
							'PMD_ID' => $payment_method->ID()
						)
					),
					//don't allow blocking. So this could orphan transactions and payments but oh well, 
					//EE should be able to handle that
					false 
					);
			//delete related currencies
			EEM_Extra_Meta::instance()->delete( array(
			array(
				'EXM_type' => 'Payment_Method',
				'OBJ_ID' => $payment_method->ID()
			 ) ) );
			$payment_method->delete();
			$success = true;
		}

	}
	$payment_methods_page->redirect_after_action( $success, 'Payment Method', 'deleted', array('action' => 'default' ) );
}
