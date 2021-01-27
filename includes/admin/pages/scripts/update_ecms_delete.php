<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['postvarscomment']) || !empty($_POST['postvarsattachid']) || !empty($_POST['postvarsfolderfileid'])
){
 
$comment = $_POST['postvarscomment'];
$folderfileid = $_POST['postvarsfolderfileid'];
$dbid_string = $_POST['postvarsattachid'];
$dbid_arr = explode (",", $dbid_string);

$table_name = 'wpqa_wpsc_epa_folderdocinfo_files';
$timestamp = date("Y-m-d H:i:s"); 

$object_ids = array();

foreach($dbid_arr as $key => $value):
$data_update = array('ecms_delete_timestamp' => $timestamp, 'ecms_delete_comment' => $comment);
$data_where = array('id' => $value);
$wpdb->update($table_name, $data_update, $data_where);

$get_object_id = $wpdb->get_row("select file_object_id from wpqa_wpsc_epa_folderdocinfo_files where id = '".$value."'");

array_push($object_ids, $get_object_id->file_object_id);

endforeach;

Patt_Custom_Func::insert_ecms_notification($folderfileid, $comment, $object_ids);

 echo "ECMS Delete Request has been submitted.";
 
} else {
   echo "ECMS Delete Request has not been submitted.";
}
?>