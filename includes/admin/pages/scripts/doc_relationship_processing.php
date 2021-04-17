<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');
	
$host = DB_HOST; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

$subfolder_path = site_url( '', 'relative'); 

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

## Custom Field value
$parent_id = $_POST['docid'];
$is_active = $_POST['isactive'];

## Total number of records without filtering
if($is_active == 1) {
    $sel = mysqli_query($con,"select count(*) as allcount from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE id != ".$parent_id." AND parent_id = ".$parent_id);
}
else {
    $sel = mysqli_query($con,"select count(*) as allcount from " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive WHERE id != ".$parent_id." AND parent_id = ".$parent_id);
}
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
if($is_active == 1) {
    $sel = mysqli_query($con,"select count(*) as allcount FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE id != ".$parent_id." AND parent_id = ".$parent_id);
}
else {
    $sel = mysqli_query($con,"select count(*) as allcount FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive WHERE id != ".$parent_id." AND parent_id = ".$parent_id);
}
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

$url_var = 'admin.php?pid=requestdetails&page=filedetails&id=';

## Fetch records
if($is_active == 1) {
    $docQuery = "SELECT 
    CONCAT(
    '<a href=\"".$url_var."',folderdocinfofile_id,'\" id=\"folderdocinfo_link\">',folderdocinfofile_id,'</a>') as folderdocinfofile_id,
    title,
    id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE parent_id = ".$parent_id." AND id != ".$parent_id." 
    order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
}
else {
    $docQuery = "SELECT 
    CONCAT(
    '<a href=\"".$url_var."',folderdocinfofile_id,'\" id=\"folderdocinfo_link\">',folderdocinfofile_id,'</a>') as folderdocinfofile_id,
    title,
    id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
    WHERE parent_id = ".$parent_id." AND id != ".$parent_id." 
    order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;    
}
$docRecords = mysqli_query($con, $docQuery);
$data = array();

while ($row = mysqli_fetch_assoc($docRecords)) {
   $data[] = array(
     "id"=>$row['id'],
     "folderdocinfofile_id"=>$row['folderdocinfofile_id'],
     "title"=>$row['title']
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