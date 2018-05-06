<?php

// define the plugin directory path and URL
define('EE_PAYMENT_METHODS_PRO_BASENAME', plugin_basename(EE_PAYMENT_METHODS_PRO_PLUGIN_FILE));
define('EE_PAYMENT_METHODS_PRO_PATH', plugin_dir_path(__FILE__));
define('EE_PAYMENT_METHODS_PRO_URL', plugin_dir_url(__FILE__));
define('EE_PAYMENT_METHODS_PRO_ADMIN', EE_PAYMENT_METHODS_PRO_PATH . 'admin' . DS . 'payment_methods_pro' . DS);



/**
 * Class  EE_Payment_Methods_Pro
 *
 * @package     Event Espresso
 * @subpackage  eea-payment-methods-pro
 * @author      Mike Nelson
 * @version     1.0.0.rc.004
 */
class EE_Payment_Methods_Pro extends EE_Addon
{


    public static function register_addon()
    {
        // register addon via Plugin API
        EE_Register_Addon::register(
            'Payment_Methods_Pro',
            array(
                'version'               => EE_PAYMENT_METHODS_PRO_VERSION,
                'min_core_version'      => EE_PAYMENT_METHODS_PRO_CORE_VERSION_REQUIRED,
                'main_file_path'        => EE_PAYMENT_METHODS_PRO_PLUGIN_FILE,
                'autoloader_paths'      => array(
                    'espresso_events_Payment_Methods_Pro_Hooks' => EE_PAYMENT_METHODS_PRO_ADMIN
                                                                   . 'espresso_events_Payment_Methods_Pro_Hooks.class.php',
                ),
                'module_paths'          => array(
                    EE_PAYMENT_METHODS_PRO_PATH . 'EED_Payment_Methods_Pro_Event_Payment_Method.module.php',
                    EE_PAYMENT_METHODS_PRO_PATH . 'EED_Payment_Methods_Pro_More_Payment_Methods.module.php',
                ),
                // if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
                'pue_options'           => array(
                    'pue_plugin_slug' => 'eea-payment-methods-pro',
                    'plugin_basename' => EE_PAYMENT_METHODS_PRO_BASENAME,
                    'checkPeriod'     => '24',
                    'use_wp_update'   => false,
                ),
                'class_extension_paths' => EE_PAYMENT_METHODS_PRO_PATH . 'core' . DS . 'db_class_extensions',
                'model_extension_paths' => EE_PAYMENT_METHODS_PRO_PATH . 'core' . DS . 'db_model_extensions',
            )
        );
    }



    /**
     * a safe space for addons to add additional logic like setting hooks
     * that will run immediately after addon registration
     * making this a great place for code that needs to be "omnipresent"
     */
    public function after_registration()
    {
        self::deactivate_if_mer_active();
        add_filter('FHEE_do_other_page_hooks_espresso_events', array(__CLASS__, 'add_admin_hooks_file'));
    }



    /**
     * Add our admin hooks class for modifying the event editing page
     *
     * @param array $registered_pages
     *
     * @return array
     */
    public static function add_admin_hooks_file($registered_pages)
    {
        // if PMP got deactivated, or somehow the hooks file didn't get autoloaded after all,
        // don't register our admin hooks file
        if (class_exists('espresso_events_Payment_Methods_Pro_Hooks')) {
            $registered_pages[] = 'espresso_events_Payment_Methods_Pro_Hooks.class.php';
        }

        return $registered_pages;
    }



    /**
     *    additional_admin_hooks
     *
     * @access    public
     * @return    void
     */
    public function additional_admin_hooks()
    {
        // is admin and not in M-Mode ?
        if (is_admin() && ! EE_Maintenance_Mode::instance()->level()) {
            add_filter('plugin_action_links', array( $this, 'plugin_actions' ), 10, 2);
        }
    }


    /**
     * plugin_actions
     * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
     *
     * @param $links
     * @param $file
     *
     * @return array
     */
    public function plugin_actions($links, $file)
    {
        if ($file === EE_PAYMENT_METHODS_PRO_BASENAME) {
            // before other links
            array_unshift(
                $links,
                '<a href="admin.php?page=espresso_payment_methods_pro">' . __('Settings', 'event_espresso') . '</a>'
            );
        }

        return $links;
    }

    /**
     * Don't run MER and payment methods pro together, because if we did that, we'd
     * have to only show payment methods usable by ALL selected events, and it's
     * possible there might be none, so we'd need to have some fallback plan etc.
     * Besides, people who need this probably don't need MER. But we'll see
     * if folks indicate otherwise
     */
    public static function deactivate_if_mer_active()
    {
        if (class_exists('EE_Multi_Event_Registration')
             && isset(EE_Registry::instance()->addons->EE_Multi_Event_Registration)
             && EE_Registry::instance()->addons->EE_Multi_Event_Registration instanceof EE_Multi_Event_Registration
        ) {
            if (! function_exists('deactivate_plugins')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            deactivate_plugins(EE_PAYMENT_METHODS_PRO_BASENAME);
            EE_Error::add_persistent_admin_notice(
                'no_mer_and_pmp_together',
                __('Payment Methods Pro addon was deactivated because Multi Event Registration was also active, and the two are incompatible.', 'event_espresso'),
                true
            );
        }
    }
}
