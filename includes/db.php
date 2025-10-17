<?php
if ( ! defined('ABSPATH') ) exit;

function wdj_dc_table() {
    global $wpdb;
    return $wpdb->prefix . 'download_codes';
}