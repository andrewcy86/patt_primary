<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// set default filter for agents and customers //not true
global $wpdb, $current_user, $wpscfunction;

if (!$current_user->ID) die();


$obj_s3link = isset($_POST['obj_s3link']) ? sanitize_text_field($_POST['obj_s3link']) : '';
$obj_key = isset($_POST['obj_key']) ? sanitize_text_field($_POST['obj_key']) : '';
$obj_size = isset($_POST['obj_size']) ? sanitize_text_field($_POST['obj_size']) : '';
$obj_type = isset($_POST['obj_type']) ? sanitize_text_field($_POST['obj_type']) : '';
$obj_name = isset($_POST['obj_name']) ? sanitize_text_field($_POST['obj_name']) : '';
$mduff = isset($_POST['mduff']) ? sanitize_text_field($_POST['mduff']) : ''; 
$folderdocinfo_files_id = isset($_POST['folderdocinfo_files_id']) ? sanitize_text_field($_POST['folderdocinfo_files_id']) : ''; 

//$error = '';

$str = $obj_s3link;
$re = '/^((http[s]?|ftp):\/)?\/?([^:\/\s]+)((\/\w+)*\/)([\w\-\.]+[^#?\s]+)(.*)?(#[\w\-]+)?$/m';
preg_match_all($re, $str, $regex_output, PREG_SET_ORDER, 0);

$bucket_name = $regex_output[0][3];

$host = '.s3.us-gov-west-1.amazonaws.com';
$bucket_name = str_replace( $host, '', $bucket_name);

$where = [
	'id' => $folderdocinfo_files_id
];


$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
$data = [
	'object_key' => $obj_key,
	'object_location' => $bucket_name,
	'file_size' => $obj_size
];

$wpdb->update( $table_name, $data ,$where );

 
/*
$output = array(
	'error'   => $error,
//	'input' => $input,
	'obj_s3link' => $obj_s3link,
	'obj_name' => $obj_name,
	'obj_key' => $obj_key,
	'obj_size' => $obj_size,
	'obj_type' => $obj_type,
	'upload' => $upload,
	'mduff' => $mduff,
	'wppatt_files_index' => $_POST['wppatt_files_index'],
	'mdocs_index' => $mdocs_index,
	'mdocs_type' => $mdocs_type,
	'bucket_name' => $bucket_name
);
echo json_encode($output);	
*/