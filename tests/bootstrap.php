<?php
/**
 * Bootstrap for eea-payment-methods-pro tests
 */

use EETests\bootstrap\AddonLoader;

$core_tests_dir = dirname(dirname(dirname(__FILE__))) . '/event-espresso-core/tests/';
require $core_tests_dir . 'includes/CoreLoader.php';
require $core_tests_dir . 'includes/AddonLoader.php';

define('EEA_PAYMENT_METHODS_PRO_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');
define('EEA_PAYMENT_METHODS_PRO_TESTS_DIR', EEA_PAYMENT_METHODS_PRO_PLUGIN_DIR . 'tests/');


$addon_loader = new AddonLoader(
    EEA_PAYMENT_METHODS_PRO_TESTS_DIR,
    EEA_PAYMENT_METHODS_PRO_PLUGIN_DIR,
    'eea-payment-methods-pro.php'
);
$addon_loader->init();
