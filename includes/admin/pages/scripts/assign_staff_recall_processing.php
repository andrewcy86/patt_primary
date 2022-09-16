<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');


$host = DB_HOST; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

$subfolder_path = site_url( '', 'relative'); 

global $current_user, $wpscfunction;


$recall_recall_denied_tag = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );
$recall_recall_cancelled_tag = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' );

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

$get_current_user_id = $_POST['searchByUser'];

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


if(isset($_POST['searchByUserAAVal'])) {
    $searchByUserAAVal = $_POST['searchByUserAAVal'];
}
else {
    $searchByUserAAVal = '';
}

$searchByUserAAName = str_replace(",", "|", $_POST['searchByUserAAName']);
$searchByUserAANameQuoted = str_replace(",", "','", $_POST['searchByUserAAName']);
$searchByUserAANameQuoted = "'".$searchByUserAANameQuoted."'";


if($searchByDigitizationCenter != ''){
   $searchHaving = " HAVING digitization_center like '%".$searchByDigitizationCenter."%' ";
}


// ## Total number of records without filtering
$query = mysqli_query($con, "SELECT COUNT(DISTINCT recall_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_recallrequest a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.recall_status_id
INNER JOIN wpqa_users u ON u.ID = b.user_id
INNER JOIN wpqa_wpsc_ticket d ON d.id = a.box_id
INNER JOIN wpqa_wpsc_epa_boxinfo e ON e.ticket_id = d.id
INNER JOIN wpqa_wpsc_epa_storage_location f ON e.storage_location_id = f.id
INNER JOIN wpqa_terms t ON t.term_id = f.digitization_center
WHERE (a.recall_approved = 0 OR a.recall_complete = 0) AND u.ID <> " . $current_user->ID);

$records = mysqli_fetch_assoc($query);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$query = mysqli_query($con, "SELECT COUNT(DISTINCT a.recall_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_recallrequest a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.recall_status_id
INNER JOIN wpqa_users u ON u.ID = b.user_id
INNER JOIN wpqa_wpsc_ticket d ON d.id = a.box_id
INNER JOIN wpqa_wpsc_epa_boxinfo e ON e.ticket_id = d.id
INNER JOIN wpqa_wpsc_epa_storage_location f ON e.storage_location_id = f.id
INNER JOIN wpqa_terms t ON t.term_id = f.digitization_center
WHERE (a.recall_approved = 0 OR a.recall_complete = 0) AND u.ID <> " . $current_user->ID . "" . $searchHaving);

/*$query = mysqli_query($con, "SELECT COUNT(a.recall_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_recallrequest a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.recall_status_id
WHERE (a.recall_approved = 0 OR a.recall_complete = 0) AND a.recall_status_id NOT IN (".$recall_recall_denied_tag.",".$recall_recall_cancelled_tag.") AND a.id != '-99999' AND b.user_id = " . $get_current_user_id);
*/
$records = mysqli_fetch_assoc($query);
$totalRecordwithFilter = $records['allcount'];

## Base Query for Recalls assigned to current user
$baseQuery = "SELECT DISTINCT a.recall_id, a.request_date, c.name as recall_status, a.recall_status_id, t.name as digitization_center,
a.recall_approved, a.recall_complete,

CASE

WHEN (
SELECT (a.recall_approved+a.recall_complete) FROM wpqa_wpsc_epa_recallrequest WHERE id=a.recall_id
) = 0
THEN '<strong>Preparation</strong>'
WHEN (
SELECT (a.recall_approved+a.recall_complete) FROM wpqa_wpsc_epa_recallrequest WHERE id=a.recall_id
) = 1
THEN '<strong>In Progress</strong>'
WHEN (
SELECT (a.recall_approved+a.recall_complete) FROM wpqa_wpsc_epa_recallrequest WHERE id=a.recall_id
) = 2
THEN '<strong>Completed</strong>'
    ELSE 'Error'
END as action

FROM " . $wpdb->prefix . "wpsc_epa_recallrequest a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.recall_status_id
INNER JOIN wpqa_users u ON u.ID = b.user_id
INNER JOIN wpqa_wpsc_ticket d ON d.id = a.box_id
INNER JOIN wpqa_wpsc_epa_boxinfo e ON e.ticket_id = d.id
INNER JOIN wpqa_wpsc_epa_storage_location f ON e.storage_location_id = f.id
INNER JOIN wpqa_terms t ON t.term_id = f.digitization_center
WHERE (a.recall_approved = 0 OR a.recall_complete = 0) AND u.ID <> " . $current_user->ID . "" . $searchHaving ."

order by a.id, ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;

$recallRecords = mysqli_query($con, $baseQuery);
$data = array();

## Row Data
while ($row = mysqli_fetch_assoc($recallRecords)) {
  
  	$recall_id = $row['recall_id'];
  
  	// Get user name foe employee assigend column
  	$user_display_name_query = $wpdb->get_row("SELECT DISTINCT u.display_name


                      FROM wpqa_wpsc_epa_recallrequest a
                      INNER JOIN wpqa_wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
                      INNER JOIN wpqa_terms c ON c.term_id = a.recall_status_id
                      INNER JOIN wpqa_users u ON u.ID = b.user_id
                      INNER JOIN wpqa_wpsc_ticket d ON d.id = a.box_id
                      INNER JOIN wpqa_wpsc_epa_boxinfo e ON e.ticket_id = d.id
                      INNER JOIN wpqa_wpsc_epa_storage_location f ON e.storage_location_id = f.id
                      INNER JOIN wpqa_terms t ON t.term_id = f.digitization_center
                      WHERE a.recall_id = " . $recall_id . " AND u.ID <> " . $current_user->ID);
  	
  $user_display_name = $user_display_name_query->display_name;

   	// Makes the Status column pretty
	$status_term_id = $row['recall_status_id'];
	$status_background = get_term_meta($status_term_id, 'wppatt_recall_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wppatt_recall_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color;

	$icons .= ' <span style="font-size: 1.0em;" onclick="edit_recall_to_do(\''.$row['recall_id'].'\')" class="assign_agents_icon"><i class="fas fa-clipboard-check" aria-hidden="true" title="Recall To Do"></i><span class="sr-only">Recall To Do</span></span>';

  
  	// get all pertinent term_ids
//Recall status slugs
$recall_approved_tag = get_term_by('slug', 'recall-approved', 'wppatt_recall_statuses'); 
$recall_complete_tag = get_term_by('slug', 'recall-complete', 'wppatt_recall_statuses'); 


$recall_approved_term = $recall_approved_tag->term_id;
$recall_complete_term = $recall_complete_tag->term_id;

// 0 - Upcoming, 1 - completed, 2 - current
$action_status = '';
  
// Check the status of each todo list item in the db
  if($row['recall_approved'] == 0 && $status_term_id == $recall_approved_term){
    $action_status = 'Current';
  }
  else if($row['recall_approved'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['recall_approved'] == 1){
    $action_status = 'Completed';
  }
  else if($row['recall_complete'] == 0 && $status_term_id == $recall_complete_term){
    $action_status = 'Current';
  }
  else if($row['recall_complete'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['recall_complete'] == 1){
    $action_status = 'Completed';
  }
  
  

   	$data[] = array(
		"recall_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=recalldetails&id=R-".$row['recall_id']."' >R-".$row['recall_id']."</a>" . $icons, 		
		"request_date"=> date('m/d/Y', strtotime( $row['request_date'] )),
		"recall_status"=>"<span class='wpsp_admin_label' style='".$status_style."'>".$row['recall_status']."</span>",
      	"employee_assigned"=>$user_display_name,
    	"digitization_center"=>$row['digitization_center'],
      	"action_status"=>$action_status,
      	"action"=>$row['action'],
   );
   
   // Clear icons
   $icons = '';
}


## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data,
  "query" => $baseQuery,
  "background_color" => $status_term_id
);



echo json_encode($response);