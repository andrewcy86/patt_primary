<?php
/*
* Related Files barcode_validate.php, barcode_lookup.php, arms_validate.php, validate_paging.php, arms_validation_processing.php, validate_pdf_include.php, update_validate.php
*/

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$dir = $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

$obj_id = htmlspecialchars($_GET["obj_id"]);

require_once($dir."/vendor/autoload.php");

function bucket() {
    return 'arms-nuxeo';
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
    
    // ARMS_API_AUTH can be switched to use the svc_patt_application service account
    curl_setopt_array($curl, array(
      CURLOPT_URL => ARMS_API . '/api/v1/search/pp/nxql_search/execute?queryParams=select%20*%20from%20epa_record%20where%20arms:identifiers/*1/key=\'PATT%20ID\'%20AND%20arms:identifiers/*1/value/*1%20=\''.$obj_id.'\'',
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
        'Authorization: '. ARMS_API_AUTH
      ),
    ));
    
    $response = curl_exec($curl);
    
    $find = ["file:content", "arms:disposition_date", "arms:epa_contact"];
    $replace   = ["file_content", "disposition_date", "epa_contact"];
    
    $new_response = str_replace($find, $replace, $response);
    
    curl_close($curl);
    
    $decoded = json_decode($new_response);

    $child2exists = count($decoded->entries[0]->uid);

    $object_key_check = 0;

    $folderfile_details = $wpdb->get_row(
		"SELECT object_key FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" . $obj_id . "'"
	);

    if(!empty($folderfile_details->object_key) && $folderfile_details->object_key == $decoded->entries[0]->uid) {
        $object_key_check = 1;
    }

    if ($child2exists == 1 && $object_key_check == 1) {

        $uid = $decoded->entries[0]->uid;
        $file_key = $decoded->entries[0]->properties->file_content->digest;
        $file_name = $decoded->entries[0]->properties->file_content->name;

        $disposition_date = $decoded->entries[0]->properties->disposition_date;
        $custodian = $decoded->entries[0]->properties->epa_contact;

        $obj_data = $s3->headObject([
            'Bucket' => bucket(),
            'Key'    => 'nuxeo-atlas/'.$file_key
         ]);

         if($obj_data['ContentLength'] <= 500000000) {
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => bucket(),
                'Key' => 'nuxeo-atlas/'.$file_key,
                //'Key' => $file_key,
                'ResponseContentDisposition' => 'inline; filename="'.$file_name.'"',
                'ResponseContentType' => 'application/pdf'
                
            ]);
            } else {
              $cmd = $s3->getCommand('GetObject', [
                'Bucket' => bucket(),
                'Key' => 'nuxeo-atlas/'.$file_key,
                //'Key' => $file_key,
                'ResponseContentDisposition' => 'attachment; filename="'.$file_name.'"',
                'ResponseContentType' => 'application/pdf'
                
            ]); 
            }
        
        $request = $s3->createPresignedRequest($cmd, '+20 minutes');
        
        $presignedUrl = (string)$request->getUri();
?>

<?php
if($obj_data['ContentLength'] <= 500000000) {
?>
<iframe src="<?php echo $presignedUrl; ?>" frameborder="0" scrolling="no" seamless="seamless" style="display:block; width:100%; height:100vh;"></iframe>
<?php
} else {
?>
<a href="<?php echo $presignedUrl; ?>">Click here to Download</a>
<?php
}
?>

<?php 

    } else {
        echo "PDF Not Found or does not exist in ARMS. Check ARMS";
    }
?>