<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');


    //$digitization_center = 'East';
    $digitization_center_bay_aisle_total = 5;

    $bay_array = range(1, $digitization_center_bay_aisle_total);
    
    $output_array = array(); 
    $i = 1;


foreach ($bay_array as $value) {
    
    
    $get_available_bay = $wpdb->get_row(
				"SELECT count(id) as count
FROM " . $wpdb->prefix . "wpsc_epa_storage_location
WHERE aisle = '" . $_GET['aisle_id'] . "' AND bay = '" . $value . "' AND  digitization_center = '" . $_GET['center'] . "'"
			);

// Updated 3 boxes to a shelf
    $output_array[$i] = 'Bay #' . $value . ' [' . (30 - $get_available_bay->count) . ' boxes remain]';
    $i++;

}

echo json_encode($output_array);
?>
