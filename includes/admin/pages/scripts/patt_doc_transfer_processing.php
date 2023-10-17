<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');

global $wpdb, $current_user, $wpscfunction;

$agent_permissions = $wpscfunction->get_current_agent_permissions();

$host = DB_HOST; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

$con = mysqli_connect($host, $user, $password,$dbname);
// Check connection
if (!$con) {
  die("Connection failed: " . mysqli_connect_error());
}

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = $_POST['search']['value']; // Search value

if($columnName == 'ticket_priority') {
$columnName = 'ticket_priority_order';
} elseif($columnName == 'status') {
$columnName = 'box_status_order';
} else {
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
}
## Custom Field value
$searchByProgramOffice = $_POST['searchByProgramOffice'];
$searchByDigitizationCenter = $_POST['searchByDigitizationCenter'];
$searchByPriority = $_POST['searchByPriority'];
$searchByRecallDecline = $_POST['searchByRecallDecline'];
$searchByOverallStatus = $_POST['searchByOverallStatus']; // ECMS has been updated to be called ARMS instead
$searchByStage = $_POST['searchByStage'];
$searchGeneric = $_POST['searchGeneric'];
$searchByStatus = $_POST['searchByStatus'];
$searchByUser = $_POST['searchByUser'];
$searchByUserAAVal = $_REQUEST['searchByUserAAVal'];
$searchByUserAAName = $_REQUEST['searchByUserAAName'];
$is_requester = $_POST['is_requester'];

## Custom Field value
$searchByDocID = str_replace(",", "|", $_POST['searchByDocID']);


## Search 
$searchQuery = "";

//Add Pallet Support
//Extract ID and determine if it is Box or Pallet
//$searchByBoxID = str_replace(",", "|", $_POST['searchByBoxID']);

// $BoxID_arr = explode(",", $_POST['searchByBoxID']);  
/*
$newBoxID_arr = array();
$newPalletID_arr = array();

foreach($BoxID_arr as $key => $value) {
//Check if Box ID
if (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $value)) {
array_push($newBoxID_arr,$value);
}
//Check if Pallet ID
if (preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $value)) {
array_push($newPalletID_arr,$value);
}
}

$newBoxID_str = str_replace(",", "|", implode(',', $newBoxID_arr));
$newPalletID_str = str_replace(",", "|", implode(',', $newPalletID_arr));
*/

if($searchByDocID != ''){
    //used to be a.folderdocinfo_id
   $searchQuery .= "and (a.folderdocinfofile_id REGEXP '^(".$searchByDocID.")$' ) ";
}

// if($newBoxID_str != ''){
//    $searchQuery .= " and (a.folderdocinfofile_id REGEXP '^(".$newBoxID_str.")$' ) ";
// }

// if($newPalletID_str != ''){
//    $searchQuery .= " and (a.pallet_id REGEXP '^(".$newPalletID_str.")$' ) ";
// }

if($searchByProgramOffice != ''){
   $searchQuery .= " and (c.office_acronym='".$searchByProgramOffice."') ";
}

if($searchByDigitizationCenter != ''){
   $searchQuery .= " and (f.name ='".$searchByDigitizationCenter."') ";
}

if($searchByPriority != ''){
   $searchQuery .= " and (b.ticket_priority='".$searchByPriority."') ";
}

if($searchByStatus != ''){
   $searchQuery .= " and (a.status ='".$searchByStatus."') ";
}

//Get term_ids for Recall status slugs
$status_recall_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	 // 878
$status_recall_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' ); //734
$status_recall_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' ); //733

$status_decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' );	 // 791
$status_decline_completed_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-complete' ); //754
    
if($searchByRecallDecline != ''){

        if($searchByRecallDecline == 'Recall') {
            $searchQuery .= "and (
            f.recall_status_id NOT IN (".$status_recall_denied_term_id.",".$status_recall_cancelled_term_id.",".$status_recall_complete_term_id.")
            )";
        }

        if($searchByRecallDecline == 'Decline') {
            $searchQuery .= "and (
            g.return_id <> ''
            )";
        }

}

$overall_status = '';

if($searchByOverallStatus != ''){
    if($searchByOverallStatus == 'Processing') {
        $overall_status = ' AND status = "Processing" ';
    }
	if($searchByOverallStatus == 'Error') {
        $overall_status = ' AND status = "Error" ';
    }
	if($searchByOverallStatus == 'Transferred') {
        $overall_status = ' AND status = "Transferred" ';
    }
	/*if($searchByOverallStatus == 'Published') {
        $overall_status = ' AND status = "Published" ';
    }*/

}

