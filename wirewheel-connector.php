<?php
/*
Plugin Name: Wirewheel Connector
Version: 1.0
Author: Nitesh Sethia
Author URI: https://www.wirewheel.io/
Description: Wirewheel connector to render the Wirewheel forms easily.
License:GPL2
License URI:https://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) {
    exit;
}

// Make sure we don't expose any info if called directly.

if (!function_exists('add_action') ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('WWC_VERSION', '5.5.6');

define('WWC_REQUIRED_WP_VERSION', '5.7');

define('WWC_PLUGIN', __FILE__);

define('WWC_PLUGIN_BASENAME', plugin_basename(WWC_PLUGIN));

define('WWC_PLUGIN_DIR', untrailingslashit(dirname(WWC_PLUGIN)));

require_once WWC_PLUGIN_DIR . '/load.php';
