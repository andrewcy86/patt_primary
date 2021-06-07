<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/wp-async-request.php';
require_once __DIR__ . '/wp-background-process.php';

if ( ! class_exists( 'WP_LOC_Request' ) ) :

class WP_LOC_Request extends WP_Async_Request {

	/**
	 * @var string
	 */
	protected $action = 'loc_request';

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {

global $current_user, $wpscfunction,$wpdb;
	
$dc_id = $_POST['dc_id'];

// ONLY supporting EAST and WEST DC's at the moment

if ( $dc_id == 2 || $dc_id == 62) {

// Find first available slot for requests with boxes equal to 1
$get_active_location = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
remaining NOT IN (0,4) AND 
digitization_center = ".$dc_id);


foreach($get_active_location as $item) {

$shelf_id = $item->shelf_id;

[$aisle, $bay, $shelf] = explode("_", $shelf_id);

//echo $aisle.'-'.$bay.'-'.$shelf.' - ';

$position_details = $wpdb->get_results("
SELECT position FROM " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE aisle = '" . $aisle . "' 
AND bay = '" . $bay . "' 
AND shelf = '" . $shelf . "' 
AND digitization_center = ".$dc_id);

$position_count = count($position_details);

//echo $position_count.'<br />';

// Set Remaining to the position count unassigned
if($position_count == 999) {

$data_update = array('remaining' => 4-$position_count);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

// Set Remaining to the position count
if($position_count > 0 && $position_count <= 3) {

$data_update = array('remaining' => 4-$position_count);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

// Set Remaining to 4
if($position_count == 0) {

$data_update = array('remaining' => 4, 'occupied' => 0);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

// Set Remaining to 0
if($position_count >= 4) {

$data_update = array('remaining' => 0, 'occupied' => 1);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

}

// Ensure shelf not marked as occupied inadvertantly
$get_remaining_occupied = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining = 0 AND
occupied = 1 AND
digitization_center = ".$dc_id);

foreach($get_remaining_occupied as $item) {
$shelf_id = $item->shelf_id;

[$aisle, $bay, $shelf] = explode("_", $shelf_id);

//echo $aisle.'-'.$bay.'-'.$shelf.' - ';

$position_details = $wpdb->get_results("
SELECT position FROM " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE aisle = '" . $aisle . "' 
AND bay = '" . $bay . "' 
AND shelf = '" . $shelf . "' 
AND digitization_center = ".$dc_id);

$position_count = count($position_details);

$data_update = array('remaining' => 4-$position_count);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}


// Ensure shelf not marked as occupied inadvertantly continued

$begin_seq = Patt_Custom_Func::begin_sequence($dc_id);

// Finds Shelf ID of next available sequence
	$find_sequence = $wpdb->get_row("
WITH 
cte1 AS
(
SELECT id, 
       CASE WHEN     occupied  = LAG(occupied) OVER (ORDER BY id)
                 AND remaining = LAG(remaining) OVER (ORDER BY id)
            THEN 0
            ELSE 1 
            END values_differs
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE digitization_center = '" . $dc_id . "'
),
cte2 AS 
(
SELECT id,
       SUM(values_differs) OVER (ORDER BY id) group_num
FROM cte1
ORDER BY id
)
SELECT MIN(id) as id
FROM cte2
GROUP BY group_num
ORDER BY COUNT(*) DESC LIMIT 1;
");

	$sequence_shelfid = $find_sequence->id;
	
$get_remaining_occupied = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining = 4 AND
occupied = 0 AND
digitization_center = ".$dc_id
);

foreach($get_remaining_occupied as $item) {
$shelf_id = $item->shelf_id;

[$aisle, $bay, $shelf] = explode("_", $shelf_id);

//echo $aisle.'-'.$bay.'-'.$shelf.' - ';

$position_details = $wpdb->get_results("
SELECT position FROM " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE aisle = '" . $aisle . "' 
AND bay = '" . $bay . "' 
AND shelf = '" . $shelf . "' 
AND digitization_center = ".$dc_id);

$position_count = count($position_details);

$data_update = array('remaining' => 4-$position_count);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}


//Check for remaining 4 and occupied = 1
$get_remaining_four = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining = 4 AND
occupied = 1 AND
digitization_center = ".$dc_id);

foreach($get_remaining_four as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('occupied' => 0);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where); 
}


//Check for negative numbers
$get_negative_remaining = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE SIGN(remaining) = -1 AND
digitization_center = ".$dc_id);

foreach($get_negative_remaining as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('remaining' => 4);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where); 
}

//Check for numbers > 5
$get_positive_remaining = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining >= 5 AND
digitization_center = ".$dc_id);

foreach($get_positive_remaining as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('remaining' => 0);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where); 
}

//Check to make sure occupied is 1
$get_not_occupied = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining IN (0,1,2,3) AND
digitization_center = ".$dc_id);

foreach($get_not_occupied as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('occupied' => 1);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where); 
}

//Check to make sure occupied is 0
$get_occupied = $wpdb->get_results("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE remaining = 4 AND
digitization_center = ".$dc_id);

foreach($get_occupied as $item) {
$shelf_id = $item->shelf_id;
$data_update = array('occupied' => 0);
$data_where = array('shelf_id' => $shelf_id, 'digitization_center' => $dc_id);
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

}



	}

} 
endif;

new WP_LOC_Request();