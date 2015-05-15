<?php
/*
  Plugin Name: Event Espresso - Payment Methods Pro (EE4.6+)
  Plugin URI: http://www.eventespresso.com
  Description: The Event Espresso Payment Methods Pro allow you to make some payment methods ONLY be usable when registering for specific events. Just activate a payment method, then update it so it's only usable in the admin, then add a postmeta/custom field to the event you want it to be used on with the key "include_payment_method", and the value of the payment method's ADMIN name, et voila! You can repeat this for other events, and for other payment methods on the same event by adding more postmetas/custom fields also named "include_payment_method".
  Version: 1.0.0.dev.000
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
define( 'EE_PAYMENT_METHODS_PRO_VERSION', '1.0.0.dev.000' );
define( 'EE_PAYMENT_METHODS_PRO_PLUGIN_FILE',  __FILE__ );
function load_espresso_payment_methods_pro() {
if ( class_exists( 'EE_Addon' )) {
	// payment_methods_pro version
	require_once ( plugin_dir_path( __FILE__ ) . 'EE_Payment_Methods_Pro.class.php' );
	EE_Payment_Methods_Pro::register_addon();
}
}
add_action( 'AHEE__EE_System__load_espresso_addons', 'load_espresso_payment_methods_pro' );

// End of file espresso_payment_methods_pro.php
// Location: wp-content/plugins/eea-payment-methods-pro/espresso_payment_methods_pro.php