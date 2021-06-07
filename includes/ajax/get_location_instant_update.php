<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

//include_once( WPPATT_UPLOADS . 'api_authorization_strings.php' );

global $current_user, $wpscfunction, $wpdb;

$dc_id= isset($_POST['dc_id']) ? sanitize_text_field($_POST['dc_id']) : 0;

$backgroundAsync = new WP_LOC_Request();

$backgroundAsync->data( array( 'dc_id' => $dc_id ) );

$backgroundAsync->dispatch();

?>