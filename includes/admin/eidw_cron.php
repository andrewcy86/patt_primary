<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

//include_once( WPPATT_UPLOADS . 'api_authorization_strings.php' );

global $current_user, $wpscfunction, $wpdb;


$lanid_query = $wpdb->get_results(
"
SELECT 
DISTINCT a.id as id, a.lan_id as lan_id, a.lan_id_details as lan_id_details, c.request_id as request_id from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id INNER JOIN " . $wpdb->prefix . "wpsc_ticket c ON b.ticket_id = c.id WHERE a.lan_id <> ''
"
);

foreach ($lanid_query as $lan_id) {

$id_val = $lan_id->id;
$lan_id_val = $lan_id->lan_id; 
$lan_id_details_val = $lan_id->lan_id_details; 
$request_id_val = $lan_id->request_id; 

$curl = curl_init();

$url = 'https://wamssoprd.epa.gov/iam/governance/scim/v1/Users?filter=userName%20eq%20'.$lan_id_val;

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
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($curl);

$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

curl_close($curl);

$err = Patt_Custom_Func::convert_http_error_code($status);

if ($status != 200) {
Patt_Custom_Func::insert_api_error('eidw-eidw-cron',$status,$err);
} else {

$json = json_decode($response, true);

$active = $json['Resources']['0']['active'];
$full_name = $json['Resources']['0']['name']['givenName'].' '.$json['Resources']['0']['name']['familyName'];
$email = $json['Resources']['0']['emails']['0']['value'];
$phone = $json['Resources']['0']['phoneNumbers']['0']['value'];
$org = $json['Resources']['0']['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User']['department'];

//get LAN ID to compare on the box details page
$lan_id_username = $json['Resources'][0]['userName'];

if ($active != 1) {
$find_requester = $wpdb->get_row("SELECT a.user_login as user_login FROM " . $wpdb->prefix . "users a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON a.user_email = b.customer_email WHERE b.request_id = '" . $request_id_val . "'");

$requester_lanid = $find_requester->user_login;

$folderdocinfo_files_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$data_lan_update = array('lan_id' => $requester_lanid);
$data_lan_where = array('id' => $id_val);
$wpdb->update($folderdocinfo_files_table, $data_lan_update, $data_lan_where);
}

if ($active == 1) {

$id_query = $wpdb->get_results("SELECT DISTINCT id from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE lan_id = '" . $lan_id_val . "'");



foreach ($id_query as $lan_id_update) {
$db_lan_id = $lan_id_update->id ;

// Declare array  
$lan_id_details_array = array( 
    "name"=>$full_name,
    "email"=>$email,
    "phone"=>$phone,
    "org"=>$org,
    "lan_id"=>$lan_id_username,
); 
   
// Use json_encode() function 
$json = json_encode($lan_id_details_array); 
   
// Display the output 
echo($json); 
   
   
$lan_id_details = $full_name.','.$email.','.$phone.','.$org.','.$lan_id_username;

// Detects update to contact info, if yes then update table
if ($lan_id_details != $lan_id_details_val && $lan_id_details != 'Error')
{
$folderdocinfo_files_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$data_update = array('lan_id_details' => $json);
$data_where = array('id' => $db_lan_id);
$wpdb->update($folderdocinfo_files_table, $data_update, $data_where);
}

}

}

//echo $lan_id_details;
//print_r($response);

}
}

?>