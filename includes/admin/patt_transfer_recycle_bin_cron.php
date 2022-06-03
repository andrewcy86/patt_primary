<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;



//BEGIN Deleting DATA FROM ARCHIVE
$get_related_patt_transfer_folderdocinfo = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "epa_patt_arms_logs WHERE status = 'Published'");

foreach($get_related_patt_transfer_folderdocinfo as $item) {
    // $ticket_id = $item->ticket_id;
    // $request_id = $item->request_id;
    // $ticket_status = $item->ticket_status;
    // $date_updated = $item->date_updated;
    // $ticket_meta_key = $item->meta_key;
    // $ticket_meta_value = $item->meta_value;
    // //echo $ticket_id;
    // $recall_decline = 0;
    // $status = 0;

    $folderdocinfodoc_id = $item->folderdocinfofile_id;
 

        //BEGIN CLONING DATA TO ARCHIVE
        Patt_Custom_Func::send_to_patt_transfer_archive($folderdocinfodoc_id);
        
        //Archive audit log
        // Patt_Custom_Func::audit_log_backup($ticket_id);

        
        //Send notification to Admins/Managers/Requester when a request is archived
        /*
        $get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
        $get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');
        
        $user_id_array = [$get_user_id->ID];
        $convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
        $patt_agent_id = implode($convert_patt_id);
        $pattagentid_array = [$patt_agent_id];
        */
        
        $agent_admin_group_name = 'Administrator';
        $pattagentid_admin_array = Patt_Custom_Func::agent_from_group($agent_admin_group_name);
        $agent_manager_group_name = 'Manager';
        $pattagentid_manager_array = Patt_Custom_Func::agent_from_group($agent_manager_group_name);
        $pattagentid_array = array_merge($pattagentid_admin_array,$pattagentid_manager_array);
        $data = [];
        
        //$email = 1;
        Patt_Custom_Func::insert_new_notification('email-request-deleted',$pattagentid_array,$request_id,$data,$email);
 
    // }
}


?>