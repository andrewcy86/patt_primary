<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

//Define tables
$table_box = $wpdb->prefix . "wpsc_epa_boxinfo";
$table_scan_list = $wpdb->prefix . "wpsc_epa_scan_list";
$storage_location_table = $wpdb->prefix . 'wpsc_epa_storage_location';

if(isset($_POST['postvarsbarcode']) && isset($_POST['postvarsuser'])){
   $barcode = $_POST['postvarsbarcode'];
   $user_id = $_POST['postvarsuser'];

$get_folderdocinfo_id = $wpdb->get_row("SELECT id, box_id, object_key
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '".$barcode."'");

$get_folderdocinfo_id_val = $get_folderdocinfo_id->id;
$get_folderdocinfo_box_id_val = $get_folderdocinfo_id->box_id;

$box_details = $wpdb->get_row("SELECT a.box_status as box_status, b.ticket_status as ticket_status, b.id as ticket_id, a.box_id as box_id, a.storage_location_id as storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON b.id = a.ticket_id
WHERE a.id = '" . $get_folderdocinfo_box_id_val . "'");

$box_full_id = $box_details->box_id;
$box_status = $box_details->box_status;
$box_ticket_status = $box_details->ticket_status;
$box_storage_location_id = $box_details->storage_location_id;
$box_ticket_id = $box_details->ticket_id;

$rescan_validate_status_id_arr = array($request_new_request_tag->term_id, $request_tabled_tag->term_id, $request_initial_review_rejected_tag->term_id, $request_cancelled_tag->term_id, $request_completed_dispositioned_tag->term_id);

if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $barcode)){
    
if ( ($box_status == $box_validation_tag->term_id) && !in_array($box_ticket_status, $rescan_validate_status_id_arr)) {

    
$data_update = array('rescan' => 1);
$data_where = array('id' => $get_folderdocinfo_id_val);
$wpdb->update($wpdb->prefix.'wpsc_epa_folderdocinfo_files', $data_update, $data_where);

do_action('wpppatt_after_rescan_document', $box_ticket_id, $barcode);

//Perform Deletion

echo '<div class="alert alert-danger" role="alert"><i class="fas fa-times-circle" aria-hidden="true" title="Re-Scan"></i><span class="sr-only">Re-Scan</span> '.$barcode.' has been set to re-scan.</div>';

//FAIL for all other barcodes       
} else {
echo 'Please ensure the box and/or request statuses are correct.';
}

} else {
echo 'Please enter a valid folder/file ID.';
}

} else {
   echo "Lookup not successful.";
}
?>