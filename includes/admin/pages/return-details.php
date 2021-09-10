<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

// $GLOBALS['id'] = $_GET['id'];
$GLOBALS['return_id'] = $_GET['id'];
$GLOBALS['pid'] = $_GET['pid'];
$subfolder_path = site_url( '', 'relative'); 

//TEST DATA 
//$GLOBALS['recall_id'] = 19;

// $prefix = 'RTN-';
$prefix = 'D-';
$str = $GLOBALS['return_id'];
if (substr($str, 0, strlen($prefix)) == $prefix) {
    $GLOBALS['return_id'] = substr($str, strlen($prefix));
    $clean_return_id = (int)$GLOBALS['return_id'];
} 

/*
echo 'Global: '.$GLOBALS['return_id'];
echo '<br>clean: '.$clean_return_id;
*/

$agent_permissions = $wpscfunction->get_current_agent_permissions();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$cancel_return_btn_css       = 'background-color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';

$cancel_return_btn_css = $action_default_btn_css;
$extend_expiration_return_btn_css = $action_default_btn_css;

?>


<div class="bootstrap-iso">
<?php

			/*
			* Get Data
			*/
		
			$where = [
				'return_id' => $GLOBALS['return_id']
			];
			$return_array = Patt_Custom_Func::get_return_data($where);
			
			//Added for servers running < PHP 7.3
			if (!function_exists('array_key_first')) {
			    function array_key_first(array $arr) {
			        foreach($arr as $key => $unused) {
			            return $key;
			        }
			        return NULL;
			    }
			}
			
			$return_array_key = array_key_first($return_array);	
			$return_obj = $return_array[$return_array_key];
			

			//
			// REAL DATA
			//
			$db_null = -99999;
			$db_empty = '';
			$blank_date = '0000-00-00 00:00:00';
			//$decline_status_pending_cancel = 'Decline Pending Cancel';
			$decline_status_pending_cancel = 'Received'; 
			$decline_cancelled_tag = get_term_by( 'slug', 'decline-cancelled', 'wppatt_return_statuses' );
			$decline_cancelled_term = $decline_cancelled_tag->term_id;
			
			
			
			$return_reason = $return_obj->reason;

			$comment = stripslashes($return_obj->comments);

			$tracking_url = Patt_Custom_Func::get_tracking_url($return_obj->tracking_number);
			$tracking_num = '<a href="' . $tracking_url.'" target="_blank">'.$return_obj->tracking_number.'</a>';

			$shipping_carrier = $return_obj->shipping_carrier;
			$shipping_status = $return_obj->status;
			$decline_status_id = $return_obj->return_status_id;
			$decline_status = Patt_Custom_Func::get_term_name_by_id( $decline_status_id ); 
			//$status_slug = get_term_by('ID', $decline_status_id );
			//$decline_status = get_term_by( 'id', 1023 ); 
			//$real_array_of_users = ($return_obj->user_id) ? $return_obj->user_id : [];
			$return_initiated_date = $return_obj->return_date;
			$return_shipped_date = '[Remove From Display]'; 
			$returned_date = $return_obj->return_receipt_date;
			$expiration_date = $return_obj->expiration_date;
			
			$box_list = ($return_obj->box_id) ? $return_obj->box_id : []; 
			$folderfile_list = ($return_obj->folderdoc_id) ? $return_obj->folderdoc_id : [];
			
			
			// Create single array with box and folderdocs
			$collated_box_folderfile_list = [];
			
			foreach( $box_list as $key=>$box ) {
				if( $box != $db_null ) {
					$collated_box_folderfile_list[] = $box;
				} else {
					$collated_box_folderfile_list[] = $folderfile_list[$key];
				}
					
			}		
			
			// Get data in the correct format for js 
 			//$searchByID = ['0000001-1','0000002-2-01-13', '0000002-3', '0000001-1-01-1', '0000001-3'];
 			$searchByID = $collated_box_folderfile_list; 			
 			$searchByIDjson = json_encode($searchByID);
 			$searchByID_alt = trim($searchByIDjson, '[');
 			$searchByID_alt = trim($searchByID_alt, ']'); 	
 			$searchByID_alt = str_replace('"', '', $searchByID_alt);
			
			
			// DEBUG	
			//
			//echo 'Current user: '.$current_user->ID.'<br>';
			//echo 'Current user term id: '.$assigned_agents[0];
			//echo "<br>Decline Object: <br><pre>";	
			//print_r($return_obj);
			//echo '</pre>';
			//echo 'Decline status slug: ' . $status_slug .'<br>';
			//echo 'Decline status: ' . $decline_status .'<br>';
			//print_r( $decline_status );
			//echo "count of array: ".count($recall_array);	
			//echo "<br>first index of array: ".array_key_first($recall_array);	
			//echo "<br>";	
			//echo "<br>Recall Array: <br>";
			//print_r($recall_array);
