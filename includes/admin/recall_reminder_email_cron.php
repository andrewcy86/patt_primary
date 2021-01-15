<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Send email to remind requestors that recalled document needs to be shipped back. 

global $current_user, $wpscfunction, $wpdb;

//Get term_ids for recall status slugs
/*
$status_recalled_term_id = Patt_Custom_Func::get_term_by_slug( 'recalled' );
$status_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' );
$status_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	
$status_approved_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-approved' );	
$status_shipped_term_id = Patt_Custom_Func::get_term_by_slug( 'shipped' );	
$status_on_loan_term_id = Patt_Custom_Func::get_term_by_slug( 'on-loan' );	
$status_shipped_back_term_id = Patt_Custom_Func::get_term_by_slug( 'shipped-back' );	
$status_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' );	
*/

//Get term_ids for Recall status slugs
$status_recall_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	 // 878
$status_recall_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' ); //734
$status_recall_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' ); //733

// Get today's date
// Get all recallrequest that have expiration_date of today.
// Loop through each: send email to requestor, update expiration_date to 30 days from now. 


// $current_datetime = date("yy-m-d H:i:s"); //("yy-m-d H:i:s"); //New "m-d-yy H:i:s"
$current_datetime = date("yy-m-d"); //("yy-m-d H:i:s"); //New "m-d-yy H:i:s"
//$current_date = date_create($current_datetime);


// Get all entries that expire today
$recalls_expired_today = $wpdb->get_results(
	"SELECT
	    *
	FROM
	    `wpqa_wpsc_epa_recallrequest`
	WHERE
	    expiration_date LIKE '" . $current_datetime . "%' 
	AND recall_status_id <> " . $status_recall_denied_term_id . 
  " AND recall_status_id <> " . $status_recall_cancelled_term_id . 
  " AND recall_status_id <> " . $status_recall_complete_term_id
);

// Set new expiration_date
$expiration_date = Date('yy-m-d', strtotime('+30 days'));

foreach ( $recalls_expired_today as $recall ) {
	
	// Set PM Notifications 
	
	// Get ticket_id based on recall_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_recall_id( $recall->recall_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Recall and filter for Requestors
	$where = [ 'recall_id' => $recall->recall_id ]; // format: '0000002'
	$recall_obj_array = Patt_Custom_Func::get_recall_data( $where );
	$requestor_agent_id_array = Patt_Custom_Func::translate_user_id( $recall_obj_array[0]->user_id, 'agent_term_id' ); 
	
	$role_array_requester = [  'Requester' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $requestor_agent_id_array, $role_array_requester);
	
	// Combine Requester on Request with Requesters on Recall
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
	$requestid = 'R-' . $recall->recall_id; 
	
	$data = [
        'item_id' => $requestid
    ];
	$email = 1;
		
	// PM Notification to the Requestor / owner
	$notification_post = 'email-recall-expired';	
// 	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $agent_id_array, $requestid, $data, $email );
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
	// Update recall db expiration_date
	$where = [ 'id' => $recall->id ];
	$current_datetime = date("yy-m-d H:i:s");
 	$data = [ 'expiration_date' => $expiration_date ]; 
	Patt_Custom_Func::update_recall_data( $data, $where );
	
}







?>
