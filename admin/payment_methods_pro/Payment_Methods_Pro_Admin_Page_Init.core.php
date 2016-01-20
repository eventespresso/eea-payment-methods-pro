<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
* Event Espresso
*
* Event Registration and Management Plugin for WordPress
*
* @ package 		Event Espresso
* @ author			Seth Shoultes
* @ copyright 	(c) 2008-2011 Event Espresso  All Rights Reserved.
* @ license 		{@link http://eventespresso.com/support/terms-conditions/}   * see Plugin Licensing *
* @ link 				{@link http://www.eventespresso.com}
* @ since		 	$VID:$
*
* ------------------------------------------------------------------------
*
* Payment_Methods_Pro_Admin_Page_Init class
*
* This is the init for the Payment_Methods_Pro Addon Admin Pages.  See EE_Admin_Page_Init for method inline docs.
*
* @package			Event Espresso (payment_methods_pro addon)
* @subpackage		admin/Payment_Methods_Pro_Admin_Page_Init.core.php
* @author				Darren Ethier
*
* ------------------------------------------------------------------------
*/
class Payment_Methods_Pro_Admin_Page_Init extends EE_Admin_Page_Init  {

	/**
	 * 	constructor
	 *
	 * @access public
	 * @return \Payment_Methods_Pro_Admin_Page_Init
	 */
	public function __construct() {

		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );

		define( 'PAYMENT_METHODS_PRO_PG_SLUG', 'espresso_payment_methods_pro' );
		define( 'PAYMENT_METHODS_PRO_LABEL', __( 'Payment Methods Pro', 'event_espresso' ));
		define( 'EE_PAYMENT_METHODS_PRO_ADMIN_URL', admin_url( 'admin.php?page=' . PAYMENT_METHODS_PRO_PG_SLUG ));
		define( 'EE_PAYMENT_METHODS_PRO_ADMIN_ASSETS_PATH', EE_PAYMENT_METHODS_PRO_ADMIN . 'assets' . DS );
		define( 'EE_PAYMENT_METHODS_PRO_ADMIN_ASSETS_URL', EE_PAYMENT_METHODS_PRO_URL . 'admin' . DS . 'payment_methods_pro' . DS . 'assets' . DS );
		define( 'EE_PAYMENT_METHODS_PRO_ADMIN_TEMPLATE_PATH', EE_PAYMENT_METHODS_PRO_ADMIN . 'templates' . DS );
		define( 'EE_PAYMENT_METHODS_PRO_ADMIN_TEMPLATE_URL', EE_PAYMENT_METHODS_PRO_URL . 'admin' . DS . 'payment_methods_pro' . DS . 'templates' . DS );

		parent::__construct();
		$this->_folder_path = EE_PAYMENT_METHODS_PRO_ADMIN;

	}





	protected function _set_init_properties() {
		$this->label = PAYMENT_METHODS_PRO_LABEL;
	}



	/**
	*		_set_menu_map
	*
	*		@access 		protected
	*		@return 		void
	*/
	protected function _set_menu_map() {
		//at least for now we don't need this
//		$this->_menu_map = new EE_Admin_Page_Sub_Menu( array(
//			'menu_group' => 'addons',
//			'menu_order' => 25,
//			'show_on_menu' => EE_Admin_Page_Menu_Map::BLOG_ADMIN_ONLY,
//			'parent_slug' => 'espresso_events',
//			'menu_slug' => PAYMENT_METHODS_PRO_PG_SLUG,
//			'menu_label' => PAYMENT_METHODS_PRO_LABEL,
//			'capability' => 'administrator',
//			'admin_init_page' => $this
//		));
	}



}
// End of file Payment_Methods_Pro_Admin_Page_Init.core.php
// Location: /wp-content/plugins/eea-payment-methods-pro/admin/payment_methods_pro/Payment_Methods_Pro_Admin_Page_Init.core.php
