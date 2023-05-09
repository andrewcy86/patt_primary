<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

if(isset($_POST['postvarspname']) && isset($_POST['postvaraname']) && isset($_POST['postvarbname']) && isset($_POST['postvarboxname']) && isset($_POST['postvarcentername'])){
   $shelf_position = $_POST['postvarspname'];
 
   $array = explode('_', $shelf_position);
   $shelf = $array[0];
   $position = $array[1];

   $aisle = $_POST['postvaraname'];
   $bay = $_POST['postvarbname'];
   $boxid = $_POST['postvarboxname'];
   $center = $_POST['postvarcentername'];

   $center_value = '';
   
   if ($center == $dc_east_tag->term_id) {
     $center_value = 'E';
   } else if ($center == $dc_west_tag->term_id){
     $center_value = 'W';  
   } else if ($center == $dc_east_cui_tag->term_id){
     $center_value = 'ECUI';    
   } else if ($center == $dc_west_cui_tag->term_id){
     $center_value = 'WCUI';    
   }

			$box_details = $wpdb->get_row(
"SELECT storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE box_id = '" . $boxid . "'"
			);

			$box_storage_location_id = $box_details->storage_location_id;
			
// Update storage status add box back from existing shelf location

			$storage_location_details = $wpdb->get_row(
"SELECT aisle,bay,shelf,position
FROM " . $wpdb->prefix . "wpsc_epa_storage_location
WHERE id = '" . $box_storage_location_id . "'"
			);

			$existing_aisle = $storage_location_details->aisle;
			$existing_bay = $storage_location_details->bay;
			$existing_shelf = $storage_location_details->shelf;
			$existing_position = $storage_location_details->position;
			$existing_shelf_id = $existing_aisle.'_'.$existing_bay.'_'.$existing_shelf;

// Update wpqa_wpsc_epa_storage_location with new location that was selected

$table_name = $wpdb->prefix . 'wpsc_epa_storage_location';
$data_update = array('aisle' => $aisle ,'bay'=>$bay,'shelf'=>$shelf,'position'=>$position);
$data_where = array('id' => $box_storage_location_id);
$wpdb->update($table_name , $data_update, $data_where);

$new_shelf_id_update = $aisle.'_'.$bay.'_'.$shelf;

// Update storage status remove box from new shelf location
Patt_Custom_Func::update_remaining_occupied($center,array($new_shelf_id_update));

Patt_Custom_Func::update_remaining_occupied($center,array($existing_shelf_id));
  

/* Change the box status has been changed to "Pending"  */
$location_statuses = $wpdb->get_row(
    "SELECT id as id,locations as locations
     FROM " . $wpdb->prefix . "wpsc_epa_location_status                
     WHERE locations = 'Pending'");

$location_statuses_id = $location_statuses->id;
$location_statuses_locations = $location_statuses->locations;

/* Set status value for box id to the returned value from above statement*/
$loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
$loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
$loc_status_boxinfo_data_where = array('box_id' => $boxid);
$wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
  
  
// DELETE the physical location to pending after a box is assigned a new location
$table_scan_list = $wpdb->prefix . "wpsc_epa_scan_list";
$wpdb->delete($table_scan_list, array('box_id' => $boxid) );



				$get_ticket_id = $wpdb->get_row("
SELECT ticket_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE
box_id = '" . $boxid . "'
");

				$ticket_id = $get_ticket_id->ticket_id;
				
$shelf_info = $aisle. 'A_' .$bay . 'B_' . $shelf .'S_'.$position.'P_'.$center_value;

$shelf_meta_existing = '';

if($existing_aisle == 0 && $existing_bay == 0 && $existing_shelf == 0 && $existing_position == 0){
$shelf_meta_existing = 'Unassigned';
} else {
$shelf_meta_existing = $existing_aisle. 'A_' .$existing_bay . 'B_' . $existing_shelf .'S_'.$existing_position.'P_'.$center_value;
}
$shelf_meta = $shelf_meta_existing.' > '.$aisle. 'A_' .$bay . 'B_' . $shelf .'S_'.$position.'P_'.$center_value;

do_action('wpppatt_after_shelf_location', $ticket_id, $boxid, $shelf_meta);

   echo "Box ID #: " . $boxid . " has been updated. New Location: " . Patt_Custom_Func::convert_bay_letter($shelf_info);
} else {
   echo "Update not successful.";
}
?>