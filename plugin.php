<?php

/*
 * Plugin Name: WP Sail plugin boilerplate
 * Description: booking hotel and trip deployed on woocommerce core and extensions
 * Version: 5.5.0
 * Author: Abolfazl Sabagh
 * Author URI: http://asabagh.ir
 * Text Domain: sail
 */

if (!defined('ABSPATH')) {
    exit;
}

define("SAIL_PRU", plugin_basename(__FILE__));
define("SAIL_PDU", plugin_dir_url(__FILE__));
define("SAIL_PRT", basename(__DIR__));
define("SAIL_PDP", plugin_dir_path(__FILE__));
define("SAIL_TMP", SAIL_PDP . "resources/views/public/");
define("SAIL_ADM", SAIL_PDP . "resources/views/admin/");
define("SAIL_FILE", __FILE__);
define("SAIL_VIEW", trailingslashit(plugin_dir_path(__FILE__)) . 'resources/views');
define("SAIL_VIEW_URI", trailingslashit(plugin_dir_url(__FILE__)) . 'resources/views');

require_once trailingslashit(__DIR__) . "includes/helpers.php";
require_once trailingslashit(__DIR__) . "includes/Packages/Providers/TableProvider.php";
require_once trailingslashit(__DIR__) . "includes/Activation.php";
add_action('plugins_loaded', 'wp_sail_plugin_init');
