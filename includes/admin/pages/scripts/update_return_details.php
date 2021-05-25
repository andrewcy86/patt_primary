<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$return_id = isset($_POST['return_id']) ? sanitize_text_field($_POST['return_id']) : '';
$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
//$title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
//$recall_ids = $_REQUEST['recall_ids']; 
//$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '';
//$recall_ids = json_decode($recall_ids);
//$num_of_recalls = count($recall_ids);

//Get term_ids for Decline status slugs
$status_decline_initiated_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-initiated' );
$status_decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' );


if( $type == 'cancel' ) {
	
	//echo '!Recall ID: '.$recall_id.PHP_EOL;
	//echo 'POST recall id: '.$_POST['recall_id'].PHP_EOL;
	//echo 'Type: '.$type.PHP_EOL;
	//echo 'Recall status before: '.$recall_obj->recall_status_id.PHP_EOL;
	//print_r($recall_array);
	
	$where = [
		'return_id' => $return_id
	];
	$return_array = Patt_Custom_Func::get_return_data($where);
	
	//Added for servers running < PHP 7.3
	if (!function_exists('array_key_first')) {
	    function array_key_first(array $arr) {
	        foreach($arr as $key => $unused) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$return_array_key = array_key_first($return_array);	
	$return_obj = $return_array[$return_array_key];
	
	echo 'Decline Object: ';
	print_r($return_obj);
	
	

	// Only cancel if Return is in status: Return Initiated
// 	if ( $return_obj->return_status_id == 752 ) {
	if ( $return_obj->return_status_id == $status_decline_initiated_term_id ) {	
		
		// Update Return status(es)
		$data_status = [ 'return_status_id' => $status_decline_cancelled_term_id ]; //change status to Cancelled old 785, now 791
		$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
		$obj = $obj[0];
		echo 'THE OBJ: ';
		print_r($obj);
		
		//
		// Set Box status back to original status before Decline
		//  
		
		foreach( $return_obj->box_id as $key => $box_id ) {
			
			$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
			$data_where = array( 'box_id' => $box_id );
			$data_update = array( 'box_status' => $return_obj->saved_box_status[$key] );
			$wpdb->update( $table_name, $data_update, $data_where );
				
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
		

		
		
		// Get array of items to get ticket_id for Audit Log
		$box_list = ($obj->box_id) ? $obj->box_id : []; 
		$folderfile_list = ($obj->folderdoc_id) ? $obj->folderdoc_id : [];
		
		// Create single array with box and folderdocs
		$collated_box_folderfile_list = [];
		
		foreach( $box_list as $key=>$box ) {
			if( $box != $db_null ) {
				$collated_box_folderfile_list[] = $box;
			} else {
				$collated_box_folderfile_list[] = $folderfile_list[$key];
			}
		}
		
		foreach( $collated_box_folderfile_list as $item ) {
			
			$where = ['box_folder_file_id' => $item ];
			$ticket_array = Patt_Custom_Func::get_ticket_id_from_box_folder_file($where);
			
			echo ' where: ';
			print_r($where);
			echo ' ticket array: ';
			print_r($ticket_array);
			
			// Audit Log
			do_action('wpppatt_after_return_cancelled', $ticket_array['ticket_id'], 'D-'.$return_id);
			
			// Set PM Notifications 
			$notifications = '';
			$notification_post = 'email-decline-id-has-been-cancelled';
			
			// Get owner of the box
			$where = [
				'ticket_id' => $ticket_array['ticket_id']
			];
			$ticket_owner_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
			
			// Get people on Return (Decline)
			$where = [
				'return_id' => $return_id
			];
			$return_data = Patt_Custom_Func::get_return_data( $where );

			$agent_id_array = Patt_Custom_Func::translate_user_id( $return_data[0]->user_id, 'agent_term_id' );;
			
			// Merge the 2 arrays, and remove any duplicates
			$pattagentid_array = array_unique(array_merge( $agent_id_array, $ticket_owner_id_array ));
			

			$requestid = 'D-'.$return_id; 			
			$data = [
// 		        'item_id' => $item_ids,
		        'action_initiated_by' => $current_user->display_name
		    ];
			$email = 1;
			
			$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
			
		} //foreach
	} // if return initiated
} // if cancel

if( $type == 'extend_expiration' ) {
	
	$four_weeks_ahead = Date('Y-m-d', strtotime('30 days'));
	
	// Update Decline DB expiration_date
	$where = [ 'id' => $return_id ];
	$data = [ 'expiration_date' => $four_weeks_ahead ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	// Audit Log
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	do_action('wpppatt_after_return_expiration_date_extended', $ticket_id, 'R-'.$recall_id, $returned_date_string);
	
	// PM Notification
	$notification_post = 'email-decline-extended';
	
	// Get owner of the box
	$where = [
		'ticket_id' => $ticket_id
	];
	$ticket_owner_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get people on Return (Decline)
	$where = [
		'return_id' => $return_id
	];
	$return_data = Patt_Custom_Func::get_return_data( $where );

	$agent_id_array = Patt_Custom_Func::translate_user_id( $return_data[0]->user_id, 'agent_term_id' );;
	
	// Merge the 2 arrays, and remove any duplicates
	$pattagentid_array = array_unique(array_merge( $agent_id_array, $ticket_owner_id_array ));
	

	$requestid = 'D-'.$return_id; 			
	$data = [
        //'action_initiated_by' => $current_user->display_name
    ];
	$email = 1;
	
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );

	

	
	
}


?>