$stage_status = '';

if($searchByStage != ''){
	if($searchByStage == 'received') {
		$stage_status = ' AND received_stage = 1 ';
	}
	if($searchByStage == 'text_extraction') {
		$stage_status = ' AND extraction_stage = 1 ';
	}
	if($searchByStage == 'keyword_id') {
		$stage_status = ' AND keyword_id_stage = 1 ';
	}
	if($searchByStage == 'metadata') {
		$stage_status = ' AND metadata_stage = 1 ';
	}
	if($searchByStage == 'arms') {
		$stage_status = ' AND arms_stage = 1 ';
	}
	/*if($searchByStage == 'published') {
		$stage_status = ' AND published_stage = 1 ';
	}*/
}



// If a user is a requester, only show the boxes from requests (tickets) they have submitted. 
if( $is_requester == 'true' ){
	$user_name = $current_user->display_name;
	$searchQuery .= " and (b.customer_name ='".$user_name."') ";
}


// Search by User code
if($searchByUser != ''){
	if( $searchByUser == 'mine' ) {
		$box_ids_for_user = '';
		$mini_query = "select distinct box_id from " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus where user_id = ".$current_user->ID;
		$mini_records = mysqli_query($con, $mini_query);
		while ($rox = mysqli_fetch_assoc($mini_records)) {
			$box_ids_for_user .= $rox['box_id'].", ";
		}
		$box_ids_for_user = substr($box_ids_for_user, 0, -2);
		
		if( $box_ids_for_user == null ) {
			$searchQuery .= " and (a.id IN (-99999)) ";
		} else {
			$searchQuery .= " and (a.id IN (".$box_ids_for_user.")) ";
		}
		
		
	} elseif( $searchByUser == 'not assigned' ) {
		
		// Register Box Status Taxonomy
		if( !taxonomy_exists('wpsc_box_statuses') ) {
			$args = array(
				'public' => false,
				'rewrite' => false
			);
			register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
		}
		
		// Get List of Box Statuses
		$box_statuses = get_terms([
			'taxonomy'   => 'wpsc_box_statuses',
			'hide_empty' => false,
			'orderby'    => 'meta_value_num',
			'order'    	 => 'ASC',
			'meta_query' => array('order_clause' => array('key' => 'wpsc_box_status_load_order')),
		]);
		
		// List of box status that do not need agents assigned.
		$ignore_box_status = ['Pending', 'Ingestion', 'Completed', 'Dispositioned'];
// 		$ignore_box_status = []; //show all box status
		
		$term_id_array = array();
		foreach( $box_statuses as $key=>$box ) {
			if( in_array( $box->name, $ignore_box_status ) ) {
				unset($box_statuses[$key]);
				
			} else {
				$term_id_array[] = $box->term_id;
			}
		}
		array_values($box_statuses);
		
		$search_in_box_statuses = '';
		foreach( $box_statuses as $status ) {
			$search_in_box_statuses .= $status->term_id.', ';
		}
		$search_in_box_statuses = substr($search_in_box_statuses, 0, -2);
		
		$box_ids_for_user = '';
		
		//Box status slugs
		$digitized_not_validated_tag = get_term_by('slug', 'closed', 'wpsc_box_statuses'); //6
		$qa_qc_tag = get_term_by('slug', 'q-a', 'wpsc_box_statuses'); //65
		$destruction_approval_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); //68
		$scanning_digitization_tag = get_term_by('slug', 'scanning-digitization', 'wpsc_box_statuses'); //671
		$scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); //672
		$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); //674
		$rescan_tag = get_term_by('slug', 're-scan', 'wpsc_box_statuses'); //743
		
		// Get all distinct box_id that have been assigned.
		$mini_query = "select box_id 
						from 
							" . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus 
						where 
							status_id IN (".$digitized_not_validated_tag->term_id.", ".$qa_qc_tag->term_id.", ".$destruction_approval_tag->term_id.", '".$scanning_digitization_tag->term_id."', ".$scanning_preparation_tag->term_id.", ".$validation_tag->term_id.", ".$rescan_tag->term_id.") 
						group by 
							box_id 
						having count(distinct status_id) = 7 ";
		$mini_records = mysqli_query($con, $mini_query); 
		while ($rox = mysqli_fetch_assoc($mini_records)) {
			$box_ids_for_user .= $rox['box_id'].", ";
		}
		$box_ids_for_user = substr($box_ids_for_user, 0, -2);
		
		$searchQuery .= " and (a.id NOT IN (".$box_ids_for_user.")) ";
	} elseif( $searchByUser == 'search for user' ) {
		$search_true = (isset($searchByUserAAVal) ) ? true : false;
		$array_of_wp_user_id = Patt_Custom_Func::translate_user_id($searchByUserAAVal, 'wp_user_id');
		$user_id_str = '';
 		if( $search_true ) {
			foreach( $array_of_wp_user_id as $id ) {
				$user_id_str .= $id.', ';
			}
			$user_id_str = substr($user_id_str, 0, -2);
			
			$box_ids_for_users = '';
			$mini_query = "select distinct box_id from " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus where user_id IN (".$user_id_str.")";
			$mini_records = mysqli_query($con, $mini_query);
			while ($rox = mysqli_fetch_assoc($mini_records)) {
				$box_ids_for_users .= $rox['box_id'].", ";
			}
			$box_ids_for_users = substr($box_ids_for_users, 0, -2);
			
			if( $user_id_str == '' ) {
				
			} else {
				if( $box_ids_for_users == null ) {
					$searchQuery .= " and (a.id IN (-99999)) ";
				} else {
					$searchQuery .= " and (a.id IN (".$box_ids_for_users.")) ";
				}
				
				//$searchQuery .= " and (a.id IN (".$box_ids_for_users.")) ";	
			}
		
		}
		

	}
	

}

