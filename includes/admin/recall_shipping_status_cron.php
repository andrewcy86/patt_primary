<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// UPDATE to update database based on list of items that are listed as shipped '1'.

global $current_user, $wpscfunction, $wpdb;

//Get term_ids for recall status slugs
$status_recalled_term_id = Patt_Custom_Func::get_term_by_slug( 'recalled' );
$status_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' );
$status_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	
$status_approved_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-approved' );	
$status_shipped_term_id = Patt_Custom_Func::get_term_by_slug( 'shipped' );	
$status_on_loan_term_id = Patt_Custom_Func::get_term_by_slug( 'on-loan' );	
$status_shipped_back_term_id = Patt_Custom_Func::get_term_by_slug( 'shipped-back' );	
$status_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' );	

// For Recall Status to change from Recall Approved [877] to Shipped [730]
/*
$shipped_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = 877
      ORDER BY shipping.id ASC"
	);
*/
$shipped_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as id_recall_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as recall_box_id
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = " . $status_approved_term_id .
      " ORDER BY shipping.id ASC"
	);

/* // OLD before Recall Approve / Recall Deny statuses.
// For Recall Status to change from Recalled [729] to Shipped [730]
$shipped_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = 729
      ORDER BY shipping.id ASC"
	);
*/
	
// For Recall Status to change from Recall Approved [729] to Shipped [730]
foreach ($shipped_recall_status_query as $item) {
	
	// update recall status to Shipped [730]
	$recall_id = $item->recall_id;	
	$where = [ 'id' => $recall_id ];
// 	$data_status = [ 'recall_status_id' => 730 ]; //change status from Recall Approved to Shipped 
	$data_status = [ 'recall_status_id' => $status_shipped_term_id ]; //change status from Recall Approved to Shipped 
	$obj = Patt_Custom_Func::update_recall_data( $data_status, $where );
	
	// Update recall db request_receipt_date when shipped. 
	$where = [ 'id' => $recall_id ];
	$current_datetime = date("Y-m-d H:i:s");
 	$data = [ 'request_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_recall_data( $data, $where );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
/*
	$data = [
		'company_name' => '',
		'tracking_number' => '',
 		'shipped' => 0,
		'status' => ''
	];
	$where = [
		'recall_id' => $recall_id
	];

	$recall_array = Patt_Custom_Func::update_recall_shipping( $data, $where );	
*/
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->recall_box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->id_recall_id,   
		'type' => 'Shipped',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
	
}




// For Recall Status to change from Shipped [730] to On Loan [731]
/*
$on_loan_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        rr.recall_status_id = 730
      ORDER BY shipping.id ASC"
	);
*/

$on_loan_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as id_recall_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as recall_box_id
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        rr.recall_status_id = " . $status_shipped_term_id .
      " ORDER BY shipping.id ASC"
	);
	
// For Recall Status to change from Shipped [730] to On Loan [731]
foreach ($on_loan_recall_status_query as $item) {
	
	// update recall status to On Loan [731]
	$recall_id = $item->recall_id;	
	$where = [ 'id' => $recall_id ];
// 	$data_status = [ 'recall_status_id' => 731 ]; //change status from Shipped to On Loan 
	$data_status = [ 'recall_status_id' => $status_on_loan_term_id ]; //change status from Shipped to On Loan 
	$obj = Patt_Custom_Func::update_recall_data( $data_status, $where );
	
	// Reset the shipping details as the same id is used for shipping to requestor and back to digitization center.
	$data = [
		'company_name' => '',
		'tracking_number' => '',
		'shipped' => 0,
		'delivered' => 0,		
		'status' => ''
	];
	$where = [
		'recall_id' => $recall_id
	];

	$recall_array = Patt_Custom_Func::update_recall_shipping( $data, $where );	
	
	// Update Recall DB Received date 
	$where = [ 'id' => $recall_id ];
	$current_datetime = date("Y-m-d H:i:s");
	$data = [ 'return_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_recall_data( $data, $where );
	
	// Need to update Recall shipping dates in recallrequest table.
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'recall_id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->recall_box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->id_recall_id,   
		'type' => 'On Loan',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
	
}



// For Recall Status to change from On Loan [731] to Shipped Back [732]
/*
$shipped_back_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = 731
      ORDER BY shipping.id ASC"
	);
*/

$shipped_back_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as id_recall_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as recall_box_id
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = " . $status_on_loan_term_id .
      " ORDER BY shipping.id ASC"
	);
	
