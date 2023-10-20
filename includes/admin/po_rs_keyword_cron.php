<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $current_user, $wpscfunction, $wpdb;

	$rs_keyword_table = $wpdb->prefix . 'epa_record_schedule_keywords';

    $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

    $wpdb->query("TRUNCATE TABLE $rs_keyword_table");

    $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

	// POPULATING LATEST SCHEDULE LIST
	$get_record_schedules = $wpdb->get_results("SELECT DISTINCT(Schedule_Number) AS Schedule_Number
	FROM " . $wpdb->prefix . "epa_record_schedule
	WHERE Superseded_Flag = 0 and Deleted_Flag = 0 and Draft_Flag = 0 and Reserved_Flag = 0 and id != '-99999'");

	foreach($get_record_schedules as $item) {

    $rs = $item->Schedule_Number;
    //echo $rs . "<br />";
    $wpdb->insert($rs_keyword_table, array(
    'records_schedule' => $rs,
    'keywords' => '' ));
      
    }

	// CONSOLIDATE AND UPDATE KEYWORDS

	$get_record_schedules_keyowrds = $wpdb->get_results("SELECT records_schedule FROM " . $rs_keyword_table );

	// Combined List
	

	foreach($get_record_schedules_keyowrds as $item) {

    $keywords_array = array();
    $rs = $item->records_schedule;
    echo $rs . "<br />";
    
    $get_record_schedules_prek = $wpdb->get_results("SELECT Keywords
	FROM " . $wpdb->prefix . "epa_record_schedule
	WHERE Schedule_Number = '". $rs ."' AND Keywords != ''");
    
    foreach($get_record_schedules_prek as $keyword) {
    $prek =  trim($keyword->Keywords, '[]');
	$str_arr = preg_split ("/\,/", str_replace("'", "", $prek));
      
    $merged_array = array_merge_recursive($keywords_array,$str_arr);
	//print_r(array_unique($merged_array));
      
    $final_kw_list = implode(', ', array_unique($merged_array));
	echo $final_kw_list;
      
    $data_update = array('keywords' => $final_kw_list);
    $data_where = array('records_schedule' => $rs);
    $wpdb->update($rs_keyword_table, $data_update, $data_where);

    }
      
    }
?>