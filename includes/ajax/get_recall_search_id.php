<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp-config.php');

global $current_user, $wpscfunction, $wpdb;
$subfolder_path = site_url( '', 'relative'); 
//$subfolder_path = '/wordpress3/'; 



// OLD
//$search_id = isset($_POST['label']) ? sanitize_text_field($_POST['label']) : '';
// $search_id = '0000020-2';
$search_id = '';
// NEW

if($_POST['searchByID']) {
	$searchByID = explode(',',$_POST['searchByID']);
	$search_id = $searchByID[0];
}

//$nothing = Patt_Custom_Func::get_default_digitization_center(238);
$box_file_details = Patt_Custom_Func::get_box_file_details_by_id($search_id);
//$box_file_details = Patt_Custom_Func::get_box_file_details_by_id('0000288-1');
// $box_file_details = Patt_Custom_Func::get_box_file_details_by_id('0000001-2-01-10');
//print_r($box_file_details);

$details_array = json_decode(json_encode($box_file_details), true);

// DEBUG
$details_array['searchByID'] = $searchByID;

// END

//if ( $details_array == false ) {
if ( $box_file_details == null ) {
	$details_array['search_error'] = true;
} else {
	$details_array['search_error'] = false;
}


// Set variables for search
$is_folder_search = array_key_exists('Folderdoc_Info_id',$details_array);
$details_array['in_recall'] = false;
$details_array['is_folder_search'] = $is_folder_search;
$details_array['error_message'] = '';
$db_null = -99999;
$icons = '';
$freeze_icon = ' <i class="fas fa-snowflake" title="Freeze"></i>';

//Get term_ids for recall status slugs
$status_recalled_term_id = Patt_Custom_Func::get_term_by_slug( 'recalled' );



	
//Get term_ids for Recall status slugs
$status_recall_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	 // 878
$status_recall_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' ); //734
$status_recall_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' ); //733

//Get term_ids for Box status slugs
$status_box_pending_term_id = Patt_Custom_Func::get_term_by_slug( 'pending' );	// 748
$status_box_scanning_preparation_term_id = Patt_Custom_Func::get_term_by_slug( 'scanning-preparation' );	// 672
$status_box_scanning_digitization_term_id = Patt_Custom_Func::get_term_by_slug( 'scanning-digitization' );	// 671	
$status_box_q_a_term_id = Patt_Custom_Func::get_term_by_slug( 'q-a' );	// 65	
$status_box_digitized_not_validated_term_id = Patt_Custom_Func::get_term_by_slug( 'closed' );	// 6	
$status_box_ingestion_term_id = Patt_Custom_Func::get_term_by_slug( 'ingestion' );	// 673	
$status_box_validation_term_id = Patt_Custom_Func::get_term_by_slug( 'verification' );	// 674	
$status_box_rescan_term_id = Patt_Custom_Func::get_term_by_slug( 're-scan' );	// 743	
$status_box_completed_term_id = Patt_Custom_Func::get_term_by_slug( 'completed' );	// 66	
$status_box_destruction_approval_term_id = Patt_Custom_Func::get_term_by_slug( 'destruction-approval' );	// 68	
$status_box_destruction_of_source_term_id = Patt_Custom_Func::get_term_by_slug( 'destruction-of-source' );
$status_box_dispositioned_term_id = Patt_Custom_Func::get_term_by_slug( 'stored' );	// 67	// dispositioned
$status_box_comp_disp_term_id = Patt_Custom_Func::get_term_by_slug( 'completed-dispositioned' ); 
$status_box_waiting_on_rlo_term_id = Patt_Custom_Func::get_term_by_slug( 'waiting-on-rlo' ); 
$status_box_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'cancelled' ); 


// Check if item is currently in recall database 

