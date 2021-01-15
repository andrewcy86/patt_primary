<?php

//if ( ! defined( 'ABSPATH' ) ) {
//	exit; // Exit if accessed directly
//}

$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php');

global $current_user, $wpscfunction, $wpdb;

// Get all rejected tickets
$get_rejected_requests = $wpdb->get_results("SELECT id as ticket_id, request_id
FROM wpqa_wpsc_ticket
WHERE ticket_status = 670 AND active = 1 AND id <> -99999");

foreach($get_total_request_count as $item) {
$ticket_id = $item->ticket_id;

$get_customer_name = $wpdb->get_row('SELECT customer_name FROM wpqa_wpsc_ticket WHERE id = "' . $ticket_id . '"');
$get_user_id = $wpdb->get_row('SELECT ID FROM wpqa_users WHERE display_name = "' . $get_customer_name->customer_name . '"');

$user_id_array = [$get_user_id->ID];
$convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
$patt_agent_id = implode($convert_patt_id);

$pattagentid_array = [$patt_agent_id];
$data = [];
$requestid = Patt_Custom_Func::convert_request_db_id($ticket_id);

//send email notification + inbox notification
$email = 1;

$rejected_timestamp = $wpscfunction->get_ticket_meta($ticket_id,'rejected_timestamp');
$rejected_notification = $wpscfunction->get_ticket_meta($ticket_id,'rejected_notification');

if(!empty($rejected_timestamp)) {
date_default_timezone_set('US/Eastern');

$t=time();
$timestamp = implode(" ",$rejected_timestamp);

$date1 = date('Y-m-d',$timestamp);
$date2 = date('Y-m-d',$t);

$diff = abs(strtotime($date2) - strtotime($date1));

$years = floor($diff / (365*60*60*24));
$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
}

// Send 1st Notification, 14 days
if(!empty($rejected_timestamp) && empty($rejected_notification) && $days >= 14) {
Patt_Custom_Func::insert_new_notification('email-initial-review-rejected',$pattagentid_array,$requestid,$data,$email);
$wpscfunction->add_ticket_meta($ticket_id,'rejected_notification','1');
}

// Send 2nd Notification, 30 days
if(!empty($rejected_timestamp) && $rejected_notification == 1 && $months >= 1) {
Patt_Custom_Func::insert_new_notification('email-initial-review-rejected',$pattagentid_array,$requestid,$data,$email);
$wpscfunction->update_ticket_meta($ticket_id,'rejected_notification',array('meta_value'=> '2'));
}

}

?>