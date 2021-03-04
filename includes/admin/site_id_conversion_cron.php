<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');
*/

global $current_user, $wpscfunction, $wpdb;

//Populate Site Name from Site ID

//get all files missing site names with site ids
$get_file_info = $wpdb->get_results("SELECT id, siteid
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo
WHERE (site_name IS NULL OR site_name = '') AND siteid IS NOT NULL");

foreach($get_file_info as $item) {
    $folderdocinfo_id = $item->id;
    $siteid = $item->siteid;

//SEMS SiteID
//get first 2 characters and cross check with array

$siteid_pre = substr($siteid, 0,2);
$valid_prefix = array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11");

//check to ensure valid SEMS siteid
//3 checks prefixed with region, 7 characters, all numbers
if (in_array($siteid_pre, $valid_prefix) && is_numeric($siteid) && (strlen($siteid) == 7)) {
$curl = curl_init();

curl_setopt_array($curl, array(
//current API does not support 11
  CURLOPT_URL => "https://semspub.epa.gov/src/sitedetails/".$siteid,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
));

$response = curl_exec($curl);
$err = curl_error($curl);
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($err) {
    //Write to error table
    echo "cURL Error #:" . $err;
    
  $wpdb->insert( $wpdb->prefix . 'epa_error_log', 
    array(
      'Status_Code'  => $http_status,
      'Error_Message' => $err,
      'Service_Type' => 'sems_siteinfo'
    ));
    
} else {
    $json = json_decode($response, true);
    $data_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo';
    $data_update = array('site_name' => $json['data']['0']['sitename']);
    $data_where = array('id' => $folderdocinfo_id);
    $wpdb->update($data_table , $data_update, $data_where);
}
}

//FRS RegistryId
//Check if 12 numbers
if (is_numeric($siteid) && (strlen($siteid) == 12)) {
$curl = curl_init();

curl_setopt_array($curl, array(
//current API does not support 11
  CURLOPT_URL => "https://ofmpub.epa.gov/enviro/frs_rest_services.get_facilities?REGISTRY_ID=".$siteid."&output=JSON",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
));

$response = curl_exec($curl);
$err = curl_error($curl);
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($err) {
    //Write to error table
    echo "cURL Error #:" . $err;
    
  $wpdb->insert( $wpdb->prefix . 'epa_error_log', 
    array(
      'Status_Code'  => $http_status,
      'Error_Message' => $err,
      'Service_Type' => 'frs_siteinfo'
    ));
    
} else {
    $json = json_decode($response, true);
    $data_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo';
    $data_update = array('site_name' => $json['Results']['FRSFacility']['0']['FacilityName']);
    $data_where = array('id' => $folderdocinfo_id);
    $wpdb->update($data_table , $data_update, $data_where);
}
}


}

?>