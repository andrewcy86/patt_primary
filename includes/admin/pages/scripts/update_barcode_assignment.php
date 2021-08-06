<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

//Define tables
$table_box = $wpdb->prefix . "wpsc_epa_boxinfo";
$table_scan_list = $wpdb->prefix . "wpsc_epa_scan_list";

if(isset($_POST['postvarslocation']) && isset($_POST['postvarsboxpallet']) && isset($_POST['postvarsuser']) && isset($_POST['postvarspage'])){
   $location = $_POST['postvarslocation'];
   $boxpallet = $_POST['postvarsboxpallet'];
   $userinfo = $_POST['postvarsuser'];
   $pageinfo = $_POST['postvarspage'];
    
   $boxpallet_arr = explode (",", $boxpallet);  
   //echo $location;
   //echo '/n';
   //print_r($boxpallet_arr);
   //echo $userinfo;
$date = date('Y-m-d H:i:s');
$pallet_count = 0;
$box_count = 0;
$pallet_box_exist = 0;
$invalid_box_pallet = 0;
$total_array_count = count($boxpallet_arr);
$pallet_update = 0;
$boxsa_update = 0;
$boxscn_update = 0;
$boxcrt_update = 0;
$box_pallet_check_array = array();
//$pallet_check_array = array();

$invalid_box_decline_check = 0;
$invalid_box_recall_check = 0;
$invalid_box_destroyed_check = 0;
$invalid_box_active_check = 0;
$invalid_box_request_status_check = 0;

$box_decline_check_array = array();
$box_recall_check_array = array();
$box_destroyed_check_array = array();
$box_active_check_array = array();
$invalid_pallet_id_array = array();
$invalid_request_check_array = array();

$received_tag = get_term_by('slug', 'received', 'wpsc_statuses');
$in_progress_tag = get_term_by('slug', 'in-process', 'wpsc_statuses');
$ecms_tag = get_term_by('slug', 'ecms', 'wpsc_statuses');
$sems_tag = get_term_by('slug', 'sems', 'wpsc_statuses');
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses');
//Boxes can only be scanned
$request_array = array($received_tag->term_id, $in_progress_tag->term_id, $ecms_tag->term_id, $sems_tag->term_id, $completed_dispositioned_tag->term_id);

////////
//Determine if location entered is valid
////////
 if(preg_match('/\b(SA-\d\d-E|SA-\d\d-W)\b/i', $location) || preg_match('/\b(SCN-\d\d-E|SCN-\d\d-W)\b/i', $location) || preg_match('/\b(CID-\d\d-E|CID-\d\d-W)\b/i', $location) || preg_match('/^\d{1,3}A_\d{1,3}B_\d{1,3}S_\d{1,3}P_(E|W|ECUI|WCUI)$/i', $location)){

////////
//Determine if box/pallet entered is valid
////////
foreach($boxpallet_arr as $box_pallet_pre_check){
 if(preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $box_pallet_pre_check) || preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $box_pallet_pre_check)){
$box_pallet_check = 0;
 } else {
$box_pallet_check++;
 }
}

if($box_pallet_check == 0) {

////////
//Determine if boxes and pallets are all valid
////////
foreach($boxpallet_arr as $box_pallet_check){

 if(preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $box_pallet_check)){
$pallet_check = $wpdb->get_row(
"SELECT id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE pallet_id = '" . $box_pallet_check . "'");
$pallet_exist = $pallet_check->id;

if ($pallet_exist != '') {
$pallet_box_exist++;
} else {
//array_push($pallet_check_array,$box_pallet_check);
$invalid_box_pallet = 1;

array_push($invalid_pallet_id_array,$box_pallet_check);
$invalid_pallet_items = implode(",", $invalid_pallet_id_array);
//exit;
}

 }

//Determine if box is valid
 if(preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $box_pallet_check)){
$box_check = $wpdb->get_row(
"SELECT a.id, b.active, a.box_destroyed, b.ticket_status
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON b.id = a.ticket_id
WHERE a.box_id = '" . $box_pallet_check . "'");
$box_exist = $box_check->id;
$box_active = $box_check->active;
$box_destroyed = $box_check->box_destroyed;
$box_request_status = $box_check->ticket_status;
//echo $box_pallet_check;
//Is box part of recall/decline?
  $type = 'box';
  $request_recall_check = Patt_Custom_Func::id_in_recall($box_pallet_check,$type);
  $return_check = Patt_Custom_Func::id_in_return($box_pallet_check, $type);
  
 /*echo $box_exist.'e';
 echo $return_check.'rc';
 echo $request_recall_check.'recallc';
 echo $box_active.'bactive';
 echo $box_destroyed.'bdestroy';*/

if (!empty($box_exist) && $return_check != 1 && $request_recall_check != 1 && $box_active == 1 && $box_destroyed == 0 && in_array($box_request_status, $request_array)) {
$pallet_box_exist++;
}

if($return_check == 1) {
    array_push($box_decline_check_array, $box_pallet_check);
    $invalid_box_decline_check = 1;
}
if($request_recall_check == 1) {
    array_push($box_recall_check_array, $box_pallet_check);
    $invalid_box_recall_check = 1;
}
if($box_destroyed == 1) {
    array_push($box_destroyed_check_array, $box_pallet_check);
    $invalid_box_destroyed_check = 1;
}
if($box_active == 0) {
    array_push($box_active_check_array, $box_pallet_check);
    $invalid_box_active_check = 1;
}
if(!in_array($box_request_status, $request_array)) {
    array_push($invalid_request_check_array, $box_pallet_check);
    $invalid_box_request_status_check = 1;
}

/*else {
//push invalid box/pallet to array
array_push($box_pallet_check_array,$box_pallet_check);
$invalid_box_pallet = 1;
}
*/
 }
}

