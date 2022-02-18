<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

$tmp_user_id_arr = array();

if( $type == 'set_requestor_group' ) {
		   
$new_requestor_group_array = $_REQUEST['new_requestor_group_array'];

		foreach( $new_requestor_group_array as $status_users ) {			
		    
// Query table and remove all user_requestor_group assignments 
$get_user_requestor_group_meta = $wpdb->get_results("
SELECT user_id
FROM " . $wpdb->prefix . "usermeta
WHERE meta_key = 'user_requestor_group' AND meta_value = '".$status_users['org']."'
");

foreach($get_user_requestor_group_meta as $user_id) {
delete_user_meta($user_id->user_id, 'user_requestor_group');
}

			if (!empty($status_users['users'])) {
			   
	            foreach($status_users['users'] as $agent_id) {
	                $wp_user_id = Patt_Custom_Func::translate_user_id(array($agent_id),'wp_user_id');
                    update_user_meta($wp_user_id[0], 'user_requestor_group', $status_users['org'] );
	                array_push($tmp_user_id_arr, $wp_user_id[0]);
            }
		}
}

//print_r($tmp_user_id_arr);

$arr_unique = array_unique($tmp_user_id_arr);
$check = count($tmp_user_id_arr) !== count($arr_unique);
$message = 'Requester assignments made.';
$arr_duplicates = [];
if($check == 1) {
    $message = "The following users cannot be assigned to multiple AA'Ships:\n";
    $arr_duplicates = array_diff_assoc($tmp_user_id_arr, $arr_unique);

    foreach($arr_duplicates as $dup_user_id) {
	  $user_info = get_userdata($dup_user_id);
      $message .= 'User: ' . $user_info->display_name . "\n";
    }
            
}

echo $message;

}

// Start reassign all user tickets 
if( $type == 'set_reassign_user' ) {
    $prev_user = $_REQUEST['prev_user'];
    $new_user = $_REQUEST['new_user'];

    $message = "All requests from " . $prev_user . " have moved to " . $new_user . ". ";

    // Query table for user display name info
    $get_prev_user_email = $wpdb->get_results("
    SELECT user_email, display_name
    FROM " . $wpdb->prefix . "users
    WHERE display_name = '".$prev_user."'
    ");

    $get_new_user_email = $wpdb->get_results("
    SELECT user_email, display_name
    FROM " . $wpdb->prefix . "users
    WHERE display_name = '".$new_user."'
    ");


    // var_dump($get_prev_user_email[0]->display_name); die();

    $new_assigned_user_email = $get_new_user_email[0]->user_email;
    $prev_assigned_user_email = $get_prev_user_email[0]->user_email;
    

      $values = array(
         'customer_name'=> $new_user,
         'customer_email' => $new_assigned_user_email
      );
      

      if($get_prev_user_email[0]->display_name == null) {
        // var_dump($get_prev_user_email[0]->display_name); die();
        $message = "Error: " . $prev_user . " does not exist.";
      }
      else if($get_new_user_email[0]->display_name == null) {
        $message = "Error: " . $new_user . " does not exist.";
      } 
      else if($wpdb->update($wpdb->prefix.'wpsc_ticket', $values, array('customer_name'=>$prev_user)) == false) {
        $message = "Error: " . $prev_user  . " has submitted no requests.";
      }    
       else {
        $wpdb->update($wpdb->prefix.'wpsc_ticket', $values, array('customer_name'=>$prev_user)); 
      }

      echo($message);
}
// End reassign all user tickets 

// Start reassign users to tickets by date
if( $type == 'set_reassign_user_by_date' ) {
  $prev_user = $_REQUEST['prev_user'];
  $new_user = $_REQUEST['new_user'];
  $start_date = $_REQUEST['start_date'];
  $end_date = $_REQUEST['end_date'];

  $start_date_formatted = date_create($start_date);
  $end_date_formatted = date_create($end_date);

  $start_date_formatted = date_format($start_date_formatted, 'm-d-Y');
  $end_date_formatted = date_format($end_date_formatted, 'm-d-Y');

  $message = "Success: Requests between " . $start_date_formatted .  " to " . $end_date_formatted . " have moved from " . $prev_user . " to " . $new_user . ".";

  // Query table for user display name info
  $get_prev_user_email = $wpdb->get_results("
    SELECT user_email, display_name
    FROM " . $wpdb->prefix . "users
    WHERE display_name = '".$prev_user."'
    ");

  $get_new_user_email = $wpdb->get_results("
    SELECT user_email, display_name
    FROM " . $wpdb->prefix . "users
    WHERE display_name = '".$new_user."'
    ");

  $new_assigned_user_email = $get_new_user_email[0]->user_email;
  $prev_assigned_user_email = $get_prev_user_email[0]->user_email;

  // Query table for user ticket info
  $get_prev_user_ticket_info = $wpdb->get_results("
  SELECT wpqa_users.display_name, wpqa_wpsc_ticket.customer_email, wpqa_wpsc_ticket.date_created
  FROM wpqa_wpsc_ticket
  INNER JOIN wpqa_users ON wpqa_wpsc_ticket.customer_name=wpqa_users.display_name 
  WHERE wpqa_users.display_name='".$prev_user."';
  ");

  
  // $ticket_dates_array = array();
  // var_dump($date_formatted); die();

  $values = array(
    'customer_name'=> $new_user,
    'customer_email' => $new_assigned_user_email
 );


  if($get_prev_user_email[0]->display_name == null) {
    // var_dump($get_prev_user_email[0]->display_name); die();
    $message = "Error: " . $prev_user . " does not exist.";
  }
  else if($get_new_user_email[0]->display_name == null) {
    // var_dump($get_new_user_email); die();
    $message = "Error: " . $new_user . " does not exist.";
  } 
  else if($get_prev_user_ticket_info != null){
    foreach($get_prev_user_ticket_info as $ticket_info) {
      // var_dump($ticket_info->date_created);

      $date = date_create($ticket_info->date_created);

      $date_formatted = date_format($date, 'Y-m-d');

      
        if($date_formatted >= $start_date &&  $date_formatted <= $end_date){
          $wpdb->update($wpdb->prefix.'wpsc_ticket', $values, array('date_created'=>$ticket_info->date_created));
        }
      
    }
  } else {
    $message = "Error: " . $prev_user  . " has submitted no requests.";
  }

  echo $message;
}
// End reassign users to tickets by date
?>