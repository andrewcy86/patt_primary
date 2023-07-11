<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');
include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '';
$item_id = isset($_POST['item_id']) ? sanitize_text_field($_POST['item_id']) : ''; // full number i.e. 0000003-1
$recall_db_id = isset($_POST['recall_db_id']) ? sanitize_text_field($_POST['recall_db_id']) : ''; // no leading zeros i.e. 3
$current_box_status = isset($_POST['current_box_status']) ? sanitize_text_field($_POST['current_box_status']) : ''; // term_id
$current_recall_status = isset($_POST['current_recall_status']) ? sanitize_text_field($_POST['current_recall_status']) : ''; // term_id
$current_decline_status = isset($_POST['current_decline_status']) ? sanitize_text_field($_POST['current_decline_status']) : ''; // term_id
$decline_db_id = isset($_POST['decline_db_id']) ? sanitize_text_field($_POST['decline_db_id']) : ''; // no leading zeros i.e. 3

// get the id aka foreign key of the box
$box_fk = Patt_Custom_Func::get_id_by_box_id( $item_id );

$box_details_obj = Patt_Custom_Func::get_box_file_details_by_id( $item_id );
$storage_location_id = $box_details_obj->storage_location_id;

// Register Recall Status Taxonomy
if( !taxonomy_exists('wppatt_recall_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wppatt_recall_statuses', 'wpsc_ticket', $args );
}

// Recall status slugs
$recall_recalled_tag = get_term_by('slug', 'recalled', 'wppatt_recall_statuses'); 
$recall_approved_tag = get_term_by('slug', 'recall-approved', 'wppatt_recall_statuses'); 
$recall_complete_tag = get_term_by('slug', 'recall-complete', 'wppatt_recall_statuses');
$recall_shipped_back_tag = get_term_by('slug', 'shipped-back', 'wppatt_recall_statuses');
$recall_received_at_ndc_tag = get_term_by('slug', 'recall-received-at-ndc', 'wppatt_recall_statuses'); 

$recall_recalled_term = $recall_recalled_tag->term_id;
$recall_approved_term = $recall_approved_tag->term_id;
$recall_complete_term = $recall_complete_tag->term_id;
$recall_shipped_back_term = $recall_shipped_back_tag->term_id;
$recall_received_at_ndc_term = $recall_received_at_ndc_tag->term_id;


// Register Decline Status Taxonomy
if( !taxonomy_exists('wppatt_return_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wppatt_return_statuses', 'wpsc_ticket', $args );
}

// Decline status slugs
$decline_initiated_tag = get_term_by('slug', 'decline-initiated', 'wppatt_return_statuses');
$decline_complete_tag = get_term_by('slug', 'decline-complete', 'wppatt_return_statuses');
$decline_received_at_ndc_tag = get_term_by('slug', 'decline-received-at-ndc', 'wppatt_return_statuses'); 

$decline_initiated_term = $decline_initiated_tag->term_id;
$decline_complete_term = $decline_complete_tag->term_id;
$decline_received_at_ndc_term = $decline_received_at_ndc_tag->term_id;

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
$next_status_arr[ $box_scanning_preparation_tag->term_id ] = $box_scanning_digitization_tag->term_id;
$next_status_arr[ $box_scanning_digitization_tag->term_id ] = $box_qa_qc_tag->term_id;
$next_status_arr[ $box_qa_qc_tag->term_id ] = $box_digitized_not_validated_tag->term_id;
$next_status_arr[ $box_destruction_approved_tag->term_id ] = $box_destruction_of_source_tag->term_id;
$next_status_arr[ $box_destruction_of_source_tag->term_id ] = $box_completed_dispositioned_tag->term_id;
// $next_status_arr[ $box_destruction_approved_tag->term_id ] = $box_destruction_approved_tag->term_id;
// $next_status_arr[ $box_destruction_of_source_tag->term_id ] = $box_completed_dispositioned_tag->term_id;

// Get the next status based on the current status
$next_box_status = $next_status_arr[ $current_box_status ];

// Set the name of the db table column to be updated in epa_storage_location
// based on the current box status, which has just been finished. 
$storage_location_status_flag_name = '';

if( $current_box_status == $box_scanning_preparation_tag->term_id ) {
  $storage_location_status_flag_name = 'scanning_preparation';
} elseif( $current_box_status == $box_scanning_digitization_tag->term_id ) {
  $storage_location_status_flag_name = 'scanning_digitization';
} elseif( $current_box_status == $box_qa_qc_tag->term_id ) {
  $storage_location_status_flag_name = 'qa_qc';
} elseif( $current_box_status == $box_destruction_approved_tag->term_id ) {
  $storage_location_status_flag_name = 'destruction_approved';
} elseif( $current_box_status == $box_destruction_of_source_tag->term_id ) {
  $storage_location_status_flag_name = 'destruction_of_source';
} 

// term_id's for each box status we are focused on - DONE
// list of box status order - DONE
// get next box status - DONE
// update section for flags
// update section for box status and previous box status.
// Audit log. 


