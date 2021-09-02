<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');


$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '';
$item_id = isset($_POST['item_id']) ? sanitize_text_field($_POST['item_id']) : ''; // full number i.e. 0000003-1
$current_box_status = isset($_POST['current_box_status']) ? sanitize_text_field($_POST['current_box_status']) : ''; // term_id

// get the id aka foreign key of the box
$box_fk = Patt_Custom_Func::get_id_by_box_id( $item_id );

$box_details_obj = Patt_Custom_Func::get_box_file_details_by_id( $item_id );
$storage_location_id = $box_details_obj->storage_location_id;

// get all pertinent term_ids
// Box status slugs
$pending_tag = get_term_by('slug', 'pending', 'wpsc_box_statuses'); 
$scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); 
$scanning_digitization_tag = get_term_by('slug', 'scanning-digitization', 'wpsc_box_statuses'); 
$qa_qc_tag = get_term_by('slug', 'q-a', 'wpsc_box_statuses'); 
$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); 
$digitized_not_val_tag = get_term_by('slug', 'closed', 'wpsc_box_statuses'); 
$destruction_approved_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); 
$destruction_of_source_tag = get_term_by('slug', 'destruction-of-source', 'wpsc_box_statuses'); 
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); 

$scanning_preparation_term = $scanning_preparation_tag->term_id;
$scanning_digitization_term = $scanning_digitization_tag->term_id;
$qa_qc_term = $qa_qc_tag->term_id;
$validation_term = $validation_tag->term_id;
$digitized_not_val_term = $digitized_not_val_tag->term_id;
$destruction_approved_term = $destruction_approved_tag->term_id;
$destruction_of_source_term = $destruction_of_source_tag->term_id;
$completed_dispositioned_term = $completed_dispositioned_tag->term_id;


// Box status flow
// $next_status_arr is an array where the index is the term_id for the current box status, 
// and the value is the new box status.
// if box status is x, then change status to y
// arr[x] = y

// scanning prep -> scanning/digitization
// scanning/digitization -> QA/QC
// QA/QC -> digitized not validated
// Validation -> never
// Destruction Approved -> Destruction of Source
// Destruction of Source -> Completed/Dispositioned

$next_status_arr = [];
$next_status_arr[ $scanning_preparation_term ] = $scanning_digitization_term;
$next_status_arr[ $scanning_digitization_term ] = $qa_qc_term;
$next_status_arr[ $qa_qc_term ] = $digitized_not_val_term;
$next_status_arr[ $destruction_approved_term ] = $destruction_of_source_term;
$next_status_arr[ $destruction_of_source_term ] = $completed_dispositioned_term;

// Get the next status based on the current status
$next_box_status = $next_status_arr[ $current_box_status ];

// Set the name of the db table column to be updated in epa_storage_location
// based on the current box status, which has just been finished. 
$storage_location_status_flag_name = '';

if( $current_box_status == $scanning_preparation_term ) {
  $storage_location_status_flag_name = 'scanning_preparation';
} elseif( $current_box_status == $scanning_digitization_term ) {
  $storage_location_status_flag_name = 'scanning_digitization';
} elseif( $current_box_status == $qa_qc_term ) {
  $storage_location_status_flag_name = 'qa_qc';
} elseif( $current_box_status == $destruction_approved_term ) {
  $storage_location_status_flag_name = 'destruction_approved';
} elseif( $current_box_status == $destruction_of_source_term ) {
  $storage_location_status_flag_name = 'destruction_of_source';
} 

// term_id's for each box status we are focused on - DONE
// list of box status order - DONE
// get next box status - DONE
// update section for flags
// update section for box status and previous box status.
// Audit log. 


if( $type == 'todo_box_status_update' ) {
  
  
  
  // Update Box status & Box Previous Status
  $table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
  $data_where = array( 'id' => $box_fk );
  $data_update = array( 'box_status' => $next_box_status, 'box_previous_status' => $current_box_status );
  $wpdb->update( $table_name, $data_update, $data_where );
	
	
	
	// Update Flags
	$table_name = $wpdb->prefix . 'wpsc_epa_storage_location';
	$data_where = array( 'id' => $storage_location_id );
	
	$col_name = '';
	$status_name = '';
	$previous_status_name = '';
	
	if( $current_box_status == $scanning_preparation_term ) {
  	$col_name = 'scanning_preparation';
  	$previous_status_name = $scanning_preparation_tag->name;
  	$status_name = $scanning_digitization_tag->name;
  	
	} elseif( $current_box_status == $scanning_digitization_term ) {
  	$col_name = 'scanning_digitization';
  	$previous_status_name = $scanning_digitization_tag->name;
  	$status_name = $qa_qc_tag->name;
  	
	} elseif( $current_box_status == $qa_qc_term ) {
  	$col_name = 'qa_qc';
  	$previous_status_name = $qa_qc_tag->name;
  	$status_name = $digitized_not_val_tag->name;
  	
	} elseif( $current_box_status == $destruction_approved_term ) {
  	$col_name = 'destruction_approved';
  	$previous_status_name = $destruction_approved_tag->name;
  	$status_name = $destruction_of_source_tag->name;
  	
	} elseif( $current_box_status == $destruction_of_source_term ) {
  	$col_name = 'destruction_of_source';
  	$previous_status_name = $destruction_of_source_tag->name;
  	$status_name = $completed_dispositioned_tag->name;
  	
	}
	
  $data_update = array( $col_name => 1 );
  $flag_update_result = $wpdb->update( $table_name, $data_update, $data_where );
	
	
	// Audit Log
	$status_str = $previous_status_name . ' to ' . $status_name;
	
	do_action('wpppatt_after_box_status_update', $ticket_id, $status_str, $item_id );
} 




$response = array(
	"type" => $type,
	"ticket_id" => $ticket_id,
	"item_id" => $item_id,
	"current_box_status" => $current_box_status,
	"next_status_arr" => $next_status_arr,
	"next_box_status" => $next_box_status,
	"storage_location_status_flag_name" => $storage_location_status_flag_name,
	"data_where" => $data_where,
	"data_update" => $data_update,
	"box_details_obj" => $box_details_obj,
	"storage_location_id" => $storage_location_id,
	"flag_update_result" => $flag_update_result
	
);
	


echo json_encode($response);

?>