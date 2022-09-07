<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');

global $wpdb, $current_user, $wpscfunction;

$agent_permissions = $wpscfunction->get_current_agent_permissions();

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
$searchByECMSSEMS = $_POST['searchByECMSSEMS'];
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

$ecms_sems = '';

if($searchByECMSSEMS != ''){
    if($searchByECMSSEMS == 'ECMS') {
        $ecms_sems = ' AND z.meta_key = "super_fund" AND z.meta_value = "false" ';
    }
    
    if($searchByECMSSEMS == 'SEMS') {
        $ecms_sems = ' AND z.meta_key = "super_fund" AND z.meta_value = "true" ';
    }
}

// If a user is a requester, only show the boxes from requests (tickets) they have submitted. 
if( $is_requester == 'true' ){
	$user_name = $current_user->display_name;
	
	$get_aa_ship_groups = Patt_Custom_Func::get_requestor_group($current_user->ID);
    $user_list = implode(",", $get_aa_ship_groups);
	
	if(!empty($user_list)) {
	    $searchQuery .= " and ( (b.customer_name ='".$user_name."') OR um.user_id IN ($user_list) ) ";
	}
	else {
	    $searchQuery .= " and (b.customer_name ='".$user_name."') ";
	}
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

   $searchQuery .= " and (a.box_id like '%".$searchGeneric."%' or 
      (a.pallet_id like '%".$searchGeneric."%' and a.pallet_id <> '') or
      b.request_id like '%".$searchGeneric."%' or
      c.office_acronym like '%".$searchGeneric."%') ";
}
}

if($searchValue != ''){
   $searchQuery .= " and (a.box_id like '%".$searchValue."%' or
      (a.pallet_id like '%".$searchGeneric."%' and a.pallet_id <> '') or
      b.request_id like '%".$searchValue."%' or
      c.office_acronym like '%".$searchValue."%') ";
}

## Total number of records without filtering
$sel = mysqli_query($con,"select count(DISTINCT a.box_id) as allcount 
from " . $wpdb->prefix . "wpsc_epa_boxinfo as a
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center

LEFT JOIN " . $wpdb->prefix . "users u ON u.user_email = b.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = u.ID

LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id

WHERE a.id <> -99999 AND b.active <> 0 " . $ecms_sems . " ");
//$sel = mysqli_query($con,"select count(*) as allcount from wpqa_wpsc_epa_boxinfo WHERE id <> -99999");
//$sel = mysqli_query($con,"select count(*) as allcount from wpqa_wpsc_ticket WHERE id <> -99999 AND active <> 0");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($con,"select count(DISTINCT a.box_id) as allcount 
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a
INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center

LEFT JOIN " . $wpdb->prefix . "users u ON u.user_email = b.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = u.ID

LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id

WHERE (b.active <> 0) AND (a.id <> -99999) " . $ecms_sems . " AND 1 ".$searchQuery); //(b.active <> 0) AND
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
//REVIEW
$boxQuery = "
SELECT DISTINCT
a.box_id, a.id as dbid, f.name as box_status, a.box_previous_status as box_previous_status, f.term_id as term,


a.pallet_id as pallet_id,

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

b.request_id as request_id_new,
e.name as location, 
c.office_acronym as acronym

FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a

INNER JOIN " . $wpdb->prefix . "terms f ON f.term_id = a.box_status
INNER JOIN " . $wpdb->prefix . "wpsc_ticket as b ON a.ticket_id = b.id
LEFT JOIN " . $wpdb->prefix . "wpsc_ticketmeta as z ON z.ticket_id = b.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as c ON a.program_office_id = c.office_code
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location as d ON a.storage_location_id = d.id
INNER JOIN " . $wpdb->prefix . "terms e ON e.term_id = d.digitization_center

LEFT JOIN " . $wpdb->prefix . "users u ON u.user_email = b.customer_email
LEFT JOIN " . $wpdb->prefix . "usermeta um ON um.user_id = u.ID

LEFT JOIN (   SELECT DISTINCT recall_status_id, box_id, folderdoc_id
   FROM   " . $wpdb->prefix . "wpsc_epa_recallrequest
   GROUP BY box_id) AS f ON (f.box_id = a.id)

LEFT JOIN (   SELECT a.box_id, a.return_id
   FROM   " . $wpdb->prefix . "wpsc_epa_return_items a
   LEFT JOIN  " . $wpdb->prefix . "wpsc_epa_return b ON a.return_id = b.id
   WHERE a.box_id <> '-99999' AND b.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
   GROUP  BY a.box_id ) AS g ON g.box_id = a.id
   
WHERE (b.active <> 0) AND (a.id <> -99999) " . $ecms_sems . " AND 1 ".$searchQuery." 
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
	$assigned_agents_icon = '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents(\''.$row['box_id'].'\')" class="assign_agents_icon"><i class="fas fa-user-friends" aria-hidden="true" title="Assigned Agents"></i><span class="sr-only">Assigned Agents</span></span>';
    
    if(($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')){	
	    $assigned_agents_icon .= ' <span style="font-size: 1.0em; color: #8b0000;" onclick="edit_to_do(\''.$row['box_id'].'\')" class="assign_agents_icon"><i class="fas fa-clipboard-check" aria-hidden="true" title="Box Status Completion"></i><span class="sr-only">Box Status Completion</span></span>';
    }
    
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

if ($row['pallet_id'] == ''){
$pallet_id = 'Unassigned';
} else {
$pallet_id = $row['pallet_id'];
}

	$data[] = array(
		"box_id"=>$row['box_id'],
		"box_id_flag"=>$box_id_flag.$box_destroyed_icon.$unauthorized_destruction_icon.$damaged_icon.$freeze_icon.$decline_icon.$recall_icon.$assigned_agents_icon, 
		"dbid"=>$row['dbid'],
	    //"box_id_column"=>array("dbid"=>$row['dbid'],"box_id"=>$row['box_id'].$freeze_icon.$unauthorized_destruction_icon.$decline_icon.$recall_icon.$assigned_agents_icon),
		//"ticket_priority"=>$row['ticket_priority_text'],
		"pallet_id"=>$pallet_id,
		"ticket_priority"=>$priority,
		"status"=>$box_status,
		"request_id"=>$request_id_link,
		"location"=>$row['location'],
		"acronym"=>$row['acronym'],
// 		"acronym"=>$searchQuery,
// 		"acronym"=>$searchByUserAAVal,
		"validation"=>$validation_icon . ' ' . $validation,
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
  
		"test111"=>$boxQuery,
"test" => $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php'
);

echo json_encode($response);