/*
			echo '<br><br>The lists: <br>';
			print_r($box_list);
			echo '<br>';
			print_r($folderfile_list);		
			echo '<br>'; 
			print_r($show_me);		
			echo '<br>';
			print_r($collated_box_folderfile_list);		
			echo '<br>';
*/

			
			
			// Make Status Pretty
			$status_term_id = $return_obj->return_status_id; //Not currently real. 
			$return_status_name = '';
			$status_background = get_term_meta($status_term_id, 'wppatt_return_status_background_color', true);
			$status_color = get_term_meta($status_term_id, 'wppatt_return_status_color', true);
			$status_style = "background-color:".$status_background.";color:".$status_color.";";
			//echo "<br>status style: ".$status_style."<br>";
			
			
			 // Register Box Status Taxonomy
			if( !taxonomy_exists('wppatt_return_statuses') ) {
				$args = array(
					'public' => false,
					'rewrite' => false
				);
				register_taxonomy( 'wppatt_return_statuses', 'wpsc_ticket', $args );
			}
			
			// Get List of Box Statuses
			$return_status_lut = get_terms([
				'taxonomy'   => 'wppatt_return_statuses',
				'hide_empty' => false,
				'orderby'    => 'meta_value_num',
				'order'    	 => 'ASC',
				'meta_query' => array('order_clause' => array('key' => 'wppatt_return_status_load_order')),
			]);
			
			foreach( $return_status_lut as $stat ) {
				if( $stat->term_id == $status_term_id ) {
					$return_status_name = $stat->name;
				}
			}
			
//			echo '<br>Return Statuses: <br>';
//			print_r($return_status_lut);
			
			
			
			// Tracking Info
			if ($tracking_num == $db_empty) {
				$tracking_num = "[ No Tracking Number ]";
			}
			
			
			// Get Users - should be failing
			$user_obj = get_user_by('id', $return_obj->user_id);
			$user_name = $user_obj->user_nicename;
			$user_email = $user_obj->user_email;

			$requestor = $user_name;
			$requestor_email = $user_email;
			$request_date = $return_obj->request_date;
			$received_date = $return_obj->request_receipt_date;
			// END should be failing
			
			
			// Set icons for shipping carriers
			$shipping_carrier_icon = '';
			if ($shipping_carrier == 'fedex' ) {
				$shipping_carrier_icon = '<i class="padding fab fa-fedex fa-lg"></i>';
			} elseif ($shipping_carrier == 'ups' ) {
				$shipping_carrier_icon = '<i class="padding fab fa-ups fa-lg"></i>';
			} elseif ($shipping_carrier == 'dhl' ) {
				$shipping_carrier_icon = '<i class="padding fab fa-dhl fa-lg"></i>';
			} elseif ($shipping_carrier == 'usps' ) {
				$shipping_carrier_icon = '<i class="padding fab fa-usps fa-lg"></i>';
			}
			
			
			// Update Date Format
			$return_initiated_date = date('m/d/Y', strtotime($return_initiated_date));
			if( $received_date == $blank_date ) {
				$received_date = '[Not Yet Received]';
			} else {
				$received_date = date('m/d/Y', strtotime($received_date));
			}
			
			if( $returned_date == $blank_date ) {
				$returned_date = '[Not Yet Declined]';
			} else {
				$returned_date = date('m/d/Y', strtotime($returned_date));
			}
			
			if( $expiration_date == $blank_date ) {
				$expiration_date = '[No Expiration Date]';
			} else {
				$expiration_date_old = $expiration_date;
				$expiration_date = date('m/d/Y', strtotime($expiration_date));
			}
			
			
			
				
			// Role and user checks for editing restriciton
			// Checks if current user is on this request.
