<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $wpdb, $current_user, $wpscfunction;

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );
include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

//Grab ticket ID and Selected Digitization Center from Modal
	$tkid = $_POST['postvartktid'];
	$dc_final = $_POST['postvardcname'];
	$destruction_flag = 0;
    $destruction_boxes = '';
//Obtain Ticket Status
/*	$ticket_details = $wpdb->get_row("
SELECT ticket_status 
FROM " . $wpdb->prefix . "wpsc_ticket 
WHERE
id = '" . $tkid . "'
");
	$ticket_details_status = $ticket_details->ticket_status;
	($ticket_details_status == $request_shipped_tag->term_id || $ticket_details_status == $request_received_tag->term_id || $ticket_details_status == $request_new_request_tag->term_id || $ticket_details_status == $request_tabled_tag->term_id || $ticket_details_status == $request_initial_review_complete_tag->term_id) && 
*/    

//Create SEMS placeholder

$sems_check = $wpscfunction->get_ticket_meta($tkid,'super_fund');
                
if(in_array("true", $sems_check)) {		

//Check to see if request has previously been in the initial review complete status
$get_inital_review_complete_check = $wpdb->get_row("SELECT count(id) as count
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_request
WHERE request_id = '".$tkid."' and type = 'Initial SEMS API Call Complete' ");

$get_initial_review_complete_check_count = $get_inital_review_complete_check->count;
  
  if($get_initial_review_complete_check_count == 0) {
	do_action( 'wppatt_sems_instant', $tkid );
    		
    $get_date_timestamp = $wpdb->get_row("SELECT date_created, customer_name FROM ".$wpdb->prefix."wpsc_ticket
			WHERE id = ".$tkid);
			
	$date_timestamp = $get_date_timestamp->date_created;
    $customer_name = $get_date_timestamp->customer_name;
			
    $table_timestamp_request = $wpdb->prefix . 'wpsc_epa_timestamps_request';
            
    $wpdb->insert($table_timestamp_request, array('request_id' => $tkid, 'type' => 'Initial SEMS API Call Complete', 'user' => $customer_name, 'timestamp' => $date_timestamp) );
  }
  
}

if (isset($_POST['postvartktid']) && isset($_POST['postvardcname']) && $_POST['postvardcname'] != $dc_not_assigned_tag->term_id) {

//Call Auto Assignment Function
/*
$obtain_box_ids_details = $wpdb->get_results("
        SELECT a.storage_location_id
        FROM ".$wpdb->prefix."wpsc_epa_boxinfo a
        INNER JOIN ".$wpdb->prefix."wpsc_epa_storage_location b ON a.storage_location_id = b.id 
        WHERE
        b.aisle = 0 AND 
        b.bay = 0 AND 
        b.shelf = 0 AND 
        b.position = 0 AND
        a.location_status_id <> 6 AND
        b.digitization_center = '" . $dc_final . "' AND
        a.ticket_id = '" . $tkid . "'
        ");

        $box_id_array = array();
        foreach ($obtain_box_ids_details as $box_id_val) {
        $box_id_array_val = $box_id_val->storage_location_id;
        array_push($box_id_array, $box_id_array_val);
        }
        
        $destruction_boxes = implode(',', $box_id_array);
*/
//include_once( WPPATT_ABSPATH . 'includes/admin/e_location_assignment_cleanup_cron.php' );
//include_once( WPPATT_ABSPATH . 'includes/admin/w_location_assignment_cleanup_cron.php' );


Patt_Custom_Func::auto_location_assignment($tkid,$dc_final,$destruction_flag,$destruction_boxes);

//echo $tkid.','.$dc_final.','.$destruction_flag.','.$destruction_boxes;

} else {
	echo "No automatic box shelf assignments made.";
}
