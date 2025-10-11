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
    </div>
    <br /><br />
    <?php
    echo do_shortcode("[DownloadTable]");
}


