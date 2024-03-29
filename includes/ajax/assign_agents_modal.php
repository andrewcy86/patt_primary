<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// set default filter for agents and customers //not true
/*
global $current_user, $wpscfunction;
if (!$current_user->ID) die();
*/

global $current_user, $wpscfunction, $wpdb;
if (!($current_user->ID && $current_user->has_cap('wpsc_agent'))) {
	exit;
}

include( WPPATT_ABSPATH . 'includes/term-ids.php' );

$subfolder_path = site_url( '', 'relative'); 

//
// Originals. 
//
$ticket_id   = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '' ; 
$current_requestor = isset($_POST['requestor']) ? sanitize_text_field($_POST['requestor']) : '' ;
$wpsc_appearance_modal_window = get_option('wpsc_modal_window');
$recall_id = isset($_POST['recall_id']) ? sanitize_text_field($_POST['recall_id']) : '';

$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$initial_review_complete_tag = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses'); //4
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003

// Digitization Center not assigned
$dc_not_assigned_obj = get_term_by('slug', 'not-assigned-digi-center', 'wpsc_categories'); //666
$dc_not_assigned = $dc_not_assigned_obj->term_id;

$status_id_arr = array( $new_request_tag->term_id, $tabled_tag->term_id, $initial_review_rejected_tag->term_id, $cancelled_tag->term_id, $completed_dispositioned_tag->term_id);

//
// Get items
//
$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
$item_ids = $_REQUEST['item_ids']; 
$recall_ids = $_REQUEST['recall_ids']; 
$decline_ids = $_REQUEST['decline_ids']; 
$num_of_items = count($item_ids);
//$ticket_id = 1;
//$ticket_id = '0000001';
$is_single_item = ( $num_of_items == 1 ) ? true : false;
$alerts_disabled = ( $type == 'view' ) ? true : false;


//
// Prep data for To-Do list
//

// get all pertinent term_ids
//Box status slugs
$scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); 
$scanning_digitization_tag = get_term_by('slug', 'scanning-digitization', 'wpsc_box_statuses'); 
$qa_qc_tag = get_term_by('slug', 'q-a', 'wpsc_box_statuses'); 
$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); 
$destruction_approved_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); 
$destruction_of_source_tag = get_term_by('slug', 'destruction-of-source', 'wpsc_box_statuses'); 

$scanning_preparation_term = $scanning_preparation_tag->term_id;
$scanning_digitization_term = $scanning_digitization_tag->term_id;
$qa_qc_term = $qa_qc_tag->term_id;
$validation_term = $validation_tag->term_id;
$destruction_approved_term = $destruction_approved_tag->term_id;
$destruction_of_source_term = $destruction_of_source_tag->term_id;

// Grab all info needed for the box
$sql = 'SELECT
            *
        FROM
            ' . $wpdb->prefix . 'wpsc_epa_boxinfo AS box
        INNER JOIN ' . $wpdb->prefix . 'wpsc_epa_storage_location AS location
        ON
            location.id = box.storage_location_id
        WHERE
            box.box_id = "' . $item_ids[0] . '"';
$todo_obj = $wpdb->get_row( $sql );

// Flags for statuses
$todo_scanning_preparation = $todo_obj->scanning_preparation;
$todo_scanning_digitization = $todo_obj->scanning_digitization;
$todo_qa_qc = $todo_obj->qa_qc;
$todo_validation = $todo_obj->validation;
$todo_destruction_approved = $todo_obj->destruction_approved;
$todo_destruction_of_source = $todo_obj->destruction_of_source;

// used for HTML checkbox checks
$todo_scanning_preparation_check = $todo_scanning_preparation == 1 ? 'checked' : '';
$todo_scanning_digitization_check = $todo_scanning_digitization == 1 ? 'checked' : '';
$todo_qa_qc_check = $todo_qa_qc == 1 ? 'checked' : '';
$todo_validation_check = $todo_validation == 1 ? 'checked' : '';
$todo_destruction_approved_check = $todo_destruction_approved == 1 ? 'checked' : '';
$todo_destruction_of_source_check = $todo_destruction_of_source == 1 ? 'checked' : '';

// used for HTML checkbox disabled
$todo_scanning_preparation_disabled = $scanning_preparation_term == $todo_obj->box_status ? '' : 'disabled';
$todo_scanning_digitization_disabled = $scanning_digitization_term == $todo_obj->box_status ? '' : 'disabled';
$todo_qa_qc_disabled = $qa_qc_term == $todo_obj->box_status ? '' : 'disabled';
$todo_validation_disabled = $validation_term == $todo_obj->box_status ? '' : 'disabled';
$todo_destruction_approved_disabled = $destruction_approved_term == $todo_obj->box_status ? '' : 'disabled';
$todo_destruction_of_source_disabled = $destruction_of_source_term == $todo_obj->box_status ? '' : 'disabled';

// Corner Case: if in destruction approved and destruction approved has already been submitted. Disable the checkbox. 
$todo_destruction_approved_disabled = ( $destruction_approved_term == $todo_obj->box_status && $todo_destruction_approved == 0 ) ? '' : 'disabled';


// Get ticket id from first or single box
$where['box_folder_file_id'] = $item_ids[0]; 
$ticket_id_obj = Patt_Custom_Func::get_ticket_id_from_box_folder_file( $where ); 
$ticket_id = $ticket_id_obj['ticket_id'];

// Get destruction approval from ticket table
$box_destruction_approval = $wpdb->get_row("SELECT destruction_approval FROM ".$wpdb->prefix."wpsc_ticket WHERE id='".$ticket_id."'");				
$todo_ticket_destruction_approval = $box_destruction_approval->destruction_approval;

if( !isset( $todo_ticket_destruction_approval ) ) {
  $todo_ticket_destruction_approval = ' ';
}

$destruction_approval_allowed;

$current_box_status = $todo_obj->box_status;

// Sets a flag for a js alert if box status is at destruction approval AND does not have destruction approval
// only for type == todo. 
// false sets the alert.
if( $todo_obj->box_status == $destruction_approved_term ) {
  if( $todo_ticket_destruction_approval === 1 && $use_type == 'todo' ) {
    $destruction_approval_allowed = true;
  } else {
    $destruction_approval_allowed = false;
  }
}


// get current status - DONE
// set all other statuses to disabled
// turn on save button
// set up save for flag, status, and previous status.
// audit log




/*
function get_tax() {
	$box_statuses = get_terms([
		'taxonomy'   => 'wpsc_box_statuses',
		'hide_empty' => false,
		'orderby'    => 'meta_value_num',
		'order'    	 => 'ASC',
		'meta_query' => array('order_clause' => array('key' => 'wpsc_box_status_load_order')),
	]);
	return $box_statuses
}
add_action('init', 'get_tax', 9999);
*/

//
// Prep data for RECALL To-Do list
//

// get all pertinent term_ids
// Recall status slugs
$recall_recalled_tag = get_term_by('slug', 'recalled', 'wppatt_recall_statuses'); 
$recall_complete_tag = get_term_by('slug', 'recall-complete', 'wppatt_recall_statuses'); 
$recall_cancelled_tag = get_term_by('slug', 'recall-cancelled', 'wppatt_recall_statuses'); 
$recall_approved_tag = get_term_by('slug', 'recall-approved', 'wppatt_recall_statuses'); 
$recall_denied_tag = get_term_by('slug', 'recall-denied', 'wppatt_recall_statuses'); 
$recall_shipped_tag = get_term_by('slug', 'shipped', 'wppatt_recall_statuses');
$recall_shipped_back_tag = get_term_by('slug', 'shipped-back', 'wppatt_recall_statuses');
$recall_received_at_ndc_tag = get_term_by('slug', 'recall-received-at-ndc', 'wppatt_recall_statuses'); 

$recall_recalled_term = $recall_recalled_tag->term_id;
$recall_complete_term = $recall_complete_tag->term_id;
$recall_cancelled_term = $recall_cancelled_tag->term_id;
$recall_approved_term = $recall_approved_tag->term_id;
$recall_denied_term = $recall_denied_tag->term_id;
$recall_shipped_term = $recall_shipped_tag->term_id;
$recall_shipped_back_term = $recall_shipped_back_tag->term_id;
$recall_received_at_ndc_term = $recall_received_at_ndc_tag->term_id;

// Grab all info needed for the recall
$whr = [ 'recall_id' => $recall_ids[0] ]; // recall_ids todo will always contain 1 value.
$recall_todo_arr = Patt_Custom_Func::get_recall_data( $whr ); 
$recall_todo_obj = $recall_todo_arr[0];


// Flags for recall statuses
$todo_recall_approved = $recall_todo_obj->recall_approved;
$todo_recall_complete = $recall_todo_obj->recall_complete;

// used for HTML checkbox checks
$todo_recall_approved_check = $todo_recall_approved == 1 ? 'checked' : '';
$todo_recall_complete_check = $todo_recall_complete == 1 ? 'checked' : '';

// used for HTML checkbox disabled
// Using $recall_recalled_term as that is the status we're in before moving to recall approved. 
// $todo_recall_approved_disabled = $recall_todo_obj->recall_status_id == $recall_recalled_term ? '' : 'disabled';
$todo_recall_approved_disabled = $recall_todo_obj->recall_status_id == $recall_approved_term ? '' : 'disabled';
// $todo_recall_complete_disabled = $recall_todo_obj->recall_status_id == $recall_complete_term ? '' : 'disabled';
$todo_recall_complete_disabled = $recall_todo_obj->recall_status_id == $recall_received_at_ndc_term ? '' : 'disabled';

if( $type == 'recall_todo' ) {
  
  // needed to surpress an error 
  $current_box_status = $recall_todo_obj->recall_status_id;
  

  $ticket_id = $recall_todo_obj->ticket_id;
  $shipping_carrier = $recall_todo_obj->shipping_carrier;
  $tracking_number = $recall_todo_obj->tracking_number;
  
  $recall_save_enabled = true;
  
  
}

// Get all the users on the recall
$recall_assigned_users = $recall_todo_obj->user_id;

//




// Register Recall Status Taxonomy
if( !taxonomy_exists('wppatt_recall_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wppatt_recall_statuses', 'wpsc_ticket', $args );
}

// $box_statuses = get_tax();

// Get List of Recall Statuses
$recall_statuses = get_terms([
	'taxonomy'   => 'wppatt_recall_statuses',
	'hide_empty' => false,
	'orderby'    => 'meta_value_num',
	'order'    	 => 'ASC',
	'meta_query' => array('order_clause' => array('key' => 'wppatt_recall_status_load_order')),
]);

// List of box status that do not need agents assigned.
$ignore_recall_status = [ 'Recalled', 'Shipped', 'On Loan', 'Shipped Back', 'Received at NDC', 'Recall Cancelled', 'Recall Denied']; 

