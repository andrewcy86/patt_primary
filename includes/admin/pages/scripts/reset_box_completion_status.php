<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['boxid'])
){

$db_box_id = $_POST['boxid'];

$get_box_info = $wpdb->get_row("SELECT box_id, ticket_id, storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE id = '".$db_box_id."'");

$box_id = $get_box_info->box_id;
$ticket_id = $get_box_info->ticket_id;
$storage_location_id = $get_box_info->storage_location_id;

$table_name = $wpdb->prefix . 'wpsc_epa_storage_location';

$get_status_info = $wpdb->get_row("SELECT (scanning_preparation+scanning_digitization+qa_qc+validation+destruction_approved+destruction_of_source) as count
FROM " . $wpdb->prefix . "wpsc_epa_storage_location
WHERE id = '".$storage_location_id."'");
$count = $get_status_info->count;

if ($count != 0) {
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