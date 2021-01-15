<?php

global $wpdb, $current_user, $wpscfunction;

$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php');

$folderdocinfo_id = $_POST["postvarsffid"];

$metadata_array = array();

$get_folderdocinfo_files = $wpdb->get_results("SELECT folderdocinfofile_id 
FROM wpqa_wpsc_epa_folderdocinfo_files 
WHERE folderdocinfo_id = '" . $folderdocinfo_id . "'");

foreach($get_folderdocinfo_files as $item) {
    $data_update = array('approve_ingestion' => 1);
    $data_where = array('folderdocinfo_id' => $folderdocinfo_id);
    $wpdb->update('wpqa_wpsc_epa_folderdocinfo_files' , $data_update, $data_where);
}

echo "Folderdocinfo ID #: " . $folderdocinfo_id . " has uploaded all associated attachments to ECMS.";

?>