<?php

// Exit if accessed directly
defined('ABSPATH') or die();


// Process file upload and insert codes into database
function getUploadFile() {
    global $wpdb;

    $allowedfileExtensions = array('txt', 'csv');
    $upload_errors = array(
        UPLOAD_ERR_OK         => 'No errors.',
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    );

    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['fileUpload']['tmp_name'];
        $fileName = $_FILES['fileUpload']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $existing_code1 = '';

        if (in_array($fileExtension, $allowedfileExtensions)) {
            $uploadFileDir = wp_upload_dir()['path'] . '/';
            $dest_path = $uploadFileDir . md5(time() . $fileName) . '.' . $fileExtension;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $file = fopen($dest_path, "r");

                if ($file !== FALSE) {
                    $lineNumber = 0;
                    $table_name = $wpdb->prefix . 'download_codes';

                    while (($line = fgets($file)) !== FALSE) {
                        $lineNumber++;

                        // Skip header rows from Bandcamp download code export file (first 11 lines)
                        if ($lineNumber >= 12) {
                            $code = trim($line);
                            // Fix: Use $wpdb->prepare() to safely interpolate $code into SQL query
                            $existing_code = $wpdb->get_var($wpdb->prepare("SELECT code FROM $table_name WHERE code = %s", $code));

                            if ($existing_code === null) {
                                $wpdb->insert($table_name, array('code' => $code));
                            } else {
                                $existing_code1 = 'Codes already exist in the table.';
                            }
                        }
                    }

                    fclose($file);
                    echo "File uploaded and processed successfully.<br />";
                    echo $existing_code1; // Echoing only once after processing

                } else {
                    echo "Error opening the file.";
                }
            } else {
                echo "There was an error moving the uploaded file.";
            }
        } else {
            echo "Upload failed. Allowed file types: " . implode(', ', $allowedfileExtensions);
        }
    } else {
        echo "There was some error in the file upload. Error: " . $upload_errors[$_FILES['fileUpload']['error']];
    }
}


add_action('admin_post_nopriv_process_file_upload', 'getUploadFile');
add_action('admin_post_process_file_upload', 'getUploadFile');

// Update the first available row in download_codes table with email and status
function update_first_available_row_with_email_and_status($fields, $entry, $form_data, $entry_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'download_codes';
    $email = isset($fields['2']['value']) ? $fields['2']['value'] : '';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE status = %d ORDER BY id ASC LIMIT 1", 0));

        if ($row) {
            $current_timestamp = time();

            $wpdb->update(
                $table_name,
                array(
                    'email'         => $email,
                    'status'        => 1,
                    'date_redeemed' => $current_timestamp
                ),
                array('id' => $row->id),
                array('%s', '%d', '%d'),
                array('%d')
            );

            $download_code = $row->code;
            $subject = 'Thank You For Signing Up For The Newsletter';

            // NOTE: Customize email content, URLs, and sender addresses for your use case
            $message = '
    <html>
    <head>
        <style>
            /* Mimic WordPress email styles */
            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                line-height: 1.6;
                background-color: #f7f7f7;
                padding: 20px;
            }
            .email-container {
                background-color: #ffffff;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
                margin: 0 auto;
                max-width: 600px;
            }
            .footer {
                margin-top: 20px;
                color: #999999;
                font-size: 12px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <h2>Your download code is: <strong>' . $download_code . '</strong></h2>
            <p></p>
            <p>You can download the album <a href="https://elektragaaz.bandcamp.com/yum">here</a>.</p>
            <p>Thank you!</p>
            <p class="footer">Sent from <a href="https://elektragaaz.com">Elektragaaz</a></p>
        </div>
    </body>
    </html>
';

            $headers[] = 'From: Elektragaaz <noreply@elecktragaaz.com>';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';

            wp_mail($email, $subject, $message, $headers);
        }
    }
}

add_action('wpforms_process_complete', 'update_first_available_row_with_email_and_status', 10, 4);

/**
 * Generate and display the download codes table if the table exists and is not empty.
 */
function getDownloadTable() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'download_codes';

    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

    if (!$table_exists) {
        echo '<p>Download codes table does not exist.</p>';
        return;
    }

    // Retrieve all rows with status 1 (redeemed codes) and count of status 0 (available codes)
    $results = $wpdb->get_results("SELECT * FROM $table_name WHERE status = '1'");
    $results1 = $wpdb->get_results("SELECT * FROM $table_name ");
    $status_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = '0'");

    // Handle form submissions to update status
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = isset($_POST['proposal']) ? intval($_POST['proposal']) : (isset($_POST['delete']) ? intval($_POST['delete']) : 0);
        $status = isset($_POST['proposal']) ? '1' : (isset($_POST['delete']) ? '3' : '0');

        if ($id && $status) {
            $wpdb->update($table_name, array('status' => $status), array('id' => $id));
        }
    }

    // Display the download codes table
    ?>
    <h1>Download Codes Emailed (<?=esc_html($status_count);?> remaining)</h1>
    <?php if (!empty($results1)) : ?>
        <table id="codes" class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Email</th>
                <th>Date Added</th>
                <!-- <th>Status</th> -->
            </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $row) :
                // Set the timezone to your local timezone
                date_default_timezone_set('America/New_York');
                // Format the timestamp into desired format
                $formatted_date_time = date('m/d/Y h:ia', $row->date_redeemed);
                ?>
                <tr class="download-row">
                    <td><span class="subtitle"><?php echo esc_html($row->id); ?></span></td>
                    <td><?php echo esc_html(ucwords($row->code)); ?></td>
                    <td><?php echo esc_html($row->email); ?></td>
                    <td><?php echo esc_html($formatted_date_time); ?></td>
                    <!-- <td><?php echo esc_html($row->status); ?></td> -->
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
   
        <p>No download codes found.</p>
    <?php endif;
}

