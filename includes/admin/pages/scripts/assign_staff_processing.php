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
$searchByPriority = $_POST['searchByPriority'];
//$searchByRecallDecline = $_POST['searchByRecallDecline'];
$searchByDigitizationCenter = $_POST['searchByDigitizationCenter'];
$searchByUserLocation = $_POST['searchByUserLocation'];
$searchByECMSSEMS = $_POST['searchByECMSSEMS'];
$searchByActionStatus = $_POST['searchByActionStatus'];
$searchByAction = $_POST['searchByAction'];
$searchGeneric = $_POST['searchGeneric'];
$searchByStatus = $_POST['searchByStatus'];
$searchByUser = $_POST['searchByUser'];
$is_requester = $_POST['is_requester'];
$user_search = $_POST['user_search'];



## User Search
//throwing Undefined Index error
/*if(isset($_POST['searchByUser'])) {
    $searchByUser = $_POST['searchByUser'];
}
else {
    $searchByUser = '';
}*/

if(isset($_POST['searchByUserAAVal'])) {
    $searchByUserAAVal = $_POST['searchByUserAAVal'];
}
else {
    $searchByUserAAVal = '';
}

$searchByUserAAName = str_replace(",", "|", $_POST['searchByUserAAName']);
$searchByUserAANameQuoted = str_replace(",", "','", $_POST['searchByUserAAName']);
$searchByUserAANameQuoted = "'".$searchByUserAANameQuoted."'";

## Form List of Active To-Do Boxes for User

$scanning_preparation_term_id = Patt_Custom_Func::get_term_by_slug( 'scanning-preparation' );	 //672
$scanning_digitization_term_id = Patt_Custom_Func::get_term_by_slug( 'scanning-digitization' );	 //671
$qa_qc_term_id = Patt_Custom_Func::get_term_by_slug( 'q-a' );	 //65					
$validation_term_id = Patt_Custom_Func::get_term_by_slug( 'verification' );	 //674
$destruction_approved_term_id = Patt_Custom_Func::get_term_by_slug( 'destruction-approval' );	 //68
$destruction_of_source_term_id = Patt_Custom_Func::get_term_by_slug( 'destruction-of-source' );	 //1272

//$totalRecordwithFilter = '';


function findZero($var){
    // returns whether the input is non zero
    return($var == 0);
}

$todo_boxes_array = array();

// ADD all the scanning prep boxes assigned to the user.

