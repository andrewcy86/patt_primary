<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003
 
$status_id_arr = array($new_request_tag->term_id, $initial_review_rejected_tag->term_id, $cancelled_tag->term_id, $completed_dispositioned_tag->term_id);

if(isset($_POST['postvarsboxid'])){
    
$box_ids = $_POST['postvarsboxid'];

$box_arr = explode(",", $box_ids);

$box_count = count($box_arr);

$count = 0;

$boxidarray = array();
$tabledarray = array();

foreach($box_arr as $key => $value) { 
    
$get_destroy_status = $wpdb->get_row("
SELECT a.box_destroyed, c.ticket_status as ticket_status, a.box_id, b.aisle, b.bay, b.shelf, b.position, b.digitization_center
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b on a.storage_location_id = b.id 
INNER JOIN " . $wpdb->prefix . "wpsc_ticket c on a.ticket_id = c.id 
WHERE a.box_id = '" . $value . "'
");

$destroy_status = $get_destroy_status->box_destroyed;
$box_id = $get_destroy_status->box_id;
$ticket_status = $get_destroy_status->ticket_status;

//checking assigned location
$aisle = $get_destroy_status->aisle;
$bay = $get_destroy_status->bay;
$shelf = $get_destroy_status->shelf;
$position = $get_destroy_status->position;
$digitization_center = $get_destroy_status->digitization_center;

if ($ticket_status == $tabled_tag->term_id) {
array_push($tabledarray, $ticket_status);
}

if (!in_array($ticket_status, $status_id_arr) || ($destroy_status == 0 && $digitization_center != 666)) {
array_push($boxidarray, $box_id);
}

if (in_array($ticket_status, $status_id_arr) || $destroy_status == 1 || $digitization_center == 666) {
$count++;
}

}

$boxidarray_val = implode(',', $boxidarray);

if (count($tabledarray) == 0 || count($tabledarray) != $box_count) {
if ($box_count == $count || (count($tabledarray) > 0 && count($tabledarray) != $box_count)) {
echo 'false'.'|'.$boxidarray_val;
} else {

if ($count < $box_count && $count != 0 ) {
echo 'warn'.'|'.$boxidarray_val;
}

if ($count < $box_count && $count == 0) {
echo 'true'.'|'.$boxidarray_val;
}

}

}

if (count($tabledarray) > 0 && count($tabledarray) == $box_count) {
if ($count < $box_count && $count == 0) {
echo 'true_tabled'.'|'.$boxidarray_val;
}
}


} else {
   echo "Update not successful.";
}
?>