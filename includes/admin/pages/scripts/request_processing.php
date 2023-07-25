<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');

global $wpdb, $current_user, $wpscfunction;

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

if($columnName == 'ticket_priority') {
$columnName = 'ticket_priority_order';
} elseif($columnName == 'ticket_status') {
$columnName = 'ticket_status_order';
} else {
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
}

## Custom Field value
$searchByRequestID = str_replace(",", "|", $_POST['searchByRequestID']);
$searchByDigitizationCenter = $_POST['searchByDigitizationCenter'];
$searchGeneric = $_POST['searchGeneric'];
$searchByStatus = $_POST['searchByStatus'];
$searchByPriority = $_POST['searchByPriority'];
$searchByRecallDecline = $_POST['searchByRecallDecline'];
$searchByECMSSEMS = $_POST['searchByECMSSEMS'];
$currentUser = $_POST['currentUser'];

$totalRecordwithFilter = '';
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

$get_aa_ship_groups = Patt_Custom_Func::get_requestor_group($currentUser);
$user_list = implode(",", $get_aa_ship_groups);

if($searchByUser == 'mine') {
   if(!empty($user_list)) {
        $searchQuery .= " and (a.customer_name ='".$currentUser."' OR um.user_id IN ($user_list)) ";  
   }
   else {
       $searchQuery .= " and (a.customer_name ='".$currentUser."') ";  
   }
}

if($searchByUser == 'search for user') {
	
	if( strlen($searchByUserAAName) == 0  ) {
		//$searchQuery .= "";
	} else {
		$searchQuery .= " and (a.customer_name IN (".$searchByUserAANameQuoted.")) ";	
	}
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
   $searchQuery .= " and (e.name ='".$searchByDigitizationCenter."') ";
}

/*if($searchByDigitizationCenter != ''){
   $searchHaving = " HAVING location like '%".$searchByDigitizationCenter."%' ";
}*/

//Get term_ids for Recall status slugs
$status_recall_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	 // 878
$status_recall_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' ); //734
$status_recall_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' ); //733

$status_decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' );	 // 791
$status_decline_completed_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-complete' ); //754
    
if($searchByRecallDecline != ''){

        if($searchByRecallDecline == 'Recall') {
            $searchQuery .= "and (
            COALESCE(x.recall_status_id, h.recall_status_id) NOT IN (".$status_recall_denied_term_id.",".$status_recall_cancelled_term_id.",".$status_recall_complete_term_id.")
            )";
        }

        if($searchByRecallDecline == 'Decline') {
            $searchQuery .= "and (
            i.return_id <> '' OR j.return_id <> ''
            )";
        }

}

$ecms_sems = '';

if($searchByECMSSEMS != ''){
    if($searchByECMSSEMS == 'ECMS') {
        $ecms_sems = 'AND z.meta_key = "super_fund" AND z.meta_value = "false" ';
    }
    
    if($searchByECMSSEMS == 'SEMS') {
        $ecms_sems = 'AND z.meta_key = "super_fund" AND z.meta_value = "true" ';
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

## Fetch records
$boxQuery = "
SELECT
count(a.id) AS total_count,
a.id as request_id,
a.request_id as patt_request_id,
a.date_updated as date_updated,
e.name as location,
CONCAT(
'<a href=\"admin.php?page=wpsc-tickets&id=',a.request_id,'\">',a.request_id,'</a> ') as request_id_flag,
a.ticket_priority as ticket_priority_order,
a.ticket_status as ticket_status_order,
 
z.meta_key as due_date

FROM " . $wpdb->prefix . "wpsc_ticket as a
INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as b ON a.id = b.ticket_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON b.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as k ON k.box_id = b.id

LEFT JOIN " . $wpdb->prefix . "users g ON g.user_email = a.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = g.ID



WHERE 1 ".$searchQuery." AND a.active <> 0 AND a.id <> -99999 " . $ecms_sems . "
group by a.request_id ".$searchHaving." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;

## Total number of records without filtering Filter out inactive (initially deleted tickets)
$TotalCount = "
SELECT
a.id as request_id,
GROUP_CONCAT(DISTINCT e.name ORDER BY e.name ASC SEPARATOR ', ') as location
FROM " . $wpdb->prefix . "wpsc_ticket as a
INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as b ON a.id = b.ticket_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON b.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as k ON k.box_id = b.id

LEFT JOIN " . $wpdb->prefix . "users g ON g.user_email = a.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = g.ID

LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS x ON (x.box_id = b.id AND x.folderdoc_id = '-99999')

LEFT JOIN (   SELECT DISTINCT recall_status_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY folderdoc_id) AS h ON (h.folderdoc_id = k.id AND h.folderdoc_id <> '-99999')
   
LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY box_id ) AS i ON i.box_id = b.id
LEFT JOIN (   SELECT a.folderdoc_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE folderdoc_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY folderdoc_id )  AS j ON j.folderdoc_id = k.id

WHERE a.active <> 0 AND a.id <> -99999 " . $ecms_sems . "
group by a.request_id ";

$sel = mysqli_query($con,$TotalCount);

$totalRecords = 0;

while ($row = mysqli_fetch_assoc($sel)) {
$totalRecords++;
}

$boxRecords = mysqli_query($con, $boxQuery);
$data = array();

while ($row = mysqli_fetch_assoc($boxRecords)) {

$request_id = $row['request_id'];
$totalRecordwithFilter = $row['total_count'];
// GET LOCATION
$location_query = $wpdb->get_row("SELECT GROUP_CONCAT(DISTINCT d.name ORDER BY d.name ASC SEPARATOR ', ') as location 
FROM " . $wpdb->prefix . "wpsc_ticket as a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as b ON a.id = b.ticket_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as c ON b.storage_location_id = c.id
INNER JOIN " . $wpdb->prefix . "terms d ON d.term_id = c.digitization_center
WHERE a.id = ".$request_id);
$location = $location_query->location;
  
// GET CUSTOMER NAME AND FULL NAME
$customer_query = $wpdb->get_row("SELECT 
CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = b.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = b.ID)) as full_name,
CONCAT (
CASE
WHEN ((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = b.ID) <> '') AND ((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = b.ID) <> '') THEN CONCAT ( '<a href=\"#\" style=\"color: #000000 !important;\" data-toggle=\"tooltip\" data-placement=\"left\" data-html=\"true\" aria-label=\"Name\" title=\"',
b.user_login
,'\">',

(
    CASE WHEN length(CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = b.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = b.ID))) > 15 THEN
        CONCAT(LEFT(CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = b.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = b.ID)), 15), '...')
    ELSE CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = b.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = b.ID))
    END
)

