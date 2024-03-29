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
$alphabet = range('A', 'O');
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
                $new_bay = $alphabet[$info->bay-1];
                $boxlist_shelf_location = $info->aisle . 'A_' . $new_bay .'B_' . $info->shelf . 'S_' . $info->position .'P_'.$boxlist_location_val;
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
                $boxlist_shelf_location = $info->aisle . 'A_' .$info->bay .'B_' . $info->shelf . 'S_' . $info->position .'P_'.$boxlist_location_val;
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
				
			    if (($box_details->aisle == '0') || ($box_details->bay == '0') || ($box_details->shelf == '0') || ($box_details->position == '0')) {
				$box_details_shelf_location = 'Currently Unassigned';
				} else {
                $new_bay = $alphabet[$box_details->bay-1];
                $box_details_shelf_location = $box_details->aisle . 'A_' .$new_bay .'B_' . $box_details->shelf . 'S_' . $box_details->position .'P_'.$box_details_location_val;
				}
				
				$boxdetails_url = admin_url( 'admin.php?page=boxdetails', 'https' );

			$type = 'box';
			$status_icon = '';
			
            if(Patt_Custom_Func::id_in_box_destroyed($barcode, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #a80000;"><i class="fas fa-ban" aria-hidden="true" title="Box Destroyed"></i><span class="sr-only">Box Destroyed</span></span>';
            }
            
            if(Patt_Custom_Func::id_in_unauthorized_destruction($barcode, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span>';
            }
            
            if(Patt_Custom_Func::id_in_damaged($barcode, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #000000;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span>';
            }
            
             if(Patt_Custom_Func::id_in_freeze($barcode, $type) == 1) {
            $status_icon .= ' <span style="font-size: 1em; color: #005C7A;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span>';
            }

if(Patt_Custom_Func::id_in_return($barcode,$type) == 1){
$status_icon .= '<span style="font-size: 1em; color: #A80000;margin-left:4px;"><i class="fas fa-undo" aria-hidden="true" title="Declined"></i><span class="sr-only">Declined</span></span>';
}

if(Patt_Custom_Func::id_in_recall($barcode,$type) == 1){
$status_icon .= '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" aria-hidden="true" title="Recall"></i><span class="sr-only">Recall</span></span>';
}

if ($pagetype == 0) {
			echo "<strong>Box ID:</strong> <a href='".$boxdetails_url."&id=" . $barcode . "' class='text_link' target='_blank'>" . $barcode . "</a>".$status_icon."<br />";
} elseif ($pagetype == 1) {
			echo "<strong>Box ID:</strong> " . $barcode .$status_icon."<br />";
} else {
    echo "Please pass a page type.";
}    
  
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

			echo "<strong>Box Status:</strong> " . $box_details->box_status. " <span style='color: ".$box_status_color." ;margin: 0px;'><i class='fas fa-circle' aria-hidden='true' title='Box Status Color'></i><span class='sr-only'>Box Status Color</span></span>";

			$tbl = '<br /><br /><strong>Box Contents:</strong><br /><br />
<table id="dataTable" class="stripe" width="100%">
<thead>
  <tr>
    <th width="35px" height="50px"></th>
    <th>ID</th>
    <th>Title</th>
    <th class="desktop">Creation Date</th>
    <th class="desktop">EPA Contact</th>
    <th class="desktop">Validation</th>
  </tr>
 </thead><tbody>
';
			foreach ($box_content as $info) {
			    $boxcontent_dbid = $info->id;
				$boxcontent_id = $info->folderdocinfofile_id;
				$boxcontent_title = $info->title;
				$boxcontent_title_truncated = (strlen($boxcontent_title) > 20) ? substr($boxcontent_title, 0, 20) . '...' : $boxcontent_title;
				$boxcontent_date = $info->date;
				$boxcontent_site = $info->site;
				$boxcontent_contact = $info->contact;
				
				$boxcontent_validation = $info->validation;
				
				if(Patt_Custom_Func::id_in_validation($info->folderdocinfofile_id,'folderfile') == 1) {
				    $boxcontent_validation_user = Patt_Custom_Func::get_validation_user($info->folderdocinfofile_id);
                    $boxcontent_validation_icon = '<span style="font-size: 1em; color: #008000;"><i class="fas fa-check-circle" aria-hidden="true" title="Validated"></i><span class="sr-only">Validated</span></span> ['. $boxcontent_validation_user .'] ';
                }
                else if (Patt_Custom_Func::id_in_validation($info->folderdocinfofile_id,'folderfile') != 1 && Patt_Custom_Func::id_in_rescan($info->folderdocinfofile_id,'folderfile') == 1) {
                    $boxcontent_validation_icon = '<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span> <span style="color: #A80000;"><strong>[Re-scan]</strong></span>';
                }
                else {
                    $boxcontent_validation_icon = '<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span>';
                }

				
				$folderfile_url = admin_url( 'admin.php?page=filedetails', 'https' );

if ($pagetype == 0) {
$tbl .= '
    <tr>
            <td width="35px" height="50px"></td>
            <td data-order="'. $boxcontent_dbid .'"><a href="'.$folderfile_url.'&id=' . $boxcontent_id . '" class="text_link" target="_blank">' . $boxcontent_id . '</a></td>
            <td>' . $boxcontent_title_truncated . '</td>
            <td>' . $boxcontent_date . '</td>
            <td>' . $boxcontent_contact . '</td>
            <td>' . $boxcontent_validation_icon . '</td>
            </tr>
            ';
} elseif ($pagetype == 1) {
$tbl .= '
    <tr>
            <td width="35px" height="50px"></td>
            <td data-order="'. $boxcontent_dbid .'">' . $boxcontent_id . '</td>
            <td>' . $boxcontent_title_truncated . '</td>
            <td>' . $boxcontent_date . '</td>
            <td>' . $boxcontent_contact . '</td>
            <td>' . $boxcontent_validation_icon . '</td>
            </tr>
            ';
} else {
    echo "Please pass a page type.";
}   
			}
			$tbl .= '</tbody></table>';

			echo $tbl;
			
} else {
 echo 'Box does not exist or is archived.';
}
			
//Determine if string is a folder/file ID     
    } elseif (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $barcode)){

//echo '<strong>Folder/File ID: '.$barcode.'<strong>';
//REVIEW
$folderfile_details = $wpdb->get_row(
		"SELECT 
	b.id as id,
	c.ticket_id as ticket_id,
	b.folderdocinfofile_id as folderdocinfofile_id,
    b.object_key
	
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo c ON c.id = b.box_id
    WHERE b.folderdocinfofile_id = '" . $barcode . "'"
	);

	$folderfile_folderdocinfofile_id = $folderfile_details->folderdocinfofile_id;
    
    $type = 'folderfile';

	$status_icon = '';
	
    if(Patt_Custom_Func::id_in_box_destroyed($barcode, $type) == 1) {
    $status_icon .= ' <span style="font-size: 1em; color: #a80000;"><i class="fas fa-ban" aria-hidden="true" title="Box Destroyed"></i><span class="sr-only">Box Destroyed</span></span>';
    }
    
    if(Patt_Custom_Func::id_in_unauthorized_destruction($barcode, $type) == 1) {
    $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" aria-hidden="true" title="Unauthorized Destruction"></i><span class="sr-only">Unauthorized Destruction</span></span>';
    }
    
    if(Patt_Custom_Func::id_in_damaged($barcode, $type) == 1) {
    $status_icon .= ' <span style="font-size: 1em; color: #000000;"><i class="fas fa-bolt" aria-hidden="true" title="Damaged"></i><span class="sr-only">Damaged</span></span>';
    }
    
     if(Patt_Custom_Func::id_in_freeze($barcode, $type) == 1) {
    $status_icon .= ' <span style="font-size: 1em; color: #005C7A;"><i class="fas fa-snowflake" aria-hidden="true" title="Freeze"></i><span class="sr-only">Freeze</span></span>';
    }

    if(Patt_Custom_Func::id_in_return($barcode,$type) == 1){
    $status_icon .= '<span style="font-size: 1em; color: #A80000;margin-left:4px;"><i class="fas fa-undo" aria-hidden="true" title="Declined"></i><span class="sr-only">Declined</span></span>';
    }
    
    if(Patt_Custom_Func::id_in_recall($barcode,$type) == 1){
    $status_icon .= '<span style="font-size: 1em; color: #000;margin-left:4px;"><i class="far fa-registered" aria-hidden="true" title="Recall"></i><span class="sr-only">Recall</span></span>';
    }
    
    //Validation check
    if(Patt_Custom_Func::id_in_validation($barcode,$type) == 1) {
	    $boxcontent_validation_user = Patt_Custom_Func::get_validation_user($barcode);
        $status_icon .= ' <span style="font-size: 1em; color: #008000;"><i class="fas fa-check-circle" aria-hidden="true" title="Validated"></i><span class="sr-only">Validated</span></span> ['. $boxcontent_validation_user .'] ';
    }
    else if (Patt_Custom_Func::id_in_validation($barcode,$type) != 1 && Patt_Custom_Func::id_in_rescan($barcode,$type) == 1) {
        $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span> <span style="color: #A80000;"><strong>[Re-scan]</strong></span>';
    }
    else {
        $status_icon .= ' <span style="font-size: 1em; color: #8b0000;"><i class="fas fa-times-circle" aria-hidden="true" title="Not Validated"></i><span class="sr-only">Not Validated</span></span>';
    }
   
$folderfile_url = admin_url( 'admin.php?page=filedetails', 'https' );

        //record schedule
    $box_rs = Patt_Custom_Func::get_record_schedule_by_id($folderfile_folderdocinfofile_id, $type);
    
    //program office
   $box_po = Patt_Custom_Func::get_program_office_by_id($folderfile_folderdocinfofile_id, $type);
    
//Checking to see if folder/file is associated with a request that both 1) exists 2) is not archived
if(!empty($folderfile_details->id) &&  Patt_Custom_Func::ticket_active($folderfile_details->ticket_id) == 1 && !empty($folderfile_details->object_key)) {	

if ($pagetype == 0) {
            echo "<strong>Folder/File ID:</strong> <a href='".$folderfile_url."&id=" . $folderfile_folderdocinfofile_id . "' target='_blank'>" . $folderfile_folderdocinfofile_id . "</a>" . $status_icon . "<br />";
} elseif ($pagetype == 1) {
            echo $folderfile_folderdocinfofile_id . $status_icon . "<br />";
} else {
    echo "Please pass a page type.";
}     
            // Calling Nuxeo metadata
            $get_object_id = $wpdb->get_row("SELECT object_key FROM " . $wpdb->prefix ."wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '" .$folderfile_folderdocinfofile_id ."'");
            $object_id = $get_object_id->object_key;

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => ARMS_API . '/api/v1/id/' . $object_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-NXproperties: *',
                'Authorization: '. ARMS_API_AUTH
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $decoded_json = json_decode($response, true);
            //Creator TODO Change for v1.3

            //Program Office
            $get_program_office = $wpdb->get_row("SELECT office_acronym FROM " . $wpdb->prefix ."wpsc_epa_program_office WHERE office_code = '" . $decoded_json['properties']['arms:program_office'][0] ."'");
            $program_office = $get_program_office->office_acronym;

            //Essential records
            if ($decoded_json['properties']['arms:essential_records'] == 0) {
                $essential_records = 'No';
            } else {
                $essential_records = 'Yes';
            }

            $access_restrictions = implode(', ', $decoded_json['properties']['arms:specific_access_restrictions']); 
            $use_restrictions = implode(', ', $decoded_json['properties']['arms:specific_use_restrictions']); 
            $addressee = implode(', ', $decoded_json['properties']['arms:addressee']);
            $rights_holder = implode(', ', $decoded_json['properties']['arms:rights_holder']);
            $program_area = $decoded_json['properties']['arms:partner_application'];
            $identifiers = $decoded_json['properties']['arms:identifiers'];

            $tags = $decoded_json['properties']['nxtag:tags'];
            $tags_arr = [];
            foreach($tags as $tag) {
                array_push($tags_arr, $tag['label']);
            }
            $tags_list = implode(', ', $tags_arr) . '</br>';
            
            if(!empty($decoded_json['properties']['arms:custodian'])) {

                $workforce_id_details = Patt_Custom_Func::workforce_id_to_json($decoded_json['properties']['arms:custodian']);
                //echo $workforce_id_details;
                $decoded_user_json = json_decode($workforce_id_details);

                echo "<strong>Custodian:</strong> " . $decoded_user_json->name . ", (". $decoded_user_json->email .")<br />";
            }

			if(!empty($program_office)) {
			    echo "<strong>Program Office:</strong> " . $program_office . "<br />";
			}
            
            if(!empty($decoded_json['properties']['arms:record_schedule'])) {
                echo "<strong>Record Schedule:</strong> " . $decoded_json['properties']['arms:record_schedule'] ."<br />";
            }
        
  			if (!empty($decoded_json['title'])) {
				echo "<strong>Title:</strong> " . $decoded_json['title'] . "<br />";
			}
			
			if(!empty($decoded_json['properties']['dc:description'])) {
			    echo "<strong>Description:</strong> " . $decoded_json['properties']['dc:description'] . "<br />";
			}

			if (!empty($decoded_json['properties']['dc:created'])) {
				echo "<strong>Creation Date:</strong> " . $decoded_json['properties']['dc:created'] . "<br />";
			}
			
			if(!empty($addressee)) {
			    echo "<strong>Addressee:</strong> " . $addressee . "<br />";
			}
			if (!empty($decoded_json['properties']['arms:record_type'])) {
				echo "<strong>Record Type:</strong> " . $decoded_json['properties']['arms:record_type'] . "<br />";
			}
			
			if (!empty($decoded_json['properties']['arms:close_date'])) {
				echo "<strong>Close Date:</strong> " . $decoded_json['properties']['arms:close_date'] . "<br />";
                echo "<strong>Disposition Date:</strong> " . $decoded_json['properties']['arms:disposition_date'] . "<br />";
			}
			
			if(!empty($access_restrictions)) {
                echo "<strong>Access Restrictions:</strong> Yes</br>";
			    echo "<strong>Specific Access Restrictions:</strong> " . $access_restrictions . "<br />";
			}
            else {
                echo "<strong>Access Restrictions:</strong> No</br>";
            }
			
			if(!empty($use_restrictions)) {
                echo "<strong>Use Restrictions:</strong> Yes<br />";
			    echo "<strong>Specific Use Restrictions:</strong> " . $use_restrictions . "<br />";
			}
            else {
                echo "<strong>Use Restrictions:</strong> No<br />";
            }

			if(!empty($rights_holder)) {
			    echo "<strong>Rights Holder:</strong> " . $rights_holder . "<br />";
			}
			
			if (!empty($decoded_json['properties']['arms:document_type'])) {
				echo "<strong>Document Type:</strong> " . $decoded_json['properties']['arms:document_type'] . "<br />";
			}

			if(!empty($program_area)) {
                echo "<strong><u>Partner Applications</u></strong> </br>";
                foreach($program_area as $key) {
                    $program_area_str .= '<strong>&nbsp&nbsp&nbsp&nbsp' . $key['name'] . ': </strong>';
                    foreach($key as $value) {
                        $program_area_str .= implode(", ", $value);
                    }
                    $program_area_str .= '</br>';
                }

                echo $program_area_str;
            }
			
			echo "<strong>Essential Record:</strong> " . $essential_records . "<br />";
			
            if(!empty($identifiers)) {
                echo "<strong><u>Identifiers</u></strong> </br>";
                foreach($identifiers as $key) {
                    $identifiers_str .= '<strong>&nbsp&nbsp&nbsp&nbsp' . $key['key'] . ': </strong>';
                    foreach($key as $value) {
                        $identifiers_str .= implode(", ", $value);
                    }
                    $identifiers_str .= '</br>';
                }

                echo $identifiers_str;
            }

			if(!empty($tags_list)) {
			    echo "<strong>Tags:</strong> " . $tags_list . "<br />";
			}

} else {
 echo "<strong>Folder/File ID:</strong> <a href='".$folderfile_url."&id=" . $folderfile_folderdocinfofile_id . "' target='_blank'>" . $folderfile_folderdocinfofile_id . "</a><br />";
 echo 'Folder/file does not exist, is archived or has not yet been transferred to ARMS.';
}

//FAIL for all other barcodes       
} else {

if($pagetype == 0) {
	echo 'Please enter a valid barcode.';
}
elseif($pagetype == 1) {
	echo 'Please enter a valid ID.';
}
else {
 echo 'Please enter a page type.'; 
}

}

} else {
   echo "Lookup not successful.";
}
?>