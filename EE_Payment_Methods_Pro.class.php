<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit(); }
/**
 * ------------------------------------------------------------------------
 *
 * Class  EE_Payment_Methods_Pro
 *
 * @package			Event Espresso
 * @subpackage		eea-payment-methods-pro
 * @author			    Brent Christensen
 * @ version		 	$VID:$
 *
 * ------------------------------------------------------------------------
 */
// define the plugin directory path and URL
define( 'EE_PAYMENT_METHODS_PRO_BASENAME', plugin_basename( EE_PAYMENT_METHODS_PRO_PLUGIN_FILE ));
define( 'EE_PAYMENT_METHODS_PRO_PATH', plugin_dir_path( __FILE__ ));
define( 'EE_PAYMENT_METHODS_PRO_URL', plugin_dir_url( __FILE__ ));
define( 'EE_PAYMENT_METHODS_PRO_ADMIN', EE_PAYMENT_METHODS_PRO_PATH . 'admin' . DS . 'payment_methods_pro' . DS );
Class  EE_Payment_Methods_Pro extends EE_Addon {

	/**
	 * class constructor
	 */
	public function __construct() {
	}

	public static function register_addon() {
		// register addon via Plugin API
		EE_Register_Addon::register(
			'Payment_Methods_Pro',
			array(
				'version' 					=> EE_PAYMENT_METHODS_PRO_VERSION,
				'min_core_version' => '4.6.0.dev.000',
				'main_file_path' 				=> EE_PAYMENT_METHODS_PRO_PLUGIN_FILE,
//				'admin_path' 			=> EE_PAYMENT_METHODS_PRO_ADMIN,
//				'admin_callback'		=> 'additional_admin_hooks',
//				'config_class' 			=> 'EE_Payment_Methods_Pro_Config',
//				'config_name' 		=> 'EE_Payment_Methods_Pro',
				'autoloader_paths' => array(
					'EE_Payment_Methods_Pro' 						=> EE_PAYMENT_METHODS_PRO_PATH . 'EE_Payment_Methods_Pro.class.php',
					'EE_Payment_Methods_Pro_Config' 			=> EE_PAYMENT_METHODS_PRO_PATH . 'EE_Payment_Methods_Pro_Config.php',
//					'Payment_Methods_Pro_Admin_Page' 		=> EE_PAYMENT_METHODS_PRO_ADMIN . 'Payment_Methods_Pro_Admin_Page.core.php',
//					'Payment_Methods_Pro_Admin_Page_Init' => EE_PAYMENT_METHODS_PRO_ADMIN . 'Payment_Methods_Pro_Admin_Page_Init.core.php',
				),
//				'dms_paths' 			=> array( EE_PAYMENT_METHODS_PRO_PATH . 'core' . DS . 'data_migration_scripts' . DS ),
				'module_paths' 		=> array( EE_PAYMENT_METHODS_PRO_PATH . 'EED_Payment_Methods_Pro_Event_Payment_Method.module.php' ),
//				'shortcode_paths' 	=> array( EE_PAYMENT_METHODS_PRO_PATH . 'EES_Payment_Methods_Pro.shortcode.php' ),
//				'widget_paths' 		=> array( EE_PAYMENT_METHODS_PRO_PATH . 'EEW_Payment_Methods_Pro.widget.php' ),
				// if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
				'pue_options'			=> array(
					'pue_plugin_slug' => 'eea-payment-methods-pro',
					'plugin_basename' => EE_PAYMENT_METHODS_PRO_BASENAME,
					'checkPeriod' => '24',
					'use_wp_update' => FALSE,
					),
//				'capabilities' => array(
//					'administrator' => array(
//						'read_addon', 'edit_addon', 'edit_others_addon', 'edit_private_addon'
//						),
//					),
//				'capability_maps' => array(
//					new EE_Meta_Capability_Map_Edit( 'edit_addon', array( 'Event', '', 'edit_others_addon', 'edit_private_addon' ) )
//					),
//				'class_paths' => EE_PAYMENT_METHODS_PRO_PATH . 'core' . DS . 'db_classes',
//				'model_paths' => EE_PAYMENT_METHODS_PRO_PATH . 'core' . DS . 'db_models',
//				'class_extension_paths' => EE_PAYMENT_METHODS_PRO_PATH . 'core' . DS . 'db_class_extensions',
//				'model_extension_paths' => EE_PAYMENT_METHODS_PRO_PATH . 'core' . DS . 'db_model_extensions',
//				'custom_post_types' => array(), //note for the mock we're not actually adding any custom
//								   //cpt stuff yet.
//				'custom_taxonomies' => array(),
//				'default_terms' => array()
			)
		);
	}



	/**
	 * 	additional_admin_hooks
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function additional_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && ! EE_Maintenance_Mode::instance()->level() ) {
			add_filter( 'plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );
		}
	}



	/**
	 * plugin_actions
	 *
	 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public function plugin_actions( $links, $file ) {
		if ( $file == EE_PAYMENT_METHODS_PRO_BASENAME ) {
			// before other links
			array_unshift( $links, '<a href="admin.php?page=espresso_payment_methods_pro">' . __('Settings') . '</a>' );
		}
		return $links;
	}






}
// End of file EE_Payment_Methods_Pro.class.php
// Location: wp-content/plugins/eea-payment-methods-pro/EE_Payment_Methods_Pro.class.php
