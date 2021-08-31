<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['postvarsfolderdocid'])
){


$folderdocid_string = $_POST['postvarsfolderdocid'];
$folderdocid_arr = explode (",", $folderdocid_string); 
$page_id = $_POST['postvarpage'];
$box_id = $_POST['boxid'];

$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$table_timestamp = $wpdb->prefix . 'wpsc_epa_timestamps_folderfile';
// Define current time
$date_time = date('Y-m-d H:i:s');

$destroyed = 0;
$unathorized_destroy = 0;
$freeze_reversal = 0;
$freeze_approval = 0;

$ticket_request_status = 0;
$ticket_box_status = 0;

//Box Statuses
$box_pending_tag = get_term_by('slug', 'pending', 'wpsc_box_statuses'); //748
$box_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
$box_cancelled_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses'); //1057

//Request statuses
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003

$status_id_arr = array($initial_review_rejected_tag->term_id, $cancelled_tag->term_id, $completed_dispositioned_tag->term_id);
$box_status_id_arr = array($box_pending_tag->term_id, $box_completed_dispositioned_tag->term_id, $box_cancelled_tag->term_id);

foreach($folderdocid_arr as $key) {

$get_statuses = $wpdb->get_row("SELECT a.ticket_status, b.box_status
FROM " . $wpdb->prefix . "wpsc_ticket a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
WHERE c.folderdocinfofile_id = '".$key."'");
$get_ticket_status_val = $get_statuses->ticket_status;
$get_box_status_val = $get_statuses->box_status;

//Documents cannot be marked as Freeze in these request statuses
if( in_array($get_ticket_status_val, $status_id_arr) ) {
   $ticket_request_status++; 
}

//Documents cannot be marked as Freeze in these box statuses
if( in_array($get_box_status_val, $box_status_id_arr)) {
    $ticket_box_status++;
}

// AY FIX JOIN
$get_destroyed = $wpdb->get_row("SELECT b.box_destroyed as box_destroyed 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a 
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id
WHERE a.freeze = 0 AND a.folderdocinfofile_id = '".$key."'");
$get_destroyed_val = $get_destroyed->box_destroyed;

if ($get_destroyed_val == 1) {
$destroyed++;
}
}

foreach($folderdocid_arr as $key) {
$get_unathorized_destroy = $wpdb->get_row("SELECT unauthorized_destruction 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_unathorized_destroy_val = $get_unathorized_destroy->unauthorized_destruction;

if ($get_unathorized_destroy_val == 1) {
$unathorized_destroy++;
}
}


foreach($folderdocid_arr as $key) {

// AY FIX JOIN
$get_freeze_approval = $wpdb->get_row("SELECT c.freeze_approval as freeze_approval 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id 
LEFT JOIN " . $wpdb->prefix . "wpsc_ticket c ON b.ticket_id = c.id
WHERE a.folderdocinfofile_id = '".$key."'");
$freeze_approval_val = $get_freeze_approval->freeze_approval;

if ($freeze_approval_val == 1) {
$freeze_approval++;
}
}

$folderdocid_arr_count = count($folderdocid_arr);


if(($page_id == 'boxdetails' || $page_id == 'folderfile') && $ticket_request_status == 0 && $ticket_box_status == 0 && $destroyed == 0 && $unathorized_destroy == 0 && $freeze_approval == $folderdocid_arr_count) {
foreach($folderdocid_arr as $key) {    
$get_freeze = $wpdb->get_row("SELECT freeze FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_freeze_val = $get_freeze->freeze;

$get_request_id = substr($key, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

if ($get_freeze_val == 1){
$freeze_reversal = 1;
$data_update = array('freeze' => 0);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_freeze_unflag', $ticket_id, $key);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($key);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Freeze' ");
$folderfile_timestamp_id = $get_folderfile_timestamp->id;

// Delete previous value
if(!empty($folderfile_timestamp_id)) {
  $wpdb->delete( $table_timestamp, array( 'id' => $folderfile_timestamp_id ) );
}

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);

}

if ($get_freeze_val == 0){
$data_update = array('freeze' => 1);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_freeze', $ticket_id, $key);

// Check to see if timestamp exists
$type = 'Freeze';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($key), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);
    
}

}

} elseif($destroyed > 0) {
echo " A destroyed folder/file has been selected and cannot be frozen.<br />Please unselect the destroyed folder/file. ";
} elseif($unathorized_destroy > 0) {
echo " A folder/file flagged as unauthorized destruction has been selected and cannot be frozen.<br />Please unselect the folder/file flagged as unauthorized destruction folder/file. ";
} elseif($freeze_approval == 0) {
echo " A folder/file flagged does not contain a litigation approval letter.<br />Please unselect the folder/file flagged as not containing a litigiation approval letter. ";
}
elseif($ticket_request_status > 0) {
    echo " A folder/file is in the:
    <ol>
        <li>Initial Review Rejected or</li>
        <li>Completed/Dispositioned</li>
    </ol>
    request status and cannot be flagged as freeze.<br /> Please review the folder/files that you have selected. ";
}
elseif($ticket_box_status > 0) {
    echo " A folder/file is in the:
    <ol>
        <li>Pending</li>
        <li>Completed/Dispositioned or</li>
        <li>Cancelled</li>
    </ol>
    box status and cannot be flagged as freeze.<br /> Please review the folder/files that you have selected. ";
}

if($page_id == 'filedetails') {

$get_freeze = $wpdb->get_row("SELECT freeze FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$folderdocid_string."'");

$get_freeze_val = $get_freeze->freeze;

$get_request_id = substr($folderdocid_string, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id, freeze_approval FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;
$filedetails_freeze_approval = $get_ticket_id->freeze_approval;

//freeze_approval was not being checked on the file details page before, would set the freeze flag with no constraints
if($filedetails_freeze_approval == 1) {

if ($get_freeze_val == 1){
$freeze_reversal = 1;
$data_update = array('freeze' => 0);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_freeze_unflag', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Freeze' ");
$folderfile_timestamp_id = $get_folderfile_timestamp->id;

// Delete previous value
if(!empty($folderfile_timestamp_id)) {
  $wpdb->delete( $table_timestamp, array( 'id' => $folderfile_timestamp_id ) );
}

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name, $data_update, $data_where);

}

if ($get_freeze_val == 0){
$data_update = array('freeze' => 1);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_freeze', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$type = 'Freeze';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name, $data_update, $data_where);

}
}
}

if ($freeze_reversal == 1 && $ticket_request_status == 0 && $ticket_box_status == 0 && $destroyed == 0 && $unathorized_destroy == 0 && $freeze_approval == $folderdocid_arr_count) {
echo " Freeze has been updated. A Freeze flag has been reversed. ";
} elseif ($freeze_reversal == 0 && $ticket_box_status == 0 && $ticket_request_status == 0 && $destroyed == 0 && $unathorized_destroy == 0 && $freeze_approval == $folderdocid_arr_count) {
echo " Freeze has been updated. ";
}

} else {
   echo "Please select one or more items to mark as frozen.";
}
?>