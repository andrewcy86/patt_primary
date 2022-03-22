<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

$table = 'wpqa_epa_patt_arms_transfer_errors';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));

$dir = $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

require_once($dir."/vendor/autoload.php");

function bucket() {
    return AWS_S3_BUCKET;
}

function region() {
    return AWS_S3_REGION;
}

$s3 = new Aws\S3\S3Client([
	    'version' => 'latest',
	    'region'  => region(),
	    'signature_version' => 'v4'
	]);

$objects = $s3->getIterator('ListObjects', array(
    "Bucket" => bucket(),
    "Prefix" => 'patt-inventory/arms-records/arms-records-prod-inventory/data/' //must have the trailing forward slash "/"
));

$objects = $s3->listObjects([
  'Bucket' => bucket(),
  'Prefix' => 'patt-inventory/arms-records/arms-records-prod-inventory/data/'
]);

$folder_contents_arr = array();

foreach ($objects['Contents']  as $object) {
  if($object['Key'] != 'patt-inventory/arms-records/arms-records-prod-inventory/data/') {
   $folder_contents_arr[] = array(
        'key' => $object['Key'],
        'date'  => strtotime($object['LastModified'])
    );
  }
}

$date_sort = array_column($folder_contents_arr, 'date');
array_multisort($date_sort, SORT_DESC, $folder_contents_arr);

#print("<pre>".print_r($folder_contents_arr,true)."</pre>");

$most_recent_file = $folder_contents_arr[0][key];

#check to make sure string contains csv.

$info = pathinfo($most_recent_file);

if ($info["extension"] == "gz") {
#echo 'is a csv, proceed';
  
$result = $s3->getObject(array(
    'Bucket' => bucket(),
    'Key'    => $most_recent_file
));

  // Cast as a string

$s3->registerStreamWrapper();

if ($stream = fopen('s3://'.bucket().'/'.$most_recent_file, 'r')) {
    // While the stream is still open
    while (!feof($stream)) {
        // Read up to 100mb from the stream
        $d = fread($stream, 100000000);

$bodyAsString = zlib_decode($d);
  
$lines = explode(PHP_EOL, $bodyAsString);
$array = array();
foreach ($lines as $line) {
    $array[] = str_getcsv($line);
}

// Only insert rows when CSV row is not empty
$array = array_map('array_filter', $array);
$array = array_filter($array);

print("<pre>".print_r($array,true)."</pre>");

$counter = 0;
foreach($array as $item) {
  	$s3_key = $array[$counter][1];
  	$modified_date = $array[$counter][2];
  
	$timestamp = strtotime($modified_date);

	$date_time = date("Y-m-d H:i:s", $timestamp);
  	$counter++;
 	// Only insert unique s3 keys
  	$get_previous_s3_key = $wpdb->get_row('SELECT ID FROM ' . $table . ' WHERE s3_key = "' . $s3_key . '"');
  	$previous_s3_key = $get_previous_s3_key->ID;
  	if(empty($previous_s3_key)) {
     // Only insert files that have not been modified in over 7 days
     if($timestamp < strtotime('-7 days')) {
        // echo($s3_key . ', '. $date_time . '<br/>');
        $wpdb->insert($table, array('s3_key' => $s3_key, 'last_modified' => $date_time) ); 
     }
    }
    }

//Finished processing, move csv file
// Copy an object.
$most_recent_filename = basename($most_recent_file);

$s3->copyObject([
    'Bucket'     => bucket(),
    'Key'        => 'patt-inventory-archive/' . $most_recent_filename,
    'CopySource' => bucket() .'/patt-inventory/arms-records/arms-records-prod-inventory/data/' . $most_recent_filename,
]);

$objects_del = $s3->getIterator('ListObjects', array(
    "Bucket" => bucket(),
    "Prefix" => 'patt-inventory/arms-records/arms-records-prod-inventory/' //must have the trailing forward slash "/"
));


foreach ($objects_del as $object) {

    $result = $s3->deleteObject(array(
        'Bucket' => bucket(),
        'Key'    => $object['Key']
    ));
}

}
// Be sure to close the stream resource when you're done with it
fclose($stream);
}
} else {
Patt_Custom_Func::insert_api_error('patt-arms-monitor','500','Unable to process due to missing .csv.gz extension.');
}
?>