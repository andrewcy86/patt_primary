<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

if(
!empty($_POST['postvarsfolderdocid'])
){

//To-do add a do action when all the boxes are flaged as unauthorized destruction.

$folderdocid_string = $_POST['postvarsfolderdocid'];
$folderdocid_arr = explode (",", $folderdocid_string);  
$page_id = $_POST['postvarpage'];
$box_id = $_POST['boxid'];
$destruction_flag = 0;
$destruction_box_array = array();
$dc_array = array();
$dc_set = '';
$dc_fail_message = 0;

$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
$table_box_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
$scan_list_table = $wpdb->prefix . 'wpsc_epa_scan_list';

$table_timestamp = $wpdb->prefix . 'wpsc_epa_timestamps_folderfile';
// Define current time
$date_time = date('Y-m-d H:i:s');

$destruction_reversal = 0;
$destruction_violation = 0;
// Determine if violation occured
$frozen = 0;

$ticket_box_status = 0;

$status_id_arr = array($request_new_request_tag->term_id, $request_tabled_tag->term_id, $request_initial_review_rejected_tag->term_id, $request_completed_dispositioned_tag->term_id);

foreach($folderdocid_arr as $key) {
//Set digitzation center array
$get_dc = $wpdb->get_row("SELECT a.ticket_category, a.id
FROM " . $wpdb->prefix . "wpsc_ticket a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
WHERE c.folderdocinfofile_id = '".$key."'");

$get_dc_val = $get_dc->ticket_category;
$get_ticket_id_val = $get_dc->id;

array_push($dc_array,$get_dc_val);

$get_statuses = $wpdb->get_row("SELECT a.ticket_status
FROM " . $wpdb->prefix . "wpsc_ticket a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
WHERE c.folderdocinfofile_id = '".$key."'");
$get_ticket_status_val = $get_statuses->ticket_status;

//Documents cannot be marked as Unauthorized Destruction in these statuses
if( in_array($get_ticket_status_val, $status_id_arr) ) {
   $ticket_box_status++;
}

$get_frozen = $wpdb->get_row("SELECT freeze, damaged FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_frozen_val = $get_frozen->freeze;
$get_damaged_val = $get_frozen->damaged;

if ($get_frozen_val == 1) {
$frozen++;
}

if($get_damaged_val == 1) {
    $get_damaged_val++;
}
}

//Determine if all files belong to same digitization center
if(count(array_unique($dc_array)) == 1) {
$dc_set = reset($dc_array);
} else {
$dc_fail_message = 1;
}

// print_r($dc_array);
// print_r($dc_set);

$return_array = array();
$recall_array = array();
foreach($folderdocid_arr as $key) {    

$getfolderdocinfo_db_id = $wpdb->get_row(
"SELECT 
id
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '" . $key . "'"
			);

$folderdocinfo_db_id = $getfolderdocinfo_db_id->id;

$getrecall_violation_count = $wpdb->get_row(
"SELECT IF( EXISTS( SELECT * FROM " . $wpdb->prefix . "wpsc_epa_recallrequest WHERE folderdoc_id = '" . $folderdocinfo_db_id . "'), 1, 0) as count"
			);

$recall_violation_count = $getrecall_violation_count->count;

if($recall_violation_count == 1) {
array_push($recall_array,$folderdocinfo_db_id);
}

$getreturn_violation_count = $wpdb->get_row(
"SELECT IF( EXISTS( SELECT * FROM " . $wpdb->prefix . "wpsc_epa_return_items WHERE folderdoc_id = '" . $folderdocinfo_db_id . "'), 1, 0) as count"
			);

$return_violation_count = $getreturn_violation_count->count;

if($return_violation_count == 1) {
array_push($return_array,$folderdocinfo_db_id);
}

if ($recall_violation_count > 0 || $return_violation_count > 0) {
$destruction_violation = 1;
}

}

if(($page_id == 'boxdetails' || $page_id == 'folderfile') && $frozen == 0 && $ticket_box_status == 0) {

foreach($folderdocid_arr as $key) {    

$get_destruction = $wpdb->get_row("SELECT unauthorized_destruction FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_destruction_val = $get_destruction->unauthorized_destruction;

$get_request_id = substr($key, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

//AY UPDATE JOIN
$get_box_id = $wpdb->get_row("
SELECT box_id FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '" . $key . "'
");
$box_id = $get_box_id->box_id;

$get_storage_id = $wpdb->get_row("
SELECT id, storage_location_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo 
WHERE id = '" . $box_id . "'
");
$storage_location_id = $get_storage_id->storage_location_id;


$box_details = $wpdb->get_row(
"SELECT 
b.digitization_center,
b.aisle,
b.bay,
b.shelf,
b.position
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b WHERE a.storage_location_id = b.id
AND a.id = '" . $box_id . "'"
			);
			
			$box_storage_digitization_center = $box_details->digitization_center;
			$box_storage_aisle = $box_details->aisle;
			$box_storage_bay = $box_details->bay;
			$box_storage_shelf = $box_details->shelf;
			$box_storage_shelf_id = $box_storage_aisle . '_' . $box_storage_bay . '_' . $box_storage_shelf;

$box_storage_status = $wpdb->get_row(
"SELECT 
occupied,
remaining
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE shelf_id = '" . $box_storage_shelf_id . "'"
			);

$box_storage_status_occupied = $box_storage_status->occupied;
$box_storage_status_remaining = $box_storage_status->remaining;
$box_storage_status_remaining_added = $box_storage_status->remaining + 1;

//AY UPDATE JOIN
$folder_file_count = $wpdb->get_row(
"SELECT 
count(id) as sum
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE box_id = '" . $box_id . "'"
			);

$folder_file_count_sum = $folder_file_count->sum;

//AY UPDATE JOIN
$destruction_count = $wpdb->get_row(
"SELECT 
count(id) as sum
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE unauthorized_destruction = 1 AND box_id = '" . $box_id . "'"
			);

$destruction_count_sum = $destruction_count->sum;

if ($get_destruction_val == 1 && $destruction_violation == 0){

//Get total count of files in a box and compare with unauthorized_destruction count
$get_box_ids = $wpdb->get_results("SELECT count(b.id) as count, b.id, b.ticket_id, b.storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.id = a.box_id
WHERE a.folderdocinfofile_id = '".$key."'");

foreach($get_box_ids as $item) {
    
    $get_total_files = $wpdb->get_row("SELECT COUNT(id) as total_count
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE box_id = " . $item->id);
    $total_files = $get_total_files->total_count;
    
    $get_total_unauthorized_destruction = $wpdb->get_row("SELECT COUNT(unauthorized_destruction) as total_unauthorized_destruction
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE unauthorized_destruction = 1 AND box_id = " . $item->id);
    $total_unauthorized_destruction = $get_total_unauthorized_destruction->total_unauthorized_destruction;
    
    //Update all flags in the storage_location table
    if( ($total_files - $total_unauthorized_destruction) == 0) {
        array_push($destruction_box_array,$item->storage_location_id);
    }
 
}    


$destruction_reversal = 1;

if($folder_file_count_sum == $destruction_count_sum && $dc_fail_message == 0) {
$data_update = array('unauthorized_destruction' => 0);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);

//Reverse the destruction
$data_update_d = array('box_destroyed' => 0);
$data_where_d = array('id' => $box_id);
$wpdb->update($table_box_name, $data_update_d, $data_where_d);
do_action('wpppatt_after_box_destruction_unflag', $ticket_id, Patt_Custom_Func::convert_box_id_to_dbid($box_id));

//Reset the physical location
// TODO determine what to do with this
$data_update_pl = array('location_status_id' => -99999);
$data_where_pl = array('id' => $box_id);
$wpdb->update($table_box_name, $data_update_pl, $data_where_pl);

} else {
$data_update = array('unauthorized_destruction' => 0);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
}

do_action('wpppatt_after_unauthorized_destruction_unflag', $ticket_id, $key);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($key);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Unauthorized Destruction' ");
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

if ($get_destruction_val == 0 && $destruction_violation == 0){
//If file is flagged as unauthorized destruction, unflag as damaged
if($get_damaged_val > 0) {
   $data_update = array('unauthorized_destruction' => 1, 'damaged' => 0); 
   
   //Delete damaged timestamp from timestamps_folderfile table
    $converted = Patt_Custom_Func::convert_folderdocinfofile_id($key);
    $get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Damaged' ");
    $folderfile_timestamp_id = $get_folderfile_timestamp->id;
    // Delete previous value
    if(!empty($folderfile_timestamp_id)) {
      $wpdb->delete( $table_timestamp, array( 'id' => $folderfile_timestamp_id ) );
    }
}
else {
    $data_update = array('unauthorized_destruction' => 1);
}
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);

//AY UPDATE JOIN
$destruction_count = $wpdb->get_row("SELECT count(id) as sum
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE unauthorized_destruction = 1 AND box_id = '" . $box_id . "'");
$destruction_count_sum = $destruction_count->sum;

if($folder_file_count_sum == $destruction_count_sum) {
//SET PHYSICAL LOCATION TO DESTROYED
$table_pl = $wpdb->prefix . 'wpsc_epa_boxinfo';
$pl_update = array('location_status_id' => '6','box_destroyed' => '1');
$pl_where = array('id' => $box_id);
$wpdb->update($table_pl , $pl_update, $pl_where);

$get_physical_locations_from_box = $wpdb->get_row("SELECT DISTINCT a.scanning_id, a.stagingarea_id, a.cart_id, a.validation_location_area_id, a.qaqc_location_area_id, a.scanning_prep_location_area_id, a.scanning_location_area_id, a.shelf_location, b.pallet_id, b.box_id
FROM " . $wpdb->prefix . "wpsc_epa_scan_list a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.scan_list_id = a.id
WHERE (a.scanning_id IS NOT NULL OR a.stagingarea_id IS NOT NULL OR a.cart_id IS NOT NULL OR a.validation_location_area_id IS NOT NULL OR a.qaqc_location_area_id IS NOT NULL OR a.scanning_prep_location_area_id IS NOT NULL OR a.scanning_location_area_id IS NOT NULL OR a.shelf_location IS NOT NULL)
AND b.id = '" .  $box_id . "'");

$scanning_id = $get_physical_locations_from_box->scanning_id;
$stagingarea_id = $get_physical_locations_from_box->stagingarea_id;
$cart_id = $get_physical_locations_from_box->cart_id;
$validation_location_area_id = $get_physical_locations_from_box->validation_location_area_id;
$qaqc_location_area_id = $get_physical_locations_from_box->qaqc_location_area_id;
$scanning_prep_location_area_id = $get_physical_locations_from_box->scanning_prep_location_area_id;
$scanning_location_area_id = $get_physical_locations_from_box->scanning_location_area_id;
$shelf_location = $get_physical_locations_from_box->shelf_location;
$pallet_id = $get_physical_locations_from_box->pallet_id;
$box_full_id = $get_physical_locations_from_box->box_id;

$get_pallet_id_from_box = $wpdb->get_row("SELECT pallet_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE id = '" .  $box_id . "'");
$boxinfo_pallet_id = $get_pallet_id_from_box->pallet_id;

if(!empty($boxinfo_pallet_id)) {
    //Delete pallet_id from the boxinfo table
    $boxinfo_pallet_update = array('pallet_id' => '');
    $boxinfo_pallet_where = array('id' => $box_id);
    $wpdb->update($table_box_name, $boxinfo_pallet_update, $boxinfo_pallet_where);
}

if(!empty($pallet_id) || !empty($scanning_id) || !empty($stagingarea_id) || !empty($cart_id) || !empty($validation_location_area_id) || !empty($qaqc_location_area_id) || !empty($scanning_prep_location_area_id) || !empty($scanning_location_area_id) || !empty($shelf_location)) {
    if(!empty($pallet_id)) {
        $get_pallet_for_boxes = $wpdb->get_row("SELECT COUNT(pallet_id) as pallet_count
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        WHERE pallet_id = '" .  $pallet_id . "'");
        $pallet_count = $get_pallet_for_boxes->pallet_count;
        
        //Delete pallet_id from the scan_list table when the pallet_id is not found in the boxinfo table
        if($pallet_count == 0) {
            $wpdb->delete( $scan_list_table, array( 'pallet_id' => $pallet_id ) );
        }
        do_action('wpppatt_after_unassign_pallet', $ticket_id, $box_full_id, $pallet_id);
    }
    
    //Delete from the scan_list table for non-pallets
    if(!empty($scanning_id) || !empty($stagingarea_id) || !empty($cart_id) || !empty($validation_location_area_id) || !empty($qaqc_location_area_id) || !empty($scanning_prep_location_area_id) || !empty($scanning_location_area_id) || !empty($shelf_location)) {
        $wpdb->delete( $scan_list_table, array( 'box_id' => $box_full_id ) );
    }
}

//SET SHELF LOCATION TO 0
$table_sl = $wpdb->prefix . 'wpsc_epa_storage_location';
$sl_update = array('digitization_center' => $dc_not_assigned_tag->term_id,'aisle' => '0','bay' => '0','shelf' => '0','position' => '0');
$sl_where = array('id' => $storage_location_id);
$wpdb->update($table_sl , $sl_update, $sl_where);

//ADD AVALABILITY TO STORAGE STATUS
if ($box_storage_status_remaining <= 4) {
$table_ss = $wpdb->prefix . 'wpsc_epa_storage_status';
$ssr_update = array('remaining' => $box_storage_status_remaining_added);
$ssr_where = array('shelf_id' => $box_storage_shelf_id, 'digitization_center' => $box_storage_digitization_center);
$wpdb->update($table_ss , $ssr_update, $ssr_where);
}

if($box_storage_status_remaining == 4){
$sso_update = array('occupied' => 0);
$sso_where = array('shelf_id' => $box_storage_shelf_id, 'digitization_center' => $box_storage_digitization_center);
$wpdb->update($table_ss , $sso_update, $sso_where);
}
do_action('wpppatt_after_box_destruction', $ticket_id, Patt_Custom_Func::convert_box_id_to_dbid($box_id));
}
do_action('wpppatt_after_unauthorized_destruction', $ticket_id, $key);

// Check to see if timestamp exists
$type = 'Unauthorized Destruction';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($key), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);

}

}

//Update all flags in the storage_location table
if(count($destruction_box_array) == 1 && $dc_fail_message == 0){
        // Call Auto Assignment Function
        $destruction_flag = 1;
        
        $destruction_boxes = implode(",", $destruction_box_array);
        //echo $destruction_boxes;
        //print_r($destruction_box_array);
        $execute_auto_assignment = Patt_Custom_Func::auto_location_assignment(0,$dc_set,$destruction_flag,$destruction_boxes);
}


} elseif($frozen > 0) {
echo " A frozen folder/file has been selected and cannot be flagged as unauthorized destruction. Please unselect the frozen folder/file. ";
}
elseif($ticket_box_status > 0) {
echo " A folder/file is in the:
        1. New Request
        2. Tabled
        3. Initial Review Rejected or
        4. Completed/Dispositioned
request status and cannot be flagged as unauthorized destruction. Please review the folder/files that you have selected. ";
}

if($page_id == 'filedetails') {

//AY UPDATE JOIN
$get_box_id = $wpdb->get_row("
SELECT box_id FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '" . $key . "'
");
$box_id = $get_box_id->box_id;

$get_destruction = $wpdb->get_row("SELECT unauthorized_destruction FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$folderdocid_string."'");

$get_destruction_val = $get_destruction->unauthorized_destruction;

$get_request_id = substr($folderdocid_string, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

//AY UPDATE JOIN
$folder_file_count = $wpdb->get_row(
"SELECT 
count(id) as sum
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE box_id = '" . $box_id . "'"
			);

$folder_file_count_sum = $folder_file_count->sum;

if ($get_destruction_val == 1 && $destruction_violation == 0 && $dc_fail_message == 0){
    
$destruction_reversal = 1;

$data_update = array('unauthorized_destruction' => 0);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);

do_action('wpppatt_after_unauthorized_destruction_unflag', $ticket_id, $folderdocid_string);

// Check to see if timestamp exists
$converted = Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string);
$get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Unauthorized Destruction' ");
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

if ($get_destruction_val == 0 && $destruction_violation == 0){
//If document flagged as unauthorized destruction, then unflag as damaged    
if($get_damaged_val > 0) {
   $data_update = array('unauthorized_destruction' => 1, 'damaged' => 0); 
   
   //Delete damaged timestamp from timestamps_folderfile table
    $converted = Patt_Custom_Func::convert_folderdocinfofile_id($folderdocid_string);
    $get_folderfile_timestamp = $wpdb->get_row("select id from " . $table_timestamp . " where folderdocinfofile_id = '".$converted."' AND type = 'Damaged' ");
    $folderfile_timestamp_id = $get_folderfile_timestamp->id;
    // Delete previous value
    if(!empty($folderfile_timestamp_id)) {
      $wpdb->delete( $table_timestamp, array( 'id' => $folderfile_timestamp_id ) );
    }
}
else {
    $data_update = array('unauthorized_destruction' => 1);
}

$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);

//AY UPDATE JOIN
$destruction_count = $wpdb->get_row(
"SELECT 
count(id) as sum
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE unauthorized_destruction = 1 AND box_id ='" . $box_id . "'"
			);

$destruction_count_sum = $destruction_count->sum;

//echo $box_id;
//echo 'des val -'.$get_destruction_val .'-';
//echo 'des vio -'.$destruction_violation;
//echo 'fold count -'.$folder_file_count_sum .'-';
//echo 'destruct count -'.$destruction_count_sum;

if($folder_file_count_sum == $destruction_count_sum) {
//SET PHYSICAL LOCATION TO DESTROYED
$table_pl = $wpdb->prefix . 'wpsc_epa_boxinfo';
$pl_update = array('location_status_id' => '6','box_destroyed' => '1');
$pl_where = array('id' => $box_id);
$wpdb->update($table_pl , $pl_update, $pl_where);
do_action('wpppatt_after_box_destruction', $ticket_id, Patt_Custom_Func::convert_box_id_to_dbid($box_id));

$get_physical_locations_from_box = $wpdb->get_row("SELECT DISTINCT a.scanning_id, a.stagingarea_id, a.cart_id, a.validation_location_area_id, a.qaqc_location_area_id, a.scanning_prep_location_area_id, a.scanning_location_area_id, a.shelf_location, b.pallet_id, b.box_id
FROM " . $wpdb->prefix . "wpsc_epa_scan_list a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.scan_list_id = a.id
WHERE (a.scanning_id IS NOT NULL OR a.stagingarea_id IS NOT NULL OR a.cart_id IS NOT NULL OR a.validation_location_area_id IS NOT NULL OR a.qaqc_location_area_id IS NOT NULL OR a.scanning_prep_location_area_id IS NOT NULL OR a.scanning_location_area_id IS NOT NULL OR a.shelf_location IS NOT NULL)
AND b.id = '" .  $box_id . "'");

$scanning_id = $get_physical_locations_from_box->scanning_id;
$stagingarea_id = $get_physical_locations_from_box->stagingarea_id;
$cart_id = $get_physical_locations_from_box->cart_id;
$validation_location_area_id = $get_physical_locations_from_box->validation_location_area_id;
$qaqc_location_area_id = $get_physical_locations_from_box->qaqc_location_area_id;
$scanning_prep_location_area_id = $get_physical_locations_from_box->scanning_prep_location_area_id;
$scanning_location_area_id = $get_physical_locations_from_box->scanning_location_area_id;
$shelf_location = $get_physical_locations_from_box->shelf_location;
$pallet_id = $get_physical_locations_from_box->pallet_id;
$box_full_id = $get_physical_locations_from_box->box_id;

if(!empty($pallet_id) || !empty($scanning_id) || !empty($stagingarea_id) || !empty($cart_id) || !empty($validation_location_area_id) || !empty($qaqc_location_area_id) || !empty($scanning_prep_location_area_id) || !empty($scanning_location_area_id) || !empty($shelf_location)) {
    if(!empty($pallet_id)) {
        //Delete pallet_id from the boxinfo table
        $boxinfo_pallet_update = array('pallet_id' => '');
        $boxinfo_pallet_where = array('id' => $box_id);
        $wpdb->update($table_box_name, $boxinfo_pallet_update, $boxinfo_pallet_where);
        
        $get_pallet_for_boxes = $wpdb->get_row("SELECT COUNT(pallet_id) as pallet_count
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        WHERE pallet_id = '" .  $pallet_id . "'");
        $pallet_count = $get_pallet_for_boxes->pallet_count;
        
        //Delete pallet_id from the scan_list table when the pallet_id is not found in the boxinfo table
        if($pallet_count == 0) {
            $wpdb->delete( $scan_list_table, array( 'pallet_id' => $pallet_id ) );
        }
        do_action('wpppatt_after_unassign_pallet', $ticket_id, $box_full_id, $pallet_id);
    }
    
    //Delete from the scan_list table for non-pallets
    if(!empty($scanning_id) || !empty($stagingarea_id) || !empty($cart_id) || !empty($validation_location_area_id) || !empty($qaqc_location_area_id) || !empty($scanning_prep_location_area_id) || !empty($scanning_location_area_id) || !empty($shelf_location)) {
        $wpdb->delete( $scan_list_table, array( 'box_id' => $box_full_id ) );
    }
}

//SET SHELF LOCATION TO 0
$table_sl = $wpdb->prefix . 'wpsc_epa_storage_location';
$sl_update = array('digitization_center' => $dc_not_assigned_tag->term_id,'aisle' => '0','bay' => '0','shelf' => '0','position' => '0');
$sl_where = array('id' => $storage_location_id);
$wpdb->update($table_sl , $sl_update, $sl_where);

//ADD AVALABILITY TO STORAGE STATUS
if ($box_storage_status_remaining <= 4) {
$table_ss = $wpdb->prefix . 'wpsc_epa_storage_status';
$ssr_update = array('remaining' => $box_storage_status_remaining_added);
$ssr_where = array('shelf_id' => $box_storage_shelf_id, 'digitization_center' => $box_storage_digitization_center);
$wpdb->update($table_ss , $ssr_update, $ssr_where);
}

if($box_storage_status_remaining == 4){
$sso_update = array('occupied' => 0);
$sso_where = array('shelf_id' => $box_storage_shelf_id, 'digitization_center' => $box_storage_digitization_center);
$wpdb->update($table_ss , $sso_update, $sso_where);
}

}
do_action('wpppatt_after_unauthorized_destruction', $ticket_id, $key);

// Check to see if timestamp exists
$type = 'Unauthorized Destruction';
$wpdb->insert($table_timestamp, array('folderdocinfofile_id' => Patt_Custom_Func::convert_folderdocinfofile_id($key), 'type' => $type, 'user' => $current_user->user_login, 'timestamp' => $date_time) ); 

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name, $data_update, $data_where);

}
}

//AY UPDATE JOIN
$get_destruction_sum = $wpdb->get_row("SELECT sum(unauthorized_destruction) as sum FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE box_id = '".$box_id."'");

$get_destruction_sum_val = $get_destruction_sum->sum;


if ($page_id == 'boxdetails' && $frozen == 0 && $ticket_box_status == 0) {
if ($get_destruction_sum_val > 0) {

if ($destruction_violation == 1) {
    //print_r($recall_array);
    //print_r($return_array);
echo " A violation has occured and a folder/file you selected cannot be set to unauthorized destruction due to a return/recall. Please check your selection. ";
} else {

if ($destruction_reversal == 1 && $destruction_violation == 0) {
    //print_r($recall_array);
    //print_r($return_array);
echo " Unauthorized destruction has been updated. A unauthorized destruction has been reversed. ";
} else {
    //print_r($recall_array);
    //print_r($return_array);
echo " Unauthorized destruction has been updated. ";
}
}

} else {
    //print_r($recall_array);
    //print_r($return_array);
echo " Unauthorized destruction has been updated. ";
}
}

if ( ($page_id == 'filedetails' || $page_id == 'folderfile') && $frozen == 0 && $ticket_box_status == 0) {

if ($destruction_violation == 1) {
    //print_r($recall_array);
    //print_r($return_array);
echo " A violation has occured and a folder/file you selected cannot be set to unauthorized destruction due to a return/recall. Please check your selection. ";
} else {

if ($destruction_reversal == 1 && $destruction_violation == 0 && $dc_fail_message == 0) {
    //print_r($recall_array);
    //print_r($return_array);
echo " Unauthorized destruction has been updated. A unauthorized destruction has been reversed. ";
} elseif ($dc_fail_message == 1 && $destruction_reversal == 1) {
echo "Files that belong to multiple digitzation centers cannot be selected.";
} else {
echo " Unauthorized destruction has been updated. ";
}
}
}

} else {
   echo "Please select one or more items to mark as unauthorized destruction.";
}
?>