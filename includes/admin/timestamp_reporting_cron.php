<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $current_user, $wpscfunction, $wpdb;

$timestamp_table = $wpdb->prefix . 'wpsc_epa_timestamps_request';
$timestamp_table_box = $wpdb->prefix . 'wpsc_epa_timestamps_box';
$timestamp_table_file = $wpdb->prefix . 'wpsc_epa_timestamps_folderfile';

$timestamp_requests_array = array();
$requests_array = array();

$get_timestamp_requests = $wpdb->get_results("
SELECT request_id
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_request
");

foreach($get_timestamp_requests as $item) {
$timestamp_request_id = $item->request_id;
array_push($timestamp_requests_array,$timestamp_request_id);
}

$get_requests = $wpdb->get_results("
SELECT id
FROM " . $wpdb->prefix . "wpsc_ticket WHERE id <> '-99999'
");

foreach($get_requests as $item) {
$request_id = $item->id;
array_push($requests_array,$request_id);
}

$result = array_diff($timestamp_requests_array,$requests_array);

//print_r($request);

// Find missing data from timestamps request table
$get_digitization_center_blanks = $wpdb->get_results("
SELECT request_id
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_request
WHERE
digitization_center = ''
");

foreach($get_digitization_center_blanks as $item) {

$patt_request_id = $item->request_id;

$get_boxes = $wpdb->get_results("
SELECT storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE
ticket_id = '" . $patt_request_id . "'"
);


$digitization_center_array = array();

//Get Boxes
foreach($get_boxes as $item) {
$storage_location_id = $item->storage_location_id;

$get_digitization_centers = $wpdb->get_row("
SELECT digitization_center
FROM " . $wpdb->prefix . "wpsc_epa_storage_location
WHERE
id  = '" . $storage_location_id . "'"
);
$digitization_center = $get_digitization_centers->digitization_center;

array_push($digitization_center_array, $digitization_center);

}

$dc_check = '';

if( count(array_unique($digitization_center_array)) == 1 && in_array('62', $digitization_center_array )) {
$dc_check = 'East';
}

if( count(array_unique($digitization_center_array)) == 1 && in_array('2', $digitization_center_array )) {
$dc_check = 'West';
}

if(count(array_unique($digitization_center_array)) == 1 && in_array('666', $digitization_center_array )) {
$dc_check = 'Not assigned';
}

if( count(array_unique($digitization_center_array)) == 2 && in_array('62', $digitization_center_array ) && in_array('2', $digitization_center_array )) {
$dc_check = 'Both';
}

if( count(array_unique($digitization_center_array)) == 2 && in_array('62', $digitization_center_array ) && in_array('666', $digitization_center_array )) {
$dc_check = 'East/Not Assigned';
}

if( count(array_unique($digitization_center_array)) == 2 && in_array('666', $digitization_center_array ) && in_array('2', $digitization_center_array )) {
$dc_check = 'West/Not Assigned';
}

if( count(array_unique($digitization_center_array)) == 3 && in_array('666', $digitization_center_array ) && in_array('2', $digitization_center_array ) && in_array('62', $digitization_center_array )) {
$dc_check = 'Both/Not Assigned';
}

//echo $item_request_id.' - '.$dc_check.'<br />';

$update_dc = array('digitization_center' => $dc_check);
$where_dc = array('request_id' => $patt_request_id);
$wpdb->update($timestamp_table , $update_dc, $where_dc);


}

//START DIGITIZATION CENTER BOX UPDATE
$get_boxes = $wpdb->get_results("SELECT a.box_id as box_id, c.digitization_center as digitization_center
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_box a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.id = a.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location c ON c.id = b.storage_location_id");

foreach($get_boxes as $item) {
    $box_id = $item->box_id;
    $dc = $item->digitization_center;
    
    if($dc == 666) {
        $dc = 'Not Assigned';
    }
    if($dc == 62) {
        $dc = 'East';
    }
    if($dc == 2) {
        $dc = 'West';
    }
    
    $update_dc = array('digitization_center' => $dc);
    $where_dc = array('box_id' => $box_id);
    $wpdb->update($timestamp_table_box, $update_dc, $where_dc);
}


//START DIGITIZATION CENTER FOLDER FILE UPDATE
$get_folderfile = $wpdb->get_results("SELECT a.folderdocinfofile_id as folderdocinfofile_id, c.digitization_center as digitization_center
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_folderfile a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files d ON d.id = a.folderdocinfofile_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.id = d.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location c ON c.id = b.storage_location_id");

foreach($get_folderfile as $item) {
    $folderfile_id = $item->folderdocinfofile_id;
    $dc = $item->digitization_center;
    
    if($dc == 666) {
        $dc = 'Not Assigned';
    }
    if($dc == 62) {
        $dc = 'East';
    }
    if($dc == 2) {
        $dc = 'West';
    }
    
    $update_dc = array('digitization_center' => $dc);
    $where_dc = array('folderdocinfofile_id' => $folderfile_id);
    $wpdb->update($timestamp_table_file, $update_dc, $where_dc);
}

?>