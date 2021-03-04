<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$subfolder_path = site_url( '', 'relative'); 

global $wpdb, $current_user, $wpscfunction;
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
//unauthorized destruction notification
/*
$box_details = $wpdb->get_row(
"SELECT count(" . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.id) as count
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo ON " . $wpdb->prefix . "wpsc_epa_boxinfo.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files ON " . $wpdb->prefix . "wpsc_epa_folderdocinfo.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.folderdocinfo_id
WHERE " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.unauthorized_destruction = 1 AND " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = '" . $ticket_id . "'"
			);

$unauthorized_destruction_count = $box_details->count;
*/

if($is_active == 1) {
    $request_type = 'request';
    $type = 'box';
}
else {
    $request_type = 'request_archive';
    $type = 'box_archive';
}

$converted_to_request_id = Patt_Custom_Func::convert_request_db_id($ticket_id);
//if($unauthorized_destruction_count > 0){
if(Patt_Custom_Func::id_in_unauthorized_destruction($converted_to_request_id, $request_type) == 1) {
?>
<div class="alert alert-danger" role="alert">
<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" title="Unauthorized Destruction"></i></span> One or more documents related to this request contains a unauthorized destruction flag.
</div>
<?php
}
?>

<?php
//freeze notification
/*
$box_freeze = $wpdb->get_row("SELECT count(" . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.id) as count
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo ON " . $wpdb->prefix . "wpsc_epa_boxinfo.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files ON " . $wpdb->prefix . "wpsc_epa_folderdocinfo.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.folderdocinfo_id
WHERE " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.freeze = 1 AND " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = '" . $ticket_id . "'");
$freeze_count = $box_freeze->count;
*/

//if($freeze_count > 0) {
if(Patt_Custom_Func::id_in_freeze($converted_to_request_id, $request_type) == 1) {
?>
<div class="alert alert-info" role="alert">
<span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" title="Freeze"></i></span> One or more documents related to this request contains a frozen folder/file.
</div>
<?php
}
?>

<?php 
$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses');
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses');
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses');

//$status_id_arr = array('3','670','69');
$status_id_arr = array($new_request_tag->term_id, $initial_review_rejected_tag->term_id, $cancelled_tag->term_id);
?>

