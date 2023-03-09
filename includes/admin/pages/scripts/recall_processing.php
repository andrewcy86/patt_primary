<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');
	
$host = DB_HOST; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

$subfolder_path = site_url( '', 'relative'); 

global $current_user, $wpscfunction;

$con = mysqli_connect($host, $user, $password,$dbname);
// Check connection
if (!$con) {
  die("Connection failed: " . mysqli_connect_error());
}

$agent_permissions = $wpscfunction->get_current_agent_permissions();

// Variables
$icons = '';
$freeze_icon = ' <i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span>';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = $_POST['search']['value']; // Search value //not used. old 'searchGeneric'

$searchByUser = $_POST['searchByUser'];
$currentUser = $_POST['currentUser'];
$searchByDigitizationCenter = $_POST['searchByDigitizationCenter'];
$searchByProgramOffice = $_POST['searchByProgramOffice'];


if($_POST['searchByRecallID']) {
	$searchByRecallID = explode(',',$_POST['searchByRecallID']);
	// Allow for filtering by full Recall ID number (i.e. R-0000001)
	$recall_ID_array_stripped = array();
	foreach( $searchByRecallID as $id ) {
		if( substr($id, 0, 1)=='r' ) {
			$recall_ID_array_stripped[] = str_replace('r-', '', $id);
		} else {
			$recall_ID_array_stripped[] = str_replace('R-', '', $id);
		}	
	}
}

$searchGeneric = trim($_POST['searchGeneric']);
$is_requester = $_POST['is_requester'];

if( strpos($searchGeneric, 'R-') !== false || strpos($searchGeneric, 'r-') !== false ) {
	$searchGeneric = str_replace('r-', '', $searchGeneric);
	$searchGeneric = str_replace('R-', '', $searchGeneric);	
}


## Recall ID Filter
$searchQuery = " ";

if( $recall_ID_array_stripped ) {
	$recall_id_str = implode('\',\'',$recall_ID_array_stripped);
	$recall_id_str = "'".$recall_id_str."'";
	$searchQuery .= " AND (recall_id IN (".$recall_id_str.")) ";
}

## Filter - Digitization Center
/*if ( $searchByDigitizationCenter != '' ) {
	$searchQuery .= " AND (digitization_center = '".$searchByDigitizationCenter."') ";
}*/

if ( $searchByDigitizationCenter != '' ) {
	$searchHaving = " HAVING (digitization_center = '".$searchByDigitizationCenter."') ";
}

## Filter - Program Office
if ( $searchByProgramOffice != '' ) {
	$searchQuery .= " AND (office_acronym = '".$searchByProgramOffice."') ";
}

// If a user is a requester, only show the boxes from requests (tickets) they have submitted. 
if( $is_requester == 'true' ) {
	$user_name = $current_user->display_name;
	$user_id = $current_user->ID;
    $get_aa_ship_groups = Patt_Custom_Func::get_requestor_group($user_id);
    $user_list = implode(",", $get_aa_ship_groups);
    if(!empty($user_list)) {

	$searchQuery .= " and (innerTable.customer_name ='".$user_name."' OR innerTable.user_id IN ($user_list)) ";
	
    } else {
	$searchQuery .= " and (innerTable.customer_name ='".$user_name."') ";        
    }
}


## Search 

if($searchGeneric != ''){
	
	$date_search = false;
	if( strpos($searchGeneric, '/') !== false ) {
		$date_search = true;
	}
	
	if( $date_search ) {
		$searchDate = date_create($searchGeneric);
		$searchDate = date_format($searchDate,"Y-m-d");
		$searchQuery .= " and ( DATE(innerTable.request_date) = '".$searchDate."' ) ";
	} else {
/*
		$searchQuery .= " and (innerTable.recall_id like '%".$searchGeneric."%' or 
			all_titles like '%".$searchGeneric."%' or 
			innerTable.office_acronym like '%".$searchGeneric."%' or
			innerTable.digitization_center like '%".$searchGeneric."%') ";	
*/


		$searchQuery .= " and (innerTable.recall_id like '%".$searchGeneric."%' or 
			all_titles like '%".$searchGeneric."%' or 
			innerTable.office_acronym like '%".$searchGeneric."%' or
			innerTable.digitization_center like '%".$searchGeneric."%' or
			innerTable.box_id = '".$searchGeneric."' or 
			innerTable.folderdoc_id like '".$searchGeneric."%') ";				
			
	}
}

## Total number of records without filtering
$query = "select count(*) as allcount from " . $wpdb->prefix . "wpsc_epa_recallrequest WHERE id > 0";

$sel = mysqli_query($con,$query);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];



