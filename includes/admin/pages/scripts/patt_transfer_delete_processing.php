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
$searchByRequestID = str_replace(",", "|", $_POST['searchByRequestID']);
$searchByDigitizationCenter = $_POST['searchByDigitizationCenter'];
$searchGeneric = $_POST['searchGeneric'];
$searchByRequest = $_POST['searchRequest'];
$searchByStatus = $_POST['searchByStatus'];
$searchByPriority = $_POST['searchByPriority'];
$searchByECMSSEMS = $_POST['searchByECMSSEMS']; // ECMS has been updated to be called ARMS instead
$currentUser = $_POST['currentUser'];
## User Search
//throwing Undefined Index error
if(isset($_POST['searchByUser'])) {
    $searchByUser = $_POST['searchByUser'];
}
else {
    $searchByUser = '';
}

if(isset($_POST['searchByUserAAVal'])) {
    $searchByUserAAVal = $_POST['searchByUserAAVal'];
}
else {
    $searchByUserAAVal = '';
}

$searchByUserAAName = str_replace(",", "|", $_POST['searchByUserAAName']);
$searchByUserAANameQuoted = str_replace(",", "','", $_POST['searchByUserAAName']);
$searchByUserAANameQuoted = "'".$searchByUserAANameQuoted."'";

## Search 
$searchQuery = " ";
$searchHaving = " ";
$locationarray = array("east", "west", "east cui", "west cui", "not assigned");

if($searchByUser == 'mine') {
   $searchQuery .= " and (a.customer_name ='".$currentUser."') ";    
}

if($searchByUser == 'search for user') {
	if( strlen($searchByUserAAName) == 0  ) {
		//$searchQuery .= "";
	} else {
		$searchQuery .= " and (a.customer_name IN (".$searchByUserAANameQuoted.")) ";	
	}
	}

if($searchByRequest != '' && $searchByRequest != 'all' ){
   $searchQuery .= " and (a.customer_name='".$searchByRequest."') ";
}

if($searchByRequestID != ''){
   $searchQuery .= " and (a.request_id REGEXP '^(".$searchByRequestID.")$' ) ";
}

if($searchByStatus != ''){
   $searchQuery .= " and (a.ticket_status='".$searchByStatus."') ";
}

if($searchByPriority != ''){
   $searchQuery .= " and (a.ticket_priority='".$searchByPriority."') ";
}

if($searchByDigitizationCenter != ''){
   $searchHaving = " HAVING location like '%".$searchByDigitizationCenter."%' ";
}

$ecms_sems = '';

if($searchByECMSSEMS != ''){
    if($searchByECMSSEMS == 'ECMS') {
        $ecms_sems = ' AND z.meta_key = "super_fund" AND z.meta_value = "false" ';
    }
    
    if($searchByECMSSEMS == 'SEMS') {
        $ecms_sems = ' AND z.meta_key = "super_fund" AND z.meta_value = "true" ';
    }
}

if($searchGeneric != ''){
if(in_array(strtolower($searchGeneric), $locationarray)){
   $searchHaving = " HAVING location like '%".$searchGeneric."%' ";
} else {
   $searchQuery .= " and (a.request_id like '%".$searchGeneric."%' or
      a.customer_name like '%".$searchGeneric."%') ";    
}

}

if($searchValue != ''){
if(in_array(strtolower($searchGeneric), $locationarray)){
   $searchHaving = " HAVING location like '%".$searchGeneric."%' ";
} else {
   $searchQuery .= " and (a.request_id like '%".$searchGeneric."%' or
      a.customer_name like '%".$searchGeneric."%') ";    
}
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