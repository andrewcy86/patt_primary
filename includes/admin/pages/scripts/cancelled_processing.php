<?php

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $wpdb, $current_user, $wpscfunction;

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

//Grab ticket ID and Selected Digitization Center from Modal
	$ticket_id = $_POST['postvartktid'];
	$tkcomment = $_POST['postvarcomment'];
	$dc_final = $_POST['postvardcname'];
	
//Get current ticket status
$ticket              = $wpscfunction->get_ticket($ticket_id);
$status_id           = $ticket['ticket_status']; 

if (isset($_POST['postvartktid'])) {

                

$get_box_ids = $wpdb->get_results("SELECT id, storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE ticket_id = '".$ticket_id."'");
                   
foreach($get_box_ids as $item) {
    $data_update_box_status = array('box_status' => $box_cancelled_tag->term_id);
    $data_where_box_status = array('id' => $item->id);
    $wpdb->update($wpdb->prefix . 'wpsc_epa_boxinfo', $data_update_box_status, $data_where_box_status);

    
}

echo 'Cancelled Success.';
//print_r($rejected_timestamp_check);
} else {
	echo "Issue with cancelled update";
}
