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
$searchByProgramOffice = $_POST['searchByProgramOffice'];
$searchByDigitizationCenter = $_POST['searchByDigitizationCenter'];
$searchByPriority = $_POST['searchByPriority'];
$searchByRecallDecline = $_POST['searchByRecallDecline'];
$searchGeneric = $_POST['searchGeneric'];
$searchByStatus = $_POST['searchByStatus'];
$searchByUser = $_POST['searchByUser'];
$searchByUserAAVal = $_REQUEST['searchByUserAAVal'];
$searchByUserAAName = $_REQUEST['searchByUserAAName'];
$is_requester = $_POST['is_requester'];


## Search 
$searchQuery = " ";

//Add Pallet Support
//Extract ID and determine if it is Box or Pallet
//$searchByBoxID = str_replace(",", "|", $_POST['searchByBoxID']);

$BoxID_arr = explode(",", $_POST['searchByBoxID']);  

$newBoxID_arr = array();
$newPalletID_arr = array();

foreach($BoxID_arr as $key => $value) {
//Check if Box ID
if (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $value)) {
array_push($newBoxID_arr,$value);
}
//Check if Pallet ID
if (preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $value)) {
array_push($newPalletID_arr,$value);
}
}

$newBoxID_str = str_replace(",", "|", implode(',', $newBoxID_arr));
$newPalletID_str = str_replace(",", "|", implode(',', $newPalletID_arr));

if($newBoxID_str != ''){
   $searchQuery .= " and (a.box_id REGEXP '^(".$newBoxID_str.")$' ) ";
}

if($newPalletID_str != ''){
   $searchQuery .= " and (a.pallet_id REGEXP '^(".$newPalletID_str.")$' ) ";
}

if($searchByProgramOffice != ''){
   $searchQuery .= " and (c.office_acronym='".$searchByProgramOffice."') ";
}

if($searchByDigitizationCenter != ''){
   $searchQuery .= " and (e.name ='".$searchByDigitizationCenter."') ";
}

if($searchByPriority != ''){
   $searchQuery .= " and (b.ticket_priority='".$searchByPriority."') ";
}

if($searchByStatus != ''){
   $searchQuery .= " and (f.name ='".$searchByStatus."') ";
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
            f.recall_status_id NOT IN (".$status_recall_denied_term_id.",".$status_recall_cancelled_term_id.",".$status_recall_complete_term_id.")
            )";
        }

        if($searchByRecallDecline == 'Decline') {
            $searchQuery .= "and (
            g.return_id <> ''
            )";
        }

}


// If a user is a requester, only show the boxes from requests (tickets) they have submitted. 
if( $is_requester == 'true' ){
	$user_name = $current_user->display_name;
	$searchQuery .= " and (b.customer_name ='".$user_name."') ";
}


