<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$type = $_REQUEST['pallet_action'];
$ticket_id = $_REQUEST['ticket_id'];

$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
$table_name_sl = $wpdb->prefix . 'wpsc_epa_scan_list';
	//
	// Set Pallet per Item
	//
	$item_ids = $_REQUEST['item_ids'];
	$is_single_item = (count($item_ids) > 1 ) ? false : true;
	
if( $type == 'yes' ) {
	
	// Set Pallet ID
	
		// Determine East or West
		
		$digitzation_center = $wpdb->get_row(

 "SELECT DISTINCT " . $wpdb->prefix . "terms.name as digitization_center
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
        INNER JOIN " . $wpdb->prefix . "terms ON " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center
        WHERE
        " . $wpdb->prefix . "wpsc_epa_storage_location.aisle <> 0 AND 
        " . $wpdb->prefix . "wpsc_epa_storage_location.bay <> 0 AND 
        " . $wpdb->prefix . "wpsc_epa_storage_location.shelf <> 0 AND 
        " . $wpdb->prefix . "wpsc_epa_storage_location.position <> 0 AND 
        " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center <> 666 AND
        " . $wpdb->prefix . "wpsc_epa_boxinfo.box_destroyed = 0 AND
        " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = " . $ticket_id
        
			);
			
		$dc = $digitzation_center->digitization_center;
		if($dc == 'East') {
		    $dc_acronym = 'E';
		}
		
		if($dc == 'West') {
		    $dc_acronym = 'W';
		}
		
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
		
		echo $ticket_id;
	
	foreach( $item_ids as $id ) {

	    $get_box_id = $wpdb->get_row(
 "SELECT DISTINCT id
        FROM " . $wpdb->prefix . "wpsc_epa_scan_list
        WHERE box_id = '" . $id . "'"
			);
			
	$box_id_delete = $get_box_id->id;
	
echo $box_id_delete;

        $wpdb->delete( $table_name_sl, array( 'id' => $box_id_delete ) );
		do_action('wpppatt_after_assign_pallet', $ticket_id, $id, $pallet_id);
		
		$data_update = array('pallet_id' => $pallet_id, 'location_status_id' => 1, 'scan_list_id' => 0);
		$data_where = array('box_id' => $id);
		$wpdb->update($table_name, $data_update, $data_where);
				
	}
	

} elseif( $type == 'no' ) {
	
	foreach( $item_ids as $id ) {
//Pull previous pallet ID if exists
		$get_pallet_id = $wpdb->get_row(
 "SELECT DISTINCT pallet_id
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        WHERE box_id = '" . $id . "'"
			);
			
	$previous_pallet_id = $get_pallet_id->pallet_id;
    
if ($previous_pallet_id != ''){
        //Insert audit log record
		do_action('wpppatt_after_unassign_pallet', $ticket_id, $id, $previous_pallet_id);
}

		$data_update = array('pallet_id' => NULL);
		$data_where = array('box_id' => $id);
		$wpdb->update($table_name, $data_update, $data_where);

	}
	
} 


?>