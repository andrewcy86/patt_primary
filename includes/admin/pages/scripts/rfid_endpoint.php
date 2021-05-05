<?php

global $wpdb, $current_user, $wpscfunction;

// Set error logging in db.php to yes or no.  Logs are written to log.txt

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');
	
include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

$rfid_table = $wpdb->prefix . "wpsc_epa_rfid_data";

date_default_timezone_set('America/New_York');

$rawPostData = file_get_contents('php://input');

// Database variables located in db.php
    $readerName = $_POST['reader_name'];
    $macAddress = $_POST['mac_address'];

    $lineEnding = $_POST['line_ending'];
    $fieldDelim = $_POST['field_delim'];
    $fieldNames = $_POST['field_names'];
    $fieldValues = $_POST['field_values'];

// Replace the field delimiter with a comma.
    str_replace($fieldDelim, ",", $fieldNames);

// Break the field values up into rows.
    $rows = explode("\n", $fieldValues);

// Remove the last row. It's always blank
    if (sizeof($rows))
        array_pop($rows);
    $fieldNames = "reader_name,mac_address,box_id," . $fieldNames;
    foreach ($rows as $row) {
        
        $array = explode(',', $row);
        $array_strip = str_replace('"', '', $array[1]);
        
        $reader_name_strip = substr($readerName, 2, -2);
        $box_id = Patt_Custom_Func::convert_epc_pattboxid($array_strip);
        $box_id_strip = str_replace("\\", "", $box_id);
        
        $row = $readerName . "," . $macAddress . ",\"" . $box_id_strip . "\","  . $row;
        $fld_value = str_replace("\\", "", $row);
        $row = $fld_value;

	$rfid_count = $wpdb->get_row(
				"SELECT count(box_id) as count FROM " . $rfid_table . " WHERE box_id = '".$box_id_strip."' AND Reader_Name = '".$reader_name_strip."'");

    $rfid_count_num = $rfid_count->count;

	$box_check_count = $wpdb->get_row(
				"SELECT count(box_id) as count FROM ".$wpdb->prefix."wpsc_epa_boxinfo WHERE box_id = '".$box_id_strip."'");

    $box_check_count_num = $box_check_count->count;
if ($rfid_count_num == 0 && $box_check_count_num > 0) {

$wpdb->query("INSERT INTO ".$rfid_table." (".$fieldNames.") VALUES (".$row.")");

}

    }

?>