// Search by User code
if($searchByUser != ''){
	if( $searchByUser == 'mine' ) {
		$box_ids_for_user = '';
		$mini_query = "select distinct box_id from " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus where user_id = ".$current_user->ID;
		$mini_records = mysqli_query($con, $mini_query);
		while ($rox = mysqli_fetch_assoc($mini_records)) {
			$box_ids_for_user .= $rox['box_id'].", ";
		}
		$box_ids_for_user = substr($box_ids_for_user, 0, -2);
		
		if( $box_ids_for_user == null ) {
			$searchQuery .= " and (a.id IN (-99999)) ";
		} else {
			$searchQuery .= " and (a.id IN (".$box_ids_for_user.")) ";
		}
		
		
	} elseif( $searchByUser == 'not assigned' ) {
		
		// Register Box Status Taxonomy
		if( !taxonomy_exists('wpsc_box_statuses') ) {
			$args = array(
				'public' => false,
				'rewrite' => false
			);
			register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
		}
		
		// Get List of Box Statuses
		$box_statuses = get_terms([
			'taxonomy'   => 'wpsc_box_statuses',
			'hide_empty' => false,
			'orderby'    => 'meta_value_num',
			'order'    	 => 'ASC',
			'meta_query' => array('order_clause' => array('key' => 'wpsc_box_status_load_order')),
		]);
		
		// List of box status that do not need agents assigned.
		$ignore_box_status = ['Pending', 'Ingestion', 'Completed', 'Dispositioned'];
// 		$ignore_box_status = []; //show all box status
		
		$term_id_array = array();
		foreach( $box_statuses as $key=>$box ) {
			if( in_array( $box->name, $ignore_box_status ) ) {
				unset($box_statuses[$key]);
				
			} else {
				$term_id_array[] = $box->term_id;
			}
		}
		array_values($box_statuses);
		
		$search_in_box_statuses = '';
		foreach( $box_statuses as $status ) {
			$search_in_box_statuses .= $status->term_id.', ';
		}
		$search_in_box_statuses = substr($search_in_box_statuses, 0, -2);
		
		$box_ids_for_user = '';
		
		//Box status slugs
		$digitized_not_validated_tag = get_term_by('slug', 'closed', 'wpsc_box_statuses'); //6
		$qa_qc_tag = get_term_by('slug', 'q-a', 'wpsc_box_statuses'); //65
		$destruction_approval_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); //68
		$scanning_digitization_tag = get_term_by('slug', 'scanning-digitization', 'wpsc_box_statuses'); //671
		$scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); //672
		$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); //674
		$rescan_tag = get_term_by('slug', 're-scan', 'wpsc_box_statuses'); //743
		
		// Get all distinct box_id that have been assigned.
		$mini_query = "select box_id 
						from 
							" . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus 
						where 
							status_id IN (".$digitized_not_validated_tag->term_id.", ".$qa_qc_tag->term_id.", ".$destruction_approval_tag->term_id.", '".$scanning_digitization_tag->term_id."', ".$scanning_preparation_tag->term_id.", ".$validation_tag->term_id.", ".$rescan_tag->term_id.") 
						group by 
							box_id 
						having count(distinct status_id) = 7 ";
		$mini_records = mysqli_query($con, $mini_query); 
		while ($rox = mysqli_fetch_assoc($mini_records)) {
			$box_ids_for_user .= $rox['box_id'].", ";
		}
		$box_ids_for_user = substr($box_ids_for_user, 0, -2);
		
		$searchQuery .= " and (a.id NOT IN (".$box_ids_for_user.")) ";
	} elseif( $searchByUser == 'search for user' ) {
		$search_true = (isset($searchByUserAAVal) ) ? true : false;
		$array_of_wp_user_id = Patt_Custom_Func::translate_user_id($searchByUserAAVal, 'wp_user_id');
		$user_id_str = '';
 		if( $search_true ) {
			foreach( $array_of_wp_user_id as $id ) {
				$user_id_str .= $id.', ';
			}
			$user_id_str = substr($user_id_str, 0, -2);
			
			$box_ids_for_users = '';
			$mini_query = "select distinct box_id from " . $wpdb->prefix . "wpsc_epa_boxinfo_userstatus where user_id IN (".$user_id_str.")";
			$mini_records = mysqli_query($con, $mini_query);
			while ($rox = mysqli_fetch_assoc($mini_records)) {
				$box_ids_for_users .= $rox['box_id'].", ";
			}
			$box_ids_for_users = substr($box_ids_for_users, 0, -2);
			
			if( $user_id_str == '' ) {
				
			} else {
				if( $box_ids_for_users == null ) {
					$searchQuery .= " and (a.id IN (-99999)) ";
				} else {
					$searchQuery .= " and (a.id IN (".$box_ids_for_users.")) ";
				}
				
				//$searchQuery .= " and (a.id IN (".$box_ids_for_users.")) ";	
			}
		
		}
		

	}
	

}


if($searchGeneric != ''){
   $searchQuery .= " and (a.box_id like '%".$searchGeneric."%' or 
      (a.pallet_id like '%".$searchGeneric."%' and a.pallet_id <> '') or
      b.request_id like '%".$searchGeneric."%' or 
      e.name like '%".$searchGeneric."%' or
      c.office_acronym like '%".$searchGeneric."%' or
      b.ticket_priority like '%".$searchGeneric."%') ";
}

if($searchValue != ''){
   $searchQuery .= " and (a.box_id like '%".$searchValue."%' or
      (a.pallet_id like '%".$searchGeneric."%' and a.pallet_id <> '') or
      b.request_id like '%".$searchValue."%' or 
      e.name like '%".$searchValue."%' or
      c.office_acronym like '%".$searchValue."%' or
      b.ticket_priority like '%".$searchValue."%') ";
}