## Base Query for Records  // UPDATED: folderdocinfo_files JOINED to recallrequest rather than via FDI
$baseQuery = "
SELECT
    " . $wpdb->prefix . "wpsc_epa_recallrequest.id,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_id,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_approved,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.expiration_date,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.request_date,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.request_receipt_date,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.return_date,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.updated_date,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.comments,
    " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_status_id,
    " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id,
    " . $wpdb->prefix . "wpsc_epa_storage_location.id AS location_id,
    T2.name AS digitization_center,
    " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id,
    " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id,
    " . $wpdb->prefix . "wpsc_epa_boxinfo.location_status_id,
    " . $wpdb->prefix . "wpsc_epa_boxinfo.box_destroyed,
    " . $wpdb->prefix . "wpsc_epa_boxinfo.date_created,
    " . $wpdb->prefix . "wpsc_epa_boxinfo.date_updated,
    FDIF.title,
    FDIF.folderdocinfofile_id AS folderdoc_id,
    " . $wpdb->prefix . "wpsc_epa_program_office.office_acronym,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.company_name AS shipping_carrier,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.tracking_number,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.status,
    " . $wpdb->prefix . "wpsc_ticket.customer_name,
    CONCAT(
        " . $wpdb->prefix . "epa_record_schedule.Schedule_Item_Number,
        ': ',
        " . $wpdb->prefix . "epa_record_schedule.Schedule_Title
    ) AS Record_Schedule,
    CONCAT(
        " . $wpdb->prefix . "epa_record_schedule.Schedule_Item_Number,
        ': ',
        " . $wpdb->prefix . "epa_record_schedule.Schedule_Title
    ) AS Schedule_Item_Number,
    CONCAT(
        " . $wpdb->prefix . "epa_record_schedule.Schedule_Item_Number,
        ': ',
        " . $wpdb->prefix . "epa_record_schedule.Schedule_Title
    ) AS Schedule_Title,
    T1.name AS recall_status,
    GROUP_CONCAT(
        " . $wpdb->prefix . "wpsc_epa_recallrequest_users.user_id
    ) AS user_id,
    CASE WHEN folderdoc_id = -99999 THEN GROUP_CONCAT(FDIF2.title) ELSE FDIF.title
END AS all_titles
FROM
    " . $wpdb->prefix . "wpsc_epa_recallrequest
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo ON " . $wpdb->prefix . "wpsc_epa_boxinfo.id = " . $wpdb->prefix . "wpsc_epa_recallrequest.box_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users ON " . $wpdb->prefix . "wpsc_epa_recallrequest_users.recallrequest_id = " . $wpdb->prefix . "wpsc_epa_recallrequest.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files FDIF ON FDIF.id = " . $wpdb->prefix . "wpsc_epa_recallrequest.folderdoc_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_program_office ON " . $wpdb->prefix . "wpsc_epa_program_office.id = " . $wpdb->prefix . "wpsc_epa_recallrequest.program_office_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_shipping_tracking ON " . $wpdb->prefix . "wpsc_epa_shipping_tracking.id = " . $wpdb->prefix . "wpsc_epa_recallrequest.shipping_tracking_id
LEFT JOIN " . $wpdb->prefix . "wpsc_ticket ON " . $wpdb->prefix . "wpsc_ticket.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id
LEFT JOIN " . $wpdb->prefix . "epa_record_schedule ON " . $wpdb->prefix . "epa_record_schedule.id = " . $wpdb->prefix . "wpsc_epa_recallrequest.record_schedule_id
LEFT JOIN " . $wpdb->prefix . "terms T1 ON
    T1.term_id = " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_status_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_storage_location.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id
LEFT JOIN " . $wpdb->prefix . "terms T2 ON
    T2.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files FDIF2 ON FDIF2.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id 

WHERE
    " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_id > 0";


## Total number of records with filtering
$outterFilterQuery_start = "SELECT count(*) as allcount FROM  (";    
//$outterFilterQuery_end = " GROUP BY " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_id ) AS innerTable WHERE 1 ";
$otterFiltergroupAndHaving = " GROUP BY " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_id".$searchHaving;
$outterFilterQuery_end = " ) AS innerTable WHERE 1 ";

$query_3 = $outterFilterQuery_start.$baseQuery.$searchQuery.$otterFiltergroupAndHaving.$outterFilterQuery_end;


