<?php

/**
 * EEME_Payment_Methods_Pro_Payment_Method extends EEM_Event so it's related to payment methods
 * over the flexible extra join table. Entries in this join table indicate an "availability exception".
 * Eg if payment method 1 is available by default on all events, and there's an
 * exception for it on event A (ie, an entry in the extra join table between
 * payment method 1 and event A), then it is NOT available on event A. It is,
 * however, available on all other events (unless they likewise are an exception).
 * The inverse is true too though: if payment method 2 is normally NOT available by default on all events,
 * but there is an exception for it on event B (ie, again an entry in the extra join
 * table between payment method 2 and event B), then it IS available on just this event.
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 */
class EEME_Payment_Methods_Pro_Payment_Method extends EEME_Base
{

    /**
     * @throws \EE_Error
     */
    public function __construct()
    {
        $this->_model_name_extended = 'Payment_Method';
        $this->_extra_relations = array(
            'Event' => new EE_HABTM_Any_Relation(),
        );
        parent::__construct();
    }



    /**
     * Gets all payment methods which are available for use on these events, factoring
     * in which are normally available and which aren't, and for which ones to make an exception
     *
     * @param int                 $event_id
     * @param EE_Payment_Method[] $payment_methods the list of default payment methods;
     *                                             if not provided we'll find it ourselves
     *                                             (so providing this only improves efficiency)
     *
     * @return EE_Payment_Method[]
     * @throws EE_Error
     */
    public function ext_get_payment_methods_available_for_event($event_id, $payment_methods = array())
    {
        if (empty($payment_methods)) {
            $payment_methods = EEM_Payment_Method::instance()->get_all_active(EEM_Payment_Method::scope_cart);
        }
        // remove payment methods which shouldn't be available by default
        foreach ((array) $payment_methods as $key => $payment_method) {
            if (! $payment_method->is_available_by_default()) {
                unset($payment_methods[ $key ]);
            }
        }
        // now let's find all the exceptions to normal payment method availability.
        // by "exception" I mean a payment method that's normally available on all events, but
        // isn't for one of these events; or a payment method that's normally NOT available,
        // but IS for one of these events.
        $payment_method_availability_exceptions = (array) $this->_->get_payment_method_availability_exceptions($event_id);
        // ok so if a payment method is normally available, but it's an exception, then it's now NOT available. Remove it.
        foreach ($payment_method_availability_exceptions as $payment_method_id => $on_by_default) {
            // assume $payment_methods is indexed by primary keys, which currently it is (the only time
            // it isn't is when the model has no primary key)
            if ($on_by_default) {
                // it's normally available, but we're making an exception. So it shouldn't be available
                unset($payment_methods[ $payment_method_id ]);
            } else {
                // it's normally NOT available, and we're making an exception. So it SHOULD be available
                // so add it back in. This isn't actually that terribly inefficient because we
                // already fetched it from the DB (it was in $payment_methods at the start of this method,
                // but it got removed) so we're just fetching it from the entity map, not making
                // another trip to the DB
                $payment_method_not_normally_available = EEM_Payment_Method::instance()
                                                                           ->get_one_by_ID($payment_method_id);
                // double-check the payment method is actually usable from the frontend
                if (in_array(EEM_Payment_Method::scope_cart, $payment_method_not_normally_available->scope())) {
                    $payment_methods[ $payment_method_id ] = $payment_method_not_normally_available;
                }
            }
        }

        return apply_filters(
            'FHEE__EEME_Payment_Methods_Pro_Payment_Method__ext_get_payment_methods_available_for_event',
            $payment_methods,
            $event_id,
            $this
        );
    }



    /**
     * Gets a mapping array, where keys are payment method IDs, and values
     * are whether or not they're available by default on all events
     *
     * @param array $query_params
     *
     * @return array keys are payment method IDs, and values are whether or not they're available
     * by default on all events
     * @throws EE_Error
     */
    public function ext_get_payment_method_default_availabilities($query_params)
    {
        $payment_method_ids = (array) $this->_->get_col($query_params);
        $availabilities     = EEM_Extra_Meta::instance()->get_all(
            array(
                array(
                    'EXM_key' => EED_Payment_Methods_Pro_More_Payment_Methods::on_by_default_meta_key,
                ),
            )
        );
        $mapping            = array();
        foreach ($payment_method_ids as $payment_method_id) {
            // if there is no extra meta row for "on_by_default", it was probably activated before PMP,
            // and in that case it WAS available by default on all events, so maintain that behaviour.
            // Logic effectively duplicated in EEE_Payment_Methods_Pro_Payment_Method::ext_is_available_by_default()
            $available = true;
            foreach ($availabilities as $extra_meta_obj) {
                if ($extra_meta_obj->get('OBJ_ID') === intval($payment_method_id)) {
                    $available = $extra_meta_obj->get('EXM_value') === '1' ? true : false;
                    break;
                }
            }
            $mapping[ $payment_method_id ] = $available;
        }

        return $mapping;
    }



    /**
     * Gets the IDs of teh payment methods which are specific to this event
     *
     * @param string $event_id
     *
     * @return array keys are payment method IDs, keys are whether or not they should be on by default
     * @throws EE_Error
     */
    public function ext_get_payment_method_availability_exceptions($event_id)
    {
        // So let's look for rows in the extra_join table, indicating they're an exception
        // (by "exception" I mean a payment method that's normally available but isn't for this event, or
        // a payment method which normally is NOT available, but is for this event).
        $pm_exceptions = (array) $this->_->get_col(
            array(
                array(
                    'Event.EVT_ID' => $event_id,
                ),
            )
        );
        // now that we know all the payment methods that are exceptions for this event, find whether they're
        // normally available or not.
        return $this->ext_get_payment_method_default_availabilities(
            array(
                array(
                    'PMD_ID' => array( 'IN', $pm_exceptions ),
                ),
            )
        );
    }
}
