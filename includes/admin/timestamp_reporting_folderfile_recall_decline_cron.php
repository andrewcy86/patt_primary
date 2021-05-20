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

function strip_tags_deep($value)
{
  return is_array($value) ?
    array_map('strip_tags_deep', $value) :
    strip_tags(preg_replace( "/\r|\n/", "", $value ));
}

function add_quotes($str) {
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
FROM wpqa_wpsc_epa_timestamps_folderfile a 
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files b ON a.folderdocinfofile_id = b.id
INNER JOIN wpqa_wpsc_epa_boxinfo c ON c.id = b.box_id
INNER JOIN wpqa_wpsc_ticket d ON d.id = c.ticket_id",ARRAY_A );

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
  $folderfile_result =  implode(',', array_map('add_quotes', $folderfile_result));
  $folderfile_csv_output .= $folderfile_result."\n";
}

$folderfile_file  = WPPATT_UPLOADS."/reports/".$folderfile_file.".csv";

//echo $request_csv_output;

unlink($folderfile_file);
file_put_contents($folderfile_file, $folderfile_csv_output);
}

//exit;


//Recall Timestamp Report

//Decline Timestamp Report

exit;
?>