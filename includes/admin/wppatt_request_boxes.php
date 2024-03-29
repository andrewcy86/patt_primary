<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$subfolder_path = site_url( '', 'relative'); 

global $wpdb, $current_user, $wpscfunction;
include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

if (!($current_user->ID && $current_user->has_cap('wpsc_agent'))) {
		exit;
}
$ticket_id 	 = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0 ;
$ticket_data         = $wpscfunction->get_ticket($ticket_id);
$status_id           = $ticket_data['ticket_status'];
$raisedby_email = $wpscfunction->get_ticket_fields($ticket_id, 'customer_email');
$wpsc_appearance_modal_window = get_option('wpsc_modal_window');
$wpsc_appearance_ticket_list = get_option('wpsc_appearance_ticket_list');

$agent_permissions = $wpscfunction->get_current_agent_permissions();
$current_agent_id  = $wpscfunction->get_current_user_agent_id();

$restrict_rules = array(
	'relation' => 'AND',
	array(
		'key'            => 'customer_email',
		'value'          => $raisedby_email,
		'compare'        => '='
	),
	array(
		'key'            => 'active',
		'value'          => 1,
		'compare'        => '='
	)
);
$ticket_permission = array(
	'relation' => 'OR'
);
if ($agent_permissions['view_unassigned']) {
	$ticket_permission[] = array(
		'key'            => 'assigned_agent',
		'value'          => 0,
		'compare'        => '='
	);
}

if ($agent_permissions['view_assigned_me']) {
	$ticket_permission[] = array(
		'key'            => 'assigned_agent',
		'value'          => $current_agent_id,
		'compare'        => '='
	);
}

if ($agent_permissions['view_assigned_others']) {
	$ticket_permission[] = array(
		'key'            => 'assigned_agent',
		'value'          => array(0,$current_agent_id),
		'compare'        => 'NOT IN'
	);
}

$restrict_rules [] = $ticket_permission;
$select_str        = 'DISTINCT t.*';
$sql               = $wpscfunction->get_sql_query( $select_str, $restrict_rules);
$tickets           = $wpdb->get_results($sql);
$ticket_list       = json_decode(json_encode($tickets), true);

$ticket_list_items = get_terms([
  'taxonomy'   => 'wpsc_ticket_custom_fields',
  'hide_empty' => false,
  'orderby'    => 'meta_value_num',
  'meta_key'	 => 'wpsc_tl_agent_load_order',
  'order'    	 => 'ASC',
  'meta_query' => array(
    'relation' => 'AND',
    array(
      'key'       => 'wpsc_allow_ticket_list',
      'value'     => '1',
      'compare'   => '='
    ),
    array(
      'key'       => 'wpsc_agent_ticket_list_status',
      'value'     => '1',
      'compare'   => '='
    ),
  ),
]);
ob_start();
?>

<?php //echo $status_id ?>
<style>
div.dataTables_wrapper {
        width: 100%;
        margin: 0;
    }

.datatable_header {
background-color: rgb(66, 73, 73) !important; 
color: rgb(255, 255, 255) !important;
width: 100%;
}
.bootstrap-iso .alert {
    padding: 8px;
}
.assign_agents_icon {
	cursor: pointer;
}
</style>
<?php

//check whether request is active, if not disable buttons
$is_active = Patt_Custom_Func::ticket_active( $ticket_id );

//Request Statuses
$request_new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$request_tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$request_initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$request_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003
$request_cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69

$alert_arr = array($request_initial_review_rejected_tag->term_id, $request_completed_dispositioned_tag->term_id);

$status_id_arr = array($request_new_request_tag->term_id, $request_cancelled_tag->term_id, $request_initial_review_rejected_tag->term_id, $request_completed_dispositioned_tag->term_id);
$status_id_tabled_arr = array($request_tabled_tag->term_id, $request_new_request_tag->term_id, $request_cancelled_tag->term_id, $request_initial_review_rejected_tag->term_id, $request_completed_dispositioned_tag->term_id);

//Fix if location is unassigned and request status is initial review complete