$term_id_array = array();
foreach( $recall_statuses as $key=>$obj ) {
	if( in_array( $obj->name, $ignore_recall_status ) ) {
		unset($recall_statuses[$key]);
		
	} else {
		$term_id_array[] = $obj->term_id;
	}
}
array_values( $recall_statuses );


//
// Prep data for DECLINE To-Do list
//

// get all pertinent term_ids
// Decline status slugs
$decline_initiated_tag = get_term_by('slug', 'decline-initiated', 'wppatt_return_statuses'); 
$decline_shipped_tag = get_term_by('slug', 'decline-shipped', 'wppatt_return_statuses'); 
$decline_received_tag = get_term_by('slug', 'decline-pending-cancel', 'wppatt_return_statuses');  //received
$decline_cancelled_tag = get_term_by('slug', 'decline-cancelled', 'wppatt_return_statuses'); 
$decline_complete_tag = get_term_by('slug', 'decline-complete', 'wppatt_return_statuses'); 
$decline_shipped_back_tag = get_term_by('slug', 'decline-shipped-back', 'wppatt_return_statuses');
$decline_received_at_ndc_tag = get_term_by('slug', 'decline-received-at-ndc', 'wppatt_return_statuses'); 
$decline_expired_tag = get_term_by('slug', 'decline-expired', 'wppatt_return_statuses'); 


$decline_initiated_term = $decline_initiated_tag->term_id;
$decline_shipped_term = $decline_shipped_tag->term_id;
$decline_received_term = $decline_received_tag->term_id;
$decline_cancelled_term = $decline_cancelled_tag->term_id;
$decline_complete_term = $decline_complete_tag->term_id;
$decline_received_at_ndc_term = $decline_received_at_ndc_tag->term_id;
$decline_shipped_back_term = $decline_shipped_back_tag->term_id;
$decline_expired_term = $decline_expired_tag->term_id;

// Grab all info needed for the decline
$whr = [ 'return_id' => $decline_ids[0] ]; // decline todo will always contain 1 value.
$decline_todo_arr = Patt_Custom_Func::get_return_data( $whr ); 
$decline_todo_obj = $decline_todo_arr[0];


// Flags for decline statuses
// NOTE: the flag is called 'return_initiated' but actually should be called 'return_ready_to_ship'. As it is used to track if a decline has a shipping tracking number associated with it. 
$todo_decline_ready_to_ship = $decline_todo_obj->return_initiated;
$todo_decline_complete = $decline_todo_obj->return_complete; 

// used for HTML checkbox checks
$todo_decline_ready_to_ship_check = $todo_decline_ready_to_ship == 1 ? 'checked' : '';
$todo_decline_complete_check = $todo_decline_complete == 1 ? 'checked' : '';

// used for HTML checkbox disabled
// Using $recall_recalled_term as that is the status we're in before moving to recall approved. 
// $todo_recall_approved_disabled = $recall_todo_obj->recall_status_id == $recall_recalled_term ? '' : 'disabled';
$todo_decline_ready_to_ship_disabled = $decline_todo_obj->return_status_id == $decline_initiated_term ? '' : 'disabled';
$todo_decline_complete_disabled = $decline_todo_obj->return_status_id == $decline_received_at_ndc_term ? '' : 'disabled';


if( $type == 'decline_todo' ) {
  
  // needed to surpress an error 
  $current_box_status = $decline_todo_obj->return_status_id;
  
  //$ticket_id = $decline_todo_obj->ticket_id;
  $shipping_carrier = $decline_todo_obj->shipping_carrier;
  $tracking_number = $decline_todo_obj->tracking_number;
  
  $decline_save_enabled = true;
  
//   if( $tracking_number == '' ) {
//     $decline_save_enabled = false;
//   } else {
//     $decline_save_enabled = true;
//   }
  
  
}

// Get all the users on the decline
$decline_assigned_users = $decline_todo_obj->user_id;

//




// Register Decline Status Taxonomy
if( !taxonomy_exists('wppatt_return_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wppatt_return_statuses', 'wpsc_ticket', $args );
}

// $box_statuses = get_tax();

// Get List of Decline Statuses
$decline_statuses = get_terms([
	'taxonomy'   => 'wppatt_return_statuses',
	'hide_empty' => false,
	'orderby'    => 'meta_value_num',
	'order'    	 => 'ASC',
	'meta_query' => array('order_clause' => array('key' => 'wppatt_return_status_load_order')),
]);

// List of box status that do not need agents assigned.
$ignore_decline_status = [ 'Decline Shipped', 'Received', 'Decline Shipped Back', 'Received at NDC', 'Decline Complete', 'Decline Cancelled', 'Decline Expired']; 

$term_id_array = array();
foreach( $decline_statuses as $key=>$obj ) {
	if( in_array( $obj->name, $ignore_decline_status ) &&  $obj->name !== 'Decline Complete' ) {
		unset($decline_statuses[$key]);
		
	} else {
		$term_id_array[] = $obj->term_id;
	}
}
array_values( $decline_statuses );