<h4>Boxes Related to Request</h4>
<?php
if($is_active == 1) {
$box_details = $wpdb->get_results("SELECT 
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as id, 
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as box_data_id,
" . $wpdb->prefix . "wpsc_epa_boxinfo.pallet_id as pallet_id,
(SELECT " . $wpdb->prefix . "terms.name FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "terms WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as status,
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_status as status_id,
" . $wpdb->prefix . "terms.name as status_name,
(SELECT sum(b.validation = 1) FROM 
" . $wpdb->prefix . "wpsc_epa_folderdocinfo a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON a.id = b.folderdocinfo_id 
WHERE a.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id) as val_sum,
(SELECT count(b.id) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo a 
 INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON a.id = b.folderdocinfo_id
 WHERE a.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id) as doc_total,
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
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as box_data_id,
(SELECT " . $wpdb->prefix . "terms.name FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "terms WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as status,
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_status as status_id,
" . $wpdb->prefix . "terms.name as status_name,
(SELECT sum(b.validation = 1) FROM 
" . $wpdb->prefix . "wpsc_epa_folderdocinfo_archive a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON a.id = b.folderdocinfo_id 
WHERE a.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id) as val_sum,
(SELECT count(b.id) 
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_archive a 
 INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON a.id = b.folderdocinfo_id
 WHERE a.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id) as doc_total,
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
			$tbl = '
<div class="table-responsive" style="overflow-x:auto;">
	<table id="tbl_templates_request_details" class="display nowrap" cellspacing="5" cellpadding="5" width="100%">
<thead>
  <tr>';
  
  
if ( !(in_array($status_id, $status_id_arr)) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1) {
$tbl .=  '<th class="datatable_header"></th>';
}     
                    
         $tbl .=   '<th class="datatable_header">Box ID</th>
    	  			<th class="datatable_header">Box Status</th>
    	  		    <th class="datatable_header">Pallet</th>
    	  			<th class="datatable_header">Physical Location</th>
    	  			<th class="datatable_header">Assigned Location</th>
    	  			<th class="datatable_header">Digitization Center</th>
    	  		    <th class="datatable_header">Validation</th>
  </tr>
 </thead><tbody>
';

			foreach ($box_details as $info) {
			    $boxlist_dbid = $info->id;
			    $boxlist_id = $info->box_id;
			    
			    $boxlist_pallet_id = $info->pallet_id;
			    if ($boxlist_pallet_id == '') {
			        $boxlist_pallet_id = 'Unassigned';
			      } 
			    

			    $boxlist_status_id = $info->status_id;
			    $status_background = get_term_meta($boxlist_status_id, 'wpsc_box_status_background_color', true);
	            $status_color = get_term_meta($boxlist_status_id, 'wpsc_box_status_color', true);
	            $status_style = "background-color:".$status_background.";color:".$status_color.";";
	            $boxlist_status_name = $info->status_name;
	            $boxlist_status = "<span class='wpsp_admin_label' style='".$status_style."'>".$boxlist_status_name."</span>";
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

 if (!(in_array($status_id, $status_id_arr)) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1) {
			$tbl .= '<td>'. $boxlist_id .'</td>';
}    
            
            //if($boxlist_box_destroyed > 0 && $boxlist_freeze_sum == 0) {
            if(Patt_Custom_Func::id_in_box_destroyed($boxlist_id,$type) == 1 && Patt_Custom_Func::id_in_freeze($boxlist_id,$type) != 1) {
                 $tbl .= '
            <td data-order="'.$boxlist_dbid.'"><a href="' . $subfolder_path . '/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=' . $boxlist_id . '" style="color:#FF0000 !important; text-decoration: line-through;">' . $boxlist_id . '</a>';

            } else if (Patt_Custom_Func::id_in_box_destroyed($boxlist_id,$type) == 1 && Patt_Custom_Func::id_in_freeze($boxlist_id,$type) == 1){
                $tbl .= '
            <td data-order="'.$boxlist_dbid.'"><a href="' . $subfolder_path . '/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=' . $boxlist_id . '" style="color:#FF0000 !important;">' . $boxlist_id . '</a>';
                
            } else {
                $tbl .= '
            <td data-order="'.$boxlist_dbid.'"><a href="' . $subfolder_path . '/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=' . $boxlist_id . '">' . $boxlist_id . '</a>';

            }
            
            //if($boxlist_box_destroyed > 0) {
            if(Patt_Custom_Func::id_in_box_destroyed($boxlist_id, $type) == 1) {
            $tbl .= ' <span style="font-size: 1em; color: #FF0000;"><i class="fas fa-ban" title="Box Destroyed"></i></span>';
            }
            
            //if($boxlist_freeze_sum > 0) {
             if(Patt_Custom_Func::id_in_freeze($boxlist_id, $type) == 1) {
                $tbl .= ' <span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" title="Freeze"></i></span>';
            }
            
            //if($boxlist_unathorized_destruction > 0) {
            if(Patt_Custom_Func::id_in_unauthorized_destruction($boxlist_id, $type) == 1) {
            $tbl .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" title="Unauthorized Destruction"></i></span>';
            }
            

$decline_icon = '';
$recall_icon = '';

if(Patt_Custom_Func::id_in_return($boxlist_id,$type) == 1){
$tbl .= ' <span style="font-size: 1em; color: #FF0000;margin-left:4px;"><i class="fas fa-undo" title="Declined"></i></span>';
}

if(Patt_Custom_Func::id_in_recall($boxlist_id,$type) == 1){
$tbl .= ' <span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" title="Recall"></i></span>';
}

            $tbl .= '<span style="font-size: 1.0em; color: #1d1f1d;margin-left:4px;" onclick="view_assigned_agents(\''. $boxlist_id .'\')" class="assign_agents_icon"><i class="fas fa-user-friends" title="Assigned Agents"></i></span></td>';
            $tbl .= '<td>' . $boxlist_status . '</td>'; 
            $tbl .= '<td>'. $boxlist_pallet_id .'</td>';
            
            
            $physical_location_id = Patt_Custom_Func::id_in_physical_location($boxlist_id, $type);
            
            //show physical location ID if it exists in the scan_list table
            if($physical_location_id != '') {
                $tbl .= '<td> <a href="#" style="color: #000000 !important;" data-toggle="tooltip" data-placement="left" data-html="true" aria-label="Name" title="" data-original-title="'.$physical_location_id.'">'.$boxlist_physical_location.'</a>
                <span style="display: none;" aria-label="'.$boxlist_physical_location.'"></span></td>';
            }
            else {
                $tbl .= '<td>'. $boxlist_physical_location .'</td>';
            }
            
			//if (!(in_array($status_id, $status_id_arr)) && ($boxlist_unathorized_destruction == 0)&&($boxlist_box_destroyed == 0)&&($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
            //Can edit location if box isn't destroyed and is in the correct request status
            if (!(in_array($status_id, $status_id_arr)) && ($boxlist_box_destroyed == 0)&&($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
            {
            if ($boxlist_location != 'Currently Unassigned' || $boxlist_dc_location != 'Not Unassigned') {
            $tbl .= '<td>' . $boxlist_location;
            if ($boxlist_dc_location != 'Not Assigned') {
            $tbl .= ' <a href="#" onclick="wpsc_get_inventory_editor(' . $boxlist_dbid . ')"><i class="fas fa-edit"></i></a>';
            }
            $tbl .= '</td>';
            
            $tbl .= '<td>' . $boxlist_dc_location . ' <a href="#" onclick="wpsc_get_digitization_editor_final(' . $boxlist_dbid . ')"><i class="fas fa-exchange-alt"></i></a></td>';
            
            } elseif ($boxlist_location == 'Currently Unassigned' && $boxlist_dc_location == 'Currently Unassigned') {
            $tbl .= '<td>' . $boxlist_location . '</td>';   
            $tbl .= '<td>' . $boxlist_dc_location . '</td>';
            }
            } else {
            $tbl .= '<td>' . $boxlist_location . '</td>';   
            $tbl .= '<td>' . $boxlist_dc_location . '</td>';
            }
            
            if($boxlist_doc_total == 0){
            $tbl .= '<td data-sort="4">-</td>';
            } else if($boxlist_val_sum != 0 && $boxlist_val_sum < $boxlist_doc_total){
            $tbl .= '<td data-sort="1"><span style="font-size: 1.3em; color: #FF8C00;"><i class="fas fa-times-circle" title="Not Validated"></i></span> ' . $boxlist_val_sum . '/' . $boxlist_doc_total . '</td>';
            } else if($boxlist_val_sum == 0 && $boxlist_val_sum < $boxlist_doc_total){
            $tbl .= '<td data-sort="2"><span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" title="Not Validated"></i></span> ' . $boxlist_val_sum . '/' . $boxlist_doc_total . '</td>';
            } else if($boxlist_val_sum == $boxlist_doc_total){
            $tbl .= '<td data-sort="3"><span style="font-size: 1.3em; color: #008000;"><i class="fas fa-check-circle" title="Validated"></i></span> ' . $boxlist_val_sum . '/' . $boxlist_doc_total . '</td>';
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

$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses');
$rescan_tag = get_term_by('slug', 're-scan', 'wpsc_box_statuses');

//if ($status_id == 743 && ($get_rescan_total_val == $get_rescan_count_val)) {
if ($status_id == $rescan_tag->term_id && ($get_rescan_total_val == $get_rescan_count_val)) {
//$wpscfunction->change_status($ticket_id, 674);
$wpscfunction->change_status($ticket_id, $validation_tag->term_id);  
}

//if ($status_id == 674 && ($get_rescan_total_val != $get_rescan_count_val)) {
if ($status_id == $validation_tag->term_id && ($get_rescan_total_val != $get_rescan_count_val)) {
//$wpscfunction->change_status($ticket_id, 743);
$wpscfunction->change_status($ticket_id, $rescan_tag->term_id);   
}

$box_rescan = $wpdb->get_row("SELECT count(c.id) as count
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo b ON b.box_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON b.id = c.folderdocinfo_id
WHERE c.rescan = 1 AND a.ticket_id = '" . $ticket_id . "'");
$rescan_count = $box_rescan->count;


//DISPAY IF NOT ARCHIVED
if($is_active == 1){
if($rescan_count > 0) {
?>
</br></br>
<div class="alert alert-danger" role="alert">
<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" title="Re-Scan"></i></span> <strong>The following folder(s)/file(s) require re-scanning</strong>
</div>
		 <?php
		  if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) {
        ?>
<button type="button" class="button" id="wppatt_undo_rescan_btn"><i class="fas fa-times-circle"></i> Undo Re-Scan </button><br /><br />
        <?php 
            }
        ?>

<div class="table-responsive" style="overflow-x:auto;">
<table id="tbl_templates_rescan" class="display nowrap" cellspacing="5" cellpadding="5" width="100%">
<thead>
<tr>
        <?php
		  if (  ($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') ) {
        ?>
    <th class="datatable_header"></th>
    <?php } ?>
    <th class="datatable_header">Box ID</th>
    <th class="datatable_header">Folder/File ID</th>
    <th class="datatable_header">Title</th>
</tr>
</thead>
<tbody>
<?php
$rescan_details = $wpdb->get_results("SELECT a.box_id as box_id, c.title as title, c.folderdocinfofile_id as folderdocinfo_id , c.id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo b ON b.box_id = a.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.folderdocinfo_id = b.id
WHERE c.rescan = 1 AND a.ticket_id = '" . $ticket_id . "'");

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
     	 var rescan = jQuery('#tbl_templates_rescan').DataTable({
	     "autoWidth": true,
	     //"scrollX" : true,
	     "initComplete": function (settings, json) {  
    jQuery("#tbl_templates_request_details").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");            
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
     
	 var dataTable = jQuery('#tbl_templates_request_details').DataTable({
	     "autoWidth": true,
	     //"scrollX" : true,
	     "initComplete": function (settings, json) {  
    jQuery("#tbl_templates_request_details").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");            
  },
         "paging" : true,
         "bDestroy": true,
		 "aLengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
		 <?php
		  if (!(in_array($status_id, $status_id_arr)) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1) {
        ?>
        'columnDefs': [	
         {
            'width': '5px',
            'targets': 0,	
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

	jQuery('#tbl_templates_rescan tbody').on('click', 'input', function () {        
	// 	console.log('checked');
		setTimeout(toggle_rescan_button_display, 1); //delay otherwise 
	});
	
	jQuery('.dt-checkboxes-select-all').on('click', 'input', function () {        
	 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
		setTimeout(toggle_rescan_button_display, 1); //delay otherwise 
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
	
	function toggle_rescan_button_display() {
	//	var form = this;
		var rows_selected = rescan.column(0).checkboxes.selected();
		if(rows_selected.count() > 0) {
			jQuery('#wppatt_undo_rescan_btn').removeAttr('disabled');
	  	} else {
	    	jQuery('#wppatt_undo_rescan_btn').attr('disabled', 'disabled');
	  	}
	}
	
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
		    item_ids: arr,
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
// });
}		
	</script>