<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php');
*/

global $current_user, $wpscfunction, $wpdb;

//Set a request to the ECMS status when all boxes in a request are in the Ingestion status

//get all active requests
$get_total_request_count = $wpdb->get_results("SELECT id as ticket_id
FROM " . $wpdb->prefix . "wpsc_ticket
WHERE active = 1 AND id <> -99999");

foreach($get_total_request_count as $item) {
    $ticket_id = $item->ticket_id;
    
    $get_total_box_count = $wpdb->get_row("SELECT COUNT(box_id) as total_box_count
    FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
    WHERE ticket_id = '" . $ticket_id . "'");
    $total_box_count = $get_total_box_count->total_box_count;
  
    $ingestion_tag = get_term_by('slug', 'ingestion', 'wpsc_box_statuses');
    $ingestion_term_id = $ingestion_tag->term_id;
    
    $get_ingestion_box_status_count = $wpdb->get_row("SELECT COUNT(a.box_id) as ingestion_box_count
    FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
    INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON b.id = a.ticket_id
    WHERE a.box_status = ".$ingestion_term_id." AND b.id = '" . $ticket_id . "'");
    $ingestion_box_status_count = $get_ingestion_box_status_count->ingestion_box_count;
        
    if($ingestion_box_status_count != 0 && $total_box_count == $ingestion_box_status_count) {
        /*
        $ecms_term_id = $ecms_tag->term_id;
        $data_update = array('ticket_status' => $ecms_term_id);
        $data_where = array('id' => $ticket_id);
        $wpdb->update('wpqa_wpsc_ticket' , $data_update, $data_where);*/
        $ecms_tag = get_term_by('slug', 'ecms', 'wpsc_statuses');
        $ecms_term_id = $ecms_tag->term_id;

        $wpscfunction->change_status( $ticket_id, $ecms_term_id);
    }
}

?>