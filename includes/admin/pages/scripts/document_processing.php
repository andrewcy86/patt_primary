<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');
	
$host = DB_HOST; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

global $wpdb, $current_user, $wpscfunction;
$agent_permissions = $wpscfunction->get_current_agent_permissions();

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
} else {
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
}

## Custom Field value
$searchByDocID = str_replace(",", "|", $_POST['searchByDocID']);

//throwing Undefined Index error
if(isset($_POST['searchByProgramOffice'])) {
    $searchByProgramOffice = $_POST['searchByProgramOffice'];
}
else {
    $searchByProgramOffice = '';
}

$searchByDigitizationCenter = $_POST['searchByDigitizationCenter'];
$searchByPriority = $_POST['searchByPriority'];
$searchByRecallDecline = $_POST['searchByRecallDecline'];
$searchByECMSSEMS = $_POST['searchByECMSSEMS'];
$searchGeneric = $_POST['searchGeneric'];
$is_requester = $_POST['is_requester'];

## Search 
$searchQuery = " ";
if($searchByDocID != ''){
    //used to be a.folderdocinfo_id
   $searchQuery .= " and (a.folderdocinfofile_id REGEXP '^(".$searchByDocID.")$' ) ";
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

    //Get term_ids for Recall status slugs
    $status_recall_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	 // 878
    $status_recall_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' ); //734
    $status_recall_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' ); //733

	$status_decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' );	 // 791
    $status_decline_completed_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-complete' ); //754
    
if($searchByRecallDecline != ''){

        if($searchByRecallDecline == 'Recall') {
            $searchQuery .= "and (
            COALESCE(g.recall_status_id, h.recall_status_id) NOT IN (".$status_recall_denied_term_id.",".$status_recall_cancelled_term_id.",".$status_recall_complete_term_id.")
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
        $ecms_sems = ' AND z.meta_key = "super_fund" AND z.meta_value = "false" ';
    }
    
    if($searchByECMSSEMS == 'SEMS') {
        $ecms_sems = ' AND z.meta_key = "super_fund" AND z.meta_value = "true" ';
    }
}

// If a user is a requester, only show the folder/files from requests (tickets) they have submitted. 
if( $is_requester == 'true' ){
	$user_name = $current_user->display_name;
	
	$get_aa_ship_groups = Patt_Custom_Func::get_requestor_group($user_name);
    $user_list = implode(",", $get_aa_ship_groups);
	
	if(!empty($user_list)) {
	    $searchQuery .= " and ( b.customer_name ='".$user_name."' OR um.user_id IN ($user_list) ) ";
	}
	else {
	    $searchQuery .= " and (b.customer_name ='".$user_name."') ";
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

   $searchQuery .= " and (a.folderdocinfofile_id like '%".$searchGeneric."%' or 
      b.request_id like '%".$searchGeneric."%' or 
      f.name like '%".$searchGeneric."%' or
      c.office_acronym like '%".$searchGeneric."%' or
      b.ticket_priority like '%".$searchGeneric."%') ";
}
}

if($searchValue != ''){
   $searchQuery .= " and (a.folderdocinfofile_id like '%".$searchValue."%' or 
      b.request_id like '%".$searchValue."%' or 
      f.name like '%".$searchValue."%' or
      c.office_acronym like '%".$searchValue."%' or
      b.ticket_priority like '%".$searchValue."%') ";
}