/*
			$current_user_on_request = 0;
			foreach( $real_array_of_users as $user ) {
				if( $user == $current_user->ID ) {
					$current_user_on_request = 1;
				}
			}
*/
			
			// Cancel button restriction 
			// if admin 
			$user_can_cancel = 0;
			if ( $agent_permissions['label'] == 'Administrator' ) {
				$user_can_cancel = 1;
			}
			
			$status_cancelled = 0;
// 			if ( $status_term_id == 791 ) { //Old 785; now: 791
      if ( $status_term_id == $decline_cancelled_term ) { 
  			
				$status_cancelled = 1;
			}
			
			// Set alert for time to expiration
			if( $decline_status == $decline_status_pending_cancel ) {
				echo '<br>';				
				$t = time();
				$timestamp = explode(" ", $expiration_date_old);
				
				$date1 = date('Y-m-d', $t);
				$date2 = $timestamp[0];
				
				$diff = strtotime($date2) - strtotime($date1);
				
				$years = floor($diff / (365*60*60*24));
				$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
				$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
				
				// PATT Begin
				$expiration_message = '<span style="font-size: 1em;"><i aria-hidden="true" class="fas fa-hourglass-half" title="Expiration Alert"></i><span class="sr-only">Expiration Alert</span></span>';
				// PATT End
				
				$expiration_message .= ' <strong>Time until expiration:</strong> ';
				
				if ($days >= 0 && $year == 0 && $months == 1 ) {
					
					echo '<div class="alert alert-success" role="alert">';
				} elseif ($days > 14 && $year == 0 && $months == 0 ) {
					
					echo '<div class="alert alert-success" role="alert">';
				} elseif ($days <= 14 && $days > 7 && $year == 0 && $months == 0 ) {
					
					echo '<div class="alert alert-warning" role="alert">';
				} elseif ($days <= 7 && $year >= 0 && $months >= 0 ) {

					echo '<div class="alert alert-danger" role="alert">';
				} else {
					echo '<div class="alert alert-danger" role="alert">';
					$expiration_message = '<strong>This Decline has expired. Decline Cancelled. </strong>';
				} 
				
				echo $expiration_message;
				
				if($years == 1) {
				    $year_val = ' Year';
				} else {
				    $year_val = ' Years';
				}
				
				if($months == 1) {
				    $month_val = ' Month';
				} else {
				    $month_val = ' Months';
				}
				
				if($days == 1) {
				    $day_val = ' Day';
				} else {
				    $day_val = ' Days';
				}
				
				printf("%d $year_val, %d $month_val, %d $day_val\n", $years, $months, $days);
				
				echo '</div>';
				
			}
			
?>


  <h3>Decline Details</h3>

 <div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important;">

<div class="row wpsc_tl_action_bar" style="background-color:<?php echo $general_appearance['wpsc_action_bar_color']?> !important;">
  
	<div class="col-sm-12">
	    <!-- PATT Begin -->
<!--     	<button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=return';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i aria-hidden="true" class="fa fa-list-ul" title="Decline List"></i><span class="sr-only" >Decline List</span> Decline List</button> -->
    	<button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=decline';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i aria-hidden="true" class="fa fa-list-ul" title="Decline List"></i><span class="sr-only" >Decline List</span> Decline List</button>
        <!-- PATT End -->
