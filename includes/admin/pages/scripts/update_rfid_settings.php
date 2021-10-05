<?php

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $wpdb, $current_user, $wpscfunction;

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

//Grab RFID settings
$rfid_settings = str_replace(' ', '', $_POST['postvarrfidsettings']);

//Explode into array
$rfid_arr = explode(",",$rfid_settings);

$rfid_count = 0;

foreach ($rfid_arr as &$value) {
    
$get_rfid_setting = $wpdb->get_row("SELECT count(id) as count
FROM " . $wpdb->prefix . "wpsc_ticketmeta
WHERE meta_key = 'rfid_settings_locations' AND meta_value LIKE '%".$value."%'");

$get_rfid_count = $get_rfid_setting->count;

if ($get_rfid_count >= 1) {
$rfid_count++;
}

}


if (isset($_POST['postvarrfidsettings'])) {

if($rfid_count == 0) {
    
$wpscfunction->add_ticket_meta(0,'rfid_settings_locations',$rfid_settings);

} else {

$wpscfunction->update_ticket_meta(0,'rfid_settings_locations',array('meta_value'=> $rfid_settings));

}

echo 'Sucessfully updated RFID Settings';

} else {
    
echo "Issue with updating RFID Settings";
}