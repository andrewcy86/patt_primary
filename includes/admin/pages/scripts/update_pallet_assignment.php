<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$type = $_REQUEST['pallet_action'];
$ticket_id = $_REQUEST['ticket_id'];
$pallet_id = $_REQUEST['pallet_id'];

$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
$table_name_sl = $wpdb->prefix . 'wpsc_epa_scan_list';
$invalid_response = 0;
$pallet_set_count = 0;

$dc_check = 0;

$metadata_array = array();

	//
	// Set Pallet per Item
	//
	$item_ids = $_REQUEST['item_ids'];
	$is_single_item = (count($item_ids) > 1 ) ? false : true;

		// Determine East or West

$digitization_center_array = array();

	foreach( $item_ids as $id ) {

$digitzation_center = $wpdb->get_row(

 "SELECT DISTINCT " . $wpdb->prefix . "terms.name as digitization_center
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
        INNER JOIN " . $wpdb->prefix . "terms ON " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center
        WHERE
        " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center <> 666 AND
        " . $wpdb->prefix . "wpsc_epa_boxinfo.box_destroyed = 0 AND
        " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id = '" . $id . "'"
        
			);
	
			$dc = $digitzation_center->digitization_center;
			
	array_push($digitization_center_array,$dc);
	
	}
		

if( count(array_unique($digitization_center_array)) == 1 && in_array('East', $digitization_center_array )) {
$dc_acronym = 'E';
}

if( count(array_unique($digitization_center_array)) == 1 && in_array('West', $digitization_center_array )) {
$dc_acronym = 'W';
}

if(array_search('', $digitization_center_array)!==false) {
$dc_check = 1;
}

if( count(array_unique($digitization_center_array)) == 2 && in_array('East', $digitization_center_array ) && in_array('West', $digitization_center_array )) {
$dc_check = 2;
}

//echo array_search('', $digitization_center_array);

