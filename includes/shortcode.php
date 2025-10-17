<?php
/**
 * Generate and display the download codes table if the table exists and is not empty.
 */
function getDownloadTable() {
    global $wpdb;

    $table_name = wdj_dc_table();

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
        <table id="codes" class="wp-list-table widefat fixed striped table-view-list posts" >
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