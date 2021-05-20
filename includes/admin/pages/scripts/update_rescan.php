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

$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$table_timestamp = $wpdb->prefix . 'wpsc_epa_timestamps_folderfile';
// Define current time
$date_time = date('Y-m-d H:i:s');

$rescan_reversal = 0;
$destroyed = 0;
$validate = 0;
$unathorized_destroy = 0;

foreach($folderdocid_arr as $key) {
$get_validate = $wpdb->get_row("SELECT validation FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_validate_val = $get_validate->validation;

if ($get_validate_val == 1) {
$validate++;
}
}

foreach($folderdocid_arr as $key) {

//AY FIX JOIN
$get_destroyed = $wpdb->get_row("SELECT b.box_destroyed as box_destroyed 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id
WHERE a.freeze = 0 AND a.folderdocinfofile_id = '".$key."'");
$get_destroyed_val = $get_destroyed->box_destroyed;

if ($get_destroyed_val == 1) {
$destroyed++;
}
}

foreach($folderdocid_arr as $key) {
$get_unathorized_destroy = $wpdb->get_row("SELECT unauthorized_destruction FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_unathorized_destroy_val = $get_unathorized_destroy->unauthorized_destruction;

if ($get_unathorized_destroy_val == 1) {
$unathorized_destroy++;
}
}

if($page_id == 'folderfile' && $destroyed == 0 && $unathorized_destroy == 0 && $validate == 0) {
foreach($folderdocid_arr as $key) {
$get_rescan = $wpdb->get_row("SELECT rescan FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_rescan_val = $get_rescan->rescan;

$get_request_id = substr($key, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;
$ticket_data = $wpscfunction->get_ticket($ticket_id);
$status_id   	= $ticket_data['ticket_status'];

if ($get_rescan_val == 1){
$rescan_reversal = 1;
$data_update = array('rescan' => 0);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_rescan_document', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($key);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Re-scan' ");
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

if ($get_rescan_val == 0){
$data_update = array('rescan' => 1);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_undo_rescan_document', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$type = 'Re-scan';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($key), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);

$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); //674
if ($status_id == $validation_tag->term_id) {
$wpscfunction->change_status($ticket_id, 743);   
}
}

if ($rescan_reversal == 1 && $destroyed == 0 && $validate == 0) {
//print_r($folderdocid_arr);
echo "<strong>".$key."</strong> : Re-scan has been updated. A re-scan flag has been reversed.<br />";
} elseif ($rescan_reversal == 0 && $destroyed == 0) {
echo "<strong>".$key."</strong> : Re-scan flag has been set<br />";
}

}

} elseif($destroyed > 0) {
echo "A destroyed folder/file has been selected and cannot be validated.<br />Please unselect the destroyed folder/file.";
} elseif($unathorized_destroy > 0) {
echo "A folder/file flagged as unauthorized destruction has been selected and cannot be validated.<br />Please unselect the folder/file flagged as unauthorized destruction folder/file.";
} elseif($validate > 0) {
echo "A folder/file has been selected that has been flagged as validated.<br />Please unselect the folder/file flagged as validated before flagging item as re-scan.";
}

if($page_id == 'filedetails') {
 
//AY FIX JOIN
$get_rescan = $wpdb->get_row("SELECT rescan, box_id 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '".$folderdocid_string."'");
$get_rescan_val = $get_rescan->rescan;
$get_rescan_boxid_val = $get_rescan->box_id;

$get_request_id = substr($folderdocid_string, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;
$ticket_data = $wpscfunction->get_ticket($ticket_id);
$status_id   	= $ticket_data['ticket_status'];

if ($get_rescan_val == 1){
$rescan_reversal = 1;
$data_update = array('rescan' => 0);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_rescan_document', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Re-scan' ");
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

if ($get_rescan_val == 0){
$data_update = array('rescan' => 1);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_undo_rescan_document', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$type = 'Re-scan';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name, $data_update, $data_where);

$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); //674
if ($status_id == $validation_tag->term_id) {
$wpscfunction->change_status($ticket_id, 743);   
}
}

if ($rescan_reversal == 1 && $destroyed == 0) {
//print_r($folderdocid_arr);
echo "Re-scan has been updated. A re-scan flag has been reversed.";
} elseif ($rescan_reversal == 0 && $destroyed == 0) {
echo "Re-scan flag has been set";
}
}

} else {
   echo "Please select one or more items to flag as re-scan.";
}
?>