$sel = mysqli_query($con, $query_3);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Recall Query
$outterQuery_start = "SELECT * FROM (";    
$outterQuery_end = ") AS innerTable WHERE 1 ";
$groupAndOrderBy = " GROUP BY " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_id order by ".$columnName." limit ".$row.",".$rowperpage;
$groupAndHaving = " GROUP BY " . $wpdb->prefix . "wpsc_epa_recallrequest.recall_id".$searchHaving." order by ".$columnName." limit ".$row.",".$rowperpage;
//$recallQuery = $outterQuery_start.$baseQuery.$groupAndOrderBy.$outterQuery_end.$searchQuery;
$recallQuery = $outterQuery_start.$baseQuery.$searchQuery.$groupAndHaving.$outterQuery_end;

$recallRecords = mysqli_query($con, $recallQuery);


## Row Data

$data = array();

while ($row = mysqli_fetch_assoc($recallRecords)) {
  	
  	//$totalRecordwithFilter = $row['total_count'];

   	// Makes the Status column pretty
	$status_term_id = $row['recall_status_id'];
	$status_background = get_term_meta($status_term_id, 'wppatt_recall_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wppatt_recall_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color.";";
	
	// Tracking Number link
	$shipping_link_start = "<a href='".Patt_Custom_Func::get_tracking_url($row['tracking_number'])."' target='_blank' />";
	$shipping_link_end = "</a>";
	$mask_length = 13;
	$tracking_num = $row['tracking_number'];
	if( strlen($row['tracking_number']) > $mask_length ) {
		$tracking_num = substr($tracking_num, 0, $mask_length);
		$tracking_num .= '...';
	}
	
	// Add icons
	//if( $row['box_id'] != null ) {
	if( $row['folderdoc_id'] == null ) {	
		$box_freeze = Patt_Custom_Func::id_in_freeze( $row['box_id'], 'box' );
		if( $box_freeze ) {
			$icons = $freeze_icon;
		}
	} else {
		$file_freeze = Patt_Custom_Func::id_in_freeze( $row['folderdoc_id'], 'folderfile' );
		if( $file_freeze ) {
			$icons = $freeze_icon;
		}
	}
	
	if(($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')){	
	    $icons .= ' <span style="font-size: 1.0em; color: #8b0000;" onclick="edit_recall_to_do(\''.$row['recall_id'].'\')" class="assign_agents_icon"><i class="fas fa-clipboard-check" aria-hidden="true" title="Recall To Do"></i><span class="sr-only">Recall To Do</span></span>';
    }
	
	
	$track = $shipping_link_start.$tracking_num.$shipping_link_end;
   
   	$data[] = array(
		"recall_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=recalldetails&id=R-".$row['recall_id']."' >R-".$row['recall_id']."</a>" . $icons, 		
		"recall_id_flag"=>$row['recall_id'],
		"status"=>"<span class='wpsp_admin_label' style='".$status_style."'>".$row['recall_status']."</span>", 
		"updated_date"=>human_time_diff(strtotime($row['updated_date'])),
		"request_date"=> date('m/d/Y', strtotime( $row['request_date'] )),
		"return_date"=> (strtotime( $row['return_date']) > 0) ? date('m/d/Y', strtotime( $row['return_date'])) : 'N/A', 
		"request_receipt_date"=> (strtotime( $row['request_receipt_date']) > 0) ? date('m/d/Y', strtotime( $row['request_receipt_date'])) : 'N/A', 		
//		"expiration_date"=>"90 Days", //date('m/d/Y', strtotime( $date_expiration)), 
//		"tracking_number"=>$row['tracking_number'],
 		"tracking_number"=>$track,
 		"recall_approved"=>$row['recall_approved']
   );
   
   // Clear icons
   $icons = '';

/*
   $data[] = array(
     "folderdocinfo_id"=>$row['folderdocinfo_id'],
     "recall_id_flag"=>$row['recall_id_flag'],
     "title"=>$row['title'],
     "date"=>$row['date'],
     "epa_contact_email"=>$row['epa_contact_email'],
     "validation"=>$row['validation']
   );
*/
}

if (empty($totalRecordwithFilter)) {
  $totalRecordwithFilter = 0;
}

## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
//  "iTotalRecords" => count($recall_total_records) - 1, //$totalRecords,
//  "iTotalDisplayRecords" => count($recall_total_records) - 1, // $totalRecordwithFilter,
//   "aaData" => $data2,
  "aaData" => $data,  
  "request" => $_REQUEST,
//   "query" => $recall_array['query'],
  "query" => $recallQuery,  
  "Search Generic" => $searchGeneric,
  "Search Query" => $searchQuery,
  "Where" => $where['custom'],
  "Random Data - DC" => $searchByDigitizationCenter,
  "Random Data 2 - PO" => $searchByProgramOffice,
  "Filtered item query" => $query_3,
  "debug" => $columnName
);



echo json_encode($response);