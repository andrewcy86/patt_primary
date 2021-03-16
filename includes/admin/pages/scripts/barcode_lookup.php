<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

//Define tables
$table_box = $wpdb->prefix . "wpsc_epa_boxinfo";
$table_scan_list = $wpdb->prefix . "wpsc_epa_scan_list";

if(isset($_POST['postvarsbarcode'])){
   $barcode = $_POST['postvarsbarcode'];

//Determine if string is URL
    if(filter_var($barcode, FILTER_VALIDATE_URL))
    {
parse_str(parse_url($barcode)['query'], $params);

//echo $params['id'];

//echo '<strong>Request ID: '.$params['id'].'</strong>';

$request_info = $wpdb->get_row(
				"SELECT " . $wpdb->prefix . "wpsc_ticket.id as id, " . $wpdb->prefix . "wpsc_ticket.active as active, " . $wpdb->prefix . "wpsc_ticket.customer_name as customer_name, " . $wpdb->prefix . "wpsc_ticket.customer_email as customer_email, status.name as ticket_status, status.term_id as ticket_status_id, " . $wpdb->prefix . "terms.name as ticket_location, priority.name as ticket_priority, priority.term_id as ticket_priority_id, " . $wpdb->prefix . "wpsc_ticket.date_created as date_created
FROM " . $wpdb->prefix . "wpsc_ticket

    INNER JOIN " . $wpdb->prefix . "terms AS status ON (
        " . $wpdb->prefix . "wpsc_ticket.ticket_status = status.term_id
    )
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo ON " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = " . $wpdb->prefix . "wpsc_ticket.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_location_status ON " . $wpdb->prefix . "wpsc_epa_boxinfo.location_status_id = " . $wpdb->prefix . "wpsc_epa_location_status.id
INNER JOIN " . $wpdb->prefix . "terms ON  " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center

    INNER JOIN " . $wpdb->prefix . "terms AS priority ON (
        " . $wpdb->prefix . "wpsc_ticket.ticket_priority = priority.term_id
    )  

WHERE " . $wpdb->prefix . "wpsc_ticket.request_id = " . $params['id']
			);
if(!empty($request_info->id) && $request_info->active == 1) {
			$request_url = admin_url( 'admin.php?page=wpsc-tickets', 'https' );
			$status_color = get_term_meta($request_info->ticket_status_id,'wpsc_status_background_color',true);
            $priority_color = get_term_meta($request_info->ticket_priority_id,'wpsc_priority_background_color',true);
			echo "<strong>Request ID:</strong> <a href='".$request_url."&id=".$params['id']."' target='_blank'>" . $params['id'] . "</a><br />";
			echo "<strong>Requestor Name:</strong> " . $request_info->customer_name . "<br />";
			echo "<strong>Requestor Email:</strong> " . $request_info->customer_email . "<br />";
			echo "<strong>Request Status:</strong> " . $request_info->ticket_status . "  <span style='color: ".$status_color." ;margin: 0px;'><i class='fas fa-circle'></i></span>
<br />";
			echo "<strong>Priority:</strong> " . $request_info->ticket_priority . "  <span style='color: ".$priority_color." ;margin: 0px;'><i class='fas fa-asterisk'></i></span><br />";
			echo "<strong>Date Created:</strong> " . date("m-d-Y", strtotime($request_info->date_created));

$box_details = $wpdb->get_results(
				"
SELECT " . $wpdb->prefix . "wpsc_epa_boxinfo.id as id, " . $wpdb->prefix . "wpsc_epa_boxinfo.id as box_data_id, " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id as box_id, 
" . $wpdb->prefix . "terms.name as digitization_center,
" . $wpdb->prefix . "wpsc_epa_storage_location.aisle as aisle, " . $wpdb->prefix . "wpsc_epa_storage_location.bay as bay, " . $wpdb->prefix . "wpsc_epa_storage_location.shelf as shelf, " . $wpdb->prefix . "wpsc_epa_storage_location.position as position, " . $wpdb->prefix . "wpsc_epa_location_status.locations as physical_location,
(SELECT " . $wpdb->prefix . "terms.name FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "terms WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as status,
" . $wpdb->prefix . "wpsc_epa_program_office.office_acronym as program_office,
" . $wpdb->prefix . "epa_record_schedule.Record_Schedule_Number as record_schedule
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_location_status ON " . $wpdb->prefix . "wpsc_epa_boxinfo.location_status_id = " . $wpdb->prefix . "wpsc_epa_location_status.id
INNER JOIN " . $wpdb->prefix . "terms ON  " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office ON " . $wpdb->prefix . "wpsc_epa_program_office.office_code = " . $wpdb->prefix . "wpsc_epa_boxinfo.program_office_id
INNER JOIN " . $wpdb->prefix . "epa_record_schedule ON " . $wpdb->prefix . "epa_record_schedule.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.record_schedule_id

WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = " . $request_info->id
			);

			$tbl = '<br /><br /><strong>Boxes associated with this request:</strong><br /><br />
<table id="dataTable" class="stripe" width="100%">
<thead>
  <tr>
    <th width="35px" height="50px"></th>
    <th>ID</th>
    <th>Physical Location</th>
    <th class="desktop">Digitization Center</th>
    <th class="desktop">Shelf Location</th>
    <th class="desktop">Box Status</th>
    <th class="desktop">Program Office</th>
    <th class="desktop">Record Schedule</th>
  </tr>
 </thead><tbody>
';

			foreach ($box_details as $info) {
				$boxlist_id = $info->box_id;
				$boxlist_location = $info->digitization_center;
				if ($boxlist_location == 'East') {
					$boxlist_location_val = "E";
				} else {
					$boxlist_location_val = "W";
				}

			    if (($info->aisle == '0') || ($info->bay == '0') || ($info->shelf == '0') || ($info->position == '0')) {
				$boxlist_shelf_location = 'Currently Unassigned';
				} else {
                $boxlist_shelf_location = $info->aisle . 'A_' .$info->bay .'B_' . $info->shelf . 'S_' . $info->position .'P_'.$boxlist_location_val;
				}
				
				$boxlist_box_status = $info->status;
                $boxlist_physical_location = $info->physical_location;
                $boxlist_program_office = $info->program_office;
                $boxlist_record_schedule = $info->record_schedule;
                
                $boxdetails_url = admin_url( 'admin.php?page=boxdetails', 'https' );
                
				$tbl .= '<tr>
            <td  width="35px" height="50px"></td>
            <td><a href="'.$boxdetails_url.'&id=' . $boxlist_id . '" target="_blank">' . $boxlist_id . '</a></td>';
            
            $type = 'box';
            $loc_id = Patt_Custom_Func::id_in_physical_location($boxlist_id, $type);
            
            if($loc_id != '') {
             $tbl .= '<td>' . $boxlist_physical_location . ' ('.$loc_id.')</td>';
            } else {
             $tbl .= '<td>' . $boxlist_physical_location . '</td>';           
            }
            
            $tbl .= '<td>' . $boxlist_location . '</td>
            <td>' . $boxlist_shelf_location . '</td>
            <td>' . $boxlist_box_status . '</td>
            <td>' . $boxlist_program_office . '</td>
            <td>' . $boxlist_record_schedule . '</td>
            </tr>
            ';
			}
			$tbl .= '</tbody></table>';

			echo $tbl;
} else {
    echo 'Request does not exist or is archived.';
}
//Determine if string contains a box ID
    } elseif (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $barcode)){

//echo '<strong>Box ID: '.$barcode.'<strong>';
$box_details = $wpdb->get_row(
				"
SELECT 
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as pk, 
" . $wpdb->prefix . "wpsc_epa_boxinfo.id as box_data_id, 
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_status as box_status_id, 
" . $wpdb->prefix . "wpsc_ticket.request_id as ticket,
" . $wpdb->prefix . "wpsc_epa_boxinfo.box_id as active,
" . $wpdb->prefix . "terms.name as location, 
" . $wpdb->prefix . "wpsc_epa_storage_location.aisle as aisle, 
" . $wpdb->prefix . "wpsc_epa_storage_location.bay as bay, 
" . $wpdb->prefix . "wpsc_epa_storage_location.shelf as shelf, 
" . $wpdb->prefix . "wpsc_epa_storage_location.position as position, 
" . $wpdb->prefix . "wpsc_epa_location_status.locations as physical_location,
" . $wpdb->prefix . "epa_record_schedule.Record_Schedule_Number as rsnum,
" . $wpdb->prefix . "wpsc_epa_program_office.office_acronym as program_office,
(SELECT " . $wpdb->prefix . "terms.name as box_status FROM " . $wpdb->prefix . "terms, " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as box_status
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo ON " . $wpdb->prefix . "wpsc_epa_boxinfo.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo.box_id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket ON " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = " . $wpdb->prefix . "wpsc_ticket.id 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_location_status ON " . $wpdb->prefix . "wpsc_epa_boxinfo.location_status_id = " . $wpdb->prefix . "wpsc_epa_location_status.id
INNER JOIN " . $wpdb->prefix . "terms ON  " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office ON " . $wpdb->prefix . "wpsc_epa_program_office.office_code = " . $wpdb->prefix . "wpsc_epa_boxinfo.program_office_id
INNER JOIN " . $wpdb->prefix . "epa_record_schedule ON " . $wpdb->prefix . "wpsc_epa_boxinfo.record_schedule_id = " . $wpdb->prefix . "epa_record_schedule.id

WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id = '" . $barcode . "'"

			);

			$box_details_id = $box_details->pk;

$request_id = $wpdb->get_row("SELECT ".$wpdb->prefix."wpsc_ticket.request_id FROM ".$wpdb->prefix."wpsc_epa_boxinfo, ".$wpdb->prefix."wpsc_ticket WHERE ".$wpdb->prefix."wpsc_ticket.id = ".$wpdb->prefix."wpsc_epa_boxinfo.ticket_id AND ".$wpdb->prefix."wpsc_epa_boxinfo.box_id = '" . $barcode . "'"); 
$location_request_id = $request_id->request_id;

$is_active = Patt_Custom_Func::request_status( $location_request_id );


if (!empty($box_details_id) && $is_active == 1) {

			$box_content = $wpdb->get_results(
				"SELECT " . $wpdb->prefix . "wpsc_epa_folderdocinfo.folderdocinfo_id as id, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.folderdocinfofile_id as folderdocinfofile_id, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.title as title, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.date as date, " . $wpdb->prefix . "wpsc_epa_boxinfo.lan_id as contact, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.validation as validation
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo ON " . $wpdb->prefix . "wpsc_epa_folderdocinfo.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files ON " . $wpdb->prefix . "wpsc_epa_folderdocinfo.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.folderdocinfo_id
WHERE " . $wpdb->prefix . "wpsc_epa_folderdocinfo.box_id = '" . $box_details_id . "'"
			);


			$request_id = $box_details->ticket;
                
				$box_details_location = $box_details->location;
				if ($box_details_location == 'East') {
					$box_details_location_val = "E";
				} else {
					$box_details_location_val = "W";
				}
				
			    if (($box_details->aisle == '0') || ($box_details->bay == '0') || ($box_details->shelf == '0') || ($box_details->position == '0')) {
				$box_details_shelf_location = 'Currently Unassigned';
				} else {
                $box_details_shelf_location = $box_details->aisle . 'A_' .$box_details->bay .'B_' . $box_details->shelf . 'S_' . $box_details->position .'P_'.$box_details_location_val;
				}
				
				$boxdetails_url = admin_url( 'admin.php?page=boxdetails', 'https' );

			$type = 'box';
			$status_icon = '';
			
			 //if($boxlist_box_destroyed > 0) {
            if(Patt_Custom_Func::id_in_box_destroyed($barcode, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #FF0000;"><i class="fas fa-ban" title="Box Destroyed"></i></span>';
            }
            
            //if($boxlist_unathorized_destruction > 0) {
            if(Patt_Custom_Func::id_in_unauthorized_destruction($barcode, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" title="Unauthorized Destruction"></i></span>';
            }
            
            //if($boxlist_unathorized_destruction > 0) {
            if(Patt_Custom_Func::id_in_damaged($barcode, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #FFC300;"><i class="fas fa-bolt" title="Damaged"></i></span>';
            }
            
            //if($boxlist_freeze_sum > 0) {
             if(Patt_Custom_Func::id_in_freeze($barcode, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" title="Freeze"></i></span>';
            }

if(Patt_Custom_Func::id_in_return($barcode,$type) == 1){
$status_icon .= ' <span style="font-size: 1em; color: #FF0000;margin-left:4px;"><i class="fas fa-undo" title="Declined"></i></span>';
}

if(Patt_Custom_Func::id_in_recall($barcode,$type) == 1){
$status_icon .= ' <span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" title="Recall"></i></span>';
}

	
			echo "<strong>Box ID:</strong> <a href='".$boxdetails_url."&id=" . $barcode . "' target='_blank'>" . $barcode . "</a>".$status_icon."<br />";
			echo "<strong>Program Office:</strong> " . $box_details->program_office . "<br />";
			echo "<strong>Record Schedule:</strong> " . $box_details->rsnum . "<br />";
			echo "<strong>Digitization Center:</strong> " . $box_details->location . "</strong><br />";
			
            $loc_id = Patt_Custom_Func::id_in_physical_location($barcode, $type);
            
            if($loc_id != '') {
             echo '<strong>Physical Location:</strong> ' . $box_details->physical_location . ' ('.$loc_id.')<br />';
            } else {
             echo '<strong>Physical Location:</strong> ' . $box_details->physical_location . '<br />';           
            }
			
			echo "<strong>Shelf Location:</strong> " . $box_details_shelf_location . "<br />";
			
			$box_status_color = get_term_meta($box_details->box_status_id, 'wpsc_box_status_background_color', true);

			echo "<strong>Box Status:</strong> " . $box_details->box_status. " <span style='color: ".$box_status_color." ;margin: 0px;'><i class='fas fa-circle'></i></span>";

			$tbl = '<br /><br /><strong>Box Contents:</strong><br /><br />
<table id="dataTable" class="stripe" width="100%">
<thead>
  <tr>
    <th width="35px" height="50px"></th>
    <th>ID</th>
    <th>Title</th>
    <th class="desktop">Date</th>
    <th class="desktop">Contact</th>
    <th class="desktop">Validation</th>
  </tr>
 </thead><tbody>
';
			foreach ($box_content as $info) {
				$boxcontent_id = $info->id;
				$boxcontent_title = $info->title;
				$boxcontent_title_truncated = (strlen($boxcontent_title) > 20) ? substr($boxcontent_title, 0, 20) . '...' : $boxcontent_title;
				$boxcontent_date = $info->date;
				$boxcontent_site = $info->site;
				$boxcontent_contact = $info->contact;
				
				$boxcontent_validation = $info->validation;
				
				/*
				if($boxcontent_validation == 0) {
				$boxcontent_validation_icon = '<span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" title="Not Validated"></i></span>';
				} else {
				$boxcontent_validation_icon = '<span style="font-size: 1.3em; color: #008000;"><i class="fas fa-check-circle" title="Validated"></i></span> ';
				}
				*/
				
				if(Patt_Custom_Func::id_in_validation($info->folderdocinfofile_id,'folderfile') == 1) {
				    $boxcontent_validation_user = Patt_Custom_Func::get_validation_user($info->folderdocinfofile_id);
                    $boxcontent_validation_icon = '<span style="font-size: 1em; color: #008000;"><i class="fas fa-check-circle" title="Validated"></i></span> ['. $boxcontent_validation_user .'] ';
                }
                else if (Patt_Custom_Func::id_in_validation($info->folderdocinfofile_id,'folderfile') != 1 && Patt_Custom_Func::id_in_rescan($info->folderdocinfofile_id,'folderfile') == 1) {
                    $boxcontent_validation_icon = '<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" title="Not Validated"></i></span> <span style="color: #FF0000;"><strong>[Re-scan]</strong></span>';
                }
                else {
                    $boxcontent_validation_icon = '<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" title="Not Validated"></i></span>';
                }

				
				$folderfile_url = admin_url( 'admin.php?page=filedetails', 'https' );

				$tbl .= '
    <tr>
            <td width="35px" height="50px"></td>
            <td><a href="'.$folderfile_url.'&id=' . $boxcontent_id . '" target="_blank">' . $boxcontent_id . '</a></td>
            <td>' . $boxcontent_title_truncated . '</td>
            <td>' . $boxcontent_date . '</td>
            <td>' . $boxcontent_contact . '</td>
            <td>' . $boxcontent_validation_icon . '</td>
            </tr>
            ';
			}
			$tbl .= '</tbody></table>';

			echo $tbl;
			
} else {
 echo 'Box does not exist or is archived.';
}
			
//Determine if string is a folder/file ID     
    } elseif (preg_match("/^([0-9]{7}-[0-9]{1,4}-02-[0-9]{1,4}(-[a][0-9]{1,4})?)(?:,\s*(?1))*$/", $barcode)){

//echo '<strong>Folder/File ID: '.$barcode.'<strong>';

$folderfile_details = $wpdb->get_row(
		"SELECT 
	a.id as id,
	c.ticket_id as ticket_id,
	b.index_level as index_level,
	a.box_id as box_id,
	b.title as title,
	b.date as date,
	a.author as author,
	b.record_type as record_type,
	a.site_name as site_name,
	a.siteid as site_id,
	a.close_date as close_date,
	b.source_format as source_format,
	a.folderdocinfo_id as folderdocinfo_id,
	a.essential_record as essential_record,
	b.validation as validation,
	b.validation_user_id as validation_user_id,
	b.rescan as rescan,
	b.unauthorized_destruction as unauthorized_destruction,
	a.folder_identifier as folder_identifier,
	b.freeze as freeze,
	a.addressee as addressee,
	b.DOC_REGID as sems_reg_id,
	b.folderdocinfofile_id as folderdocinfofile_id,
	b.id as folderfileid,
	b.attachment,
	b.file_name,
	b.object_key,
	b.object_location,
	b.source_file_location,
	b.file_object_id,
	b.file_size,
    b.description,
    b.tags,
    b.access_restriction,
    b.specific_access_restriction,
    b.use_restriction,
    b.specific_use_restriction,
    b.rights_holder,
    b.source_dimensions
	
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo a
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON a.id = b.folderdocinfo_id
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo c ON c.id = a.box_id
    WHERE b.folderdocinfofile_id = '" . $barcode . "'"
	);

//folderdocinfo table id
    $folderfile_id = $folderfile_details->id;
    
    //folderdocinfofiles table id
    $folderdocinfofileid = $folderfile_details->folderfileid;
    
    $folderfile_index_level = $folderfile_details->index_level;
	$folderfile_boxid = $folderfile_details->box_id;
	$folderfile_title = $folderfile_details->title;
	$folderfile_date = $folderfile_details->date;
	$folderfile_author = $folderfile_details->author;
	$folderfile_record_type = $folderfile_details->record_type;
	$folderfile_site_name = $folderfile_details->site_name;
	$folderfile_site_id = $folderfile_details->site_id;
	$folderfile_close_date = $folderfile_details->close_date;
	$folderfile_source_format = $folderfile_details->source_format;
	
	$folderfile_sems_reg_id = $folderfile_details->sems_reg_id;
	$folderfile_file_object_id = $folderfile_details->file_object_id;
	
	$folderfile_folderdocinfo_id = $folderfile_details->folderdocinfo_id;
	$folderfile_folderdocinfofile_id = $folderfile_details->folderdocinfofile_id;
	
	$folderfile_essential_record = $folderfile_details->essential_record;
	$folderfile_validation = $folderfile_details->validation;
	$folderfile_validation_user = $folderfile_details->validation_user_id;	
	$folderfile_rescan = $folderfile_details->rescan;
    $folderfile_destruction = $folderfile_details->unauthorized_destruction;
    $folderfile_identifier = $folderfile_details->folder_identifier;
    $folderfile_freeze = $folderfile_details->freeze;
    $folderfile_damaged = $folderfile_details->damaged;
    $folderfile_addressee = $folderfile_details->addressee;
    
    $folderfile_description = $folderfile_details->description;
    $folderfile_tags = $folderfile_details->tags;
    $folderfile_access_restriction = $folderfile_details->access_restriction;
    $folderfile_specific_access_restriction = $folderfile_details->specific_access_restriction;
    $folderfile_use_restriction = $folderfile_details->use_restriction;
    $folderfile_specific_use_restriction = $folderfile_details->specific_use_restriction;
    $folderfile_rights_holder = $folderfile_details->rights_holder;
    $folderfile_source_dimensions = $folderfile_details->source_dimensions;
    
    $type = 'folderfile';

	$status_icon = '';
	
	 //if($boxlist_box_destroyed > 0) {
    if(Patt_Custom_Func::id_in_box_destroyed($barcode, $type) == 1) {
    $status_icon .= ' <span style="font-size: 1em; color: #FF0000;"><i class="fas fa-ban" title="Box Destroyed"></i></span>';
    }
    
    //if($boxlist_unathorized_destruction > 0) {
    if(Patt_Custom_Func::id_in_unauthorized_destruction($barcode, $type) == 1) {
    $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" title="Unauthorized Destruction"></i></span>';
    }
    
    //if($boxlist_unathorized_destruction > 0) {
    if(Patt_Custom_Func::id_in_damaged($barcode, $type) == 1) {
    $status_icon .= ' <span style="font-size: 1em; color: #FFC300;"><i class="fas fa-bolt" title="Damaged"></i></span>';
    }
    
    //if($boxlist_freeze_sum > 0) {
     if(Patt_Custom_Func::id_in_freeze($barcode, $type) == 1) {
    $status_icon .= ' <span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" title="Freeze"></i></span>';
    }

    if(Patt_Custom_Func::id_in_return($barcode,$type) == 1){
    $status_icon .= ' <span style="font-size: 1em; color: #FF0000;margin-left:4px;"><i class="fas fa-undo" title="Declined"></i></span>';
    }
    
    if(Patt_Custom_Func::id_in_recall($barcode,$type) == 1){
    $status_icon .= ' <span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" title="Recall"></i></span>';
    }
    
    //Validation check
    if(Patt_Custom_Func::id_in_validation($barcode,$type) == 1) {
	    $boxcontent_validation_user = Patt_Custom_Func::get_validation_user($barcode);
        $status_icon .= ' <span style="font-size: 1em; color: #008000;"><i class="fas fa-check-circle" title="Validated"></i></span> ['. $boxcontent_validation_user .'] ';
    }
    else if (Patt_Custom_Func::id_in_validation($barcode,$type) != 1 && Patt_Custom_Func::id_in_rescan($barcode,$type) == 1) {
        $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" title="Not Validated"></i></span> <span style="color: #FF0000;"><strong>[Re-scan]</strong></span>';
    }
    else {
        $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" title="Not Validated"></i></span>';
    }
    
    /*
    if(Patt_Custom_Func::id_in_validation($barcode,$type) == 1){
    $status_icon .= ' <span style="font-size: 1em; color: #008000;"><i class="fas fa-check-circle" title="Validated"></i></span>';
    } else {
    $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" title="Not Validated"></i></span>'; 
    }
    
    if(Patt_Custom_Func::id_in_rescan($barcode,$type) == 1){
    $status_icon .= ' <span style="color: red;"> [rescan]</span>';
    }
    */
    
$folderfile_url = admin_url( 'admin.php?page=filedetails', 'https' );

        //record schedule
    $box_rs = Patt_Custom_Func::get_record_schedule_by_id($folderfile_folderdocinfofile_id, $type);
    
    //program office
   $box_po = Patt_Custom_Func::get_program_office_by_id($folderfile_folderdocinfofile_id, $type);
    
if(!empty($folderfile_details->id) &&  Patt_Custom_Func::ticket_active($folderfile_details->ticket_id) == 1) {	

            echo "<strong>Folder/File ID:</strong> <a href='".$folderfile_url."&id=" . $folderfile_folderdocinfofile_id . "' target='_blank'>" . $folderfile_folderdocinfofile_id . "</a>" . $status_icon . "<br />";


			if(!empty($box_po)) {
			    echo "<strong>Program Office:</strong> " . $box_po . "<br />";
			}
			else {
			    echo "<strong style='color:red'>Program Office: REASSIGN IMMEDIATELY</strong> <br />";
			}
            
            if(!empty($box_rs)) {
                echo "<strong>Record Schedule:</strong> " . $box_rs ."<br />";
            }
            else {
			    echo "<strong style='color:red'>Record Schedule: REASSIGN IMMEDIATELY</strong> <br />";
			}
			
			if(!empty($folderfile_identifier)) {
			    echo "<strong>Folder Identifier:</strong> " . $folderfile_identifier . "<br />";
			}
  
  			if (!empty($folderfile_title)) {
				echo "<strong>Title:</strong> " . $folderfile_title . "<br />";
			}
			
			if(!empty($folderfile_description)) {
			    echo "<strong>Description:</strong> " . $folderfile_description . "<br />";
			}

			if (!empty($folderfile_date)) {
				echo "<strong>Creation Date:</strong> " . $folderfile_date . "<br />";
			}
			
			$folderfile_author_array = array();
			$folderfile_author_explode = explode(';', $folderfile_author);
            foreach ($folderfile_author_explode as $creator) {
                array_push($folderfile_author_array, $creator);
            }
			
			if(!empty($folderfile_author)) {
			    echo "<strong>Creator:</strong> " . implode("; ", $folderfile_author_array) . "<br />";
			}
			
			$folderfile_addressee_array = array();
			$folderfile_addressee_explode = explode(';', $folderfile_addressee);
			foreach ($folderfile_addressee_explode as $addressee) {
                array_push($folderfile_addressee_array, $addressee);
            }
			
			if(!empty($folderfile_addressee)) {
			    echo "<strong>Addressee:</strong> " . implode("; ", $folderfile_addressee_array) . "<br />";
			}
			if (!empty($folderfile_record_type)) {
				echo "<strong>Record Type:</strong> " . $folderfile_record_type . "<br />";
			}
			if (!empty($folderfile_site_name)) {
				echo "<strong>Site Name:</strong> " . $folderfile_site_name . "<br />";
			}
			if (!empty($folderfile_site_id)) {
				echo "<strong>Site ID #:</strong> " . $folderfile_site_id . "<br />";
			}
			if (!empty($folderfile_close_date)) {
				echo "<strong>Close Date:</strong> " . $folderfile_close_date . "<br />";
			}
			
			if(!empty($folderfile_access_restriction)) {
			    echo "<strong>Access Restriction:</strong> " . $folderfile_access_restriction . "<br />";
			}
			
			$folderfile_specific_access_restrictions_array = array();
			$folderfile_specific_access_restrictions_explode = explode(';', $folderfile_specific_access_restriction);
			foreach ($folderfile_specific_access_restrictions_explode as $specific_access_restriction) {
                array_push($folderfile_specific_access_restrictions_array, $specific_access_restriction);
            }

			if(!empty($folderfile_specific_access_restriction)) {
			    echo "<strong>Specfic Access Restriction:</strong> " . implode("; ", $folderfile_specific_access_restrictions_array) . "<br />";
			}
			
			
			if(!empty($folderfile_use_restriction)) {
			    echo "<strong>Use Restriction:</strong> " . $folderfile_use_restriction . "<br />";
			}
			
			$folderfile_specific_use_restrictions_array = array();
			$folderfile_specific_use_restrictions_explode = explode(';', $folderfile_specific_use_restriction);
			foreach ($folderfile_specific_use_restrictions_explode as $specific_use_restriction) {
                array_push($folderfile_specific_use_restrictions_array, $specific_use_restriction);
            }
			
			if(!empty($folderfile_specific_use_restriction)) {
			    echo "<strong>Specific Use Restriction:</strong> " . implode("; ", $folderfile_specific_use_restrictions_array) . "<br />";
			}
			
			$folderfile_rights_holder_array = array();
			$folderfile_rights_holder_explode = explode(';', $folderfile_rights_holder);
			foreach ($folderfile_rights_holder_explode as $rights_holder) {
                array_push($folderfile_rights_holder_array, $rights_holder);
            }

			if(!empty($folderfile_rights_holder)) {
			    echo "<strong>Rights Holder:</strong> " . implode("; ", $folderfile_rights_holder_array) . "<br />";
			}
			
			if (!empty($folderfile_source_format)) {
				echo "<strong>Source Type:</strong> " . stripslashes($folderfile_source_format) . "<br />";
			}
			
			if(!empty($folderfile_source_dimensions)) {
			    echo "<strong>Source Dimensions:</strong> " . stripslashes($folderfile_source_dimensions) . "<br />";
			}
			
			if($folderfile_essential_record == 1) {
			    echo "<strong>Essential Record:</strong> Yes" . "<br />";
			}
			else {
			    echo "<strong>Essential Record:</strong> No" . "<br />";
			}
			
			$folderfile_tags_array = array();
			$folderfile_tags_explode = explode(',', $folderfile_tags);
			foreach ($folderfile_tags_explode as $tag) {
                array_push($folderfile_tags_array, $tag);
            }
			
			if(!empty($folderfile_tags)) {
			    echo "<strong>Tags:</strong> " . implode(", ", $folderfile_tags_array) . "<br />";
			}

} else {
 echo 'Folder/file does not exist or is archived.';
}

//FAIL for all other barcodes       
} else {

echo 'Please scan a valid barcode.';

}

} else {
   echo "Lookup not successful.";
}
?>