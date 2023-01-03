<?php
/**
 * Template Name: S3 Delete File
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0 
 */

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));

$dir = $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

$obj_id = htmlspecialchars($_GET["obj_id"]);

require_once($dir."/vendor/autoload.php");

function bucket() {
    return 'arms-nuxeo';
    #return 'arms-records';
}

function region() {
    return 'us-east-1';
}

$s3 = new Aws\S3\S3Client([
	    'version' => 'latest',
	    'region'  => region(),
	    'signature_version' => 'v4'
	]);

$file_key = 'f3b434cc86985e97520ee812d8877790';
//$file_key = 'digitization-center/binary/0000040-2/0000040-2-02-1.pdf';

$cmd = $s3->getCommand('GetObject', [
    'Bucket' => bucket(),
    //'Key' => 'nuxeodev/'.$file_key,
    'Key' => $file_key,
    #'ResponseContentDisposition' => 'inline; filename="'.$file_key.'.pdf"',
    'ResponseContentDisposition' => 'inline',
    'ResponseContentType' => 'application/pdf'
    
]);

$request = $s3->createPresignedRequest($cmd, '+20 minutes');

$presignedUrl = (string)$request->getUri();

?>

<?php echo $obj_id; ?>

<iframe src="<?php echo $presignedUrl; ?>" frameborder="0" scrolling="no" seamless="seamless" style="display:block; width:100%; height:100vh;"></iframe>
