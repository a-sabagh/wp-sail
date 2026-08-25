<?php

/*
 * Plugin Name: WP Sail plugin boilerplate
 * Description: ⛵ wordpress plugin boilerplate for Api and Web with custom service provider
 * Version: 1.0.0
 * Requires PHP: 8.2
 * Author: Abolfazl Sabagh
 * Author URI: http://asabagh.ir
 * Text Domain: wp-sail
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define("WPSAIL_PRU", plugin_basename(__FILE__));
define("WPSAIL_PDU", plugin_dir_url(__FILE__));
define("WPSAIL_PRT", basename(__DIR__));
define("WPSAIL_PDP", plugin_dir_path(__FILE__));
define("WPSAIL_FILE", __FILE__);

require_once WPSAIL_PDP . 'vendor/autoload.php';

global $wpsail;

$wpsail = new WPSail\App();
