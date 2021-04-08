<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php');
*/

global $current_user, $wpscfunction, $wpdb;

//Move a request to the recycle bin when a request is Completed/Dispositioned or Cancelled

//get all active requests
$get_total_request_count = $wpdb->get_results("SELECT id as ticket_id, request_id, ticket_status
FROM " . $wpdb->prefix . "wpsc_ticket
WHERE active = 1 AND id <> -99999");

foreach($get_total_request_count as $item) {
    $ticket_id = $item->ticket_id;
    $request_id = $item->request_id;
    $ticket_status = $item->ticket_status;
    
    $recall_decline = 0;
    $status = 0;
    
    if(Patt_Custom_Func::id_in_recall($request_id, 'request') == 1 || Patt_Custom_Func::id_in_return($request_id, 'request') == 1) {
        $recall_decline = 1;
    }
    
    //Send all Cancelled requests to the Archive
    $cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
    $cancelled_term_id = $cancelled_tag->term_id;
    
    //Send all Completed/Dispositioned requests to the Archive
    $completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses');
    $completed_dispositioned_term_id = $completed_dispositioned_tag->term_id;
    
    $box_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
    $box_cancelled_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses'); //1057
    
    $get_box_status = $wpdb->get_results("SELECT box_status FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE ticket_id = '".$ticket_id."'");
    $get_box_status_array = array();

    foreach ($get_box_status as $boxstatus) {
	    array_push($get_box_status_array, $boxstatus->box_status);
	}
	
	//check if all boxes in a request are the same status
	if( count(array_unique($get_box_status_array)) == 1 ) {
        if( in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array) && $ticket_status != $completed_dispositioned_term_id) {
            $wpscfunction->change_status($ticket_id, $completed_dispositioned_term_id);
        }
        if( in_array($box_cancelled_tag->term_id, $get_box_status_array) && $ticket_status != $cancelled_term_id) {
            $wpscfunction->change_status($ticket_id, $cancelled_term_id);
        }
    }
   
    //check if there are a mix of completed/dispositioned and cancelled boxes in a request
    if( count(array_unique($get_box_status_array)) == 2 && (in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array) && in_array($box_cancelled_tag->term_id, $get_box_status_array)) && $ticket_status != $completed_dispositioned_term_id) {
        $wpscfunction->change_status($ticket_id, $completed_dispositioned_term_id);
    }
    
    //If all boxes are in the status of Completed/Dispositioned and in the correct request status they can be archived
    if( count(array_unique($get_box_status_array)) == 1 && in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array ) && $ticket_status == $completed_dispositioned_term_id) {
        $status = 1;
    }
    
    //If all boxes are in the status of Cancelled and in the correct request status they can be archived
    if( count(array_unique($get_box_status_array)) == 1 && in_array($box_cancelled_tag->term_id, $get_box_status_array ) && $ticket_status == $cancelled_term_id) {
        $status = 1;
    }
    
    //If there are a mix of Completed/Dispositioned and Cancelled boxes and they are in the request status Completed/Dispositioned they can be archived
    if( count(array_unique($get_box_status_array)) == 2 && ( in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array) && in_array($box_cancelled_tag->term_id, $get_box_status_array) ) && $ticket_status == $completed_dispositioned_term_id) {
        $status = 1;
    }
    
    /*
    $get_cancelled_request = $wpdb->get_row("SELECT COUNT(id) as cancelled_count
    FROM " . $wpdb->prefix . "wpsc_ticket a
    WHERE ticket_status = ".$cancelled_term_id." AND id =  '" . $ticket_id . "'");
    $total_cancelled_requests = $get_cancelled_request->cancelled_count;

    $get_completed_dispositioned_request = $wpdb->get_row("SELECT COUNT(id) as completed_dispositioned_count
    FROM " . $wpdb->prefix . "wpsc_ticket a
    WHERE ticket_status = ".$completed_dispositioned_term_id." AND id =  '" . $ticket_id . "'");
    $total_completed_dispositioned_requests = $get_completed_dispositioned_request->completed_dispositioned_count;
    */
    
    //if( ($total_completed_dispositioned_requests > 0 || $total_cancelled_requests > 0) && $recall_decline == 0) {
    if($status == 1 && $recall_decline == 0) {
        $data_update = array('active' => 0);
        $data_where = array('id' => $ticket_id);
        $wpdb->update($wpdb->prefix . 'wpsc_ticket' , $data_update, $data_where);
        //Archive audit log
        Patt_Custom_Func::audit_log_backup($ticket_id);

        //BEGIN CLONING DATA TO ARCHIVE
        Patt_Custom_Func::send_to_archive($ticket_id);
        
        //Send notification to Admins/Managers/Requester when a request is archived
        /*
        $get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
        $get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');
        
        $user_id_array = [$get_user_id->ID];
        $convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
        $patt_agent_id = implode($convert_patt_id);
        $pattagentid_array = [$patt_agent_id];
        */
        
        $agent_admin_group_name = 'Administrator';
        $pattagentid_admin_array = Patt_Custom_Func::agent_from_group($agent_admin_group_name);
        $agent_manager_group_name = 'Manager';
        $pattagentid_manager_array = Patt_Custom_Func::agent_from_group($agent_manager_group_name);
        $pattagentid_array = array_merge($pattagentid_admin_array,$pattagentid_manager_array);
        $data = [];
        
        //$email = 1;
        Patt_Custom_Func::insert_new_notification('email-request-deleted',$pattagentid_array,$request_id,$data,$email);
    }
}


?>