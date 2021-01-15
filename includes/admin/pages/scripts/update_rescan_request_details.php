<?php

global $wpdb, $current_user, $wpscfunction;

$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php');

if(
!empty($_POST['postvarselection']) || !empty($_POST['postvarsticketid']) 
){

$ticket_id = $_POST['postvarsticketid'];

$folderdocid_arr = explode (",", $_POST['postvarselection']); 


foreach($folderdocid_arr as $key) {

            $get_folderdbid = $wpdb->get_row("SELECT id
FROM wpqa_wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '" . $key . "'");
            
            $folderdbid = $get_folderdbid->id;
            
$table_name = 'wpqa_wpsc_epa_folderdocinfo_files';
$data_update = array('rescan' => 0);
$data_where = array('id' => $folderdbid);

$wpdb->update($table_name , $data_update, $data_where);

do_action('wpppatt_after_undo_rescan_document', $ticket_id, $key);
echo "<strong>".$key."</strong> : Re-scan has been updated. A re-scan flag has been reversed.<br />";
}

} else {
   echo "Please select one or more items to unflag re-scan.";
}
?>