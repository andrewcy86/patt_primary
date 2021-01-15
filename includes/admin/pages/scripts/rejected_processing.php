<?php
global $wpdb, $current_user, $wpscfunction;

$path = preg_replace('/wp-content.*$/', '', __DIR__);
include ($path . 'wp-load.php');
include ($path . 'wp-content/plugins/pattracking/includes/class-wppatt-custom-function.php');

//Grab ticket ID and Selected Digitization Center from Modal
	$ticket_id = $_POST['postvartktid'];
	$tkstatus = $_POST['postvarstatus'];
	$tkcomment = $_POST['postvarcomment'];
	

//Get current ticket status
$ticket              = $wpscfunction->get_ticket($ticket_id);
$status_id           = $ticket['ticket_status']; 

if (isset($_POST['postvartktid']) && isset($_POST['postvarstatus'])) {

$rejected_comment_check = $wpscfunction->get_ticket_meta($ticket_id,'rejected_comment');

if($tkcomment != '' && $tkstatus == '670' && empty($rejected_comment_check)) {
    
$wpscfunction->add_ticket_meta($ticket_id,'rejected_comment',$tkcomment);

} elseif($tkcomment != '' && $tkstatus == '670' && !empty($rejected_comment_check)) {

$wpscfunction->update_ticket_meta($ticket_id,'rejected_comment',array('meta_value'=> $tkcomment));

} elseif($tkstatus != '670' && !empty($rejected_comment_check)) {

$wpscfunction->delete_ticket_meta($ticket_id,'rejected_comment',true);

} else {

$wpscfunction->delete_ticket_meta($ticket_id,'rejected_comment',true);
}

//Set the initial review rejected timestamp
$rejected_timestamp_check = $wpscfunction->get_ticket_meta($ticket_id,'rejected_timestamp');
$t=time();
if($tkstatus == '670' && empty($rejected_timestamp_check)) {
    
$wpscfunction->add_ticket_meta($ticket_id,'rejected_timestamp',$t);

} elseif($tkstatus == '670' && !empty($rejected_timestamp_check) && $status_id != '670') {

$wpscfunction->update_ticket_meta($ticket_id,'rejected_timestamp',array('meta_value'=> $t));

} elseif($tkstatus != '670' && !empty($rejected_timestamp_check)) {

$wpscfunction->delete_ticket_meta($ticket_id,'rejected_timestamp',true);

} elseif($tkstatus == '670' && !empty($rejected_timestamp_check) && $status_id == '670' && $tkcomment != $rejected_comment_check) {

} else {

$wpscfunction->delete_ticket_meta($ticket_id,'rejected_timestamp',true);
}




echo 'Reject Success.'.$test;
//print_r($rejected_timestamp_check);
} else {
	echo "Issue with reject timestamp update";
}