/*
if ($invalid_box_pallet == 1 && (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $box_pallet_check) || preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $box_pallet_check))) {
$invalid_items = implode(",", $box_pallet_check_array);
echo "Invalid: [".$invalid_items."]";
exit;
}
*/
if($invalid_box_pallet == 1) {
echo "The following pallets do not exist in PATT: [".$invalid_pallet_items."]";
}

if($invalid_box_decline_check == 1 && (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $box_pallet_check) || preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $box_pallet_check))) {
    $invalid_items_decline = implode(",", $box_decline_check_array);
    if(count($box_decline_check_array) == 1) {
        echo "<strong>Box in decline status</strong> : [".$invalid_items_decline."] <br /> <br />";
    }
    else {
        echo "<strong>Boxes in decline status</strong> : [".$invalid_items_decline."] <br /> <br />";
    }
    exit;
}
    
if($invalid_box_recall_check == 1 && (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $box_pallet_check) || preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $box_pallet_check))) {
    $invalid_items_recall = implode(",", $box_recall_check_array);
    if(count($box_recall_check_array) == 1) {
        echo "<strong>Box in recall status</strong> : [".$invalid_items_recall."] <br /> <br />";
    }
    else {
        echo "<strong>Boxes in recall status</strong> : [".$invalid_items_recall."] <br /> <br />";
    }
    exit;
}
    
if($invalid_box_destroyed_check == 1 && (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $box_pallet_check) || preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $box_pallet_check))) {
    $invalid_items_destroyed = implode(",", $box_destroyed_check_array);
    if(count($box_destroyed_check_array) == 1) {
        echo "<strong>Box has been destroyed</strong> : [".$invalid_items_destroyed."] <br /> <br />";
    }
    else {
        echo "<strong>Boxes have been destroyed</strong> : [".$invalid_items_destroyed."] <br /> <br />";
    }
    exit;
}
    
if($invalid_box_active_check == 1 && (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $box_pallet_check) || preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $box_pallet_check))) {
    $invalid_items_active = implode(",", $box_active_check_array);
    if(count($box_active_check_array) == 1) {
        echo "<strong>Box has been archived/does not exist</strong>: [".$invalid_items_active."] <br /> <br />";
    }
    else {
       echo "<strong>Boxes have been archived/do not exist</strong>: [".$invalid_items_active."] <br /> <br />";
    }
    exit;
}

if($invalid_box_request_status_check == 1 && (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $box_pallet_check) || preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $box_pallet_check))) {
    $invalid_items_active = implode(",", $invalid_request_check_array);
    if(count($invalid_request_check_array) == 1) {
        echo "<strong>Box is not able to be assigned a physical location at this request status</strong>: [".$invalid_items_active."] <br /> <br />";
    }
    else {
       echo "<strong>Boxes are not able to be assigned a physical location at this request status</strong>: [".$invalid_items_active."] <br /> <br />";
    }
    exit;
}

//Proceed all box/pallets are valid
if($pallet_box_exist == $total_array_count) {

////////
//Determine if there is a mix of pallets and boxes
//If all pallet determine if location is staging area only
//Insert pallet location information
////////

foreach($boxpallet_arr as $pallet){
 if(preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $pallet)){
  $pallet_count++;
 }
}
//Pallet Exists!

