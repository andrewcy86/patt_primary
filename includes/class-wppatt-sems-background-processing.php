<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/wp-async-request.php';
require_once __DIR__ . '/wp-background-process.php';

if ( ! class_exists( 'WP_SEMS_Request' ) ) :

class WP_SEMS_Request extends WP_Async_Request {

	/**
	 * @var string
	 */
	protected $action = 'sems_request';

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {
		// Actions to perform

global $current_user, $wpscfunction,$wpdb;

$ticket_id = $_POST['ticket_id'];

// Check if request is SEMS
$sems_check = $wpscfunction->get_ticket_meta($ticket_id,'super_fund');

if(in_array("true", $sems_check)) {  

// Get count of rows from folderdocinfo_files table

$get_file_count = $wpdb->get_row(
	"SELECT 
      count(a.id) as count
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files AS a
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_boxinfo AS b 
	ON (
        a.box_id = b.id
	   )
	INNER JOIN 
		" . $wpdb->prefix . "wpsc_ticket AS c 
	ON (
        b.ticket_id = c.id
	   )
	WHERE 
        c.id = '".$ticket_id."'");

//Batch Size
$range = 1;
$total_size = $get_file_count->count;

//Box array
$box_in_request_array = array();

$get_box_ids = $wpdb->get_results(
"
SELECT id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE 
ticket_id  = '".$ticket_id."'"
);

$json_block_array = array();

foreach ($get_box_ids as $data) {
$box_ids = $data->id;
array_push($box_in_request_array, $box_ids);
}

$i_box = 0;
$i_file = 0;

foreach( $box_in_request_array as $item_box ) {
$i_box++;
$get_file_details = $wpdb->get_results(
	"SELECT 
	a.id,
	b.box_id,
	a.folderdocinfofile_id,
	a.title,
	a.description,
	a.parent_id,
	a.author,
	a.date,
	a.addressee,
	a.record_type,
	c.Schedule_Item_Number as record_schedule,
	d.office_acronym as office_acronym,
	a.siteid,
	a.site_name,
	a.close_date,
	a.lan_id,
	a.access_restriction,
	a.specific_access_restriction,
	a.use_restriction,
	a.specific_use_restriction,
	a.rights_holder,
	a.source_format,
	a.source_dimensions,
	a.essential_record,
	a.tags,
	a.source_file_location,
	a.index_level,
	a.program_area
	
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
	    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_boxinfo AS b 
	ON (
        a.box_id = b.id
	   )
	      INNER JOIN 
		" . $wpdb->prefix . "epa_record_schedule AS c
	ON (
        b.record_schedule_id = c.id
	   )
	   	  INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_program_office AS d
	ON (
        b.program_office_id = d.office_code
	   )
	WHERE 
        a.box_id = '".$item_box."'");

foreach( $get_file_details as $item_file ) {
    

$i_file++;
$dbId = $item_file->id;
$boxId = $item_file->box_id;
$folderId = $item_file->folderdocinfofile_id;
$title = $item_file->title;
$descriptionOfRecord = $item_file->description;
$parentChildIndicator = $item_file->parent_id;
$createdUser = $item_file->author;
$creationDate = $item_file->date;
$addressee = $item_file->addressee;
$recordType = $item_file->record_type;
$dispositionSchedule = $item_file->record_schedule;
$siteID = $item_file->siteid;
$siteName = $item_file->site_name;
$closeDate = $item_file->close_date;
$epaContact = $item_file->lan_id;
$accessRestriction = $item_file->access_restriction;
$specificAccessRestiction = $item_file->specific_access_restriction;
$useRestriction = $item_file->use_restriction;
$specificUseRestriction = $item_file->specific_use_restriction;
$rightsHolder = $item_file->rights_holder;
$sourceType = $item_file->source_format;
$sourceDimension = $item_file->source_dimensions;
$programOffice = $item_file->office_acronym;
$essentialRecord = $item_file->essential_record;
$tag = $item_file->tags;
$folderName = $item_file->source_file_location;
$indexLevel = $item_file->index_level;
$program_area = $item_file->program_area;

// Declare array  
$file_details_array = array( 
"id" => $dbId,
"boxId" => $boxId,
"folderId" => $folderId,
"title" => $title,
"descriptionOfRecord" => $descriptionOfRecord,
"parentChildIndicator" => $parentChildIndicator,
"createdUser" => $createdUser,
"creationDate" => $creationDate,
"addressee" => $addressee,
"recordType" => $recordType,
"dispositionSchedule" => $dispositionSchedule,
"siteID" => $siteID,
"siteName" => $siteName,
"closeDate" => $closeDate,
"epaContact" => $epaContact,
"accessRestriction" => $accessRestriction,
"specificAccessRestiction" => $specificAccessRestiction,
"useRestriction" => $useRestriction,
"specificUseRestriction" => $specificUseRestriction,
"rightsHolder" => $rightsHolder,
"sourceType" => $sourceType,
"sourceDimension" => $sourceDimension,
"programOffice" => $programOffice,
"essentialRecord" => $essentialRecord,
"tag" => $tag,
"folderName" => $folderName,
"indexLevel" => $indexLevel,
"programArea" => $program_area
); 

//print_r($file_details_array);
// Use json_encode() function 

    array_push($json_block_array, $file_details_array);
    
}

}
	
	echo '<hr>';

	$batches = array_chunk($json_block_array, $range);
	//$batches = array_chunk($test_arr, $range);
	echo 'length batches: ' . count($batches) . '<br>';
	foreach ($batches as $batch) {

$curl = curl_init();

        $url = 'http://adrast.rtpnc.epa.gov:8011/sems-ws/rm/saveDocuments';

        $payload = json_encode($batch);
        curl_setopt($curl,CURLOPT_URL, $url);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $payload );
        curl_setopt($curl,CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl,CURLOPT_TIMEOUT, 30);
        curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);


$response = curl_exec($curl);

$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

curl_close($curl);

$err = Patt_Custom_Func::convert_http_error_code($status);

if ($status != 200) {
Patt_Custom_Func::insert_api_error('sems-class-background-processing',$status,$err);
}
	  
        //echo $sems_details;		  
		
		//echo 'length batch: ' . count($batch) . '<br><pre>';
		//echo json_encode($batch);
		//echo '</pre><br><hr><br>';
	  
		//echo json_encode($json_block_array).'<hr />';
	
	}


//echo $i_box.' box';
//echo $i_file.' file';

} // END SEMS check
	
	}

} 
endif;

new WP_SEMS_Request();