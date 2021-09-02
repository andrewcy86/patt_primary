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
$storage_location_table = $wpdb->prefix . 'wpsc_epa_storage_location';
$boxinfo_table = $wpdb->prefix . 'wpsc_epa_boxinfo';

$table_timestamp = $wpdb->prefix . 'wpsc_epa_timestamps_folderfile';
// Define current time
$date_time = date('Y-m-d H:i:s');

$rescan_reversal = 0;
$destroyed = 0;
$validate = 0;
$unathorized_destroy = 0;

$ticket_request_status = 0;
$ticket_box_status = 0;

//Box Statuses
$box_pending_tag = get_term_by('slug', 'pending', 'wpsc_box_statuses'); //748
$box_scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); //672
$box_scanning_digitization_tag = get_term_by('slug', 'scanning-digitization', 'wpsc_box_statuses'); //671
$box_destruction_approved_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); //68
$box_destruction_of_source_tag = get_term_by('slug', 'destruction-of-source', 'wpsc_box_statuses'); //1272
$box_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
$box_cancelled_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses'); //1057

$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); //674
$rescan_tag = get_term_by('slug', 're-scan', 'wpsc_box_statuses'); //743

//Request statuses
$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003

$status_id_arr = array($new_request_tag->term_id, $tabled_tag->term_id, $initial_review_rejected_tag->term_id, $initial_review_rejected_tag->term_id, $completed_dispositioned_tag->term_id);
$box_status_id_arr = array($box_pending_tag->term_id, $box_scanning_preparation_tag->term_id, $box_destruction_approved_tag->term_id, $box_destruction_of_source_tag->term_id, $box_completed_dispositioned_tag->term_id, $box_cancelled_tag->term_id);

