<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// UPDATE to update database based on list of items that are listed as shipped '1'.

global $current_user, $wpscfunction, $wpdb;

//Get term_ids for recall status slugs - OLD
/*
$status_decline_initiated_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-initiated' );	 
$status_decline_shipped_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-shipped' ); 
$status_decline_pending_cancel_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-pending-cancel' );
$status_decline_shipped_back_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-shipped-back' ); 
$status_decline_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-complete' ); 
$status_decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' ); 
*/

// Set Dates
$current_date = date("Y-m-d"); 
$two_weeks_ago = Date('Y-m-d', strtotime('-14 days'));
$four_weeks_ago = Date('Y-m-d', strtotime('-30 days'));
$two_weeks_ahead = Date('Y-m-d', strtotime('14 days'));
$four_weeks_ahead = Date('Y-m-d', strtotime('30 days'));

//Get term_ids for Decline status slugs
if( !taxonomy_exists('wppatt_return_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wppatt_return_statuses', 'wpsc_ticket', $args );
} 

$status_decline_initiated_term_id = get_term_by( 'slug', 'decline-initiated', 'wppatt_return_statuses' );	 
$status_decline_shipped_term_id = get_term_by( 'slug', 'decline-shipped', 'wppatt_return_statuses' ); 
$status_decline_pending_cancel_term_id = get_term_by( 'slug', 'decline-pending-cancel', 'wppatt_return_statuses' );
$status_decline_shipped_back_term_id = get_term_by( 'slug', 'decline-shipped-back', 'wppatt_return_statuses' ); 
$status_decline_complete_term_id = get_term_by( 'slug', 'decline-complete', 'wppatt_return_statuses' ); 
$status_decline_cancelled_term_id = get_term_by( 'slug', 'decline-cancelled', 'wppatt_return_statuses' ); 

$status_decline_initiated_term_id = $status_decline_initiated_term_id->term_id;
$status_decline_shipped_term_id = $status_decline_shipped_term_id->term_id;
$status_decline_pending_cancel_term_id = $status_decline_pending_cancel_term_id->term_id;
$status_decline_shipped_back_term_id = $status_decline_shipped_back_term_id->term_id;
$status_decline_complete_term_id = $status_decline_complete_term_id->term_id;
$status_decline_cancelled_term_id = $status_decline_cancelled_term_id->term_id;