if( $type == 'todo_box_status_update' ) {
  
  // If completing the Destruction Approved status, do not move it to the next status & no audit log.
  if( $next_box_status != $box_destruction_approved_tag->term_id ) {
    
    // Update Box status & Box Previous Status
    $table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
    $data_where = array( 'id' => $box_fk );
    $data_update = array( 'box_status' => $next_box_status, 'box_previous_status' => $current_box_status );
    $wpdb->update( $table_name, $data_update, $data_where );
	
	}
	
	// Update Flags in epa_storage_location
	$table_name = $wpdb->prefix . 'wpsc_epa_storage_location';
	$data_where = array( 'id' => $storage_location_id );
	
	$col_name = '';
	$status_name = '';
	$previous_status_name = '';
	
	if( $current_box_status == $box_scanning_preparation_tag->term_id ) {
  	$col_name = 'scanning_preparation';
  	$previous_status_name = $box_scanning_preparation_tag->name;
  	$status_name = $box_scanning_digitization_tag->name;
  	
	} elseif( $current_box_status == $box_scanning_digitization_tag->term_id ) {
  	$col_name = 'scanning_digitization';
  	$previous_status_name = $box_scanning_digitization_tag->name;
  	$status_name = $box_qa_qc_tag->name;
  	
	} elseif( $current_box_status == $box_qa_qc_tag->term_id ) {
  	$col_name = 'qa_qc';
  	$previous_status_name = $box_qa_qc_tag->name;
  	$status_name = $box_digitized_not_validated_tag->name;
  	
	} elseif( $current_box_status == $box_destruction_approved_tag->term_id ) {
  	$col_name = 'destruction_approved';
  	$previous_status_name = $box_destruction_approved_tag->name;
  	$status_name = $box_destruction_of_source_tag->name;
  	
	} elseif( $current_box_status == $box_destruction_of_source_tag->term_id ) {
  	$col_name = 'destruction_of_source';
  	$previous_status_name = $box_destruction_of_source_tag->name;
  	$status_name = $box_completed_dispositioned_tag->name;
  	
	}
	
  $data_update = array( $col_name => 1 );
  $flag_update_result = $wpdb->update( $table_name, $data_update, $data_where );
	
	
	// Audit Log, only for all statuses except for Destruction Approval, as it's not moving to the next state here. 
	if( $next_box_status != $box_destruction_approved_tag->term_id ) {
  	
  	$status_str = $previous_status_name . ' to ' . $status_name;
  	do_action('wpppatt_after_box_status_update', $ticket_id, $status_str, $item_id );
  }
  
} elseif( $type == 'todo_recall_update' )  {
  // Update flags in recall table
  
  // Update Flags in wpsc_epa_recallrequest
	$table_name = $wpdb->prefix . 'wpsc_epa_recallrequest';
	$data_where = array( 'recall_id' => $item_id );
	$data_update;
  
  // Update Flags in wpsc_epa_shipping_tracking
	$table_name2 = $wpdb->prefix . 'wpsc_epa_shipping_tracking';
	$data_where2 = array( 'recallrequest_id' => $recall_db_id );
	$data_update2;
	
	if( $current_recall_status == $recall_approved_term ) {
      $data_update = [ 'recall_approved'=>1 ];
	} elseif( $current_recall_status == $recall_received_at_ndc_term ) {
      $data_update = [ 'recall_complete'=>1 ];
      $data_update2 = [ 'delivered'=>1 ];
	}
  
  $flag_update_result = $wpdb->update( $table_name, $data_update, $data_where );
  $flag_update_result2 = $wpdb->update( $table_name2, $data_update2, $data_where2 );
  
} elseif( $type == 'todo_decline_update' )  {
  
  // Update Flags in wpsc_epa_return
	$table_name = $wpdb->prefix . 'wpsc_epa_return';
	$data_where = array( 'return_id' => $item_id );
	$data_update;
  
  // Update Flags in wpsc_epa_shipping_tracking
	$table_name2 = $wpdb->prefix . 'wpsc_epa_shipping_tracking';
	$data_where2 = array( 'return_id' => $decline_db_id );
	$data_update2;
	
	if( $current_decline_status == $decline_initiated_tag->term_id ) {
  		// $data_update = [ 'return_complete'=>1 ];
		  $data_update = [ 'return_initiated'=>1 ];
	} elseif( $current_decline_status == $decline_received_at_ndc_tag->term_id ) {
		$data_update = [ 'return_complete'=>1 ];
      	$data_update2 = [ 'delivered'=>1 ];
	}
  
  $flag_update_result = $wpdb->update( $table_name, $data_update, $data_where );
  $flag_update_result2 = $wpdb->update( $table_name2, $data_update2, $data_where2 );
  
}





$response = array(
	"type" => $type,
	"ticket_id" => $ticket_id,
	"item_id" => $item_id,
	"table_name" => $table_name,
	"current_box_status" => $current_box_status,
	"current_recall_status" => $current_recall_status,
	"recall_recalled_term" => $recall_recalled_term,
	"recall_recalled_tag" => $recall_recalled_tag,
	"recall_complete_term" => $recall_complete_term,
	"recall_complete_tag" => $recall_complete_tag,
	"recall_shipped_back_term" => $recall_shipped_back_term,
	"recall_shipped_back_tag" => $recall_shipped_back_tag,
	"recall_received_at_ndc_term" => $recall_received_at_ndc_term,
  	"recall_received_at_ndc_tag" => $recall_received_at_ndc_tag,
	"decline_initiated_term" => $decline_initiated_term,
	"decline_initiated_tag" => $decline_initiated_tag,
	"decline_complete_term" => $decline_complete_term,
	"decline_complete_tag" => $decline_complete_tag,
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