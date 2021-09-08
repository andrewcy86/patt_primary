<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

if(
!empty($_POST['postvarsboxid'])
){

$boxid_string = $_POST['postvarsboxid'];
$boxid_arr = explode (",", $boxid_string);  
$page_id = $_POST['postvarpage'];

$destruction_flag = 0;
$destruction_box_array = array();
$dc_array = array();
$dc_set = '';

$box_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';

// Define current time
$date_time = date('Y-m-d H:i:s');

$destruction_reversal = array();
$counter = 0;

//Raised By, Staff Only Fields, Recipients
$status_id_arr = array($request_new_request_tag->term_id, $request_tabled_tag->term_id, $request_initial_review_rejected_tag->term_id, $request_cancelled_tag->term_id, $request_completed_dispositioned_tag->term_id);

foreach($boxid_arr as $key) {    

$get_box = $wpdb->get_row("SELECT a.ticket_category, a.id, b.box_destroyed
FROM " . $wpdb->prefix . "wpsc_ticket a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
WHERE b.box_id = '".$key."'");
$get_dc_val = $get_box->ticket_category;
$get_box_destroyed_val = $get_box->box_destroyed;
$get_ticket_id_val = $get_box->id;

array_push($dc_array,$get_dc_val);

array_push($destruction_reversal,$get_box_destroyed_val);

}

$dc_array_count = count(array_unique($dc_array));


foreach($boxid_arr as $key) {    
$counter++;

$get_box_db_id = $wpdb->get_row("select id, storage_location_id from " . $wpdb->prefix . "wpsc_epa_boxinfo where box_id = '".$key."'");
$box_db_id = $get_box_db_id->id;
$storage_location_id = $get_box_db_id->storage_location_id;

array_push($destruction_box_array,$storage_location_id);


//REVIEW
$get_sum_total = $wpdb->get_row("select count(b.id) as sum_total_count 
from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b
where b.box_id = '".$box_db_id."'");
$sum_total_val = $get_sum_total->sum_total_count;

//REVIEW
$get_sum_validation = $wpdb->get_row("select sum(b.validation) as sum_validation 
from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b
where b.validation = 1 AND b.box_id = '".$box_db_id."'");
$sum_validation = $get_sum_validation->sum_validation;

$get_status = $wpdb->get_row("select box_status as status
from " . $wpdb->prefix . "wpsc_epa_boxinfo
where id = '".$box_db_id."'");
$box_status_id = $get_status->status;

$get_destruction_auth_status = $wpdb->get_row("select a.destruction_approval as da, a.ticket_status
from " . $wpdb->prefix . "wpsc_ticket a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.id = b.ticket_id
where b.id = '".$box_db_id."'");
$destruction_auth_status = $get_destruction_auth_status->da;
$status_id = $get_destruction_auth_status;

$get_storage_id = $wpdb->get_row("
SELECT id, storage_location_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo 
WHERE box_id = '" . $key . "'
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
AND a.box_id = '" . $key . "'"
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

if( (($sum_total_val != $sum_validation) || ($box_status_id != $box_destruction_of_source_tag->term_id) || ($destruction_auth_status == 0)) && !in_array($status_id, $status_id_arr)) {
    echo '<strong>'.$key.'</strong> : ';
    echo 'Please ensure all documents are validated, the request status is not:
    <ol>
        <li>New Request</li>
        <li>Tabled</li>
        <li>Initial Review Rejected</li>
        <li>Cancelled or</li>
        <li>Completed/Dispositioned</li>
    </ol>
    the box status is in Destruction of Source, and the destruction approval form has been recevied before destroying the box.';


if ($counter > 0) {
echo '<br />';
echo '<br />';
}

} else {
$get_destruction = $wpdb->get_row("SELECT box_destroyed 
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo 
WHERE box_id = '".$key."'");
$get_destruction_val = $get_destruction->box_destroyed;

$get_request_id = substr($key, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id 
FROM " . $wpdb->prefix . "wpsc_ticket 
WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

if ($get_destruction_val == 1 && $dc_array_count == 1 && count(array_unique($destruction_reversal)) == 1){
    
$box_data_update = array('box_destroyed' => 0, 'location_status_id' => '-99999');
$box_data_where = array('id' => $box_db_id);
$wpdb->update($box_table_name , $box_data_update, $box_data_where);

do_action('wpppatt_after_box_destruction_unflag', $ticket_id, $key);

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('id' => $box_db_id);
$wpdb->update($box_table_name, $data_update, $data_where);

}

if ($get_destruction_val == 0 && count(array_unique($destruction_reversal)) == 1){
$box_data_update = array('box_destroyed' => 1);
$box_data_where = array('id' => $box_db_id);
$wpdb->update($box_table_name , $box_data_update, $box_data_where);


//SET PHYSICAL LOCATION TO DESTROYED
$pl_update = array('location_status_id' => '6');
$pl_where = array('id' => $box_db_id);
$wpdb->update($box_table_name , $pl_update, $pl_where);

//SET SHELF LOCATION TO 0
$dc_notassigned_val = $dc_not_assigned_tag->term_id;

$table_sl = $wpdb->prefix . 'wpsc_epa_storage_location';
$sl_update = array('digitization_center' => $dc_notassigned_val, 'aisle' => '0','bay' => '0','shelf' => '0','position' => '0');
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

do_action('wpppatt_after_box_destruction', $ticket_id, $key);

//Update date_updated column
$data_update = array('date_updated' => $date_time);
$data_where = array('box_id' => $key);
$wpdb->update($box_table_name, $data_update, $data_where);

}

if ($dc_array_count > 1 && count(array_unique($destruction_reversal)) > 1) {
echo '<strong>'.$key.'</strong> : ';
echo "Flagging/unflagging box destruction from boxes in different digitization centers not allowed.";
if ($counter > 0) {
echo '<br />';
echo '<br />';
}
} elseif ($dc_array_count > 1 && count(array_unique($destruction_reversal)) == 1 && $destruction_reversal[0] == 1) {
echo '<strong>'.$key.'</strong> : ';
echo "Boxes that belong to multiple digitzation centers cannot be selected.";
if ($counter > 0) {
echo '<br />';
echo '<br />';
}
} elseif(count(array_unique($destruction_reversal)) == 1 && $destruction_reversal[0] == 1 && $dc_array_count == 1) {
echo '<strong>'.$key.'</strong> : ';
echo "Box destruction has been updated. A box destruction has been reversed.";
if ($counter > 0) {
echo '<br />';
echo '<br />';
}
} elseif(count(array_unique($destruction_reversal)) == 1 && $destruction_reversal[0] == 0) {
echo '<strong>'.$key.'</strong> : ';
echo "Box destruction has been updated";
if ($counter > 0) {
echo '<br />';
echo '<br />';
}
}

}

}
//debug
/*
print_r( $destruction_reversal );
echo $get_destruction_val;
echo count(array_unique($destruction_reversal));
*/

//Determine if all files belong to same digitization center
if($dc_array_count == 1) {
$dc_set = reset($dc_array);
}

//echo $dc_set;
if($get_destruction_val == 1 && $dc_array_count == 1 && count(array_unique($destruction_reversal)) == 1){
        // Call Auto Assignment Function
        $destruction_flag = 1;
        
        $destruction_boxes = implode(",", $destruction_box_array);
        //echo $destruction_boxes;
        //print_r($destruction_box_array);
        //echo 'executing';
        $execute_auto_assignment = Patt_Custom_Func::auto_location_assignment(0,$dc_set,$destruction_flag,$destruction_boxes);
}

} else {
   echo "Please select one or more boxes to mark for destruction.";
}
?>