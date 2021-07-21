<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// set default filter for agents and customers //not true
global $current_user, $wpscfunction, $current_user, $wpdb;

if (!$current_user->ID) die();

// Grab POST variables
//$item_ids = $_REQUEST['item_ids'];
$item_ids = $_REQUEST['id_array'];


// Define
$term_arr = [];
$data = [];
$restricted_request_statuses = [ 'New Request', 'Initial Review Rejected', 'Tabled', 'Cancelled' ];
$in_restricted_status = false;

forEach( $item_ids as $item ) {
	
	$data['box_folder_file_id'] = $item;
	$ticket_arr = Patt_Custom_Func::get_ticket_id_from_box_folder_file( $data );
	$where['ticket_id'] = $ticket_arr[ 'ticket_id' ];
	$status = Patt_Custom_Func::get_ticket_status( $where );
	$term = Patt_Custom_Func::get_term_name_by_id(  $status ); 
	$term_arr[] = $term;
}

$term_arr = array_unique( $term_arr );

forEach( $term_arr as $term ) {
	
	$valid = in_array( $term, $restricted_request_statuses );
	
	if( $valid ) {
		$in_restricted_status = true;
	}
	
}


$id_array = $_REQUEST['id_array'];
	
$response = array(
	"id_array" => $id_array,
	"term_array" => $term_arr,
	"in_restricted_status" => $in_restricted_status
);
	

echo json_encode( $response );