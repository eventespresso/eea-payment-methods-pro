<p>
    <?php _e( 'Select which payment methods will be usable when buying tickets for this event.', 'event_espresso' ); ?>
    <?php _e( 'Only one payment method of each type can be usable on an event at a time.', 'event_espresso' ); ?>
</p><?php
/* 
 * Shows a list of all the event-specific payment methods which will always be usable by this event
 */
if ( empty( $form_input_html ) ) { ?>
    <p><?php _e( 'No Event-Specific payment methods active. Please activate a payment method and set it as usable for "Only Specific Events"',
                'event_espresso' ); ?></p>
<?php } else {
    echo $form_input_html;
}

