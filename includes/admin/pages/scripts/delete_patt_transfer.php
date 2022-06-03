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

//var_dump('request id ' . $requestid_string);
//var_dump('request arr ' . $requestid_arr);

$get_ticket_requestid_val_array = array();

foreach($requestid_arr as $key) {
$get_ticket_status = $wpdb->get_row("SELECT id, folderdocinfofile_id FROM " . $wpdb->prefix . "epa_patt_arms_logs WHERE folderdocinfofile_id = '".$key."'");
$get_ticket_id_val = $get_ticket_status->id;
$get_folderdocinfofile_id_val = $get_ticket_status->folderdocinfofile_id;

//var_dump($get_ticket_status);
//var_dump($key);




//BEGIN CLONING DATA TO ARCHIVE
Patt_Custom_Func::send_to_patt_transfer_archive($get_folderdocinfofile_id_val);

//Archive audit log

Patt_Custom_Func::audit_log_backup($key);


echo '<strong>'.$get_folderdocinfofile_id_val.'</strong> - Has been moved to the Archive.<br />';

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


?>