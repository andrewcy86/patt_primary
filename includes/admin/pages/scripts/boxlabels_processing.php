<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(isset($_POST['postvarsboxid'])){
    
$box_ids = $_POST['postvarsboxid'];

$box_arr = explode(",", $box_ids);

$box_count = count($box_arr);

$count = 0;

$boxidarray = array();

foreach($box_arr as $key => $value) { 
    
$get_destroy_status = $wpdb->get_row("
SELECT a.box_destroyed, a.box_id, b.aisle, b.bay, b.shelf, b.position, b.digitization_center
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b on a.storage_location_id = b.id 
WHERE a.box_id = '" . $value . "'
");
$destroy_status = $get_destroy_status->box_destroyed;
$box_id = $get_destroy_status->box_id;

//checking assigned location
$aisle = $get_destroy_status->aisle;
$bay = $get_destroy_status->bay;
$shelf = $get_destroy_status->shelf;
$position = $get_destroy_status->position;
$digitization_center = $get_destroy_status->digitization_center;

if ($destroy_status == 0 && ($aisle != 0 && $bay != 0 && $shelf != 0 && $position != 0 && $digitization_center != 666)) {
array_push($boxidarray, $box_id);
}

if ($destroy_status == 1 || ($aisle == 0 || $bay == 0 || $shelf == 0 || $position == 0 || $digitization_center == 666)) {
$count++;
}

}

$boxidarray_val = implode(',', $boxidarray);

if ($box_count == $count) {
echo 'false'.'|'.$boxidarray_val;
}

if ($count < $box_count && $count != 0) {
echo 'warn'.'|'.$boxidarray_val;
}

if ($count < $box_count && $count == 0) {
echo 'true'.'|'.$boxidarray_val;
}

} else {
   echo "Update not successful.";
}
?>