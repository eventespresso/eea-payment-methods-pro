<?php
/* @var $config EE_Payment_Methods_Pro_Config */
?>
<div class="padding">
	<h4>
		<?php _e('Payment_Methods_Pro Settings', 'event_espresso'); ?>
	</h4>
	<table class="form-table">
		<tbody>

			<tr>
				<th><?php _e("Reset Payment_Methods_Pro Settings?", 'event_espresso');?></th>
				<td>
					<?php echo EEH_Form_Fields::select( __('Reset Payment_Methods_Pro Settings?', 'event_espresso'), 0, $yes_no_values, 'reset_payment_methods_pro', 'reset_payment_methods_pro' ); ?><br/>
					<span class="description">
						<?php _e('Set to \'Yes\' and then click \'Save\' to confirm reset all basic and advanced Event Espresso Payment_Methods_Pro settings to their plugin defaults.', 'event_espresso'); ?>
					</span>
				</td>
			</tr>

		</tbody>
	</table>

</div>

<input type='hidden' name="return_action" value="<?php echo $return_action?>">