,'</a>' )
ELSE b.user_login
END
) as customer_name

FROM " . $wpdb->prefix . "wpsc_ticket as a
LEFT JOIN " . $wpdb->prefix . "users b ON b.user_email = a.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = b.ID
WHERE a.id = ".$request_id);
$full_name_pre = $customer_query->full_name;
$customer_name = $customer_query->customer_name;
$full_name = '<span style="display: none;" aria-label="'.$full_name_pre.'"></span>';

//GET Ticket Status
$status_query = $wpdb->get_row("SELECT 
CONCAT(
'<span class=\"wpsp_admin_label\" style=\"background-color:',
(SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_status_background_color' AND term_id = a.ticket_status),
';color:',
(SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_status_color' AND term_id = a.ticket_status),
';\">',
(SELECT name from " . $wpdb->prefix . "terms where term_id = a.ticket_status),
'</span>') as ticket_status
FROM " . $wpdb->prefix . "wpsc_ticket as a
WHERE a.id = ".$request_id);

$status = $status_query->ticket_status;
  
//GET Ticket Priority
$priority_query = $wpdb->get_row("SELECT
CONCAT(
'<span class=\"wpsp_admin_label\" style=\"background-color:',
(SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_priority_background_color' AND term_id = a.ticket_priority),
';color:',
(SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_priority_color' AND term_id = a.ticket_priority),
';\">',
(SELECT name from " . $wpdb->prefix . "terms where term_id = a.ticket_priority),
'</span>') as ticket_priority
FROM " . $wpdb->prefix . "wpsc_ticket as a
WHERE a.id = ".$request_id);

$priority = $priority_query->ticket_priority;


$seconds = time() - strtotime($row['date_updated']); 

$decline_icon = '';
$recall_icon = '';
$unauthorized_destruction_icon = '';
$freeze_icon = '';
$box_destroyed_icon = '';
$damaged_icon = '';
$type = 'request';

if(Patt_Custom_Func::id_in_return($row['patt_request_id'],$type) == 1){
//$decline_icon = '<span style="margin-left:4px;"><img src="'.WPPATT_PLUGIN_URL.'asset/images/decline.gif" alt="Declined"/></span>';
$decline_icon = '<span style="font-size: 1em; color: #FF0000;margin-left:4px;"><i class="fas fa-undo" aria-hidden="true" title="Declined"></i><span class="sr-only">Declined</span></span>';
}

if(Patt_Custom_Func::id_in_recall($row['patt_request_id'],$type) == 1){
//$recall_icon = '<span style="margin-left:4px;"><img src="'.WPPATT_PLUGIN_URL.'asset/images/recall.gif" alt="Recall"/></span>';
$recall_icon = '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" aria-hidden="true" title="Recall"></i><span class="sr-only">Recall</span></span>';
}

if(Patt_Custom_Func::id_in_unauthorized_destruction($row['patt_request_id'],$type) == 1) {
    $unauthorized_destruction_icon = ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span>';
}

if(Patt_Custom_Func::id_in_damaged($row['patt_request_id'],$type) == 1) {
    $damaged_icon = ' <span style="font-size: 1em; color: #000000;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span>';
}

if(Patt_Custom_Func::id_in_freeze($row['patt_request_id'],$type) == 1) {
    $freeze_icon = ' <span style="font-size: 1em; color: #005C7A;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span>';
}

if(Patt_Custom_Func::id_in_box_destroyed($row['patt_request_id'],$type) == 1) {
    $box_destroyed_icon = ' <span style="font-size: 1em; color: #B4081A;"><i class="fas fa-ban" aria-hidden="true" title="Box Destroyed"></i><span class="sr-only">Box Destroyed</span></span>';
}

   $data[] = array(
     "due_date"=>$row['due_date'],
     "request_id"=>$row['request_id'],
     "request_id_flag"=>$row['request_id_flag'].$box_destroyed_icon.$unauthorized_destruction_icon.$damaged_icon.$freeze_icon.$decline_icon.$recall_icon,
     "ticket_priority"=>$priority,
     "ticket_status"=>$status,
     "customer_name"=>$customer_name.$full_name,
     "location"=> $location,
     //"ticket_priority"=>$row['ticket_priority'],
     "date_updated"=>calculate_time_span($seconds),
   );
}

if (empty($totalRecordwithFilter)) {
  $totalRecordwithFilter = 0;
}

## Response
$response = array(
  "draw" => intval($draw),
  "docQuery" => $boxQuery,
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);

echo json_encode($response);