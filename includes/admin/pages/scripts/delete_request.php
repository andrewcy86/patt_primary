<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');
	

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['postvarsrequest_id'])
){

$requestid_string = $_POST['postvarsrequest_id'];
$requestid_arr = explode (",", $requestid_string);  

//print_r($requestid_arr);

$get_ticket_requestid_val_array = array();

foreach($requestid_arr as $key) {
$get_ticket_status = $wpdb->get_row("SELECT id, request_id, ticket_status FROM " . $wpdb->prefix . "wpsc_ticket WHERE id = '".$key."'");
$get_ticket_id_val = $get_ticket_status->id;
$get_ticket_requestid_val = $get_ticket_status->request_id;
$get_ticket_status_val = $get_ticket_status->ticket_status;

array_push($get_ticket_requestid_val_array, $get_ticket_requestid_val);

$recall_decline = 0;
$status = 0;

$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003

//Checking if there is a recall/decline in a request
if( (Patt_Custom_Func::id_in_recall($get_ticket_requestid_val, 'request') == 1) || (Patt_Custom_Func::id_in_return($get_ticket_requestid_val, 'request') == 1) ) {
    $recall_decline = 1;
}

$get_box_status = $wpdb->get_results("SELECT box_status FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE ticket_id = '".$key."'");

$get_box_status_array = array();

foreach ($get_box_status as $boxstatus) {
	array_push($get_box_status_array, $boxstatus->box_status);
	}

$box_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
$box_cancelled_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses'); //1057

//print_r($get_box_status_array);
//If all boxes are in the status of Completed/Dispositioned and in the correct request status they can be archived
if( count(array_unique($get_box_status_array)) == 1 && in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array ) && $get_ticket_status_val == $completed_dispositioned_tag->term_id) {
    $status = 1;
}

//If all boxes are in the status of Cancelled and in the correct request status they can be archived
if( count(array_unique($get_box_status_array)) == 1 && in_array($box_cancelled_tag->term_id, $get_box_status_array ) && $get_ticket_status_val == $cancelled_tag->term_id) {
    $status = 1;
}

//If there are a mix of Completed/Dispositioned and Cancelled boxes and they are in the request status Completed/Dispositioned they can be archived
if( count(array_unique($get_box_status_array)) == 2 && ( in_array($box_completed_dispositioned_tag->term_id, $get_box_status_array) && in_array($box_cancelled_tag->term_id, $get_box_status_array) ) && $get_ticket_status_val == $completed_dispositioned_tag->term_id) {
    $status = 1;
}

$table_name = $wpdb->prefix . 'wpsc_ticket';

if($status == 1 && $recall_decline == 0) {
$data_update = array('active' => 0);
$data_where = array('id' => $key);
$wpdb->update($table_name , $data_update, $data_where);
do_action('wpsc_after_recycle', $key);

//BEGIN CLONING DATA TO ARCHIVE
Patt_Custom_Func::send_to_archive($key);

//Archive audit log

Patt_Custom_Func::audit_log_backup($key);


echo '<strong>'.$get_ticket_requestid_val.'</strong> - Has been moved to the Archive.<br />';

//sends email/notification to admins/managers when request is deleted
$agent_admin_group_name = 'Administrator';
$pattagentid_admin_array = Patt_Custom_Func::agent_from_group($agent_admin_group_name);
$agent_manager_group_name = 'Manager';
$pattagentid_manager_array = Patt_Custom_Func::agent_from_group($agent_manager_group_name);
$pattagentid_array = array_merge($pattagentid_admin_array,$pattagentid_manager_array);
$data = [];

//disabled email notification
$email = 1;

//insert notification for each request
Patt_Custom_Func::insert_new_notification('email-request-deleted',$pattagentid_array,$get_ticket_requestid_val,$data,$email);

}


}

if($status == 0 || $recall_decline == 1) {
    
$implode_array = implode(', ',$get_ticket_requestid_val_array);
    
echo '<strong>'. $implode_array. ' Request must be: </strong><br /><br />
    <ol>
        <li>In the status of Completed/Dispositioned and all boxes under the request must be in the Completed/Dispositioned status or</li>
        <li>In the status of Cancelled and all boxes under the request must be in the Cancelled status or</li>
        <li>In the status of Completed/Dispositioned and all boxes under the request must either be in the Completed/Dispositioned or Cancelled status.</li>
    </ol>
    And no documents/boxes within the request can be in recall or decline.<br />';
}

} else {
   echo "Please select one or more items to delete.";
}
?>