//
// For Decline Status to change from Decline Initiated to Decline Shipped
//
$shipped_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        ( ret.return_status_id = " . $status_decline_initiated_term_id . " OR ret.return_status_id = " . $status_decline_cancelled_term_id
      . ") ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Initiated [752] to Decline Shipped [753]
foreach ($shipped_return_status_query as $item) {
	
	// update Decline status to Decline Shipped [753]
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_shipped_term_id ]; //change status from Decline Initiated to Decline Shipped
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	
	// Update Decline (Return) ship date  when it is shipped.
	$where = [ 'id' => $return_id ];
	$current_datetime = date( "Y-m-d H:i:s" );
 	$data = [ 'return_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	
	// Prep Timestmp Table data. 
	// Get Decline obj
	
	$where = [
		'return_id' => $return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$return_array_key = array_key_first( $return_array );
	$return_obj = $return_array[ $return_array_key ];

	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Decline Shipped',
		'user' => $current_user->user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
	// No PM Notification for shipping Decline
	
	// CORNER CASE
	// If return status was cancelled, but was still picked up, use the normal statuses, but send PM Message to admin and requestor
	if( $item->return_status == $status_decline_cancelled_term_id ) {
		
		//
		// PM Notification :: Decline Cancelled item shipped
		//
	
		// Get ticket_id based on return_id
		$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
		
		// Get owner of ticket. 
		$where = [ 'ticket_id' => $ticket_id ];
		$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
		$role_array_requester = [ 'Requester', 'Requester Pallet' ];
		$agent_id_array = Patt_Custom_Func::return_agent_ids_in_role( $agent_id_array, $role_array_requester);
		
		// Get all users on Decline (currently only the person who submitted it, and no way to add others)
		$where = [ 'return_id' => $return_id ]; // format: '0000002'
		$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
		$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
		
		// Redundant as only one initiator allowed on Decline currently (which is the person who submitted decline). 
		$role_array_admin = [ 'Administrator', 'Manager' ];
		$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_admin);
		
		// Combine Requester on Request with Admin's on Decline
		$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
		$pattagentid_array = array_unique( $pattagentid_array );
		
		$requestid = 'D-' . $return_id; 
		
		$data = [
	        //'item_id' => $requestid
	    ];
		$email = 1;
		
		$notification_post = 'email-decline-cancelled-but-shipped';
			
		// PM Notification to the Requestor / owner
		$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	

		
	}
	
}


//
// For Decline Status to change from Decline Shipped to Decline Pending Cancelled (equivolent to Received or On Loan) (OLD: Decline Complete)
//
$return_complete_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        ret.return_status_id = " . $status_decline_shipped_term_id . 
      " ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Shipped to Received
foreach ($return_complete_return_status_query as $item) {
	
	// update Decline status to Decline Pending Cancelled
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_pending_cancel_term_id ]; 
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	

	
	// Clear shipping table data 
	// Reset the shipping details as the same id is used for shipping to requestor and back to digitization center.
	$data = [
		'company_name' => '',
		'tracking_number' => '',
		'shipped' => 0,
		'delivered' => 0,		
		'status' => ''
	];
	$where = [
		'return_id' => $return_id
	];

	$return_array = Patt_Custom_Func::update_return_shipping( $data, $where );	
	
	// Update Decline DB Received date
	$where = [ 'id' => $return_id ];
	$current_datetime = date("Y-m-d H:i:s");
	$data = [ 'received_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	// Update Decline DB expiration_date
	$where = [ 'id' => $return_id ];
	$data = [ 'expiration_date' => $four_weeks_ahead ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	
	
	
	// Prep Timestmp Table data. 
	// Get Decline obj
	
	$where = [
		'return_id' => $return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$return_array_key = array_key_first( $return_array );
	$return_obj = $return_array[ $return_array_key ];

	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Received',
		'user' => $current_user->user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );

	

	//
	// PM Notification :: 4 week timer started
	//

	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-arrived-at-requester';
		
	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
}


//
// Decline 2 week PM Notification to fix box and ship back to Digitization Center
//
$return_pending_cancel_2week_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        ret.return_status_id = " . $status_decline_pending_cancel_term_id . 
    " AND 
    	expiration_date LIKE '" . $two_weeks_ahead . "%'  
      ORDER BY shipping.id ASC"
	);

	
// Decline 2 week PM Notification to fix box and ship back to Digitization Center
foreach ($return_pending_cancel_2week_status_query as $item) {
	
	//
	// PM Notification :: 2 week notice
	//
	$return_id = $item->return_id;	
	
	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-2-week-notification';
		
	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
}


//
// Check and Cancel Declines that have not been updated after 4 weeks. 
//
$return_complete_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        ret.return_status_id = " . $status_decline_pending_cancel_term_id . 
    " AND 
    	expiration_date LIKE '" . $current_date . "%'  
      ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Pending Cancel to Decline Cancelled
foreach ($return_complete_return_status_query as $item) {
	
	// update Decline status from Decline Pending Cancel to Cancelled
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_cancelled_term_id ]; //change status from Decline Pending Cancel to Cancelled 
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	
	// Update Decline DB updated_date
	$where = [ 'id' => $return_id ];
	$current_datetime = date("Y-m-d H:i:s");
	$data = [ 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	// Set all boxes inside the Decline to have Box Status: Cancelled. // TEST ONCE NEW BOX STATUS CREATED UPDATED
	$where = [
		'return_id' => $item->return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	$return_obj = $return_array[0];
	$return_box_array = $return_obj->box_id;
	
	$cancelled_status_id = get_term_by( 'slug', 'cancelled', 'wpsc_box_statuses' ); //get id from slug
	foreach( $return_box_array as $box ) {
		$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
		$data_update = array('box_status' => $cancelled_status_id->term_id );
		
		$box_id = Patt_Custom_Func::get_box_file_details_by_id( $box )->Box_id_FK;
		$data_where = array('id' => $box_id);
		$wpdb->update($table_name, $data_update, $data_where);
	}
		
	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Decline Cancelled',
		'user' => $current_user->user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	//
	// PM Notification :: Decline Cancelled
	//

	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-cancelled';
		
	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
}


//
// For Decline Status to change from Decline Pending Cancel to Decline Shipped Back
//
$shipped_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        ret.return_status_id = " . $status_decline_pending_cancel_term_id .
      " ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Pending Cancel to Decline Shipped Back
foreach ($shipped_return_status_query as $item) {
	
	// update Decline status to Decline Shipped Back
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_shipped_back_term_id ]; 
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	
	// Update Decline db table (Return) when it is shipped.
	$where = [ 'id' => $return_id ];
	$current_datetime = date("Y-m-d H:i:s");
 	$data = [ 'return_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	
	// Prep Timestmp Table data. 
	// Get Decline obj
	
	$where = [
		'return_id' => $return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$return_array_key = array_key_first( $return_array );
	$return_obj = $return_array[ $return_array_key ];

	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Decline Shipped Back',
		'user' => $current_user->user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
	// No PM Notification for shipping Decline Back
}


//
// For Decline Status to change from Decline Shipped Back to Decline Complete
//
$return_complete_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        ret.return_status_id = " . $status_decline_shipped_back_term_id . 
      " ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Shipped Back to Decline Complete
foreach ($return_complete_return_status_query as $item) {
	
	// update Decline status to Decline Complete
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_complete_term_id ]; 
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	
	// Update Decline DB Received date
	$where = [ 'id' => $return_id ];
	$current_datetime = date("Y-m-d H:i:s");
	$data = [ 'received_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	$ticket_id = ltrim( $ticket_id, '0' );
	
	//
	// Set Box status back to original status before Decline
	//  
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_obj = $decline_obj_array[0];
	
	foreach( $decline_obj->box_id as $key => $box_id ) {
		
		$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
		$data_where = array( 'box_id' => $box_id );
		$data_update = array( 'box_status' => $decline_obj->saved_box_status[$key] );
		$wpdb->update( $table_name, $data_update, $data_where );
		
		// Box Status Audit Log
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'terms WHERE term_id = ' . $decline_obj->saved_box_status[$key];
		$status_info = $wpdb->get_row( $sql );
		$status_name = $status_info->name;
		
		$status_full = 'Waiting on RLO to ' . $status_name;
		do_action('wpppatt_after_box_status_update', $ticket_id, $status_full, $box_id );	
		
	}


	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $decline_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'decline_id' => $decline_obj->id,   
		'type' => 'Decline Complete',
		'user' => $current_user->user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	
	
	// Decline Audit Log
	
	do_action('wpppatt_after_return_completed', $ticket_id, 'D-'.$return_id );

	//
	// PM Notification :: Requester & Digitization Staff - Decline Complete
	//

	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Get digitization staff
	$agent_admin_group_name = 'Administrator';
	$pattagentid_admin_array = Patt_Custom_Func::agent_from_group( $agent_admin_group_name );
	 
	$agent_manager_group_name = 'Manager';
	$pattagentid_manager_array = Patt_Custom_Func::agent_from_group( $agent_manager_group_name );
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array, $pattagentid_admin_array, $pattagentid_manager_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
// 	$requestid = 'D-' . $decline->return_id; 
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-complete';
		
	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
}






?>