//IF Search Generic Contains Commas

$searchForValue = ',';

if($searchGeneric != ''){
    
if(strpos($searchGeneric, $searchForValue) !== false){
    
//Strip spaces, breaks, tabs
$search_request_ids = preg_replace('/\s+/', '', $searchGeneric);

//Determine if ALL values are request IDs
   $var=explode(',',$search_request_ids);
   
   $count_var = count($var);
   
   $count_match = 0;
   foreach($var as $data)
    {

    $get_request = $wpdb->get_row("SELECT COUNT(id) as count
FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = ".$data);

    $request_id_match = $get_request->count;
    
    if($request_id_match != 0) {
    $count_match++;
    }
    
    if($count_var == $count_match) {
    
    $searchQuery .= " and b.request_id IN (".$search_request_ids.") ";
    
    } else {
    $searchQuery .= "";
    }
    
}

} else {

//    $searchQuery .= " and (a.folderdocinfofile_id like '%".$searchGeneric."%' or 
//       (a.pallet_id like '%".$searchGeneric."%' and a.pallet_id <> '') or
//       b.request_id like '%".$searchGeneric."%' or
//       c.office_acronym like '%".$searchGeneric."%') ";

		$searchQuery .= " and (a.folderdocinfofile_id like '%".$searchGeneric."%' or 
			status like '%".$searchGeneric."%') ";
}
}

if($searchValue != ''){
//    $searchQuery .= " and (a.folderdocinfofile_id like '%".$searchValue."%' or
//       (a.pallet_id like '%".$searchGeneric."%' and a.pallet_id <> '') or
//       b.request_id like '%".$searchValue."%' or
//       c.office_acronym like '%".$searchValue."%') ";

		$searchQuery .= " and (a.folderdocinfofile_id like '%".$searchValue."%' or
			status like '%".$searchGeneric."%')  ";
}


// ## Total number of records without filtering
// $sel = mysqli_query($con,"select count(DISTINCT a.box_id) as allcount 
// from " . $wpdb->prefix . "wpsc_epa_boxinfo as a
// INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
// INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
// INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
// INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
// LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
//    FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
//    GROUP BY box_id) AS f ON (f.box_id = a.id)

// LEFT JOIN (   SELECT a.box_id, a.return_id
//    FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
//    LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
//    WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
//    GROUP  BY a.box_id ) AS g ON g.box_id = a.id

// WHERE a.id <> -99999 AND b.active <> 0 " . $ecms_sems . " ");
// //$sel = mysqli_query($con,"select count(*) as allcount from wpqa_wpsc_epa_boxinfo WHERE id <> -99999");
// //$sel = mysqli_query($con,"select count(*) as allcount from wpqa_wpsc_ticket WHERE id <> -99999 AND active <> 0");
// $records = mysqli_fetch_assoc($sel);
// $totalRecords = $records['allcount'];


## Total number of records without filtering
$sel = mysqli_query($con,"SELECT COUNT(*) as allcount
FROM " . $wpdb->prefix . "epa_patt_arms_logs as a ");

$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];


