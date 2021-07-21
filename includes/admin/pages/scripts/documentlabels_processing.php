<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(isset($_POST['postvarsfolderdocid'])){
    
$document_ids = $_POST['postvarsfolderdocid'];
$document_array = explode(",", $document_ids);
$document_count = count($document_array);
$total_count = 0;
$folderdocarray = array();

foreach($document_array as $key => $value) { 
//REVIEW
$get_document = $wpdb->get_row("SELECT a.folderdocinfofile_id as folderdocinfofile_id
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo c ON a.box_id = c.id 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location d on c.storage_location_id = d.id 
WHERE (d.digitization_center <> 666) AND 
(c.box_destroyed = 0) AND a.folderdocinfofile_id = '" . $value . "'");
$document = $get_document->folderdocinfofile_id;

if ($document != '') {
array_push($folderdocarray, $document);
}

if ($document == '') {
$total_count++;
}

}

$folderdocarray_val = implode(',', $folderdocarray);

if ($document_count == $total_count) {
echo 'false'.'|'.$folderdocarray_val;
}

if ($total_count < $document_count && $total_count != 0) {
echo 'warn'.'|'.$folderdocarray_val;
}

if ($total_count < $document_count && $total_count == 0) {
echo 'true'.'|'.$folderdocarray_val;
}

} else {
   echo "Update not successful.";
}
?>