$get_dc = $wpdb->get_row("SELECT ticket_category
FROM " . $wpdb->prefix . "wpsc_ticket
WHERE id = '".$ticket_id."'");

$get_dc_val = $get_dc->ticket_category;

$destruction_flag = 0;
$destruction_boxes = '';

$dc = Patt_Custom_Func::get_default_digitization_center($ticket_id);

//Double Check to make sure DC is assigned (DC is now assigned at ingestion conformation page)
if($status_id == $request_tabled_tag->term_id || $status_id == $request_initial_review_complete_tag->term_id){

$get_dc_unassigned_boxes = $wpdb->get_results("SELECT a.storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b ON b.id = a.storage_location_id
WHERE b.digitization_center = '" . $dc_not_assigned_tag->term_id . "' AND a.ticket_id = '" . $ticket_id . "'");

$data_update = array('ticket_category' => $dc);
$data_where = array('id' => $ticket_id);
$wpdb->update($wpdb->prefix.'wpsc_ticket', $data_update, $data_where);

foreach ($get_dc_unassigned_boxes as $info) {
$storage_location_id = $info->storage_location_id;
$data_update = array('digitization_center' => $dc);
$data_where = array('id' => $storage_location_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_location', $data_update, $data_where);
}

}

//Double Check to make sure Aisle,Bay,Shelf,Position is Assigned
/*if($status_id == $request_initial_review_complete_tag->term_id){
$dc = Patt_Custom_Func::get_default_digitization_center($ticket_id);

$data_update = array('ticket_category' => $dc);
$data_where = array('id' => $ticket_id);
$wpdb->update($wpdb->prefix.'wpsc_ticket', $data_update, $data_where);

//include_once( WPPATT_ABSPATH . 'includes/admin/e_location_assignment_cleanup_cron.php' );
//include_once( WPPATT_ABSPATH . 'includes/admin/w_location_assignment_cleanup_cron.php' );
       
    echo '<div style="display:none">';
Patt_Custom_Func::auto_location_assignment($ticket_id,$get_dc_val,$destruction_flag,$destruction_boxes);
    echo '</div>';

}
*/
/*
if($status_id == $request_initial_review_complete_tag->term_id){
echo '<div style="display:none">';
Patt_Custom_Func::auto_location_assignment($ticket_id,$get_dc_val,$destruction_flag,$destruction_boxes);
echo '</div>';

}*/

//Double Check to make sure Aisle,Bay,Shelf,Position is cleared after rejection of request
/*if($status_id == $request_initial_review_rejected_tag->term_id){
    
$get_box_ids_count = $wpdb->get_row("SELECT count(a.id) as count
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b ON a.storage_location_id = b.id
WHERE b.aisle <> 0 AND b.bay <> 0 AND b.shelf <> 0 AND b.position <> 0 AND a.ticket_id = '".$ticket_id."'");

$get_shelf_id_arr = array();

if($get_box_ids_count->count > 0) {

$get_box_ids = $wpdb->get_results("SELECT a.id, a.storage_location_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b ON a.storage_location_id = b.id
WHERE b.aisle <> 0 AND b.bay <> 0 AND b.shelf <> 0 AND b.position <> 0 AND a.ticket_id = '".$ticket_id."'");

foreach($get_box_ids as $item) {
    $data_update_box_status = array('box_status' => $box_cancelled_tag->term_id);
    $data_where_box_status = array('id' => $item->id);
    $wpdb->update($wpdb->prefix . 'wpsc_epa_boxinfo', $data_update_box_status, $data_where_box_status);
    
// RESET Aisle/Bay/Shelf/Position Location

// GET SHELF ID
    $get_shelf_id = $wpdb->get_row("SELECT aisle, bay, shelf FROM " . $wpdb->prefix . "wpsc_epa_storage_location WHERE id = '" . $item->storage_location_id . "'");
    $shelf_id = $get_shelf_id->aisle.'_'.$get_shelf_id->bay.'_'.$get_shelf_id->shelf;

    array_push($get_shelf_id_arr, $shelf_id);
    
    
// RESET AISLE, BAY, SHELF, POSITION TO 0
    $data_update_storage_location = array('aisle' => 0,'bay' => 0,'shelf' => 0,'position' => 0);
    $data_where_storage_location = array('id' => $item->storage_location_id);
    $wpdb->update($wpdb->prefix . 'wpsc_epa_storage_location', $data_update_storage_location, $data_where_storage_location);
    
}

Patt_Custom_Func::update_remaining_occupied($dc,$get_shelf_id_arr);

//include_once( WPPATT_ABSPATH . 'includes/admin/e_location_assignment_cleanup_cron.php' );
//include_once( WPPATT_ABSPATH . 'includes/admin/w_location_assignment_cleanup_cron.php' );

}

}*/


if($is_active == 1) {
    $request_type = 'request';
    $type = 'box';
}
else {
    $request_type = 'request_archive';
    $type = 'box_archive';
}

$converted_to_request_id = Patt_Custom_Func::convert_request_db_id($ticket_id);
//unauthorized destruction notification
if(Patt_Custom_Func::id_in_unauthorized_destruction($converted_to_request_id, $request_type) == 1) {
?>
<div class="alert alert-danger" role="alert">
<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span> One or more documents related to this request contains a unauthorized destruction flag.
</div>
<?php
}
//damaged notification
if(Patt_Custom_Func::id_in_damaged($converted_to_request_id, $request_type) == 1) {
?>
<div class="alert alert-warning" role="alert">
<span style="font-size: 1em; color: #000000;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span> One or more documents related to this request contains a damaged folder/file.
</div>
<?php
}

$get_request_status_name = $wpdb->get_row("SELECT b.name
FROM " . $wpdb->prefix . "wpsc_ticket a
INNER JOIN " . $wpdb->prefix . "terms b ON b.term_id = a.ticket_status
WHERE a.id = " . $ticket_id);
$status_name = $get_request_status_name->name;

//editing disabled notification
if( in_array($status_id, $alert_arr) ) {
?>
<div class="alert alert-warning" role="alert">
<span style="font-size: 1em; color: #FFC300;"><i class="fas fa-exclamation-triangle" aria-hidden="true" title="Alert"></i><span class="sr-only">Alert</span></span> Editing is disabled and cannot be completed in the <?php echo $status_name; ?> status.
</div>

<div class="alert alert-warning" role="alert">
<span style="font-size: 1em; color: #FFC300;"><i class="fas fa-exclamation-triangle" aria-hidden="true" title="Alert"></i><span class="sr-only">Alert</span></span> Rejections are final.
</div>
<?php
}
?>

<?php
//freeze notification
if(Patt_Custom_Func::id_in_freeze($converted_to_request_id, $request_type) == 1) {
?>
<div class="alert alert-info" role="alert">
<span style="font-size: 1em; color: #005C7A;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span> One or more documents related to this request contains a frozen folder/file.
</div>
<?php
}
?>

<h4>Boxes Related to Request</h4>
<?php
//START REVIEW
if($is_active == 1) {
$box_details = $wpdb->get_results("SELECT 
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as id, 
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_previous_status as box_previous_status,
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as box_data_id,
" . $wpdb->prefix . "wpsc_epa_boxinfo.pallet_id as pallet_id,
(SELECT " . $wpdb->prefix . "terms.name FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "terms WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as status,
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_status as status_id,
" . $wpdb->prefix . "terms.name as status_name,
(SELECT sum(b.validation = 1) 
 FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b
WHERE b.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id) as val_sum,
(SELECT count(b.id) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b
 WHERE b.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id) as doc_total,
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_id as box_id, 
(SELECT " . $wpdb->prefix . "terms.name 
FROM " . $wpdb->prefix . "terms, " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as digitization_center,
(SELECT " . $wpdb->prefix . "terms.slug 
FROM " . $wpdb->prefix . "wpsc_epa_storage_location, " . $wpdb->prefix . "terms, " . $wpdb->prefix . "wpsc_epa_boxinfo 
WHERE " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center AND " . $wpdb->prefix . "wpsc_epa_storage_location.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id and " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as digitization_center_slug, 
" . $wpdb->prefix . "wpsc_epa_storage_location.aisle as aisle, 
" . $wpdb->prefix . "wpsc_epa_storage_location.bay as bay, 
" . $wpdb->prefix . "wpsc_epa_storage_location.shelf as shelf, 
" . $wpdb->prefix . "wpsc_epa_storage_location.position as position, 
" . $wpdb->prefix . "wpsc_epa_location_status.locations as physical_location
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_location_status ON " . $wpdb->prefix . "wpsc_epa_boxinfo.location_status_id = " . $wpdb->prefix . "wpsc_epa_location_status.id
INNER JOIN " . $wpdb->prefix . "terms ON " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status
WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = '" . $ticket_id . "'");
} else {
$box_details = $wpdb->get_results("SELECT 
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as id, 
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_previous_status as box_previous_status,
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as box_data_id,
" . $wpdb->prefix . "wpsc_epa_boxinfo.pallet_id as pallet_id,
(SELECT " . $wpdb->prefix . "terms.name FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "terms WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as status,
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_status as status_id,
" . $wpdb->prefix . "terms.name as status_name,
(SELECT sum(b.validation = 1) 
 FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b
WHERE b.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id) as val_sum,
(SELECT count(b.id) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b
 WHERE b.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id) as doc_total,
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_id as box_id, 
(SELECT " . $wpdb->prefix . "terms.name 
FROM " . $wpdb->prefix . "terms, " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as digitization_center,
(SELECT " . $wpdb->prefix . "terms.slug 
FROM " . $wpdb->prefix . "wpsc_epa_storage_location, " . $wpdb->prefix . "terms, " . $wpdb->prefix . "wpsc_epa_boxinfo 
WHERE " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center AND " . $wpdb->prefix . "wpsc_epa_storage_location.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id and " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as digitization_center_slug, 
" . $wpdb->prefix . "wpsc_epa_storage_location.aisle as aisle, 
" . $wpdb->prefix . "wpsc_epa_storage_location.bay as bay, 
" . $wpdb->prefix . "wpsc_epa_storage_location.shelf as shelf, 
" . $wpdb->prefix . "wpsc_epa_storage_location.position as position, 
" . $wpdb->prefix . "wpsc_epa_location_status.locations as physical_location
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_location_status ON " . $wpdb->prefix . "wpsc_epa_boxinfo.location_status_id = " . $wpdb->prefix . "wpsc_epa_location_status.id
INNER JOIN " . $wpdb->prefix . "terms ON " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status
WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = '" . $ticket_id . "'");
}
//END REVIEW
			$tbl = '
<div class="table-responsive" style="overflow-x:auto;">
	<table id="tbl_templates_request_details" class="text_highlight display nowrap" cellspacing="5" cellpadding="5" width="100%">
<thead>
  <tr>';
  
  
if ( !(in_array($status_id, $status_id_arr)) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet')) && $is_active == 1) {
$tbl .=  '<th class="datatable_header" id="selectall" scope="col"></th>';
}     
                    // PATT BEGIN
         $tbl .=   '<th class="datatable_header" scope="col">Box ID</th>
    	  			<th class="datatable_header" scope="col">Box Status</th>
    	  		    <th class="datatable_header" scope="col">Pallet</th>
    	  			<th class="datatable_header" scope="col">Physical Location</th>
    	  			<th class="datatable_header" scope="col">Assigned Location</th>
    	  			<th class="datatable_header" scope="col">Digitization Center</th>
    	  		    <th class="datatable_header" scope="col">Validation</th>
  </tr>
 </thead><tbody>
';
                    // PATT END
			foreach ($box_details as $info) {
			    $boxlist_dbid = $info->id;
			    $boxlist_id = $info->box_id;
			    
			    $boxlist_pallet_id = $info->pallet_id;
			    if ($boxlist_pallet_id == '') {
			        $boxlist_pallet_id = 'Unassigned';
			      } 
			    

			    $boxlist_status_id = $info->status_id;
			    $box_previous_status_id = $info->box_previous_status;
			    $status_background = get_term_meta($boxlist_status_id, 'wpsc_box_status_background_color', true);
	            $status_color = get_term_meta($boxlist_status_id, 'wpsc_box_status_color', true);
	            $status_style = "background-color:".$status_background.";color:".$status_color.";";
	            $boxlist_status_name = $info->status_name;


$get_term_name = $wpdb->get_row("SELECT name
FROM " . $wpdb->prefix . "terms WHERE term_id = ".$box_previous_status_id);
$box_previous_term_name = $get_term_name->name;

$show_previous_box_status_array = array($box_waiting_shelved_tag->term_id, $box_waiting_on_rlo_tag->term_id, $box_cancelled_tag->term_id);

if (in_array($boxlist_status_id, $show_previous_box_status_array) && $box_previous_status_id != 0) {
    $boxlist_status = "<a href='#' style='color: #000000 !important;' data-toggle='tooltip' data-placement='right' data-html='true' aria-label='Previous Box Status' title='Previous Box Status: ".$box_previous_term_name."'><span class='wpsp_admin_label' style='".$status_style."'>".$boxlist_status_name."</span></a>";
} else {
    $boxlist_status = "<span class='wpsp_admin_label' style='".$status_style."'>".$boxlist_status_name."</span>";
}
			    $boxlist_dc = $info->digitization_center;
			    $boxlist_dc_val = strtoupper($info->digitization_center_slug);
			    $boxlist_aisle = $info->aisle;
			    $boxlist_bay = $info->bay;
				$boxlist_shelf = $info->shelf;
				$boxlist_position = $info->position;
			    $boxlist_physical_location = $info->physical_location;
			    $boxlist_val_sum = $info->val_sum;
			    $boxlist_doc_total = $info->doc_total;
				if (($info->aisle == '0') || ($info->bay == '0') || ($info->shelf == '0') || ($info->position == '0')) {
				$boxlist_location = 'Currently Unassigned';
				} else {
                $boxlist_location = $info->aisle . 'A_' .$info->bay .'B_' . $info->shelf . 'S_' . $info->position .'P_'.$boxlist_dc_val;
                $boxlist_location = Patt_Custom_Func::convert_bay_letter($boxlist_location);
				}
				
				if ($info->digitization_center == '') {
				$boxlist_dc_location = 'Currently Unassigned';
				} else {
                $boxlist_dc_location = $info->digitization_center;
				}
				
				//switching out icons using functions
				//$boxlist_unathorized_destruction = $info->ud;
				//$boxlist_box_destroyed = $info->bd;
				//$boxlist_freeze_sum = $info->freeze_sum;
				
			/*	
			if($boxlist_unathorized_destruction > 0) {
			$tbl .= '<tr class="wpsc_tl_row_item" style="background-color: #e7c3c3;">';
			} else {
			$tbl .= '<tr class="wpsc_tl_row_item">';
			}
			*/
			$tbl .= '<tr class="wpsc_tl_row_item">';

 if (!(in_array($status_id, $status_id_arr)) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet')) && $is_active == 1) {
			$tbl .= '<td>'. $boxlist_id .'</td>';
}    

            if(Patt_Custom_Func::id_in_box_destroyed($boxlist_id,$type) == 1 && Patt_Custom_Func::id_in_freeze($boxlist_id,$type) != 1) {
                 $tbl .= '
            <td data-order="'.$boxlist_dbid.'"><a href="' . $subfolder_path . '/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=' . $boxlist_id . '" style="color:#B4081A !important; text-decoration: line-through;">' . $boxlist_id . '</a>';

            } else if (Patt_Custom_Func::id_in_box_destroyed($boxlist_id,$type) == 1 && Patt_Custom_Func::id_in_freeze($boxlist_id,$type) == 1){
                $tbl .= '
            <td data-order="'.$boxlist_dbid.'"><a href="' . $subfolder_path . '/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=' . $boxlist_id . '" style="color:#B4081A !important;">' . $boxlist_id . '</a>';
                
            } else {
                $tbl .= '
            <td data-order="'.$boxlist_dbid.'"><a href="' . $subfolder_path . '/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=' . $boxlist_id . '">' . $boxlist_id . '</a>';

            }
            
            if(Patt_Custom_Func::id_in_box_destroyed($boxlist_id, $type) == 1) {
            $tbl .= ' <span style="font-size: 1em; color: #B4081A;"><i class="fas fa-ban" aria-hidden="true" title="Box Destroyed"></i><span class="sr-only">Box Destroyed</span></span>';
            }

            if(Patt_Custom_Func::id_in_unauthorized_destruction($boxlist_id, $type) == 1) {
            $tbl .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span>';
            }

            if(Patt_Custom_Func::id_in_damaged($boxlist_id, $type) == 1) {
            $tbl .= ' <span style="font-size: 1em; color: #000000;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span>';
            }

            if(Patt_Custom_Func::id_in_freeze($boxlist_id, $type) == 1) {
                $tbl .= ' <span style="font-size: 1em; color: #005C7A;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span>';
            }

if(Patt_Custom_Func::id_in_return($boxlist_id,$type) == 1){
$tbl .= '<span style="font-size: 1em; color: #B4081A;margin-left:4px;"><i class="fas fa-undo" aria-hidden="true" title="Declined"></i><span class="sr-only">Declined</span></span>';
}

if(Patt_Custom_Func::id_in_recall($boxlist_id,$type) == 1){
$tbl .= '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" aria-hidden="true" title="Recall"></i><span class="sr-only">Recall</span></span>';
}

if(Patt_Custom_Func::display_box_user_icon($boxlist_dbid) == 1){
$tbl .= '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents(\''. $boxlist_id .'\')" class="assign_agents_icon"><i class="fas fa-user-friends" aria-hidden="true" title="Assigned Agents"></i><span class="sr-only">Assigned Agents</span></span>';
}

            $tbl .= '</td>';
            $tbl .= '<td>' . $boxlist_status . '</td>'; 
            $tbl .= '<td>'. $boxlist_pallet_id .'</td>';
            
            
            $physical_location_id = Patt_Custom_Func::id_in_physical_location($boxlist_id, $type);
            //$physical_location_id = Patt_Custom_Func::convert_bay_letter($physical_location_id);
            //show physical location ID if it exists in the scan_list table
            if($physical_location_id != '') {
              
              if (preg_match('/^\d{1,3}A_\d{1,3}B_\d{1,3}S_[1-3]{1}P_(E|W|ECUI|WCUI)$/i', $physical_location_id)) {
                $physical_location_id =  Patt_Custom_Func::convert_bay_letter($physical_location_id);
              }
              
                $tbl .= '<td> <a href="#" style="color: #000000 !important;" data-toggle="tooltip" data-placement="left" data-html="true" aria-label="Name" title="" data-original-title="'.$physical_location_id.'">'.$boxlist_physical_location.'</a>
                <span style="display: none;" aria-label="'.$boxlist_physical_location.'"></span></td>';
            }
            else {
                $tbl .= '<td>'. $boxlist_physical_location .'</td>';
            }
            
			//if (!(in_array($status_id, $status_id_arr)) && ($boxlist_unathorized_destruction == 0)&&($boxlist_box_destroyed == 0)&&($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
            //Can edit location if box isn't destroyed and is in the correct request status
            if (!(in_array($status_id, $status_id_tabled_arr)) && ($boxlist_box_destroyed == 0)&&($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
            {
				if ($boxlist_dc_location != 'Not Unassigned') {
					$tbl .= '<td>' . $boxlist_location;
				}
				if ($boxlist_dc_location != 'Not Assigned') {
					$tbl .= ' <a href="#" onclick="wpsc_get_inventory_editor(' . $boxlist_dbid . ')"><i class="fas fa-edit" aria-hidden="true" title="Edit Assigned Shelf Location"></i><span class="sr-only">Edit Assigned Shelf Location</span></a>';
				}	
				$tbl .= '</td>';

				$tbl .= '<td>' . $boxlist_dc_location;
				if($boxlist_dc_location != 'Not Unassigned') {
					$tbl .= ' <a href="#" onclick="wpsc_get_digitization_editor_final(' . $boxlist_dbid . ')"><i class="fas fa-exchange-alt" aria-hidden="true" title="Edit Digitization Center"></i><span class="sr-only">Edit Digitization Center</span></a></td>';
				}
				$tbl .= '</td>';
		
			} elseif ($boxlist_dc_location == 'Not Unassigned') {
				$tbl .= '<td>' . $boxlist_location . '</td>';   
				$tbl .= '<td>' . $boxlist_dc_location . '</td>';
			} else {
				$tbl .= '<td>' . $boxlist_location . '</td>';   
				$tbl .= '<td>' . $boxlist_dc_location . '</td>';
			}
              
            
            if($boxlist_doc_total == 0){
            $tbl .= '<td data-sort="4">-</td>';
            } else if($boxlist_val_sum != 0 && $boxlist_val_sum < $boxlist_doc_total){
            $tbl .= '<td data-sort="1"><span style="font-size: 1.3em; color: #b55000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span> ' . $boxlist_val_sum . '/' . $boxlist_doc_total . '</td>';
            } else if($boxlist_val_sum == 0 && $boxlist_val_sum < $boxlist_doc_total){
            $tbl .= '<td data-sort="2"><span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span> ' . $boxlist_val_sum . '/' . $boxlist_doc_total . '</td>';
            } else if($boxlist_val_sum == $boxlist_doc_total){
            $tbl .= '<td data-sort="3"><span style="font-size: 1.3em; color: #008000;"><i class="fas fa-check-circle" aria-hidden="true" title="Validated"></i><span class="sr-only">Validated</span></span> ' . $boxlist_val_sum . '/' . $boxlist_doc_total . '</td>';
            }

            
            $tbl .= '</tr>';

			}
			$tbl .= '</tbody></table></div>';

			echo $tbl;
            
?>			

<?php
//Rescan
$ticket_data = $wpscfunction->get_ticket($ticket_id);
$status_id   	= $ticket_data['ticket_status'];

$get_request_id = $wpdb->get_row("SELECT request_id FROM " . $wpdb->prefix . "wpsc_ticket WHERE id = '".$ticket_id."'");
$get_request_id_val = $get_request_id->request_id;

$get_rescan_total = $wpdb->get_row("SELECT count(id) as totalcount FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id LIKE '".$get_request_id_val."%'");
$get_rescan_total_val = $get_rescan_total->totalcount;

$get_rescan_count = $wpdb->get_row("SELECT count(id) as count FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE rescan = 0 AND folderdocinfofile_id LIKE '".$get_request_id_val."%'");
$get_rescan_count_val = $get_rescan_count->count;

//This is most likely a holdover from when all statuses were at the request level
// if ($status_id == $rescan_tag->term_id && ($get_rescan_total_val == $get_rescan_count_val)) {
// $wpscfunction->change_status($ticket_id, $validation_tag->term_id);  
// }

// if ($status_id == $validation_tag->term_id && ($get_rescan_total_val != $get_rescan_count_val)) {
// $wpscfunction->change_status($ticket_id, $rescan_tag->term_id);   
// }

//REVIEW
$box_rescan = $wpdb->get_row("SELECT count(b.id) as count
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
WHERE b.rescan = 1 AND a.ticket_id = '" . $ticket_id . "'");
$rescan_count = $box_rescan->count;


//DISPAY IF NOT ARCHIVED
if($is_active == 1){
if($rescan_count > 0) {
?>
</br></br>
<div class="alert alert-danger" role="alert">
<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Re-Scan"></i><span class="sr-only">Re-Scan</span></span> The following folder(s)/file(s) require re-scanning
</div>
		 <?php
		  if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) {
        ?>
<button type="button" class="button" id="wppatt_undo_rescan_btn"><i class="fas fa-times-circle" aria-hidden="true" title="Undo Re-Scan"></i><span class="sr-only">Undo Re-Scan</span> Undo Re-Scan </button><br /><br />
        <?php 
            }
        ?>

<div class="table-responsive" style="overflow-x:auto;">
<table id="tbl_templates_rescan" class="text_highlight display nowrap" cellspacing="5" cellpadding="5" width="100%">
<thead>
<tr>
        <?php
		  if (  ($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') ) {
        ?>
        
    <!-- PATT BEGIN -->
    <th class="datatable_header" id="selectall_2" scope="col"></th>
    <?php } ?>
    <th class="datatable_header" scope="col">Box ID</th>
    <th class="datatable_header" scope="col">Folder/File ID</th>
    <th class="datatable_header" scope="col">Title</th>
    
    <!-- PATT END -->
</tr>
</thead>
<tbody>
<?php
//REVIEW
$rescan_details = $wpdb->get_results("SELECT a.box_id as box_id, b.title as title, b.folderdocinfofile_id as folderdocinfo_id , b.id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
WHERE b.rescan = 1 AND a.ticket_id = '" . $ticket_id . "'");

foreach ($rescan_details as $info) {

$tbl = '<tr>';
if ( ($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') ) {
$tbl .= '<td>'.$info->folderdocinfo_id.'</td>';
}
$tbl .= '<td data-order="'.$info->id.'"><a href="' . $subfolder_path . '/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=' . $info->box_id . '">'.$info->box_id.'</a></td>';
$tbl .= '<td><a href="' . $subfolder_path . '/wp-admin/admin.php?page=filedetails&pid=requestdetails&id=' . $info->folderdocinfo_id . '">'.$info->folderdocinfo_id.'</a></td>';
$tbl .= '<td>'.$info->title.'</td>';
$tbl .= '</tr>';

echo $tbl;

}
?>
</tbody>
</table>
</div>
<?php
}
// END DISPLAY IF NOT ARCHIVED
}
?>
<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.3/jquery.tagsinput.css" crossorigin="anonymous">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.3/jquery.tagsinput.js" crossorigin="anonymous"></script>

<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>

<script>
 jQuery(document).ready(function() {

<?php 
//DISPAY IF NOT ARCHIVED
if($rescan_count > 0 && $is_active == 1) {
?>
     	 var rescan = jQuery('#tbl_templates_rescan').DataTable({
	     "autoWidth": true,
	     //"scrollX" : true,
	     "initComplete": function (settings, json) {  
    jQuery("#tbl_templates_rescan").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
    jQuery('#selectall_2').append('<span class="sr-only">Select All</span>');
  },
         "paging" : true,
         "bDestroy": true,
		 "aLengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
		<?php
		  if ( (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) ) {
        ?>
		 'columnDefs': [
		    {
            'width': '5px',
            'targets': 0,
            'title': 'Select All Checkbox',
            'checkboxes': {	
               'selectRow': true	
            },
         },
            { 'width' : '10%', targets: 1 },
            { 'width' : '10%', targets: 2 },
            { 'width' : '80%', targets: 3 }
      ],
      
        'select': {	
         'style': 'multi'	
      },	
      'order': [[1, 'asc']],
      <?php } ?>
		});
<?php 
//END DISPAY IF NOT ARCHIVED
}
?>     
	 var dataTable = jQuery('#tbl_templates_request_details').DataTable({
	     "autoWidth": true,
	     //"scrollX" : true,
	     "initComplete": function (settings, json) {  
    jQuery("#tbl_templates_request_details").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");         
    jQuery('#selectall').append('<span class="sr-only">Select All</span>');
  },
         "paging" : true,
         "bDestroy": true,
		 "aLengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
		 <?php
		  if (!(in_array($status_id, $status_id_arr)) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet')) && $is_active == 1) {
        ?>
        'columnDefs': [	
         {
            'width': '5px',
            'targets': 0,
            'title': 'Select All Checkbox',
            'checkboxes': {	
               'selectRow': true	
            },
         },

            { 'width' : '3%', targets: 1 },
            { 'width' : '85%', targets: 2 },
            { 'width' : '3%', targets: 3 },
            { 'width' : '3%', targets: 4 },
            { 'width' : '3%', targets: 5 },
            { 'width' : '3%', targets: 6 },
            { 'width' : '3%', targets: 7 }
      ],
      'select': {	
         'style': 'multi'	
      },	
      'order': [[1, 'asc']],
      <?php } ?>
		});
		
	// Code block for toggling edit buttons on/off when checkboxes are set
	jQuery('#tbl_templates_request_details tbody').on('click', 'input', function () {        
	// 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
<?php 
//DISPAY IF NOT ARCHIVED
if($rescan_count > 0 && $is_active == 1) {
?>
	jQuery('#tbl_templates_rescan tbody').on('click', 'input', function () {        
	// 	console.log('checked');
		setTimeout(toggle_rescan_button_display, 1); //delay otherwise 
	});
	
<?php 
//END DISPAY IF NOT ARCHIVED
}
?>  
	jQuery('.dt-checkboxes-select-all').on('click', 'input', function () {        
	 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
<?php 
//DISPAY IF NOT ARCHIVED
if($rescan_count > 0 && $is_active == 1) {
?>
		setTimeout(toggle_rescan_button_display, 1); //delay otherwise 
<?php 
//END DISPAY IF NOT ARCHIVED
}
?> 
	});
	
	jQuery('#wppatt_change_status_btn').attr('disabled', 'disabled');
	jQuery('#wppatt_change_pallet_btn').attr('disabled', 'disabled');
	jQuery('#wppatt_assign_staff_btn').attr('disabled', 'disabled');
	jQuery('#wppatt_undo_rescan_btn').attr('disabled', 'disabled');	
	
	function toggle_button_display() {
	//	var form = this;
		var rows_selected = dataTable.column(0).checkboxes.selected();
		if(rows_selected.count() > 0) {
			jQuery('#wppatt_change_status_btn').removeAttr('disabled');
			jQuery('#wppatt_change_pallet_btn').removeAttr('disabled');
			jQuery('#wppatt_assign_staff_btn').removeAttr('disabled');
	  	} else {
	    	jQuery('#wppatt_change_status_btn').attr('disabled', 'disabled');  
	    	jQuery('#wppatt_change_pallet_btn').attr('disabled', 'disabled');  
	    	jQuery('#wppatt_assign_staff_btn').attr('disabled', 'disabled');
	  	}
	}

<?php 
//DISPAY IF NOT ARCHIVED
if($is_active == 1 && $rescan_count > 0){
?>	
	function toggle_rescan_button_display() {
	//	var form = this;
		var rows_selected = rescan.column(0).checkboxes.selected();
		if(rows_selected.count() > 0) {
			jQuery('#wppatt_undo_rescan_btn').removeAttr('disabled');
	  	} else {
	    	jQuery('#wppatt_undo_rescan_btn').attr('disabled', 'disabled');
	  	}
	}
<?php 
//END DISPAY IF NOT ARCHIVED
}
?>  	
	jQuery('[data-toggle="tooltip"]').tooltip(); 
	
	// Assign Box Status Button Click
	jQuery('#wppatt_change_status_btn').click( function() {	
	
		let rows_selected = dataTable.column(0).checkboxes.selected();
	    let arr = [];
	
	    // Loop through array
	    [].forEach.call(rows_selected, function(inst){
	        console.log('the inst: '+inst);
	        arr.push(inst);
	    });
		
		wpsc_modal_open('Edit Box Status');
		
		var data = {
		    action: 'wppatt_change_box_status',
		    item_ids: arr,
		    type: 'edit',
		    //only refresh on the request details page
		    page: 'requestdetails'
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
	// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
		
	});
	
	// Assign Pallet Button Click
	jQuery('#wppatt_change_pallet_btn').click( function() {	
	
		let rows_selected = dataTable.column(0).checkboxes.selected();
	    let arr = [];
	
	    // Loop through array
	    [].forEach.call(rows_selected, function(inst){
	        console.log('the inst: '+inst);
	        arr.push(inst);
	    });
		
		wpsc_modal_open('Pallet Assignment');
		
		var data = {
		    action: 'wppatt_set_pallet_assignment',
		    ticket_id: <?php echo $ticket_id; ?>,
		    item_ids: arr
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
	// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
	});
	

	jQuery('#wppatt_undo_rescan_btn').click( function() {	
	
	var rows_selected = rescan.column(0).checkboxes.selected();
	
    jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_rescan_request_details.php',{
    postvarselection : rows_selected.join(","),
    postvarsticketid : <?php echo $ticket_id; ?>
    },
    function (response) {
        //alert(response);
        
        wpsc_modal_open('Re-scan');
		  var data = {
		    action: 'wpsc_get_rescan_ff',
		    response_data: response
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
		  
    });

    rescan.column(0).checkboxes.deselectAll();
    wpsc_open_ticket(<?php echo htmlentities($ticket_id)?>);
	});
	
	// Assign Staff Button Click
	jQuery('#wppatt_assign_staff_btn').click( function() {	
	
		var rows_selected = dataTable.column(0).checkboxes.selected();
	    var arr = [];
	
	    // Loop through array
	    [].forEach.call(rows_selected, function(inst){
	        console.log('the inst: '+inst);
	        arr.push(inst);
	    });
	    
	    console.log('arr: '+arr);
	    console.log(arr);
		
		wpsc_modal_open('Edit Assigned Staff');
		
		var data = {
		    action: 'wppatt_assign_agents',
		    ticket_id: <?php echo $ticket_id; ?>,
		    item_ids: arr,
		    page: 'request',
		    type: 'edit'
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
	// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		    
		}); 

	});

});

		function wpsc_get_digitization_editor_final(box_id){
		  wpsc_modal_open('Re-assign Digitization Center');
		  var data = {
		    action: 'wpsc_get_digitization_editor_final',
		    box_id: box_id
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });  
		}
   
   		function wpsc_get_inventory_editor(box_id){
		  wpsc_modal_open('Assigned Location Editor');
		  var data = {
		    action: 'wpsc_get_inventory_editor',
		    box_id: box_id
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });  
		}
	
// Open Modal for viewing assigned staff
function view_assigned_agents( box_id ) {	
	
	console.log('Icon!');
    var arr = [box_id];
    
    console.log('arr: '+arr);
    console.log(arr);
	
	wpsc_modal_open('View Assigned Staff');
	
	var data = {
	    action: 'wppatt_assign_agents',
	    item_ids: arr,
	    type: 'view'
	};
	jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
	    var response = JSON.parse(response_str);
// 		    jQuery('#wpsc_popup_body').html(response_str);		    
	    jQuery('#wpsc_popup_body').html(response.body);
	    jQuery('#wpsc_popup_footer').html(response.footer);
	    jQuery('#wpsc_cat_name').focus();
	}); 

}		
	</script>