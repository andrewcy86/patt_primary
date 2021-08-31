<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $wpdb, $current_user, $wpscfunction;

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

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

    $new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses');
    $initial_review_complete_tag = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses');
    $tabled_request_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
    $complete_tag = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses');
    $shipped_tag = get_term_by('slug', 'awaiting-agent-reply', 'wpsc_statuses');
    $received_tag = get_term_by('slug', 'received', 'wpsc_statuses');
    
if (($ticket_details_status == $shipped_tag->term_id || $ticket_details_status == $received_tag->term_id || $ticket_details_status == $new_request_tag->term_id || $ticket_details_status == $tabled_request_tag->term_id || $ticket_details_status == $complete_tag->term_id) && isset($_POST['postvartktid']) && isset($_POST['postvardcname']) && $_POST['postvardcname'] != '666') {

//Call Auto Assignment Function
Patt_Custom_Func::auto_location_assignment($tkid,$dc_final,$destruction_flag,$destruction_boxes);

//echo $tkid.','.$dc_final.','.$destruction_flag.','.$destruction_boxes;
	
} else {
	echo "No automatic box shelf assignments made.";
}