$get_scanning_prep = $wpdb->get_results("SELECT 
a.box_id
FROM  " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location c ON b.storage_location_id =c.id
WHERE b.id <> '-99999' AND c.id <> '-99999' AND c.scanning_preparation = 0 AND a.user_id = ".$searchByUser." AND a.status_id = ".$scanning_preparation_term_id);

foreach ($get_scanning_prep as $data) {
$scanning_prep_box_id = $data->box_id;
array_push($todo_boxes_array, $scanning_prep_box_id);
}

// ADD all other boxes assigned to the user ONLY when previous status is marked as completed.

$get_completion_status = $wpdb->get_results("SELECT 
id,
scanning_preparation,
scanning_digitization,
qa_qc,
validation,
destruction_approved,
destruction_of_source
FROM  " . $wpdb->prefix . "wpsc_epa_storage_location
WHERE id <> '-99999' AND scanning_preparation <> 0 OR scanning_digitization <> 0 OR qa_qc <> 0 OR validation <> 0 OR destruction_approved <> 0 OR destruction_of_source <> 0");

foreach ($get_completion_status as $data) {
$storage_location_id = $data->id;
$scanning_preparation = $data->scanning_preparation;
$scanning_digitization = $data->scanning_digitization;
$qa_qc = $data->qa_qc;
$validation = $data->validation;
$destruction_approved = $data->destruction_approved;
$destruction_of_source = $data->destruction_of_source;

$box_complete_array = array(
    $scanning_preparation_term_id=>$scanning_preparation,
    $scanning_digitization_term_id=>$scanning_digitization,
    $qa_qc_term_id=>$qa_qc,
    $validation_term_id=>$validation,
    $destruction_approved_term_id=>$destruction_approved,
    $destruction_of_source_term_id=>$destruction_of_source
    );
    
//print_r($box_complete_array);


$newPair = array_filter($box_complete_array, "findZero");
//print_r($newPair); //Contains array of zero values

$first_key = key($newPair); // First element's key


if($first_key != '') {

$get_box_id = $wpdb->get_row("SELECT 
id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE id <> '-99999' AND storage_location_id = ".$storage_location_id);

$box_id = $get_box_id->id;

$get_todo_boxes = $wpdb->get_row("SELECT box_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus WHERE box_id = ".$box_id." AND user_id = ".$searchByUser." AND status_id = ".$first_key);

$todo_boxes = $get_todo_boxes->box_id;
} else {
$todo_boxes = ''; 
}
if($todo_boxes != '') {
array_push($todo_boxes_array, $todo_boxes);
}

}

$boxcommaList = implode(', ', array_unique($todo_boxes_array));

## Search 
$searchQuery = " ";
$ecms_sems_box = '';

//$searchByBoxID = str_replace(",", "|", $_POST['searchByBoxID']);

$BoxID_arr = explode(",", $_POST['searchByBoxID']);  

$newBoxID_arr = array();

foreach($BoxID_arr as $key => $value) {
//Check if Box ID
if (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $value)) {
array_push($newBoxID_arr,$value);
}
}

$newBoxID_str = str_replace(",", "|", implode(',', $newBoxID_arr));

if(count(array_unique($todo_boxes_array)) != 0){
$ecms_sems_box .= " and a.id IN (".$boxcommaList.") ";
} else {
$ecms_sems_box .= " and a.id = 0 ";
}

if($newBoxID_str != ''){
   $searchQuery .= " and (a.box_id REGEXP '^(".$newBoxID_str.")$' ) ";
}

if($searchByPriority != ''){
   $searchQuery .= " and (b.ticket_priority='".$searchByPriority."') ";
}

if($searchByStatus != ''){
   $searchQuery .= " and (f.name ='".$searchByStatus."') ";
}


$get_aa_ship_groups = Patt_Custom_Func::get_requestor_group($currentUser);
$user_list = implode(",", $get_aa_ship_groups);


//Get term_ids for Recall status slugs
$status_recall_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	 // 878
$status_recall_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' ); //734
$status_recall_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' ); //733

$status_decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' );	 // 791
$status_decline_completed_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-complete' ); //754
    

if($searchByDigitizationCenter != ''){
   $searchHaving = " HAVING digitization_center like '%".$searchByDigitizationCenter."%' ";
}

if($searchByUserLocation == 'Not Assigned') {
    // To show only tickets that are not owned by the currently signed in user
    $searchQuery .= " AND (um.meta_key = 'user_digization_center' AND (um.meta_value = 'Not Assigned' OR um.meta_value = NULL) ) ";  
}

if( strlen($searchByUserAAName) == 0  ) {
  //$searchQuery .= "";
} 
else {
  $searchQuery .= " and (b.customer_name IN (".$searchByUserAANameQuoted.")) ";	
}


if($searchByActionStatus != ''){
  	 if($searchByActionStatus == 'Current') {
        $searchQuery .=  " AND (d.scanning_preparation = 0 AND a.box_status = 672) OR 
        					   (d.scanning_digitization = 0 AND a.box_status = 671) OR
                               (d.qa_qc = 0 AND a.box_status = 65) OR
                               (d.validation = 0 AND a.box_status = 674) OR
                               (d.destruction_approved = 0 AND a.box_status = 68) OR
                               (d.destruction_of_source = 0 AND a.box_status = 1272) ";
    }
  
    if($searchByActionStatus == 'Upcoming') {
        $searchQuery .= " AND (a.box_previous_status = 0) OR
        					  (a.box_previous_status = 1056) ";
    }
  
  	if($searchByActionStatus == 'Completed') {
        $searchQuery .= " AND (d.scanning_preparation = 1 AND a.box_status = 672) OR 
        					   (d.scanning_digitization = 1 AND a.box_status = 671) OR
                               (d.qa_qc = 1 AND a.box_status = 65) OR
                               (d.validation = 1 AND a.box_status = 674) OR
                               (d.destruction_approved = 1 AND a.box_status = 68) OR
                               (d.destruction_of_source = 1 AND a.box_status = 1272) ";
    }
}


if($searchByAction == "672"){
  $searchQuery .= " AND (d.scanning_preparation+d.scanning_digitization+d.qa_qc+d.validation+d.destruction_approved+d.destruction_of_source = 0) ";
}
if($searchByAction == "671"){
  $searchQuery .= " AND (d.scanning_preparation+d.scanning_digitization+d.qa_qc+d.validation+d.destruction_approved+d.destruction_of_source = 1) ";
}
if($searchByAction == "65"){
  $searchQuery .= " AND (d.scanning_preparation+d.scanning_digitization+d.qa_qc+d.validation+d.destruction_approved+d.destruction_of_source = 2) ";
}
if($searchByAction == "674"){
  $searchQuery .= " AND (d.scanning_preparation+d.scanning_digitization+d.qa_qc+d.validation+d.destruction_approved+d.destruction_of_source = 3) ";
}
if($searchByAction == "68"){
  $searchQuery .= " AND (d.scanning_preparation+d.scanning_digitization+d.qa_qc+d.validation+d.destruction_approved+d.destruction_of_source = 4) ";
}
if($searchByAction == "1272"){
  $searchQuery .= " AND (d.scanning_preparation+d.scanning_digitization+d.qa_qc+d.validation+d.destruction_approved+d.destruction_of_source = 5) ";
}




// If a user is a requester, only show the boxes from requests (tickets) they have submitted. 
if( $is_requester == 'true' ){
	$user_name = $current_user->display_name;
	$searchQuery .= " and (b.customer_name ='".$user_name."') ";
}


if($searchGeneric != ''){
   $searchQuery .= " and (a.box_id like '%".$searchGeneric."%' or
      b.request_id like '%".$searchGeneric."%') ";
}

if($searchValue != ''){
   $searchQuery .= " and (a.box_id like '%".$searchValue."%' or
      b.request_id like '%".$searchValue."%' or
      h.scanning_id like '%".$searchGeneric."%' or
      h.stagingarea_id like '%".$searchGeneric."%' or
      h.cart_id like '%".$searchGeneric."%' or
      h.shelf_location like '%".$searchGeneric."%') ";
}