if ($pallet_count >= 1) {

if ($total_array_count != $pallet_count) {
echo "All scanned barcodes must be either boxes or pallets.";

} else {
//Check if location is a staging area
if(preg_match('/\b(SA-\d\d-E|SA-\d\d-W)\b/i', $location)) {
//Update wpqa_wpsc_epa_boxinfo table with pallet location
foreach($boxpallet_arr as $pallet){

//Update Boxinfo Table
$pallet_boxinfo_update = array('location_status_id' => 3);
$pallet_boxinfo_where = array('pallet_id' => $pallet);
$wpdb->update($table_box, $pallet_boxinfo_update, $pallet_boxinfo_where);

$get_pallet_ticket_id = $wpdb->get_row(
"SELECT DISTINCT ticket_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE pallet_id = '" . $pallet . "' LIMIT 1");
$pallet_ticket_id = $get_pallet_ticket_id->ticket_id;

//Insert into Scan List Table
//Does location exist in Scan List Table?

$check_pallet_id = $wpdb->get_row(
"SELECT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE pallet_id = '" . $pallet . "'");
$pallet_id_dbid = $check_pallet_id->id;

if (empty($pallet_id_dbid)) {
$wpdb->insert(
				$table_scan_list,
				array(
					'pallet_id' => $pallet,
					'stagingarea_id' => $location
				)
);

$updpate_check_pallet_id = $wpdb->get_row(
"SELECT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE pallet_id = '" . $pallet . "'");
$update_pallet_id_dbid = $updpate_check_pallet_id->id;

// Update Box Info Table
$pallet_boxinfodbid_update = array('scan_list_id' => $update_pallet_id_dbid, 'location_status_id' => 3);
$pallet_boxinfodbid_where = array('pallet_id' => $pallet);
$wpdb->update($table_box, $pallet_boxinfodbid_update, $pallet_boxinfodbid_where);

} else {

$pallet_scanlist_update = array('cart_id' => NULL, 'scanning_id' => NULL, 'stagingarea_id' => $location, 'shelf_location' => NULL,'date_modified' => $date);
$pallet_scanlist_where = array('id' => $pallet_id_dbid);
$wpdb->update($table_scan_list , $pallet_scanlist_update, $pallet_scanlist_where);
}

$pallet_update = 1;
//Insert audit log information
do_action('wpppatt_after_assign_pallet_location',$pallet_ticket_id,$location,$pallet,$userinfo);

}

if($pallet_update == 1) {
 echo 'Pallet locations has been updated.';   
}

} else {
echo "Pallet location must be a staging area.";
}
}
}


//Determine if there is a mix of pallets and boxes
//If all pallet determine if location is staging area only
//Insert pallet location information
////////

foreach($boxpallet_arr as $boxes){
 if(preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $boxes)){
  $box_count++;
 }
}

////////
//Insert Box Location > Staging Area ID Information
////////
//Check if location is a scanning area NEED AND STATEMENT TO CHECK IF ALL PALETS

if(preg_match('/\b(SA-\d\d-E|SA-\d\d-W)\b/i', $location) && ($box_count >= 1) && ($total_array_count == $box_count)) {

//Update wpqa_wpsc_epa_boxinfo table with box location
foreach($boxpallet_arr as $box_sa){

//Update Boxinfo Table
$boxsa_boxinfo_update = array('location_status_id' => 3);
$boxsa_boxinfo_where = array('box_id' => $box_sa);
$wpdb->update($table_box, $boxsa_boxinfo_update, $boxsa_boxinfo_where);

$get_boxsa_ticket_id = $wpdb->get_row(
"SELECT DISTINCT ticket_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE box_id = '" . $box_sa . "' LIMIT 1");
$boxsa_ticket_id = $get_boxsa_ticket_id->ticket_id;

//Insert into Scan List Table
//Does location exist in Scan List Table?

$check_boxsa_id = $wpdb->get_row(
"SELECT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id = '" . $box_sa . "'");
$boxsa_dbid = $check_boxsa_id->id;

if (empty($boxsa_dbid)) {
$wpdb->insert(
				$table_scan_list,
				array(
					'box_id' => $box_sa,
					'stagingarea_id' => $location
				)
);  
// Get back DB ID
$get_box_id = $wpdb->get_row(
"SELECT DISTINCT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id = '" . $box_sa . "'");
$box_id_dbid = $get_box_id->id;
// Update Box Info Table
$box_boxinfodbid_update = array('scan_list_id' => $box_id_dbid);
$box_boxinfodbid_where = array('box_id' => $box_sa);
$wpdb->update($table_box, $box_boxinfodbid_update, $box_boxinfodbid_where);
Patt_Custom_Func::pallet_cleanup();

} else {
$boxsa_scanlist_update = array('cart_id' => NULL, 'scanning_id' => NULL, 'stagingarea_id' => $location, 'shelf_location' => NULL,'date_modified' => $date);
$boxsa_scanlist_where = array('id' => $boxsa_dbid);
$wpdb->update($table_scan_list , $boxsa_scanlist_update, $boxsa_scanlist_where);
}

$boxsa_update = 1;
//Insert audit log information
do_action('wpppatt_after_assign_box_location',$boxsa_ticket_id,$location,$box_sa,$userinfo);

}

if($boxsa_update == 1) {
 echo 'Box location has been set to '.$location.'.';   
}
}

