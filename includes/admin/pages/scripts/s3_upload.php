<?php
	if(isset($_FILES['image'])){
		$file_name = $_FILES['image']['name'];   
		$temp_file_location = $_FILES['image']['tmp_name']; 

		require 'vendor/autoload.php';
		$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
		require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

		include_once( WPPATT_UPLOADS . 'api_authorization_strings.php' );

		$s3 = new Aws\S3\S3Client([
			'region'  => $s3_region,
			'version' => 'latest'
		]);		

		$result = $s3->putObject([
			'Bucket' => $s3_bucket,
			'Key'    => $file_name,
			'SourceFile' => $temp_file_location			
		]);

		var_dump($result);
	}
?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">         
	<input type="file" name="image" />
	<input type="submit"/>
</form>