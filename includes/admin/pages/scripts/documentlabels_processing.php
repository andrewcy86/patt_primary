<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003
 
$status_list = $new_request_tag->term_id .",". $initial_review_rejected_tag->term_id .",". $cancelled_tag->term_id .",". $completed_dispositioned_tag->term_id;



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
INNER JOIN " . $wpdb->prefix . "wpsc_ticket e ON c.ticket_id = e.id 

WHERE (e.ticket_status NOT IN (" . $status_list . ")) AND (d.digitization_center <> 666) AND 
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