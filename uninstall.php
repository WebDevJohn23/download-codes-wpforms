<?php
// Runs only on plugin uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

require_once __DIR__ . '/includes/db.php'; // wdj_dc_table()

$purge = get_option('wdj_dc_purge_on_uninstall', '0');
if ($purge !== '1') return;

global $wpdb;

$table = wdj_dc_table(); // wp_{prefix}wdj_movies_data
if (preg_match('/^[A-Za-z0-9_]+$/', $table)) {
    $wpdb->query("DROP TABLE IF EXISTS `$table`");
}

delete_option('wdj_dc_purge_on_uninstall');
