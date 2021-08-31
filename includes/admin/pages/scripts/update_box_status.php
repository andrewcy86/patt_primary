<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

//$recall_id = isset($_POST['recall_id']) ? sanitize_text_field($_POST['recall_id']) : '';
$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
//$title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
//$recall_ids = $_REQUEST['recall_ids']; 
//$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '';

//Get Statuses to check against when saving box_previous_status
$bs_waiting_shelved_obj = get_term_by('slug', 'waiting-shelved', 'wpsc_box_statuses'); //816
$bs_waiting_rlo_obj = get_term_by('slug', 'waiting-on-rlo', 'wpsc_box_statuses'); // 1056

$bs_waiting_shelved = $bs_waiting_shelved_obj->term_id;
$bs_waiting_rlo = $bs_waiting_rlo_obj->term_id;


if( $type == 'box_status' ) {

	//
	// New Requests
	//
	$new_agents_array = $_REQUEST['new_agents_array'];
	$item_ids = $_REQUEST['item_ids'];
	$is_single_item = (count($item_ids) > 1 ) ? false : true;
	$new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
	//$old_status = isset($_POST['old_status']) ? sanitize_text_field($_POST['old_status']) : '';	

	$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
	$data_update = array('box_status' => $new_status);

/*
		// Update Each Status in DB // Must happen after Audit log to maintain old data
	if( $is_single_item ) {
		$box_id = Patt_Custom_Func::get_box_file_details_by_id($item_ids[0])->Box_id_FK;	
		$data_where = array('id' => $box_id);
		$wpdb->update($table_name, $data_update, $data_where);
		
	} else {
		foreach( $item_ids as $id ) {
			$box_id = Patt_Custom_Func::get_box_file_details_by_id($id)->Box_id_FK;	
			$data_where = array('id' => $box_id);
			$wpdb->update($table_name, $data_update, $data_where);
		}
	}
*/
	

	
	// NEW
	// Compiles the object for insertion
	


	
	
	
	// DEBUG INFO
/*
	echo 'Test 1: '.PHP_EOL;
	print_r($test_item_1);	
	echo PHP_EOL.'Test 2: '.PHP_EOL;
	print_r($test_item_2);
	echo 'Type: '.$type.PHP_EOL;
	echo 'New Agents Array: '.PHP_EOL;
	print_r($new_agents_array);
	echo 'Items Array: '.PHP_EOL;
	print_r($item_ids);
	echo 'User Status Array: '.PHP_EOL;
	print_r($user_status_array);
	echo 'Data Array: '.PHP_EOL;
	print_r($data);
	echo 'New Data Array: '.PHP_EOL;
	print_r($data2);
	echo '$new_status: '.$new_status.PHP_EOL;
//	print_r($new_status);
//	echo 'Old: '.$old_assigned_agents_string;
//	echo 'New: '.$new_assigned_agents_string;	
	echo 'Combo: '.$recall_requestors_string.PHP_EOL;
	echo 'ticket id: '.$ticket_id.PHP_EOL;
	//print_r($old_recall_requestors);	
	//echo 'Requestor String: '.$recall_requestors_string.PHP_EOL;
// 	echo 'Requestor Value: '.$new_requestor_value.PHP_EOL;
*/
	
	// Update the Users associated with the Recall. 
/*
	$data = [
			'recall_id' => $recall_id,			
			'user_id' => $new_assigned_agents_array
		];
*/
	//Patt_Custom_Func::update_recall_user_by_id($data);
	
	
	//This will be it. 
// 	$update_status_by_id = Patt_Custom_Func::update_status_by_id($data2);
	
	//Update the Updated Date
// 	$current_datetime = date("yy-m-d H:i:s");
	$current_datetime = date("Y-m-d H:i:s");
	$update = [	'updated_date' => $current_datetime ];
	$where = [ 'id' => $recall_id ];
	//Patt_Custom_Func::update_recall_dates($update, $where);
	
	//
	// Audit Log 
	//
	
	// Register Box Status Taxonomy
	if( !taxonomy_exists('wpsc_box_statuses') ) {
		$args = array(
			'public' => false,
			'rewrite' => false
		);
		register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
	}
	
	foreach( $item_ids as $id ) {

		$where = ['box_folder_file_id' => $id ];
		$ticket_id = Patt_Custom_Func::get_ticket_id_from_box_folder_file( $where );
		$old_status_str = get_term_by( 'id', Patt_Custom_Func::get_box_file_details_by_id($id)->box_status, 'wpsc_box_statuses');
		$new_status_str = get_term_by( 'id', $new_status, 'wpsc_box_statuses');
		$status_str = $old_status_str->name.' to '.$new_status_str->name;

		do_action('wpppatt_after_box_status_update', $ticket_id['ticket_id'], $status_str, $id);
		
		// Debug
/*
		echo 'id: ';
		print_r($id);
		echo '<br>ticket_id: ';
		print_r($ticket_id['ticket_id']);
		echo '<br>status string: '.$status_str;
*/
	}


	// Update Each Status in DB
	if( $is_single_item ) {
		
		
		
		// Update previous box status
		$box_id = Patt_Custom_Func::get_box_file_details_by_id($item_ids[0])->Box_id_FK;	
		$old_status_obj = get_term_by( 'id', Patt_Custom_Func::get_box_file_details_by_id( $item_ids[0] )->box_status, 'wpsc_box_statuses');
		$old_status = $old_status_obj->term_id;
		
		// don't update box_previous_status if waiting on rlo or waiting shevled. 
		if( $old_status != $bs_waiting_shelved && $old_status != $bs_waiting_rlo ) {
  		$data_where = array( 'id' => $box_id );
  		$data_old_status = array( 'box_previous_status' => $old_status );
  		$wpdb->update( $table_name, $data_old_status, $data_where);
    }
		
		// Update Box status
		$box_id = Patt_Custom_Func::get_box_file_details_by_id($item_ids[0])->Box_id_FK;	
		$data_where = array('id' => $box_id);
		$wpdb->update($table_name, $data_update, $data_where);
		
	} else {
		foreach( $item_ids as $id ) {
			
			// Update previous box status
  		$box_id = Patt_Custom_Func::get_box_file_details_by_id( $id )->Box_id_FK;	
  		$old_status_obj = get_term_by( 'id', Patt_Custom_Func::get_box_file_details_by_id( $id )->box_status, 'wpsc_box_statuses');
  		$old_status = $old_status_obj->term_id;
  		
  		// don't update box_previous_status if waiting on rlo or waiting shevled. 
  		if( $old_status != $bs_waiting_shelved && $old_status != $bs_waiting_rlo ) {
    		$data_where = array( 'id' => $box_id );
    		$data_old_status = array( 'box_previous_status' => $old_status );
    		$wpdb->update( $table_name, $data_old_status, $data_where);
      }
			
			// Update Box status
			$box_id = Patt_Custom_Func::get_box_file_details_by_id( $id )->Box_id_FK;	
			$data_where = array('id' => $box_id);
			$wpdb->update($table_name, $data_update, $data_where);
		}
	}
	
} elseif( $type == 'cancel' ) {
	
	
	
} 


?>