<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');
include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );
	
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

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc

$get_current_user_id = $_POST['sbu'];

## Total number of records without filtering
$query = "SELECT COUNT(a.recall_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_recallrequest a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.recall_status_id
WHERE (a.recall_approved = 0 OR a.recall_complete = 0) AND a.recall_status_id NOT IN (".$recall_recall_denied_tag->term_id.",".$recall_recall_cancelled_tag->term_id.") AND a.id != '-99999' AND b.user_id = " . $get_current_user_id;

$sel = mysqli_query($con,$query);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Base Query for Recalls assigned to current user
$baseQuery = "SELECT a.recall_id, a.request_date, c.name as recall_status
FROM " . $wpdb->prefix . "wpsc_epa_recallrequest a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_recallrequest_users b ON b.recallrequest_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.recall_status_id
WHERE (a.recall_approved = 0 OR a.recall_complete = 0) AND a.recall_status_id NOT IN (".$recall_recall_denied_tag->term_id.",".$recall_recall_cancelled_tag->term_id.") AND a.id != '-99999' AND b.user_id = " . $get_current_user_id;

$recallRecords = mysqli_query($con, $baseQuery);

## Row Data

$data = array();

while ($row = mysqli_fetch_assoc($recallRecords)) {

   	// Makes the Status column pretty
	$status_term_id = $row['recall_status_id'];
	$status_background = get_term_meta($status_term_id, 'wppatt_recall_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wppatt_recall_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color.";";

	$icons .= ' <span style="font-size: 1.0em; color: #8b0000;" onclick="edit_recall_to_do(\''.$row['recall_id'].'\')" class="assign_agents_icon"><i class="fas fa-clipboard-check" aria-hidden="true" title="Recall To Do"></i><span class="sr-only">Recall To Do</span></span>';


   	$data[] = array(
		"recall_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=recalldetails&id=R-".$row['recall_id']."' >R-".$row['recall_id']."</a>" . $icons, 		
		"request_date"=> date('m/d/Y', strtotime( $row['request_date'] )),
		"recall_status"=>"<span class='wpsp_admin_label' style='".$status_style."'>".$row['recall_status']."</span>", 
   );
   
   // Clear icons
   $icons = '';
}


## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "aaData" => $data,
  "query" => $baseQuery,  
);



echo json_encode($response);