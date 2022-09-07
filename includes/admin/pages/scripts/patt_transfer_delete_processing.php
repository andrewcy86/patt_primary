<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');
	
$host = DB_HOST; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

$con = mysqli_connect($host, $user, $password,$dbname);
// Check connection
if (!$con) {
  die("Connection failed: " . mysqli_connect_error());
}

function calculate_time_span($seconds)
{  
 $year = floor($seconds /31556926);
$months = floor($seconds /2629743);
$week=floor($seconds /604800);
$day = floor($seconds /86400); 
$hours = floor($seconds / 3600);
 $mins = floor(($seconds - ($hours*3600)) / 60); 
$secs = floor($seconds % 60);
 if($seconds < 60) $time = $secs." seconds ago";
 else if($seconds < 3600 ) $time =($mins==1)?$mins." minute ago":$mins." minutes ago";
 else if($seconds < 86400) $time = ($hours==1)?$hours." hour ago":$hours." hours ago";
 else if($seconds < 604800) $time = ($day==1)?$day." day ago":$day." days ago";
 else if($seconds < 2629743) $time = ($week==1)?$week." week ago":$week." weeks ago";
 else if($seconds < 31556926) $time =($months==1)? $months." month ago":$months." months ago";
 else $time = ($year==1)? $year." year ago":$year." years ago";
return $time; 
}  

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = $_POST['search']['value']; // Search value

