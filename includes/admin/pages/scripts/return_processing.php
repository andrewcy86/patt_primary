<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');
	
$host = DB_HOST; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

$subfolder_path = site_url( '', 'relative'); 

global $current_user, $wpscfunction;

$con = mysqli_connect($host, $user, $password, $dbname);
// Check connection
if (!$con) {
  die("Connection failed: " . mysqli_connect_error());
}

$agent_permissions = $wpscfunction->get_current_agent_permissions();

// Icons
$icons = '';
$freeze_icon = ' <i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span>';
$unauth_dest_icon = ' <i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span>';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = $_POST['search']['value']; // Search value


$searchByDigitizationCenter = $_POST['searchByDigitizationCenter'];
$searchByProgramOffice = $_POST['searchByProgramOffice'];
//$searchByProgramOfficeID = $_POST['searchByProgramOfficeID'];
if($_POST['searchByReturnID']) {
	$searchByReturnID = explode(',',$_POST['searchByReturnID']);
	// Allow for filtering by full Return ID number (i.e. RTN-0000001)
	$return_ID_array_stripped = array();
	foreach( $searchByReturnID as $id ) {
		if( substr($id, 0, 1)=='r' ) {
// 			$return_ID_array_stripped[] = str_replace('rtn-', '', $id);
			$return_ID_array_stripped[] = str_replace('d-', '', $id);
		} else {
// 			$return_ID_array_stripped[] = str_replace('RTN-', '', $id);
			$return_ID_array_stripped[] = str_replace('D-', '', $id);
		}	
	}
}

$searchGeneric = $_POST['searchGeneric'];
$is_requester = $_POST['is_requester'];


## Fix for column order by request date
if ( $columnName == 'request_date' ) {
	$columnName = 'return_date';
}

## NEW METHOD
if( strpos($searchGeneric, 'D-') !== false || strpos($searchGeneric, 'd-') !== false ) {
	$searchGeneric = str_replace('d-', '', $searchGeneric);
	$searchGeneric = str_replace('D-', '', $searchGeneric);	
}
/*
if( strpos($searchGeneric, 'RTN-') !== false || strpos($searchGeneric, 'rtn-') !== false ) {
	$searchGeneric = str_replace('rtn-', '', $searchGeneric);
	$searchGeneric = str_replace('RTN-', '', $searchGeneric);	
}
*/


## Return ID Filter
$searchQuery = " ";

if( $return_ID_array_stripped ) {
	$return_id_str = implode('\',\'',$return_ID_array_stripped);
	$return_id_str = "'".$return_id_str."'";
	$searchQuery .= " AND return_id IN (".$return_id_str.") ";
}

## Filter - Digitization Center
if ( $searchByDigitizationCenter != '' ) {
//	$searchQuery .= " AND digitization_center = '".$searchByDigitizationCenter."' ";
// 	$searchQuery .= " AND ( digitization_center_box = '".$searchByDigitizationCenter."' or  digitization_center_folderdoc = '".$searchByDigitizationCenter."' ) ";
	$searchQuery .= " AND ( digitization_center_box like '%".$searchByDigitizationCenter."%' or  digitization_center_folderdoc like '%".$searchByDigitizationCenter."%' ) ";
}

## Filter - Program Office
if ( $searchByProgramOffice != '' ) {
//	$searchQuery .= " AND office_acronym = '".$searchByProgramOffice."' ";
// 	$searchQuery .= " AND ( office_acronym_combo_box = '".$searchByProgramOffice."' or  office_acronym_combo_folderdoc = '".$searchByProgramOffice."' ) ";
	$searchQuery .= " AND ( office_acronym_combo_box like '%".$searchByProgramOffice."%' or  office_acronym_combo_folderdoc like '%".$searchByProgramOffice."%' ) ";
}

