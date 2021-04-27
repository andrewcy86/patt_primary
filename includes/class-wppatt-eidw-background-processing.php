<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/wp-async-request.php';
require_once __DIR__ . '/wp-background-process.php';

if ( ! class_exists( 'WP_EIDW_Request' ) ) :

class WP_EIDW_Request extends WP_Async_Request {

	/**
	 * @var string
	 */
	protected $action = 'eidw_request';

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

$json;

// D E B U G 
/*
$folderdocinfo_files_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

$data_update = array('lan_id_details' => $ticket_id );
$data_where = array('id' => 1708 );
$wpdb->update($folderdocinfo_files_table, $data_update, $data_where);

*/


$lanid_query = $wpdb->get_results(
"
SELECT 
DISTINCT a.lan_id as lan_id from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id INNER JOIN " . $wpdb->prefix . "wpsc_ticket c ON b.ticket_id = c.id WHERE a.lan_id <> '' AND c.id = ".$ticket_id
);


foreach ($lanid_query as $lan_id) {

	$lan_id_check_val = $lan_id->lan_id; 
	
	$curl = curl_init();
	
	$url = 'https://wamssoprd.epa.gov/iam/governance/scim/v1/Users?filter=userName%20eq%20'.$lan_id_check_val;
	
	$eidw_authorization = 'Authorization: Basic '.EIDW;
	
	$headers = [
	    'Cache-Control: no-cache',
		$eidw_authorization
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
	$err = curl_error($curl);
	curl_close($curl);
	
	$lan_id_details = '';
	
	$error_check = '404 Not Found';
	
	//if ( $err ) {
	//if ( $response == null || str_contains( $reponse, $error_check ) ) {
	if ( $response == null ) {	
		$lan_id_details = 'Error';
	} else {
	
	    
		$json = json_decode($response, true);
		
		$active = $json['Resources']['0']['active'];
		$full_name = $json['Resources']['0']['name']['givenName'].' '.$json['Resources']['0']['name']['familyName'];
		$email = $json['Resources']['0']['emails']['0']['value'];
		$phone = $json['Resources']['0']['phoneNumbers']['0']['value'];
		$org = $json['Resources']['0']['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User']['department'];
		
		//get LAN ID to compare on the box details page
		$lan_id_username = $json['Resources'][0]['userName'];
		
		if ($active != 1) {
		    
			$request_id_query = $wpdb->get_results("SELECT a.id as id from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id INNER JOIN " . $wpdb->prefix . "wpsc_ticket c ON b.ticket_id = c.id WHERE a.lan_id = '" . $lan_id_check_val . "' AND c.id = ".$ticket_id);
			
			$find_requester = $wpdb->get_row("SELECT a.user_login as user_login FROM " . $wpdb->prefix . "users a
			INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON a.user_email = b.customer_email WHERE b.id = '" . $ticket_id . "'");
			
			$requester_lanid = $find_requester->user_login;
			$requestor_json = Patt_Custom_Func::lan_id_to_json( $requester_lanid );
			
			foreach ($request_id_query as $request_lan_id_update) {
				$request_db_lan_id = $request_lan_id_update->id ;
				
				$folderdocinfo_files_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
				
				
// 				if ( $requestor_json != 'Error' || $lan_id_details != 'Error') {
				if ( $requestor_json != 'Error' ) {
					$data_update = array('lan_id_details' => $requestor_json, 'lan_id' => $requester_lanid);
					$data_where = array('id' => $request_db_lan_id);
					$wpdb->update($folderdocinfo_files_table, $data_update, $data_where);
					
				} elseif ( $requestor_json == 'Error' ) {  // && $requester_lanid != ''
					$data_update = array( 'lan_id' => $requester_lanid );
					$data_where = array('id' => $request_db_lan_id);
					$wpdb->update($folderdocinfo_files_table, $data_update, $data_where);
					
				}
			
			}
		
	}
	
	if ($active == 1) {
	
		// Declare array  
		$lan_id_details_array = array( 
		    "name"=>$full_name,
		    "email"=>$email,
		    "phone"=>$phone,
		    "org"=>$org,
		    "lan_id"=>$lan_id_username,
		); 
		   
		// Use json_encode() function 
		$json = json_encode($lan_id_details_array); 
		   
		// Display the output 
		//echo($json); 
		   
		   
		$lan_id_details = $full_name.','.$email.','.$phone.','.$org.','.$lan_id_username;
		
		$id_query = $wpdb->get_results("SELECT a.id as id from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id INNER JOIN " . $wpdb->prefix . "wpsc_ticket c ON b.ticket_id = c.id WHERE a.lan_id = '" . $lan_id_check_val . "' AND c.id = ".$ticket_id);
		
		foreach ($id_query as $lan_id_update) {
			$db_lan_id = $lan_id_update->id ;
			
			// Detects update to contact info, if yes then update table
			//if ($lan_id_details != $lan_id_details_val && $lan_id_details != 'Error')
			if ( $json != null || $lan_id_details != 'Error') {
				$folderdocinfo_files_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
				
				$data_update = array('lan_id_details' => $json);
				$data_where = array('id' => $db_lan_id);
				$wpdb->update($folderdocinfo_files_table, $data_update, $data_where);
			}
		
		}
	
	}
	
	//echo $lan_id_details;
	//print_r($response);
	
	}



}

$output = array(
  'ticket_id'   => $ticket_id,
  'json' => $json,
  'requestor_json' => $requestor_json,
  'lan_id_details' => $lan_id_details,
  'requester_lanid' => $requester_lanid,
  'response' => $response
);

//echo json_encode($output);


	}

}

endif;

new WP_EIDW_Request();