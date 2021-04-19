<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $current_user, $wpscfunction, $wpdb;

/*
If all records in all boxes of a request were either checked off as validated, 
or all the records in all the boxes of the request were checked off as validated or the boxes were cancelled 
(please note – if all boxes in a request are cancelled the Request Status would be cancelled, not flip like this), 
the Request Status would flip from “In Process” over to “ECMS” or “SEMS” as appropriate.
*/

function in_array_all($value, $array)
{
  return (reset($array) == $value && count(array_unique($array)) == 1);
}

$get_total_request_count = $wpdb->get_results("SELECT id as ticket_id, request_id, ticket_status
FROM " . $wpdb->prefix . "wpsc_ticket
WHERE active = 1 AND id <> -99999");

$identified_requests_array = array();
$validated_box = 0;

$ecms_tag = get_term_by('slug', 'ecms', 'wpsc_statuses'); //998
$sems_tag = get_term_by('slug', 'sems', 'wpsc_statuses'); //1010
$completed_dispositoned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
//ECMS, SEMS, Completed/Diposition and Cancelled array

//$exempt_status_array = array(998,1010,1003,69);
$exempt_status_array = array($ecms_tag->term_id,$sems_tag->term_id,$completed_dispositioned_tag->term_id,$cancelled_tag->term_id);
//FIND request where all documents are validated

foreach($get_total_request_count as $item) {
    $ticket_id = $item->ticket_id;
    $request_id = $item->request_id;
    $ticket_status = $item->ticket_status;
    
    // Get all boxes that belong to the ticket
    
    $get_boxes = $wpdb->get_results("SELECT box_id, box_status
    FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
    WHERE ticket_id = ".$ticket_id." AND id <> -99999");
    
    $check_validation_array = array();
    $check_status_array = array();
    
    foreach($get_boxes as $box_item) {
    $box_id = $box_item->box_id;
    $box_status = $box_item->box_status;
    
    //Are all boxes in the request cancelled?
    array_push($check_status_array, $box_status);
        
    $check_validation = Patt_Custom_Func::id_in_validation($box_id, 'box');
    
    array_push($check_validation_array, $check_validation);
    }
    
    $cancelled_box_status_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses'); //1057
    
    $validated_box = in_array_all(1,$check_validation_array);
    $cancel_box_check = in_array_all($cancelled_box_status_tag->term_id,$check_status_array);
    
    if($validated_box == 1 && $cancel_box_check != 1) {
    array_push($identified_requests_array, $ticket_id);
    }
}

foreach($identified_requests_array as $item) {
    
    //Get ticket status
    $get_ticket_status = $wpdb->get_row("SELECT ticket_status
FROM " . $wpdb->prefix . "wpsc_ticket
WHERE id =".$item);

    $ticket_status = $get_ticket_status->ticket_status;

    //Determine if requests is SEMS or ECMS?
    $sems_check = $wpscfunction->get_ticket_meta($item,'super_fund');
    $in_process_tag = get_term_by('slug', 'in-process', 'wpsc_statuses'); //997                
    if(in_array("true", $sems_check)) {
    // Change Request Status to SEMS and Log
    //echo $item.'- SEMS,'. $sems_tag->term_id;
    // Check if status is in Process and not beyond SEMS
    
        if($ticket_status == $in_process_tag->term_id && !in_array($ticket_status, $exempt_status_array)) {
            $wpscfunction->change_status($item, $sems_tag->term_id);
        }
    } else {
    // Change Request Status to ECMS and Log
    //echo $item.'- ECMS,'. $ecms_tag->term_id;
    // Check if status is In Process and not beyond SEMS
        if($ticket_status == $in_process_tag->term_id && !in_array($ticket_status, $exempt_status_array)) {
             $wpscfunction->change_status($item, $ecms_tag->term_id);
        }
    }

}

?>