// If a user is a requester, only show the boxes from requests (tickets) they have submitted. 
if( $is_requester == 'true' ) {
	
	// 	$user_name = $current_user->display_name;
	$user_id = $current_user->ID;
	$user_email = $current_user->user_email;
	// $searchQuery .= " and (innerTable.customer_name ='".$user_name."') ";
//	$searchQuery .= " and (innerTable.customer_email ='".$user_email."') ";		
	
	

    $get_aa_ship_groups = Patt_Custom_Func::get_requestor_group($user_id);
    $user_list = implode(",", $get_aa_ship_groups);
    if(!empty($user_list)) {
	$searchQuery .= " and (innerTable.customer_email like '%".$user_email."%' OR innerTable.user_id IN ($user_list)) ";
    } else {
	$searchQuery .= " and (innerTable.customer_email like '%".$user_email."%') ";       
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
		$searchQuery .= " and ( DATE(innerTable.return_date) = '".$searchDate."' ) ";
	} else {
/*		// Original: working
		$searchQuery .= " and (innerTable.return_id like '%".$searchGeneric."%' or 
			all_titles_folderdoc like '%".$searchGeneric."%' or
			all_titles_box like '%".$searchGeneric."%' or			 
			innerTable.office_acronym_combo_box like '%".$searchGeneric."%' or
			innerTable.office_acronym_combo_folderdoc like '%".$searchGeneric."%' or			
			innerTable.digitization_center_box like '%".$searchGeneric."%' or
			innerTable.digitization_center_folderdoc like '%".$searchGeneric."%') ";	
*/
			
		$searchQuery .= " and (innerTable.return_id like '%".$searchGeneric."%' or 
			all_titles_folderdoc like '%".$searchGeneric."%' or
			all_titles_box like '%".$searchGeneric."%' or			 
			innerTable.office_acronym_combo_box like '%".$searchGeneric."%' or
			innerTable.office_acronym_combo_folderdoc like '%".$searchGeneric."%' or			
			innerTable.digitization_center_box like '%".$searchGeneric."%' or
			innerTable.digitization_center_folderdoc like '%".$searchGeneric."%' or
			innerTable.display_box_id like '%".$searchGeneric."%' or 
			innerTable.display_folderdoc_id like '%".$searchGeneric."%') ";	
			
				
			
/*
			$searchQuery .= " and (innerTable.recall_id like '%".$searchGeneric."%' or 
			all_titles like '%".$searchGeneric."%' or 
			innerTable.office_acronym like '%".$searchGeneric."%' or
			innerTable.digitization_center like '%".$searchGeneric."%' or
			innerTable.box_id like '".$searchGeneric."' or 
			innerTable.folderdoc_id = '".$searchGeneric."') ";		
*/
			
			
	}
}

## Total number of records without filtering
$query = "select count(*) as allcount from " . $wpdb->prefix . "wpsc_epa_return WHERE id > 0";

$sel = mysqli_query($con,$query);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];


