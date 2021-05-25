<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// set default filter for agents and customers //not true
global $wpdb, $current_user, $wpscfunction;

if (!$current_user->ID) die();

$title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
$customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
$customer_email = isset($_POST['customer_email']) ? sanitize_text_field($_POST['customer_email']) : '';
$recall_comment = isset($_POST['recall_comment']) ? sanitize_text_field($_POST['recall_comment']) : '';
$item_id = isset($_POST['item_id']) ? sanitize_text_field($_POST['item_id']) : '';

$box_fk = $_POST['box_fk'];
$folderdoc_fk = $_POST['folderdoc_fk'];
$folderdoc_files_fk = $_POST['folderdoc_files_fk'];

$assigned_agent_ids = $_REQUEST['assigned_agent_ids'];

$program_office = isset($_POST['program_office']) ? sanitize_text_field($_POST['program_office']) : '';
$record_schedule = isset($_POST['record_schedule']) ? sanitize_text_field($_POST['record_schedule']) : '';

$db_null = -99999;
$date_null = '0000-00-00 00:00:00';

$current_datetime = date("Y-m-d H:i:s"); //("yy-m-d H:i:s"); //New "m-d-yy H:i:s"
$expiration_date = date_create($current_datetime);
date_add($expiration_date,date_interval_create_from_date_string("30 days"));
$expiration_date_string = date_format($expiration_date,"Y-m-d H:i:s");

// $current_user_id = $current_user->ID;

//won't work, as it's the user's display_name
//$submitted_user_obj = get_user_by('login', $customer_name );
//$submitted_user_obj = get_user_by('id', '6' );
// Single Submitted user name - orginal method
$args= array(
  'search' => $customer_name, // or login or nicename in this example
  'search_fields' => array('user_login','user_nicename','display_name')
);
$submitted_user_obj = new WP_User_Query($args);
$submitted_user_id = $submitted_user_obj->results[0]->ID;


// Array of Submitted users - by wpsc agent id (term id)
$agent_ids = array();
$agents = get_terms([
	'taxonomy'   => 'wpsc_agents',
	'hide_empty' => false,
	'orderby'    => 'meta_value_num',
	'order'    	 => 'ASC',
]);
foreach ($agents as $agent) {
	$agent_ids[] = [
		'agent_term_id' => $agent->term_id,
		'wp_user_id' => get_term_meta( $agent->term_id, 'user_id', true),
	];
}

$wp_user_ids = array();
foreach( $assigned_agent_ids as $term_id ) {
	$key = array_search($term_id, array_column($agent_ids, 'agent_term_id'));
	$agent_term_id = $agent_ids[$key]['wp_user_id']; //current user agent term id
	$wp_user_ids[] = $agent_term_id;
}

//
// Get box status for save/restore
//

// Get current box status
$box_id_array = Patt_Custom_Func::get_box_id_by_id( $box_fk );
$box_id = $box_id_array[0];
$box_obj = Patt_Custom_Func::get_box_file_details_by_id( $box_id );
$box_status = $box_obj->box_status;

// Check existing Recalls to determine if that Box id already has a Recall in it, 
// if so, change restore box status, to the stored saved_box_status from Box. 
$saved_box_status = Patt_Custom_Func::existing_recall_box_status( $box_fk );
if( $saved_box_status['num'] ) {
	$box_status = $saved_box_status['saved_box_status'];
}


// create function for FK on FDIF ($folderdoc_files_fk)

