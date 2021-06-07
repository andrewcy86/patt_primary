<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/image.php');
include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/media.php');
include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/file.php');

global $current_user, $wpscfunction, $wpdb;

function strip_tags_deep_ts_ffr($value)
{
  return is_array($value) ?
    array_map('strip_tags_deep', $value) :
    strip_tags(preg_replace( "/\r|\n/", "", $value ));
}

function add_quotes_ts_ffr($str) {
    return sprintf('"%s"', $str);
}

date_default_timezone_set('America/New_York');

// Folder/File Timestamp Report
$folderfile_file = 'folderfile_timestamp_report'; // csv file name
$folderfile_results = $wpdb->get_results("SELECT 
d.request_id,
c.box_id,
b.folderdocinfofile_id,
b.date_created,
a.type as timestamp_flag,
a.timestamp,
DATEDIFF(a.timestamp, b.date_created) as date_diff
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_folderfile a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON a.folderdocinfofile_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo c ON c.id = b.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket d ON d.id = c.ticket_id",ARRAY_A );

// get column names
$folderfile_columnNamesList = ['Request ID','Box ID','Folder/File ID','Folder/File Created Date', 'Type', 'Timestamp', 'Date Difference'];


$folderfile_csv_output.="Last Updated: ".date("Y-m-d H:i",time()).",,,,,,";
$folderfile_csv_output.="\n";
foreach ( $folderfile_columnNamesList as $folderfile_column_name ) {
    $folderfile_csv_output.=$folderfile_column_name.",";
}


// remove last additional comma 
$folderfile_csv_output = substr($folderfile_csv_output,0,strlen($folderfile_csv_output)-1);

// start dumping csv rows in new line
$folderfile_csv_output.="\n";

if(count($folderfile_results) > 0){
  foreach($folderfile_results as $folderfile_result){
  $folderfile_result = array_values($folderfile_result);
  $folderfile_result =  implode(',', array_map('add_quotes_ts_ffr', $folderfile_result));
  $folderfile_csv_output .= $folderfile_result."\n";
}

$folderfile_file  = WPPATT_UPLOADS."/reports/".$folderfile_file.".csv";

//echo $request_csv_output;

unlink($folderfile_file);
file_put_contents($folderfile_file, $folderfile_csv_output);
}

//exit;


//Recall Timestamp Report
$recall_file = 'recall_timestamp_report'; // csv file name
$recall_results = $wpdb->get_results("SELECT
CONCAT('R-', b.recall_id) as recall_id,
c.box_id,
d.folderdocinfofile_id,
b.request_date,
a.type as timestamp_flag,
a.timestamp,
DATEDIFF(a.timestamp, b.request_date) as date_diff
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_recall a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest b ON b.id = a.recall_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo c ON c.id = b.box_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files d ON d.id = b.folderdoc_id",ARRAY_A );

// get column names
$recall_columnNamesList = ['Recall ID','Box ID','Folder/File ID','Recall Created Date', 'Recall Status', 'Timestamp', 'Date Difference'];


$recall_csv_output.="Last Updated: ".date("Y-m-d H:i",time()).",,,,,,";
$recall_csv_output.="\n";
foreach ( $recall_columnNamesList as $recall_column_name ) {
    $recall_csv_output.=$recall_column_name.",";
}


// remove last additional comma 
$recall_csv_output = substr($recall_csv_output,0,strlen($recall_csv_output)-1);

// start dumping csv rows in new line
$recall_csv_output.="\n";

if(count($recall_results) > 0){
  foreach($recall_results as $recall_result){
  $recall_result = array_values($recall_result);
  $recall_result =  implode(',', array_map('add_quotes_ts_ffr', $recall_result));
  $recall_csv_output .= $recall_result."\n";
}

$recall_file  = WPPATT_UPLOADS."/reports/".$recall_file.".csv";

//echo $request_csv_output;

unlink($recall_file);
file_put_contents($recall_file, $recall_csv_output);
}


//Decline Timestamp Report
$decline_file = 'decline_timestamp_report'; // csv file name
$decline_results = $wpdb->get_results("SELECT
CONCAT('D-', b.return_id) as decline_id,
c.box_id,
b.return_date,
a.type as timestamp_flag,
a.timestamp,
DATEDIFF(a.timestamp, b.return_date) as date_diff
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_decline a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_return b ON b.id = a.decline_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_return_items c ON c.return_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo d ON d.id = c.box_id",ARRAY_A );

// get column names
$decline_columnNamesList = ['Decline ID','Box ID','Decline Created Date', 'Decline Status', 'Timestamp', 'Date Difference'];


$decline_csv_output.="Last Updated: ".date("Y-m-d H:i",time()).",,,,,,";
$decline_csv_output.="\n";
foreach ( $decline_columnNamesList as $decline_column_name ) {
    $decline_csv_output.=$decline_column_name.",";
}


// remove last additional comma 
$decline_csv_output = substr($decline_csv_output,0,strlen($decline_csv_output)-1);

// start dumping csv rows in new line
$decline_csv_output.="\n";

if(count($decline_results) > 0){
  foreach($decline_results as $decline_result){
  $decline_result = array_values($decline_result);
  $decline_result =  implode(',', array_map('add_quotes_ts_ffr', $decline_result));
  $decline_csv_output .= $decline_result."\n";
}

$decline_file  = WPPATT_UPLOADS."/reports/".$decline_file.".csv";

//echo $request_csv_output;

unlink($decline_file);
file_put_contents($decline_file, $decline_csv_output);
}
exit;
?>