////////
//Insert Box Location > Scanning ID Information
////////
//Check if location is a scanning area
if(preg_match('/\b(SCN-\d\d-E|SCN-\d\d-W)\b/i', $location) && ($box_count >= 1) && ($total_array_count == $box_count)) {

//Update wpqa_wpsc_epa_boxinfo table with box location
foreach($boxpallet_arr as $box_scn){

//Update Boxinfo Table
$boxscn_boxinfo_update = array('location_status_id' => 5);
$boxscn_boxinfo_where = array('box_id' => $box_scn);
$wpdb->update($table_box, $boxscn_boxinfo_update, $boxscn_boxinfo_where);

$get_boxscn_ticket_id = $wpdb->get_row(
"SELECT DISTINCT ticket_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE box_id = '" . $box_scn . "' LIMIT 1");
$boxscn_ticket_id = $get_boxscn_ticket_id->ticket_id;

//Insert into Scan List Table
//Does location exist in Scan List Table?

$check_boxscn_id = $wpdb->get_row(
"SELECT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id = '" . $box_scn . "'");
$boxscn_dbid = $check_boxscn_id->id;

if (empty($boxscn_dbid )) {
$wpdb->insert(
				$table_scan_list,
				array(
					'box_id' => $box_scn,
					'scanning_id' => $location
				)
);
// Get back DB ID
$get_box_id = $wpdb->get_row(
"SELECT DISTINCT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id = '" . $box_scn . "'");
$box_id_dbid = $get_box_id->id;
// Update Box Info Table
$box_boxinfodbid_update = array('scan_list_id' => $box_id_dbid);
$box_boxinfodbid_where = array('box_id' => $box_scn);
$wpdb->update($table_box, $box_boxinfodbid_update, $box_boxinfodbid_where);
Patt_Custom_Func::pallet_cleanup();

} else {
$boxscn_scanlist_update = array('cart_id' => NULL, 'scanning_id' => $location, 'stagingarea_id' => NULL, 'shelf_location' => NULL,'date_modified' => $date);
$boxscn_scanlist_where = array('id' => $boxscn_dbid);
$wpdb->update($table_scan_list , $boxscn_scanlist_update, $boxscn_scanlist_where);
}

$boxscn_update = 1;
//Insert audit log information
do_action('wpppatt_after_assign_box_location',$boxscn_ticket_id,$location,$box_scn,$userinfo);

}

if($boxscn_update == 1) {
 echo 'Box location has been set to '.$location.'.';   
}
}