## Total number of records without filtering
$sel = mysqli_query($con,"select count(*) as allcount 
from " . $wpdb->prefix . "wpsc_epa_boxinfo as a
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id

WHERE a.id <> -99999 AND b.active <> 0");
//$sel = mysqli_query($con,"select count(*) as allcount from wpqa_wpsc_epa_boxinfo WHERE id <> -99999");
//$sel = mysqli_query($con,"select count(*) as allcount from wpqa_wpsc_ticket WHERE id <> -99999 AND active <> 0");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($con,"select count(a.box_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center
LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id

WHERE (b.active <> 0) AND (a.id <> -99999) AND 1 ".$searchQuery); //(b.active <> 0) AND
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
/*
$boxQuery = "
SELECT 
a.box_id, a.id, f.name as box_status, f.term_id as term,
CONCAT(

CASE WHEN 
(
SELECT sum(c.freeze = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id
) <> 0 AND
a.box_destroyed > 0 


THEN CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\" style=\"color: #FF0000 !important;\">',a.box_id,'</a> <span style=\"font-size: 1em; color: #FF0000;\"><i class=\"fas fa-ban\" title=\"Box Destroyed\"></i></span>')

WHEN a.box_destroyed > 0 


THEN CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\" style=\"color: #FF0000 !important; text-decoration: line-through;\">',a.box_id,'</a> <span style=\"font-size: 1em; color: #FF0000;\"><i class=\"fas fa-ban\" title=\"Box Destroyed\"></i></span>')


ELSE CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\">',a.box_id,'</a>')
END,


CASE 
WHEN (SELECT sum(c.freeze = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id) > 0 THEN ' <span style=\"font-size: 1em; color: #009ACD;\"><i class=\"fas fa-snowflake\" title=\"Freeze\"></i></span>'
ELSE '' 
END,
CASE 
WHEN (SELECT sum(c.unauthorized_destruction = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id WHERE b.box_id = a.id) > 0 THEN ' <span style=\"font-size: 1em; color: #8b0000;\"><i class=\"fas fa-flag\" title=\"Unauthorized Destruction\"></i></span>'
ELSE '' 
END
) as box_id_flag,

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

CONCAT('<a href=admin.php?page=wpsc-tickets&id=',b.request_id,'>',b.request_id,'</a>') as request_id, 
e.name as location, 
c.office_acronym as acronym,
CONCAT(
CASE 

WHEN (SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id) = 0 AND
(SELECT count(c.id) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE b.box_id = a.id) = 0
THEN
''

WHEN (SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id) != 0 AND 
(SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id) < (SELECT count(c.id) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE b.box_id = a.id)
THEN 
'<span style=\"font-size: 1.3em; color: #FF8C00;\"><i class=\"fas fa-times-circle\" title=\"Not Validated\"></i></span> '

WHEN (SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE b.box_id = a.id) = 0 AND 
(SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id) < (SELECT count(c.id) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE b.box_id = a.id)
THEN 
'<span style=\"font-size: 1.3em; color: #8b0000;\"><i class=\"fas fa-times-circle\" title=\"Not Validated\"></i></span> '

WHEN (SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id) = (SELECT count(c.id) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE b.box_id = a.id)
THEN 
'<span style=\"font-size: 1.3em; color: #008000;\"><i class=\"fas fa-check-circle\" title=\"Validated\"></i></span> '

ELSE '' 
END,

CASE 
WHEN (SELECT count(id) FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo WHERE box_id = a.id) != 0
THEN
CONCAT((SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id), '/', (SELECT count(fdif.id) FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files fdif INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo fdi ON fdi.id = fdif.folderdocinfo_id WHERE fdi.box_id = a.id))
ELSE '-'
END
) as validation

FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a

INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center


LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id
   
WHERE (b.active <> 0) AND (a.id <> -99999) AND 1 ".$searchQuery." 
order by a.id AND ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
*/

//INNER JOIN wpqa_wpsc_epa_boxinfo_userstatus g ON g.box_id = a.id
//INNER JOIN wpqa_wpsc_epa_boxinfo_userstatus h ON h.box_id = a.id 

$boxQuery = "
SELECT 
a.box_id, a.id as dbid, f.name as box_status, f.term_id as term,
CONCAT(

CASE WHEN 
(
SELECT sum(c.freeze = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id
) <> 0 AND
a.box_destroyed > 0 


THEN CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\" style=\"color: #FF0000 !important;\">',a.box_id,'</a>')

WHEN a.box_destroyed > 0 


THEN CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\" style=\"color: #FF0000 !important; text-decoration: line-through;\">',a.box_id,'</a>')


ELSE CONCAT('<a href=\"admin.php?page=boxdetails&pid=boxsearch&id=',a.box_id,'\">',a.box_id,'</a>')
END) as box_id_flag,

a.pallet_id as pallet_id,

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

CONCAT('<a href=admin.php?page=wpsc-tickets&id=',b.request_id,'>',b.request_id,'</a>') as request_id, 
e.name as location, 
c.office_acronym as acronym,
CONCAT(
CASE 
WHEN (SELECT count(id) FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo WHERE box_id = a.id) != 0
THEN
CONCAT((SELECT sum(c.validation = 1) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo b 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id 
WHERE b.box_id = a.id), '/', (SELECT count(fdif.id) FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files fdif INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo fdi ON fdi.id = fdif.folderdocinfo_id WHERE fdi.box_id = a.id))
ELSE '-'
END
) as validation

FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a

INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center


LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id
   
WHERE (b.active <> 0) AND (a.id <> -99999) AND 1 ".$searchQuery." 
order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;

$boxRecords = mysqli_query($con, $boxQuery);
$data = array();
// $assigned_agents_icon = '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents()" class="assign_agents_icon"><i class="fas fa-user-friends" title="Assigned Agents"></i></span>';

while ($row = mysqli_fetch_assoc($boxRecords)) {
	
	$status_term_id = $row['term'];
	$status_background = get_term_meta($status_term_id, 'wpsc_box_status_background_color', true);
	$status_color = get_term_meta($status_term_id, 'wpsc_box_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color.";";
	$box_status = "<span class='wpsp_admin_label' style='".$status_style."'>".$row['box_status']."</span>";
	
	//$assigned_agents_icon = '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents(666)" class="assign_agents_icon"><i class="fas fa-user-friends" title="Assigned Agents"></i></span>';
	
	$assigned_agents_icon = '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents(\''.$row['box_id'].'\')" class="assign_agents_icon"><i class="fas fa-user-friends" title="Assigned Agents"></i></span>';

$decline_icon = '';
$recall_icon = '';
$unauthorized_destruction_icon = '';
$freeze_icon = '';
$box_destroyed_icon = '';
$damaged_icon = '';
$type = 'box';

if(Patt_Custom_Func::id_in_return($row['box_id'],$type) == 1){
$decline_icon = '<span style="font-size: 1em; color: #FF0000;margin-left:4px;"><i class="fas fa-undo" title="Declined"></i></span>';
}

if(Patt_Custom_Func::id_in_recall($row['box_id'],$type) == 1){
$recall_icon = '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" title="Recall"></i></span>';
}

if(Patt_Custom_Func::id_in_unauthorized_destruction($row['box_id'],$type) == 1) {
    $unauthorized_destruction_icon = ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" title="Unauthorized Destruction"></i></span>';
}

if(Patt_Custom_Func::id_in_damaged($row['box_id'],$type) == 1) {
    $damaged_icon = ' <span style="font-size: 1em; color: #FFC300;"><i class="fas fa-bolt" title="Damaged"></i></span>';
}

if(Patt_Custom_Func::id_in_freeze($row['box_id'],$type) == 1) {
    $freeze_icon = ' <span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" title="Freeze"></i></span>';
}

if(Patt_Custom_Func::id_in_box_destroyed($row['box_id'],$type) == 1) {
    $box_destroyed_icon = ' <span style="font-size: 1em; color: #FF0000;"><i class="fas fa-ban" title="Box Destroyed"></i></span>';
}

$get_file_count = $wpdb->get_row("SELECT COUNT(c.id) as total
FROM wpqa_wpsc_epa_boxinfo a 
INNER JOIN wpqa_wpsc_epa_folderdocinfo b ON b.box_id = a.id
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE a.box_id = '" .  $row['box_id'] . "'");

$get_validation_count = $wpdb->get_row("SELECT SUM(c.validation) as val_count
FROM wpqa_wpsc_epa_boxinfo a 
INNER JOIN wpqa_wpsc_epa_folderdocinfo b ON b.box_id = a.id
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE a.box_id = '" .  $row['box_id'] . "'");

if(Patt_Custom_Func::id_in_validation($row['box_id'],$type) == 1) {
    $validation_icon = '<span style="font-size: 1.3em; color: #008000;"><i class="fas fa-check-circle" title="Validated"></i></span> ';
}
else if( ($get_validation_count->val_count > 0) && ($get_validation_count->val_count < $get_file_count->total) ) {
    $validation_icon = '<span style="font-size: 1.3em; color: #FF8C00;"><i class="fas fa-times-circle" title="Not Validated"></i></span> ';
}
else {
    $validation_icon = '<span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" title="Not Validated"></i></span> ';
}

if ($row['pallet_id'] == ''){
$pallet_id = 'Unassigned';
} else {
$pallet_id = $row['pallet_id'];
}

	$data[] = array(
		"box_id"=>$row['box_id'],
		"box_id_flag"=>$row['box_id_flag'].$box_destroyed_icon.$unauthorized_destruction_icon.$damaged_icon.$freeze_icon.$decline_icon.$recall_icon.$assigned_agents_icon, 
		"dbid"=>$row['dbid'],
	    //"box_id_column"=>array("dbid"=>$row['dbid'],"box_id"=>$row['box_id'].$freeze_icon.$unauthorized_destruction_icon.$decline_icon.$recall_icon.$assigned_agents_icon),
		//"ticket_priority"=>$row['ticket_priority_text'],
		"pallet_id"=>$pallet_id,
		"ticket_priority"=>$row['ticket_priority'],
		"status"=>$box_status,
		"request_id"=>$row['request_id'],
		"location"=>$row['location'],
		"acronym"=>$row['acronym'],
// 		"acronym"=>$searchQuery,
// 		"acronym"=>$searchByUserAAVal,
		"validation"=>$validation_icon . ' ' . $row['validation'],
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