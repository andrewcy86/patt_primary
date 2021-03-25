<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

// Instantiate the client.
		$s3 = new Aws\S3\S3Client([
			'region'  => 'us-east-1',
			'version' => 'latest'
		]);

// Use the high-level iterators (returns ALL of your objects).
try {
    $results = $s3->getPaginator('ListObjects', [
        'Bucket' => 'digitization-stage'
    ]);

    foreach ($results as $result) {
        foreach ($result['Contents'] as $object) {
            echo $object['Key'] . PHP_EOL . '<br />';
        }
    }
} catch (S3Exception $e) {
    echo $e->getMessage() . PHP_EOL . '<br />';
}