if( !taxonomy_exists('wpsc_box_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
} 

$status_waiting_rlo_term_id = get_term_by( 'slug', 'waiting-on-rlo', 'wpsc_box_statuses' );	 
$status_waiting_rlo_term_id = $status_waiting_rlo_term_id->term_id;



// Get term_id for recall status slug
$recall_status_term_id = Patt_Custom_Func::get_term_by_slug( 'recalled' );

$data = [ 
	'box_id' => $box_fk, 	 
//	'folderdoc_id' => $folderdoc_fk,
	'folderdoc_id' => $folderdoc_files_fk,	  	
	'program_office_id' => $program_office, 
	'shipping_tracking_id' => $db_null, 
	'record_schedule_id' => $record_schedule, 
// 	'user_id' => $submitted_user_id, 
// 	'user_id' => [5,6], 
	'user_id' => $wp_user_ids, 
// 	'recall_status_id' => 729, 
	'recall_status_id' => $recall_status_term_id, 
	'expiration_date' => $expiration_date_string, 
	'request_date' => $current_datetime, 
	'request_receipt_date' => $date_null,
	'return_date' => $date_null,
	'updated_date' => $current_datetime, 
	'comments' => $recall_comment, 
	'saved_box_status' => $box_status
];

$recall_id = Patt_Custom_Func::insert_recall_data($data);

if($recall_id == 0) {
	$error_message = 'Recall Not Submitted';
} else {
	$error_message = 'No Errors';
	$recall_id_old = $recall_id;
	$recall_id = 'R-'.$recall_id;
	
	// Add original requester to the Recall
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_recall_id( $recall_id_old );
	$wp_user_ticket = Patt_Custom_Func::get_wp_user_id_by_ticket_id( $ticket_id );
	$wp_user_ticket_array = [ $wp_user_ticket ];
	$new_wp_users_array = array_merge( $wp_user_ticket_array, $wp_user_ids);
	$new_wp_users_array = array_unique( $new_wp_users_array ); 
	
	// Update the Users associated with the Recall. 
	$data = [
			'recall_id' => $recall_id_old,			
			'user_id' => $new_wp_users_array
		];
	Patt_Custom_Func::update_recall_user_by_id( $data );

	
	// Audit Log wpppatt_after_recall_created
	$where = ['recall_id' => $recall_id_old ];
	$recall_array = Patt_Custom_Func::get_recall_data($where);
	
	//Added for servers running < PHP 7.3
	if (!function_exists('array_key_first')) {
	    function array_key_first(array $arr) {
	        foreach($arr as $key => $unused) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first($recall_array);		
	$recall_obj = $recall_array[$recall_array_key];
	
	
	//
	// Update Box status to Waiting on RLO
	//
	$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
	$data_where = array( 'id' => $box_fk );
	$data_update = array( 'box_status' => $status_waiting_rlo_term_id );
	$wpdb->update( $table_name, $data_update, $data_where );
	
	//
	// Add Recall Comment to Threaded Comments.
	// $recall_comment
	
	// Prepare arguments
	$id_box_id;
	
	//if($recall_obj->box_id > 0 && $recall_obj->folderdoc_id == $db_null ) {
	//if( $recall_obj->folderdoc_id == $db_null ) {	
	if( $recall_obj->folderdoc_id == null ) {		
		$recall_type = "Box";
		$item_id = $recall_obj->box_id;
		$the_box_id = $recall_obj->box_id;
		$id_box_id = $recall_obj->recall_box_id;
// 	} elseif ( $recall_obj->box_id > 0 && $recall_obj->folderdoc_id !== $db_null ) {
	} elseif ( $recall_obj->box_id > 0 && $recall_obj->folderdoc_id !== null ) {
		$recall_type = "Folder/File";
		$item_id = $recall_obj->folderdoc_id;
		$the_box_id = $recall_obj->box_id;
		$id_box_id = $recall_obj->recall_box_id;
	} 
	
	//echo 'item_id: ' . $item_id . PHP_EOL;
	//$item_id_first = $item_id;
	$where = [ 'box_folder_file_id' => $item_id ];
	$ticket_array = Patt_Custom_Func::get_ticket_id_from_box_folder_file( $where );
	$ticket_id = $ticket_array['ticket_id'];
	
	if( $current_user->ID ) {
		$customer_name  = $current_user->display_name;
		$customer_email = $current_user->user_email;
	} else {
		$customer_name  = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
		$customer_email = isset($_POST['customer_email']) ? sanitize_text_field($_POST['customer_email']) : '';
	}
	
	if( !$customer_name || !$customer_email ) die( 'no customer name or email' );
	
	$attachments = array();
	
	$ip_address	= isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'';
	if (strlen($ip_address)>28) {
		$ip_address = '';
	}
	$os_platform = $wpscfunction->get_os();
	$browser	 = $wpscfunction->get_browser();
	
	$ticket_raised_by = $wpscfunction->get_ticket_fields( $ticket_id, 'customer_email' );
	$user_seen = 'null';
	if( $current_user->user_email == $ticket_raised_by ){
		$user_seen = date("Y-m-d H:i:s");
	}
	
	
	$args = array(
		'recall_id'      => $recall_id, // check
		'reply_body'     => $wpscfunction->replace_macro( $recall_comment, $ticket_id), // GET $ticket_id
		'customer_name'  => $customer_name, 
		'customer_email' => $customer_email,
		'attachments'    => $attachments,
		'thread_type'    => 'reply',
		'ip_address'	 => $ip_address,
		'reply_source'	 => 'browser',
		'os'			 => $os_platform,
		'browser'		 => $browser,
		'user_seen'		 => $user_seen
	);
	
/*
	echo '<br>args: <br>';
	print_r($args);
	echo PHP_EOL;
	echo 'POST[reply_bcc]: ' . $_POST['reply_bcc'].PHP_EOL;
	echo 'ticket_id: ' . $ticket_id.PHP_EOL;
*/
	
	//die();
	
	$args = apply_filters( 'wpsc_thread_args', $args );
	// $thread_id = $wpscfunction->submit_ticket_thread($args);
	
	// Add initial comment to the recall thread
	$thread_id = Patt_Custom_Func::submit_recall_thread( $args ); 

	
	
	
	
	//
	// Audit Log
	//
	

	
/*
	if($recall_obj->box_id > 0 && $recall_obj->folderdoc_id == $db_null ) {
		$recall_type = "Box";
		$item_id = $recall_obj->box_id;
	} elseif ($recall_obj->box_id > 0 && $recall_obj->folderdoc_id !== $db_null) {
		$recall_type = "Folder/File";
		$item_id = $recall_obj->folderdoc_id;
	} 
*/
	
	$item_name_and_id_str = $recall_type .': '. $item_id;
	$box_status_message = 'Box: ' . $the_box_id . '\'s status has changed to <strong>Waiting on RLO</strong>';
	
	do_action('wpppatt_after_recall_created', $recall_obj->ticket_id, $recall_id, $item_name_and_id_str, $box_status_message );
	
	//
	// Set PM Notifications 
	//
	$notifications = '';
	//$notification_post = 9223;
	
	//$pattagentid_array = $assigned_agent_ids;	
	$pattagentid_admin_array = Patt_Custom_Func::agent_from_group( 'Administrator' );
	$pattagentid_array = array_merge( $pattagentid_admin_array, $assigned_agent_ids );
	$pattagentid_array = array_unique( $pattagentid_array );
	
	// Split up array of users on Recall into 2 arrays: digitization staff and requesters
	$role_array_digi_staff = [ 'Administrator', 'Manager', 'Agent' ];
	$results_digi = Patt_Custom_Func::return_agent_ids_in_role( $pattagentid_array, $role_array_digi_staff);
	
	$role_array_requester = [  'Requester', 'Requester Pallet' ];
	$results_requester = Patt_Custom_Func::return_agent_ids_in_role( $pattagentid_array, $role_array_requester);
	
	$requestid = $recall_id; 
	
	$data = [
        'item_type' => $recall_type, 
        'item_id' => $item_id,
        'action_initiated_by' => $current_user->display_name
    ];
	$email = 0;
	
	// Old: to everyone. 
	//$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
	
	//
	// PM Notification to the Digitization Staff
	//
	
//	$notification_post = 'email-recall-id-has-been-approved-digitization-staff';	
	$notification_post = 'email-id-has-been-recalled-digitization-staff';	
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $results_digi, $requestid, $data, $email );
	
	if($new_notification != 'Invalid Message Type' && is_numeric( $new_notification )) {
		$notifications .= "Recall Notification sent to digi staff.";
	} else {
		$notifications .= "Notification not sent. To Digi Staff.";
	}
	
	//
	// PM Notification to the Requestors
	//
	
	$notification_post = 'email-id-has-been-recalled-requester';
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $results_requester, $requestid, $data, $email );
	
	if($new_notification != 'Invalid Message Type' && is_numeric( $new_notification )) {
		$notifications .= "Notification sent to requestor.";
	} else {
		$notifications .= "Notification not sent to requester.";
	}
	
	//
	// Timestamp Table
	//
	
		
	//$dc = Patt_Custom_Func::get_dc_array_from_ticket_id( $ticket_id );
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $id_box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $recall_obj->id,   //$recall_id,
		'type' => 'Recalled',
		'user' => $current_user->user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );


}


$output = array(
  'customer_name'   => $customer_name,
  'title' => $recall_obj->folderdoc_id,
  'date' => date_format($expiration_date,"Y-m-d H:i:s"),
  'recall_id' => $recall_id,
  'error'  => $error_message,
  'notifications' => $notifications,
  'item_id' => $item_id,
  'ticket_id' => $ticket_id,
  'args' => $args,
  'debug' => $recall_obj
);
echo json_encode($output);