// ## Total number of records with filtering
$sel = mysqli_query($con,"SELECT COUNT(*) as allcount 
FROM " . $wpdb->prefix . "epa_patt_arms_logs as a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as g ON g.folderdocinfofile_id = a.folderdocinfofile_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as d ON g.box_id = d.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as e ON d.storage_location_id = e.id
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.digitization_center
WHERE 1 " . $stage_status . $overall_status . $searchQuery);

// $sel = mysqli_query($con,"SELECT COUNT(*) as allcount
// FROM " . $wpdb->prefix . "epa_patt_arms_logs");



// $sel = mysqli_query($con,"select count(DISTINCT a.box_id) as allcount 
// FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a
// INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
// INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
// INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
// INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
// LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
//    FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
//    GROUP BY box_id) AS f ON (f.box_id = a.id)

// LEFT JOIN (   SELECT a.box_id, a.return_id
//    FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
//    LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
//    WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
//    GROUP  BY a.box_id ) AS g ON g.box_id = a.id

// WHERE (b.active <> 0) AND (a.id <> -99999) " . $ecms_sems . " AND 1 ".$searchQuery); //(b.active <> 0) AND


$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];




## Fetch records
//REVIEW
$boxQuery = "
SELECT *,
CONCAT(
	CASE 
	WHEN a.received_stage = 2 THEN CONCAT('<span>',a.folderdocinfofile_id,'</span>')
	ELSE CONCAT('<a href=\"admin.php?pid=docsearch&page=patttransferdetails&id=',a.folderdocinfofile_id,'\">',a.folderdocinfofile_id,'</a>') 
	END) as folderdocinfo_id_flag
FROM " . $wpdb->prefix . "epa_patt_arms_logs as a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as g ON g.folderdocinfofile_id = a.folderdocinfofile_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as d ON g.box_id = d.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as e ON d.storage_location_id = e.id
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.digitization_center
WHERE 1 " . $stage_status . $overall_status . $searchQuery." limit ".$row.",".$rowperpage;

$boxRecords = mysqli_query($con, $boxQuery);
$data = array();
$assigned_agents_icon = '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents()" class="assign_agents_icon"><i class="fas fa-user-friends" title="Assigned Agents"></i></span>';

