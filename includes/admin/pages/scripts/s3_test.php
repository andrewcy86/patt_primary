<?php

require 'vendor/autoload.php';
include_once( WPPATT_UPLOADS . 'api_authorization_strings.php' );

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

// Instantiate the client.
		$s3 = new Aws\S3\S3Client([
			'region'  => $s3_region,
			'version' => 'latest'
		]);

// Use the high-level iterators (returns ALL of your objects).
try {
    $results = $s3->getPaginator('ListObjects', [
        'Bucket' => $s3_bucket
    ]);

    foreach ($results as $result) {
        foreach ($result['Contents'] as $object) {
            echo $object['Key'] . PHP_EOL . '<br />';
        }
    }
} catch (S3Exception $e) {
    echo $e->getMessage() . PHP_EOL . '<br />';
}