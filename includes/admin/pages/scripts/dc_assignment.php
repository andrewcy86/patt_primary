<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $wpdb, $current_user, $wpscfunction;

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

//Grab ticket ID and Selected Digitization Center from Modal
	$tkid = $_POST['postvartktid'];
	$dc_final = $_POST['postvardcname'];

//Obtain Ticket Status
	$ticket_details = $wpdb->get_row("
SELECT ticket_status 
FROM " . $wpdb->prefix . "wpsc_ticket 
WHERE
id = '" . $tkid . "'
");

	$ticket_details_status = $ticket_details->ticket_status;

    $new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses');
    $tabled_request_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
    $complete_tag = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses');
    
    
if (($ticket_details_status == $new_request_tag->term_id || $ticket_details_status == $tabled_request_tag->term_id) && isset($_POST['postvartktid']) && isset($_POST['postvardcname']) && $_POST['postvardcname'] != '666') {

$find_boxes = $wpdb->get_results("
SELECT storage_location_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE ticket_id = '" . $tkid . "'
");

foreach ($find_boxes as $info) {
    $find_storage_location_id = $info->storage_location_id;
    $updatedc_table_name = $wpdb->prefix . 'wpsc_epa_storage_location';
	$updatedc_data_update = array(
	'digitization_center' => $dc_final
	);
	
	$updatedc_data_where = array('id' => $find_storage_location_id);

	$wpdb->update($updatedc_table_name, $updatedc_data_update, $updatedc_data_where);
}

} else {
	echo "No automatic box shelf assignments made.";
}
