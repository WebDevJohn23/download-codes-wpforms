<?php
/**
 * Plugin Name: Download Codes
 * Description: Allow users to get a download code when they sign up for the newsletter.
 * Version: 1.0
 * Author: Johnathan Julig
 * License: GPLv2 or later
 * Text Domain: downloadcodes
 */

// Exit if accessed directly
defined('ABSPATH') or die();

// Enable error reporting for debugging purposes
 ini_set('display_errors', 0); // Set to 1 for debugging
 error_reporting(E_ALL);

// Includes
require_once(__DIR__ . '/settings.php');
require_once(__DIR__ . '/functions.php');


// Add settings link on plugin page
function download_codes_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=download-search">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin_basename = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin_basename", 'download_codes_settings_link');

// Function to create or upgrade database tables
function download_codes_options_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'download_codes';

    // Check if table exists and create if not
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(2048) NOT NULL,
            `email` varchar(255) NOT NULL,
            `date_redeemed` int(11) NOT NULL,
            `status` int(10) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'download_codes_options_install');
