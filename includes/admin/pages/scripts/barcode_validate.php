<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

//Define tables
$table_box = $wpdb->prefix . "wpsc_epa_boxinfo";
$table_scan_list = $wpdb->prefix . "wpsc_epa_scan_list";

if(isset($_POST['postvarsbarcode'])){
   $barcode = $_POST['postvarsbarcode'];

if ( ($box_status == $validation_tag->term_id || $box_status == $rescan_tag->term_id) && !in_array($box_ticket_status, $rescan_validate_status_id_arr)) {

if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $barcode)){

$get_folderdocinfo_id = $wpdb->get_row("SELECT id, box_id
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '".$barcode."'");

$get_folderdocinfo_id_val = $get_folderdocinfo_id->id;
$get_folderdocinfo_box_id_val = $get_folderdocinfo_id->box_id;

$box_details = $wpdb->get_row("SELECT c.name, a.id, a.box_previous_status, a.box_status, a.box_destroyed, b.request_id as request_id, a.box_id as box_id, a.ticket_id as ticket_id, b.ticket_priority as ticket_priority, 
(SELECT name as ticket_priority FROM " . $wpdb->prefix . "terms WHERE term_id = b.ticket_priority) as ticket_priority_name, b.ticket_status as ticket_status, 
(SELECT name as ticket_status FROM " . $wpdb->prefix . "terms WHERE term_id = b.ticket_status) as ticket_status_name
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON b.id = a.ticket_id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.box_status
WHERE a.id = '" . $get_folderdocinfo_box_id_val . "'");

$box_status = $box_details->box_status;
$box_ticket_status = $box_details->ticket_status;
$rescan_validate_status_id_arr = array($new_request_tag->term_id, $tabled_tag->term_id, $initial_review_rejected_tag->term_id, $cancelled_tag->term_id, $completed_dispositioned_tag->term_id);

$data_update = array('validation' => 1);
$data_where = array('id' => $get_folderdocinfo_id_val);
$wpdb->update($wpdb->prefix.'wpsc_epa_folderdocinfo_files', $data_update, $data_where);

echo $barcode.' has been set to validated.';

//FAIL for all other barcodes       
} else {
echo 'Please enter a valid barcode.';
}

} else {
echo 'Please ensure the box and/or request statuses are correct.';
}

} else {
   echo "Lookup not successful.";
}
?>