<?php

global $wpdb, $current_user, $wpscfunction;

/*
https://www.hashbangcode.com/article/using-authentication-and-filegetcontents

https://ecms.epa.gov/dctm-rest/repositories/ecmsrmr65/archived-contents?object-id=0901efd0800537c4

$username = 'username';
$password = 'password';
 
$context = stream_context_create(array(
    'http' => array(
        'header'  => "Authorization: Basic " . base64_encode("$username:$password")
    )
));
$data = file_get_contents($url, false, $context);
*/


$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$obj_id = $_GET['obj_id'];

if ($obj_id != '') {
//echo WPPATT_UPLOADS;
header("Content-type: application/pdf");

//echo $filename;

//Convert obj id to filename

$filename = $obj_id;

//$pdf = file_get_contents('zip://'.WPPATT_UPLOADS.$filename.'_content.zip#'.$filename.'.pdf');
/*
$username = 'admin';
$password = 'environmentalprotectionagency';
 
$context = stream_context_create(array(
    'http' => array(
        'header'  => "Authorization: Basic " . base64_encode("$username:$password")
    )
));*/
//$pdf = file_get_contents('https://nitacreations.com/test_files/LDF_1_2_6_ldf_09019588800598d8_content.zip', false, $context);
//$pdf = file_get_contents('zip://https://nitacreations.com/test_files/'.$filename.'_content.zip#'.$filename.'.pdf');
//$pdf = file_get_contents('http://www.africau.edu/images/default/sample.pdf');


//$pdf = file_get_contents('zip://'.WPPATT_UPLOADS.$filename.'_content.zip#'.$filename.'.pdf');


$user = 'admin';
$password = 'environmentalprotectionagency';
$url = 'https://nitacreations.com/test_files/'.$filename.'_content.zip';

$curl = curl_init();
// Define which url you want to access
curl_setopt($curl, CURLOPT_URL, $url);

// Add authorization header
curl_setopt($curl, CURLOPT_USERPWD, $user . ':' . $password);

// Allow curl to negotiate auth method (may be required, depending on server)
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);

$fp = fopen(WPPATT_UPLOADS."/validation-temp/".$filename."_content.zip", "w+");
curl_setopt($curl,  CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_FILE, $fp);
curl_setopt($curl, CURLOPT_URL, $url);


// Get response and possible errors
$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);



$pdf = file_get_contents('zip://'.WPPATT_UPLOADS.'/validation-temp/'.$filename.'_content.zip#'.$filename.'.pdf');
echo $pdf;
} else {
echo 'PDF Failed to render.';
}
?>