<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');
include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

$status_list = $request_new_request_tag->term_id .",". $request_initial_review_rejected_tag->term_id .",". $request_cancelled_tag->term_id .",". $request_completed_dispositioned_tag->term_id;

$count = 0;

if(isset($_POST['postvarsboxid'])){
    
$box_ids = $_POST['postvarsboxid'];

$box_id_arr = explode(",", $box_ids);

$box_count = count($box_id_arr);

$pallet_arr_final = array();

foreach($box_id_arr as $key => $value) { 
    
$get_pallet_id = $wpdb->get_row("
SELECT a.pallet_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON a.ticket_id = b.id 
WHERE (b.ticket_status NOT IN (" . $status_list . ")) AND a.box_id = '" . $value . "'
");
$pallet_id_final = $get_pallet_id->pallet_id;

if(!empty($pallet_id_final)) {
   array_push($pallet_arr_final, $pallet_id_final); 
} else {
$count++;
}

}

$palletidarray_val = implode(',', array_unique($pallet_arr_final));

if ($count == 0) {
echo 'true'.'|'.$palletidarray_val;
} else {
echo 'false'.'|'.$palletidarray_val;
}

} else {
   echo "Update not successful.";
}
?>