<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

//Define tables
$table_box = $wpdb->prefix . "wpsc_epa_boxinfo";
$table_scan_list = $wpdb->prefix . "wpsc_epa_scan_list";
$storage_location_table = $wpdb->prefix . 'wpsc_epa_storage_location';

if(isset($_POST['postvarsbarcode']) && isset($_POST['postvarsuser'])){
   $barcode = $_POST['postvarsbarcode'];
   $user_id = $_POST['postvarsuser'];

$get_folderdocinfo_id = $wpdb->get_row("SELECT id, box_id, object_key
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE folderdocinfofile_id = '".$barcode."'");

$get_folderdocinfo_id_val = $get_folderdocinfo_id->id;
$get_folderdocinfo_box_id_val = $get_folderdocinfo_id->box_id;

$box_details = $wpdb->get_row("SELECT a.box_status as box_status, b.ticket_status as ticket_status, b.id as ticket_id, a.box_id as box_id, a.storage_location_id as storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON b.id = a.ticket_id
WHERE a.id = '" . $get_folderdocinfo_box_id_val . "'");

$box_full_id = $box_details->box_id;
$box_status = $box_details->box_status;
$box_ticket_status = $box_details->ticket_status;
$box_storage_location_id = $box_details->storage_location_id;
$box_ticket_id = $box_details->ticket_id;

$rescan_validate_status_id_arr = array($request_new_request_tag->term_id, $request_tabled_tag->term_id, $request_initial_review_rejected_tag->term_id, $request_cancelled_tag->term_id, $request_completed_dispositioned_tag->term_id);

if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $barcode)){
    
if ( ($box_status == $box_validation_tag->term_id) && !in_array($box_ticket_status, $rescan_validate_status_id_arr)) {

    
$data_update = array('validation' => 1, 'validation_user_id' => $user_id);
$data_where = array('id' => $get_folderdocinfo_id_val);
$wpdb->update($wpdb->prefix.'wpsc_epa_folderdocinfo_files', $data_update, $data_where);

do_action('wpppatt_after_validate_document', $box_ticket_id, $barcode);

// Get all validations in a box vs. total files
$get_total_files = $wpdb->get_row("SELECT COUNT(id) as total_count
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE box_id = " . $get_folderdocinfo_box_id_val);
$total_files = $get_total_files->total_count;

$get_total_validation = $wpdb->get_row("SELECT COUNT(validation) as total_validation
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE validation = 1 AND box_id = " . $get_folderdocinfo_box_id_val);
$total_validation = $get_total_validation->total_validation;

if( ($total_files - $total_validation) == 0) {
  $data_update_todo = array('scanning_preparation' => 1, 'scanning_digitization' => 1, 'qa_qc' => 1, 'validation' => 1);
  $data_where_todo = array('id' => $box_storage_location_id);
  $wpdb->update($storage_location_table, $data_update_todo, $data_where_todo);
  
  $old_status_str = Patt_Custom_Func::get_box_status($get_folderdocinfo_box_id_val);
  $new_status_str = $box_waiting_on_rlo_tag->name;
  $status_str = $old_status_str . ' to ' . $new_status_str;
  do_action('wpppatt_after_box_status_update', $box_ticket_id, $status_str, $box_full_id);
  
  // Set box status to Destruction Approved and previous box status
  $data_update_box_status = array('box_status' => $box_waiting_on_rlo_tag->term_id, 'box_previous_status' => $box_status);
  $data_where_box_status = array('id' => $get_folderdocinfo_box_id_val);
  $wpdb->update($table_box, $data_update_box_status, $data_where_box_status);
}

$curl = curl_init();

//Call disposition endpoint

//Check to see if diposition exists

//Check for litigation hold

//Check for AA'ship and Custodian

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/site/automation/ARMSDeclareRecord',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{"input": "6926ebe1-b53d-4ff1-aa32-3fab7a82d6f8","params": {"disposition": "2027-09-08T19:13:04.107Z","retention": "false","legalhold": "false","sensitive": "false","aaship": "H0000000","move": "true","custodian": "BFONTENO"}}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json+nxrequest',
    'Accept: application/json',
    'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
  ),
));


$response = curl_exec($curl);

curl_close($curl);

//Handle Error and Print to alert user.

echo '<div class="alert alert-success"><i class="fas fa-check-circle" aria-hidden="true" title="Validated"></i><span class="sr-only">Validated</span> '.$barcode.' has been set to validated.</div>';

/*
        $filename = 'LDF_1_2_6_ldf_09019588800598d8';
        $file = WPPATT_UPLOADS."/validation-temp/".$filename."_content.zip";
        
    if (file_exists($file)) {
        unlink($file);
        echo 'file delete';
    } else {
        echo 'no file delete';
    }
*/

//FAIL for all other barcodes       
} else {
echo 'Please ensure the box and/or request statuses are correct.';
}

} else {
echo 'Please enter a valid folder/file id.';
}

} else {
   echo "Lookup not successful.";
}
?>