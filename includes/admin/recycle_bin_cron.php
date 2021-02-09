<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php');
*/

global $current_user, $wpscfunction, $wpdb;

//Move a request to the recycle bin when all of the boxes are in the Completed status

//get all active requests
$get_total_request_count = $wpdb->get_results("SELECT id as ticket_id
FROM " . $wpdb->prefix . "wpsc_ticket
WHERE active = 1 AND id <> -99999");

foreach($get_total_request_count as $item) {
    $ticket_id = $item->ticket_id;
    
    //Send all Cancelled requests to the Archive
    $cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses');
    $cancelled_term_id = $cancelled_tag->term_id;
    
    $get_cancelled_request = $wpdb->get_row("SELECT COUNT(id) as cancelled_count
    FROM " . $wpdb->prefix . "wpsc_ticket a
    WHERE ticket_status = ".$cancelled_term_id." AND id =  '" . $ticket_id . "'");
    $total_cancelled_requests = $get_cancelled_request->cancelled_count;
    
    //Send all Completed/Dispositioned requests to the Archive
    $ecms_tag = get_term_by('slug', 'ecms', 'wpsc_statuses');
    $ecms_term_id = $ecms_tag->term_id;
    
    $completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses');
    $completed_dispositioned_term_id = $completed_dispositioned_tag->term_id;
    
    $get_ecms_request = $wpdb->get_row("SELECT COUNT(id) as ecms
    FROM " . $wpdb->prefix . "wpsc_ticket a
    WHERE ticket_status = ".$ecms_term_id." AND id =  '" . $ticket_id . "'");
    $total_ecms_requests = $get_ecms_request->ecms;
    
    if($total_ecms_requests > 0 || $total_cancelled_requests > 0) {
        if($total_ecms_requests > 0) {
            $data_update = array('ticket_status' => $completed_dispositioned_term_id);
            $data_where = array('id' => $ticket_id);
            $wpdb->update('wpqa_wpsc_ticket' , $data_update, $data_where);
        }
        $data_update = array('active' => 0);
        $data_where = array('id' => $ticket_id);
        $wpdb->update('wpqa_wpsc_ticket' , $data_update, $data_where);

        //BEGIN CLONING DATA TO ARCHIVE
        Patt_Custom_Func::send_to_archive($ticket_id);
    }
    
    /*$get_total_box_count = $wpdb->get_row("SELECT COUNT(box_id) as total_box_count
    FROM wpqa_wpsc_epa_boxinfo
    WHERE ticket_id = '" . $ticket_id . "'");
    $total_box_count = $get_total_box_count->total_box_count;
    
    $completed_tag = get_term_by('slug', 'completed', 'wpsc_box_statuses');
    $completed_term_id = $completed_tag->term_id;
    
    $get_completed_box_status_count = $wpdb->get_row("SELECT COUNT(a.box_id) as completed_box_count
    FROM wpqa_wpsc_epa_boxinfo a
    INNER JOIN wpqa_wpsc_ticket b ON b.id = a.ticket_id
    WHERE a.box_status = ".$completed_term_id." AND b.id = '" . $ticket_id . "'");
    $completed_box_status_count = $get_completed_box_status_count->completed_box_count;
        
        //begin move of requests to recycle bin when all box statuses are in Completed or request is Cancelled
        if( ($completed_box_status_count != 0 && $total_box_count == $completed_box_status_count) || ($total_cancelled_requests > 0) ) {
            $data_update = array('active' => 0);
            $data_where = array('id' => $ticket_id);
            $wpdb->update('wpqa_wpsc_ticket' , $data_update, $data_where);
        }
    */
}

/*
//get all active requests
Handled currently in private message cleanup cron
$get_unwanted_pm = $wpdb->get_results("SELECT DISTINCT pm_id FROM " . $wpdb->prefix . "pm_users WHERE deleted = '2'");

//Empty deleted private messages
foreach($get_unwanted_pm as $item) {
    $pm_id = $item->pm_id;

    $table_pm = $wpdb->prefix . 'pm';
    $wpdb->delete( $table_pm, array( 'id' => $pm_id ) );

    $table_pm_users = $wpdb->prefix . 'pm_users';
    $wpdb->delete( $table_pm_users, array( 'pm_id' => $pm_id ) );
}
*/

?>