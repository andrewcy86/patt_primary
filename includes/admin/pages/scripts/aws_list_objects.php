<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

//$bucket = 'cg-be8a2cea-5528-4e0a-a887-5f0ce918f009';
$bucket = 'cg-bb8dada3-350b-496e-b545-2d2b2a9aa6fe';

// Instantiate the client.
		$s3 = new Aws\S3\S3Client([
			'region'  => 'us-gov-west-1',
			'version' => 'latest',
			'credentials' => [
// 				'key'    => "AKIAR7FXZINYEYJXDFVK",
				'key'    => "AKIAR7FXZINYI2L5R42Q",
// 				'secret' => "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",
				'secret' => "AwnMZjwr8iDFi23/XM/DWsUpm2JolQ4HFfkwqCQR",
			]
		]);

echo 'Bucket: ' . $bucket . '<br>';
echo 'AWS_S3_BUCKET: ' . AWS_S3_BUCKET . '<br>';

// Use the high-level iterators (returns ALL of your objects).
try {
    $results = $s3->getPaginator('ListObjects', [
        'Bucket' => $bucket
    ]);

    foreach ($results as $result) {
        foreach ($result['Contents'] as $object) {
	        
	        $file_exist = $s3->doesObjectExist( $bucket, $object['Key'] ); //AWS_S3_BUCKET
            $does_file_exist = $file_exist ? 'true' : 'false';
            echo $object['Key'] . ' - ' . $does_file_exist . PHP_EOL . '<br />';
            echo '<pre>';
			print_r( $object );
			echo '</pre>';
			
            //echo $object['Key'] . PHP_EOL . '<br />'; // Clean
            
            $headObj = $s3->headObject( [
			    'Bucket' => $bucket,
			    //'Key' => $folderfile_details->object_key
			    'Key' => $object['Key']
			]);
			
			echo '<span class="" >MetaData: </span>';
			echo '<pre>';
			print_r( $headObj );
			echo '</pre>';
        }
    }
} catch (S3Exception $e) {
    echo $e->getMessage() . PHP_EOL . '<br />';
}