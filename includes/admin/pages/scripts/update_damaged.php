<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(!empty($_POST['postvarsfolderdocid'])){

$folderdocid_string = $_POST['postvarsfolderdocid'];
$folderdocid_arr = explode (",", $folderdocid_string); 
$page_id = $_POST['postvarpage'];
$box_id = $_POST['boxid'];

$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$table_timestamp = $wpdb->prefix . 'wpsc_epa_timestamps_folderfile';
// Define current time
$date_time = date('Y-m-d H:i:s');

$damaged_reversal = 0;
$ticket_box_status = 0;
//Box status
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
$unauthorized_destruction = 0;

//Request statuses
$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69

foreach($folderdocid_arr as $key) {
//REVIEW
$get_statuses = $wpdb->get_row("SELECT a.ticket_status, b.box_status
FROM wpqa_wpsc_ticket a
INNER JOIN wpqa_wpsc_epa_boxinfo b ON b.ticket_id = a.id
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
WHERE c.damaged = 0 AND c.folderdocinfofile_id = '".$key."'");
$get_ticket_status_val = $get_statuses->ticket_status; 
$get_box_status_val = $get_statuses->box_status;

//Documents cannot be marked as Damaged in the Completed/Dispositioned box status
if($get_box_status_val == $completed_dispositioned_tag->term_id || $get_ticket_status_val == $new_request_tag->term_id || $get_ticket_status_val == $tabled_tag->term_id || $get_ticket_status_val == $initial_review_rejected_tag->term_id || $get_ticket_status_val == $cancelled_tag->term_id) {
   $ticket_box_status++; 
}

}

foreach($folderdocid_arr as $key) {
$get_unauthorized_destruction = $wpdb->get_row("SELECT unauthorized_destruction 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files 
WHERE folderdocinfofile_id = '".$key."'");
$get_unauthorized_destruction_val = $get_unauthorized_destruction->unauthorized_destruction;

//Documents cannot be marked as Damaged if they are already marked as Unauthorized Destruction
if ($get_unauthorized_destruction_val == 1) {
$unauthorized_destruction++;
}
}

//$folderdocid_arr_count = count($folderdocid_arr);

if(($page_id == 'boxdetails' || $page_id == 'folderfile') && $ticket_box_status == 0 && $unauthorized_destruction == 0) {
foreach($folderdocid_arr as $key) {

$get_damaged = $wpdb->get_row("SELECT damaged 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files 
WHERE folderdocinfofile_id = '".$key."'");
$get_damaged_val = $get_damaged->damaged;

$get_request_id = substr($key, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id 
FROM " . $wpdb->prefix . "wpsc_ticket 
WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

if ($get_damaged_val == 1){
$damaged_reversal = 1;
$data_update = array('damaged' => 0);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_damaged_unflag', $ticket_id, $key);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($key);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Damaged' ");
$folderfile_timestamp_id = $get_folderfile_timestamp->id;

// Delete previous value
if(!empty($folderfile_timestamp_id)) {
  $wpdb->delete( $table_timestamp, array( 'id' => $folderfile_timestamp_id ) );
}

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);

echo "<strong>".$key."</strong> : Damaged has been updated. A damaged flag has been reversed.<br />";
}

if ($get_damaged_val == 0){
$data_update = array('damaged' => 1);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_damaged', $ticket_id, $key);

// Check to see if timestamp exists
$type = 'Damaged';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($key), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);

echo "<strong>".$key."</strong> : Damaged has been updated.<br />";
}
}
}

elseif($unauthorized_destruction > 0) {
echo " A folder/file flagged as unauthorized destruction has been selected and cannot be flagged as damaged.<br />Please unselect the folder/file flagged as unauthorized destruction. ";
} elseif($ticket_box_status > 0) {
echo " A folder/file is in a status that cannot be flagged as damaged.<br /> Please review the folder/files that you have selected. ";
}

if( ($page_id == 'filedetails') && $ticket_box_status == 0 && $unauthorized_destruction == 0) {
$get_damaged = $wpdb->get_row("SELECT damaged 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files 
WHERE folderdocinfofile_id = '".$folderdocid_string."'");
$get_damaged_val = $get_damaged->damaged;

$get_request_id = substr($folderdocid_string, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id 
FROM " . $wpdb->prefix . "wpsc_ticket 
WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

if ($get_damaged_val == 1){
$damaged_reversal = 1;
$data_update = array('damaged' => 0);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_damaged_unflag', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Damaged' ");
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

if ($get_damaged_val == 0){
$data_update = array('damaged' => 1);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_damaged', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$type = 'Damaged';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name, $data_update, $data_where);

}
}
if (($page_id == 'filedetails') && $damaged_reversal == 1 && $ticket_box_status == 0 && $unauthorized_destruction == 0) {
echo " Damaged has been updated. A damaged flag has been reversed. ";
}
if (($page_id == 'filedetails') && $damaged_reversal == 0 && $ticket_box_status == 0 && $unauthorized_destruction == 0) {
echo " Damaged has been updated. ";
}
   
} else {
   echo "Please select one or more items to mark as damaged.";
}
?>