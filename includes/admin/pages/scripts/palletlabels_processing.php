<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(isset($_POST['postvarsboxid'])){
    
$box_ids = $_POST['postvarsboxid'];

$box_id_arr = explode(",", $box_ids);

$pallet_arr_final = array();

foreach($box_id_arr as $key => $value) { 
    
$get_pallet_id = $wpdb->get_row("
SELECT pallet_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE box_id = '" . $value . "'
");
$pallet_id_final = $get_pallet_id->pallet_id;

if(!empty($pallet_id_final)) {
   array_push($pallet_arr_final, $pallet_id_final); 
}

}

$palletidarray_val = implode(',', array_unique($pallet_arr_final));

echo $palletidarray_val;

} else {
   echo "Update not successful.";
}
?>