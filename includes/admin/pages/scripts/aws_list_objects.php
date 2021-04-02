<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

$bucket = 'cg-be8a2cea-5528-4e0a-a887-5f0ce918f009';

// Instantiate the client.
		$s3 = new Aws\S3\S3Client([
			'region'  => 'us-gov-west-1',
			'version' => 'latest',
			'credentials' => [
				'key'    => "AKIAR7FXZINYEYJXDFVK",
				'secret' => "XtiRrMaHA048yG8bKgNbBxHPe9O27WJ4LvYQ7zlk",
			]
		]);

// Use the high-level iterators (returns ALL of your objects).
try {
    $results = $s3->getPaginator('ListObjects', [
        'Bucket' => $bucket
    ]);

    foreach ($results as $result) {
        foreach ($result['Contents'] as $object) {
            echo $object['Key'] . PHP_EOL . '<br />';
        }
    }
} catch (S3Exception $e) {
    echo $e->getMessage() . PHP_EOL . '<br />';
}