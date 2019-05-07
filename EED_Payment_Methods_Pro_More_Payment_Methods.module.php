<?php
/**
 * Class  EED_Payment_Methods_Pro_More_Payment_Methods
 * Modifies the payment methods admin page to allow an admin to
 * activate more payment methods of the same type.
 *
 * @package            Event Espresso
 * @subpackage         eea-payment-methods-pro
 * @author             Mike Nelson
 */
class EED_Payment_Methods_Pro_More_Payment_Methods extends EED_Module
{

    /**
     * name of the extra meta key where we store whether or not a payment
     * method should appear by default on all events
     */
    const on_by_default_meta_key = 'on_by_default';

    /**
     * @return EED_Payment_Methods_Pro_more_Payment_Methods
     */
    public static function instance()
    {
        return parent::get_instance(__CLASS__);
    }



    /**
     *    set_hooks - for hooking into EE Core, other modules, etc
     *
     * @access    public
     * @return    void
     */
    public static function set_hooks()
    {
    }

    /**
     *    set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @access    public
     * @return    void
     */
    public static function set_hooks_admin()
    {
        add_filter(
            'FHEE__Payments_Admin_Page__page_setup__page_routes',
            array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'add_admin_routes' )
        );
        add_filter(
            'FHEE__Payments_Admin_Page___payment_methods_list__payment_methods',
            array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'add_other_payment_methods' )
        );
        add_filter(
            'FHEE__Payments_Admin_Page___generate_payment_method_settings_form__form_subsections',
            array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'add_buttons_onto_payment_settings_forms' ),
            10,
            2
        );
        add_filter(
            'FHEE__Payments_Admin_Page___activate_payment_method_button__form_subsections',
            array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'change_activate_pm_button' ),
            10,
            2
        );
        add_action(
            'admin_enqueue_scripts',
            array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'enqueue_scripts' )
        );
        // add EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key to all payment method forms
        add_action(
            'AHEE__EE_Form_Input_Base___construct__before_construct_finalize_called',
            array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'add_primary_input_to_payment_method_forms' ),
            10,
            1
        );
        // make sure it gets autofilled correctly
        add_filter(
            'FHEE__EE_Model_Form_Section__populate_model_obj',
            array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'populate_primary_input_too' ),
            10,
            2
        );
        // save it when saving the payment method too
        add_action(
            'AHEE__EE_Model_Form_Section__save__done',
            array( 'EED_Payment_Methods_Pro_More_Payment_Methods', 'save_payment_method_form' ),
            10,
            2
        );
    }



    /**
     * Adds buttons onto the end of each payment method settings form to allow
     * activating a new payment method of the same type, and permanently delete them too
     *
     * @param array             $subsections
     * @param EE_Payment_Method $payment_method
     *
     * @return array
     */
    public static function add_buttons_onto_payment_settings_forms($subsections, $payment_method)
    {
        $activate_another_text = sprintf(
            __('Activate Another %1$s Payment Method', 'event_espresso'),
            $payment_method->type_obj()->pretty_name()
        );
        $delete_text           = sprintf(
            __('Permanently Delete %1$s Payment Method', 'event_espresso'),
            $payment_method->admin_name()
        );
        $url                   = defined('EE_PAYMENTS_ADMIN_URL') ? EE_PAYMENTS_ADMIN_URL : '';
        $subsections           = EEH_Array::insert_into_array(
            $subsections,
            array(
                'activate_another' => new EE_Form_Section_HTML(
                    EEH_HTML::table(
                        EEH_HTML::tr(
                            EEH_HTML::th() .
                            EEH_HTML::td(
                                EEH_HTML::link(
                                    EE_Admin_Page::add_query_args_and_nonce(
                                        array(
                                            'action'              => 'activate_another_payment_method',
                                            'payment_method_type' => $payment_method->type(),
                                        ),
                                        $url
                                    ),
                                    $activate_another_text,
                                    $activate_another_text,
                                    'activate_another_' . $payment_method->slug(),
                                    'espresso-button button-secondary'
                                )
                            )
                        )
                    )
                ),
            )
            // insert at very beginning
        );
        $subsections = EEH_Array::insert_into_array(
            $subsections,
            array(
                'permanently_delete' => new EE_Form_Section_HTML(
                    EEH_HTML::table(
                        EEH_HTML::tr(
                            EEH_HTML::th() .
                            EEH_HTML::td(
                                EEH_HTML::link(
                                    EE_Admin_Page::add_query_args_and_nonce(
                                        array(
                                            'action'         => 'delete_payment_method',
                                            'payment_method' => $payment_method->slug(),
                                        ),
                                        $url
                                    ),
                                    $delete_text,
                                    $delete_text,
                                    'delete_' . $payment_method->slug(),
                                    'espresso-button button-secondary delete delete-payment-method'
                                )
                            )
                        )
                    )
                ),
            ),
            // insert new subsection before the form fine print
            'fine_print'
        );

        return $subsections;
    }



    /**
     * Adds other payment methods of the same type, which weren't picked up
     * by EE core
     *
     * @param EE_Payment_Method[] $payment_methods
     *
     * @return EE_Payment_Method[]
     * @throws EE_Error
     */
    public static function add_other_payment_methods($payment_methods)
    {
        $other_payment_methods = EEM_Payment_Method::instance()->get_all(
            array(
                array(
                    'PMD_type' => array( '!=', 'Admin_Only' ),
                    'PMD_slug' => array( 'NOT_IN', array_keys($payment_methods) ),
                ),
            )
        );

        return array_merge($payment_methods, $other_payment_methods);
    }


    /**
     * Adds other routes for the payments admin page.
     *
     * @param array $routes
     *
     * @return array
     */
    public static function add_admin_routes($routes)
    {
        $routes['activate_another_payment_method'] = array(
            'func'       => array(
                'EED_Payment_Methods_Pro_More_Payment_Methods',
                'ee_payment_methods_pro_activate_another_payment_method',
            ),
            'noheader'   => true,
            'capability' => 'ee_edit_payment_methods',
        );
        $routes['delete_payment_method']           = array(
            'func'       => array(
                'EED_Payment_Methods_Pro_More_Payment_Methods',
                'ee_payment_methods_pro_delete_payment_method',
            ),
            'noheader'   => true,
            'capability' => 'ee_delete_payment_methods',
        );
        $routes['activate_payment_method']         = array(
            'func'       => array(
                'EED_Payment_Methods_Pro_More_Payment_Methods',
                'ee_payment_methods_pro_activate_payment_method',
            ),
            'noheader'   => true,
            'capability' => 'ee_edit_payment_methods',
        );

        return $routes;
    }

    /**
     * Changes the payment method settings form so the button to activate a payment
     * method sends in the payment method slug too, so we can identify which payment method
     * of a particular type they want activated
     *
     * @param EE_Form_Section_Base[] $subsections
     * @param EE_Payment_Method      $payment_method
     *
     * @return EE_Form_Section_Base[]
     */
    public static function change_activate_pm_button($subsections, $payment_method)
    {
        $link_text_and_title = sprintf(
            __('Activate %1$s Payment Method?', 'event_espresso'),
            $payment_method->admin_name()
        );

        return array(
            new EE_Form_Section_HTML(
                EEH_HTML::no_row(
                        $payment_method->type_obj()->introductory_html(),
                        2
                ) .
                EEH_HTML::table(
                    EEH_HTML::tr(
                        EEH_HTML::th(
                            EEH_HTML::label(__('Click to Activate ', 'event_espresso'))
                        ) .
                        EEH_HTML::td(
                            EEH_HTML::link(
                                EE_Admin_Page::add_query_args_and_nonce(
                                    array(
                                        'action'              => 'activate_payment_method',
                                        'payment_method_type' => $payment_method->type(),
                                        'payment_method_slug' => $payment_method->slug(),
                                    ),
                                    EE_PAYMENTS_ADMIN_URL
                                ),
                                $link_text_and_title,
                                $link_text_and_title,
                                'activate_' . $payment_method->slug(),
                                'espresso-button-green button-primary'
                            )
                        )
                    )
                )
            ),
        );
    }

    /**
     * Enqueues some javascript which: when a user clicks to delete a payment method
     * permanently, we have a js popup ask for confirmation
     *
     * @param string $hook see
     */
    public static function enqueue_scripts($hook)
    {
        if ($hook === 'event-espresso_page_espresso_payment_settings') {
            wp_enqueue_script(
                'pmp_more_payment_methods',
                EE_PAYMENT_METHODS_PRO_URL . '/scripts/' . 'espresso_payment_methods_pro.js'
            );
            wp_localize_script(
                'pmp_more_payment_methods',
                'ee_pmp_i18n',
                array(
                    'delete_pm_confirm' => __(
                        'Are you sure you want to permanently delete this payment method? It cannot be undone.',
                        'event_espresso'
                    ),
                )
            );
        }
    }


    /**
     *    run - initial module setup
     *
     * @access    public
     *
     * @param  WP $WP
     *
     * @return    void
     */
    public function run($WP)
    {
    }



    /**
     * Add the EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key input, but only once
     *
     * @param EE_Payment_Method_Form $form
     *
     * @throws EE_Error
     */
    public static function add_primary_input_to_payment_method_forms($form)
    {
        if ($form instanceof EE_Payment_Method_Form
             && ! $form->subsection_exists(EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key)
        ) {
            $form->add_subsections(
                array(
                    EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key => new EE_Yes_No_Input(
                        array(
                            'html_label_text' => __('Available By Default', 'event_espresso'),
                            'html_help_text'  => __(
                                'Set to "No" in order to only make this payment method available for specific events (go to the event\'s admin editing page, and select the payment method in the "Payment Methods" metabox.) When set to "Yes" the payment method is available on all events by default, like normal.',
                                'event_espresso'
                            ),
                        )
                    ),
                ),
                'PMD_scope',
                false
            );
        }
    }

    /**
     * Sets the default value for EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key
     * in payment method forms
     *
     * @param array                  $defaults
     * @param EE_Payment_Method_Form $form
     *
     * @return array
     */
    public static function populate_primary_input_too($defaults, $form)
    {
        if ($form instanceof EE_Payment_Method_Form) {
            $defaults[ EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key ] = (bool) $form->get_model_object()
                                                                                                            ->is_available_by_default();
        }

        return $defaults;
    }



    /**
     * Also save the EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key
     * field when updating a payment method using the form
     *
     * @param EE_Payment_Method_Form $payment_method_form
     * @param bool                   $save_result
     *
     * @throws EE_Error
     */
    public static function save_payment_method_form($payment_method_form, $save_result)
    {
        // if we just successfully used a payment method form to save a payment method
        if ($payment_method_form instanceof EE_Payment_Method_Form) {
            $payment_method_form->get_model_object()
                                ->set_available_by_default($payment_method_form->get_input_value(EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key));
        }
    }



    /**
     * This needs to be a separate function because of how admin page routes are called.
     * This route activates another payment method of the requested type
     *
     * @param Payments_Admin_Page $payment_methods_page
     *
     * @throws EE_Error
     */
    public static function ee_payment_methods_pro_activate_another_payment_method(
        Payments_Admin_Page $payment_methods_page
    ) {
        $req_data = $payment_methods_page->get_request_data();
        $pm_slug  = null;
        $success  = false;
        if (isset($req_data['payment_method_type'])) {
            EE_Registry::instance()->load_lib('Payment_Method_Manager');
            $payment_methods_manager = EE_Payment_Method_Manager::instance();
            $pm_type_class           = $payment_methods_manager->payment_method_class_from_type($req_data['payment_method_type']);
            if (class_exists($pm_type_class)) {
                /** @var $pm_type_obj EE_PMT_Base */
                $pm_type_obj = new $pm_type_class;
                /** @var EE_Payment_Method $pm */
                $pm              = $payment_methods_manager->create_payment_method_of_type($pm_type_obj);
                $admin_name_base = $pm->admin_name();
                $slug_base       = $pm->slug();
                $count           = 2;
                while (EEM_Payment_Method::instance()->exists(array( array( 'PMD_slug' => $pm->slug() ) ))) {
                    $pm->set_slug($slug_base . '-' . $count);
                    $pm->set_admin_name($admin_name_base . ' ' . $count ++);
                }
                $payment_methods_manager->initialize_payment_method($pm);
                $pm->set_active();
                $pm->save();
                $pm->set_available_by_default(false);
                $pm_slug = $pm->slug();
                $success = true;
            }
        }
        $payment_methods_page->redirect_after_action(
            $success,
            'Payment Method',
            'activated',
            array(
                'action'         => 'default',
                'payment_method' => $pm_slug,
            )
        );
    }



    /**
     * Route that deletes the specified payment method.
     * Yes this orphans all the payments and transactions for this payment method,
     * but ee core should be able to handle that because it's quite common for a payment
     * method from an addon to be deactivated and would similarly orphan them
     *
     * @param Payments_Admin_Page $payment_methods_page
     *
     * @throws \EE_Error
     */
    public static function ee_payment_methods_pro_delete_payment_method(Payments_Admin_Page $payment_methods_page)
    {
        $req_data = $payment_methods_page->get_request_data();
        $success  = false;
        if (isset($req_data['payment_method'])) {
            $payment_method = EEM_Payment_Method::instance()->get_one_by_slug($req_data['payment_method']);
            if ($payment_method instanceof EE_Payment_Method) {
                if (EE_Registry::instance()->is_model_name('Currency_Payment_Method')) {
                    EEM_Currency_Payment_Method::instance()->delete(
                        array(
                            array(
                                'PMD_ID' => $payment_method->ID(),
                            ),
                        ),
                        // don't allow blocking. So this could orphan transactions and payments but oh well,
                        // EE should be able to handle that
                        false
                    );
                }
                // delete related currencies
                EEM_Extra_Meta::instance()->delete(array(
                    array(
                        'EXM_type' => 'Payment_Method',
                        'OBJ_ID'   => $payment_method->ID(),
                    ),
                ));
                $payment_method->delete();
                $success = true;
            }
        }
        $payment_methods_page->redirect_after_action(
            $success,
            'Payment Method',
            'deleted',
            array( 'action' => 'default' )
        );
    }



    /**
     * Used instead of normal payment method activation route because
     * we want to look for the specific payment method slug
     *
     * @param Payments_Admin_Page $payment_methods_page
     *
     * @throws EE_Error
     */
    public static function ee_payment_methods_pro_activate_payment_method(Payments_Admin_Page $payment_methods_page)
    {
        $req_data = $payment_methods_page->get_request_data();
        $slug     = isset($req_data['payment_method_slug']) ? $req_data['payment_method_slug'] : '';
        $type     = isset($req_data['payment_method_type']) ? $req_data['payment_method_type'] : '';
        // if there's already a payment method of the correct type and slug, just reactivate it
        $payment_method = EEM_Payment_Method::instance()->get_one(
            array(
                array(
                    'PMD_slug' => $slug,
                    'PMD_type' => $type,
                ),
            )
        );
        if ($payment_method instanceof EE_Payment_Method) {
            EE_Registry::instance()->load_lib('Payment_Method_Manager');
            $payment_method->set_active();
            $payment_method->save();
            $payment_method->set_available_by_default(true);
            $payment_methods_page->redirect_after_action(
                1,
                'Payment Method',
                'activated',
                array(
                    'action'         => 'default',
                    'payment_method' => $payment_method->slug(),
                )
            );
        }
        // if the slug didn't find a payment method, fallback to the old way of looking
        // if the payment method slug
        $payment_method_type = sanitize_text_field($req_data['payment_method_type']);
        // see if one exists
        EE_Registry::instance()->load_lib('Payment_Method_Manager');
        $payment_method = EE_Payment_Method_Manager::instance()
                                                   ->activate_a_payment_method_of_type($payment_method_type);
        if ($payment_method instanceof EE_Payment_Method) {
            $payment_methods_page->redirect_after_action(
                1,
                'Payment Method',
                'activated',
                array(
                    'action'         => 'default',
                    'payment_method' => $payment_method->slug(),
                )
            );
        } else {
            $payment_methods_page->redirect_after_action(
                false,
                'Payment Method',
                'activated',
                array( 'action' => 'default' )
            );
        }
    }
}
