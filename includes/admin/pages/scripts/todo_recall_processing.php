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

// ## Total number of records without filtering
$query = mysqli_query($con, "SELECT COUNT(a.recall_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_recallrequest a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.recall_status_id
WHERE (a.recall_approved = 0 OR a.recall_complete = 0) AND a.recall_status_id NOT IN (".$recall_recall_denied_tag.",".$recall_recall_cancelled_tag.") AND a.id != '-99999' AND b.user_id = " . $get_current_user_id);

$records = mysqli_fetch_assoc($query);
$totalRecords = $records['allcount'];



## Base Query for Recalls assigned to current user
$baseQuery = "SELECT
COUNT(a.recall_id) OVER() as total_count,
a.recall_id, 
a.request_date, 
c.name as recall_status, 
a.recall_status_id

FROM " . $wpdb->prefix . "wpsc_epa_recallrequest a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.recall_status_id
WHERE (a.recall_approved = 0 OR a.recall_complete = 0) AND a.recall_status_id NOT IN (".$recall_recall_denied_tag.",".$recall_recall_cancelled_tag.") AND a.id != '-99999' AND b.user_id = " . $get_current_user_id . "

order by a.id, ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;

$recallRecords = mysqli_query($con, $baseQuery);
$data = array();

## Row Data
while ($row = mysqli_fetch_assoc($recallRecords)) {
  
  	$totalRecordwithFilter = $row['total_count'];

   	// Makes the Status column pretty
	$status_term_id = $row['recall_status_id'];
	$status_background = get_term_meta($status_term_id, 'wppatt_recall_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wppatt_recall_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color;

	$icons .= ' <span style="font-size: 1.0em;" onclick="edit_recall_to_do(\''.$row['recall_id'].'\')" class="assign_agents_icon"><i class="fas fa-clipboard-check" aria-hidden="true" title="Recall To Do"></i><span class="sr-only">Recall To Do</span></span>';


   	$data[] = array(
		"recall_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=recalldetails&id=R-".$row['recall_id']."' >R-".$row['recall_id']."</a>" . $icons, 		
		"request_date"=> date('m/d/Y', strtotime( $row['request_date'] )),
		"recall_status"=>"<span class='wpsp_admin_label' style='".$status_style."'>".$row['recall_status']."</span>", 
   );
   
   // Clear icons
   $icons = '';
}

if (empty($totalRecordwithFilter)) {
  $totalRecordwithFilter = 0;
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