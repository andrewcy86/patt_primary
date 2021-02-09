<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$folderdocinfo_id = $_POST["postvarsffid"];

$metadata_array = array();

$get_folderdocinfo_files = $wpdb->get_results("SELECT folderdocinfofile_id 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files 
WHERE folderdocinfo_id = '" . $folderdocinfo_id . "'");

foreach($get_folderdocinfo_files as $item) {
    $data_update = array('approve_ingestion' => 1);
    $data_where = array('folderdocinfo_id' => $folderdocinfo_id);
    $wpdb->update($wpdb->prefix . 'wpsc_epa_folderdocinfo_files' , $data_update, $data_where);
}

echo "Folderdocinfo ID #: " . $folderdocinfo_id . " has uploaded all associated attachments to ECMS.";

?>