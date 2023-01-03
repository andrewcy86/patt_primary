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


    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/api/v1/search/pp/nxql_search/execute?queryParams=select%20*%20from%20epa_record%20where%20arms:identifiers/*1/key=\'PATT%20ID\'%20AND%20arms:identifiers/*1/value/*1%20=\''.$obj_id.'\'',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'X-NXproperties: *',
        'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
      ),
    ));
    
    $response = curl_exec($curl);
    
    $new_response = str_replace("file:content","file_content",$response);
    
    
    curl_close($curl);
    
    $decoded = json_decode($new_response);

    $child2exists = count($decoded->entries[0]->uid);

    if ($child2exists == 1) {
        
        $uid = $decoded->entries[0]->uid;
        $file_key = $decoded->entries[0]->properties->file_content->digest;

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => bucket(),
            'Key' => 'nuxeodev/'.$file_key,
            #'ResponseContentDisposition' => 'inline; filename="'.$file_key.'.pdf"',
            'ResponseContentDisposition' => 'inline',
            'ResponseContentType' => 'application/pdf'
            
        ]);
        
        $request = $s3->createPresignedRequest($cmd, '+20 minutes');
        
        $presignedUrl = (string)$request->getUri();
?>
<iframe src="<?php echo $presignedUrl; ?>" frameborder="0" scrolling="no" seamless="seamless" style="display:block; width:100%; height:100vh;"></iframe>
<?php 

    } else {
        echo "PDF Not Found. Check ARMS";
    }
?>

<?php echo $obj_id; ?>