## OLD: working before dropping folderdocinfo table
/*

$baseQuery = "
SELECT
    " . $wpdb->prefix . "wpsc_epa_return.id,
    " . $wpdb->prefix . "wpsc_epa_return.return_id,
    " . $wpdb->prefix . "wpsc_epa_return.return_date,
    " . $wpdb->prefix . "wpsc_epa_return.return_receipt_date,
    " . $wpdb->prefix . "wpsc_epa_return.received_date,
    " . $wpdb->prefix . "wpsc_epa_return.comments,
    " . $wpdb->prefix . "wpsc_epa_return.return_status_id,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_ticket.customer_name
    ) AS customer_name,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_ticket.customer_email
    ) AS customer_email,
    T4.name AS return_status_name,
    " . $wpdb->prefix . "wpsc_epa_return.updated_date,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_return_items.box_id
    ) AS box_id,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_return_items.folderdoc_id
    ) AS folderdoc_id,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.company_name AS shipping_carrier,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.tracking_number,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.status,
    " . $wpdb->prefix . "terms.name AS reason,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_return_users.user_id
    ) AS user_id,
    GROUP_CONCAT(
        DISTINCT NULLIF(
            " . $wpdb->prefix . "wpsc_epa_program_office.office_acronym,
            -99999
        )
    ) AS office_acronym_combo_box,
    GROUP_CONCAT(
        DISTINCT NULLIF(PO2.office_acronym, -99999)
    ) AS office_acronym_combo_folderdoc,
    GROUP_CONCAT(DISTINCT T2.name) AS digitization_center_box,
    GROUP_CONCAT(DISTINCT T3.name) AS digitization_center_folderdoc,
    GROUP_CONCAT(DISTINCT FDIF.title) AS all_titles_folderdoc,
    GROUP_CONCAT(DISTINCT FDIF2.title) AS all_titles_box,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_folderdocinfo.folderdocinfo_id
    ) AS display_folderdoc_id,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id
    ) AS display_box_id
FROM
    " . $wpdb->prefix . "wpsc_epa_return
LEFT JOIN " . $wpdb->prefix . "terms ON " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_return.return_reason_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_return_items ON " . $wpdb->prefix . "wpsc_epa_return_items.return_id = " . $wpdb->prefix . "wpsc_epa_return.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_return_users ON " . $wpdb->prefix . "wpsc_epa_return_users.return_id = " . $wpdb->prefix . "wpsc_epa_return.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_shipping_tracking ON " . $wpdb->prefix . "wpsc_epa_shipping_tracking.id = " . $wpdb->prefix . "wpsc_epa_return.shipping_tracking_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo ON " . $wpdb->prefix . "wpsc_epa_boxinfo.id = " . $wpdb->prefix . "wpsc_epa_return_items.box_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_program_office ON " . $wpdb->prefix . "wpsc_epa_program_office.office_code = " . $wpdb->prefix . "wpsc_epa_boxinfo.program_office_id
LEFT JOIN " . $wpdb->prefix . "wpsc_ticket ON " . $wpdb->prefix . "wpsc_ticket.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo ON " . $wpdb->prefix . "wpsc_epa_folderdocinfo.id = " . $wpdb->prefix . "wpsc_epa_return_items.folderdoc_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo B2 ON
    B2.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo.box_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_program_office PO2 ON
    PO2.office_code = B2.program_office_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_storage_location.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id
LEFT JOIN " . $wpdb->prefix . "terms T2 ON
    T2.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center AND(
        NULLIF(
            " . $wpdb->prefix . "wpsc_epa_storage_location.id,
            -99999
        )
    )
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location Loc2 ON
    Loc2.id = B2.storage_location_id
LEFT JOIN " . $wpdb->prefix . "terms T3 ON
    T3.term_id = Loc2.digitization_center AND(NULLIF(Loc2.id, -99999))
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo FDI1 ON
    FDI1.id = " . $wpdb->prefix . "wpsc_epa_return_items.folderdoc_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files FDIF ON
    FDIF.folderdocinfo_id = FDI1.folderdocinfo_id    
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo B3 ON
    B3.id = " . $wpdb->prefix . "wpsc_epa_return_items.box_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo FDI2 ON
    FDI2.box_id = B3.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files FDIF2 ON
    FDIF2.folderdocinfo_id = FDI2.folderdocinfo_id    
LEFT JOIN " . $wpdb->prefix . "terms T4 ON
    T4.term_id = " . $wpdb->prefix . "wpsc_epa_return.return_status_id
WHERE
    " . $wpdb->prefix . "wpsc_epa_return.id > 0";    
*/

