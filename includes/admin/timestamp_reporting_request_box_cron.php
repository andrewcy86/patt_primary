<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/image.php');
include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/media.php');
include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/file.php');

global $current_user, $wpscfunction, $wpdb;

function strip_tags_deep_ts_box($value)
{
  return is_array($value) ?
    array_map('strip_tags_deep_ts_box', $value) :
    strip_tags(preg_replace( "/\r|\n/", "", $value ));
}

function add_quotes_ts_box($str) {
    return sprintf('"%s"', $str);
}

date_default_timezone_set('America/New_York');

//Request Timestamp Report

                       $request_file = 'request_timestamp_report'; // csv file name
                       $request_results = $wpdb->get_results("SELECT b.request_id,
                        (SELECT name from " . $wpdb->prefix . "terms WHERE term_id = b.ticket_priority) as ticket_priority,
                        (SELECT name from " . $wpdb->prefix . "terms WHERE term_id = b.ticket_status) as current_ticket_status,
                        b.date_created,
                        a.type as timestamp_status,
                        a.timestamp,
                        DATEDIFF(a.timestamp, b.date_created) as date_diff
                        FROM " . $wpdb->prefix . "wpsc_epa_timestamps_request a INNER JOIN
                        " . $wpdb->prefix . "wpsc_ticket b ON a.request_id = b.id",ARRAY_A );

                        // get column names
                        $request_columnNamesList = ['Request ID','Request Priority','Request Status','Request Created Date', 'Timestamp Status', 'Timestamp', 'Date Difference'];
                        

                        $request_csv_output.="Last Updated: ".date("Y-m-d H:i",time()).",,,,,,";
                        $request_csv_output.="\n";
                        foreach ( $request_columnNamesList as $request_column_name ) {
                            $request_csv_output.=$request_column_name.",";
                        }


                        // remove last additional comma 
                        $request_csv_output = substr($request_csv_output,0,strlen($request_csv_output)-1);

                        // start dumping csv rows in new line
                        $request_csv_output.="\n";

                       if(count($request_results) > 0){
                          foreach($request_results as $request_result){
                          $request_result = array_values($request_result);
                          $request_result =  implode(',', array_map('add_quotes_ts_box', $request_result));
                          $request_csv_output .= $request_result."\n";
                        }
                      
                      $request_file  = WPPATT_UPLOADS."/reports/".$request_file.".csv";
                      
                      //echo $request_csv_output;
                      
                      unlink($request_file);
                      file_put_contents($request_file, $request_csv_output);
                        }

                      //exit;


//Box Timestamp Report

                       $box_file = 'box_timestamp_report'; // csv file name
                       $box_results = $wpdb->get_results("SELECT (SELECT request_id from " . $wpdb->prefix . "wpsc_ticket WHERE id = b.ticket_id) as request_id,
                        b.box_id,
                        (SELECT name from " . $wpdb->prefix . "terms WHERE term_id = b.box_status) as current_box_status,
                        b.date_created,
                        a.type as timestamp_status,
                        a.timestamp,
                        DATEDIFF(a.timestamp, b.date_created) as date_diff
                        FROM " . $wpdb->prefix . "wpsc_epa_timestamps_box a INNER JOIN
                        " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id",ARRAY_A );

                        // get column names
                        $box_columnNamesList = ['Request ID','Box ID','Request Priority','Current Box Status','Request Created Date', 'Timestamp Status', 'Timestamp', 'Date Difference'];
                        

                        $box_csv_output.="Last Updated: ".date("Y-m-d H:i",time()).",,,,,,";
                        $box_csv_output.="\n";
                        foreach ( $box_columnNamesList as $box_column_name ) {
                            $box_csv_output.=$box_column_name.",";
                        }


                        // remove last additional comma 
                        $box_csv_output = substr($box_csv_output,0,strlen($box_csv_output)-1);

                        // start dumping csv rows in new line
                        $box_csv_output.="\n";

                       if(count($box_results) > 0){
                          foreach($box_results as $box_result){
                          $box_result = array_values($box_result);
                          $box_result =  implode(',', array_map('add_quotes_ts_box', $box_result));
                          $box_csv_output .= $box_result."\n";
                        }
                      
                      $box_file  = WPPATT_UPLOADS."/reports/".$box_file.".csv";
                      
                      echo $box_csv_output;
                      
                      unlink($box_file);
                      file_put_contents($box_file, $box_csv_output);
                        }

                      exit;
?>