## Total number of records without filtering
$sel = mysqli_query($con,"select count(DISTINCT a.box_id) as allcount 
FROM wpqa_wpsc_epa_boxinfo_userstatus as userStatus

INNER JOIN wpqa_wpsc_epa_boxinfo a ON a.id = userStatus.box_id
INNER JOIN wpqa_terms f ON f.term_id = a.box_status
INNER JOIN wpqa_wpsc_ticket as b ON a.ticket_id = b.id
LEFT JOIN wpqa_users g ON g.ID = userStatus.user_id
LEFT JOIN wpqa_wpsc_ticketmeta as z ON z.ticket_id = b.id
INNER JOIN wpqa_wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN wpqa_terms t ON t.term_id = d.digitization_center


LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id

WHERE a.id <> -99999 AND b.active <> 0 AND (userStatus.user_id <> " . $current_user->ID . ") ");

$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

// filter results
$sel = mysqli_query($con,"SELECT DISTINCT
COUNT(DISTINCT a.box_id) AS allcount


FROM wpqa_wpsc_epa_boxinfo_userstatus as userStatus

INNER JOIN wpqa_wpsc_epa_boxinfo a ON a.id = userStatus.box_id
INNER JOIN wpqa_terms f ON f.term_id = a.box_status
INNER JOIN wpqa_wpsc_ticket as b ON a.ticket_id = b.id

WHERE (b.active <> 0) AND (a.id <> -99999) AND (userStatus.user_id <> " . $current_user->ID . ") " .$searchQuery );

$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
//REVIEW
$boxQuery = "
SELECT DISTINCT
a.box_id, 
a.id as dbid, 
a.box_previous_status as box_previous_status,
CASE

WHEN (
SELECT (scanning_preparation+scanning_digitization+qa_qc+validation+destruction_approved+destruction_of_source) FROM " . $wpdb->prefix . "wpsc_epa_storage_location WHERE id=a.storage_location_id
) = 0
THEN '<strong>Scanning Preparation</strong>'
WHEN (
SELECT (scanning_preparation+scanning_digitization+qa_qc+validation+destruction_approved+destruction_of_source) FROM " . $wpdb->prefix . "wpsc_epa_storage_location WHERE id=a.storage_location_id
) = 1
THEN '<strong>Scanning/Digitization</strong>'
WHEN (
SELECT (scanning_preparation+scanning_digitization+qa_qc+validation+destruction_approved+destruction_of_source) FROM " . $wpdb->prefix . "wpsc_epa_storage_location WHERE id=a.storage_location_id
) = 2
THEN '<strong>QA/QC</strong>'
WHEN (
SELECT (scanning_preparation+scanning_digitization+qa_qc+validation+destruction_approved+destruction_of_source) FROM " . $wpdb->prefix . "wpsc_epa_storage_location WHERE id=a.storage_location_id
) = 3
THEN '<strong>Validation</strong>'
WHEN (
SELECT (scanning_preparation+scanning_digitization+qa_qc+validation+destruction_approved+destruction_of_source) FROM " . $wpdb->prefix . "wpsc_epa_storage_location WHERE id=a.storage_location_id
) = 4
THEN '<strong>Destruction Approved</strong>'
WHEN (
SELECT (scanning_preparation+scanning_digitization+qa_qc+validation+destruction_approved+destruction_of_source) FROM " . $wpdb->prefix . "wpsc_epa_storage_location WHERE id=a.storage_location_id
) = 5
THEN '<strong>Destruction of Source</strong>'
    ELSE '<strong>Completed/Disposition</strong>'
END as action,

f.name as box_status, f.term_id as term,


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
 


CASE 
WHEN a.box_status = 748
THEN
1
WHEN a.box_status = 816
THEN
2
WHEN a.box_status = 672
THEN
3
WHEN a.box_status = 671
THEN
4
WHEN a.box_status = 65
THEN
5
WHEN a.box_status = 6
THEN
6
WHEN a.box_status = 673
THEN
7
WHEN a.box_status = 674
THEN
8
WHEN a.box_status = 743
THEN
9
WHEN a.box_status = 68
THEN
10
WHEN a.box_status = 67
THEN
11
WHEN a.box_status = 66
THEN
12
ELSE
999
END
 as box_status_order,
CONCAT(
CASE 
WHEN (SELECT count(id) FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE box_id = a.id) != 0
THEN
CONCAT((SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c
WHERE c.box_id = a.id), '/', (SELECT count(fdif.id) FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files fdif
WHERE fdif.box_id = a.id))
ELSE '-'
END
) as validation, t.name as digitization_center,
d.scanning_preparation, d.scanning_digitization, d.qa_qc, 
d.validation, d.destruction_approved, d.destruction_of_source,
b.request_id as request_id_new

FROM wpqa_wpsc_epa_boxinfo_userstatus as userStatus

INNER JOIN wpqa_wpsc_epa_boxinfo a ON a.id = userStatus.box_id
INNER JOIN wpqa_terms f ON f.term_id = a.box_status
INNER JOIN wpqa_wpsc_ticket as b ON a.ticket_id = b.id
LEFT JOIN wpqa_users g ON g.ID = userStatus.user_id
LEFT JOIN wpqa_wpsc_ticketmeta as z ON z.ticket_id = b.id
INNER JOIN wpqa_wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN wpqa_terms t ON t.term_id = d.digitization_center


LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id

WHERE (b.active <> 0) AND (a.id <> -99999) AND (userStatus.user_id <> " . $current_user->ID . ") " . $searchHaving . " AND 1 ".$searchQuery."
order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;

$boxRecords = mysqli_query($con, $boxQuery);
$data = array();
// $assigned_agents_icon = '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents()" class="assign_agents_icon"><i class="fas fa-user-friends" title="Assigned Agents"></i></span>';

while ($row = mysqli_fetch_assoc($boxRecords)) {
  	$request_id = $row['request_id_new'];
  	
  
  	// Get Box ID Flag
  	$box_id_flag_query = $wpdb->get_row("SELECT 
    CONCAT( 
    CASE WHEN 
    (
    SELECT sum(c.freeze = 1) 
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c
    WHERE c.box_id = a.id
    ) <> 0 AND
    a.box_destroyed > 0 


    THEN CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\" style=\"color: #B4081A !important;\">',a.box_id,'</a>')

    WHEN a.box_destroyed > 0 


    THEN CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\" style=\"color: #B4081A !important; text-decoration: underline line-through;\">',a.box_id,'</a>')


    ELSE CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\">',a.box_id,'</a>')
    END) as box_id_flag
  	
  	FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a

    INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
    INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
    LEFT JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
    INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
    WHERE a.id = " .$row['dbid']);
  
  	$box_id_flag = $box_id_flag_query->box_id_flag;
  
  	// Get Ticket Priority
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
  
  
  	
  	// Create request id link
  	$request_id_query = $wpdb->get_row("SELECT CONCAT('<a href=admin.php?page=wpsc-tickets&id=',a.request_id,'>',a.request_id,'</a>') as request_id_link
    FROM " . $wpdb->prefix . "wpsc_ticket as a
    WHERE a.id = ".$request_id);
  
  	$request_id_link = $request_id_query->request_id_link;
  
  	
  	// Get Validaion
  	$validation_query = $wpdb->get_row("SELECT CONCAT(
    CASE 
    WHEN (SELECT count(id) FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE box_id = a.id) != 0
    THEN
    CONCAT((SELECT sum(c.validation = 1) 
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c
    WHERE c.box_id = a.id), '/', (SELECT count(fdif.id) FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files fdif
    WHERE fdif.box_id = a.id))
    ELSE '-'
    END
    ) as validation

    FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a

    INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
    INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
    LEFT JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
    INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
    WHERE a.id = " .$row['dbid']);
  
  	$validation = $validation_query->validation;
  
  	// Get Customer/Display Name
  	$customer_name_query = $wpdb->get_row("SELECT CONCAT((SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'first_name' AND user_id = g.ID), ' ', (SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'last_name' AND user_id = g.ID)) as full_name,
    CONCAT (
    CASE
    WHEN ((SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'first_name' AND user_id = g.ID) <> '') AND ((SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'last_name' AND user_id = g.ID) <> '') THEN CONCAT ( '<a href=\"#\" style=\"color: #000000 !important;\" data-toggle=\"tooltip\" data-placement=\"left\" data-html=\"true\" aria-label=\"Name\" title=\"',
    g.user_login
    ,'\">',

    (
        CASE WHEN length(CONCAT((SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'first_name' AND user_id = g.ID), ' ', (SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'last_name' AND user_id = g.ID))) > 15 THEN
            CONCAT(LEFT(CONCAT((SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'first_name' AND user_id = g.ID), ' ', (SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'last_name' AND user_id = g.ID)), 15), '...')
        ELSE CONCAT((SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'first_name' AND user_id = g.ID), ' ', (SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'last_name' AND user_id = g.ID))
        END
    )

    ,'</a>' )
    ELSE g.user_login
    END
    ) as customer_name

    FROM " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus as userStatus

	INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo a ON a.id = userStatus.box_id
    INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON a.ticket_id = b.id
    LEFT JOIN " . $wpdb->prefix . "users g ON g.ID = userStatus.user_id
    INNER JOIN " . $wpdb->prefix . "users u ON u.user_email = b.customer_email
    WHERE a.id = " .$row['dbid'] . " AND (g.ID <> " . $current_user->ID . ") ");
  
  	$customer_name = $customer_name_query->customer_name;
  
  	
  
  
	
	$status_term_id = $row['term'];
	$status_background = get_term_meta($status_term_id, 'wpsc_box_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wpsc_box_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color.";";
	
	$waiting_shelved_term_id = Patt_Custom_Func::get_term_by_slug( 'waiting-shelved' );	 //816
    $waiting_rlo_term_id = Patt_Custom_Func::get_term_by_slug( 'waiting-on-rlo' );	 //1056
    $cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'cancelled' );	 //1057

$get_term_name = $wpdb->get_row("SELECT name
FROM " . $wpdb->prefix . "terms WHERE term_id = ".$row['box_previous_status']);

$term_name = $get_term_name->name;

if ($status_term_id == $waiting_shelved_term_id && $row['box_previous_status'] != 0) {
    $box_status = "<a href='#' style='color: #000000 !important;' data-toggle='tooltip' data-placement='right' data-html='true' aria-label='Previous Box Status' title='Previous Box Status: ".$term_name."'><span class='wpsp_admin_label' style='".$status_style."'>".$row['box_status']."</span></a>";
} elseif ($status_term_id == $waiting_rlo_term_id && $row['box_previous_status'] != 0) {
    $box_status = "<a href='#' style='color: #000000 !important;' data-toggle='tooltip' data-placement='right' data-html='true' aria-label='Previous Box Status' title='Previous Box Status: ".$term_name."'><span class='wpsp_admin_label' style='".$status_style."'>".$row['box_status']."</span></a>";
} elseif ($status_term_id == $cancelled_term_id && $row['box_previous_status'] != 0) {
    $box_status = "<a href='#' style='color: #000000 !important;' data-toggle='tooltip' data-placement='right' data-html='true' aria-label='Previous Box Status' title='Previous Box Status: ".$term_name."'><span class='wpsp_admin_label' style='".$status_style."'>".$row['box_status']."</span></a>";
} else {
    $box_status = "<span class='wpsp_admin_label' style='".$status_style."'>".$row['box_status']."</span>";
}
	
	//$assigned_agents_icon = '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents(666)" class="assign_agents_icon"><i class="fas fa-user-friends" title="Assigned Agents"></i></span>';

if(Patt_Custom_Func::display_box_user_icon($row['dbid']) == 1){	
	$assigned_agents_icon = '
	<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents(\''.$row['box_id'].'\')" class="assign_agents_icon"><i class="fas fa-user-friends" aria-hidden="true" title="Assigned Agents"></i><span class="sr-only">Assigned Agents</span></span>
	<span style="font-size: 1.0em; color: #1d1f1d;" onclick="edit_to_do(\''.$row['box_id'].'\')" class="assign_agents_icon"><i class="fas fa-clipboard-check" aria-hidden="true" title="Box Status Completion"></i><span class="sr-only">Box Status Completion</span></span>
	';
} else {
    $assigned_agents_icon = '';
}

$decline_icon = '';
$recall_icon = '';
$unauthorized_destruction_icon = '';
$freeze_icon = '';
$box_destroyed_icon = '';
$damaged_icon = '';
$type = 'box';

if(Patt_Custom_Func::id_in_return($row['box_id'],$type) == 1){
$decline_icon = '<span style="font-size: 1em; color: #B4081A;margin-left:4px;"><i class="fas fa-undo" aria-hidden="true" title="Declined"></i><span class="sr-only">Declined</span></span>';
}

if(Patt_Custom_Func::id_in_recall($row['box_id'],$type) == 1){
$recall_icon = '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" aria-hidden="true" title="Recall"></i><span class="sr-only">Recall</span></span>';
}

if(Patt_Custom_Func::id_in_unauthorized_destruction($row['box_id'],$type) == 1) {
    $unauthorized_destruction_icon = ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span>';
}

if(Patt_Custom_Func::id_in_damaged($row['box_id'],$type) == 1) {
    $damaged_icon = ' <span style="font-size: 1em; color: #FFC300;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span>';
}

if(Patt_Custom_Func::id_in_freeze($row['box_id'],$type) == 1) {
    $freeze_icon = ' <span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span>';
}

if(Patt_Custom_Func::id_in_box_destroyed($row['box_id'],$type) == 1) {
    $box_destroyed_icon = ' <span style="font-size: 1em; color: #B4081A;"><i class="fas fa-ban" aria-hidden="true" title="Box Destroyed"></i><span class="sr-only">Box Destroyed</span></span>';
}

$get_file_count = $wpdb->get_row("SELECT COUNT(c.id) as total
FROM wpqa_wpsc_epa_boxinfo a
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.box_id = a.id
WHERE a.box_id = '" .  $row['box_id'] . "'");

$get_validation_count = $wpdb->get_row("SELECT SUM(c.validation) as val_count
FROM wpqa_wpsc_epa_boxinfo a
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.box_id = a.id
WHERE a.box_id = '" .  $row['box_id'] . "'");

if(Patt_Custom_Func::id_in_validation($row['box_id'],$type) == 1) {
    $validation_icon = '<span style="font-size: 1.3em; color: #2f631d;"><i class="fas fa-check-circle" aria-hidden="true" title="Validated"></i><span class="sr-only">Validated</span></span> ';
}
else if( ($get_validation_count->val_count > 0) && ($get_validation_count->val_count < $get_file_count->total) ) {
    $validation_icon = '<span style="font-size: 1.3em; color: #b55000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span> ';
}
else {
    $validation_icon = '<span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span> ';
}
  
// get all pertinent term_ids
//Box status slugs
$scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); 
$scanning_digitization_tag = get_term_by('slug', 'scanning-digitization', 'wpsc_box_statuses'); 
$qa_qc_tag = get_term_by('slug', 'q-a', 'wpsc_box_statuses'); 
$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); 
$destruction_approved_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); 
$destruction_of_source_tag = get_term_by('slug', 'destruction-of-source', 'wpsc_box_statuses'); 

$scanning_preparation_term = $scanning_preparation_tag->term_id;
$scanning_digitization_term = $scanning_digitization_tag->term_id;
$qa_qc_term = $qa_qc_tag->term_id;
$validation_term = $validation_tag->term_id;
$destruction_approved_term = $destruction_approved_tag->term_id;
$destruction_of_source_term = $destruction_of_source_tag->term_id;

// 0 - Upcoming, 1 - completed, 2 - current
$action_status = '';
  
// Check the status of each todo list item in the db
  if($row['scanning_preparation'] == 0 && $status_term_id == $scanning_preparation_term){
    $action_status = 'Current';
  }
  else if($row['scanning_preparation'] == 0) {
    $action_status = 'Upcoming';
  }
  else if($row['scanning_preparation'] == 1 && $status_term_id == $scanning_preparation_term){
    $action_status = 'Completed';
  }
  else if($row['scanning_digitization'] == 0 && $status_term_id == $scanning_digitization_term){
    $action_status = 'Current';
  }
  else if($row['scanning_digitization'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['scanning_digitization'] == 1 && $status_term_id == $scanning_digitization_term){
    $action_status = 'Completed';
  }
  else if($row['qa_qc'] == 0 && $status_term_id == $qa_qc_term){
    $action_status = 'Current';
  }
  else if($row['qa_qc'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['qa_qc'] == 1 && $status_term_id == $qa_qc_term){
    $action_status = 'Completed';
  }
  else if($row['validation'] == 0 && $status_term_id == $validation_term){
    $action_status = 'Current';
  }
  else if($row['validation'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['validation'] == 1 && $status_term_id == $validation_term){
    $action_status = 'Completed';
  }
  else if($row['destruction_approved'] == 0 && $status_term_id == $destruction_approved_term){
    $action_status = 'Current';
  }
  else if($row['destruction_approved'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['destruction_approved'] == 1 && $status_term_id == $destruction_approved_term){
    $action_status = 'Completed';
  }
  else if($row['destruction_approved'] == 0 && $status_term_id == $destruction_of_source_term){
    $action_status = 'Current';
  }
  else if($row['destruction_of_source'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['destruction_of_source'] == 1 && $status_term_id == $destruction_of_source_term){
    $action_status = 'Completed';
  }
  else if($row['box_status'] == 1056 || $row['box_status'] == 748){
    $action_status = 'Upcoming';
  }
  else if($row['box_status'] == 'Completed/Dispositioned' || $row['box_status'] == 1258){
    $action_status = 'Completed';
  }
  

	$data[] = array(
		"box_id"=>$row['box_id'],
		"box_id_flag"=>$box_id_flag.$box_destroyed_icon.$unauthorized_destruction_icon.$damaged_icon.$freeze_icon.$decline_icon.$recall_icon.$assigned_agents_icon, 
		"dbid"=>$row['dbid'],
	    //"box_id_column"=>array("dbid"=>$row['dbid'],"box_id"=>$row['box_id'].$freeze_icon.$unauthorized_destruction_icon.$decline_icon.$recall_icon.$assigned_agents_icon),
		//"ticket_priority"=>$row['ticket_priority_text'],
	    "action"=>$row['action'],
		"request_id"=>$request_id_link ,
		"ticket_priority"=>$priority,
		"status"=>$box_status,
// 		"acronym"=>$searchQuery,
// 		"acronym"=>$searchByUserAAVal,
		"validation"=>$validation_icon . ' ' . $validation,
		"physical_location"=>$row['physical_location'],
      	"employee_assigned"=>$customer_name,
      	"digitization_center"=>$row['digitization_center'],
      	"action_status"=>$action_status,
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
"test" => $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php'
);

echo json_encode($response);