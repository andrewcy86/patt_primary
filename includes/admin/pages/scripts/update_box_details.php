<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['postvarspo']) ||
!empty($_POST['postvarsrs']) ||
!empty($_POST['postvarsbs']) ||
($_POST['postvarsdc'] == 0) || ($_POST['postvarsdc'] == 1)
){
   //id in box table (e.g. 1)
   $box_id = $_POST['postvarsboxid'];
   //box_id in box table (e.g. 0000001-1)
   $pattboxid = $_POST['postvarspattboxid'];
   $po = $_POST['postvarspo'];
   $rs = $_POST['postvarsrs'];
   $dc = $_POST['postvarsdc'];
   $bs = $_POST['postvarsbs'];

$type = 'box';

$get_ticket_id = $wpdb->get_row("SELECT ticket_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE box_id = '" . $pattboxid . "'");
$ticket_id = $get_ticket_id->ticket_id;
$metadata_array = array();
$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';

$old_box_dc = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE box_id = '" . $pattboxid . "'");
$old_dc = $old_box_dc->box_destroyed;

$old_box_status = $wpdb->get_row("SELECT box_status 
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo b
WHERE b.box_id = '" . $pattboxid . "'");
$old_bs = $old_box_status->box_status;

$old_box_status_name = $wpdb->get_row("SELECT a.name as box_status 
FROM wpqa_terms a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.box_status = a.term_id 
WHERE b.box_id = '" . $pattboxid . "'");
$old_bs_name = $old_box_status_name->box_status;

//updates box status
if($old_bs != $bs) {
$data_update = array('box_status' => $bs);
$data_where = array('id' => $box_id);
$wpdb->update($table_name , $data_update, $data_where);

$new_box_status = $wpdb->get_row("SELECT a.name as box_status 
FROM wpqa_terms a
INNER JOIN wpqa_wpsc_epa_boxinfo b ON b.box_status = a.term_id
WHERE b.box_id = '" . $pattboxid . "'");
$new_bs = $new_box_status->box_status;

array_push($metadata_array,'Box Status: '.$old_bs_name.' > '.$new_bs);
}

//updates destruction completed and adds to request history
if($dc != $old_dc) {
$data_update = array('box_destroyed' => $dc);
$data_where = array('id' => $box_id);

$dc_val = '';
$old_dc_val = '';
if($dc == '1') {
    $dc_val = 'Yes';
} 
elseif($dc == '0') {
    $dc_val = 'No';
}

if($old_dc == '1') {
    $old_dc_val = 'Yes';
} 
elseif($old_dc == '0') {
    $old_dc_val = 'No';
}

$wpdb->update($table_name, $data_update, $data_where);
array_push($metadata_array,'Destruction Completed: '.$old_dc_val.' > '.$dc_val);

$get_storage_id = $wpdb->get_row("
SELECT id, storage_location_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo 
WHERE id = '" . $box_id . "'
");
$storage_location_id = $get_storage_id->storage_location_id;

$box_details = $wpdb->get_row(
"SELECT 
b.digitization_center,
b.aisle,
b.bay,
b.shelf,
b.position
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b WHERE a.storage_location_id = b.id
AND a.id = '" . $box_id . "'"
			);
			
			$box_storage_digitization_center = $box_details->digitization_center;
			$box_storage_aisle = $box_details->aisle;
			$box_storage_bay = $box_details->bay;
			$box_storage_shelf = $box_details->shelf;
			$box_storage_shelf_id = $box_storage_aisle . '_' . $box_storage_bay . '_' . $box_storage_shelf;

$box_storage_status = $wpdb->get_row(
"SELECT 
occupied,
remaining
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE shelf_id = '" . $box_storage_shelf_id . "'"
			);

$box_storage_status_occupied = $box_storage_status->occupied;
$box_storage_status_remaining = $box_storage_status->remaining;
$box_storage_status_remaining_added = $box_storage_status->remaining + 1;

//SET PHYSICAL LOCATION TO DESTROYED
$pl_update = array('location_status_id' => '6');
$pl_where = array('id' => $box_id);
$wpdb->update($table_name , $pl_update, $pl_where);

//SET SHELF LOCATION TO 0
$table_sl = $wpdb->prefix . 'wpsc_epa_storage_location';
$sl_update = array('digitization_center' => '666','aisle' => '0','bay' => '0','shelf' => '0','position' => '0');
$sl_where = array('id' => $storage_location_id);
$wpdb->update($table_sl , $sl_update, $sl_where);

//ADD AVALABILITY TO STORAGE STATUS
if ($box_storage_status_remaining <= 4) {
$table_ss = $wpdb->prefix . 'wpsc_epa_storage_status';
$ssr_update = array('remaining' => $box_storage_status_remaining_added);
$ssr_where = array('shelf_id' => $box_storage_shelf_id, 'digitization_center' => $box_storage_digitization_center);
$wpdb->update($table_ss , $ssr_update, $ssr_where);
}

if($box_storage_status_remaining == 4){
$sso_update = array('occupied' => 0);
$sso_where = array('shelf_id' => $box_storage_shelf_id, 'digitization_center' => $box_storage_digitization_center);
$wpdb->update($table_ss , $sso_update, $sso_where);
}
}

if(!empty($po)) {
$get_old_acronym = $wpdb->get_row("SELECT b.office_acronym as office_acronym FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a LEFT JOIN " . $wpdb->prefix . "wpsc_epa_program_office b ON a.program_office_id = b.office_code WHERE a.box_id = '" . $pattboxid . "'");
$po_old_acronym = $get_old_acronym->office_acronym;

//update box table with program office foreign key
$data_update = array('program_office_id' => $po);
$data_where = array('id' => $box_id);
$wpdb->update($table_name , $data_update, $data_where);

$get_new_acronym = $wpdb->get_row("SELECT office_acronym FROM " . $wpdb->prefix . "wpsc_epa_program_office WHERE office_code = '" . $po . "'");
$po_new_acronym = $get_new_acronym->office_acronym;
if($po_old_acronym != $po_new_acronym) {
array_push($metadata_array,'Program Office: '.$po_old_acronym.' > '.$po_new_acronym);
}
}

if(!empty($rs)) {
$get_old_rs = $wpdb->get_row("SELECT b.Schedule_Item_Number as Schedule_Item_Number FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a LEFT JOIN " . $wpdb->prefix . "epa_record_schedule b ON a.record_schedule_id = b.id WHERE a.box_id = '" . $pattboxid . "'");
$rs_old_num = $get_old_rs->Schedule_Item_Number;

//update box table with record schedule foreign key
$data_update = array('record_schedule_id' => $rs);
$data_where = array('id' => $box_id);
$wpdb->update($table_name , $data_update, $data_where);

$get_new_rs = $wpdb->get_row("SELECT Schedule_Item_Number FROM " . $wpdb->prefix . "epa_record_schedule WHERE id = '" . $rs . "'");
$rs_new_num = $get_new_rs->Schedule_Item_Number;
if($rs_old_num != $rs_new_num) {
array_push($metadata_array,'Record Schedule: '.$rs_old_num.' > '.$rs_new_num);
}
}

$metadata = implode (", ", $metadata_array);

if( ($old_bs != $bs) || ($dc != $old_dc) || ($po_old_acronym != $po_new_acronym) || ($rs_old_num != $rs_new_num) ) {
do_action('wpppatt_after_box_metadata', $ticket_id, $metadata, $pattboxid);
}

//send email/notification when program office is updated
if($po_old_acronym != $po_new_acronym) {
$get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
$get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');

$user_id_array = [$get_user_id->ID];
$convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
$patt_agent_id = implode($convert_patt_id);
$pattagentid_array = [$patt_agent_id];
$data = [];

//disabled email notification
//$email = 1;
Patt_Custom_Func::insert_new_notification('email-program-office-updated',$pattagentid_array,$pattboxid,$data,$email);
}

//send email/notification when record schedule is updated
if($rs_old_num != $rs_new_num) {
$get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
$get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');

$user_id_array = [$get_user_id->ID];
$convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
$patt_agent_id = implode($convert_patt_id);
$pattagentid_array = [$patt_agent_id];
$data = [];

//disabled email notification
$email = 1;
Patt_Custom_Func::insert_new_notification('email-record-schedule-updated',$pattagentid_array,$pattboxid,$data,$email);
}

echo "Box ID #: " . $pattboxid . " has been updated.";

} else {
    echo $pattboxid;
   echo "Please make an edit.";
}
?>