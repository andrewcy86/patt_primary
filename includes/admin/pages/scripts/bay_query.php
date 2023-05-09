<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if ($_GET['aisle_id'] < 10) {
$trim_val = 2;
} else {
$trim_val = 3;
}

$get_bay_num = $wpdb->get_results("
SELECT substring_index(substring_index(shelf_id,'_',-2),'_',1) as count
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
LEFT(shelf_id, ".$trim_val.") = '".$_GET['aisle_id']."_'
");

$valsArray = array();

foreach($get_bay_num as $item) {

$bay_count = $item->count;
array_push($valsArray, $bay_count);
  
}

$valsArray_final = array_unique($valsArray);

$digitization_center_bay_aisle_total = max($valsArray_final);

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
    $output_array[$i] = 'Bay ' . Patt_Custom_Func::get_bay_from_number($value) . ' [' . (27 - $get_available_bay->count) . ' boxes remain]';
    $i++;

}

echo json_encode($output_array);
?>