////////
//Insert Box Location > Cart ID Information
////////
//Check if location is on a cart
//if(preg_match_all('/(\bCID-\d\d-E\b|\bCID-\d\d-W\b)|(\bCID-\d\d-EAST\sCUI\b|\bCID-\d\d-WEST\sCUI\b)|(\bCID-\d\d-EAST\b|\bCID-\d\d-WEST\b)|(\bCID-\d\d-EASTCUI\b|\bCID-\d\d-WESTCUI\b)/im', $location)) {
if(preg_match('/\b(CID-\d\d-E|CID-\d\d-W)\b/i', $location) && ($box_count >= 1) && ($total_array_count == $box_count)) {

//Update wpqa_wpsc_epa_boxinfo table with box location
foreach($boxpallet_arr as $box_crt){

//Update Boxinfo Table
$boxcrt_boxinfo_update = array('location_status_id' => 4);
$boxcrt_boxinfo_where = array('box_id' => $box_crt);
$wpdb->update($table_box, $boxcrt_boxinfo_update, $boxcrt_boxinfo_where);

$get_boxcrt_ticket_id = $wpdb->get_row(
"SELECT DISTINCT ticket_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE box_id = '" . $box_crt . "' LIMIT 1");
$boxcrt_ticket_id = $get_boxcrt_ticket_id->ticket_id;

//Insert into Scan List Table
//Does location exist in Scan List Table?

$check_boxcrt_id = $wpdb->get_row(
"SELECT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id = '" . $box_crt . "'");
//$boxcrt_id_count = $check_boxcrt_id->count;
$boxcrt_dbid = $check_boxcrt_id->id;

if (empty($boxcrt_dbid)) {
$wpdb->insert(
				$table_scan_list,
				array(
					'box_id' => $box_crt,
					'cart_id' => $location
				)
);  
// Get back DB ID
$get_box_id = $wpdb->get_row(
"SELECT DISTINCT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id = '" . $box_crt . "'");
$box_id_dbid = $get_box_id->id;
// Update Box Info Table
$box_boxinfodbid_update = array('scan_list_id' => $box_id_dbid);
$box_boxinfodbid_where = array('box_id' => $box_crt);
$wpdb->update($table_box, $box_boxinfodbid_update, $box_boxinfodbid_where);
Patt_Custom_Func::pallet_cleanup();
} else {
$boxcrt_scanlist_update = array('cart_id' => $location, 'scanning_id' => NULL, 'stagingarea_id' => NULL, 'shelf_location' => NULL,'date_modified' => $date);
$boxcrt_scanlist_where = array('id' => $boxcrt_dbid);
$wpdb->update($table_scan_list , $boxcrt_scanlist_update, $boxcrt_scanlist_where);
}

$boxcrt_update = 1;
//Insert audit log information
do_action('wpppatt_after_assign_box_location',$boxcrt_ticket_id,$location,$box_crt,$userinfo);

}

if($boxcrt_update == 1) {
 echo 'Box location has been set to '.$location.'.';   
}
}

} //END Check for valid/invalid box/pallet check

