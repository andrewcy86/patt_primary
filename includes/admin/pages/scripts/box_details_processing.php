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

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = $_POST['search']['value']; // Search value

## Custom Field value
$box_id = str_replace(",", "|", $_POST['BoxID']);
$page_id = $_POST['page'];
$p_id = $_POST['PID'];
$searchGeneric = $_POST['searchGeneric'];
$isactive = $_POST['isactive'];

## Search 
$searchQuery = "";
if($searchGeneric != ''){
    //REVIEW
   $searchQuery .= " and (b.folderdocinfofile_id like '%".$searchGeneric."%' or 
      b.title like '%".$searchGeneric."%' or 
      b.date like '%".$searchGeneric."%' or
      b.lan_id like '%".$searchGeneric."%') ";
}

if($searchValue != ''){
    //REVIEW
   $searchQuery .= " and (b.folderdocinfofile_id like '%".$searchValue."%' or 
      b.title  like '%".$searchValue."%' or 
      b.date like '%".$searchValue."%' or
      b.lan_id like '%".$searchGeneric."%') ";
}

## Total number of records without filtering
//START REVIEW
if($isactive == 1) {
$sel = mysqli_query($con,"select count(*) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b
LEFT JOIN " . $wpdb->prefix . "users u ON u.ID = b.validation_user_id
WHERE b.box_id = ".$box_id);
} else {
$sel = mysqli_query($con,"select count(*) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b
LEFT JOIN " . $wpdb->prefix . "users u ON u.ID = b.validation_user_id
WHERE b.box_id = ".$box_id);
}
//END REVIEW
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
//START REVIEW
if($isactive == 1) {
$sel = mysqli_query($con,"select count(*) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b
LEFT JOIN " . $wpdb->prefix . "users u ON u.ID = b.validation_user_id
WHERE 1 ".$searchQuery." AND b.box_id = ".$box_id);
} else {
$sel = mysqli_query($con,"select count(*) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b
LEFT JOIN " . $wpdb->prefix . "users u ON u.ID = b.validation_user_id
WHERE 1 ".$searchQuery." AND b.box_id = ".$box_id);
}
//END REVIEW

$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

$url_var = '';
if ($p_id == 'requestdetails') {
$url_var = 'admin.php?pid=requestdetails&page=filedetails&id=';
} elseif ($p_id == 'boxsearch') {
$url_var = 'admin.php?pid=boxsearch&page=filedetails&id=';
} elseif ($p_id == 'docsearch') {
$url_var = 'admin.php?pid=docsearch&page=filedetails&id=';
} else {
$url_var = 'admin.php?&page=filedetails&id=';
}

## Fetch records

if ($rowperpage == '-1') {
$row_limit = '';
} else {
$row_limit = " limit ".$row.",".$rowperpage;    
}

//SQL query using functions to generate icons
//START REVIEW
if($isactive == 1) {
$boxQuery = "SELECT 
b.id as dbid,
CONCAT(

CASE WHEN (
SELECT " . $wpdb->prefix . "wpsc_epa_boxinfo.box_destroyed FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.id = b.box_id) > 0 AND
b.freeze <> 1

THEN CONCAT('<a href=\"".$url_var."',b.folderdocinfofile_id,'\" id=\"folderdocinfo_link\" style=\"color: #B4081A !important; text-decoration: underline line-through;\">',b.folderdocinfofile_id,'</a>')
ELSE CONCAT('<a href=\"".$url_var."',b.folderdocinfofile_id,'\" id=\"folderdocinfo_link\">',b.folderdocinfofile_id,'</a>')
END) as folderdocinfo_id_flag,
b.folderdocinfofile_id as folderdocinfo_id,
case when length(b.title) > 25 
then concat(substring(b.title, 1, 25), '...')
else b.title 
end 
as title,
b.date,
b.lan_id as epa_contact_email,

CONCAT(
CASE 
WHEN b.validation = 1 THEN CONCAT('[',

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

FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b
LEFT JOIN " . $wpdb->prefix . "users u ON u.ID = b.validation_user_id
WHERE 1 ".$searchQuery." AND b.box_id = ".$box_id." 
order by ".$columnName." ".$columnSortOrder.$row_limit;

} else {
$boxQuery = "SELECT 
b.id as dbid,
CONCAT(

CASE WHEN (
SELECT " . $wpdb->prefix . "wpsc_epa_boxinfo.box_destroyed FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.id = b.box_id) > 0 AND b.freeze <> 1
THEN CONCAT('<a href=\"".$url_var."',b.folderdocinfofile_id,'\" id=\"folderdocinfo_link\" style=\"color: #B4081A !important; text-decoration: line-through;\">',b.folderdocinfofile_id,'</a> <span style=\"font-size: 1em; color: #B4081A;\"><i class=\"fas fa-ban\" title=\"Box Destroyed\"></i></span>')
ELSE CONCAT('<a href=\"".$url_var."',b.folderdocinfofile_id,'\" id=\"folderdocinfo_link\">',b.folderdocinfofile_id,'</a>')
END) as folderdocinfo_id_flag,
b.folderdocinfofile_id as folderdocinfo_id,
case when length(b.title) > 25 
then concat(substring(b.title, 1, 25), '...')
else b.title 
end 
as title,
b.date,
b.lan_id as epa_contact_email,

CONCAT(
CASE 
WHEN b.validation = 1 THEN CONCAT('[',

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

FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b
LEFT JOIN " . $wpdb->prefix . "users u ON u.ID = b.validation_user_id
WHERE 1 ".$searchQuery." AND b.box_id = ".$box_id." 
order by ".$columnName." ".$columnSortOrder.$row_limit;
}
//END REVIEW
$boxRecords = mysqli_query($con, $boxQuery);
$data = array();

while ($row = mysqli_fetch_assoc($boxRecords)) {

$decline_icon = '';
$recall_icon = '';
$unauthorized_destruction_icon = '';
$freeze_icon = '';
$damaged_icon = '';
$box_destroyed_icon = '';

if($isactive == 1) {
    $type = 'folderfile';
}
else {
    $type = 'folderfile_archive';
}

if(Patt_Custom_Func::id_in_return($row['folderdocinfo_id'],$type) == 1){
$decline_icon = '<span style="font-size: 1em; color: #B4081A;margin-left:4px;"><i class="fas fa-undo" aria-hidden="true" title="Declined"></i><span class="sr-only">Declined</span></span>';
}

if(Patt_Custom_Func::id_in_recall($row['folderdocinfo_id'],$type) == 1){
$recall_icon = '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" aria-hidden="true" title="Recall"></i><span class="sr-only">Recall</span></span>';
}

if(Patt_Custom_Func::id_in_unauthorized_destruction($row['folderdocinfo_id'],$type) == 1) {
    $unauthorized_destruction_icon = ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span>';
}

if(Patt_Custom_Func::id_in_damaged($row['folderdocinfo_id'],$type) == 1) {
    $damaged_icon = ' <span style="font-size: 1em; color: #FFC300;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span>';
}

if(Patt_Custom_Func::id_in_freeze($row['folderdocinfo_id'],$type) == 1) {
    $freeze_icon = ' <span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span>';
}

if(Patt_Custom_Func::id_in_box_destroyed($row['folderdocinfo_id'],$type) == 1) {
    $box_destroyed_icon = ' <span style="font-size: 1em; color: #B4081A;"><i class="fas fa-ban" aria-hidden="true" title="Box Destroyed"></i><span class="sr-only">Box Destroyed</span></span>';
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
     "folderdocinfo_id_flag"=>$row['folderdocinfo_id_flag'].$box_destroyed_icon.$unauthorized_destruction_icon.$damaged_icon.$freeze_icon.$decline_icon.$recall_icon,
     "dbid"=>$row['dbid'],
     "title"=>$row['title'],
     "date"=>Patt_Custom_Func::get_converted_date($row['date']),
     "epa_contact_email"=>$row['epa_contact_email'],
     "validation"=>$validation_icon . ' ' . $row['validation']
   );
}

## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data,
  "test" => $boxQuery
);

echo json_encode($response);