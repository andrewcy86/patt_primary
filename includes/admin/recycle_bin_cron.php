<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php');
*/

global $current_user, $wpscfunction, $wpdb;

//Send all Cancelled requests to the Archive
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
$cancelled_term_id = $cancelled_tag->term_id;

//Send all Completed/Dispositioned requests to the Archive
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses');
$completed_dispositioned_term_id = $completed_dispositioned_tag->term_id;

$box_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
$box_cancelled_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses'); //1057

//Send all Initial Review Rejected requests to the Archive
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$initial_review_rejected_term_id = $initial_review_rejected_tag->term_id;

//Move a request to the recycle bin when a request is Completed/Dispositioned or Cancelled

//get all active requests
$get_total_request_count = $wpdb->get_results("SELECT DISTINCT wpqa_wpsc_ticket.id as ticket_id, request_id, ticket_status, date_updated
FROM " . $wpdb->prefix . "wpsc_ticket
INNER JOIN wpqa_wpsc_ticketmeta
ON wpqa_wpsc_ticket.id = wpqa_wpsc_ticketmeta.ticket_id
WHERE active = 1 AND wpqa_wpsc_ticket.id <> -99999");

foreach($get_total_request_count as $item) {
    $ticket_id = $item->ticket_id;
    $request_id = $item->request_id;
    $ticket_status = $item->ticket_status;
    $date_updated = $item->date_updated;
    // $ticket_meta_key = $item->meta_key;
    // $ticket_meta_value = $item->meta_value;
    //echo $ticket_id;
    $recall_decline = 0;
    $status = 0;

 
    $date_updated = date_create($date_updated);
    $date_updated_formatted = date_format($date_updated, "m/d/Y");
    $current_date = date_create();
    $current_date_formatted = date("m/d/Y");

    // var_dump($ticket_id);
    // var_dump($expire_date_formatted);
    // var_dump(strtotime($current_date_formatted) >= strtotime($expire_date_formatted));

    if(Patt_Custom_Func::id_in_recall($request_id, 'request') == 1 || Patt_Custom_Func::id_in_return($request_id, 'request') == 1) {
        $recall_decline = 1;
    }
    
    $get_box_status = $wpdb->get_results("SELECT box_status FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE ticket_id = '".$ticket_id."'");
    $get_box_status_array = array();

    foreach ($get_box_status as $box) {
	    array_push($get_box_status_array, $box->box_status);
	}

    $get_ticket_meta = $wpdb->get_results("SELECT DISTINCT ticket_id, meta_key, meta_value  
                        FROM " . $wpdb->prefix . "wpsc_ticketmeta WHERE ticket_id = '".$ticket_id."' AND meta_key = 'rejected_timestamp'");
   

    
    
    // var_dump($get_ticket_meta);
    // var_dump($get_ticket_meta_array);
	
	//check if all boxes in a request are the same status
	if( count(array_unique($get_box_status_array)) == 1 ) {
        if( in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array) && $ticket_status != $completed_dispositioned_term_id) {
            $wpscfunction->change_status($ticket_id, $completed_dispositioned_term_id);
            do_action('wpsc_after_edit_change_ticket_status',$ticket_id);
        }
        
        if( in_array($box_cancelled_tag->term_id, $get_box_status_array) && $ticket_status != $cancelled_term_id) {
			if (count($get_ticket_meta) != 0) {
				// list is not empty.
				foreach ($get_ticket_meta as $ticket) {
					// array_push($get_ticket_meta_array, $ticket->ticket_id);
					$ticket_meta_id = $ticket->ticket_id;
					$ticket_meta_key = $ticket->meta_key;
					$ticket_meta_value = $ticket->meta_value;


					if($ticket_meta_key == 'rejected_timestamp') {
						$expire_date = (strtotime("+2 weeks", $ticket_meta_value));
						$expire_date_formatted = date("m/d/y", $expire_date);
						
						// var_dump($item);
						// var_dump($expire_date_formatted);
				
						if(strtotime($current_date_formatted) >= strtotime($expire_date_formatted)) {
							$wpscfunction->change_status($ticket_id, $cancelled_term_id);
							do_action('wpsc_after_edit_change_ticket_status',$ticket_id);
						}
						
					}
				}

			} else {
                $wpscfunction->change_status($ticket_id, $cancelled_term_id);
                do_action('wpsc_after_edit_change_ticket_status',$ticket_id); 
            }

			 
        }
    }
    
    //check if there are a mix of completed/dispositioned and cancelled boxes in a request
    if( count(array_unique($get_box_status_array)) == 2 && (in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array) && in_array($box_cancelled_tag->term_id, $get_box_status_array)) && $ticket_status != $completed_dispositioned_term_id) {
        $wpscfunction->change_status($ticket_id, $completed_dispositioned_term_id);
        do_action('wpsc_after_edit_change_ticket_status',$ticket_id);
    }
    
    //If all boxes are in the status of Completed/Dispositioned and in the correct request status they can be archived
    if( count(array_unique($get_box_status_array)) == 1 && in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array ) && ($ticket_status == $completed_dispositioned_term_id || $ticket_status == $cancelled_term_id) ) {
        $status = 1;
    }
    
    //If all boxes are in the status of Cancelled and in the correct request status they can be archived
    if( count(array_unique($get_box_status_array)) == 1 && in_array($box_cancelled_tag->term_id, $get_box_status_array ) && ($ticket_status == $cancelled_term_id || $ticket_status == $completed_dispositioned_term_id) ) {
        $status = 1;
    }
    
    //If there are a mix of Completed/Dispositioned and Cancelled boxes and they are in the request status Completed/Dispositioned they can be archived
    if( count(array_unique($get_box_status_array)) == 2 && ( in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array) && in_array($box_cancelled_tag->term_id, $get_box_status_array) ) && ($ticket_status == $completed_dispositioned_term_id || $ticket_status == $cancelled_term_id) ) {
        $status = 1;
    }

    if($status == 1 && $recall_decline == 0) {
        //set request to inactive
        $data_update = array('active' => 0);
        $data_where = array('id' => $ticket_id);
        $wpdb->update($wpdb->prefix . 'wpsc_ticket' , $data_update, $data_where);
        do_action('wpsc_after_recycle', $ticket_id);
        
        //BEGIN CLONING DATA TO ARCHIVE
        Patt_Custom_Func::send_to_archive($ticket_id);
        
        //Archive audit log
        Patt_Custom_Func::audit_log_backup($ticket_id);

        
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