<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['postvarsfolderdocid'])
){


$folderdocid_string = $_POST['postvarsfolderdocid'];
$get_userid = $_POST['postvarsuserid'];
$folderdocid_arr = explode (",", $folderdocid_string);  
$page_id = $_POST['postvarpage'];

$table_timestamp = $wpdb->prefix . 'wpsc_epa_timestamps_folderfile';
// Define current time
$date_time = date('Y-m-d H:i:s');

$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$validation_reversal = 0;
$destroyed = 0;
$rescan = 0;
$unathorized_destroy = 0;

foreach($folderdocid_arr as $key) {
//AY UPDATE JOIN
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
$get_unathorized_destroy = $wpdb->get_row("SELECT unauthorized_destruction FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_unathorized_destroy_val = $get_unathorized_destroy->unauthorized_destruction;

if ($get_unathorized_destroy_val == 1) {
$unathorized_destroy++;
}
}

foreach($folderdocid_arr as $key) {
$get_rescan = $wpdb->get_row("SELECT rescan FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_rescan_val = $get_rescan->rescan;

if ($get_rescan_val == 1) {
$rescan++;
}
}

if($page_id == 'folderfile' && $destroyed == 0 && $unathorized_destroy == 0 && $rescan == 0) {

foreach($folderdocid_arr as $key) {
    
$get_validation = $wpdb->get_row("SELECT validation FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_validation_val = $get_validation->validation;

$get_request_id = substr($key, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

if ($get_validation_val == 1){
$validation_reversal = 1;
$data_update = array('validation' => 0, 'validation_user_id'=>'');
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_invalidate_document', $ticket_id, $key);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($key);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Validated' ");
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

if ($get_validation_val == 0){
$data_update = array('validation' => 1, 'validation_user_id'=>$get_userid);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_validate_document', $ticket_id, $key);

//PATT BEGIN FOR REPORTING
// Check to see if timestamp exists
$type = 'Validated';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($key), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) );

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);
}

if ($validation_reversal == 1 && $destroyed == 0) {
echo "<strong>".$key."</strong> : Validation has been updated. A validation has been reversed<br />";
} elseif ($validation_reversal == 0 && $destroyed == 0) {
echo "<strong>".$key."</strong> : Validation has been updated<br />";
}

}
    
} elseif($destroyed > 0) {
echo "A destroyed folder/file has been selected and cannot be validated.<br />Please unselect the destroyed folder/file.";
} elseif($unathorized_destroy > 0) {
echo "A folder/file flagged as unauthorized destruction has been selected and cannot be validated.<br />Please unselect the folder/file flagged as unauthorized destruction folder/file.";
} elseif($rescan > 0) {
echo "A folder/file has been selected that has been flagged as requiring a re-scan.<br />Please unselect the folder/file flagged as re-scan before validating.";
}

if($page_id == 'filedetails') {
 
$get_validation = $wpdb->get_row("SELECT validation FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$folderdocid_string."'");
$get_validation_val = $get_validation->validation;

$get_request_id = substr($folderdocid_string, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

$get_rescan = $wpdb->get_row("SELECT rescan FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$folderdocid_string."'");
$get_rescan_val = $get_rescan->rescan;

if ($get_validation_val == 1 && $get_rescan_val == 0){
$validation_reversal = 1;
$data_update = array('validation' => 0, 'validation_user_id'=>'');
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_invalidate_document', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Validated' ");
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

if ($get_validation_val == 0 && $get_rescan_val == 0){
$data_update = array('validation' => 1, 'validation_user_id'=>$get_userid);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_validate_document', $ticket_id, $folderdocid_string);

//PATT BEGIN FOR REPORTING
// Check to see if timestamp exists
$type = 'Validated';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name, $data_update, $data_where);

}
}



if ($get_rescan_val == 1 && $destroyed == 0 && $unathorized_destroy == 0) {
echo "You must unflag document from re-scanning before validating";
} elseif ($validation_reversal == 1 && $destroyed == 0 && $unathorized_destroy == 0) {
//print_r($folderdocid_arr);
echo "Validation has been updated. A validation has been reversed";
} elseif ($validation_reversal == 0 && $destroyed == 0 && $unathorized_destroy == 0) {
echo "Validation has been updated";
}

}

else {
   echo "Please select one or more items to validate.";
}
?>