<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;



// BEGIN Deleting DATA FROM Ticket List
// Improved SQL query that includes tickets that have box_list_post_id that doesn't start with a number or are empty
// Use this query to test that the 2nd batch of deletions are being logged by the activity log plugin or added to the log table in db
$get_ticketmeta = $wpdb->get_results("SELECT DISTINCT ticket_id, request_id FROM wpqa_wpsc_ticketmeta 
									INNER JOIN wpqa_wpsc_ticket
									ON wpqa_wpsc_ticket.id = wpqa_wpsc_ticketmeta.ticket_id
									WHERE ticket_id NOT IN 
									(SELECT ticket_id FROM wpqa_wpsc_ticketmeta WHERE META_KEY = 'box_list_post_id' AND meta_value REGEXP'^[0-9]*$' AND meta_value <> '' )");

foreach($get_ticketmeta as $item) {
    $ticketmeta_ticket_id = $item->ticket_id;
    $request_id = $item->request_id;
    // $ticket_status = $item->ticket_status;
    // $date_updated = $item->date_updated;
    // $ticket_meta_key = $item->meta_key;
    // $ticket_meta_value = $item->meta_value;
    // //echo $ticket_id;
    // $recall_decline = 0;
    // $status = 0;

    //var_dump($ticketmeta_ticket_id);

     // Connect to the wpqa_epa_error_log
     $table_epa_error_log = $wpdb->prefix."epa_error_log";
     $wpdb->insert($table_epa_error_log, array(
         'Status_Code' => 500,
         'Error_Message' => 'A critical error has occurred during the new ticket creation process for ticket ID ' . $ticketmeta_ticket_id,
         'Service_Type' => 'Delete',
         //'Timestamp' => date("m-d-Y h:i:s A", time())
     ));


    //delete tickets without a box_list_post_id from the tickets, ticketmeta, and boxinfo tables
    //based on the ticket_id
    //$wpdb->query( "DELETE FROM " . $wpdb->prefix . "wpsc_ticket WHERE id IN($ids)" );
    $wpdb->delete( $wpdb->prefix . 'wpsc_ticket', array( 'id' => $ticketmeta_ticket_id ) );
    $wpdb->delete( $wpdb->prefix . 'wpsc_ticketmeta', array( 'ticket_id' => $ticketmeta_ticket_id ) );
    $wpdb->delete( $wpdb->prefix . 'wpsc_epa_boxinfo', array( 'ticket_id' => $ticketmeta_ticket_id ) );


    
 

        
    //Send notification to Admins/Managers/Requester when a request is archived
    
    // $get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticketmeta_ticket_id . '"');
    // $get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');
    
    // $user_id_array = [$get_user_id->ID];
    // $convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
    // $patt_agent_id = implode($convert_patt_id);
    // $pattagentid_array = [$patt_agent_id];
    
    
    $agent_admin_group_name = 'Administrator';
    $pattagentid_admin_array = Patt_Custom_Func::agent_from_group($agent_admin_group_name);
    $agent_manager_group_name = 'Manager';
    $pattagentid_manager_array = Patt_Custom_Func::agent_from_group($agent_manager_group_name);
    $pattagentid_array = array_merge($pattagentid_admin_array,$pattagentid_manager_array);
    $data = [];
    
    $email = 1;
    Patt_Custom_Func::insert_new_notification('duplicate-ticket-deleted',$pattagentid_array,$request_id,$data,$email);
 
}



?>