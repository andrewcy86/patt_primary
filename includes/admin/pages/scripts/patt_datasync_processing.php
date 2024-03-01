<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');

global $wpdb, $current_user, $wpscfunction;

    // Begins the Datasync Process
    if($_POST['action'] == 'datasync'){
         $response = Patt_Custom_Func::patt_datasync_file_check();
        //$test = Patt_Custom_Func::patt_datasync_file_check();
    } else {
        $response = 'Datasync did not execute';

    }
    

echo json_encode($response);