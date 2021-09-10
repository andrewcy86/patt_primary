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
	$ticket_details = $wpdb->get_row("
SELECT ticket_status 
FROM " . $wpdb->prefix . "wpsc_ticket 
WHERE
id = '" . $tkid . "'
");
	$ticket_details_status = $ticket_details->ticket_status;
    
if (($ticket_details_status == $request_shipped_tag->term_id || $ticket_details_status == $request_received_tag->term_id || $ticket_details_status == $request_new_request_tag->term_id || $ticket_details_status == $request_tabled_tag->term_id || $ticket_details_status == $request_initial_review_complete_tag->term_id) && isset($_POST['postvartktid']) && isset($_POST['postvardcname']) && $_POST['postvardcname'] != $dc_not_assigned_tag->term_id) {

//Call Auto Assignment Function
Patt_Custom_Func::auto_location_assignment($tkid,$dc_final,$destruction_flag,$destruction_boxes);

//echo $tkid.','.$dc_final.','.$destruction_flag.','.$destruction_boxes;
	
} else {
	echo "No automatic box shelf assignments made.";
}
