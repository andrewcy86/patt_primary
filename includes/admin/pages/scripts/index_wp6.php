<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');



header("Access-Control-Allow-Origin: *");
require_once __DIR__."/vendor/autoload.php";

// TEMPORARY
$s3_bucket = 'cg-be8a2cea-5528-4e0a-a887-5f0ce918f009';
$s3_region ='us-gov-west-1';
$s3_key = 'AKIAR7FXZINYEYJXDFVK';
$s3_secret ='XtiRrMaHA048yG8bKgNbBxHPe9O27WJ4LvYQ7zlk';

// D E B U G


// You can call the following to erase all pending multipart uploads. 
// It's a good idea to set your bucket to do this automatically (via console)
// or set this in a cronjob for every 24-48 hours
// echo abortPendingUploads(bucket());

function bucket() {
	//include WPPATT_UPLOADS.'api_authorization_strings.php';
    return $s3_bucket;
//	return AWS_S3_BUCKET;
}

function region() {
	//include WPPATT_UPLOADS.'api_authorization_strings.php';
    return $s3_region;
//	return AWS_S3_REGION;
}

function s3_key() {
	return $s3_key;
}

function s3_secret() {
	return $s3_secret;
}





/**
 * Easy wrapper around S3 API
 * @param  string $command the function to call
 * @param  mixed $args    variable args to pass
 * @return mixed
 */
function s3($command=null,$args=null)
{
	static $s3=null;
	if ($s3===null)
	$s3 = new Aws\S3\S3Client([
	    'version' => 'latest',
	    'region'  => region(),
	    'signature_version' => 'v4',
	    //'profile' => 'default',
	]);
	if ($command===null)
		return $s3;
	$args=func_get_args();
	array_shift($args);
	try {
		$res=call_user_func_array([$s3,$command],$args);
		return $res;
	}
	catch (AwsException $e)
	{
		echo $e->getMessage(),PHP_EOL;
	}	
	return null;
}
/**
 * Output data as json with proper header
 * @param  mixed $data
 */
function json_output($data)
{
    header('Content-Type: application/json');
    die(json_encode($data));
}
/**
 * Deletes all multipart uploads that are not completed.
 *
 * Useful to clear up the clutter from your bucket
 * You can also set the bucket to delete them every day
 * @return integer number of deleted objects
 */
function abortPendingUploads($bucket)
{
    $count=0;
    $res=s3("listMultipartUploads",["Bucket"=>bucket()]);
    if (is_array($res["Uploads"]))
    foreach ($res["Uploads"] as $item)
    {

        $r=s3("abortMultipartUpload",[
            "Bucket"=>$bucket,
            "Key"=>$item["Key"],
            "UploadId"=>$item["UploadId"],
        ]);
        $count++;
    }
    return $count;
}
/*
*
 * Enables CORS on bucket
 *
 * This needs to be called exactly once on a bucket before browser uploads.
 * @param string $bucket 
*/

function setCORS($bucket)
{
    //$res=s3("getBucketCors",["Bucket"=>$bucket]);
    $res=s3("putBucketCors",
        [
            "Bucket"=>$bucket,
            "CORSConfiguration"=>[
                "CORSRules"=>[
                    [
                    'AllowedHeaders'=>['*'],
                    'AllowedMethods'=> ['POST','GET','HEAD','PUT'],
                    "AllowedOrigins"=>["localhost","*"],
                    ],
                ],
            ],
        ]);
}

if (isset($_POST['command']))
{
	$command=$_POST['command'];

	if ($command=="create")
	{
	    //echo setCORS(bucket());
		$res=s3("createMultipartUpload",[
			'Bucket' => bucket(),
            'Key' => $_REQUEST['key'],
            'ContentType' => $_REQUEST['fileInfo']['type'],
            'Metadata' => $_REQUEST['fileInfo']
		]);
	 	json_output(array(
               'uploadId' => $res->get('UploadId'),
               'key' => $res->get('Key'),
        ));
	}

    if ($command=="listparts")
    {
        $partsModel = s3("listParts",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'MaxParts' => 10000,
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
        ]);
        json_output(array(
               'parts' => $partsModel['Parts'],
        ));
    }

	if ($command=="part")
	{
		$command=s3("getCommand","UploadPart",[
			'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
            'PartNumber' => $_REQUEST['partNumber'],
            'ContentLength' => $_REQUEST['contentLength']
		]);

        // Give it at least 24 hours for large uploads
		$request=s3("createPresignedRequest",$command,"+8 hours");
        json_output([
            'url' => (string)$request->getUri(),
        ]);		
	}

	if ($command=="complete")
	{
        sleep(2);
	 	$partsModel = s3("listParts",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'MaxParts' => 10000,
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
        ]);
        if (!isset($partsModel['Parts']))
        {
            sleep(2);
            $partsModel = s3("listParts",[
                'Bucket' => bucket(),
                'Key' => $_REQUEST['sendBackData']['key'],
                'MaxParts' => 10000,
                'UploadId' => $_REQUEST['sendBackData']['uploadId'],
            ]);
        }
        $model = s3("completeMultipartUpload",[
            'Bucket' => bucket(),
            'Key' => $_REQUEST['sendBackData']['key'],
            'UploadId' => $_REQUEST['sendBackData']['uploadId'],
            'MultipartUpload' => [
            	"Parts"=>$partsModel["Parts"],
            ],
        ]);

        json_output([
            'success' => true,
            'locationinfo' => $model->get('Location'),
        ]);
	}
	if ($command=="abort")
	{
		// $model = s3("abortMultipartUpload",[
        //    'Bucket' => bucket(),
        //    'Key' => $_REQUEST['sendBackData']['key'],
        //    'UploadId' => $_REQUEST['sendBackData']['uploadId']
        //]);
        //json_output([
        //    'success' => true
        //]);
	}

	exit(0);
}

//include( WPPATT_ABSPATH.'includes/admin/pages/scripts/page.htm' );
// include( WPPATT_ABSPATH .'includes/admin/pages/scripts/page2.php' );
//include( WPPATT_ABSPATH .'includes/admin/pages/scripts/s3_modal_slice.php' ); // removed Jan 2021 Podbelski
//include "page.htm";