$recall_rows = $wpdb->get_results(
'SELECT 
	' . $wpdb->prefix . 'wpsc_epa_recallrequest.id as id, 
    ' . $wpdb->prefix . 'wpsc_epa_recallrequest.recall_id as recall_id,	
	' . $wpdb->prefix . 'wpsc_epa_recallrequest.box_id as box_id, 
	boxinfo.box_id as display_box_id,
	boxinfo.box_destroyed as box_destroyed,
    folderinfo.folderdocinfo_id as dispay_folder_id,
	' . $wpdb->prefix . 'wpsc_epa_recallrequest.folderdoc_id as folderdoc_id,
	' . $wpdb->prefix . 'wpsc_epa_recallrequest.recall_status_id as status_id
FROM 
	' . $wpdb->prefix . 'wpsc_epa_recallrequest 
	INNER JOIN 
		' . $wpdb->prefix . 'wpsc_epa_boxinfo AS boxinfo 
	ON (
                ' . $wpdb->prefix . 'wpsc_epa_recallrequest.box_id = boxinfo.id
	)
        INNER JOIN 
		' . $wpdb->prefix . 'wpsc_epa_folderdocinfo AS folderinfo 
	ON (
                ' . $wpdb->prefix . 'wpsc_epa_recallrequest.folderdoc_id = folderinfo.id
	)
 ORDER BY id ASC' );
 

// Box Search  
if( !$is_folder_search ) {
	
	
	

	
	// if Box Destroyed, No recall allowed
	if( $details_array['box_destroyed'] == true ) {
		$details_array['error_message'] = 'Box Destroyed';
	} else { // if box not destroyed, check if it's been recalled
		
		// Search through all Recalls to determine if box has been recalled.
		foreach ($recall_rows as $item) {
		
			// Is Box Recalled?
// 			if( $details_array['box_id'] == $item->display_box_id && $item->folderdoc_id == $db_null && ($item->status_id != 733 && $item->status_id != 734 && $item->status_id != 878) ) {
			if( $details_array['box_id'] == $item->display_box_id && $item->folderdoc_id == $db_null && ($item->status_id != $status_recall_complete_term_id && $item->status_id != $status_recall_cancelled_term_id && $item->status_id != $status_recall_denied_term_id) ) {	
				$details_array['error'] = 'Found: '.$item->status_id.' - '.$details_array['error'];
				$details_array['in_recall'] = true;
				$details_array['in_recall_where'] = $item->recall_id;
				$details_array['error_message'] = 'Box Already Recalled';
				break;
			}
		}
		

		
		
		// if not recalled, check all folder/files inside of box for Destroyed Files
		if( $details_array['in_recall'] == false ) {
			
			if( $details_array['Box_id_FK'] == '' || $details_array['Box_id_FK'] == null ) {
				$details_array['Box_id_FK'] = 'null';
			}
			
			
			
			$folder_rows = $wpdb->get_results( $wpdb->prepare(
				'SELECT 
					folderinfo.id as id, 
				    fdif.folderdocinfofile_id as display_folderdocinfo_id,
				    fdif.unauthorized_destruction as unauthorized_destruction
				FROM 
					' . $wpdb->prefix . 'wpsc_epa_folderdocinfo as folderinfo
				JOIN 
                    ' . $wpdb->prefix . 'wpsc_epa_folderdocinfo_files as fdif ON fdif.folderdocinfo_id = folderinfo.id
				WHERE
				    folderinfo.box_id = ' . $details_array['Box_id_FK'] . '
				   AND
				    fdif.unauthorized_destruction = 1
				ORDER BY id ASC'
			));
			
/*          // OLD: before changing DB structure to move unauthorized_destruction to fdi_files
			$folder_rows = $wpdb->get_results(
				'SELECT 
					folderinfo.id as id, 
				    folderinfo.folderdocinfo_id as display_folderdocinfo_id,
				    folderinfo.unauthorized_destruction as unauthorized_destruction
				FROM 
					wpqa_wpsc_epa_folderdocinfo as folderinfo
				WHERE
				    folderinfo.box_id = '. $details_array['Box_id_FK'] .'
				   AND
				    unauthorized_destruction = 1
				ORDER BY id ASC'
			);
*/
			
			if( $folder_rows ) {
				$list_of_destroyed_files = [];
		
				foreach( $folder_rows as $folder ) {
					$list_of_destroyed_files[] = $folder->display_folderdocinfo_id;
				}
				
				$details_array['error_message'] = 'Box Contains Destroyed Files';
				$details_array['error'] = 'Box Contains Destroyed Files';
				$details_array['destroyed_files'] = $list_of_destroyed_files;	
			}	
		}
		
		
		// Check the box status to determine if box is recallable 
		switch( $details_array['box_status'] ) {

// 			case 748: // Box Status: Pending
			case $status_box_pending_term_id: // Box Status: Pending
				//$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed until the Box status enters Scanning/Digitization.';
				$details_array['box_status_name'] = 'Pending';
				break;
// 			case 672: // Box Status: Scanning Preperation
			case $status_box_scanning_preparation_term_id: // Box Status: Scanning Preperation
				//$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed until the Box status enters Scanning/Digitization.';
				$details_array['box_status_name'] = 'Scanning Preperation';
				break;
// 			case 671: // Box Status: Scanning/Digitization
			case $status_box_scanning_digitization_term_id: // Box Status: Scanning/Digitization
				//$details_array['error'] = '';
				//$details_array['error_message'] = '';
				$details_array['box_status_name'] = 'Scanning/Digitization';
				break;
// 			case 65: // Box Status: QA/QC
			case $status_box_q_a_term_id: // Box Status: QA/QC
				//$details_array['error'] = '';
				//$details_array['error_message'] = '';
				$details_array['box_status_name'] = 'QA/QC';
				break;
// 			case 6: // Box Status: Digitized - Not Validated
			case $status_box_digitized_not_validated_term_id: // Box Status: Digitized - Not Validated
				//$details_array['error'] = '';
				//$details_array['error_message'] = '';
				$details_array['box_status_name'] = 'Digitized - Not Validated';
				break;
// 			case 673: // Box Status: Ingestion
			case $status_box_ingestion_term_id: // Box Status: Ingestion
				//$details_array['error'] = '';
				//$details_array['error_message'] = '';
				$details_array['box_status_name'] = 'Ingestion';
				break;
// 			case 674: // Box Status: Validation
			case $status_box_validation_term_id: // Box Status: Validation
				//$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed for Boxes in Validation to Re-Scan statuses.';
				$details_array['box_status_name'] = 'Validation';
				break;
// 			case 743: // Box Status: Re-scan
			case $status_box_rescan_term_id: // Box Status: Re-scan
				//$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed for Boxes in Validation to Re-Scan statuses.';
				$details_array['box_status_name'] = 'Re-scan';
				break;
// 			case 66: // Box Status: Completed Permanent Records
			case $status_box_completed_term_id: // Box Status: Completed
				//$details_array['error'] = '';
				//$details_array['error_message'] = '';
				//$details_array['box_status_name'] = 'Completed';
				$details_array['box_status_name'] = 'Completed Permanent Records';
				break;
// 			case 68: // Box Status: Destruction Approval // Destruction Approved
			case $status_box_destruction_approval_term_id: // Box Status: Destruction Approval
				//$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed in the Destruction Approval status.';
				//$details_array['box_status_name'] = 'Destruction Approval';
				$details_array['box_status_name'] = 'Destruction Approved';
				break;
			case $status_box_destruction_of_source_term_id: // Box Status: Destruction Approval
				//$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed in the Destruction of Source status.';
				$details_array['box_status_name'] = 'Destruction of Source';
				break;	
// 			case 67: // Box Status: Dispositioned
			case $status_box_dispositioned_term_id: // Box Status: Dispositioned
				$details_array['error'] = 'Box Status Not Recallable';
				$details_array['error_message'] = 'Recalls are not allowed in the Dispositioned status. (No longer a box status)';
				$details_array['box_status_name'] = 'Dispositioned';
				break;
			case $status_box_comp_disp_term_id: // Box Status: Completed/Dispositioned
				$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed in the Completed/Dispositioned status.';
				$details_array['box_status_name'] = 'Completed/Dispositioned';
				break;
			case $status_box_waiting_on_rlo_term_id: // Box Status: Waiting on RLO
				$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed in the Waiting on RLO status.';
				$details_array['box_status_name'] = 'Waiting on RLO';
				break;
			case $status_box_cancelled_term_id: // Box Status: Cancelled
				$details_array['error'] = 'Box Status Not Recallable';
				//$details_array['error_message'] = 'Recalls are not allowed in the Cancelled status.';
				$details_array['box_status_name'] = 'Cancelled';
				break;			
			

		}
		
		// Check if item is currently in Return 
		$ret = Patt_Custom_Func::item_in_return($search_id, 'Box', $subfolder_path);
		if( $ret['return_id'] != null ) {
			$details_array['error'] = 'Item in Return';
			$details_array['error_message'] = $ret['item_error'];
			$details_array['return_id'] = $ret['return_id'];
			//$type = 'Box' or 'Folder/Doc';	
		}
	
	}
} else { // Folder/File Search
	
	// if Folder / File  Unauthorized Destruction, No recall allowed
	if( $details_array['unauthorized_destruction'] == true ) {
		$details_array['error_message'] = 'Folder/File Unauthorized Destruction';
	} // if Folder/File not destroyed, check if it's been recalled 
	elseif ( $details_array['in_recall'] == false ) {
		foreach( $recall_rows as $item ) {
// 			if ($details_array['Folderdoc_Info_id'] == $item->dispay_folder_id && ($item->status_id != 733 && $item->status_id != 734 && $item->status_id != 878)) {
			if ($details_array['Folderdoc_Info_id'] == $item->dispay_folder_id && ($item->status_id != $status_recall_complete_term_id && $item->status_id != $status_recall_cancelled_term_id && $item->status_id != $status_recall_denied_term_id)) {	
				$details_array['error'] = 'Found: '.$item->dispay_folder_id.' - '.$details_array['error'];
				$details_array['in_recall'] = true;
				$details_array['in_recall_where'] = $item->recall_id;
				$details_array['error_message'] = 'Folder/File already Recalled';
			}
		}
	} 
	
	// if not destoryed && not recalled, check if containing box has been recalled
	if ( $details_array['in_recall'] == false && $details_array['error_message'] != 'Folder/File Unauthorized Destruction' ) { 
		// Search through all Recalls to determine if box has been recalled.
		foreach ($recall_rows as $item) {
			$details_array['Test'] = $item;
			// Is Box Recalled?
// 			if( $details_array['Box_id_FK'] == $item->box_id && $item->folderdoc_id == $db_null && ($item->status_id != 733 && $item->status_id != 734 && $item->status_id != 878)) {
			if( $details_array['Box_id_FK'] == $item->box_id && $item->folderdoc_id == $db_null && ($item->status_id != $status_recall_complete_term_id && $item->status_id != $status_recall_cancelled_term_id && $item->status_id != $status_recall_denied_term_id)) {	
				$details_array['error'] = 'Found: '.$item->status_id.' - '.$details_array['error'];
				$details_array['in_recall'] = true;
				$details_array['in_recall_where'] = $item->recall_id;
				$details_array['error_message'] = 'Folder/File in Recalled Box';
				break;
			}
		}
		
	}
	
	// Check the status of the containing box to determine if it's recallable
	switch( $details_array['box_status'] ) {
		
// 		case 748: // Box Status: Pending
		case $status_box_pending_term_id: // Box Status: Pending
			//$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed until the Box status enters Scanning/Digitization.';
			$details_array['box_status_name'] = 'Pending';
			break;
// 		case 672: // Box Status: Scanning Preperation
		case $status_box_scanning_preparation_term_id: // Box Status: Scanning Preperation
			//$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed until the Box status enters Scanning/Digitization.';
			$details_array['box_status_name'] = 'Scanning Preperation';
			break;
// 		case 671: // Box Status: Scanning/Digitization
		case $status_box_scanning_digitization_term_id: // Box Status: Scanning/Digitization
			//$details_array['error'] = '';
			//$details_array['error_message'] = '';
			$details_array['box_status_name'] = 'Scanning/Digitization';
			break;
// 		case 65: // Box Status: QA/QC
		case $status_box_q_a_term_id: // Box Status: QA/QC
			//$details_array['error'] = '';
			//$details_array['error_message'] = '';
			$details_array['box_status_name'] = 'QA/QC';
			break;
// 		case 6: // Box Status: Digitized - Not Validated
		case $status_box_digitized_not_validated_term_id: // Box Status: Digitized - Not Validated
			//$details_array['error'] = '';
			//$details_array['error_message'] = '';
			$details_array['box_status_name'] = 'Digitized - Not Validated';
			break;
// 		case 673: // Box Status: Ingestion
		case $status_box_ingestion_term_id: // Box Status: Ingestion
			//$details_array['error'] = '';
			//$details_array['error_message'] = '';
			$details_array['box_status_name'] = 'Ingestion';
			break;
// 		case 674: // Box Status: Validation
		case $status_box_validation_term_id: // Box Status: Validation
			//$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed for Boxes in Validation to Re-Scan statuses.';
			$details_array['box_status_name'] = 'Validation';
			break;
// 		case 743: // Box Status: Re-scan
		case $status_box_rescan_term_id: // Box Status: Re-scan
			//$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed for Boxes in Validation to Re-Scan statuses.';
			$details_array['box_status_name'] = 'Re-scan';
			break;
// 		case 66: // Box Status: Completed Permanent Records
		case $status_box_completed_term_id: // Box Status: Completed
			//$details_array['error'] = '';
			//$details_array['error_message'] = '';
			$details_array['box_status_name'] = 'Completed Permanent Records';
			break;
// 		case 68: // Box Status: Destruction Approval  // Destruction Approved
		case $status_box_destruction_approval_term_id: // Box Status: Destruction Approval
			//$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed in the Destruction approval status.';
			//$details_array['box_status_name'] = 'Destruction Approval';
			$details_array['box_status_name'] = 'Destruction Approved';
			break;
		case $status_box_destruction_of_source_term_id: // Box Status: Destruction Approval
			//$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed in the Destruction of Source status.';
			$details_array['box_status_name'] = 'Destruction of Source';
			break;	
// 		case 67: // Box Status: Dispositioned
		case $status_box_dispositioned_term_id: // Box Status: Dispositioned
			$details_array['error'] = 'Containing Box Status Not Recallable';
			$details_array['error_message'] = 'Recalls are not allowed in the Dispositioned status. (No longer a box status)';
			$details_array['box_status_name'] = 'Dispositioned';
			break;
		case $status_box_comp_disp_term_id: // Box Status: Completed/Dispositioned
			$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed in the Dispositioned status.';
			$details_array['box_status_name'] = 'Completed/Dispositioned';
			break;		
		case $status_box_waiting_on_rlo_term_id: // Box Status: Waiting on RLO
			$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed in the Waiting on RLO status.';
			$details_array['box_status_name'] = 'Waiting on RLO';
			break;
		case $status_box_cancelled_term_id: // Box Status: Cancelled
			$details_array['error'] = 'Containing Box Status Not Recallable';
			//$details_array['error_message'] = 'Recalls are not allowed in the Cancelled status.';
			$details_array['box_status_name'] = 'Cancelled';
			break;
		
	}

	// Check if item is currently in Return 
	$ret = Patt_Custom_Func::item_in_return($search_id, 'Folder/Doc', $subfolder_path);
	//$details_array['return_id'] = $ret['return_id'];
	if( $ret['return_id'] != null ) {
		$details_array['error'] = 'Item in Return';
		$details_array['error_message'] = $ret['item_error'];
		$details_array['return_id'] = $ret['return_id'];
	}

	
}




// OLD METHOD


// NEW METHOD
// Set variables

$data2 = array();
$num_of_records = 0;

if( $details_array['search_error'] == false ) {
	if($is_folder_search) {
		$the_id = $details_array['Folderdoc_Info_id'];
		$link_str_ff = "<a href='".$subfolder_path."/wp-admin/admin.php?pid=boxsearch&page=filedetails&id=".
								$details_array['Folderdoc_Info_id']."' target='_blank' >".$details_array['Folderdoc_Info_id']."</a>";
		$title = $details_array['title'];
		
		$file_freeze = Patt_Custom_Func::id_in_freeze( $the_id, 'folderfile' );
		if( $file_freeze ) {
			$icons = $freeze_icon;
		}
		
	} else {
		$the_id = $details_array['box_id'];
		$link_str_ff = "<a href='".$subfolder_path."/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=".
								$details_array['box_id']."' target='_blank' >".$details_array['box_id']."</a>";
		$title = '[Boxes do not have Titles]';	
		
		$box_freeze = Patt_Custom_Func::id_in_freeze( $the_id, 'box' );
		if( $box_freeze ) {
			$details_array['box_freeze'] = true;
			$icons = $freeze_icon;
		}
	}
	
	$num_of_records = count($searchByID);
	
	
	$data2[] = array(
			"box_id"=>$the_id, 
			"box_id_flag"=>$link_str_ff . $icons,
			"title"=>$title,
			"request_id"=>$details_array['Record_Schedule_Number'], 
			"program_office"=>$details_array['office_acronym'].': '.$details_array['office_name']
// 			"validation"=>'another thing'	      
		);		
}


	



//$data2 = [];



	

$response2 = array(
	"draw" => intval($draw),
	"iTotalRecords" => $num_of_records,
	"iTotalDisplayRecords" => $num_of_records,
	"aaData" => $data2,
	"errors" => 'errors',
	"alerts" => $nothing,
	"details" => $details_array,
	"test" => '',
	"search_id" => $search_id,
	"searchByID" => $searchByID
);
	
/*
$response2 = array(
  "draw" => intval($draw),
  "iTotalRecords" => count($searchByID),
  "iTotalDisplayRecords" => count($searchByID),
  "aaData" => $data2,
  "errors" => $error_array,
  "alerts" => $return_check,
  "details" => $details_array
);
*/

/*
$data2 = array();

$response2 = array(
  "aaData" => $data2
);
*/

echo json_encode($response2);