<?php

/*
 * Plugin Name: woocommerce plugin boilerplate
 * Description: description
 * Version: 5.1.0
 * Author: Abolfazl Sabagh
 * Author URI: http://asabagh.ir
 * Text Domain: wore
 */

if (!defined('ABSPATH')) {
    exit;
}

define("WOAP_FILE", __FILE__);
define("WOAP_PRU", plugin_basename(__FILE__));
define("WOAP_PDU", plugin_dir_url(__FILE__));
define("WOAP_PRT", basename(__DIR__));
define("WOAP_PDP", plugin_dir_path(__FILE__));
define("WOAP_TMP", WOAP_PDP . "public/");
define("WOAP_ADM", WOAP_PDP . "admin/");

require_once trailingslashit(__DIR__) . "includes/helpers.php";
require_once trailingslashit(__DIR__) . "includes/Activation.php";
add_action('woocommerce_loaded','woocommerce_application_init',100);