## Total number of records without filtering
$sel = mysqli_query($con,"select count(DISTINCT a.folderdocinfofile_id) as allcount from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as d ON a.box_id = d.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as e ON d.storage_location_id = e.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON d.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON d.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.digitization_center

LEFT JOIN " . $wpdb->prefix . "users us ON us.user_email = b.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = us.ID

LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS g ON (g.box_id = d.id AND g.folderdoc_id = '-99999')

LEFT JOIN (   SELECT DISTINCT recall_status_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY folderdoc_id) AS h ON (h.folderdoc_id = a.id AND h.folderdoc_id <> '-99999')

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS i ON i.box_id = d.id
LEFT JOIN (   SELECT a.folderdoc_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.folderdoc_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.folderdoc_id )  AS j ON j.folderdoc_id = a.id

WHERE a.id <> -99999 AND b.active <> 0 " . $ecms_sems . "
");

$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
//gets all folder/files for every user
$sel = mysqli_query($con,"select count(DISTINCT a.folderdocinfofile_id) as allcount FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as d ON a.box_id = d.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as e ON d.storage_location_id = e.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON d.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON d.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.digitization_center

LEFT JOIN " . $wpdb->prefix . "users us ON us.user_email = b.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = us.ID

LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS g ON (g.box_id = d.id AND g.folderdoc_id = '-99999')

LEFT JOIN (   SELECT DISTINCT recall_status_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY folderdoc_id) AS h ON (h.folderdoc_id = a.id AND h.folderdoc_id <> '-99999')
   
LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS i ON i.box_id = d.id
LEFT JOIN (   SELECT a.folderdoc_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.folderdoc_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.folderdoc_id )  AS j ON j.folderdoc_id = a.id

WHERE (b.active <> 0) AND (a.id <> -99999) " . $ecms_sems . " AND 1 ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
//SQL query using functions to generate icons
$docQuery = "SELECT DISTINCT
a.id as dbid,
a.folderdocinfofile_id as folderdocinfo_id,
CONCAT(
CASE 
WHEN d.box_destroyed > 0 AND a.freeze <> 1 THEN CONCAT('<a href=\"admin.php?pid=docsearch&page=filedetails&id=',a.folderdocinfofile_id,'\" style=\"color: #B4081A !important; text-decoration: underline line-through;\">',a.folderdocinfofile_id,'</a> <span style=\"font-size: 1em; color: #B4081A;\"></span>')
WHEN d.box_destroyed > 0 AND a.freeze = 1 THEN CONCAT('<a href=\"admin.php?pid=docsearch&page=filedetails&id=',a.folderdocinfofile_id,'\">',a.folderdocinfofile_id,'</a>')
ELSE CONCAT('<a href=\"admin.php?pid=docsearch&page=filedetails&id=',a.folderdocinfofile_id,'\">',a.folderdocinfofile_id,'</a>')
END) as folderdocinfo_id_flag,
CONCAT(
'<span class=\"wpsp_admin_label\" style=\"background-color:',
(SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_priority_background_color' AND term_id = b.ticket_priority),
';color:',
(SELECT meta_value from " . $wpdb->prefix . "termmeta where meta_key = 'wpsc_priority_color' AND term_id = b.ticket_priority),
';\">',
(SELECT name from " . $wpdb->prefix . "terms where term_id = b.ticket_priority),
'</span>') as ticket_priority,
CASE 
WHEN b.ticket_priority = 621
THEN
1
WHEN b.ticket_priority = 9
THEN
2
WHEN b.ticket_priority = 8
THEN
3
WHEN b.ticket_priority = 7
THEN
4
ELSE
999
END
 as ticket_priority_order,
CONCAT('<a href=admin.php?page=wpsc-tickets&id=',b.request_id,'>',b.request_id,'</a>') as request_id, f.name as location, c.office_acronym as acronym,
CONCAT(
CASE 
WHEN a.validation = 1 THEN CONCAT('[',
CONCAT (
CASE
WHEN ((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = u.ID) <> '') AND ((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = u.ID) <> '') THEN CONCAT ( '<a href=\"#\" style=\"color: #000000 !important;\" data-toggle=\"tooltip\" data-placement=\"left\" data-html=\"true\" aria-label=\"Name\" title=\"',
u.user_login
,'\">',
(
    CASE WHEN length(CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = u.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = u.ID))) > 15 THEN
        CONCAT(LEFT(CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = u.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = u.ID)), 15), '...')
    ELSE CONCAT((SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'first_name' AND user_id = u.ID), ' ', (SELECT meta_value FROM " . $wpdb->prefix . "usermeta WHERE meta_key = 'last_name' AND user_id = u.ID))
    END
)
,'</a>' )
ELSE u.user_login
END
)
,']')
ELSE ''
END) as validation
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files as a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo as d ON a.box_id = d.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as e ON d.storage_location_id = e.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON d.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON d.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.digitization_center

LEFT JOIN " . $wpdb->prefix . "users us ON us.user_email = b.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = us.ID

LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS g ON (g.box_id = d.id AND g.folderdoc_id = '-99999')

LEFT JOIN (   SELECT DISTINCT recall_status_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY folderdoc_id) AS h ON (h.folderdoc_id = a.id AND h.folderdoc_id <> '-99999')
   
LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS i ON i.box_id = d.id
LEFT JOIN (   SELECT a.folderdoc_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.folderdoc_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.folderdoc_id )  AS j ON j.folderdoc_id = a.id

LEFT JOIN " . $wpdb->prefix . "users as u ON a.validation_user_id = u.ID

WHERE (b.active <> 0) AND (a.id <> -99999) " . $ecms_sems . " AND 1 ".$searchQuery." 
order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;

$docRecords = mysqli_query($con, $docQuery);
$data = array();

while ($row = mysqli_fetch_assoc($docRecords)) {

$decline_icon = '';
$recall_icon = '';
$box_destroyed_icon = '';
$unauthorized_destruction_icon = '';
$freeze_icon = '';
$damaged_icon = '';
$type = 'folderfile';

if(Patt_Custom_Func::id_in_return($row['folderdocinfo_id'],$type) == 1){
$decline_icon = '<span style="font-size: 1em; color: #B4081A;margin-left:4px;"><i class="fas fa-undo" aria-hidden="true" title="Declined"></i><span class="sr-only">Declined</span></span>';
}

if(Patt_Custom_Func::id_in_recall($row['folderdocinfo_id'],$type) == 1){
$recall_icon = '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" aria-hidden="true" title="Recall"></i><span class="sr-only">Recall</span></span>';
}

if(Patt_Custom_Func::id_in_box_destroyed($row['folderdocinfo_id'], $type) == 1) {
    $box_destroyed_icon = ' <i class="fas fa-ban" aria-hidden="true" title="Box Destroyed" style="color: #B4081A"></i><span class="sr-only">Box Destroyed</span>';
}

if(Patt_Custom_Func::id_in_unauthorized_destruction($row['folderdocinfo_id'],$type) == 1) {
    $unauthorized_destruction_icon = ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span>';
}

if(Patt_Custom_Func::id_in_damaged($row['folderdocinfo_id'],$type) == 1) {
    $damaged_icon = ' <span style="font-size: 1em; color: #000000;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span>';
}

if(Patt_Custom_Func::id_in_freeze($row['folderdocinfo_id'],$type) == 1) {
    $freeze_icon = ' <span style="font-size: 1em; color: #005C7A;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span>';
}

if(Patt_Custom_Func::id_in_validation($row['folderdocinfo_id'],$type) == 1) {
    $validation_icon = '<span style="font-size: 1.3em; color: #2f631d;"><i class="fas fa-check-circle" aria-hidden="true" title="Validated"></i><span class="sr-only">Validated</span></span> ';
}
else if (Patt_Custom_Func::id_in_validation($row['folderdocinfo_id'],$type) != 1 && Patt_Custom_Func::id_in_rescan($row['folderdocinfo_id'],$type) == 1) {
    $validation_icon = '<span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span> <span style="color: #B4081A;"><strong>[Re-scan]</strong></span>';
}
else {
    $validation_icon = '<span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span>';
}

   $data[] = array(
     "folderdocinfo_id"=>$row['folderdocinfo_id'],
     "dbid"=>$row['dbid'],
     //"folderdocinfo_id_flag"=>$row['folderdocinfo_id_flag'].$decline_icon.$recall_icon,
	 "folderdocinfo_id_flag"=>$row['folderdocinfo_id_flag'].$box_destroyed_icon.$unauthorized_destruction_icon.$damaged_icon.$freeze_icon.$decline_icon.$recall_icon,
     "ticket_priority"=>$row['ticket_priority'],
//      "folderdocinfo_id_flag"=>$row['folderdocinfo_id_flag'],
     "request_id"=>$row['request_id'],
     "location"=>$row['location'],
     "acronym"=>$row['acronym'],
     "validation"=>$validation_icon. ' ' .$row['validation']
     //"ticket_priority"=>$row['ticket_priority']
   );
}

## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data,
  "sqlQuery" => $docQuery,
  "is_requester" => $is_requester
);

echo json_encode($response);