<?php		
	if ( ($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager') )
	{
?>       	

    <!-- PATT Begin -->
    	<button type="button" id="wppatt_return_cancel" onclick="wppatt_cancel_return();" class="btn btn-sm wpsc_action_btn" style="<?php echo $cancel_return_btn_css?>"><i aria-hidden="true" class="fa fa-ban" title="Cancel Decline"></i><span class="sr-only">Cancel Decline</span> Cancel Decline</button>
    	<button type="button" id="wppatt_return_expiration_extend" onclick="wppatt_extend_return_expiration();" class="btn btn-sm wpsc_action_btn" style="<?php echo $extend_expiration_return_btn_css?>"><i aria-hidden="true" class="fa fa-ban" title="Extend Decline Expiration"></i><span class="sr-only">Extend Decline Expirtion</span> Extend Decline Expiration</button>
    <!-- PATT End -->

<?php		
	}
?>    

  	
  </div>
	
</div>

<div class="row" id="return_details_container" style="background-color:<?php echo $general_appearance['wpsc_bg_color']?> !important;color:<?php echo $general_appearance['wpsc_text_color']?> !important;">


  <div class="col-sm-8 col-md-9 wpsc_it_body">
<!--
    <div class="row wpsc_it_subject_widget">
	    <?php if($GLOBALS['return_id']) { ?>
    	<h3>[Return ID # <?php echo $GLOBALS['return_id']; ?>]</h3>
    </div>
-->
	
	<div id="return_details_sub_container">
		
<!-- 		<h3>Return ID # RTN-<?php echo $GLOBALS['return_id']; ?></h3> -->
		<h3>Decline ID # D-<?php echo $GLOBALS['return_id']; ?></h3>
		<br>
		<div id="search_status"></div>
		<div class="">
			<label class="wpsc_ct_field_label">Decline ID:</label>
			<span id="return_id" class=""><?php echo $GLOBALS['return_id']; ?></span>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Decline Reason: </label>
			<span id="return_reason" class=""><?php echo $return_reason; ?></span>
		</div>
		<div class="clear">
			<div class="">
				<label class="wpsc_ct_field_label">Comment: </label>
				<span id="comment" class=""><?php echo $comment; ?></span>
			</div>
		</div>

		<div class="">
			<label class="wpsc_ct_field_label">Shipping Tracking Number: </label>
			<span id="shipping_tracking" class=""><?php echo $shipping_carrier_icon; echo $tracking_num; ?></span>
			
			<?php				
				if( $agent_permissions['label'] == 'Agent' || $agent_permissions['label'] == 'Administrator' || ($agent_permissions['label'] == 'Manager') ) 
				{
					if( $status_cancelled == 0 ) 
					{
		
			?>
			
			<!-- PATT Begin -->
			<a href="#" onclick="wppatt_get_shipping_tracking_editor()"><i aria-hidden="true" class="fas fa-edit" title="Shipping Tracking Editor"></i><span class="sr-only">Shipping Tracking Editor</span></a>
			<!-- PATT End -->
			
			<?php
					}
				}
			?>
			
			<?php				
				if( $agent_permissions['label'] == 'Requester' || $agent_permissions['label'] == 'Requester Pallet' ) 
				{
					if( $decline_status == $decline_status_pending_cancel ) 
					{
		
			?>
			
			<!-- PATT Begin -->
			<a href="#" onclick="wppatt_get_shipping_tracking_editor()"><i aria-hidden="true" class="fas fa-edit" title="Shipping Tracking Editor"></i><span class="sr-only">Shipping Tracking Editor</span></a>
			<!-- PATT End -->
			
			<?php
					}
				}
			?>
			
			
			
		</div>
<!--
		<div class="">
			<label class="wpsc_ct_field_label">Shipping Carrier: </label>
			<span id="shipping_carrier" class=""><?php echo $shipping_carrier_icon; echo strtoupper($shipping_carrier); ?></span>
		</div>
-->
		<div class="">
			<label class="wpsc_ct_field_label">Status: </label>
			<span id="status" class="wpsp_admin_label" style="<?php echo $status_style ?>"><?php echo $return_status_name; ?></span>
		</div>
<!--
		<div class="requestor">
			<label class="wpsc_ct_field_label">Returning to Requestor(s): </label>
		</div>
		<div class="requestor">	
			<?php 
				$j = 0;
				foreach($real_array_of_users as $a_requestor) {
					$user_obj = get_user_by('id', $a_requestor);
					//print_r($user_obj);
					$user_name = $user_obj->user_nicename;
					$user_email = $user_obj->user_email;
					echo '<span id="return_requestor" class="requestor_name">'.$user_name.'</span>';
					echo '<span id="requestor_email" class="requestor_email">['.$user_email.']</span>';
					if( $j == 0 ) {
						
						// if user is requester && requestor on this Recall
						// OR admin
						if ( $agent_permissions['label'] == 'Administrator' || $current_user_on_request ) { 
							if( $status_cancelled == 0 ) 
							{
							    
							    // PATT Begin
								echo '<a href="#" onclick="wppatt_get_return_requestor_editor()"><i aria-hidden="true" class="fas fa-edit" title="Return Requestor Editor"></i><span class="sr-only">Return Requestor Editor</span></a>';
							    // PATT End
							    
							}
						}
					}
					echo '<br>';
					$j++;
				}
				
			?>
		</div>	
-->
		<div class="clear">
			<label class="wpsc_ct_field_label">Decline Initiated Date: </label>
			<span id="request_date" class=""><?php echo $return_initiated_date; ?></span>
			
		</div>
<!--
		<div class="">
			<label class="wpsc_ct_field_label">Return Shipped Date: </label>
			<span id="received_date" class=""><?php echo $return_shipped_date; ?></span>
			
		</div>
-->
		<div class="">
			<label class="wpsc_ct_field_label">Returned Date: </label>
			<span id="returned_date" class=""><?php echo $returned_date; ?></span>
		</div>
		
		<div class="">
			<label class="wpsc_ct_field_label">Expiration Date: </label>
			<span id="expiration_date" class=""><?php echo $expiration_date; ?></span>
		</div>
		
		
		

		
		
	</div>

	
	<div class="row create_ticket_fields_container">
			<label class="wpsc_ct_field_label" >
				Box IDs in Decline
			</label>
			
			<table id="tbl_templates_return_details" class="table table-striped table-bordered" cellspacing="5" cellpadding="5" width="100%">
		        <thead>
		            <tr>
					<?php		
					if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
					{
					?>
<!-- 					                <th class="datatable_header" scope="col" ></th> -->
					<?php
					}
					?>
		                <th class="datatable_header" scope="col" >Box ID</th>
		                <th class="datatable_header" scope="col" >Title</th>
		                <th class="datatable_header" scope="col" >Request ID</th>
		                <th class="datatable_header" scope="col" >Program Office</th>
<!-- 		                <th class="datatable_header" scope="col" >Validation</th> -->
		            </tr>
		        </thead>
		    </table>
			<br><br>
		</div>
	
	
	
	
	
	

<!-- Pop-up snippet start -->
<div id="wpsc_popup_background" style="display:none;"></div>
<div id="wpsc_popup_container" style="display:none;">
  <div class="bootstrap-iso">
    <div class="row">
      <div id="wpsc_popup" class="col-xs-10 col-xs-offset-1 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
        <div id="wpsc_popup_title" class="row"><h3>Modal Title</h3></div>
        <div id="wpsc_popup_body" class="row">I am body!</div>
        <div id="wpsc_popup_footer" class="row">
          <button type="button" class="btn wpsc_popup_close"><?php _e('Close','supportcandy');?></button>
          <button type="button" class="btn wpsc_popup_action"><?php _e('Save Changes','supportcandy');?></button>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Pop-up snippet end -->

<br />

<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>
<script>
 jQuery(document).ready(function() {
  
  // If a Requester (who did not submit the Request that is being Declined) tries to access page, redirect back to Dashboard
	// Need to add code to get the original requestor, then compare that to who is trying to access. 
/*
	let agent_permissions = '<?php echo $agent_permissions['label'] ?>';
	console.log({agent_permissions:agent_permissions});
	if( agent_permissions == 'Requester' || agent_permissions == 'Requester Pallet' ) {
		alert('You do not have access to this page. You will be redirected to the Decline Dashboard');
		location.href='admin.php?page=decline';
	}
*/
  
  
	dataTable = jQuery('#tbl_templates_return_details').DataTable({
		'processing': true,
		'serverSide': true,
		'stateSave': true,
		"bPaginate": false,
// 			"bInfo" : false,
/*
		'stateSaveParams': function(settings, data) {
			data.sg = jQuery('#searchGeneric').val();
			data.bid = jQuery('#searchByID').val();
			data.po = jQuery('#searchByProgramOffice').val();
			data.dc = jQuery('#searchByDigitizationCenter').val();
		},
		'stateLoadParams': function(settings, data) {
			jQuery('#searchGeneric').val(data.sg);
			jQuery('#searchByID').val(data.bid);
			jQuery('#searchByProgramOffice').val(data.po);
			jQuery('#searchByDigitizationCenter').val(data.dc);
		},
*/
		'serverMethod': 'post',
		'searching': false, // Remove default Search Control
		'ajax': {
			'url':'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/return_details_processing.php',
			'data': function(data){
				// Read values
				var returnids = '<?php echo $searchByID_alt; ?>';
				
				// Append to data
				data.searchByID = returnids;
				
			},
			'complete': function(response) {
				console.log('success!!');
				console.log(response);
				console.log(response.responseJSON.errors);	
				console.log(typeof(response.responseJSON.errors));										
				//error_alerts(response.responseJSON.errors);
				//Object.entries(response.responseJSON.errors).forEach(error_alerts);
			}
		},
// 		'lengthMenu': [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
		'lengthMenu': [[ 25, 50, 100, 500], [ 25, 50, 100, 500]],
		<?php		
		if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
		{
		?>
/*
			    'columnDefs': [	
		     {	
		        'targets': 0,
		        'checkboxes': {	
		           'selectRow': true	
		        }	
		     }
		  ],
		  'select': {	
		     'style': 'multi'	
		  },
		  'order': [[1, 'asc']],
*/
		<?php
		}
		?>
		'columns': [
		<?php		
		if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
		{
		?>
// 		   { data: 'box_id' }, 
		<?php
		}
		?>
		   { data: 'box_id_flag', 'class' : 'text_highlight' }, 
		   { data: 'title' },
		   { data: 'request_id', 'class' : 'text_highlight' },
		   { data: 'program_office' },
// 		   { data: 'validation' },
		]
	});
	
	
	
	
	
	
	 
	 
	 
	// Updates Admin Menu to highlight the submenu page that this page is under. 
	jQuery('#toplevel_page_wpsc-tickets').removeClass('wp-not-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets').addClass('wp-has-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets').addClass('wp-menu-open'); 
	jQuery('#toplevel_page_wpsc-tickets a:first').removeClass('wp-not-current-submenu');
	jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-has-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-menu-open');
	jQuery('#menu-dashboard').removeClass('current');
	jQuery('#menu-dashboard a:first').removeClass('current');
	 
	<?php
// 	if ($_GET['page'] == 'returncreate') {
	if ($_GET['page'] == 'declinecreate') {	
	?>
		 jQuery('.wp-submenu li:nth-child(6)').addClass('current');
	<?php
	}
	?>
	<?php
// 	if ($_GET['page'] == 'returndetails') {
	if ($_GET['page'] == 'declinedetails') {	
	?>
		 jQuery('.wp-submenu li:nth-child(6)').addClass('current');
	<?php
	}
	?>
	 
	 
	 
	 
	// Disable cancel if status not recalled. Or is user doesn't have role. 
	let current_status = jQuery( '#status' ).html();
	jQuery( '#wppatt_return_cancel' ).attr( 'disabled', 'disabled' );

	var user_can_cancel = <?php echo $user_can_cancel ?>;
	//if( jQuery( '#status' ).html() == 'Decline Initiated' && user_can_cancel ) {
	if( current_status == 'Decline Initiated' && user_can_cancel ) {	
		jQuery( '#wppatt_return_cancel' ).removeAttr( 'disabled' );	 
	}
	 
	// Disable Extend if status not decline. Or is user doesn't have role. 
	jQuery( '#wppatt_return_expiration_extend' ).attr( 'disabled', 'disabled' );

	//var user_can_cancel = <?php echo $user_can_cancel ?>;
// 	if( current_status == 'Decline Initiated' && current_status == 'Decline Shipped' && current_status == 'Received' ) {
//   if( current_status == 'Decline Initiated' || current_status == 'Decline Shipped' || current_status == 'Received' ) {
  if( current_status == 'Received' ) {
		jQuery( '#wppatt_return_expiration_extend' ).removeAttr( 'disabled' );	 
	} 


} );
    
    // NOT USED ANYMORE
		function wpsc_get_folderfile_editor(doc_id){
<?php
			$box_il_val = '';
			if ($box_il == 1) {
?>
		  wpsc_modal_open('Edit Folder Metadata');
<?php
			} else {
?>
		  wpsc_modal_open('Edit File Metadata');
<?php
			}
?>

		  var data = {
		    action: 'wpsc_get_folderfile_editor',
		    doc_id: doc_id
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });  
		}
</script>


  </div>
 

<?php
} else {

echo '<span style="padding-left: 10px">Please pass a valid Recall ID</span>';

}
?>
</div>
</div>
<!-- </div> -->


<!--
New Scripts for the Page
Decline Editing
-->

<script>
	
	var return_id = "<?php echo $GLOBALS['return_id'] ?>";
	var ticket_id = "<?php echo $ticket_id ?>";
	//recall_id = 3; //Test data
	//IMPLEMENT: check to ensure that valid recall_id.
	
	function wppatt_get_shipping_tracking_editor() {
		//alert('edit tracking');
		var shipping_tracking = jQuery('#shipping_tracking').text();
		var shipping_carrier = jQuery('#shipping_carrier').text();
		//alert('shipping tracking: '+shipping_tracking+' carrier: '+shipping_carrier);
		
		wpsc_modal_open('Edit Shipping Details');
		
		var data = {
		    action: 'wppatt_recall_get_shipping',
		    recall_id: return_id,
		    recall_ids: [return_id],
		    return_ids: [return_id],		    
		    shipping_tracking: shipping_tracking,
		    shipping_carrier: shipping_carrier,
		    ticket_id: ticket_id,
		    category: 'return',
		    from_page: 'return-details'
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
	}
	
	// NOT USED ANYMORE.
	function wppatt_get_return_requestor_editor() {
// 		alert('edit requestor');
		var return_requestor_array = [];
//		console.log('requestor array: ');
//		console.log(recall_requestor_array);
		
		jQuery('.requestor_name').each(function() {
			
			return_requestor_array.push(jQuery(this).text());
			//console.log(recall_requestor_array);
		});


		var requestor = jQuery('#return_requestor').text();

		wpsc_modal_open('Edit Requestor Details');
		var data = {
		    action: 'wppatt_recall_get_requestor',
		    recall_id: return_id,
		    ticket_id: ticket_id,
		    requestor: requestor
		    
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
	} 
	
	// NOT USED ANYMORE.
	function wppatt_get_date_editor(date_type) {
 		//alert('Date Type: '+date_type);
		//jQuery('.datepicker').datepicker();
		
		switch (date_type) {
			case 'request_date':
				var title = 'Request';
				var old_date = jQuery('#request_date').text();
				console.log("old date: "+old_date);
				break;
			case 'received_date':
				 var title = 'Received';
				 var old_date = jQuery('#received_date').text();
				break;
			case 'returned_date':
				 var title = 'Returned';
				 var old_date = jQuery('#returned_date').text();
				break;
			default:
				var title = 'false';
		}
		
// 		alert('Date Title: '+title);
		
		
		wpsc_modal_open('Edit '+title+' Date Details');
		var data = {
		    action: 'wppatt_recall_get_date',
		    recall_id: return_id,
		    date_type: date_type,
		    title: title,
		    old_date: old_date,
		    ticket_id: ticket_id
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
//		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
		
	}
	
	function wppatt_cancel_return(  ) {
		
		console.log('return_id: '+return_id);
		console.log('ticket id: '+ticket_id);
		
// 		wpsc_modal_open('Cancel Return: RTN-'+return_id);
		wpsc_modal_open('Cancel Decline: D-'+return_id);
		var data = {
		    action: 'wppatt_return_cancel',
		    return_id: return_id
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
//		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 

	}
	
	function wppatt_extend_return_expiration(  ) {
		
		console.log('return_id: '+return_id);
		console.log('ticket id: '+ticket_id);
		
// 		wpsc_modal_open('Cancel Return: RTN-'+return_id);
		wpsc_modal_open('Extend Decline: D-' + return_id + ' Expiration' );
		var data = {
		    action: 'wppatt_return_extend_expiration',
		    return_id: return_id
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
//		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 

	}
	

	
</script>


<!--
New styling for Decline page
To be added to a css file later
-->
<style type="text/css">

	#return_details_sub_container {
		padding: 0 20px;	
	}

	#return_details_sub_container div {
		margin-bottom: 10px;
		font-size: 15px;
	}
	
	#return_details_sub_container div a {
		margin-left: 5px;
	}
	
	#return_details_sub_container span {
		font-size: 15px;
		padding-left: 7px;
	}
	
	.calendar {
		display: inline-flex;
	}
	
	.requestor {
		float: left;
		display: inline-block;

	}
	
	.clear {
		clear: both;
	}
	
	.padding {
		padding-right: 5px;
	}
	
	.datatable_header {
		background-color: rgb(66, 73, 73) !important; 
		color: rgb(255, 255, 255) !important; 
	}
	
	.fa-snowflake {
		color: #005C7A;
	}
	
	.fa-flag {
		color: #B4081A;
	}
	
	.wpsc_loading_icon {
		margin-top: 0px !important;
	}
	
</style>
