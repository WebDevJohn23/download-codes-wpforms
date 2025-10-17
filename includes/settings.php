<?php
// Exit if accessed directly
defined('ABSPATH') or die();

// Register admin menu
add_action('admin_menu', 'download_codes_adminmenu');
function download_codes_adminmenu() {
    add_options_page('Download Codes', 'Download Codes', 'manage_options', 'download-search', 'download_codes_adminpage');
}

// Admin page content and upload 
function download_codes_adminpage() {
    global $wpdb;

    if (isset($_POST['download']) && $_POST['download'] == '1' && check_admin_referer('download_codes_upload', 'download_codes_nonce')) {
        getUploadFile(); // Call function if form is submitted
    }

    ?>
    <div class="wrap">
        <h2>Download Codes</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('download_codes_upload', 'download_codes_nonce'); ?>
            <input type="hidden" name="download" value="1"/>
            <input type="file" name="fileUpload" required/>
            <button type="submit" class="button button-primary">Upload New Codes</button>
        </form>

    <br />
    <hr>
    <br />
    <?php
    echo do_shortcode("[DownloadTable]");


    // Danger zone: toggle purge-on-uninstall

    // Danger zone: toggle purge-on-uninstall
    if ( isset($_POST['wdj_dc_toggle_purge']) && check_admin_referer('wdj_dc_toggle_purge') ) {
        update_option('wdj_dc_purge_on_uninstall', isset($_POST['purge_confirm']) ? '1' : '0');
        echo '<div class="notice notice-success"><p>Purge on uninstall '
            . (get_option('wdj_dc_purge_on_uninstall','0')==='1' ? 'ENABLED' : 'DISABLED')
            . '.</p></div>';
    }

    echo '<br /><br /><hr><h2 style="color:#b32d2e">Danger zone</h2>';
    echo '<form method="post" onsubmit="return confirm(\'This controls table deletion on uninstall. Continue?\');">';
    wp_nonce_field('wdj_dc_toggle_purge');
    $checked = get_option('wdj_dc_purge_on_uninstall','0')==='1' ? 'checked' : '';
    echo '<label><input type="checkbox" name="purge_confirm" value="1" ' . $checked . '> ';
    echo 'Delete this table when this plugin is uninstalled.</label><br><br>';
    submit_button('Save setting', 'secondary', 'wdj_dc_toggle_purge', false);
    echo '</form></div>';


}