## Base Query for Records  // UPDATED: for Title and Date moved from folderdocinfo to folderdocinfo_files
$baseQuery = "
SELECT
    " . $wpdb->prefix . "wpsc_epa_return.id,
    " . $wpdb->prefix . "wpsc_epa_return.return_id,
    " . $wpdb->prefix . "wpsc_epa_return.return_date,
    " . $wpdb->prefix . "wpsc_epa_return.return_receipt_date,
    " . $wpdb->prefix . "wpsc_epa_return.received_date,
    " . $wpdb->prefix . "wpsc_epa_return.comments,
    " . $wpdb->prefix . "wpsc_epa_return.return_status_id,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_ticket.customer_name
    ) AS customer_name,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_ticket.customer_email
    ) AS customer_email,
    T4.name AS return_status_name,
    " . $wpdb->prefix . "wpsc_epa_return.updated_date,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_return_items.box_id
    ) AS box_id,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_return_items.folderdoc_id
    ) AS folderdoc_id,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.company_name AS shipping_carrier,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.tracking_number,
    " . $wpdb->prefix . "wpsc_epa_shipping_tracking.status,
    " . $wpdb->prefix . "terms.name AS reason,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_return_users.user_id
    ) AS user_id,
    GROUP_CONCAT(
        DISTINCT NULLIF(
            " . $wpdb->prefix . "wpsc_epa_program_office.office_acronym,
            -99999
        )
    ) AS office_acronym_combo_box,
    GROUP_CONCAT(
        DISTINCT NULLIF(PO2.office_acronym, -99999)
    ) AS office_acronym_combo_folderdoc,
    GROUP_CONCAT(DISTINCT T2.name) AS digitization_center_box,
    GROUP_CONCAT(DISTINCT T3.name) AS digitization_center_folderdoc,
    GROUP_CONCAT(DISTINCT FDIF.title) AS all_titles_folderdoc,
    GROUP_CONCAT(DISTINCT FDIF2.title) AS all_titles_box,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.folderdocinfofile_id
    ) AS display_folderdoc_id,
    GROUP_CONCAT(
        DISTINCT " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id
    ) AS display_box_id
FROM
    " . $wpdb->prefix . "wpsc_epa_return
LEFT JOIN " . $wpdb->prefix . "terms ON " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_return.return_reason_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_return_items ON " . $wpdb->prefix . "wpsc_epa_return_items.return_id = " . $wpdb->prefix . "wpsc_epa_return.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_return_users ON " . $wpdb->prefix . "wpsc_epa_return_users.return_id = " . $wpdb->prefix . "wpsc_epa_return.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_shipping_tracking ON " . $wpdb->prefix . "wpsc_epa_shipping_tracking.id = " . $wpdb->prefix . "wpsc_epa_return.shipping_tracking_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo ON " . $wpdb->prefix . "wpsc_epa_boxinfo.id = " . $wpdb->prefix . "wpsc_epa_return_items.box_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_program_office ON " . $wpdb->prefix . "wpsc_epa_program_office.office_code = " . $wpdb->prefix . "wpsc_epa_boxinfo.program_office_id
LEFT JOIN " . $wpdb->prefix . "wpsc_ticket ON " . $wpdb->prefix . "wpsc_ticket.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files ON " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.id = " . $wpdb->prefix . "wpsc_epa_return_items.folderdoc_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo B2 ON
    B2.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.box_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_program_office PO2 ON
    PO2.office_code = B2.program_office_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_storage_location.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id
LEFT JOIN " . $wpdb->prefix . "terms T2 ON
    T2.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center AND(
        NULLIF(
            " . $wpdb->prefix . "wpsc_epa_storage_location.id,
            -99999
        )
    )
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location Loc2 ON
    Loc2.id = B2.storage_location_id
LEFT JOIN " . $wpdb->prefix . "terms T3 ON
    T3.term_id = Loc2.digitization_center AND(NULLIF(Loc2.id, -99999))
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files FDIF ON
    FDIF.id = " . $wpdb->prefix . "wpsc_epa_return_items.folderdoc_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo B3 ON
    B3.id = " . $wpdb->prefix . "wpsc_epa_return_items.box_id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files FDIF2 ON
    FDIF2.box_id = B3.id
LEFT JOIN " . $wpdb->prefix . "terms T4 ON
    T4.term_id = " . $wpdb->prefix . "wpsc_epa_return.return_status_id
WHERE
    " . $wpdb->prefix . "wpsc_epa_return.id > 0";    


## Total number of records with filtering
$outterFilterQuery_start = "SELECT count(*) as allcount FROM  (";    
$outterFilterQuery_end = " GROUP BY " . $wpdb->prefix . "wpsc_epa_return.return_id ) AS innerTable WHERE 1 ";

$query_3 = $outterFilterQuery_start.$baseQuery.$outterFilterQuery_end.$searchQuery;

$sel = mysqli_query($con, $query_3);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];


