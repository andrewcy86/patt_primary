<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$dir = $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

require_once($dir."/vendor/autoload.php");


function bucket() {
    return 'arms-nuxeo';
    #return 'arms-records';
}

function region() {
    return 'us-east-1';
}

$obj_id = $_GET['obj_id'];

if ($obj_id = 'LDF_1_2_6_ldf_09019588800598d8'){
    
    $file_key = 'f3b434cc86985e97520ee812d8877790';
    
    } else {

    $file_key = '';

    }

if ($obj_id != '') {
//echo WPPATT_UPLOADS;
//header("Content-type: application/pdf");

//echo $filename;

//Convert obj id to filename

$s3 = new Aws\S3\S3Client([
    'version' => 'latest',
    'region'  => region(),
    'signature_version' => 'v4'
]);

$cmd = $s3->getCommand('GetObject', [
'Bucket' => bucket(),
//'Key' => 'nuxeodev/'.$file_key,
'Key' => $file_key,
'ResponseContentDisposition' => 'inline',
'ResponseContentType' => 'application/pdf'

]);

$request = $s3->createPresignedRequest($cmd, '+20 minutes');

$presignedUrl = (string)$request->getUri();

echo '<iframe src="'+$presignedUrl+'"></iframe>';

} else {
echo 'PDF Failed to render.';
}
?>