if( $type == 'yes' && $dc_check == 0) {
	
	// Set Pallet ID
		
		// Build Pallet ID
		$num_id = random_int(1,99999);
		
		$pallet_id = 'P-'.$dc_acronym.'-'.$num_id;
		
		// Does ID Exist?
		
		$pallet_id_count = $wpdb->get_row(

 "SELECT COUNT id
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        WHERE pallet_id = '" . $pallet_id . "'"
        
			);
			
		//IF YES redo $num_id
		
		if ($pallet_id_count > 0) {
				$num_id = random_int(1,99999);
		}
		
		$pallet_id = 'P-'.$dc_acronym.'-'.$num_id;
		
//echo $ticket_id;
	
	foreach( $item_ids as $id ) {

	    $get_box_id = $wpdb->get_row(
 "SELECT DISTINCT id
        FROM " . $wpdb->prefix . "wpsc_epa_scan_list
        WHERE box_id = '" . $id . "'"
			);
			
	$box_id_delete = $get_box_id->id;
	
//echo $box_id_delete;

        $wpdb->delete( $table_name_sl, array( 'id' => $box_id_delete ) );
		do_action('wpppatt_after_assign_pallet', $ticket_id, $id, $pallet_id);
		
		$data_update = array('pallet_id' => $pallet_id, 'location_status_id' => 1, 'scan_list_id' => 0);
		$data_where = array('box_id' => $id);
		$wpdb->update($table_name, $data_update, $data_where);
				
	}
	
echo "Pallets have been assigned an ID.";

} elseif( $type == 'no' && $dc_check == 0) {
	
	foreach( $item_ids as $id ) {
//Pull previous pallet ID if exists
		$get_pallet_id = $wpdb->get_row(
 "SELECT DISTINCT pallet_id
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        WHERE box_id = '" . $id . "'"
			);
			
	$previous_pallet_id = $get_pallet_id->pallet_id;
    
if (!empty($previous_pallet_id)){
        //Insert audit log record
		do_action('wpppatt_after_unassign_pallet', $ticket_id, $id, $previous_pallet_id);

		$data_update = array('pallet_id' => NULL, 'location_status_id' => 1, 'scan_list_id' => 0);
		$data_where = array('box_id' => $id);
		$wpdb->update($table_name, $data_update, $data_where);
} else {
    $invalid_response++;
}

	}

if($invalid_response >= 1) {
    echo "One or more of the selected boxes are not assigned to a pallet.";	
} else {
echo "Pallets IDs have been un-assigned.";
}

} elseif ( $type == 'reassign' && $dc_check == 0 ) {

//Get 3rd value from pallet_id/Digitization Center
$pallet_dc = substr($pallet_id, 2, 1);

//echo 'PALLET : ' . $pallet_id;
$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
$scan_list_table = $wpdb->prefix . 'wpsc_epa_scan_list';

$get_scan_list_id = $wpdb->get_row("SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE pallet_id = '" . $pallet_id . "'");
$scan_list_id = $get_scan_list_id->id;

if(empty($scan_list_id)) {
$scan_list_id = 0;
}

//Does pallet exist in scanning table? If not, set location_status_id to 1 (Pending).
$get_physical_location_exist = $wpdb->get_row("SELECT id
FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE pallet_id = '" . $pallet_id . "'");
$physical_location_exist = $get_physical_location_exist->id;

if(empty($physical_location_exist)) {
$physical_location_id = 1;
} else {
$physical_location_id = 3;
}

//echo 'PHYSICAL LOCATION : ' . $physical_location_id;

			
foreach( $item_ids as $id ) {

$digitzation_center_reassign = $wpdb->get_row(
 "SELECT DISTINCT " . $wpdb->prefix . "terms.name as digitization_center
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
        INNER JOIN " . $wpdb->prefix . "terms ON " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center
        WHERE
        " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center <> 666 AND
        " . $wpdb->prefix . "wpsc_epa_boxinfo.box_destroyed = 0 AND
        " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id = '" . $id . "'"
        
			);
	
			$get_dc_reassign = $digitzation_center_reassign->digitization_center;
			
			$dc_reassign = '';
			
			if($get_dc_reassign == 'East') {
			$dc_reassign = 'E';
			}
			
			if($get_dc_reassign == 'West') {
			$dc_reassign = 'W';
			}
			
$get_old_physical_location = $wpdb->get_row("SELECT b.locations, a.location_status_id, a.pallet_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_location_status b ON b.id = a.location_status_id
WHERE a.box_id = '" . $id . "'");
$old_physical_location = $get_old_physical_location->locations;
$old_location_status_id = $get_old_physical_location->location_status_id;
$old_pallet_id = $get_old_physical_location->pallet_id;

//echo 'PALLET ID : ' . $pallet_id;

//Delete box_ids from scan_list table if pallet assigned already assigned to a staging area
if(!empty($pallet_id)) {
    $get_box_from_scan_list = $wpdb->get_row("SELECT * 
    FROM " . $wpdb->prefix . "wpsc_epa_scan_list
    WHERE box_id = '" . $id . "'");
    $box_from_scan_list = $get_box_from_scan_list->box_id;
    $dbid_from_scan_list = $get_box_from_scan_list->id;
    //echo $box_from_scan_list;
    if(!empty($box_from_scan_list)) {
        $wpdb->delete( $scan_list_table, array( 'id' => $dbid_from_scan_list) );
    }
}

//updates pallet ID

if(!empty($pallet_id) && ($old_pallet_id != $pallet_id) && ($pallet_dc == $dc_reassign) && !empty($old_pallet_id)) {

//if pallet ID is reassigned set physical location to Pending, unless new pallet ID already has a stagingarea_id, then set to In Staging Area
//echo $scan_list_id;

$data_update = array('pallet_id' => $pallet_id, 'scan_list_id' => $scan_list_id);
$data_where = array('box_id' => $id);
$wpdb->update($table_name, $data_update, $data_where);

array_push($metadata_array,'Pallet ID: '.$old_pallet_id.' > '.$pallet_id);

}

//updates pallet ID when unassigned
if(!empty($pallet_id) && empty($old_pallet_id) && ($pallet_dc == $dc_reassign)) {

$data_update_unassigned = array('pallet_id' => $pallet_id, 'scan_list_id' => $scan_list_id);
$data_where_unassigned = array('box_id' => $id);
$wpdb->update($table_name, $data_update_unassigned, $data_where_unassigned);

$old_pallet_id = 'Unassigned';

array_push($metadata_array,'Pallet ID: '.$old_pallet_id.' > '.$pallet_id);

}

if($old_location_status_id != $physical_location_id && $pallet_dc == $dc_reassign) {

    $get_new_physical_location = $wpdb->get_row("SELECT a.locations
    FROM " . $wpdb->prefix . "wpsc_epa_location_status a
    WHERE a.id = '" . $physical_location_id . "'");
    $new_physical_location = $get_new_physical_location->locations;
    
    $data_update_physical_location = array('location_status_id' => $physical_location_id);
    $data_where_physical_location = array('box_id' => $id);
    $wpdb->update($table_name, $data_update_physical_location, $data_where_physical_location);
    
    array_push($metadata_array,'Physical Location: ' . $old_physical_location . ' > ' . $new_physical_location);
}

if (empty($metadata_array)) {
$pallet_set_count = 99999;
} else {
$metadata = implode (", ", array_unique($metadata_array));

do_action('wpppatt_after_box_metadata', $ticket_id, $metadata, $id);

$pallet_set_count++;    
}


}

if($pallet_set_count >= 1 && $pallet_set_count != 99999 && ($pallet_dc == $dc_reassign) ) {
echo "Successfully updated Pallet ID.";
}

if($pallet_dc != $dc_reassign) {
echo "Attempted to assign a pallet that belongs to a different digitization center. ";
}

if($pallet_set_count == 99999) {
echo "No Pallet IDs to update.";
}

}

if($dc_check == 1) {
echo "Digitization center has not been assigned to one or more boxes.";
}

if($dc_check == 2) {
echo "You must select boxes that are all associated with the same digitization center.";
}

Patt_Custom_Func::pallet_cleanup();
?>