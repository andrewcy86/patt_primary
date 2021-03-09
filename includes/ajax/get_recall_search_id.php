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

//new
$details_array2 = Patt_Custom_Func::item_in_recall( $search_id );

//$nothing = Patt_Custom_Func::get_default_digitization_center(238);
$box_file_details = Patt_Custom_Func::get_box_file_details_by_id( $search_id );
//$box_file_details = Patt_Custom_Func::get_box_file_details_by_id('0000288-1');
// $box_file_details = Patt_Custom_Func::get_box_file_details_by_id('0000001-2-01-10');
//print_r($box_file_details);


// Check if item is currently in Return 
$ret = Patt_Custom_Func::item_in_return( $search_id, 'Box', $subfolder_path );
if( $ret['return_id'] != null ) {
	$details_array2['error'] = 'Item in Return';
	$details_array2['error_message'] = $ret['item_error'];
	$details_array2['return_id'] = $ret['return_id'];
}

// Check if item is currently in Return 
/*
$ret = Patt_Custom_Func::item_in_return( $search_id, 'Folder/Doc', $subfolder_path );
if( $ret['return_id'] != null ) {
	$details_array2['error'] = 'Item in Return';
	$details_array2['error_message'] = $ret['item_error'];
	$details_array2['return_id'] = $ret['return_id'];
}
*/





// NEW METHOD
// Set variables

$data2 = array();
$num_of_records = 0;

if( $details_array2['search_error'] == false ) {
	//if($is_folder_search) {
	if( $details_array2['is_folder_search'] == 'true' ) {	
		$the_id = $details_array2['Folderdoc_Info_id'];
		$link_str_ff = "<a href='".$subfolder_path."/wp-admin/admin.php?pid=boxsearch&page=filedetails&id=".
								$details_array2['Folderdoc_Info_id']."' target='_blank' >".$details_array2['Folderdoc_Info_id']."</a>";
		$title = $details_array2['title'];
		
		$file_freeze = Patt_Custom_Func::id_in_freeze( $the_id, 'folderfile' );
		if( $file_freeze ) {
			$icons = $freeze_icon;
		}
		
	} else {
		$the_id = $details_array2['box_id']; 
		$link_str_ff = "<a href='".$subfolder_path."/wp-admin/admin.php?page=boxdetails&pid=requestdetails&id=".
								$details_array2['box_id']."' target='_blank' >".$details_array2['box_id']."</a>";
		
		$title = '[Boxes do not have Titles]';	
		
		$box_freeze = Patt_Custom_Func::id_in_freeze( $the_id, 'box' );
		if( $box_freeze ) {
			$details_array2['box_freeze'] = true;
			$icons = $freeze_icon;
		}
	}
	
	$num_of_records = count($searchByID);
	
	
	$data2[] = array(
			"box_id"=>$the_id, 
			"box_id_flag"=>$link_str_ff . $icons,
			"title"=>$title,
			"request_id"=>$details_array2['Record_Schedule_Number'], 
			"program_office"=>$details_array2['office_acronym'].': '.$details_array2['office_name']
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
	"details" => $details_array2,
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