<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

// EAST Cleanup

global $current_user, $wpscfunction, $wpdb;

// Find first available slot for requests with boxes equal to 1
$get_active_location = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
digitization_center = 2
");

foreach($get_active_location as $item) {

$shelf_id = $item->shelf_id;

[$aisle, $bay, $shelf] = explode("_", $shelf_id);

//echo $aisle.'-'.$bay.'-'.$shelf.' - ';

$position_details = $wpdb->get_results("
SELECT position FROM " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE aisle = '" . $aisle . "' 
AND bay = '" . $bay . "' 
AND shelf = '" . $shelf . "' 
AND digitization_center = 2
");

$position_count = count($position_details);

//echo $position_count.'<br />';

// Set Remaining to 0
if($position_count >= 4) {

$data_update = array('remaining' => 0);
$data_where = array('shelf_id' => $shelf_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

// Set Remaining to the position count
if($position_count > 0 && $position_count <= 3) {

$data_update = array('remaining' => $position_count);
$data_where = array('shelf_id' => $shelf_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

}


//Check for negative numbers
$get_negative_remaining = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE SIGN(remaining) = -1 AND
digitization_center = 2
");

foreach($get_negative_remaining as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('remaining' => 4);
$data_where = array('shelf_id' => $shelf_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where); 
}

//Check for numbers > 5
$get_positive_remaining = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining >= 5 AND
digitization_center = 2
");

foreach($get_positive_remaining as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('remaining' => 0);
$data_where = array('shelf_id' => $shelf_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where); 
}

//Check to make sure occupied is 1
$get_not_occupied = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining >= 0 AND
remaining <= 4 AND
digitization_center = 2
");

foreach($get_not_occupied as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('occupied' => 1);
$data_where = array('shelf_id' => $shelf_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where); 
}

//Check to make sure occupied is 0
$get_occupied = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining = 4 AND
digitization_center = 2
");

foreach($get_occupied as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('occupied' => 0);
$data_where = array('shelf_id' => $shelf_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where); 
}

//Get current storage_id's in box table and check storage location table

$get_storage_box_ids = $wpdb->get_results("
SELECT DISTINCT storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE id <> '-99999'
");

$get_storage_location_ids = $wpdb->get_results("
SELECT DISTINCT id
FROM " . $wpdb->prefix . "wpsc_epa_storage_location
WHERE id <> '-99999'
");

$get_storage_box_ids_array = array();
$get_storage_location_array = array();



foreach($get_storage_box_ids as $info) {
$storage_box_ids = $info->storage_location_id;
array_push($get_storage_box_ids_array, $storage_box_ids);
}



foreach($get_storage_location_ids as $info) {
$storage_location_ids = $info->id;
array_push($get_storage_location_array, $storage_location_ids);
}

$result = array_diff($get_storage_location_array,$get_storage_box_ids_array);

foreach($result as $diff) {
$wpdb->delete( $wpdb->prefix.'wpsc_epa_storage_location', array( 'id' => $diff) );
}
?>