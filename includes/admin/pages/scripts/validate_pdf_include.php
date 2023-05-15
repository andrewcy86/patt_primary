<?php
/**
 * Template Name: S3 Delete File
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0 
 * Related Files barcode_validate.php, barcode_lookup.php, arms_validate.php, validate_paging.php, arms_validation_processing.php, validate_pdf_include.php, update_validate.php
 */

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));

$dir = $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

$obj_id = htmlspecialchars($_GET["obj_id"]);

require_once($dir."/vendor/autoload.php");

function bucket() {
    return 'arms-nuxeo';
}

function region() {
    return 'us-east-1';
}

$s3 = new Aws\S3\S3Client([
	    'version' => 'latest',
	    'region'  => region(),
	    'signature_version' => 'v4'
	]);


    $curl = curl_init();
    
    // ARMS_API_AUTH can be switched to use the svc_patt_application service account
    curl_setopt_array($curl, array(
      CURLOPT_URL => ARMS_API . '/api/v1/search/pp/nxql_search/execute?queryParams=select%20*%20from%20epa_record%20where%20arms:identifiers/*1/key=\'PATT%20ID\'%20AND%20arms:identifiers/*1/value/*1%20=\''.$obj_id.'\'',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'X-NXproperties: *',
        'Authorization: '. ARMS_API_AUTH
      ),
    ));
    
    $response = curl_exec($curl);
    
    $find = ["file:content", "arms:disposition_date", "arms:epa_contact"];
    $replace   = ["file_content", "disposition_date", "epa_contact"];
    
    $new_response = str_replace($find, $replace, $response);
    
    curl_close($curl);
    
    $decoded = json_decode($new_response);

    $child2exists = count($decoded->entries[0]->uid);

    $object_key_check = 0;
    echo $obj_id;
    echo decoded;

   