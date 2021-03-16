<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(!empty($_POST['postvarsfolderdocid'])){

$folderdocid_string = $_POST['postvarsfolderdocid'];
$folderdocid_arr = explode (",", $folderdocid_string); 
$page_id = $_POST['postvarpage'];
$box_id = $_POST['boxid'];

$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$damaged_reversal = 0;
$completed_dispositioned = 0;
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
$unauthorized_destruction = 0;

foreach($folderdocid_arr as $key) {
$get_completed_dispositioned = $wpdb->get_row("SELECT a.box_status
FROM wpqa_wpsc_epa_boxinfo a 
INNER JOIN wpqa_wpsc_epa_folderdocinfo b ON b.box_id = a.id
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE c.damaged = 0 AND c.folderdocinfofile_id = '".$key."'");
$get_completed_dispositioned_val = $get_completed_dispositioned->box_status;

//Documents cannot be marked as Damaged in the Completed/Dispositioned box status
if($get_completed_dispositioned_val == $completed_dispositioned_tag->term_id) {
   $completed_dispositioned++; 
}
}

foreach($folderdocid_arr as $key) {
$get_unauthorized_destruction = $wpdb->get_row("SELECT unauthorized_destruction 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_unauthorized_destruction_val = $get_unauthorized_destruction->unauthorized_destruction;

//Documents cannot be marked as Damaged if they are already marked as Unauthorized Destruction
if ($get_unauthorized_destruction_val == 1) {
$unauthorized_destruction++;
}
}

//$folderdocid_arr_count = count($folderdocid_arr);

if(($page_id == 'boxdetails' || $page_id == 'folderfile') && $completed_dispositioned == 0 && $unauthorized_destruction == 0) {
foreach($folderdocid_arr as $key) {

$get_damaged = $wpdb->get_row("SELECT damaged FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$key."'");
$get_damaged_val = $get_damaged->damaged;

$get_request_id = substr($key, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

if ($get_damaged_val == 1){
$damaged_reversal = 1;
$data_update = array('damaged' => 0);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_damaged_unflag', $ticket_id, $key);

echo "<strong>".$key."</strong> : Damaged has been updated. A damaged flag has been reversed.<br />";
}

if ($get_damaged_val == 0){
$data_update = array('damaged' => 1);
$data_where = array('folderdocinfofile_id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_damaged', $ticket_id, $key);

echo "<strong>".$key."</strong> : Damaged has been updated.<br />";
}
}
}

elseif($unauthorized_destruction > 0) {
echo "A folder/file flagged as unauthorized destruction has been selected and cannot be flagged as damaged.<br />Please unselect the folder/file flagged as unauthorized destruction.";
} elseif($completed_dispositioned > 0) {
echo "A folder/file is in the box status of Completed/Dispositioned and cannot be flagged as damaged.<br />Please unselect the folder/file in the box status of Completed/Dispositioned.";
}

if( ($page_id == 'filedetails') && $completed_dispositioned == 0 && $unauthorized_destruction == 0) {
$get_damaged = $wpdb->get_row("SELECT damaged FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '".$folderdocid_string."'");
$get_damaged_val = $get_damaged->damaged;

$get_request_id = substr($folderdocid_string, 0, 7);
$get_ticket_id = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '".$get_request_id."'");
$ticket_id = $get_ticket_id->id;

if ($get_damaged_val == 1){
$damaged_reversal = 1;
$data_update = array('damaged' => 0);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_damaged_unflag', $ticket_id, $folderdocid_string);

}

if ($get_damaged_val == 0){
$data_update = array('damaged' => 1);
$data_where = array('folderdocinfofile_id' => $folderdocid_string);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_damaged', $ticket_id, $folderdocid_string);
}
}
if (($page_id == 'filedetails') && $damaged_reversal == 1 && $completed_dispositioned == 0 && $unauthorized_destruction == 0) {
echo "Damaged has been updated. A damaged flag has been reversed.";
}
if (($page_id == 'filedetails') && $damaged_reversal == 0 && $completed_dispositioned == 0 && $unauthorized_destruction == 0) {
echo "Damaged has been updated.";
}
   
} else {
   echo "Please select one or more items to mark as damaged.";
}
?>