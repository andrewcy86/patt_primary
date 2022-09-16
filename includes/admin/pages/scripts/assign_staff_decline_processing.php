<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');


$host = DB_HOST; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

$subfolder_path = site_url( '', 'relative'); 

global $current_user, $wpscfunction;

$decline_received_tag = Patt_Custom_Func::get_term_by_slug( 'decline-pending-cancel' ); //754
$decline_decline_complete_tag = Patt_Custom_Func::get_term_by_slug( 'decline-complete' ); //1023
$decline_decline_cancelled_tag = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' ); //791
$decline_decline_expired_tag = Patt_Custom_Func::get_term_by_slug( 'decline-expired' ); //2726

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
$query = mysqli_query($con, "SELECT COUNT(DISTINCT a.return_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_return a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_return_users b ON b.return_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.return_status_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_shipping_tracking d ON d.id = a.shipping_tracking_id
INNER JOIN wpqa_wpsc_epa_return_items e ON e.return_id = a.id
INNER JOIN wpqa_wpsc_epa_boxinfo g ON g.id = e.box_id
INNER JOIN wpqa_wpsc_epa_storage_location h ON g.storage_location_id = h.id
INNER JOIN wpqa_terms t ON t.term_id = h.digitization_center
INNER JOIN wpqa_users u ON u.ID = b.user_id

WHERE a.return_complete = 0 AND u.ID <> " . $current_user->ID);

$records = mysqli_fetch_assoc($query);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$query = mysqli_query($con, "SELECT COUNT(DISTINCT a.return_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_return a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_return_users b ON b.return_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.return_status_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_shipping_tracking d ON d.id = a.shipping_tracking_id
INNER JOIN wpqa_wpsc_epa_return_items e ON e.return_id = a.id
INNER JOIN wpqa_wpsc_epa_boxinfo g ON g.id = e.box_id
INNER JOIN wpqa_wpsc_epa_storage_location h ON g.storage_location_id = h.id
INNER JOIN wpqa_terms t ON t.term_id = h.digitization_center
INNER JOIN wpqa_users u ON u.ID = b.user_id

WHERE a.return_complete = 0 AND u.ID <> " . $current_user->ID);

/*$query = mysqli_query($con, "SELECT COUNT(a.return_id) as allcount
FROM " . $wpdb->prefix . "wpsc_epa_return a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_return_users b ON b.return_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.return_status_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_shipping_tracking d ON d.id = a.shipping_tracking_id
WHERE a.return_complete = 0 AND a.return_status_id NOT IN (".$decline_received_tag.",".$decline_decline_complete_tag.",".$decline_decline_cancelled_tag.",".$decline_decline_expired_tag.") AND a.id != '-99999' AND b.user_id != " . $get_current_user_id);
*/
$records = mysqli_fetch_assoc($query);
$totalRecordwithFilter = $records['allcount'];

## Base Query for Declines assigned to current user
$baseQuery = "SELECT DISTINCT a.return_id, c.name as decline_status, a.return_date, a.return_status_id, a.return_initiated, a.return_complete,
d.tracking_number, t.name as digitization_center,

CASE

WHEN (
SELECT (a.return_initiated+a.return_complete) FROM wpqa_wpsc_epa_return WHERE id=a.return_id
) = 0
THEN '<strong>Preparation</strong>'
WHEN (
SELECT (a.return_initiated+a.return_complete) FROM wpqa_wpsc_epa_return WHERE id=a.return_id
) = 1
THEN '<strong>In Progress</strong>'
WHEN (
SELECT (a.return_initiated+a.return_complete) FROM wpqa_wpsc_epa_return WHERE id=a.return_id
) = 2
THEN '<strong>Completed</strong>'
    ELSE 'Error'
END as action

FROM " . $wpdb->prefix . "wpsc_epa_return a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_return_users b ON b.return_id = a.id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.return_status_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_shipping_tracking d ON d.id = a.shipping_tracking_id
INNER JOIN wpqa_wpsc_epa_return_items e ON e.return_id = a.id
INNER JOIN wpqa_wpsc_epa_boxinfo g ON g.id = e.box_id
INNER JOIN wpqa_wpsc_epa_storage_location h ON g.storage_location_id = h.id
INNER JOIN wpqa_terms t ON t.term_id = h.digitization_center
INNER JOIN wpqa_users u ON u.ID = b.user_id

WHERE a.return_complete = 0 AND u.ID <> " . $current_user->ID . "
ORDER BY a.id, ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$declineRecords = mysqli_query($con, $baseQuery);
$data = array();

## Row Data
while ($row = mysqli_fetch_assoc($declineRecords)) {
  
  	$return_id = $row['return_id'];
  
  	// Get user name foe employee assigend column
  	$user_display_name_query = $wpdb->get_row("SELECT DISTINCT u.display_name


                      FROM " . $wpdb->prefix . "wpsc_epa_return a
                      INNER JOIN " . $wpdb->prefix . "wpsc_epa_return_users b ON b.return_id = a.id
                      INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.return_status_id
                      INNER JOIN " . $wpdb->prefix . "wpsc_epa_shipping_tracking d ON d.id = a.shipping_tracking_id
                      INNER JOIN wpqa_wpsc_epa_return_items e ON e.return_id = a.id
                      INNER JOIN wpqa_wpsc_epa_boxinfo g ON g.id = e.box_id
                      INNER JOIN wpqa_wpsc_epa_storage_location h ON g.storage_location_id = h.id
                      INNER JOIN wpqa_terms t ON t.term_id = h.digitization_center
                      INNER JOIN wpqa_users u ON u.ID = b.user_id
                      WHERE a.return_id = " . $return_id . " AND u.ID <> " . $current_user->ID);
  	
  	$user_display_name = $user_display_name_query->display_name;

   	// Makes the Status column pretty
	$status_term_id = $row['return_status_id'];
	$status_background = get_term_meta($status_term_id, 'wppatt_return_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wppatt_return_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color;

	$icons .= ' <span style="font-size: 1.0em;" onclick="edit_decline_to_do(\''.$row['return_id'].'\')" class="assign_agents_icon"><i class="fas fa-clipboard-check" aria-hidden="true" title="Decline To Do"></i><span class="sr-only">Recall To Do</span></span>';

    if(empty($row['tracking_number'])){
     //$icons .= ' <span style="font-size: 1.0em;"><i class="fas fa-truck" aria-hidden="true" title="Shipping Tracking Number Required"></i><span class="sr-only">Shipping Tracking Number Required</span></span>';
    }
  
    	// get all pertinent term_ids
//Recall status slugs
$decline_initiated_tag = get_term_by('slug', 'decline-initiated', 'wppatt_return_statuses'); // 752
$decline_complete_tag = get_term_by('slug', 'decline-complete', 'wppatt_return_statuses'); // 1023


$decline_initiated_term = $decline_initiated_tag->term_id;
$decline_complete_term = $decline_complete_tag->term_id;

// 0 - Upcoming, 1 - completed, 2 - current
$action_status = '';
  
// Check the status of each todo list item in the db
  if($row['return_initiated'] == 0 && $status_term_id == $decline_initiated_term){
    $action_status = 'Current';
  }
  else if($row['return_initiated'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['return_initiated'] == 1){
    $action_status = 'Completed';
  }
  else if($row['return_complete'] == 0 && $status_term_id == $decline_complete_term){
    $action_status = 'Current';
  }
  else if($row['return_complete'] == 0){
    $action_status = 'Upcoming';
  }
  else if($row['return_complete'] == 1){
    $action_status = 'Completed';
  }
  
   	$data[] = array(
		"return_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=declinedetails&id=D-".$row['return_id']."' >D-".$row['return_id']."</a>" . $icons,
		"return_date"=> date('m/d/Y', strtotime( $row['return_date'] )),
		"decline_status"=>"<span class='wpsp_admin_label' style='".$status_style."'>".$row['decline_status']."</span>",
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