// Shortcode for displaying the download codes table
add_shortcode('DownloadTable', 'getDownloadTable');

function getDlCode() {
    $next_available_row = '';
    global $wpdb;
    $table_name = $wpdb->prefix . 'download_codes';

    if ( is_page( 'thank-you' ) ) {
        // This code will execute if the current page has the slug thank you - Fan Page
        // Prepare the SQL query
        $query = $wpdb->prepare("
        SELECT * 
        FROM $table_name 
        WHERE status = %d 
        ORDER BY id DESC 
        LIMIT 1
    ", 1);
    } else {
        // This code will execute for all other pages
        // Prepare the SQL query
        $query = $wpdb->prepare("
        SELECT * 
        FROM $table_name 
        WHERE status = %d 
        ORDER BY id ASC 
        LIMIT 1
    ", 0);
    }

    // Execute the query
    $next_available_row = $wpdb->get_row($query);

    if ($next_available_row !== null) {
        // Return the download code
        return $next_available_row->code;
    } else {
        return 'No available code. Please contact us to get one.';
    }
}

// WP Forms Newsletter Signup Form

if ( class_exists( 'WPForms_Template', false ) ) :

    /**
     * Newsletter Signup Form Template for WPForms.
     */
    class WPForms_Template_newsletter_signup_form extends WPForms_Template {

        /**
         * Primary class constructor.
         *
         * @since 1.0.0
         */
        public function init() {
            // Template name
            $this->name = 'Newsletter Signup Form';

            // Template slug
            $this->slug = 'newsletter_signup_form';

            // Template description
            $this->description = '';

            // Template field and settings
            $this->data = array(
                'fields' => array(
                    2 => array(
                        'id' => '2',
                        'type' => 'email',
                        'label' => 'Email',
                        'required' => '1',
                        'size' => 'large',
                        'placeholder' => 'Enter Email Address',
                        'label_hide' => '1',
                    ),
                ),
                'field_id' => 3,
                'settings' => array(
                    'form_title' => 'Newsletter Signup Form',
                    'submit_text' => 'SUBMIT',
                    'submit_text_processing' => 'Sending...',
                    'antispam' => '1',
                    'ajax_submit' => '1',
                    'notifications' => array(
                        2 => array(
                            'notification_name' => 'User Confirmation',
                            'email' => '{field_id="2"}',
                            'subject' => 'Thank You From Elecktragaaz',
                            'sender_name' => 'Elektragaaz',
                            'sender_address' => 'thankyou@elecktragaaz.com',
                            'replyto' => 'Noreply@elecktragaaz.com',
                            'message' => 'Thank you for Signing up for the newsletter.

',
                        ),
                        1 => array(
                            'notification_name' => 'Default Notification',
                            'email' => '{admin_email}',
                            'subject' => 'New Entry: Newsletter Signup Form',
                            'sender_name' => 'My WordPress',
                            'sender_address' => 'wordpress@elecktragaaz.com',
                            'replyto' => '{field_id="2"}',
                            'message' => '{all_fields}',
                        ),
                    ),
                    'confirmations' => array(
                        1 => array(
                            'name' => 'Default Confirmation',
                            'type' => 'redirect',
                            'message' => '<p>Thanks for signing up for the newsletter! We\'ll be in touch soon.</p>',
                            'message_scroll' => '1',
                            'page' => '2',
                            'redirect' => 'https://elektragaaz.com/thank-you/',
                            'message_entry_preview_style' => 'basic',
                        ),
                    ),
                ),
                'meta' => array(
                    'template' => 'newsletter_signup_form',
                ),
            );

            // Call parent constructor to initialize the template
            parent::init();
        }
    }

// Instantiate the template
    new WPForms_Template_newsletter_signup_form();

endif;



add_action('template_redirect', 'restrict_page_based_on_referrer');

function restrict_page_based_on_referrer() {
    // Replace with the page ID or slug you want to restrict
    $restricted_page_id = 51; // Page ID of the page you want to restrict
    $restricted_page_slug = 'thank-you'; // Alternatively, use the page slug

    // Check if the current page is the restricted page
    if (is_page($restricted_page_id) || is_page($restricted_page_slug)) {
        // Check the HTTP referer to see if it’s from within the same site
        $allowed_referrer = parse_url(home_url(), PHP_URL_HOST);
        $current_referrer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';

        // Check if the referrer is the same as the site's domain or it's an empty string (direct access)
        if ($current_referrer !== $allowed_referrer) {
            // Redirect to a specific page or show a 403 Forbidden message
            wp_redirect(home_url()); // Redirect to homepage or a custom page
            exit();
        }
    }
}
