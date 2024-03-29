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
    
// Begin Site ID Validation

$siteid_query = $wpdb->get_results(
"
SELECT 
DISTINCT a.siteid as siteid, a.site_name as site_name from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.id = a.box_id WHERE b.ticket_id = ".$ticket_id
);


$pattagentid_array = '';
$patt_ticket_id = '';
$invalid_siteid_array = '';
$invalid_site_id = array();

foreach ($siteid_query as $siteinfo) {
    
	$siteid_check_val = $siteinfo->siteid; 
	$current_sitename = $siteinfo->site_name; 
	
                $curl = curl_init();
				$url = SEMS_ENDPOINT;
				//7 character site ID
				if(strlen($siteid_check_val) == 7) {
                $url .= '?id='.$siteid_check_val;
				
				$headers = [
				    'Cache-Control: no-cache'
				];
				
			    curl_setopt($curl,CURLOPT_URL, $url);
			    curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			    curl_setopt($curl,CURLOPT_MAXREDIRS, 10);
			    curl_setopt($curl,CURLOPT_TIMEOUT, 30);
			    curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
			    curl_setopt($curl,CURLOPT_CUSTOMREQUEST, "GET");
			    curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
				//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				
				$response = curl_exec($curl);
				
				//Strip first and last characters
				
				$final_response = substr($response, 1, -1);
				
				//
				$object = json_decode($final_response);
				
				$site_name = $object->{'sitename'};
			
				$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			
				curl_close($curl);
			
				$err = Patt_Custom_Func::convert_http_error_code($status);
			
				if ($status != 200) {
					Patt_Custom_Func::insert_api_error('sems-site-id-validation',$status,$err);
				} else {
				    $folderdocinfo_files_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
				    $box_table = $wpdb->prefix . 'wpsc_epa_boxinfo';
				    
					if (strtoupper($current_sitename) != strtoupper($site_name)) {
					 
                    $sql = 
                    "UPDATE ".$folderdocinfo_files_table." a
                    JOIN ".$box_table." b
                    ON b.id = a.box_id
                    SET a.site_name = '".$site_name."'
                    WHERE a.siteid = '".$siteid_check_val."'
                    AND b.ticket_id = '".$ticket_id."'
                    ;";

                     $results = $wpdb->query($sql);
					    
					$data_update = array('site_name' => $site_name);
					$data_where = array('siteid' => $siteid_check_val);
					$wpdb->update($folderdocinfo_files_table, $data_update, $data_where);
					
					// Send Email
					array_push($invalid_site_id, $siteid_check_val);
                    
                    $patt_ticket_id = Patt_Custom_Func::ticket_to_request_id($ticket_id);
					
					//send notification and email when any folder/file metadata has been updated
                    $get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
                    $get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');
                    
                    $user_id_array = [$get_user_id->ID];
                    $convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
                    $patt_agent_id = implode($convert_patt_id);
                    $pattagentid_array = [$patt_agent_id];
                      
                    $pattagentid_admin_array = Patt_Custom_Func::agent_from_group($agent_admin_group_name);
                    $pattagentid_manager_array = Patt_Custom_Func::agent_from_group($agent_manager_group_name);
                     
                    $pattagentid_array = array_merge($pattagentid_admin_array,$pattagentid_manager_array,$pattagentid_array);
                    
                    
					}	
				} 
		    }
}

if(!empty($invalid_site_id)) {
//enabled email notification
$email = 1;

$invalid_site_id_email = implode(', ', $invalid_site_id);
$invalid_siteid_array = array("invalid_site_id" => $invalid_site_id_email);
					
Patt_Custom_Func::insert_new_notification('email-invalid-site-id',$pattagentid_array,$patt_ticket_id,$invalid_siteid_array,$email);
}

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
$range = 100;
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

        $url = SEMS_ENDPOINT_SAVE;

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