// For Recall Status to change from On Loan [731] to Shipped Back [732]
foreach ($shipped_back_recall_status_query as $item) {
	
	// update recall status to Shipped Back [732]
	$recall_id = $item->recall_id;	
	$where = [ 'id' => $recall_id ];
// 	$data_status = [ 'recall_status_id' => 732 ]; //change status from On Loan to Shipped Back
	$data_status = [ 'recall_status_id' => $status_shipped_back_term_id ]; //change status from On Loan to Shipped Back
	$obj = Patt_Custom_Func::update_recall_data( $data_status, $where );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
	
	// Update recall db request_receipt_date when shipped. 
	$where = [ 'id' => $recall_id ];
	$current_datetime = date("Y-m-d H:i:s");
 	$data = [ 'request_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_recall_data( $data, $where );
	
	
	
	
	// Set PM Notifications 
	$notification_post = 'email-recall-id-has-been-shipped-back';
	
	// Get digitization staff
	$agent_admin_group_name = 'Administrator';
	$pattagentid_admin_array = Patt_Custom_Func::agent_from_group( $agent_admin_group_name );
	 
	$agent_manager_group_name = 'Manager';
	$pattagentid_manager_array = Patt_Custom_Func::agent_from_group( $agent_manager_group_name );
	
	// Get people on Recall 
	$where = [
		'recall_id' => $recall_id
	];
	$recall_data = Patt_Custom_Func::get_recall_data( $where );

	$agent_id_array = Patt_Custom_Func::translate_user_id( $recall_data[0]->user_id, 'agent_term_id' );;
	
	// Merge the 3 arrays, and remove any duplicates
	$pattagentid_array = array_unique(array_merge( $agent_id_array, $pattagentid_admin_array, $pattagentid_manager_array ));
	
	$requestid = 'R-'.$recall_id; 			
	$data = [
        'action_initiated_by' => $current_user->display_name
    ];
	$email = 0;
	
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'recall_id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->recall_box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->id_recall_id,   
		'type' => 'Shipped Back',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
}



// For Recall Status to change from Shipped Back [732] to Recall Complete [733]
/*
$recall_complete_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        rr.recall_status_id = 732
      ORDER BY shipping.id ASC"
	);
*/

$recall_complete_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as the_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as box_id,
      rr.recall_complete as recall_complete,
      rr.saved_box_status as saved_box_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        rr.recall_status_id = " . $status_shipped_back_term_id .
      " ORDER BY shipping.id ASC"
	);



// For Recall Status to change from Shipped Back [732] to Recall Complete [733]
foreach ($recall_complete_recall_status_query as $item) {
	
	// Data for Audit logs
	$dub = array( 'id' => $item->the_id );
	$recall = Patt_Custom_Func::get_recall_data( $dub );
	$recall_data = $recall[0];
	
	$ticket_id = $recall_data->ticket_id;
	$box_id = $recall_data->box_id; 
	$folderdoc_id = $recall_data->folderdoc_id; 
	$status_id = $recall_data->saved_box_status; 
	$recall_id = $recall_data->recall_id;
	
	
	
	//
	// Restore the saved Box Status
	//	
	$saved_box_status = Patt_Custom_Func::existing_recall_box_status( $item->box_id );
	// if only 1 recalled file, restore status
	if( $saved_box_status['num'] == 1)  {
		$box_status = $item->saved_box_status;
		
		$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
		$data_where = array( 'id' => $item->box_id );
		$data_update = array( 'box_status' => $box_status );
		$wpdb->update( $table_name, $data_update, $data_where );
		
		
		// Audit log for changed box status
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'terms WHERE term_id = '.$status_id;
		$status_info = $wpdb->get_row( $sql );
		$status_name = $status_info->name;
		
		$sql = 'SELECT box_id FROM ' . $wpdb->prefix . 'wpsc_epa_boxinfo WHERE box_id = "'.$box_id . '"';
		$box_info = $wpdb->get_row( $sql );
		$item_id = $box_info->box_id;
		
		$status_full = 'Waiting on RLO to ' . $status_name;
		
		do_action('wpppatt_after_box_status_update', $ticket_id, $status_full, $item_id );

	} 
	
	
	if($item->recall_complete == 1) {
    	// update recall status to Recall Complete [733]
    	$recall_id = $item->recall_id;	
    	$where = [ 'id' => $recall_id ];
    // 	$data_status = [ 'recall_status_id' => 733 ]; //change status from On Loan to Shipped Back
    	$data_status = [ 'recall_status_id' => $status_complete_term_id ]; //change status from On Loan to Shipped Back
    	$obj = Patt_Custom_Func::update_recall_data( $data_status, $where );
    	
    	// Update Recall DB Received date 
    	$where = [ 'id' => $recall_id ];
    	$current_datetime = date("Y-m-d H:i:s");
    	$data = [ 'return_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
    	Patt_Custom_Func::update_recall_data( $data, $where );
	}
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
	
	// Audit log for Recall Complete
	if( $folderdoc_id == null || $folderdoc_id == '' ) {
		$item_id = $box_id;
	} else {
		$item_id = $folderdoc_id;
	}
	
	do_action('wpppatt_after_recall_completed', $ticket_id, 'R-'.$recall_id, $item_id );
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'recall_id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->the_id,   
		'type' => 'Recall Complete',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
}



?>
