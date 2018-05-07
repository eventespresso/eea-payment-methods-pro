<?php

/**
 * Adds functionality to payment methods
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 */
class EEE_Payment_Methods_Pro_Payment_Method extends EEE_Base_Class
{

    /**
     * EEE_Payment_Methods_Pro_Payment_Method constructor.
     *
     * @throws EE_Error
     */
    public function __construct()
    {
        $this->_model_name_extended = 'Payment_Method';
        parent::__construct();
    }



    /**
     * Sets these payment method IDs to be the only payment methods related to this event
     * (ie overwrites previous relations)
     *
     * @param boolean $on_by_default IDs of related payment methods
     *
     * @return void
     * @throws EE_Error
     */
    public function ext_set_available_by_default($on_by_default)
    {
        // if this payment method is being set to being on by default,
        // we need to ensure there are no other payment methods are on by default
        // (we think folks will only want one payment method of a given type
        // active on an event at a time; but they're allowed to have lots of
        // inactive ones of course)
        // get the previous value
        $previous_default_availability = $this->_->is_available_by_default();
        if (! $previous_default_availability
             && $on_by_default
        ) {
            // ok get the previous available-by-default payment method of this type (there should only be one at a time)
            // we'd like to use a DB query to fetch all the available-by-default ones
            // but ones which don't have the extra meta set are also implied to be available-by-default
            // and in order for MySQL to find those, it would need to use the extra meta key on the join clause
            // which currently our models don't handle
            // so instead, fetch all other payment methods of this type, and then we'll just loop over them
            $other_pms_of_this_type = EEM_Payment_Method::instance()->get_all(
                array(
                    array(
                        'PMD_type'          => $this->_->type(),
                        'Extra_Meta.OBJ_ID' => array( '!=', $this->_->ID() ),
                    ),
                )
            );
            // loop over all these PMs, looking for a different one that's on by default
            foreach ($other_pms_of_this_type as $other_pm) {
                // we'd like to just call $previous_default_payment_method->set_available_by_default( false );
                // but model extensions don't allow for recursion currently. So do the gist of it:
                // update its extra meta
                if ($other_pm->get_extra_meta(
                    EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key,
                    true,
                    true
                )
                ) {
                    $other_pm->update_extra_meta(
                        EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key,
                        false
                    );
                    // and reassign exceptions to the new default
                    EEM_Extra_Join::instance()->update(
                        array(
                            'EXJ_second_model_ID' => $this->_->ID(),
                        ),
                        array(
                            array(
                                'EXJ_first_model_name'  => 'Event',
                                'EXJ_second_model_name' => 'Payment_Method',
                                'EXJ_second_model_ID'   => $other_pm->ID(),
                            ),
                        )
                    );
                    // that might have made some double-exceptions, remove them
                    // (eg previously $this->_ was in use on event A as an exception
                    // but now that $this->_ is available by default, there is no need for it to be an exception)
                    $exj_table = EEM_Extra_Join::instance()->table();
                    global $wpdb;
                    $wpdb->query("DELETE t
                        FROM {$exj_table} t
                        INNER JOIN (SELECT EXJ_ID, EXJ_first_model_ID, EXJ_first_model_name, EXJ_second_model_ID, EXJ_second_model_name
                                   FROM   {$exj_table}
                                   GROUP  BY EXJ_first_model_ID, EXJ_first_model_name, EXJ_second_model_ID, EXJ_second_model_name
                                   HAVING COUNT(EXJ_ID) > 1) d
                               ON t.EXJ_first_model_ID = d.EXJ_first_model_ID
                               AND t.EXJ_first_model_name = d.EXJ_first_model_name
                               AND t.EXJ_second_model_ID = d.EXJ_second_model_ID
                               AND t.EXJ_second_model_name = d.EXJ_second_model_name;");
                }
            }
        } elseif ($previous_default_availability
                   && ! $on_by_default
        ) {
            // this payment method WAS available by default, but now it won't be.
            // so for events where it was in use because it was default: set none available for that event by not adding an exception
            // for events where it wasn't in use because it was an exception: stay that way (remove those exceptions)
            $this->_->remove_availability_exceptions();
        }
        $this->_->update_extra_meta(
            EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key,
            $on_by_default
        );
    }



    /**
     * Whether or not this payment method is available by default on all events
     *
     * @return bool
     * @throws EE_Error
     */
    public function ext_is_available_by_default()
    {
        // if there is no extra meta row for "on_by_default", it was probably activated before PMP,
        // and in that case it WAS available by default on all events, so maintain that behaviour.
        // Logic effectively duplicated in EEME_Payment_Methods_Pro_Payment_Method::ext_get_payment_method_default_availabilities()
        $string_val = $this->_->get_extra_meta(
            EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key,
            true,
            '1'
        );
        if ($string_val === '1') {
            return true;
        } else {
            return false;
        }
    }



    /**
     * Removes all the payment methods' availability exceptions. So if this payment
     * method should be available on all events by default, it will be. Or if
     * it should NOT be available on events by default, then it won't be.
     *
     * @return int the number of availability exceptions that were deleted
     * @throws EE_Error
     */
    public function ext_remove_availability_exceptions()
    {
        return EEM_Extra_Join::instance()->delete(
            array(
                array(
                    'EXJ_first_model_name'  => 'Event',
                    'EXJ_second_model_name' => 'Payment_Method',
                    'EXJ_second_model_ID'   => $this->_->ID(),
                ),
            )
        );
    }
}