// Register Box Status Taxonomy
if( !taxonomy_exists('wpsc_box_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
}

// $box_statuses = get_tax();

// Get List of Box Statuses
$box_statuses = get_terms([
	'taxonomy'   => 'wpsc_box_statuses',
	'hide_empty' => false,
	'orderby'    => 'meta_value_num',
	'order'    	 => 'ASC',
	'meta_query' => array('order_clause' => array('key' => 'wpsc_box_status_load_order')),
]);

// List of box status that do not need agents assigned.
$ignore_box_status = ['Pending', 'Waiting/Shelved', 'Ingestion', 'Completed Permanent Records', 'Completed/Dispositioned', 'Waiting on RLO', 'Cancelled']; 

$term_id_array = array();
foreach( $box_statuses as $key=>$box ) {
	if( in_array( $box->name, $ignore_box_status ) ) {
		unset($box_statuses[$key]);
		
	} else {
		$term_id_array[] = $box->term_id;
	}
}
array_values($box_statuses);


// Constant array with terms for box status which do not allow duplicate users
$box_status_qa_term_id = Patt_Custom_Func::get_term_by_slug( 'q-a' ); // 65 aka QA/QC
$box_status_validation_term_id = Patt_Custom_Func::get_term_by_slug( 'verification' ); // 674 aka Validation
// $status_list_no_dup_user = [65, 674];
$status_list_no_dup_user = [$box_status_qa_term_id, $box_status_validation_term_id];
$status_list_name_no_dup_user = [];
foreach( $status_list_no_dup_user as $term_id ) {
	$status_obj = get_term($term_id);
	$status_list_name_no_dup_user[$term_id] = $status_obj->name;
}

// Get user array from Recall ID -> Put in meta data format (rather than wp_user)

$where = [ 'recall_id' => $recall_id ]; 

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

$user_array_wp = $recall_obj->user_id;

// Get current user id & convert to wpsc agent id.
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

$assigned_agents = [];

if( is_array($user_array_wp) ) {
	foreach ( $user_array_wp as $wp_id ) {
		$key = array_search( $wp_id, array_column($agent_ids, 'wp_user_id'));
		$agent_term_id = $agent_ids[$key]['agent_term_id']; //current user agent term id
		$assigned_agents[] = $agent_term_id;
	}
} else {
	$key = array_search( $user_array_wp, array_column($agent_ids, 'wp_user_id'));
	$agent_term_id = $agent_ids[$key]['agent_term_id']; //current user agent term id
	$assigned_agents[] = $agent_term_id;
}

$old_assigned_agents = $assigned_agents; // for audit log


//
// Get all users and translate from wp_user id to wpsc agent id
//

if( $is_single_item ) {
	$box_id = Patt_Custom_Func::get_box_file_details_by_id($item_ids[0])->Box_id_FK;	
}

$where = [
			 'box_id' => $box_id,
			// 'user_id' => 2,
			// 'status_id' => 672	
		];
$assigned_agents = Patt_Custom_Func::get_user_status_data($where);

//$assigned_agents = array_values($assigned_agents);
//$assigned_agents = $assigned_agents[0];

// Translate the wp_users in the obj to agent_id
foreach( $assigned_agents['status'] as $key=>$val_array ) {
	//$assigned_agents['status'][$key] = translate_user_id( $val_array, 'agent_term_id' );
	$assigned_agents['status'][$key] = Patt_Custom_Func::translate_user_id( $val_array, 'agent_term_id' );
}

// Gets an array of all the items and their ticket status
$test = 'x';
$save_enabled = true;
$ticket_id_array = array();
foreach( $item_ids as $key=>$id ) {
	$data = ['box_folder_file_id'=>$id];
	$ticket_id_array[] = Patt_Custom_Func::get_ticket_id_from_box_folder_file( $data );
	
	//$data = ['ticket_id'=>$ticket_id_array[$key]['ticket_id']];
	$data = $ticket_id_array[$key]['ticket_id']; 
	$ticket_status = Patt_Custom_Func::get_ticket_status( $data );
	$ticket_id_array[$key]['ticket_status'] = $ticket_status;
	
	$test = $ticket_status;
	
	// If ticket is in status Received [63] then save enabled is true.
	if( in_array( $ticket_status, $status_id_arr ) ) { 
		$save_enabled = false;
	}
	
	// If digitization center is unassigned or assigned location is unassigned, disable saving.
	$storage_location = $wpdb->get_row("SELECT
										    *
										FROM
										    " . $wpdb->prefix . "wpsc_epa_storage_location Location
										INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo Box ON
										    Box.storage_location_id = Location.id
										WHERE
										    Box.box_id = '" . $id . "'");
	//$office_name = $storage_location->office_name;
	
	$ticket_id_array[$key]['digitization_center'] = $storage_location->digitization_center;
	$ticket_id_array[$key]['aisle'] = $storage_location->aisle;
	$ticket_id_array[$key]['bay'] = $storage_location->bay;
	$ticket_id_array[$key]['shelf'] = $storage_location->shelf;
	$ticket_id_array[$key]['position'] = $storage_location->position;	
	
	
	// If digitization center is unassigned, do not allow saving. 
// 	if( $storage_location->digitization_center == 666 ) { 
	if( $storage_location->digitization_center == $dc_not_assigned ) {
		$save_enabled = false;		
	}
	
	// If assigned location is 0,0,0,0 then do not allow saving.
	if( $storage_location->aisle == 0 || $storage_location->bay == 0 || $storage_location->shelf == 0 || $storage_location->position == 0 ) {
		$save_enabled = false;
	}
}

//ob_start();
// Creates an array of Box_ID -> status_term -> user_id
$box_status_term_user_list = [];
$box_id_list = '';
foreach($item_ids as $the_box_id) {
	$the_fk_box_ID = Patt_Custom_Func::get_id_by_box_id( $the_box_id );	
	//echo 'the_fk_box_ID: '.$the_fk_box_ID."<br>";
	$box_id_list .= $the_box_id . ', ';
	
	$status_list = [];
	foreach( $status_list_no_dup_user as $status_term ) {
		
		$the_users_obj = $wpdb->get_results("SELECT
											    user_id
											FROM
											    " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus
											WHERE
											    box_id = " . $the_fk_box_ID . " AND status_id = " . $status_term . "");
		
		$user_list_array = [];
		foreach( $the_users_obj as $user_obj) {
			$user_list_array[] = $user_obj->user_id;
			
		}									    
		
		$status_list[$status_term] = Patt_Custom_Func::translate_user_id($user_list_array, 'agent_term_id');
		
		
/*
		echo '<br><br>';
		print_r($user_list_array);
		echo '<br><br>';
*/		
		
	}
	

	
	
	
	$box_status_term_user_list[$the_box_id] = $status_list;
}

$box_id_list =  rtrim( $box_id_list, ', ');

/*
echo '<br><br><pre>';
print_r($box_status_term_user_list);
echo '</pre><br><br>';	
*/


ob_start();

// D E B U G 
/*
echo "type: " . $type ."<br>";
echo "tracking_number: " . $tracking_number ."<br>";
echo "todo_ticket_destruction_approval: " . $todo_ticket_destruction_approval ."<br>";
echo "decline_todo_obj: <br><pre>";
print_r( $decline_todo_obj );
//print_r($status_id_arr);
echo "</pre>";
echo "Assign Agents for: ".$type;
echo "<br>decline_statuses: <br><pre>";
print_r( $decline_statuses );
echo "</pre><br>decline_ids Array: <pre>";
print_r($decline_ids);
echo "</pre><br>";
echo "Ticket Statuses: ";
//print_r($box_statuses);
//print_r($ticket_status);
print_r($status_id_arr);
echo "<br>";
echo "Num of Items: ".$num_of_items;
echo "<br>";
echo "Is single: ".$is_single_item;
echo "<br>";
echo "current: " . $test;
echo "<br>";
echo "box status terms: ";
echo $scanning_preparation_term . "-" . $scanning_digitization_term . "-" . $qa_qc_term . "-" . $validation_term . "-" . $destruction_approved_term . "-" . $destruction_of_source_term . "<br>";
echo "todo_scanning_preparation_disabled: " . $todo_scanning_preparation_disabled . "<br>";
echo "todo_scanning_digitization_disabled: " . $todo_scanning_digitization_disabled . "<br>";
echo "todo_qa_qc_disabled: " . $todo_qa_qc_disabled . "<br>";
echo "todo_validation_disabled: " . $todo_validation_disabled . "<br>";
echo "todo_destruction_approved_disabled: " . $todo_destruction_approved_disabled . "<br>";
echo "todo_destruction_of_source_disabled: " . $todo_destruction_of_source_disabled . "<br>";
echo "recall_recalled_tag: <pre>";
print_r( $recall_recalled_tag );
echo "</pre>";
echo "recall_complete_tag: <pre>";
print_r( $recall_complete_tag );
echo "</pre>";
*/






//echo "<br>Fake Agents: ";
//print_r($fake_array_of_users);
?>
<!-- <h4>[Box ID # <?php echo $the_box_id; ?>]</h4> -->
<?php if( $type == 'recall_todo') { ?>

<h4>[Recall ID # R-<?php echo $recall_ids[0]; ?>]</h4>

<?php } elseif( $type == 'decline_todo') { ?>
<h4>[Decline ID # D-<?php echo $decline_ids[0]; ?>]</h4>

<?php } else { ?>
<h4>[Box ID # <?php echo $box_id_list; ?>]</h4>
<?php } ?>

<div id='alert_status' class=''></div> 
<br>
<!--
<label class="wpsc_ct_field_label">Current Requestor: </label>
	<span id="modal_current_requestor" class=""><?php echo $current_requestor; ?></span>
<br>
-->

<?php if( $type == 'todo') { ?>

<div class="row">
	<div class="col-sm-4">
		<label class="wpsc_ct_field_label">Box Status: </label>
	</div>
	
	<div class="col-sm-2">
		<label class="wpsc_ct_field_label">To-Do: </label>
	</div>
	
	<div class="col-sm-6">
		<label class="wpsc_ct_field_label">Assign Agents: </label>
	</div>
</div>

<?php } elseif( $type == 'recall_todo') { ?>

<div class="row">
	<div class="col-sm-4">
		<label class="wpsc_ct_field_label">Recall Status: </label>
	</div>
	
	<div class="col-sm-2">
		<label class="wpsc_ct_field_label">To-Do: </label>
	</div>
	
	<div class="col-sm-6">
		<label class="wpsc_ct_field_label">Assign Agents: </label>
	</div>
</div>

<?php } elseif( $type == 'decline_todo') { ?>

<div class="row">
	<div class="col-sm-4">
		<label class="wpsc_ct_field_label">Decline Status: </label>
	</div>
	
	<div class="col-sm-2">
		<label class="wpsc_ct_field_label">To-Do: </label>
	</div>
	
	<div class="col-sm-6">
		<label class="wpsc_ct_field_label">Assign Agents: </label>
	</div>
</div>

<?php } else { ?> 


<div class="row">
	<div class="col-sm-4">
		<label class="wpsc_ct_field_label">Box Status: </label>
	</div>
	
	<div class="col-sm-8">
		<label class="wpsc_ct_field_label">Assign Agents: </label>
	</div>
</div>

<?php } ?> 


<hr class='tight'>

<?php 
	if( $type == 'edit') {
		foreach( $box_statuses as $status) { 	
?>
		
		
		<div class="row zebra">
			<?php
				if( $status->name == 'QA/QC' ) {
					echo '<div id="qaqc_alert_status" class="" ></div>';
				}
			?>
			
			
			<div class="col-sm-4">
              	<!-- removes the digitized - not validated box status text -->
			<?php
				//if( $status->name != 'Digitized - Not Validated') {
			?>
			    <?php
			    if($status->name == 'QA/QC') {
			    ?>
			    <label class="wpsc_ct_field_label"><?php echo $status->name; ?> <a href="#" aria-label="QA/QC" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-staff-qa-qc'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a></label>
				<?php }
				elseif($status->name == 'Validation') { ?>
				<label class="wpsc_ct_field_label"><?php echo $status->name; ?> <a href="#" aria-label="Validation" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-staff-validation'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a></label>
				<?php }
				else { ?>
				<label class="wpsc_ct_field_label"><?php echo $status->name; ?> </label>
				<?php } ?>
              <!-- closing bracket for if statement that removes the digitized - not validated box status text -->
			  <?php //} ?>
			</div>
			
			
			<div class="col-sm-8">
	<!-- 			<label class="wpsc_ct_field_label">Search Digitization Staff: </label> -->
	
				<form id="frm_get_ticket_assign_agent" action = "<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/assign_staff_processing.php" method = "POST">
					<div id="assigned_agent">
                      <!-- removes the digitized - not validated input field -->
						<?php //if( $status->term_id != '6') { ?>
						<div class="form-group wpsc_display_assign_agent ">
						    <input class="form-control  wpsc_assign_agents ui-autocomplete-input term-<?php echo $status->term_id; ?>" name="assigned_agent"  type="text" autocomplete="off" aria-label="Search agent ..." placeholder="<?php _e('Search agent ...','supportcandy')?>" />
							<ui class="wpsp_filter_display_container"></ui>
						</div>
					</div>
					<div id="assigned_agents" class="form-group col-sm-12 term-<?php echo $status->term_id; ?>">
						<?php
						    if($is_single_item) {
							    foreach ( $assigned_agents['status'] as $term_id=>$agent_list ) {							    
								    if( $term_id == $status->term_id ) :
								    	foreach( $agent_list as $agent ) {
								    	
											$agent_name = get_term_meta( $agent, 'label', true);
											 	
											if($agent && $agent_name):
							?>
													<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">
														<div class="flex-container staff-badge" style="">
															<?php echo htmlentities($agent_name)?><span class="staff-close" onclick="wpsc_remove_filter(this);remove_user(<?php echo $status->term_id; ?>);"><i class="fa fa-times" aria-hidden="true" title="Remove User"></i><span class="sr-only">Remove User</span></span>
															  <input type="hidden" name="assigned_agent[<?php echo $status->term_id; ?>]" value="<?php echo htmlentities($agent) ?>" />
						<!-- 									  <input type="hidden" name="new_requestor" value="<?php echo htmlentities($agent) ?>" /> -->
														</div>
													</div>
							<?php
											endif;
										}
									endif;	
								}
							}
						?>
                      <!-- closing bracket for if statement that removes the digitized - not validated input field -->
					<?php //} ?>
				  </div>
						<input type="hidden" name="action" value="wpsc_tickets" />
						<input type="hidden" name="setting_action" value="set_change_assign_agent" />
						<input type="hidden" name="recall_id" value="<?php echo htmlentities($recall_id) ?>" />
				</form>
			</div>
		</div>
<?php
		} 	
	}
?>




<?php 
	if( $type == 'view') {
		foreach( $box_statuses as $status) { 	
?>

		
		<div class="row zebra">
			<div class="col-sm-4">
				<label class="wpsc_ct_field_label label_center"><?php echo $status->name; ?> </label>
			</div>
			
			<div class="col-sm-8">
					<div id="assigned_agents" class="  term-<?php echo $status->term_id; ?>">
						<?php
						    if($is_single_item) {
							    foreach ( $assigned_agents['status'] as $term_id=>$agent_list ) {							    
								    if( $term_id == $status->term_id ) :
								    	foreach( $agent_list as $agent ) {
								    	
											$agent_name = get_term_meta( $agent, 'label', true);
											 	
											if($agent && $agent_name):
							?>
													<div class=" wpsp_filter_display_element wpsc_assign_agents ">
														<div class="flex-container staff-badge" style="">
															<?php echo htmlentities($agent_name)?>
														</div>
													</div>
							<?php
											endif;
										}
									endif;	
								}
							}
						?>

				  </div>

			</div>
		</div>
<?php
		} 	
	}
?>


<?php 
	//
	// To-Do List HTML
	//
	
	if( $type == 'todo') {
  	
  	// Prep
  	$save_enabled = true;
?> 	
  <form id="todo-form">
<?php  	
		foreach( $box_statuses as $status) { 	
?>

		<?php
    if( $status->name != 'Digitized - Not Validated' && $status->name != 'Re-scan' ) {
    ?>
		<div class="row zebra">
			<div class="col-sm-4">
				<label class="wpsc_ct_field_label label_center"><?php echo $status->name; ?> </label>
			</div>
			
			<div class="col-sm-2 text-xs-center">
				<?php
  				
        if( $status->name == 'Scanning Preparation' ) {
        ?>
				  <input data-box-status-id="<?php echo $scanning_preparation_term; ?>" type="checkbox"  <?php echo $todo_scanning_preparation_check . ' ' . $todo_scanning_preparation_disabled; ?> class="center-check">
				<?php
        } elseif( $status->name == 'Scanning/Digitization' ) {
        ?> 
          <input data-box-status-id="<?php echo $scanning_digitization_term; ?>" type="checkbox"  <?php echo $todo_scanning_digitization_check . ' ' . $todo_scanning_digitization_disabled; ?> class="center-check">
        <?php
        } elseif( $status->name == 'QA/QC' ) {
        ?> 
          <input data-box-status-id="<?php echo $qa_qc_term; ?>" type="checkbox"  <?php echo $todo_qa_qc_check . ' ' . $todo_qa_qc_disabled; ?> class="center-check">
        <?php
        } elseif( $status->name == 'Validation' ) {
        ?> 
          <input data-box-status-id="<?php echo $validation_term; ?>" type="checkbox" disabled <?php echo $todo_validation_check . ' ' . $todo_validation_disabled; ?> class="center-check">
        <?php
        } elseif( $status->name == 'Destruction Approved' ) {
        ?> 
          <input data-box-status-id="<?php echo $destruction_approved_term; ?>" type="checkbox"  <?php echo $todo_destruction_approved_check . ' ' . $todo_destruction_approved_disabled; ?> class="center-check">
        <?php
        } elseif( $status->name == 'Destruction of Source' ) {
        ?> 
          <input data-box-status-id="<?php echo $destruction_of_source_term; ?>" type="checkbox"  <?php echo $todo_destruction_of_source_check . ' ' . $todo_destruction_of_source_disabled; ?> class="center-check">
        <?php
        } 
        ?> 
 
 

 
			</div>
			
			<div class="col-sm-6">
					<div id="assigned_agents" class="  term-<?php echo $status->term_id; ?>">
						<?php
						    if($is_single_item) {
							    foreach ( $assigned_agents['status'] as $term_id=>$agent_list ) {							    
								    if( $term_id == $status->term_id ) :
								    	foreach( $agent_list as $agent ) {
								    	
  											$agent_name = get_term_meta( $agent, 'label', true);
  											 	
  											if($agent && $agent_name):
  							?>
  													<div class=" wpsp_filter_display_element wpsc_assign_agents ">
  														<div class="flex-container staff-badge" style="">
  															<?php echo htmlentities($agent_name)?>
  														</div>
  													</div>
  							<?php
  											endif;
										}
									endif;	
								}
							}
						?>

				  </div>

			</div>
		</div>
		<?php 
    } 
    ?>
<?php
		} 	
		?>
  </form>
<?php		
	}
?>


<?php 
	//
	// Recall To-Do List HTML
	//
	
	if( $type == 'recall_todo') {
  	
  	// Prep
  	$save_enabled = true;
?> 	
  <form id="todo-form">
<?php  	
      foreach( $recall_statuses as $status) {
        //if( $status->name == 'Recall Approved' || $status->name == 'Received at NDC' ) {
?>

		<div class="row zebra">
			<div class="col-sm-4">
				<label class="wpsc_ct_field_label label_center"><?php echo $status->name; ?> </label>
			</div>
			
			<div class="col-sm-2 text-xs-center">
				<?php
  				
        if( $status->name == 'Recall Approved' ) {
        ?>
				  <input data-box-status-id="<?php echo $recall_approved_term; ?>" type="checkbox"  <?php echo $todo_recall_approved_check . ' ' . $todo_recall_approved_disabled; ?> class="center-check">
				<?php
        } elseif( $status->name == 'Recall Complete' ) {
        ?> 
          <input data-box-status-id="<?php echo $recall_received_at_ndc_term; ?>" type="checkbox"  <?php echo $todo_recall_complete_check . ' ' . $todo_recall_complete_disabled; ?> class="center-check">
        <?php
        } 
        ?> 
 
			</div>
			
			<div class="col-sm-6">
					<div id="assigned_agents" class="  term-<?php echo $status->term_id; ?>">
						<?php

					    foreach ( $recall_assigned_users as $wp_user_id ) {							    

						    $user_obj = get_user_by( 'id', $wp_user_id );	
								$name = $user_obj->display_name;
								$login = $user_obj->user_login;
                
						?>
								<div class=" wpsp_filter_display_element wpsc_assign_agents ">
									<div class="flex-container staff-badge" style="">
										<?php echo htmlentities( $name  )?>
									</div>
								</div>
						<?php
								}
						?>
				  </div>
			</div>
		</div>
		<?php
      //}
    } 
    ?>

  </form>
<?php		
	}
?>


<?php 
	//
	// Decline To-Do List HTML
	//
	
	if( $type == 'decline_todo') {
  	
  	// Prep
  	//$save_enabled = true;
?> 	
  <form id="todo-form">
<?php  	
		foreach( $decline_statuses as $status) { 	
?>

		<div class="row zebra">
			<div class="col-sm-4">
				<label class="wpsc_ct_field_label label_center"><?php echo $status->name; ?> </label>
			</div>
			
			<div class="col-sm-2 text-xs-center">
				<?php
  				
        if( $status->name == 'Decline Initiated' ) {
        ?>
				  <input data-box-status-id="<?php echo $decline_initiated_term; ?>" type="checkbox"  <?php echo $todo_decline_ready_to_ship_check . ' ' . $todo_decline_ready_to_ship_disabled; ?> class="center-check">
				<?php
        } 
        
        if( $status->name == 'Decline Complete' ) {
		?>
				  <input data-box-status-id="<?php echo $decline_received_at_ndc_term; ?>" type="checkbox"  <?php echo $todo_decline_complete_check . ' ' . $todo_decline_complete_disabled; ?> class="center-check" />
				<?php
		} 

        ?> 
 
			</div>
			
			<div class="col-sm-6">
					<div id="assigned_agents" class="  term-<?php echo $status->term_id; ?>">
						<?php

					    foreach ( $decline_assigned_users as $wp_user_id ) {							    

						    $user_obj = get_user_by( 'id', $wp_user_id );	
								$name = $user_obj->display_name;
								$login = $user_obj->user_login;
                
						?>
								<div class=" wpsp_filter_display_element wpsc_assign_agents ">
									<div class="flex-container staff-badge" style="">
										<?php echo htmlentities( $name  )?>
									</div>
								</div>
						<?php
								}
						?>
				  </div>
			</div>
		</div>
		<?php 
    } 
    ?>

  </form>
<?php		
	}
?>


<?php 
//ERMD Error Message Fix
$get_todo_flag = $wpdb->get_row("SELECT a.destruction_of_source, b.box_destroyed FROM " . $wpdb->prefix . "wpsc_epa_storage_location a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.storage_location_id = a.id WHERE b.box_id = '" . $box_id_list . "'");

$scanning_d_s_val = $get_todo_flag->destruction_of_source;
$scanning_b_d_val = $get_todo_flag->box_destroyed;



// If save enabled, 
// get all restrictions on the boxes listed.
if( $save_enabled ) {

	$status_list = Patt_Custom_Func::get_restricted_box_status_list_2( $item_ids, $agent_type ); 
	$box_statuses = $status_list[ 'box_statuses' ];
	$restriction_reason = $status_list[ 'restriction_reason' ];
	$restricted_reason_array = $status_list[ 'restricted_reason_array' ];
	
	
	
} else {
	
	// If save not enabled, list the restriction reason as: C5 - statuses of the selected Boxes are not all the same
	$box_statuses = [];
	$restriction_reason = $restriction_reason_C5;
	
	foreach( $item_ids as $key => $item ) {
	  $restricted_reason_array[ $item ] = $restriction_reason_C5;
  }
}

// print_r($restricted_reason_array);
// echo json_encode($restricted_reason_array); 
?>

<style>
.zebra {
	padding: 7px 0px 7px 0px;
}

.zebra:nth-of-type(even) {
/* 	background: #e0e0e0; */
	background: #f3f3f3;
	border-radius: 4px;
}

#assigned_agents {
	margin-bottom: 0px !important;
}

.wpsc_display_assign_agent {
	margin-bottom: 5px !important;
}

.tight {
	margin-top: 3px !important;
	margin-bottom: 10px !important;
}

.staff-badge {
	padding: 3px 5px 3px 5px;
	font-size:1.0em !important;
	vertical-align: middle;
}

.staff-close {
	margin-left: 3px;
	margin-right: 3px;
}

.label_center {
	margin-top: 5px !important;
	margin-bottom: 0px !important;
}

.alert_spacing {
	margin: 0px 0px 0px 0px;
}

.center-check {
  text-align: center;
}
  
.ui-autocomplete {
    max-height: 100px;
    overflow-y: auto;
    /* prevent horizontal scrollbar */
    overflow-x: hidden;
}
  
/* IE 6 doesn't support max-height
* we use height instead, but this forces the menu to always be this tall
*/
* html .ui-autocomplete {
    height: 100px;
}


</style>

<script>
  window.onerror = function (e) {
    console.log('Error: ', e);
  };
</script>

<script>
jQuery(document).ready(function(){

	jQuery('[data-toggle="tooltip"]').tooltip();
	
	jQuery("input[name='assigned_agent']").keypress(function(e) {
		//Enter key
		if (e.which == 13) {
			return false;
		}
	});
		
	
	//
	// Auto Complete for assign agents
	//
	
	jQuery( ".wpsc_assign_agents" ).autocomplete({
			minLength: 0,
// 			appendTo: jQuery('.wpsc_assign_agents').parent(),
			appendTo: jQuery('.wpsc_assign_agents.term-68').parent(), //targeting any .term-xxx fixes type ahead issue. 
			source: function( request, response ) {
				var term = request.term;
				request = {
					action: 'wpsc_tickets',
					setting_action : 'filter_autocomplete',
					term : term,
					field : 'assigned_agent',
					no_requesters : true,
				}
				jQuery.getJSON( wpsc_admin.ajax_url, request, function( data, status, xhr ) {
					response(data);
				});
			},
			select: function (event, ui) {

				//console.log('Focus: ');
				//console.log( jQuery(':focus').prop("classList") );
				
				//console.log({event:event, ui:ui});
				
				let list = jQuery(':focus').prop("classList");
				let the_term = '';
				list.forEach( function(y) {
					console.log(y);
					if ( y.startsWith('term-') ) {
						the_term = y.replace('term-','');
					}
				});
				 							
				html_str = get_display_user_html(ui.item.label, ui.item.flag_val, the_term, ui.item.wp_user_obj);
 				jQuery('#assigned_agents.term-'+the_term+'').append(html_str);
 				

 				
 				// Enable / Disable Save based on PHP save enabled which denotes if the Box is savable.
 				var save_enabled = '<?php echo $save_enabled ?>';
 				console.log({save_enabled:save_enabled});
 				
				if( !save_enabled ) {
					console.log('save disabled');
					jQuery("#button_agent_submit").hide();
					//jQuery("#button_agent_submit").attr( 'disabled', 'disabled' );
					//jQuery("#button_agent_submit").hide();
				} else {
					jQuery("#button_agent_submit").show();	
					//jQuery("#button_agent_submit").removeAttr( 'disabled' );
				}
				
				// Enable / Disable Save based on js save enabled which denotes if all of the fields are filled in
				var save_enabled_js = false;
				let term_id_array = <?php echo json_encode($term_id_array); ?>;
				let is_single = <?php echo json_encode($is_single_item); ?>;
				
				console.log('is single? ');
				console.log(is_single);
				term_id_array.forEach( function(x) {
					if( is_single ) {
						//console.log('is single is FALSE');
						let entries_per_term = jQuery("input[name='assigned_agent["+x+"]']").map(function(){return jQuery(this).val();}).get();	
						if( entries_per_term < 1) {
							save_enabled_js = false;
							//jQuery("#button_agent_submit").hide();
							return false;
						} else {
							save_enabled_js = true;
						}
					}
				});
				
				
			    jQuery(this).val(''); return false;
			}
	}).focus(function() {
			jQuery(this).autocomplete("search", "");
	});
  
  
  	//
	// Auto Complete for last type of assign agents
	//

	jQuery(".ui-autocomplete-input").eq(6).autocomplete({
			minLength: 0,
			position: { my: "left bottom", at: "left top" }
	});
	
	jQuery('.wpsc_assign_agents').last().autocomplete({
			minLength: 0,
			position: { my: "left bottom", at: "left top" }
	});
  

	// Checks if ticket status of any item inhibits it's editing
	var items_and_status = <?php echo json_encode($ticket_id_array, true); ?>;
	var alerts_disabled = '<?php echo $alerts_disabled ?>'; 
  console.log({items_and_status:items_and_status});
	
	let is_error = false;
		
	let status_error_link = '';
	let dc_error_link = '';	// digitization center error
	let al_error_link = '';	// assigned location error
    let box_id_var = '';	// box id
	let status_error_count = 0;
	let dc_error_count = 0;
	let al_error_count = 0;
	let status_error = false;
	let dc_error = false; 
	let al_error = false; 
	let status_error_start = '';
	let status_error_mid = '';
	let dc_error_start = '';
	let dc_error_mid = '';
	let al_error_start = '';
	let al_error_mid = '';	
	
	var subfolder_path = '<?php echo $subfolder_path ?>';
	let box_link_start = '<a href="'+subfolder_path+'/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=';
	let request_link_start = '<a href="'+subfolder_path+'/wp-admin/admin.php?page=wpsc-tickets&id=';	
	let link_mid = '" style="text-decoration: underline;" target="_blank">';
	let link_end = '</a>';
	
	var new_request_tag = '<?php $new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
	echo $new_request_tag->term_id; ?>';
	var tabled_tag = '<?php $tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //3
	echo $tabled_tag->term_id; ?>';
	var initial_review_complete_tag = '<?php $initial_review_complete_tag = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses'); //4
	echo $initial_review_complete_tag->term_id; ?>';
	var initial_review_rejected = '<?php $initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
	echo $initial_review_rejected_tag->term_id; ?>';
	var cancelled_tag = '<?php $cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
	echo $cancelled_tag->term_id; ?>';
	var completed_dispositioned_tag = '<?php $completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003
	echo $completed_dispositioned_tag->term_id; ?>';
	
	// Digitization Center not assigned term_id
	var dc_not_assigned_tag = '<?php $dc_not_assigned_tag = get_term_by('slug', 'not-assigned-digi-center', 'wpsc_categories' ); //666
	echo $dc_not_assigned_tag->term_id; ?>';
	
/*
	// D E B U G 
	console.log({dc_not_assigned_tag:dc_not_assigned_tag});
	console.log({new_request_tag:new_request_tag});
	console.log({tabled_tag:tabled_tag});
	console.log({initial_review_complete_tag:initial_review_complete_tag});
	console.log({initial_review_rejected:initial_review_rejected});
	console.log({cancelled_tag:cancelled_tag});
	console.log({completed_dispositioned_tag:completed_dispositioned_tag});
*/
	
	//
	// Recall To Do Error Check
	//
	let type = '<?php echo $type; ?>';
	var recall_save_enabled = '<?php echo $recall_save_enabled; ?>';
	
	if( type == 'recall_todo' ) {
  	let shipping_carrier = '<?php echo $shipping_carrier; ?>';
  	let tracking_number = '<?php echo $tracking_number; ?>';
  	
  	    // Red warning message that disables the save button for decline initiated if there's no shipping number for the box
//   	if( !tracking_number ) {
    	
//     	let note = 'This Item does not have a shipping tracking number. Saving Disabled.';
//     	recall_save_enabled = false;
//     	set_alert( 'danger', note );
    	
//   	}
  	
  }
  
  // Disable recall save button
//   if( !recall_save_enabled ) {
// 		console.log('recall save disabled');
// 		jQuery("#button_todo_recall_submit").hide();
// 	}
	
	
	//
	// Decline To Do Error Check
	//
  type = '<?php echo $type; ?>';
	var decline_save_enabled = '<?php echo $decline_save_enabled; ?>';
	console.log({decline_type:type});
	if( type == 'decline_todo' ) {
  	let shipping_carrier = '<?php echo $shipping_carrier; ?>';
  	let tracking_number = '<?php echo $tracking_number; ?>';
  	console.log({ shipping_carrier:shipping_carrier, tracking_number:tracking_number });
  	
  	
  	// Red warning message that disables the save button for decline initiated if there's no shipping number for the box
//   	if( !tracking_number ) {
    	
//     	let note = 'This Item does not have a shipping tracking number. Saving Disabled.';
//     	decline_save_enabled = false;
//     	set_alert( 'danger', note );
    	
//   	}
  	
  }
  
  console.log({decline_save_enabled:decline_save_enabled});
  
  // Disable recall save button
  if( !decline_save_enabled ) {
		console.log('decline save disabled');
		jQuery("#button_todo_decline_submit").hide();
	}

  
	
	//
	// Accordion Error Display
	//
	
	let accordion_notification = '';
	
	let accordion_pre_message = '<strong><div class="" id="alert-message">Click the Box number to view the restrictions.</div></strong>';
	let accordion_start = '<div class="accordion" id="the-accordion">';
	
	let accordion_item_start_1 = '<div class="accordion-item"><h2 class="accordion-header" id="';
	let accordion_item_start_2 = '"><button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#';
	let accordion_item_start_3 = '" aria-expanded="false" aria-controls="';
	let accordion_item_start_4 = '">';

	let accordion_item_mid_1 = '</button></h2><div id="';
	let accordion_item_mid_2 = '" class="accordion-collapse collapse section-content" aria-labelledby="';
	let accordion_item_mid_3 = '" data-parent="#the-accordion"><div class="accordion-body">';
	
	
	let accordion_item_end = '</div></div></div>';
	let accordion_end = '</div>';
	
  
	accordion_notification += accordion_pre_message;
	accordion_notification += accordion_start;
  
  
  let restricted_reason_obj = <?php echo json_encode( $restricted_reason_array ); ?>;
	const restricted_reason_array = Object.entries( restricted_reason_obj );
  

  restricted_reason_array.forEach( function( item, index ) {	
		
		let heading = 'heading' + index;
		let collapse = 'collapse' + index;
		
		
		accordion_notification += accordion_item_start_1;
		accordion_notification += 'heading-' + index;
		accordion_notification += accordion_item_start_2;
		accordion_notification += 'collapse-' + index;
		accordion_notification += accordion_item_start_3;
		accordion_notification += 'collapse-' + index;
		accordion_notification += accordion_item_start_4;
		accordion_notification += item[0];
		accordion_notification += accordion_item_mid_1;
		accordion_notification += 'collapse-' + index;
		accordion_notification += accordion_item_mid_2;
		accordion_notification += 'heading-' + index;
		accordion_notification += accordion_item_mid_3;
		//accordion_notification += item[1];
    	accordion_notification += (item[1] != null ? item[1] : 'Box ' + item[0] + ' has an unassigned digitization center and/or shelf location.');
		accordion_notification += accordion_item_end;
		
		
	});

		
	
	
	
	
	//check and display error for digitization center and assigned location
	
	items_and_status.forEach( function(x) {
		
		// OLD || x.ticket_status == initial_review_complete_tag
		
		if( x.ticket_status == new_request_tag  || x.ticket_status == tabled_tag || x.ticket_status == initial_review_rejected || x.ticket_status == cancelled_tag || x.ticket_status == completed_dispositioned_tag ) {
			is_error = true;
			status_error = true;
			status_error_count++;
			let ticket_id = get_containing_ticket(x.item_id);
// 			error_link += box_link_start+x.item_id+link_mid+x.item_id+link_end+', '; //for box link
			status_error_link += request_link_start+ticket_id+link_mid+x.item_id+link_end+', ';
		}
		
// 		if( x.digitization_center == 666 ) {dc_not_assigned_tag
		if( x.digitization_center == dc_not_assigned_tag ) {
			is_error = true;
			dc_error = true;
			dc_error_count++;
			dc_error_link += box_link_start+x.item_id+link_mid+x.item_id+link_end+', ';
		}
		
		if( x.aisle == 0 && x.bay == 0 && x.shelf == 0 && x.position == 0 ) {
			is_error = true;
			al_error = true;
			al_error_count++;
			al_error_link += box_link_start+x.item_id+link_mid+x.item_id+link_end+', ';
		}
		
		box_id_var += x.item_id;
	});
	
	status_error_link = status_error_link.slice(0, -2);
	dc_error_link = dc_error_link.slice(0, -2); 
	al_error_link = al_error_link.slice(0, -2); 
	
	// Accordion 
	accordion_notification += accordion_end;
  console.log( accordion_notification );

	// Alter these if statements to include accordion code. 
	// might need to use for loops for multiple issues per statement. 
	
	if( is_error == true && !alerts_disabled ){
		
		if( status_error && status_error_count > 1 ) {
			status_error_start = 'Boxes ';
// 			status_error_mid = ': The containing Request statuses are not all Received. ';
      status_error_mid = ': One of the boxes selected is in one of the following request statuses: New Request, Tabled, Initial Review Rejected, Cancelled, Completed/Dispositioned. ';
		} else if( status_error && status_error_count == 1 ) {
			status_error_start = 'Box ';
// 			status_error_mid = ': The containing Request status is not Received. '; 
      status_error_mid = ': The selected box is in one of the following request statuses: New Request, Tabled, Initial Review Rejected, Cancelled, Completed/Dispositioned. '; 
		}
		
		if( dc_error && dc_error_count > 1 ) {
			dc_error_start = 'Boxes ';
			dc_error_mid = ': The Digitization Center is Unassigned. ';
		} else if( dc_error && dc_error_count == 1 ) {
			dc_error_start = 'Box ';
			dc_error_mid = ': The Digitization Center is Unassigned. ';
		}
		
		if( al_error && al_error_count > 1 ) {
			al_error_start = 'Boxes ';
			al_error_mid = ': The Assigned Location is Unassigned. ';
		} else if( al_error && al_error_count == 1 ) {
			al_error_start = 'Box ';
			al_error_mid = ': The Assigned Location is Unassigned. ';
		}
		
		const error_end = ' Saving Disabled.';
// 		message = status_error_start+status_error_link+status_error_mid+error_end;
		message = status_error_start+status_error_link+status_error_mid + 
					dc_error_start+dc_error_link+dc_error_mid + 
					al_error_start+al_error_link+al_error_mid + 
					error_end;

//ERMD Disable the following errors: The Digitization Center is Unassigned. AND The Assigned Location is Unassigned. Saving Disabled. WHEN ALL to-do flags are set to 1 except for destruction of source		

<?php
if($scanning_b_d_val == 1) {
?>

		set_alert( 'danger', message);
<?php
}
?>

	}
	
	var save_enabled = '<?php echo $save_enabled ?>';
	console.log('save enabled');
	if( !save_enabled ) {
		console.log('save disabled');
		jQuery("#button_agent_submit").hide();
		//jQuery("#button_agent_submit").attr( 'disabled', 'disabled' );
		//jQuery("#button_agent_submit").hide();
	}
	
	// Accordion style error: display

	if( restricted_reason_array.length > 0 ) {
		console.log('in');
		//set_alert('warning', accordion_notification);	
		let alert_style = 'alert-warning';
		//jQuery('#alert_status').html('<div id="alert-1" class=" alert '+alert_style+'">'+accordion_notification+'</div>'); 
		//jQuery('#alert_status').html( 'test2' ); 
		jQuery('#alert_status').html( accordion_notification ); 
		jQuery('#alert_status').addClass('alert_spacing');
		jQuery('#alert_status').addClass('alert');
		jQuery('#alert_status').addClass('alert-warning');				
	}


  // additional Accordion code - start
	jQuery('.section-title').click(function(e) {
    // Get current link value
    var currentLink = jQuery(this).attr('href');
    if(jQuery(e.target).is('.active')) {
    	close_section();
    }else {
	     close_section();
	    // Add active class to section title
	    jQuery(this).addClass('active');
	    // Display the hidden content
	    jQuery('.accordion ' + currentLink).slideDown(350).addClass('open');
    }
		e.preventDefault();
	});
 
	function close_section() {
    jQuery('.accordion .section-title').removeClass('active');
    jQuery('.accordion .section-content').removeClass('open').slideUp(350);
	}
	// Accordion - end
  
  //
  // Set data for To-Do functionality 
  //
  let use_type = '<?php echo $type; ?>';
  let current_box_status = <?php echo $current_box_status; ?>;
  //let current_box_status = <?php echo json_encode( $current_box_status ); ?>;  
//   let todo_ticket_destruction_approval = <?php echo $todo_ticket_destruction_approval; ?>;
  let todo_ticket_destruction_approval = <?php echo json_encode( $todo_ticket_destruction_approval ); ?>;
  let destruction_approval_allowed = <?php echo json_encode( $destruction_approval_allowed ); ?>;
  var todo_save_enabled = false;
  
  console.log({use_type:use_type, todo_ticket_destruction_approval:todo_ticket_destruction_approval, destruction_approval_allowed:destruction_approval_allowed});
  
  // Disable To-Do save until one checkbox is clicked. 
  if( use_type == 'todo' ) {
    console.log( 'love todo' );    
    let rando = jQuery("#todo-form input:checkbox:checked");
    
    console.log( rando );
    
    // don't think this is used anymore. 
    jQuery('#scanprep').change(function() {
      console.log('clickity clack');
      if( this.checked ) {
        console.log( 'check' );
      } else {
        console.log( 'uncheck' );
      }
    });
    
    jQuery('*[data-box-status-id="'+current_box_status+'"]').change(function() {
      console.log( 'Clicked ' + current_box_status );
      if( this.checked ) {
        console.log( 'DATA check' );
        // todo_save_enabled = true;
        jQuery("#button_todo_submit").show();
      } else {
        console.log( 'DATA uncheck' );
        // todo_save_enabled = false;
        jQuery("#button_todo_submit").hide();
      }
    });  
  }
  
  // Disable RECALL To-Do save until one checkbox is clicked. 
  if( use_type == 'recall_todo' ) {
    console.log( 'love recall_todo' );
    let rando = jQuery("#todo-form input:checkbox:checked");
    
    console.log( rando );
    console.log({current_box_status:current_box_status});
    
    
    jQuery('*[data-box-status-id="'+current_box_status+'"]').change(function() {
      console.log( 'Clicked ' + current_box_status );
      if( this.checked ) {
        console.log( 'DATA check' );
        todo_save_enabled = true;
        jQuery("#button_todo_recall_submit").show();
      } else {
        console.log( 'DATA uncheck' );
        todo_save_enabled = false;
        jQuery("#button_todo_recall_submit").hide();
      }
    });  
  }
  
  // Disable DECLINE To-Do save until one checkbox is clicked. 
  if( use_type == 'decline_todo' ) {
    console.log( 'love decline_todo' );
    let rando = jQuery("#todo-form input:checkbox:checked");
    
    let decline_save_enabled = '<?php echo $decline_save_enabled; ?>';
    
    console.log( {decline_save_enabled:decline_save_enabled });
    console.log({current_box_status:current_box_status});
    
    
    jQuery('*[data-box-status-id="'+current_box_status+'"]').change(function() {
      console.log( 'Clicked ' + current_box_status );
      if( this.checked ) {
        console.log( 'DATA check' );
        //todo_save_enabled = true;
        if( decline_save_enabled ) {
          jQuery("#button_todo_decline_submit").show();
        }
      } else {
        console.log( 'DATA uncheck' );
        //todo_save_enabled = false;
        jQuery("#button_todo_decline_submit").hide();
      }
    });  
  }
  
  // Check and set alert if To-Do ticket does not have destruction approval. 
  if( use_type == 'todo' && destruction_approval_allowed == false && todo_ticket_destruction_approval === 0 ) {
    
    let message = 'The containing Request does not have Destruction Approval yet.';
    set_alert( 'danger', message );    
    
    //disable the checkbox
    let destruction_approved_term = <?php echo $destruction_approved_term; ?>;
    console.log({ destruction_approved_term:destruction_approved_term }); 
    jQuery( '*[data-box-status-id="' + destruction_approved_term + '"]' ).attr( "disabled", true );
  }
  
  
  
  


});

// Simple hash function based on java's. Used for set_alert.
function hashCode( str ) {
	var hash = 0;
    for (var i = 0; i < str.length; i++) {
        var character = str.charCodeAt(i);
        hash = ((hash<<5)-hash)+character;
        hash = hash & hash; // Convert to 32bit integer
    }
    return hash;
}


// Sets an alert
function set_alert( type, message ) {
	
	let hash = hashCode( message );
	console.log({hash:hash});
	let alert_style = '';
	let alert_location = 'alert_status';
	
	switch( type ) {
		case 'success':
			alert_style = 'alert-success';		
			break;
		case 'warning':
			alert_style = 'alert-warning';
			break;
		case 'danger':
			alert_style = 'alert-danger';
			break;
		case 'qaqc-danger': 
			alert_location = 'qaqc_alert_status';
			alert_style = 'alert-danger';
			break;
	}

	jQuery('#'+alert_location).show();
	//jQuery('#'+alert_location).html('<div class=" alert '+alert_style+'">'+message+'</div>'); 
	jQuery('#'+alert_location).html('<div id="alert-' + hash + '" class=" alert '+alert_style+'">'+message+'</div>'); 
	jQuery('#'+alert_location).addClass('alert_spacing');
	
	//alert_dismiss( alert_location, hash );  // auto-dismissed alerts causing confusion when save is disabled and alert dismisses. 
}



	
function alert_dismiss( alert_location, hash ) {
// 	setTimeout(function(){ jQuery('#alert_status').fadeOut(1000); }, 9000);	
// 	setTimeout(function(){ jQuery('#'+alert_location).fadeOut(1000); }, 9000);	
	setTimeout(function(){ jQuery('#alert-'+hash).fadeOut(1000); }, 11000);	

}


function get_containing_ticket( box_folder_id ) {
	let num = box_folder_id.split("-").length - 1;
	console.log('The Num: '+num);
	
	if( num == 1 ) {
		let type = 'Box';
		let arr = box_folder_id.split("-");
// 		var ticket_id = parseInt(arr[0]);
		var ticket_id = arr[0];
	} else if( num == 3 ) {
		let type = 'Folder/File';
		let arr = box_folder_id.split("-");
// 		var ticket_id = parseInt(arr[0]);
		var ticket_id = arr[0];
	} else {
		let type = 'Error';
		let ticket_id = null;
	}
	
	return ticket_id;
}

// Returns the HTML for the user to be added to a section
// Error checks for duplicates and 
function get_display_user_html(user_name, termmeta_user_val, term_id, is_wp_user ) {
	
	console.log( 'get_display_user_html' );
	var requestor_list = jQuery("input[name='assigned_agent["+term_id+"]']").map(function(){return jQuery(this).val();}).get();
	//var status_list_no_dup_user = [65, 674]; // Error Checking: same user cannot be assigned to status: QA/QC and Validation	
	var status_list_no_dup_user = <?php echo json_encode($status_list_no_dup_user); ?>; 	
	var status_list_name_no_dup_user = <?php echo json_encode($status_list_name_no_dup_user); ?>; 		
	var subfolder_path = '<?php echo $subfolder_path ?>';
	let box_link_start = '<a href="'+subfolder_path+'/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=';
	let link_mid = '" target="_blank">';
	let link_end = '</a>';	

	console.log({status_list_no_dup_user:status_list_no_dup_user});
	console.log({status_list_name_no_dup_user:status_list_name_no_dup_user});
	console.log({term_id:term_id});
	console.log( 'test: ' + status_list_no_dup_user.includes(Number(term_id)) );
	console.log( 'fake test: ' + status_list_no_dup_user.includes("674") );
	
	// Checks if user is a wp_user (corner case error checking), if they aren't, do not add them. 
	if( !is_wp_user ) {
		
		set_alert( 'danger', 'User: <b>' + user_name + '</b> will not be added as the PATT Agent is not associated with a valid WP User. Try deleting the PATT Agent and re-adding them.' );
		
		return false;	
	}
	
	
	// Checks if the user being added is already listed on the same status (not allowed)
	if( requestor_list.indexOf(termmeta_user_val.toString()) >= 0 ) { 
		console.log('termmeta_user_val: '+termmeta_user_val+' is already listed');
		html_str = '';
		set_alert('warning', 'User already listed in the same Status.');
	} else {

		var html_str = '<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">'
						+'<div class="flex-container staff-badge" style="">'
							+user_name
							+'<span class="staff-close" onclick="wpsc_remove_filter(this);remove_user('+term_id+');"><i class="fa fa-times" aria-hidden="true" title="Remove User"></i><span class="sr-only">Remove User</span></span>'
						+'<input type="hidden" name="assigned_agent['+term_id+']" value="'+termmeta_user_val+'" />'
						+'</div>'
					+'</div>';	

	}
	
	
	// Validation check: does PATT user have a WP User associated with it. Corner Case issue. 
	
	//Patt_Custom_Func::translate_user_id();
	
	
	

	// Single Box: Check if the same user is assigned to status: QA/QC and Validation (not allowed)
	var duplicate = false;
	let term_user_list = [];
// 	if( status_list_no_dup_user.includes(Number(term_id)) ) { 
	if( status_list_no_dup_user.includes(Number(term_id)) || status_list_no_dup_user.includes(term_id) ) {
		
		console.log('Checking for Duplicates');
					
		status_list_no_dup_user.forEach( function(x) {
			term_user_list[x] = jQuery("input[name='assigned_agent["+x+"]']").map(function(){return jQuery(this).val();}).get();
			let user_num = termmeta_user_val.toString();
			let user_already_assigned = term_user_list[x].includes(user_num);
			console.log('User in QAQC or Validate: '+user_already_assigned);
			
			if( user_already_assigned ) {
				duplicate = true;
				html_str = '';
// 				set_alert( 'warning', 'User not added. Same user cannot be assigned for QA/QC & Validation.' );
				//console.log('qaqc warning 1');
				set_alert( 'qaqc-danger', 'User not added. Same user cannot be assigned for QA/QC & Validation.' );
				return false; // breaks foreach loop
			}
		});		
		console.log('the list of terms and users: ');
		console.log(term_user_list);		
	}
	
	
	
	// Multiple Boxes: Check if the same user is assigned to status: QA/QC and Validation (not allowed)	
	let item_ids = <?php echo json_encode($item_ids); ?>;
	let box_status_term_user_list = <?php echo json_encode($box_status_term_user_list); ?>;	
	console.log('term list');
	console.log(box_status_term_user_list);
	term_user_list = [];
	
// 	if( status_list_no_dup_user.includes(Number(term_id)) ) {
	if( status_list_no_dup_user.includes(Number(term_id)) || status_list_no_dup_user.includes(term_id) ) {	
		console.log('Checking for Duplicates - MULTI');
		console.log('user value: '+termmeta_user_val);

		let already_included = false;
		
		item_ids.forEach( function(box_id) {
			console.log('box_id: '+box_id);
			let sublist = [];
			status_list_no_dup_user.forEach( function(status_id) { 
				
				console.log(box_status_term_user_list[box_id][status_id]);
				
				if( box_status_term_user_list[box_id][status_id] ) {
					already_included = box_status_term_user_list[box_id][status_id].includes(termmeta_user_val);
				}
				
				console.log('already included: '+already_included);
				
				if( already_included ) {
					console.log('break!');
					console.log('inside box_id: '+box_id);					
					html_str = '';
					let link = box_link_start+box_id+link_mid+box_id+link_end;
					let status_name = status_list_name_no_dup_user[status_id];
// 					set_alert( 'warning', 'User not added. Same user cannot be assigned for QA/QC & Validation. Box: '+link+' status: '+status_name );
					console.log('qaqc warning 2');
					set_alert( 'qaqc-danger', 'User not added. Same user cannot be assigned for QA/QC & Validation. Box: ' + link + ' status: ' + status_name );
					already_included = false; // reset
					return false; // breaks foreach loop
				}
			});
			
		});
	}
	
	return html_str;		
}


function remove_user(term_id) {
	//if zero users remove save
	//if more than 1 user show save
	var requestor_list = jQuery("input[name='assigned_agent["+term_id+"]']").map(function(){return jQuery(this).val();}).get();
	let is_single_item = <?php echo json_encode($is_single_item); ?>;
	console.log('Remove user');
	console.log(requestor_list);
	console.log('length: '+requestor_list.length);
	console.log('single item? '+is_single_item);
	console.log('term_id: '+term_id);
	
	
	var save_enabled = '<?php echo $save_enabled ?>';
	console.log('Save Enabled? '+save_enabled);
	if( is_single_item ) {
		console.log('doing single item stuff');
// 		if( requestor_list.length > 0 && save_enabled) {
		if( save_enabled ) {
			console.log('show');
			jQuery("#button_agent_submit").show();
			//jQuery("#button_agent_submit").removeAttr( 'disabled' );
		} else {
			jQuery("#button_agent_submit").hide();
			//jQuery("#button_agent_submit").attr( 'disabled', 'disabled' );
		}
	}
}



</script>

<?php

$body = ob_get_clean();

ob_start();

?>
<!-- Disable refresh -->
<!--<button type="button" class="btn wpsc_popup_close" onclick="wpsc_modal_close();window.location.reload();"><?php _e('Close','wpsc-export-ticket');?></button>-->
<button type="button" class="btn wpsc_popup_close" onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<?php
if( !in_array($ticket_status, $status_id_arr) && $type == 'edit' ) {
?>
<button type="button" id="button_agent_submit" class="btn wpsc_popup_action"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_agents();"><?php _e('Save','supportcandy');?></button>
<?php } 
  
  if( $type == 'todo' ) {
?>
  <button type="button" id="button_todo_submit" class="btn wpsc_popup_action"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_todo();"><?php _e('Save','supportcandy');?></button>
<?php } elseif( $type == 'recall_todo' ) { ?>
  <button type="button" id="button_todo_recall_submit" class="btn wpsc_popup_action"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_todo_recall();"><?php _e('Save','supportcandy');?></button>
<?php } elseif( $type == 'decline_todo' ) { ?>
  <button type="button" id="button_todo_decline_submit" class="btn wpsc_popup_action"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_todo_decline();"><?php _e('Save','supportcandy');?></button>

<?php } ?>

<script>
jQuery("#button_agent_submit").hide();
//jQuery("#button_agent_submit").attr( 'disabled', 'disabled' );
jQuery("#button_todo_submit").hide();
jQuery("#button_todo_recall_submit").hide();
jQuery("#button_todo_decline_submit").hide();




//
// Sets Status via AJAX for the To-Do list. 
//

function wppatt_set_todo() {
  console.log( 'SET TODO' );
  
   // Datatable object
  var dataTable = jQuery('#tbl_templates_todo').DataTable({
	    
	    'autoWidth': true,
		'processing': true,
		'serverSide': true,
		'stateSave': true,
		//'scrollX' : true,
		
		"initComplete": function (settings, json) {
		    jQuery("#tbl_templates_todo").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
		},
		'retrieve': true,
		'paging' : true,
		'stateSaveParams': function(settings, data) {
			data.sg = jQuery('#searchGeneric').val();
			data.bid = jQuery('#searchByBoxID').val();
			data.po = jQuery('#searchByProgramOffice').val();
			<?php
			if (($agent_permissions['label'] == 'Requester') || ($agent_permissions['label'] == 'Requester Pallet'))
            {
			?>
		    data.dc = jQuery('#searchByDigitizationCenter').val(); 
			<?php
            }
			?>
			data.sp = jQuery('#searchByPriority').val();
			data.sbs = jQuery('#searchByStatus').val();
			data.rd = jQuery('#searchByRecallDecline').val();
			data.es = jQuery('#searchByECMSSEMS').val();
			data.sbu = jQuery('#searchByUser').val(); 
			data.aaVal = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();     
			data.aaName = jQuery(".searched-user").map(function(){return jQuery(this).text();}).get();                   
		},
		'stateLoadParams': function(settings, data) {
			jQuery('#searchGeneric').val(data.sg);
			jQuery('#searchByBoxID').val(data.bid);
			jQuery('#searchByProgramOffice').val(data.po);
			<?php
			if (($agent_permissions['label'] == 'Requester') || ($agent_permissions['label'] == 'Requester Pallet'))
            {
			?>
			jQuery('#searchByDigitizationCenter').val(data.dc);
			<?php
            }
			?>
			jQuery('#searchByPriority').val(data.sp);
			jQuery('#searchByRecallDecline').val(data.rd);
			jQuery('#searchByECMSSEMS').val(data.es);
			jQuery('#searchByStatus').val(data.sbs); 
			jQuery('#searchByUser').val(data.sbu); 
			
			// If data values aren't defined then set them as blank arrays.
			if( typeof data.aaVal == 'undefined' ) {
				data.aaVal = [];
				data.aaName = [];				
			}
			
			data.aaVal.forEach( function(val, key) {
				let html_str = get_display_user_html(data.aaName[key], val); 
				jQuery('#assigned_agents').append(html_str);
			});
			//let html_str = get_display_user_html(ui.item.label, ui.item.flag_val);
			//jQuery('#assigned_agents').append(html_str);
			//jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get(); //load saved users   
		},
		'serverMethod': 'post',
		'searching': false, // Remove default Search Control
		'ajax': {
			'url':'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/box_processing.php',
			'data': function(data){
				// Read values
				var po_value = jQuery('#searchByProgramOffice').val();
				var po = jQuery('#searchByProgramOfficeList [value="' + po_value + '"]').data('value');
				var sg = jQuery('#searchGeneric').val();
				var boxid = jQuery('#searchByBoxID').val();
				var dc = jQuery('#searchByDigitizationCenter').val();
				var sp = jQuery('#searchByPriority').val();
				var rd = jQuery('#searchByRecallDecline').val();
				var es = jQuery('#searchByECMSSEMS').val();
				var sbs = jQuery('#searchByStatus').val(); 
				var sbu = jQuery('#searchByUser').val();  
				var aaVal = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();     
				var aaName = jQuery(".searched-user").map(function(){return jQuery(this).text();}).get(); 
				//console.log({is_requester:is_requester});
				// Append to data
				data.searchGeneric = sg;
				data.searchByBoxID = boxid;
				data.searchByProgramOffice = po;
				data.searchByDigitizationCenter = dc;
				data.searchByPriority = sp;
				data.searchByRecallDecline = rd;
				data.searchByECMSSEMS = es;
				data.searchByStatus = sbs;
				data.searchByUser = sbu;
				data.searchByUserAAVal = aaVal;
				data.searchByUserAAName = aaName;
				data.is_requester = is_requester;
			
			}
		},
		'drawCallback': function (settings) { 
		    jQuery('[data-toggle="tooltip"]').tooltip();
	        // Here the response
	        var response = settings.json;
	       	        console.log(response);
    	},
        'lengthMenu': [[10, 25, 50, 100], [10, 25, 50, 100]],
		'fixedColumns': true,
	<?php		
	if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet'))
	{
	?>
		'columnDefs': [
		{	'width' : 5,
			'targets': 0,	
			'checkboxes': {	
			   'selectRow': true	
			},
		},
		{
            'targets': [ 1 ],
            'orderData': [ 2 ]
        },
        {
            'targets': [ 2 ],
            'visible': false,
            'searchable': false
        },
		{ 'width': '100%', 'targets': 4 },
		{ 'width': '5%', 'targets': 6 }
		],
		'select': {	
			'style': 'multi'
		},
		'order': [[1, 'asc']],
// 		'order': [[1, 'desc']],
	<?php
	}
	?>
		'columns': [
	<?php		
	if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet'))
	{
	?>
			{ data: 'box_id', 'title': 'Select All Checkbox'},

	<?php
	}
	?>

			{ data: 'box_id_flag', 'class' : 'text_highlight'},
			{ data: 'dbid', visible: false},
			{ data: 'pallet_id' },
			{ data: 'ticket_priority' },
			{ data: 'request_id', 'class' : 'text_highlight' },
			{ data: 'status' },       
			{ data: 'location' },
			{ data: 'acronym' },
			{ data: 'validation' },
		]
	});
  

  let destruction_of_source_term_id = <?php echo $destruction_of_source_term; ?>;
  
  let item_id = '<?php echo $item_ids[0]; ?>';	// always a single item, never multiple
//   let ticket_id = <?php echo $ticket_id; ?>;
  let ticket_id = <?php echo json_encode( $ticket_id ); ?>;
  let current_box_status = <?php echo $current_box_status; ?>;
  
  console.log({ item_id:item_id, ticket_id:ticket_id });  
  
  let is_checked = jQuery('*[data-box-status-id="'+current_box_status+'"]').is(':checked');
  console.log({is_checked:is_checked});
  console.log('current box status: ' + current_box_status);
  console.log( 'term id: ' + destruction_of_source_term_id);

  if(current_box_status == destruction_of_source_term_id){
	var form = this;
	     var rows_selected = dataTable.column(0).checkboxes.selected();
		 console.log('rows selected: ' + rows_selected);
			   jQuery.post(
	   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_destruction.php',{
	postvarsboxid : item_id,
	}, 
	   function (response) {
	      //if(!alert(response)){
	      
	      wpsc_modal_open('Destruction Completed');
			  var data = {
			    action: 'wpsc_get_destruction_completed_b',
			    response_data: response
			  };
			  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
			    var response = JSON.parse(response_str);
			    jQuery('#wpsc_popup_body').html(response.body);
			    jQuery('#wpsc_popup_footer').html(response.footer);
			    jQuery('#wpsc_cat_name').focus();
			  }); 
			  
	          dataTable.ajax.reload( null, false );
              dataTable.column(0).checkboxes.deselectAll();
	      //}
	   });
  }
  
  jQuery.post(
    '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_todo_list_box_status.php',{
      type: 'todo_box_status_update',
      item_id: item_id,
      current_box_status: current_box_status,
      ticket_id: ticket_id
	  }, 
    function (response) {
			//alert('updated: '+response);
			response = JSON.parse( response );
			console.log('TODO Response:');
			console.log(response);
			//Disable refresh
			//window.location.reload();
			
			//Only refreshes the datatable, not the window // To-Do icon/functionality on To-Do Dashboard and Box Dashboard.
			jQuery('#tbl_templates_todo').DataTable().ajax.reload(null, false);
			jQuery('#tbl_templates_boxes').DataTable().ajax.reload(null, false); 
  });
  
  wpsc_modal_close();
}

//
// Sets Status via AJAX for the Recall To-Do list. 
//

function wppatt_set_todo_recall() {
  console.log( 'SET RECALL TODO' );
  
  let recall_ids = '<?php echo $recall_ids[0]; ?>'; // always a single item, never multiple // Recall ID with leading zeros
//  let ticket_id = <?php echo $ticket_id; ?>;
  let ticket_id = <?php echo json_encode( $ticket_id ); ?>;
  let current_recall_status = <?php echo $current_box_status; ?>; // current recall status.
  let recall_db_id = <?php echo ltrim($recall_ids[0], "0"); ?> // recall id trimmed without leading zeros
  
  console.log({ recall_ids:recall_ids, ticket_id:ticket_id, current_recall_status:current_recall_status, recall_db_id: recall_db_id });  
  
  //let is_checked = jQuery('*[data-box-status-id="'+current_box_status+'"]').is(':checked');
  //console.log({is_checked:is_checked});
  
  jQuery.post(
    '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_todo_list_box_status.php',{
      type: 'todo_recall_update',
      item_id: recall_ids,
      current_recall_status: current_recall_status,
      ticket_id: ticket_id,
      recall_db_id: recall_db_id
	  }, 
    function (response) {
			//alert('updated: '+response);
			response = JSON.parse( response );
			console.log('RECALL TODO Response:');
			console.log(response);
			//Disable refresh
			//window.location.reload();
			
			//Only refreshes the datatable, not the window // Recall To-Do icon/functionality only on Recall Dashboard.
	    jQuery('#tbl_templates_recall').DataTable().ajax.reload(null, false);
	    jQuery('#tbl_templates_recall_todo').DataTable().ajax.reload(null, false);
  });
  wpsc_modal_close();
}

//
// Sets Status via AJAX for the Decline To-Do list. 
//

function wppatt_set_todo_decline() {
  console.log( 'SET Decline TODO' );
  
  let item_id = '<?php echo $decline_ids[0]; ?>'; // always a single item, never multiple
  //let ticket_id = <?php echo $ticket_id; ?>;
  //let ticket_id = <?php echo json_encode( $ticket_id ); ?>;
  let current_decline_status = <?php echo $current_box_status; ?>; // current decline status.
  let decline_db_id = <?php echo ltrim($decline_ids[0], "0"); ?> // recall id trimmed without leading zeros
  
  //console.log({ item_id:item_id, ticket_id:ticket_id });  
  
  //let is_checked = jQuery('*[data-box-status-id="'+current_box_status+'"]').is(':checked');
  //console.log({is_checked:is_checked});
  
  console.log('current decline status: ' + current_decline_status);
  
  jQuery.post(
    '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_todo_list_box_status.php',{
      type: 'todo_decline_update',
      item_id: item_id,
      current_decline_status: current_decline_status,
      decline_db_id: decline_db_id
      //current_box_status: current_box_status,
      //ticket_id: ticket_id
	  }, 
    function (response) {
			//alert('updated: '+response);
			response = JSON.parse( response );
			console.log('Decline TODO Response:');
			console.log(response);
			//Disable refresh
			//window.location.reload();
			
			//Only refreshes the datatable, not the window // Decline To-Do icon/functionality only on Decline Dashboard.
	    jQuery('#tbl_templates_return').DataTable().ajax.reload(null, false);
	    jQuery('#tbl_templates_decline_todo').DataTable().ajax.reload(null, false);
  });
  wpsc_modal_close();
}


// Sets agents for the statuses. 
function wppatt_set_agents() {
	let item_ids = <?php echo json_encode($item_ids); ?>;	
	let term_id_array = <?php echo json_encode($term_id_array); ?>;
	let is_single_item = <?php echo json_encode($is_single_item); ?>;
	
	console.log('setting agents for items: ');
	console.log(item_ids);
	
	
	//OLD - start
	var new_requestors = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();
 	var old_requestors = <?php echo json_encode($old_assigned_agents); ?>;
 	//OLD -end
 	
 	let new_agents_array = [];
 	term_id_array.forEach( function(x) {
// 	 	console.log('x= ');
// 	 	console.log(x);
// 	 	console.log(jQuery("input[name='assigned_agent["+x+"]']"));
// 	 	new_agents_array.push(  jQuery("input[name='assigned_agent["+x+"]']").map(function(){return jQuery(this).val();}).get() );
	 	new_agents_array.push( {term:x, agents:jQuery("input[name='assigned_agent["+x+"]']").map(function(){return jQuery(this).val();}).get()} );	 	
 	});	
 
	//console.log(new_requestors);
	console.log('term id array: ');
	console.log(term_id_array);
	console.log('new agents array');
	console.log(new_agents_array);
	console.log('item_ids ');
	console.log(item_ids);
	
	// Another check to ensure you can't save 0 users
// 	if( new_requestors.length > 0 ) {
		jQuery.post(
      '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_box_status_agents.php',{
        type: 'box_status_agents',
        new_agents_array: new_agents_array,
        item_ids: item_ids,
        is_single_item: is_single_item,
        recall_id: '<?php echo $recall_id ?>',
        ticket_id: '<?php echo $ticket_id ?>',
        new_requestors: new_requestors,
        old_requestors: old_requestors
		  }, 
	    function (response) {
  			//alert('updated: '+response);
  			console.log('The Response:');
  			console.log(response);
  			//window.location.reload();
	  });
//     }

	//wpsc_modal_close();

<?php if($ticket_id != '' && $_REQUEST['page'] == 'request') { ?>
wpsc_open_ticket(<?php echo $ticket_id; ?>);
wpsc_modal_close();
<?php
}

if ($_REQUEST['page'] == 'boxes') {
?>
var wpsc_setting_action = 'boxes';
var attrs = {"page":"boxes"};

jQuery(document).ready(function(){
         //Disables windows reload and enables datatables refresh
         //wpsc_init(wpsc_setting_action,attrs);
         jQuery('#tbl_templates_boxes').DataTable().ajax.reload(null, false);
});

wpsc_modal_close();
<?php 
} 
if ($_REQUEST['page'] == 'boxdetails') {
?>
wpsc_modal_close();
//Disable refresh
//window.location.reload();
<?php 
}

if($_REQUEST['page'] == 'todo') {
?>
wpsc_modal_close();
//Disable refresh
//window.location.reload();
<?php } ?>
}
  
  	//
	// Auto Complete for last type of assign agents
	//

	jQuery(".ui-autocomplete-input").eq(5).autocomplete({
			minLength: 0,
			position: { my: "left bottom", at: "left top" }
	});
	
	jQuery('.wpsc_assign_agents').last().autocomplete({
			minLength: 0,
			position: { my: "left bottom", at: "left top" }
	});
</script>



<?php 
$footer = ob_get_clean();

$output = array(
  'body'   => $body,
  'footer' => $footer
);
echo json_encode($output);