## Return (Decline) Query
$outterQuery_start = "SELECT * FROM (";    
$outterQuery_end = ") AS innerTable WHERE 1 ";
$groupAndOrderBy = " GROUP BY " . $wpdb->prefix . "wpsc_epa_return.return_id order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$returnQuery = $outterQuery_start.$baseQuery.$groupAndOrderBy.$outterQuery_end.$searchQuery;

$returnRecords = mysqli_query($con, $returnQuery);


## Row Data 

$data = array();
$test = array();

while ($row = mysqli_fetch_assoc($returnRecords)) {

   	// Makes the Status column pretty
	$status_term_id = $row['return_status_id'];
	$status_background = get_term_meta($status_term_id, 'wppatt_return_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wppatt_return_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color.";";
	
	// Tracking Number link
	$shipping_link_start = "<a href='".Patt_Custom_Func::get_tracking_url($row['tracking_number'])."' target='_blank' />";
	$shipping_link_end = "</a>";
	
	$mask_length = 10;
	$tracking_num = $row['tracking_number'];
	if( strlen($row['tracking_number']) > $mask_length ) {
		$tracking_num = substr($tracking_num, 0, $mask_length);
		$tracking_num .= '...';
	}
	
//	$track = $shipping_link_start.$row['tracking_number'].$shipping_link_end;
	$track = $shipping_link_start.$tracking_num.$shipping_link_end;
	
	// icons
	$box_freeze = Patt_Custom_Func::id_in_freeze( $row['display_box_id'], 'box' );
	if( $box_freeze ) {
		$icons = $freeze_icon;
	}
	
	$box_unauth_dest = Patt_Custom_Func::id_in_unauthorized_destruction( $row['display_box_id'], 'box' );
	if( $box_unauth_dest ) {
		$icons .= $unauth_dest_icon;
	}
	
	if(($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')){	
    //$icons .= ' <span style="font-size: 1.0em; color: #8b0000;" onclick="edit_decline_to_do(\''.$row['return_id'].'\')" class="assign_agents_icon"><i class="fas fa-clipboard-check" aria-hidden="true" title="Decline To Do"></i><span class="sr-only">Decline To Do</span></span>';
    
    
    $decline_expired_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-expired' );
    $decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' );

    if(empty($row['tracking_number']) && $status_term_id != $decline_expired_term_id && $status_term_id != $decline_cancelled_term_id){
    $icons .= ' <span style="font-size: 1.0em; color: #8b0000;"><i class="fas fa-truck" aria-hidden="true" title="Shipping Tracking Number Required"></i><span class="sr-only">Shipping Tracking Number Required</span></span>';
    }
	    
	}	
   
   	$data[] = array(
		"return_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=declinedetails&id=D-".$row['return_id']."' >D-".$row['return_id']."</a>" . $icons, 		
		"return_id_flag"=>$row['return_id'],
		"status"=>"<span class='wpsp_admin_label' style='".$status_style."'>".$row['return_status_name']."</span>", 
		"updated_date"=>human_time_diff(strtotime($row['updated_date'])),
		"request_date"=> date('m/d/Y', strtotime( $row['return_date'] )),
		//"return_date"=> (strtotime( $row['return_date']) > 0) ? date('m/d/Y', strtotime( $row['return_date'])) : 'N/A', 
		"return_receipt_date"=> (strtotime( $row['return_receipt_date']) > 0) ? date('m/d/Y', strtotime( $row['return_receipt_date'])) : 'N/A', 	
		"received_date"=> (strtotime( $row['received_date']) > 0) ? date('m/d/Y', strtotime( $row['received_date'])) : 'N/A',	
//		"expiration_date"=>"90 Days", //date('m/d/Y', strtotime( $date_expiration)), 
 		"tracking_number"=>$track,
   );
   
   $icons = '';
   $test[] = $row['updated_date'];
   
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
  "query" => $returnQuery,  
  "Search Generic" => $searchGeneric,
  "Search Query" => $searchQuery,
  "Where" => 'unused',
  "Random Data - DC" => $searchByDigitizationCenter,
  "Random Data 2 - PO" => $searchByProgramOffice,
  "Filtered item query" => $query_3,
  "test" => $test,
);











/*

## OLD METHOD
$data2 = array();

##
$where = [
    // 'id' => 19,
    // 'id' => [19, 20],
    // 'recall_id' => 19,
    //     'recall_id' => ['0000001', '0000002'],
    //     'recall_id' => '',
    // 'recall_status_id' => 5,
    // 'program_office_id' => 2,
    //     'digitization_center' => 'East',
    //    'digitization_center' => $searchByDigitizationCenter,
    'filter' => [
        'records_per_page' => $rowperpage,
        'paged' => $row,
        'orderby' => $columnName,
        'order' => $columnSortOrder,
    ],
];

if($searchByDigitizationCenter) {
	$where['digitization_center'] = '"'.$searchByDigitizationCenter.'"';
}

if ($searchByProgramOffice) {
	// use function for id of program office
	$acro_num = Patt_Custom_Func::get_program_offic_id_by_acronym($searchByProgramOffice);
    $where['program_office_id'] = '"'.$acro_num.'"';
//     $where['program_office_id'] = '"'.$searchByProgramOffice.'"';

}

if(count($searchByReturnID) > 0){
	$where['return_id'] = $searchByReturnID;
}


if ($searchGeneric != '') {
	global $wpdb;
	
	if ( strpos($searchGeneric, 'RTN-') !== false ) {
	    $searchGeneric = str_replace('RTN-', '', $searchGeneric);
	}
	
    
    $where['custom'] = " ({$wpdb->prefix}wpsc_epa_return.return_id like '%" . $searchGeneric . "%' or
      {$wpdb->prefix}wpsc_epa_return.return_date like '%" . $searchGeneric . "%') ";  
}


$return_array = Patt_Custom_Func::get_return_data($where);
$display_filter = $where;
$display_filter['filter']['records_per_page'] = -1;
$return_total_records = Patt_Custom_Func::get_return_data($display_filter);


// Get all statuses
$tax = 'wppatt_return_statuses';
$status_list = Patt_Custom_Func::get_all_status_from_tax($tax);


foreach($return_array as $row) {
	if($row->id < 1) continue;


	$status_term_id = $row->return_status_id;
	$status_background = get_term_meta($status_term_id, 'wppatt_return_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wppatt_return_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color.";";
	$status_name = 'error';

	// link status
	if( array_key_exists($row->return_status_id, $status_list ) ) {
		$status_name = $status_list[$row->return_status_id];
	}

	$date_expiration = $row->expiration_date;
	$date_expiration = "90 Days";
	
	

	

$shipping_link_start = "<a href='".Patt_Custom_Func::get_tracking_url($row->tracking_number)."' target='_blank' />";
$shipping_link_end = "</a>";

	$data2[] = array(
		"return_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=returndetails&id=RTN-".$row->return_id."' >RTN-".$row->return_id."</a>", 		
		"return_id_flag"=>$row->return_id,
		"status"=>"<span class='wpsp_admin_label' style='".$status_style."'>".$status_name."</span>", 
 		"updated_date"=>human_time_diff(strtotime($row->updated_date)),
		"request_date"=> date('m/d/Y', strtotime( $row->return_date )),
		"return_receipt_date"=> (strtotime( $row->return_receipt_date) > 0) ? date('m/d/Y', strtotime( $row->return_receipt_date)) : 'N/A', 		
		"tracking_number"=>$shipping_link_start.$row->tracking_number.$shipping_link_end,
   );


}




## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => count($return_total_records)-1, //$totalRecords,
  "iTotalDisplayRecords" => count($return_total_records)-1, // $totalRecordwithFilter,
  "aaData" => $data2,
  "request" => $_REQUEST,
  "query" => $return_array['query'],
  "search Generic" => $searchGeneric,
  "Where" => $where['custom'],
  "Program Office " => $searchByProgramOffice.' :: '.$acro_num,
);
*/

// print_r($_POST); 
echo json_encode($response);