<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

//include_once( WPPATT_UPLOADS . 'api_authorization_strings.php' );

if(!empty($_POST['postvarslanid'])){
   //id in box table (e.g. 1)
   $box_id = $_POST['postvarsboxid'];
   //box_id in box table (e.g. 0000001-1)
   //$pattboxid = $_POST['postvarspattboxid'];
   $lanid = $_POST['postvarslanid'];
   $pattdocid = $_POST['postvarspattdocid'];
   $dbid = $_POST['postvarsdbid'];

$curl = curl_init();

$url = 'https://wamssoprd.epa.gov/iam/governance/scim/v1/Users?filter=userName%20eq%20'.$lanid;
$eidw_authorization = 'Authorization: Basic '.EIDW;
$headers = [
    'Cache-Control: no-cache',
	$eidw_authorization
];

        curl_setopt($curl,CURLOPT_URL, $url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl,CURLOPT_TIMEOUT, 30);
        curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($curl);

$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

curl_close($curl);

$err = Patt_Custom_Func::convert_http_error_code($status);

if ($status != 200) {
Patt_Custom_Func::insert_api_error('eidw-update-epa-contact',$status,$err);
} else {

$json = json_decode($response, true);

$active = $json['Resources']['0']['active'];

if ($active == 1) {

$get_ticket_id = $wpdb->get_row("SELECT ticket_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE id = '" . $box_id . "'");
$ticket_id = $get_ticket_id->ticket_id;

$metadata_array = array();
$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$old_box_lanid = $wpdb->get_row("SELECT lan_id FROM " . $table_name . " WHERE id = '" . $dbid . "'");
$old_lanid = $old_box_lanid->lan_id;

//$folderfile_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo';

//updates the epa contact by entering a LANID
if(!empty($lanid) && $old_lanid != $lanid) {
$get_json = Patt_Custom_Func::lan_id_to_json($lanid);
$data_update = array('lan_id' => $lanid, 'lan_id_details' => $get_json);
$data_where = array('id' => $dbid);
array_push($metadata_array,'EPA Contact: '.$old_lanid.' > '.$lanid);
$wpdb->update($table_name, $data_update, $data_where);

$metadata = implode (", ", $metadata_array);

do_action('wpppatt_after_folder_doc_metadata', $ticket_id, $metadata, $pattdocid);

//sends email/notification to user when epa contact is updated
$get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
$get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');
$user_id_array = [$get_user_id->ID];
$convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
$patt_agent_id = implode($convert_patt_id);
$pattagentid_array = [$patt_agent_id];
$data = [];

$email = 1;

Patt_Custom_Func::insert_new_notification('email-epa-contact-changed',$pattagentid_array,$pattdocid,$data,$email);

echo "Folder/File ID #: " . $pattdocid . " has been updated.";
}
} else { echo "Please enter a valid LAN ID"; }
}
} else {
   //echo $pattboxid;
   echo "Please make an edit.";
}
?>