<?php

/*
* Related Files barcode_validate.php, barcode_lookup.php, arms_validate.php, validate_paging.php, arms_validation_processing.php, validate_pdf_include.php, update_validate.php
*/

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');
?>

<style>
.dataTable thead tr {
	background-color: rgb(66, 73, 73) !important; 
	color: rgb(255, 255, 255) !important;
}

.text_link {
  color:#1d4289;
  text-decoration: underline;
}
</style>

<?php
//Define tables
$table_box = $wpdb->prefix . "wpsc_epa_boxinfo";
$table_scan_list = $wpdb->prefix . "wpsc_epa_scan_list";

if(isset($_POST['postvarsbarcode'])){
   $barcode = $_POST['postvarsbarcode'];
   $pagetype = $_POST['postvarspagetype'];

//Determine if string is URL
    if(filter_var($barcode, FILTER_VALIDATE_URL) || preg_match("/^([0-9]{7})(?:,\s*(?1))*$/", $barcode))
    {
        
if(preg_match("/^([0-9]{7})(?:,\s*(?1))*$/", $barcode)) {
$requestid = $barcode;
} else {
parse_str(parse_url($barcode)['query'], $params);
$requestid = $params['id'];
}


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

WHERE " . $wpdb->prefix . "wpsc_ticket.request_id = " . $requestid
			);
//Checking to see if requests both 1) exists 2) is not archived
if(!empty($request_info->id) && $request_info->active == 1) {
            $type = 'request';
            $status_icon = '';
            //Status icons
            if(Patt_Custom_Func::id_in_box_destroyed($requestid, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #a80000;"><i class="fas fa-ban" aria-hidden="true" aria-hidden="true" title="Box Destroyed"></i><span class="sr-only">Box Destroyed</span></span>';
            }
            
            if(Patt_Custom_Func::id_in_unauthorized_destruction($requestid, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span>';
            }
            
            if(Patt_Custom_Func::id_in_damaged($requestid, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #000000;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span>';
            }
            
             if(Patt_Custom_Func::id_in_freeze($requestid, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #005C7A;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span>';
            }

            if(Patt_Custom_Func::id_in_return($requestid,$type) == 1){
            $status_icon .= '<span style="font-size: 1em; color: #A80000;margin-left:4px;"><i class="fas fa-undo" aria-hidden="true" title="Declined"></i><span class="sr-only">Declined</span></span>';
            }
            
            if(Patt_Custom_Func::id_in_recall($requestid,$type) == 1){
            $status_icon .= '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" aria-hidden="true" title="Recall"></i><span class="sr-only">Recall</span></span>';
            }
			$request_url = admin_url( 'admin.php?page=wpsc-tickets', 'https' );
			$status_color = get_term_meta($request_info->ticket_status_id,'wpsc_status_background_color',true);
            $priority_color = get_term_meta($request_info->ticket_priority_id,'wpsc_priority_background_color',true);

if ($pagetype == 0) {
    echo "<strong>Request ID:</strong> <a href='".$request_url."&id=".$requestid."' class='text_link' target='_blank'>" . $requestid . "</a>" . $status_icon . "<br />";
} elseif ($pagetype == 1) {
    echo "<strong>Request ID:</strong> " . $requestid . $status_icon . "<br />";
} else {
    echo "Please pass a page type.";
}
  			echo "<strong>Requestor Name:</strong> " . $request_info->customer_name . "<br />";
			echo "<strong>Requestor Email:</strong> " . $request_info->customer_email . "<br />";
			echo "<strong>Request Status:</strong> " . $request_info->ticket_status . "  <span style='color: ".$status_color." ;margin: 0px;'><i class='fas fa-circle' aria-hidden='true' title='Request Status Color'></i><span class='sr-only'>Request Status Color</span></span>
<br />";
			echo "<strong>Priority:</strong> " . $request_info->ticket_priority . "  <span style='color: ".$priority_color." ;margin: 0px;'><i class='fas fa-asterisk' aria-hidden='true' title='Request Priority Color'></i></span><span class='sr-only'>Request Priority Color</span><br />";
			echo "<strong>Date Created:</strong> " . date("m-d-Y", strtotime($request_info->date_created));

$box_details = $wpdb->get_results(
				"
SELECT " . $wpdb->prefix . "wpsc_epa_boxinfo.id as id, " . $wpdb->prefix . "wpsc_epa_boxinfo.id as box_data_id, " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id as box_id, 
" . $wpdb->prefix . "terms.name as digitization_center,
" . $wpdb->prefix . "wpsc_epa_storage_location.aisle as aisle, " . $wpdb->prefix . "wpsc_epa_storage_location.bay as bay, " . $wpdb->prefix . "wpsc_epa_storage_location.shelf as shelf, " . $wpdb->prefix . "wpsc_epa_storage_location.position as position, " . $wpdb->prefix . "wpsc_epa_location_status.locations as physical_location,
(SELECT " . $wpdb->prefix . "terms.name FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "terms WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as status,
" . $wpdb->prefix . "wpsc_epa_program_office.office_acronym as program_office,
" . $wpdb->prefix . "epa_record_schedule.Schedule_Item_Number as record_schedule
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
			    $boxlist_dbid = $info->id;
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
                $new_bay = Patt_Custom_Func::get_bay_from_number($info->bay);
                $boxlist_shelf_location = $info->aisle . 'A_' .$new_bay.'B_' . $info->shelf . 'S_' . $info->position .'P_'.$boxlist_location_val;
				}
				
				$boxlist_box_status = $info->status;
                $boxlist_physical_location = $info->physical_location;
                $boxlist_program_office = $info->program_office;
                $boxlist_record_schedule = $info->record_schedule;
                
                $boxdetails_url = admin_url( 'admin.php?page=boxdetails', 'https' );
if ($pagetype == 0) {
			$tbl .= '<tr>
            <td  width="35px" height="50px"></td>
            <td data-order="'. $boxlist_dbid .'"><a href="'.$boxdetails_url.'&id=' . $boxlist_id . '" class="text_link" target="_blank">' . $boxlist_id . '</a></td>';
} elseif ($pagetype == 1) {
			$tbl .= '<tr>
            <td  width="35px" height="50px"></td>
            <td data-order="'. $boxlist_dbid .'">' . $boxlist_id . '</td>';
} else {
    echo "Please pass a page type.";
}                
            
            $type = 'box';
            if (preg_match('/^\d{1,3}A_[A-Z]{1,2}B_\d{1,3}S_[1-3]{1}P_(E|W|ECUI|WCUI)$/i', $loc_id)) {
              $loc_id_converted = Patt_Custom_Func::convert_bay_letter(Patt_Custom_Func::id_in_physical_location($boxlist_id, $type));
            } else {
              $loc_id_converted = Patt_Custom_Func::id_in_physical_location($boxlist_id, $type);
            }
            
            if($loc_id != '') {
             $tbl .= '<td>' . $boxlist_physical_location . ' ('.$loc_id_converted.')213213</td>';
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
    } elseif (preg_match("/^P-(E|W)-[0-9]{5}$/", $barcode)){

$box_details = $wpdb->get_results(
				"
SELECT " . $wpdb->prefix . "wpsc_epa_boxinfo.id as id, " . $wpdb->prefix . "wpsc_epa_boxinfo.id as box_data_id, " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id as box_id, 
" . $wpdb->prefix . "terms.name as digitization_center,
" . $wpdb->prefix . "wpsc_epa_storage_location.aisle as aisle, " . $wpdb->prefix . "wpsc_epa_storage_location.bay as bay, " . $wpdb->prefix . "wpsc_epa_storage_location.shelf as shelf, " . $wpdb->prefix . "wpsc_epa_storage_location.position as position, " . $wpdb->prefix . "wpsc_epa_location_status.locations as physical_location,
(SELECT " . $wpdb->prefix . "terms.name FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "terms WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as status,
" . $wpdb->prefix . "wpsc_epa_program_office.office_acronym as program_office,
" . $wpdb->prefix . "epa_record_schedule.Schedule_Item_Number as record_schedule
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_location_status ON " . $wpdb->prefix . "wpsc_epa_boxinfo.location_status_id = " . $wpdb->prefix . "wpsc_epa_location_status.id
INNER JOIN " . $wpdb->prefix . "terms ON  " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center
INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office ON " . $wpdb->prefix . "wpsc_epa_program_office.office_code = " . $wpdb->prefix . "wpsc_epa_boxinfo.program_office_id
INNER JOIN " . $wpdb->prefix . "epa_record_schedule ON " . $wpdb->prefix . "epa_record_schedule.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.record_schedule_id

WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.pallet_id = '" . $barcode ."'"
			);
      
   

			$tbl = '<strong>Boxes associated with this pallet:</strong><br /><br />
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
			    $boxlist_dbid = $info->id;
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
                $new_bay = Patt_Custom_Func::get_bay_from_number($info->bay);
                $boxlist_shelf_location = $info->aisle . 'A_' .$new_bay .'B_' . $info->shelf . 'S_' . $info->position .'P_'.$boxlist_location_val;
				}
				
				$boxlist_box_status = $info->status;
                $boxlist_physical_location = $info->physical_location;
                $boxlist_program_office = $info->program_office;
                $boxlist_record_schedule = $info->record_schedule;
                
                $boxdetails_url = admin_url( 'admin.php?page=boxdetails', 'https' );
                
if ($pagetype == 0) {
			$tbl .= '<tr>
            <td  width="35px" height="50px"></td>
            <td data-order="'. $boxlist_dbid .'"><a href="'.$boxdetails_url.'&id=' . $boxlist_id . '" class="text_link" target="_blank">' . $boxlist_id . '</a></td>';
} elseif ($pagetype == 1) {
			$tbl .= '<tr>
            <td  width="35px" height="50px"></td>
            <td data-order="'. $boxlist_dbid .'">' . $boxlist_id . '</td>';
} else {
    echo "Please pass a page type.";
}    
              
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
      
//Determine if string contains a box ID
    } elseif (preg_match("/^[0-9]{7}-[0-9]{1,3}$/", $barcode)){

//echo '<strong>Box ID: '.$barcode.'<strong>';
//REVIEW
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
" . $wpdb->prefix . "epa_record_schedule.Schedule_Item_Number as rsnum,
" . $wpdb->prefix . "wpsc_epa_program_office.office_acronym as program_office,
(SELECT " . $wpdb->prefix . "terms.name as box_status FROM " . $wpdb->prefix . "terms, " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status = " . $wpdb->prefix . "terms.term_id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = box_data_id) as box_status
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files ON " . $wpdb->prefix . "wpsc_epa_boxinfo.id = " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.box_id
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
//REVIEW
			$box_content = $wpdb->get_results(
				"SELECT id, folderdocinfofile_id, title as title, date as date, lan_id as contact, validation as validation
                FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
                WHERE box_id = '" . $box_details_id . "'"
			);


			$request_id = $box_details->ticket;
                
				$box_details_location = $box_details->location;
				if ($box_details_location == 'East') {
					$box_details_location_val = "E";
				} else {
					$box_details_location_val = "W";
				}
				
			    if (($box_details->aisle ==%2