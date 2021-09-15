<?php

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $wpdb, $current_user, $wpscfunction;

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

//Grab ticket ID and Selected Digitization Center from Modal
	$ticket_id = $_POST['postvartktid'];
	$tkstatus = $_POST['postvarstatus'];
	$tkcomment = $_POST['postvarcomment'];
	
//Get current ticket status
$ticket              = $wpscfunction->get_ticket($ticket_id);
$status_id           = $ticket['ticket_status']; 

if (isset($_POST['postvartktid']) && isset($_POST['postvarstatus'])) {

                
//Insert timestamp to expedite shipping by requestor

$review_complete_timestamp_check = $wpscfunction->get_ticket_meta($ticket_id,'review_complete_timestamp');
$t=time();
if($tkstatus == $request_initial_review_complete_tag->term_id && empty($review_complete_timestamp_check)) {
$wpscfunction->add_ticket_meta($ticket_id,'review_complete_timestamp',$t);
echo 'ticketid'.$tkid;
}


$rejected_comment_check = $wpscfunction->get_ticket_meta($ticket_id,'rejected_comment');


if($tkstatus == $request_initial_review_rejected_tag->term_id) {

$get_box_ids = $wpdb->get_results("SELECT id, storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE ticket_id = '".$ticket_id."'");
                   
foreach($get_box_ids as $item) {
    $data_update_box_status = array('box_status' => $box_cancelled_tag->term_id);
    $data_where_box_status = array('id' => $item->id);
    $wpdb->update($wpdb->prefix . 'wpsc_epa_boxinfo', $data_update_box_status, $data_where_box_status);
    
// RESET Aisle/Bay/Shelf/Position Location

// GET SHELF ID
    $get_shelf_id = $wpdb->get_row("SELECT aisle, bay, shelf FROM " . $wpdb->prefix . "wpsc_epa_storage_location WHERE id = '" . $item->storage_location_id . "'");
    $shelf_id = $get_shelf_id->aisle.'_'.$get_shelf_id->bay.'_'.$get_shelf_id->shelf;

// RESET AISLE, BAY, SHELF, POSITION TO 0
    $data_update_storage_location = array('aisle' => 0,'bay' => 0,'shelf' => 0,'position' => 0);
    $data_where_storage_location = array('id' => $item->storage_location_id);
    $wpdb->update($wpdb->prefix . 'wpsc_epa_storage_location', $data_update_storage_location, $data_where_storage_location);
    
}

}

if($tkcomment != '' && $tkstatus == $request_initial_review_rejected_tag->term_id && empty($rejected_comment_check)) {
    
$wpscfunction->add_ticket_meta($ticket_id,'rejected_comment',$tkcomment);

} elseif($tkcomment != '' && $tkstatus == $request_initial_review_rejected_tag->term_id && !empty($rejected_comment_check)) {

$wpscfunction->update_ticket_meta($ticket_id,'rejected_comment',array('meta_value'=> $tkcomment));

} elseif($tkstatus != $request_initial_review_rejected_tag->term_id && !empty($rejected_comment_check)) {

$wpscfunction->delete_ticket_meta($ticket_id,'rejected_comment',true);

} else {

$wpscfunction->delete_ticket_meta($ticket_id,'rejected_comment',true);
}

//Set the initial review rejected timestamp
$rejected_timestamp_check = $wpscfunction->get_ticket_meta($ticket_id,'rejected_timestamp');
$t=time();
if($tkstatus == $request_initial_review_rejected_tag->term_id && empty($rejected_timestamp_check)) {
    
$wpscfunction->add_ticket_meta($ticket_id,'rejected_timestamp',$t);

} elseif($tkstatus == $request_initial_review_rejected_tag->term_id && !empty($rejected_timestamp_check) && $status_id != $request_initial_review_rejected_tag->term_id) {

$wpscfunction->update_ticket_meta($ticket_id,'rejected_timestamp',array('meta_value'=> $t));

} elseif($tkstatus != $request_initial_review_rejected_tag->term_id && !empty($rejected_timestamp_check)) {

$wpscfunction->delete_ticket_meta($ticket_id,'rejected_timestamp',true);

} elseif($tkstatus == $request_initial_review_rejected_tag->term_id && !empty($rejected_timestamp_check) && $status_id == $request_initial_review_rejected_tag->term_id && $tkcomment != $rejected_comment_check) {

} else {

$wpscfunction->delete_ticket_meta($ticket_id,'rejected_timestamp',true);
}


// RESET OCCUPIED and REMAINING
include_once( WPPATT_ABSPATH . 'includes/admin/e_location_assignment_cleanup_cron.php' );
include_once( WPPATT_ABSPATH . 'includes/admin/w_location_assignment_cleanup_cron.php' );

echo 'Reject Success.'.$test;
//print_r($rejected_timestamp_check);
} else {
	echo "Issue with reject timestamp update";
}
