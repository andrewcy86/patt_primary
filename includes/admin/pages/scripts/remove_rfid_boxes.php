<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['postvarslocation']) && !empty($_POST['postvarsboxpallet'])
){

$boxes = explode(",", $_POST['postvarsboxpallet']);

foreach($boxes as $items) {

$table_name = $wpdb->prefix . 'wpsc_epa_rfid_data';

$wpdb->delete($table_name, array( 'Reader_Name' => $_POST['postvarslocation'], 'box_id' => $items) );

}

   echo "All selected entries have been removed.";
 
} else {
   echo "Location/Box(es) selection incorrect.";
}
?>