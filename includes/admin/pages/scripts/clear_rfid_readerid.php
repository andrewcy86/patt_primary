<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['postvarsreaderid'])
){
   $reader_id = $_POST['postvarsreaderid'];

$table_name = $wpdb->prefix . 'wpsc_epa_rfid_data';

$wpdb->delete($table_name, array( 'Reader_Name' => $reader_id ) );
  
  
 echo "All entries with the following RFID Reader ID: " . $reader_id . " have been removed.";
 
} else {
   echo "No RFID Reader ID Passed.";
}
?>