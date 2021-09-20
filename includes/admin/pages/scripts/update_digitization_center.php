<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(isset($_POST['postvarsboxidname'])){
   $box_id = $_POST['postvarsboxidname'];
   $dc = $_POST['postvarsdc'];

   $box_details = $wpdb->get_row(
"SELECT 
" . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id as storage_location_id, 
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as id, 
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_id as box_id, 
" . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center as digitization_center,
" . $wpdb->prefix . "terms.name as dc_name,
" . $wpdb->prefix . "wpsc_epa_storage_location.aisle as aisle,
" . $wpdb->prefix . "wpsc_epa_storage_location.bay as bay,
" . $wpdb->prefix . "wpsc_epa_storage_location.shelf as shelf,
" . $wpdb->prefix . "wpsc_epa_storage_location.shelf as position
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
INNER JOIN " . $wpdb->prefix . "terms ON '".$dc."' = " . $wpdb->prefix . "terms.term_id
WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.id = '" . $box_id . "'"
			);
			

			$box_storage_location_id = $box_details->storage_location_id;
			$box_storage_digitization_center = $box_details->digitization_center;
			$box_storage_aisle = $box_details->aisle;
			$box_storage_bay = $box_details->bay;
			$box_storage_shelf = $box_details->shelf;
			$box_sotrage_shelf_id = $box_storage_aisle . '_' . $box_storage_bay . '_' . $box_storage_shelf;
			$box_id_val = $box_details->box_id;
			$box_dc_name = $box_details->dc_name;

$table_sl = $wpdb->prefix . 'wpsc_epa_storage_location';
$sl_update = array('digitization_center' => $dc, 'aisle' => '0' ,'bay'=>'0','shelf'=>'0','position'=>'0');
$sl_where = array('id' => $box_storage_location_id);
$wpdb->update($table_sl , $sl_update, $sl_where);

//Update Box Status Table
Patt_Custom_Func::update_remaining_occupied($dc,array($box_sotrage_shelf_id));


$get_ticket_id = $wpdb->get_row("
SELECT ticket_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE
box_id = '" . $box_id_val . "'");

$ticket_id = $get_ticket_id->ticket_id;

do_action('wpppatt_after_digitization_center', $ticket_id, $box_id_val, $box_dc_name);

if($box_id_val == '' && $box_dc_name == '') {
echo "Please select a digitization center. No updates have been made";
} else {
echo "Box ID #: " . $box_id_val . " has been updated.\nAssigned Digitization Center: " .$box_dc_name;
}   


} else {
   echo "Update not successful.";
}
?>