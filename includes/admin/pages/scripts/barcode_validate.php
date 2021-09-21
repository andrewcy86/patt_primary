<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');
?>

<?php
//Define tables
$table_box = $wpdb->prefix . "wpsc_epa_boxinfo";
$table_scan_list = $wpdb->prefix . "wpsc_epa_scan_list";

if(isset($_POST['postvarsbarcode'])){
   $barcode = $_POST['postvarsbarcode'];

if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $barcode)){

$get_folderdocinfo_id = $wpdb->get_row("SELECT id
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '".$barcode."'");

$get_folderdocinfo_id_val = $get_folderdocinfo_id->id;

$data_update = array('validation' => 1);
$data_where = array('id' => $get_folderdocinfo_id_val);
$wpdb->update($wpdb->prefix.'wpsc_epa_folderdocinfo_files', $data_update, $data_where);

echo $barcode.' has been set to validated.';

//FAIL for all other barcodes       
} else {

echo 'Please enter a valid barcode.';

}

} else {
   echo "Lookup not successful.";
}
?>