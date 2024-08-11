<?php

/**
 * Plugin Name: Wholesale module for WooCommerce
 * Description: Adds wholesale pricing and related functionality to WooCommerce.
 * Version: 1.0.0
 * Author: Prit Bhuva
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('WHOLESALE_PLUGIN_FILE', __FILE__);

/**
 * Include the class file.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-wwd-wholesale-module.php';

/**
 * Check if the WooCommerce plugin is installed and activated.
 */
$plugin_path = trailingslashit(WP_PLUGIN_DIR) . 'woocommerce/woocommerce.php';

if (in_array($plugin_path, wp_get_active_and_valid_plugins())) {
    // Initialize the plugin
    new WWD_Wholesale_Module();
} else {
    // Display an admin notice if the woocommerce plugin is not installed and activated
    add_action('admin_notices', 'wwd_admin_notice');
}

/**
 * Displays an admin notice if the WooCommerce plugin is not installed and activated.
 *
 * @return void
 */
if (!function_exists('wwd_admin_notice')):
    function wwd_admin_notice()
    {
?>
        <div class="notice notice-warning is-dismissible">
            <p><?php _e('Wholesale module for WooCommerce requires WooCommerce to be installed and activated.', 'woocommerce'); ?></p>
        </div>
<?php
    }
endif;

/**
 * Plugin activation function.
 */
if (!function_exists('wwd_activate_plugin')):
    function wwd_activate_plugin()
    {
        // Add code which you want to perform on plugin activation
    }
    register_activation_hook(__FILE__, 'wwd_activate_plugin');
endif;