while ($row = mysqli_fetch_assoc($boxRecords)) {
	


// Stages Status Codes Initialization
$received_status_code = 0;
$extraction_status_code = 0;
$keyword_status_code = 0;
$metadata_status_code = 0;




// Stages Icons
$received_pending_icon = '';
$received_success_icon = '';
$received_failure_icon = '';
$received_warning_icon = '';

$extraction_pending_icon = '';
$extraction_success_icon = '';
$extraction_failure_icon = '';
$extraction_warning_icon = '';

$keyword_pending_icon = '';
$keyword_success_icon = '';
$keyword_failure_icon = '';
$keyword_warning_icon = '';

$metadata_pending_icon = '';
$metadata_success_icon = '';
$metadata_failure_icon = '';
$metadata_warning_icon = '';

$arms_pending_icon = '';
$arms_success_icon = '';
$arms_failure_icon = '';
$arms_warning_icon = '';

/*$published_pending_icon = '';
$published_success_icon = '';
$published_failure_icon = '';
$published_warning_icon = '';*/

$clock_icon = '<a><span style="font-size: 1.2em; margin-left: 4px; color: #111;"><i class="fas fa-clock" aria-hidden="true" title="Duration"></i><span class="sr-only"></span></span></a>';
//$published_icon = '';
  $arms_icon = '';

// Received Stage Statuses
if($row['received_stage'] == 0) {
	// Pending Status
	$received_pending_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Pending"><span style="font-size: 1.3em; color: #1C5D8A;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i><span class="sr-only">Pending</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 26px;width: 3.5%;">';
}
elseif($row['received_stage'] == 1) {
	// Success Status
	$received_success_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Success"><span style="font-size: 1.3em; color: #2f631d;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i><span class="sr-only">Success</span></span></a> 
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 26px;width: 3.5%;">';
}
elseif($row['received_stage'] == 2) {
	// Fail Status
	$received_failure_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage Error: ' . $row['received_stage_log'] . '"><span style="font-size: 1.3em; color: #B4081A;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i><span class="sr-only">Failed</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 26px;width: 3.5%;">';
}
else {
	// Warning Status
	$received_warning_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Warning"><span style="font-size: 1.3em; color: #dba617;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i><span class="sr-only">Warning</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 26px;width: 3.5%;">';
}

// Extraction Stage Statuses
if($row['extraction_stage'] == 0) {
	// Pending Status
	$extraction_pending_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Extraction Stage: Pending"><span style="font-size: 1.3em; color: #1C5D8A;margin-left:4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i><span class="sr-only">Pending</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 50px;width: 3.5%;">';
}
elseif($row['extraction_stage'] == 1) {
	// Success Status
	$extraction_success_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Extraction Stage: Success"><span style="font-size: 1.3em; color: #2f631d; margin-left: 4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i><span class="sr-only">Success</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 50px;width: 3.5%;">';
}
elseif($row['extraction_stage'] == 2) {
	// Fail Status
	$extraction_failure_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Extraction Stage: Failed"><span style="font-size: 1.3em; color: #B4081A;margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i><span class="sr-only">Failed</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 50px;width: 3.5%;">';
}
else {
	// Warning Status
	$extraction_warning_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Extraction Stage: Warning"><span style="font-size: 1.3em; color: #dba617;margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i><span class="sr-only">Warning</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 50px;width: 3.5%;">';
}

// Keyword ID Stage Statuses
if($row['keyword_id_stage'] == 0) {
	// Pending Status
	$keyword_pending_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Keyword Stage: Pending"><span style="font-size: 1.3em; color: #1C5D8A;margin-left:4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i><span class="sr-only">Pending</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 75px;width: 3.5%;">';
}
elseif($row['keyword_id_stage'] == 1) {
	// Success Status
	$keyword_success_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Keyword Stage: Success"><span style="font-size: 1.3em; color: #2f631d;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i><span class="sr-only">Success</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 75px;width: 2.3%;">';
}
elseif($row['keyword_id_stage'] == 2) {
	// Fail Status
	$keyword_failure_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Keyword Stage: Failed"><span style="font-size: 1.3em; color: #B4081A;margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i><span class="sr-only">Failed</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 75px;width: 3.5%;">';
}
else {
	// Warning Status
	$keyword_warning_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Keyword Stage: Warning"><span style="font-size: 1.3em; color: #dba617;margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i><span class="sr-only">Warning</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 75px;width: 3.5%;">';
}

// Metadata Stage Statuses
if($row['metadata_stage'] == 0) {
	// Pending Status
	$metadata_pending_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Metadata Stage: Pending"><span style="font-size: 1.3em; color: #1C5D8A;margin-left:4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i><span class="sr-only">Pending</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 100px;width: 3.5%;">';
}
elseif($row['metadata_stage'] == 1) {
	// Success Status
	$metadata_success_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Metadata Stage: Success"><span style="font-size: 1.3em; color: #2f631d; margin-left:4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i><span class="sr-only">Success</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 100px;width: 3.5%;">';
}
elseif($row['metadata_stage'] == 2) {
	// Fail Status
	$metadata_failure_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Metadata Stage: Failed"><span style="font-size: 1.3em; color: #B4081A;margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i><span class="sr-only">Failed</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 100px;width: 3.5%;">';
}
else {
	// Warning Status
	$metadata_warning_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Metadata Stage: Warning"><span style="font-size: 1.3em; color: #dba617;margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i><span class="sr-only">Warning</span></span></a>
	<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 100px;width: 3.5%;">';
}

// ARMS Transfer Stage Statuses
if($row['arms_stage'] == 0) {
	// Pending Status
	$arms_pending_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="ARMS stage: Pending"><span style="font-size: 1.3em; color: #1C5D8A;margin-left:4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i><span class="sr-only">Pending</span></span></a>';
	//<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 125px;width: 3.5%;">';
}
elseif($row['arms_stage'] == 1) {
  $arms_url = ARMS_PERMALINK.$row['object_key'];
  //$arms_url = getenv('ARMS_PERMALINK').$row['object_key'];
	// Success Status
	$arms_success_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="ARMS stage: Success"><span style="font-size: 1.3em; color: #2f631d;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i><span class="sr-only">Success</span></span></a>';
  if($row['object_key'] != '' || $row['object_key'] != null) {
  	$arms_icon = '<a href="'.$arms_url.'" target="_blank"><span style="font-size: 1.2em; margin-left: 4px; color: #1C5D8A;"><i class="fa-regular fa-paper-plane" aria-hidden="true" title="ARMS"></i><span class="sr-only"></span></span></a>';
  } 
  // Testing ARMS Icon
  else {
    $arms_icon = '<a href="'.$arms_url.'" target="_blank"><span style="font-size: 1.2em; margin-left: 4px; color: #1C5D8A;"><i class="fa-regular fa-paper-plane" aria-hidden="true" title="ARMS"></i><span class="sr-only"></span></span></a>';
  }
	//<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 125px;width: 3.5%;">';
}
elseif($row['arms_stage'] == 2) {
	// Fail Status
	$arms_failure_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="ARMS stage: Failed"><span style="font-size: 1.3em; color: #B4081A;margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i><span class="sr-only">Failed</span></span></a>';
	//<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 125px;width: 3.5%;">';
}
else {
	// Warning Status
	$arms_warning_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="ARMS Stage: Warning"><span style="font-size: 1.3em; color: #dba617;margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i><span class="sr-only">Warning</span></span></a>';
	//<hr style="position: absolute;border: 0.1px solid gray;top: -2.5px;left: 125px;width: 3.5%;">';
}

// Published Stage Statuses
/*if($row['published_stage'] == 0) {
	// Pending Status
	$published_pending_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Published Stage: Pending"><span style="font-size: 1.3em; color: #1C5D8A;margin-left:4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i><span class="sr-only">Pending</span></span></a>';
}
elseif($row['published_stage'] == 1) {
	// Success Status
	$published_success_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Published Stage: Success"><span style="font-size: 1.3em; color: #2f631d;margin-left:4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i><span class="sr-only">Success</span></span></a>';
	$published_icon = '<a><span style="font-size: 1.2em; margin-left: 4px; color: #1C5D8A;"><i class="fa-solid fa-shield-blank" aria-hidden="true" title="Published"></i><span class="sr-only"></span></span></a>';
}
elseif($row['published_stage'] == 2) {
	// Fail Status
	$published_failure_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Published Stage: Failed"><span style="font-size: 1.3em; color: #B4081A;margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i><span class="sr-only">Failed</span></span></a>';
}
else {
	// Warning Status
	$published_warning_icon = '<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Published Stage: Warning"><span style="font-size: 1.3em; color: #dba617;margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warining"></i><span class="sr-only">Warining</span></span></a>';
}*/




	$start_stage = Date_Create($row['received_stage_timestamp']);
	$end_stage = Date_Create($row['metadata_stage_timestamp']);
	$duration = date_diff($end_stage,$start_stage);
	

	$data[] = array(
		"db_id"=>$row['ID'],
		"doc_id"=>$row['folderdocinfo_id_flag'].$arms_icon,
		"status"=>$row['status'],
      	//"received_stage"=>$received_pending_icon.$received_success_icon.$received_failure_icon.$received_warning_icon . '' . $extraction_pending_icon.$extraction_success_icon.$extraction_failure_icon.$extraction_warning_icon . '' . $keyword_pending_icon.$keyword_success_icon.$keyword_failure_icon.$keyword_warning_icon . '' . $metadata_pending_icon.$metadata_success_icon.$metadata_failure_icon.$metadata_warning_icon . '' . $arms_pending_icon.$arms_success_icon.$arms_failure_icon.$arms_warning_icon . '' . $published_pending_icon.$published_success_icon.$published_failure_icon.$published_warning_icon,
		"received_stage"=>$received_pending_icon.$received_success_icon.$received_failure_icon.$received_warning_icon . '' . $extraction_pending_icon.$extraction_success_icon.$extraction_failure_icon.$extraction_warning_icon . '' . $keyword_pending_icon.$keyword_success_icon.$keyword_failure_icon.$keyword_warning_icon . '' . $metadata_pending_icon.$metadata_success_icon.$metadata_failure_icon.$metadata_warning_icon . '' . $arms_pending_icon.$arms_success_icon.$arms_failure_icon.$arms_warning_icon,
		"location"=>$row['name'],
		"duration"=> $duration->format('%H:%I:%S').$clock_icon,
	);
}
## Response

$obj = array(
            'username'=>$lv_username,
            'address'=>$lv_address,
            'location'=>array('id'=>$lv_locationId)
    );
    
    
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data,
  //"test" => $boxQuery,
  "box_ids_for_user" => $box_ids_for_user,
  "box_ids_for_users" => $box_ids_for_users,
  "searchByUser" => $searchByUser,
  "box_ids_for_user" => $box_ids_for_user,
  "is_requester" => $is_requester,
  
		"test111"=>$boxQuery,
"test" => $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php'
);

echo json_encode($response);