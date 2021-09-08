<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['boxid'])
){

$db_box_id = $_POST['boxid'];

$box_scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); //672

$get_box_info = $wpdb->get_row("SELECT box_id, ticket_id, storage_location_id, box_status
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE id = '".$db_box_id."'");

$box_id = $get_box_info->box_id;
$ticket_id = $get_box_info->ticket_id;
$storage_location_id = $get_box_info->storage_location_id;
$box_status_id = $get_box_info->box_status;

$table_name = $wpdb->prefix . 'wpsc_epa_storage_location';
$boxinfo_table = $wpdb->prefix . 'wpsc_epa_boxinfo';

$get_status_info = $wpdb->get_row("SELECT (scanning_preparation+scanning_digitization+qa_qc+validation+destruction_approved+destruction_of_source) as count
FROM " . $wpdb->prefix . "wpsc_epa_storage_location
WHERE id = '".$storage_location_id."'");
$count = $get_status_info->count;

if ($count != 0) {
// Old box status and new box status audit log action
$old_status_str = Patt_Custom_Func::get_box_status($db_box_id);
$new_status_str = $box_scanning_preparation_tag->name;
$status_str = $old_status_str . ' to ' . $new_status_str;

$data_update_boxstatus = array('box_status' => $box_scanning_preparation_tag->term_id, 'box_previous_status' => $box_status_id);
$data_where_boxstatus = array('id' => $db_box_id);
$wpdb->update($boxinfo_table , $data_update_boxstatus, $data_where_boxstatus);
do_action('wpppatt_after_box_status_update', $ticket_id, $status_str, $box_id);
    
$data_update = array('scanning_preparation' => 0,
'scanning_digitization' => 0,
'qa_qc' => 0,
'validation' => 0,
'destruction_approved' => 0,
'destruction_of_source' => 0);
$data_where = array('id' => $storage_location_id);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpppatt_after_reset_box_completion_status', $ticket_id, $box_id);

  echo 'The box completion status has been reset for the following box: #'.$box_id ;
} else {
  echo 'The box completion status cannot be reset';    
}
} else {
   echo "An error has occured.";
}
?>