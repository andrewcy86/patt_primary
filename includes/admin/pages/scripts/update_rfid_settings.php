<?php

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $wpdb, $current_user, $wpscfunction;

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

//Grab RFID settings
	$rfid_settings = str_replace(' ', '', $_POST['postvarrfidsettings']);

if (isset($_POST['postvarrfidsettings'])) {

if($rfid_settings == 1) {
    
$wpscfunction->add_ticket_meta(0,'rfid_settings_locations',$rfid_settings);

} elseif(!empty($rfid_settings)) {

$wpscfunction->update_ticket_meta(0,'rfid_settings_locations',array('meta_value'=> $rfid_settings));

}

echo 'Sucessfully updated RFID Settings';

} else {
    
echo "Issue with updating RFID Settings";
}