//fixes sort for priority and status
if($columnName == 'ticket_priority') {
    $columnName = 'ticket_priority_order';
}
elseif($columnName == 'ticket_status') {
$columnName = 'ticket_status_order';
}
else {
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

## Custom Field value
$searchByDocID = str_replace(",", "|", $_POST['searchByDocID']);


## Search 
$searchQuery = "";

//Add Pallet Support
//Extract ID and determine if it is Box or Pallet
//$searchByBoxID = str_replace(",", "|", $_POST['searchByBoxID']);

// $BoxID_arr = explode(",", $_POST['searchByBoxID']);  

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

if($searchByDocID != ''){
    //used to be a.folderdocinfo_id
   $searchQuery .= "and (a.folderdocinfofile_id REGEXP '^(".$searchByDocID.")$' ) ";
}

if($newBoxID_str != ''){
   $searchQuery .= " and (a.folderdocinfofile_id REGEXP '^(".$newBoxID_str.")$' ) ";
}

if($newPalletID_str != ''){
   $searchQuery .= " and (a.pallet_id REGEXP '^(".$newPalletID_str.")$' ) ";
}

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
	if($searchByOverallStatus == 'Published') {
        $overall_status = ' AND status = "Published" ';
    }

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
	if($searchByStage == 'published') {
		$stage_status = ' AND published_stage = 1 ';
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


## Total number of records without filtering Filter out inactive (initially deleted tickets)
$sel = mysqli_query($con,"select count(*) as allcount from " . $wpdb->prefix . "epa_patt_arms_logs_archive");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
// $sel = mysqli_query($con,"select count(*) as allcount FROM (select COUNT(DISTINCT a.request_id) as allcount, GROUP_CONCAT(DISTINCT e.name ORDER BY e.name ASC SEPARATOR ', ') as location
// FROM " . $wpdb->prefix . "wpsc_ticket as a
// INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = a.id
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as b ON a.id = b.ticket_id
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON b.storage_location_id = d.id
// INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
// LEFT JOIN " . $wpdb->prefix . "users g ON g.display_name = a.customer_name
// WHERE 1 ".$searchQuery." AND a.active <> 1 " . $ecms_sems . "
// group by a.request_id ".$searchHaving.") t");

$sel = mysqli_query($con,"SELECT COUNT(DISTINCT a.folderdocinfofile_id) as allcount, GROUP_CONCAT(DISTINCT f.name ORDER BY f.name ASC SEPARATOR ', ') as location
FROM " . $wpdb->prefix . "epa_patt_arms_logs_archive as a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as g ON g.folderdocinfofile_id = a.folderdocinfofile_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as d ON g.box_id = d.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as e ON d.storage_location_id = e.id
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.digitization_center
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b
INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
LEFT JOIN " . $wpdb->prefix . "users h ON h.display_name = b.customer_name
WHERE 1 ".$searchQuery);

$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];


## Fetch records
// $boxQuery = "
// SELECT

// a.id as request_id,
// CONCAT(
// '<a href=\"admin.php?page=wpsc-tickets&id=',a.request_id,'\">',a.request_id,'</a> ') as request_id_flag,

// CONCAT(
// '<span class=\"wpsp_admin_label\" style=\"background-color:',
// (SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_priority_background_color' AND term_id = a.ticket_priority),
// ';color:',
// (SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_priority_color' AND term_id = a.ticket_priority),
// ';\">',
// (SELECT name from " . $wpdb->prefix . "terms where term_id = a.ticket_priority),
// '</span>') as ticket_priority,

// CASE 
// WHEN a.ticket_priority = 621 THEN 1
// WHEN a.ticket_priority = 9 THEN 2
// WHEN a.ticket_priority = 8 THEN 3
// WHEN a.ticket_priority = 7 THEN 4
// ELSE 999
// END
// as ticket_priority_order,

// CONCAT(
// '<span class=\"wpsp_admin_label\" style=\"background-color:',
// (SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_status_background_color' AND term_id = a.ticket_status),
// ';color:',
// (SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_status_color' AND term_id = a.ticket_status),
// ';\">',
// (SELECT name from " . $wpdb->prefix . "terms where term_id = a.ticket_status),
// '</span>') as ticket_status,

// CASE 
// WHEN a.ticket_status = 3 THEN 1
// WHEN a.ticket_status = 4 THEN 2
// WHEN a.ticket_status = 670 THEN 3
// WHEN a.ticket_status = 5 THEN 4
// WHEN a.ticket_status = 63 THEN 5
// WHEN a.ticket_status = 69 THEN 6
// ELSE 999
// END
// as ticket_status_order,

// CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = g.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = g.ID)) as full_name,
// CONCAT (
// CASE
// WHEN ((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = g.ID) <> '') AND ((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = g.ID) <> '') THEN CONCAT ( '<a href=\"#\" style=\"color: #000000 !important;\" data-toggle=\"tooltip\" data-placement=\"left\" data-html=\"true\" aria-label=\"Name\" title=\"',
// g.user_login
// ,'\">',

// (
//     CASE WHEN length(CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = g.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = g.ID))) > 15 THEN
//         CONCAT(LEFT(CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = g.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = g.ID)), 15), '...')
//     ELSE CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = g.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = g.ID))
//     END
// )

// ,'</a>' )
// ELSE g.user_login
// END
// ) as customer_name,

// a.date_updated as date_updated,

// GROUP_CONCAT(DISTINCT e.name ORDER BY e.name ASC SEPARATOR ', ') as location
// FROM " . $wpdb->prefix . "wpsc_ticket as a
// INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = a.id
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as b ON a.id = b.ticket_id
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON b.storage_location_id = d.id
// INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center

// LEFT JOIN " . $wpdb->prefix . "users g ON g.user_email = a.customer_email
// WHERE 1 ".$searchQuery." AND a.active <> 1 AND a.id <> -99999 " . $ecms_sems . "
// group by request_id ".$searchHaving." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;

// $boxQuery = "
// SELECT DISTINCT a.folderdocinfofile_id, a.status, b.customer_name, f.name,
// CONCAT('<a href=\"admin.php?pid=docsearch&page=patttransferdetails&id=',a.folderdocinfofile_id,'\">',a.folderdocinfofile_id,'</a>') as folderdocinfo_id_flag
// FROM " . $wpdb->prefix . "epa_patt_arms_logs_archive as a
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as g ON g.folderdocinfofile_id = a.folderdocinfofile_id
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as d ON g.box_id = d.id
// INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as e ON d.storage_location_id = e.id
// INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.digitization_center
// INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b
// INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
// LEFT JOIN " . $wpdb->prefix . "users h ON h.display_name = b.customer_name
// WHERE 1 ".$searchQuery." AND b.active <> 1 AND b.id <> -99999 " . $ecms_sems;

$boxQuery = "
SELECT *,
CONCAT('<a href=\"admin.php?pid=docsearch&page=patttransferdetails&id=',a.folderdocinfofile_id,'\">',a.folderdocinfofile_id,'</a>') as folderdocinfo_id_flag
FROM " . $wpdb->prefix . "epa_patt_arms_logs_archive as a";

$boxRecords = mysqli_query($con, $boxQuery);
$data = array();

while ($row = mysqli_fetch_assoc($boxRecords)) {
$seconds = time() - strtotime($row['date_updated']); 

$clock_icon = '<a><span style="font-size: 1.2em; margin-left: 4px; color: #111;"><i class="fas fa-clock" aria-hidden="true" title="Duration"></i><span class="sr-only"></span></span></a>';

$start_stage = Date_Create($row['received_stage_timestamp']);
	$end_stage = Date_Create($row['metadata_stage_timestamp']);
	$duration = date_diff($end_stage,$start_stage);

   $data[] = array(
    //  "request_id"=>$row['request_id'],
    //  //"request_id_flag"=>$row['request_id_flag'].$box_destroyed_icon.$freeze_icon.$unauthorized_destruction_icon,
    //  "request_id_flag" =>$row['request_id_flag'].$box_destroyed_icon.$unauthorized_destruction_icon.$damaged_icon.$freeze_icon,
    //  "ticket_priority"=>$row['ticket_priority'],
    //  "ticket_status"=>$row['ticket_status'],
    //  "customer_name"=>$row['customer_name'],
    //  "location"=>$row['location'],
    //  //"ticket_priority"=>$row['ticket_priority'],
    "date_updated"=>calculate_time_span($seconds),
    "db_id"=>$row['ID'],
    "doc_id"=>$row['folderdocinfo_id_flag'].$published_icon,
    "folderdocinfo_id"=>$row['folderdocinfofile_id'],
    "status"=>$row['status'],
    "customer_name"=>$row['customer_name'],
    "received_stage"=>$received_pending_icon.$received_success_icon.$received_failure_icon.$received_warning_icon . '' . $extraction_pending_icon.$extraction_success_icon.$extraction_failure_icon.$extraction_warning_icon . '' . $keyword_pending_icon.$keyword_success_icon.$keyword_failure_icon.$keyword_warning_icon . '' . $metadata_pending_icon.$metadata_success_icon.$metadata_failure_icon.$metadata_warning_icon . '' . $arms_pending_icon.$arms_success_icon.$arms_failure_icon.$arms_warning_icon . '' . $published_pending_icon.$published_success_icon.$published_failure_icon.$published_warning_icon,
    "location"=>$row['name'],
    "duration"=> $duration->format('%H:%I:%S').$clock_icon,
   );
}
## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);

echo json_encode($response);