foreach($folderdocid_arr as $key) {

$get_statuses = $wpdb->get_row("SELECT a.ticket_status, b.box_status
FROM wpqa_wpsc_ticket a
INNER JOIN wpqa_wpsc_epa_boxinfo b ON b.ticket_id = a.id
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
WHERE c.folderdocinfofile_id = '".$key."'");
$get_ticket_status_val = $get_statuses->ticket_status;
$get_box_status_val = $get_statuses->box_status;

//Documents cannot be marked as Re-scan in these statuses
if( in_array($get_ticket_status_val, $status_id_arr) ) {
   $ticket_request_status++;
}    

//Documents cannot be marked as Re-scan in these box statuses
if( in_array($get_box_status_val, $box_status_id_arr)) {
    $ticket_box_status++;
}

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

do_action('wpppatt_after_undo_rescan_document', $ticket_id, $folderdocid_string);

if($page_id == 'folderfile' && $destroyed == 0 && $unathorized_destroy == 0 && $validate == 0 && $ticket_request_status == 0 && $ticket_box_status == 0) {
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
//do_action('wpppatt_after_rescan_document', $ticket_id, $folderdocid_string);


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

//Get total count of files in a box and compare with validation count
$get_box_ids = $wpdb->get_results("SELECT b.id, b.storage_location_id, b.box_id, b.box_status
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.id = a.box_id
WHERE a.folderdocinfofile_id = '".$key."'");

foreach($get_box_ids as $item) {
    
    $get_total_files = $wpdb->get_row("SELECT COUNT(id) as total_files
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE box_id = " . $item->id);
    $total_files = $get_total_files->total_files;
    
    $get_total_rescan = $wpdb->get_row("SELECT COUNT(rescan) as total_rescan
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE rescan = 1 AND box_id = " . $item->id);
    $total_rescan = $get_total_rescan->total_rescan;
    
    //Update all flags in the storage_location table
    if( ($total_files - $total_rescan) == $total_files) {
        
        //Folder/File should go to scanning preparation when it has already been recalled, otherwise go to scanning digitization
        if(Patt_Custom_Func::id_in_recall($key, 'folderfile') == 1 || Patt_Custom_Func::id_in_recall($item->box_id, 'box') == 1) {
            $data_update = array('scanning_preparation' => 0, 'scanning_digitization' => 0, 'qa_qc' => 0, 'validation' => 0, 'destruction_approved' => 0, 'destruction_of_source' => 0);
        
            // Old box status and new box status audit log action
            $old_status_str = Patt_Custom_Func::get_box_status($item->id);
    		$new_status_str = $box_scanning_preparation_tag->name;
    		$status_str = $old_status_str . ' to ' . $new_status_str;
        
            $data_update_box_status = array('box_status' => $box_scanning_preparation_tag->term_id, 'box_previous_status' => $item->box_status);
            $data_where_box_status = array('id' => $item->id);
            $wpdb->update($boxinfo_table, $data_update_box_status, $data_where_box_status);
            
            // Audit log action for resetting the box completion to do list
            do_action('wpppatt_after_reset_box_completion_status', $ticket_id, $item->box_id);
            do_action('wpppatt_after_box_status_update', $ticket_id, $status_str, $item->box_id);
        }
        else {
            $data_update = array('scanning_preparation' => 1, 'scanning_digitization' => 0, 'qa_qc' => 0, 'validation' => 0, 'destruction_approved' => 0, 'destruction_of_source' => 0);
        
            // Old box status and new box status audit log action
            $old_status_str = Patt_Custom_Func::get_box_status($item->id);
    		$new_status_str = $box_scanning_digitization_tag->name;
    		$status_str = $old_status_str . ' to ' . $new_status_str;
        
            $data_update_box_status = array('box_status' => $box_scanning_digitization_tag->term_id, 'box_previous_status' => $item->box_status);
            $data_where_box_status = array('id' => $item->id);
            $wpdb->update($boxinfo_table, $data_update_box_status, $data_where_box_status);
            
            // Audit log action for resetting the box completion to do list
            do_action('wpppatt_after_reset_box_completion_status', $ticket_id, $item->box_id);
            do_action('wpppatt_after_box_status_update', $ticket_id, $status_str, $item->box_id);
        }
        
        $data_where = array('id' => $item->storage_location_id);
        $wpdb->update($storage_location_table, $data_update, $data_where);
    }
}    

$data_update = array('rescan' => 1);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
//do_action('wpppatt_after_undo_rescan_document', $ticket_id, $folderdocid_string);


// Check to see if timestamp exists
$type = 'Re-scan';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($key), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);
}

if ($rescan_reversal == 1 && $destroyed == 0 && $validate == 0 && $ticket_request_status == 0 && $ticket_box_status == 0) {
//print_r($folderdocid_arr);
echo "<strong>".$key."</strong> : Re-scan has been updated. A re-scan flag has been reversed.<br />";
} elseif ($rescan_reversal == 0 && $destroyed == 0 && $ticket_request_status == 0 && $ticket_box_status == 0) {
echo "<strong>".$key."</strong> : Re-scan flag has been set<br />";
}

} //END FOREACH

do_action('wpppatt_after_rescan_document', $ticket_id, $folderdocid_string);

} elseif($destroyed > 0) {
echo " A destroyed folder/file has been selected and cannot be validated.<br />Please unselect the destroyed folder/file. ";
} elseif($unathorized_destroy > 0) {
echo " A folder/file flagged as unauthorized destruction has been selected and cannot be validated.<br />Please unselect the folder/file flagged as unauthorized destruction folder/file. ";
} elseif($validate > 0) {
echo " A folder/file has been selected that has been flagged as validated.<br />Please unselect the folder/file flagged as validated before flagging item as re-scan. ";
}
elseif($ticket_request_status > 0) {
echo " A folder/file is the: 
    <ol>
        <li>New Request</li>
        <li>Tabled</li>
        <li>Initial Review Rejected</li>
        <li>Cancelled or </li>
        <li>Completed/Dispositioned</li>
    </ol>
    request status and cannot be flagged as re-scan.<br /> Please review the folder/files that you have selected. ";
}
elseif($ticket_box_status > 0) {
    echo " A folder/file is in the:
    <ol>
        <li>Pending</li>
        <li>Scanning Preparation</li>
        <li>Destruction Approved</li>
        <li>Destruction of Source</li>
        <li>Completed/Dispositioned or</li>
        <li>Cancelled</li>
    </ol>
    box status and cannot be flagged as re-scan.<br /> Please review the folder/files that you have selected. ";
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
//do_action('wpppatt_after_rescan_document', $ticket_id, $folderdocid_string);
do_action('wpppatt_after_undo_rescan_document', $ticket_id, $folderdocid_string);

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

//Get total count of files in a box and compare with rescan count
$get_box_ids = $wpdb->get_row("SELECT b.id, b.storage_location_id, b.box_id, b.box_status
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.id = a.box_id
WHERE a.folderdocinfofile_id = '".$folderdocid_string."'");

$get_total_files = $wpdb->get_row("SELECT COUNT(id) as total_files
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE box_id = " . $get_box_ids->id);
$total_files = $get_total_files->total_files;

$get_total_rescan = $wpdb->get_row("SELECT COUNT(rescan) as total_rescan
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE rescan = 1 AND box_id = " . $get_box_ids->id);
$total_rescan = $get_total_rescan->total_rescan;

//Update all flags in the storage_location table
if( ($total_files - $total_rescan) == $total_files) {
        //Folder/File should go to scanning preparation when it has already been recalled, otherwise go to scanning digitization
        $data_update = '';
        if(Patt_Custom_Func::id_in_recall($key, 'folderfile') == 1 || Patt_Custom_Func::id_in_recall($get_box_ids->box_id, 'box') == 1) {
            $data_update = array('scanning_preparation' => 0, 'scanning_digitization' => 0, 'qa_qc' => 0, 'validation' => 0, 'destruction_approved' => 0, 'destruction_of_source' => 0);
            
            // Old box status and new box status audit log action
            $old_status_str = Patt_Custom_Func::get_box_status($get_box_ids->id);
    		$new_status_str = $box_scanning_preparation_tag->name;
    		$status_str = $old_status_str . ' to ' . $new_status_str;
            
            $data_update_box_status = array('box_status' => $box_scanning_preparation_tag->term_id, 'box_previous_status' => $get_box_ids->box_status);
            $data_where_box_status = array('id' => $get_box_ids->id);
            $wpdb->update($boxinfo_table, $data_update_box_status, $data_where_box_status);
            
            do_action('wpppatt_after_box_status_update', $ticket_id, $status_str, $get_box_ids->box_id);
            
            // TODO add action for audit log for next step in the todo list
        }
        else {
            $data_update = array('scanning_preparation' => 1, 'scanning_digitization' => 0, 'qa_qc' => 0, 'validation' => 0, 'destruction_approved' => 0, 'destruction_of_source' => 0);
        
            // Old box status and new box status audit log action
            $old_status_str = Patt_Custom_Func::get_box_status($get_box_ids->id);
    		$new_status_str = $box_scanning_digitization_tag->name;
    		$status_str = $old_status_str . ' to ' . $new_status_str;
        
            $data_update_box_status = array('box_status' => $box_scanning_digitization_tag->term_id, 'box_previous_status' => $get_box_ids->box_status);
            $data_where_box_status = array('id' => $get_box_ids->id);
            $wpdb->update($boxinfo_table, $data_update_box_status, $data_where_box_status);
            
            do_action('wpppatt_after_box_status_update', $ticket_id, $status_str, $get_box_ids->box_id);
            
            // TODO add action for audit log for next step in the todo list
        }
    $data_where = array('id' => $get_box_ids->storage_location_id);
    $wpdb->update($storage_location_table, $data_update, $data_where);
}
    
$data_update = array('rescan' => 1);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
//do_action('wpppatt_after_undo_rescan_document', $ticket_id, $folderdocid_string);
do_action('wpppatt_after_rescan_document', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$type = 'Re-scan';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name, $data_update, $data_where);
}

if ($rescan_reversal == 1 && $destroyed == 0 && $ticket_request_status == 0 && $ticket_box_status == 0) {
//print_r($folderdocid_arr);
echo " Re-scan has been updated. A re-scan flag has been reversed. ";
} elseif ($rescan_reversal == 0 && $destroyed == 0 && $ticket_request_status == 0 && $ticket_box_status == 0) {
echo " Re-scan flag has been set. ";
}
}

} else {
   echo "Please select one or more items to flag as re-scan.";
}
?>