////////
//Insert Box Location > Shelf ID Information
////////
//Check if location is for shelf
if(preg_match('/^\d{1,3}A_\d{1,3}B_\d{1,3}S_\d{1,3}P_(E|W|ECUI|WCUI)$/i', $location) && ($box_count >= 1) && ($total_array_count == $box_count)) {

foreach($boxpallet_arr as $box_shelf){
    
//echo $box_shelf;
                        /* Restrict physical location scanning assignments to one box */
                        if($total_array_count == 1){
                        
                            $position_array = explode('_', $location);
            
                            $aisle = substr($position_array[0], 0, -1);
                            $bay = substr($position_array[1], 0, -1);
                            $shelf = substr($position_array[2], 0, -1);
                            $position = substr($position_array[3], 0, -1);
                            $dc = $position_array[4];
                            $center_term_id = term_exists($dc);
                            $new_term_object = get_term( $center_term_id );
                            $new_position_id_storage_location = $aisle.'A_'.$bay.'B_'.$shelf.'S_'.$position.'P_'.strtoupper($dc); 
                            $new_A_B_S_only_storage_location = $aisle.'_'.$bay.'_'.$shelf;
            
                            /* Add logic to determine if a location is in the facility. */
            
            			    $storage_location_details = $wpdb->get_row(
            			                                                "SELECT shelf_id 
            			                                                FROM " . $wpdb->prefix . "wpsc_epa_storage_status
                                                                        WHERE shelf_id = '" . esc_sql($new_A_B_S_only_storage_location) . "'"
                                            			              );
                                            			              
                			$facility_shelfid = $storage_location_details->shelf_id;
    
    
                			if($facility_shelfid == $new_A_B_S_only_storage_location){
                		        
                		        $box_id_new_scan = $box_shelf;
                		         
                		        /* Determine if the position is occupied */
                		        $existing_boxinfo_details = $wpdb->get_row(
                			                                                "SELECT b.aisle as aisle,b.bay as bay,b.shelf as shelf,b.position as position,b.digitization_center as dc
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a                
                                                                            LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b ON a.storage_location_id = b.id
                                                                            WHERE a.box_id = '" . esc_sql($box_id_new_scan) . "'
                                            			                ");
                                      			              
                		        $existing_boxinfo_aisle = $existing_boxinfo_details->aisle;
                			    $existing_boxinfo_bay = $existing_boxinfo_details->bay;
                			    $existing_boxinfo_shelf = $existing_boxinfo_details->shelf;
                			    $existing_boxinfo_position = $existing_boxinfo_details->position;
                			    $term_object = get_term( $existing_boxinfo_details->dc);
                			    $existing_boxinfo_dc =  $term_object->slug;
                		        $existing_boxinfo_position_id_storage_location = $existing_boxinfo_aisle.'A_'.$existing_boxinfo_bay.'B_'.$existing_boxinfo_shelf.'S_'.$existing_boxinfo_position.'P_'.strtoupper($existing_boxinfo_dc);
    
                		   		if($existing_boxinfo_position_id_storage_location != "0A_0B_0S_0P_NOT-ASSIGNED" ){
                		   		    
                		   		    /* If the proposed storage position scanned on the shelf matches the initial auto=selected/manual location storage position (assigned) */
                                    if($new_position_id_storage_location == $existing_boxinfo_position_id_storage_location){
                                            
                                            /* Change the box status has been changed from "on cart" to "on shelf" */

//Update Boxinfo Table
$boxshelf_boxinfo_update = array('location_status_id' => 2);
$boxshelf_boxinfo_where = array('box_id' => $box_shelf);
$wpdb->update($table_box, $boxshelf_boxinfo_update, $boxshelf_boxinfo_where);

$get_boxshelf_ticket_id = $wpdb->get_row(
"SELECT DISTINCT ticket_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE box_id = '" . $box_shelf . "' LIMIT 1");
$boxshelf_ticket_id = $get_boxshelf_ticket_id->ticket_id;

//Does location exist in Scan List Table?

$check_boxshelf_id = $wpdb->get_row(
"SELECT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id = '" . $box_shelf . "'");
$boxshelf_dbid = $check_boxshelf_id->id;

if (empty($boxshelf_dbid)) {
$wpdb->insert(
				$table_scan_list,
				array(
					'box_id' => $box_shelf,
					'shelf_location' => $location
				)
);  
// Get back DB ID
$get_box_id = $wpdb->get_row(
"SELECT DISTINCT id FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id = '" . $box_shelf . "'");
$box_id_dbid = $get_box_id->id;
// Update Box Info Table
$box_boxinfodbid_update = array('scan_list_id' => $box_id_dbid);
$box_boxinfodbid_where = array('box_id' => $box_shelf);
$wpdb->update($table_box, $box_boxinfodbid_update, $box_boxinfodbid_where);
Patt_Custom_Func::pallet_cleanup();
} else {
$boxshelf_scanlist_update = array('cart_id' => NULL, 'scanning_id' => NULL, 'stagingarea_id' => NULL, 'shelf_location' => $location,'date_modified' => $date);
$boxshelf_scanlist_where = array('id' => $boxshelf_dbid);
$wpdb->update($table_scan_list , $boxshelf_scanlist_update, $boxshelf_scanlist_where);
}


                                            /* Notify the user that the box status has been changed from "on cart" to "on shelf" */
                                            $message = "Updated: Box ID " . $box_shelf . " has been placed on shelf: " . $new_position_id_storage_location ."<br />";
                                            do_action('wpppatt_after_shelf_location', $boxshelf_ticket_id, $box_shelf, $message);
                                            echo $message;
                                    } else {
                            		    //Works
                            			$message = "Not Updated: The scanned location ". $new_position_id_storage_location . " does not match the assigned shelf location for the box. Please select another location and try again.<br />";
                                        echo $message;
                                        exit;
                                    }
                		   		}else{
                		   		    
                   		   		    $message = "Not Updated: The location ". $existing_boxinfo_position_id_storage_location ." is already assigned. Please select another location and try again.<br />";
                                    echo $message;
                                    exit;
                		   		}
            				}else{
            				    //Works
            				    $message = "Not Updated: The location ". $new_position_id_storage_location . " does not exist in the facility. Please select another location and try again.<br />";
                                echo $message;
                                exit;
                			}	
                        }else{
                            $message = "Not Updated. The Location Scan cannot be assigned to multiple Box ID's.<br />";
                            echo $message;
                            exit;
                        }
                    }
                    
                    } //end loop

//Clear RFID Dashboard
if($pageinfo == 'rfid'){

foreach($boxpallet_arr as $items) {

$table_name = $wpdb->prefix . 'wpsc_epa_rfid_data';

$wpdb->delete($table_name, array( 'Reader_Name' => $location, 'box_id' => $items) );

}

}

} else {
   echo "Please ensure all box/pallet IDs are valid.";
}
 } else {
   echo "Please enter a valid location.";
}
} else {
   echo "Update not successful.";
}
?>