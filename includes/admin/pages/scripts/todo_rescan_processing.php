<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');


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

if($columnName == 'priority') {
    $columnName = 'ticket_priority_order';
}

$get_current_user_id = $_POST['searchByUser'];
$re_scan_term_id = Patt_Custom_Func::get_term_by_slug( 're-scan' );   //743

// ## Total number of records without filtering
$query = mysqli_query($con, "SELECT COUNT(b.folderdocinfofile_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus c ON c.box_id = a.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_scan_list d ON d.box_id = a.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket e ON e.id = a.ticket_id
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.ticket_priority 
WHERE b.rescan = 1 AND a.box_destroyed = 0");

$records = mysqli_fetch_assoc($query);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$query = mysqli_query($con, "SELECT COUNT(b.folderdocinfofile_id) as allcount
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus c ON c.box_id = a.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_scan_list d ON d.box_id = a.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket e ON e.id = a.ticket_id
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.ticket_priority 
WHERE b.rescan = 1 AND a.box_destroyed = 0 AND c.status_id = ".$re_scan_term_id." AND c.user_id = " . $get_current_user_id);

$records = mysqli_fetch_assoc($query);
$totalRecordwithFilter = $records['allcount'];

## Base Query for Declines assigned to current user
$baseQuery = "SELECT b.title as title, b.folderdocinfofile_id, b.id as id, a.box_id,
CASE
WHEN d.scanning_id IS NOT NULL THEN d.scanning_id
WHEN d.stagingarea_id IS NOT NULL THEN d.stagingarea_id
WHEN d.cart_id IS NOT NULL THEN d.cart_id
WHEN d.shelf_location IS NOT NULL THEN d.shelf_location
ELSE '-'
END as physical_location,
e.request_id, f.name, e.ticket_priority,
CASE 
WHEN e.ticket_priority = 621 THEN 1
WHEN e.ticket_priority = 9 THEN 2
WHEN e.ticket_priority = 8 THEN 3
WHEN e.ticket_priority = 7 THEN 4
ELSE 999
END as ticket_priority_order

FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus c ON c.box_id = a.id
LEFT JOIN " . $wpdb->prefix . "wpsc_epa_scan_list d ON d.box_id = a.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket e ON e.id = a.ticket_id
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = e.ticket_priority 

WHERE b.rescan = 1 AND a.box_destroyed = 0 AND c.status_id = ".$re_scan_term_id." AND c.user_id = " . $get_current_user_id . "
ORDER BY ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$rescanRecords = mysqli_query($con, $baseQuery);
$data = array();

## Row Data
while ($row = mysqli_fetch_assoc($rescanRecords)) {
	$priority_background = get_term_meta($row['ticket_priority'], 'wpsc_priority_background_color', true);
    $priority_color = get_term_meta($row['ticket_priority'], 'wpsc_priority_color', true);
    $priority_style = "background-color:".$priority_background.";color:".$priority_color;
	
	$icons .= ' <span style="font-size: 1.0em;" onclick="wppatt_set_rescan(\''.$row['folderdocinfofile_id'].'\');" class="assign_agents_icon"><i class="fas fa-backspace" aria-hidden="true" title="Undo Re-scan"></i><span class="sr-only">Undo Re-scan</span></span>';
    
   	$data[] = array(
   	    "dbid"=>$row['id'],
		"folderdocinfofile_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=filedetails&pid=requestdetails&id=".$row['folderdocinfofile_id']."' >".$row['folderdocinfofile_id']."</a>" . $icons,
		"title"=> $row['title'],
		"box_id"=>"<a href='".$subfolder_path."/wp-admin/admin.php?page=boxdetails&pid=boxsearch&id=".$row['box_id']."' >".$row['box_id']."</a>",
		"request_id" =>"<a href='".$subfolder_path."/wp-admin/admin.php?page=wpsc-tickets&id=".$row['request_id']."' >".$row['request_id']."</a>",
		"physical_location" => $row['physical_location'],
		"priority" => "<span class='wpsp_admin_label' style='".$priority_style."'>".$row['name']."</span>", 
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