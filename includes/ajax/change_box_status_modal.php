
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// set default filter for agents and customers //not true
/*
global $current_user, $wpscfunction;
if (!$current_user->ID) die();
*/

global $current_user, $wpscfunction, $wpdb;
if (!($current_user->ID && $current_user->has_cap('wpsc_agent'))) {
	exit;
}

$subfolder_path = site_url( '', 'relative'); 
$save_enabled = true;





//
// Get items
//

$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

$item_ids = $_REQUEST['item_ids']; 

$agent_permissions = $wpscfunction->get_current_agent_permissions(); 
$agent_permissions['label']; 
$agent_type = $agent_permissions['label']; // Administrator, Agent, Manager

//$agent_type = isset($_POST['agent_type']) ? sanitize_text_field($_POST['agent_type']) : ''; // Administrator, Agent

//$is_single_item = isset($_POST['is_single_item']) ? sanitize_text_field($_POST['is_single_item']) : '';
$num_of_items = count($item_ids);
$is_single_item = ($num_of_items == 1) ? true : false;
$old_status = Patt_Custom_Func::get_box_file_details_by_id( $item_ids[0] )->box_status;




// New Section - Start

// Single
// check status
// add all but NEXT status to $restricted_status_list.

// Multi-Select
// Array of all item status
// if all same status, allow change. 
// if one status off, do not allow status change. 

// Add if (!Administrator) to this new code. 

// Check ticket status and restrict based on status. 
$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses');
$tabled_request_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses');
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses');
$comp_disp_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses');

$new_request_term = $new_request_tag->term_id;
$tabled_request_term = $tabled_request_tag->term_id;
$initial_review_rejected_term = $initial_review_rejected_tag->term_id;
$cancelled_term = $cancelled_tag->term_id;
$comp_disp_term = $comp_disp_tag->term_id;

$request_restrictions = [ $new_request_term, $tabled_request_term, $initial_review_rejected_term, $cancelled_term, $comp_disp_term ];


// get ticket_id from items.
// check if different ticket_ids (error check)
// if status matches, restrict. 
// get in js 
// set alert. 
$ticket_arr = [];

// get array of ticket_ids
foreach( $item_ids as $key => $item ) {
  $where = [ 'box_folder_file_id' => $item ];
  $ticket_res = Patt_Custom_Func::get_ticket_id_from_box_folder_file( $where );          
  $ticket_arr[] = $ticket_res[ 'ticket_id' ];
}

// make unique
$ticket_arr_unique = array_unique( $ticket_arr );
$ticket_status_arr = [];

// default state
$request_restriction_bool = false;

// get status of tickets into an array
foreach( $ticket_arr_unique as $ticket ) {
  $ticket_status_arr[] = Patt_Custom_Func::get_ticket_status( $ticket );
}

// compare the statuses of the tickets and restrict if in a restricted status.
foreach( $ticket_status_arr as $status ) {
  if( in_array($status, $request_restrictions) ) {
    $request_restriction_bool = true;
  }
}



// Multi: Check if all statuses the same. 
// if not, restrict saving

if( !$is_single_item ) {
	$items_status_array = [];
	foreach( $item_ids as $item ) {
		$items_status_array[$item] = Patt_Custom_Func::get_box_file_details_by_id($item)->box_status;   
	}
	
	$item_count_array = array_count_values($items_status_array);
	$num_of_statuses = count($item_count_array);
	
	// If multiple status exist. Can only move status if all status are the same. 
	if( $num_of_statuses > 1 ) {
		$save_enabled = false;
		$restriction_reason_C5 = 'The statuses of the selected Boxes are not all the same. No Status Selectable.';
	}
}

// New Section - End


// If save enabled, 
// get all restrictions on the boxes listed.
if( $save_enabled ) {

	$status_list = Patt_Custom_Func::get_restricted_box_status_list_2( $item_ids, $agent_type ); 
	$box_statuses = $status_list[ 'box_statuses' ];
	$restriction_reason = $status_list[ 'restriction_reason' ];
	$restricted_reason_array = $status_list[ 'restricted_reason_array' ];
	
	
	
} else {
	
	// If save not enabled, list the restriction reason as: C5 - statuses of the selected Boxes are not all the same
	$box_statuses = [];
	$restriction_reason = $restriction_reason_C5;
	
	foreach( $item_ids as $key => $item ) {
	  $restricted_reason_array[ $item ] = $restriction_reason_C5;
  }
}

$all_statuses_list = Patt_Custom_Func::get_all_status();
			



ob_start();
// DEBUG
/*
echo '<br>';
echo 'type: '.$type.'<br>';
echo 'request_restriction_bool: '. $request_restriction_bool .'.<br>';
$request_restriction_bool_text = $request_restriction_bool ? 'true' : 'false';
echo 'request_restriction_bool_text: '. $request_restriction_bool_text .'.<br>';
echo 'item ids: <br>';
print_r($item_ids);
echo '<br>';
echo 'request_restrictions: <br><pre>';
print_r( $request_restrictions ); 
echo '</pre><br>';
echo 'ticket_status_arr: <br><pre>';
print_r( $ticket_status_arr ); 
echo '</pre><br>';
echo 'old status: '.$old_status.'<br>';
echo 'agent type: '.$agent_type.'<br>';
echo 'item status array: <br>';
print_r($items_status_array); // blank if single
echo '<br>';
echo 'item count array: <br>';
print_r($item_count_array); 
echo 'box_statuses: <br><pre>';
// print_r($box_statuses); Patt_Custom_Func::get_all_status()
print_r(Patt_Custom_Func::get_all_status()); 
echo '</pre><br>';
echo '$num_of_statuses: '.$num_of_statuses.'<br>';

echo 'restricted_status_list: <br><pre>';
// print_r($status_list['debug_restricted_status_list']);
print_r($status_list['restricted_status_list']);

echo '</pre><br>';

echo 'box_statuses: <br><pre>';
print_r( $box_statuses );
echo '</pre><br>';

echo 'restriction_reason: <br><pre>';
print_r( $restriction_reason );
echo '</pre><br>';

echo 'status_list: <br><pre>';
print_r( $status_list );
echo '</pre><br>';

echo 'restricted_reason_array: <br><pre>';
print_r( $restricted_reason_array );
echo '</pre><br>';
*/

	
	

	

// Debug
/*
echo '<br>box obj<br><pre>';	
print_r($box_obj);
echo '</pre><br>';
*/
/*
echo 'Condition 1: <br>';
echo 'All Unassigned? ';
echo ($all_unassigned) ? 'true' : 'false';
echo '<br> Restricted Reason: '.$restriction_reason.'<br>';
echo 'Restricted Status List: <pre>';
print_r($restricted_status_list);
echo '</pre>';

echo 'Condition 2: <br>';
echo 'Validated? ';
echo ($validated) ? 'true' : 'false';
echo '<br> Restricted Reason: '.$restriction_reason.'<br>';
echo 'Restricted Status List: <pre>';
print_r($restricted_status_list);
echo '</pre>';

echo 'Condition 3: <br>';
echo 'Box Destruction Approval? ';
echo ($box_destruction_approval->destruction_approval) ? 'true' : 'false';
echo '<br>Second thoughts: '.$box_destruction_approval->destruction_approval;
echo '<br> Restricted Reason: '.$restriction_reason.'<br>';
echo 'Restricted Status List: <pre>';
print_r($restricted_status_list);
echo '</pre>';

echo 'Condition 4: <br>';
echo 'Ticket Status? ';
print_r( $ticket_status);
echo '<br> Restricted Reason: '.$restriction_reason.'<br>';
echo 'Restricted Status List: <pre>';
print_r($restricted_status_list);
echo '</pre>';


echo "<br>Assign Agents for: ".$type;
echo "<br>";
print_r($item_ids);
echo "<br>Box Statuses: <pre>";
print_r($box_statuses);
echo "</pre><br>";
echo "current status: ";
print_r( Patt_Custom_Func::get_box_file_details_by_id($item_ids[0]));
//print_r($term_id_array);
echo "<br>";
//echo "Get user status data: <br>";
//print_r($get_user_status_data);
echo "<br>Actual status data: <br>";
print_r($status_agent_array);
echo "<br>";
echo "Is single: ".$is_single_item;
echo "<br>";
echo "<br>Status list Assignable: <br>";
print_r($status_list_assignable);
*/
?>


<div id='request_alert_status' class='col-lg-12'></div> 
<div id='alert_status' class='col-lg-12'></div> 

<div class="row">
	<div class="col-lg-2">
		<label class="wpsc_ct_field_label">Box Status: </label>
	</div>
	
	<div class="col-lg-10">
		<select id='new_box_status'> 
					<option value=''>-- Select Status --</option>
					<?php 

						
						foreach( $all_statuses_list as $term=>$status ) {
        
              $selected = ''; 
              
              if( in_array( $status, $box_statuses) ) {
                $disabled = '';
              } else {
                $disabled = 'disabled';
              }
              
              echo '<option '.$selected. ' '. $disabled .' value="'.$term.'">'.$status.'</option>';
              			
            }


					?>
				</select>
	</div>
</div>














<style>
.zebra {
	padding: 7px 0px 7px 0px;
}

.zebra:nth-of-type(even) {
/* 	background: #e0e0e0; */
	background: #f3f3f3;
	border-radius: 4px;
}

#assigned_agents {
	margin-bottom: 0px !important;
}

.wpsc_display_assign_agent {
	margin-bottom: 5px !important;
}

.tight {
	margin-top: 3px !important;
	margin-bottom: 10px !important;
}

.staff-badge {
	padding: 3px 5px 3px 5px;
	font-size:1.0em !important;
	vertical-align: middle;
}

.staff-close {
	margin-left: 3px;
	margin-right: 3px;
}

.label_center {
	margin-top: 5px !important;
	margin-bottom: 0px !important;
}

.alert_spacing {
	margin: 0px 0px 10px 0px;
}

.accordion-header {
	margin-top: 0px !important;
	margin-bottom: 5px !important;
}

.accordion-body {
	padding-left: 15px;
}

</style>

<!--
<div class="accordion-item">
    <h2 class="accordion-header" id="headingOne">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        Accordion Item #1
      </button>
    </h2>
    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        <strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
      </div>
    </div>
  </div>
-->

<!--
<div class="accordion">
    <div class="section">
        <strong><a class="section-title" style="text-decoration: none;" href="#accordion-1" style="color: #174eb5;">Edit More</a></strong>
        <div id="accordion-1" class="section-content">
            <p>
                <strong>
                    Program Office:
                    <a href="#" aria-label="Program office" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-program-office'); ?>">
                        <i class="far fa-question-circle"></i>
                    </a>
                </strong>
                <br />

                <input type="search" list="ProgramOfficeList" placeholder="Enter program office" id="po" />
                <datalist id="ProgramOfficeList"> </datalist>

                <br />

                <strong>
                    Record Schedule:
                    <a href="#" aria-label="Record Schedule" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-record-schedule'); ?>">
                        <i class="far fa-question-circle"></i>
                    </a>
                </strong>
                <br />

                <input type="search" list="RecordScheduleList" placeholder="Enter record schedule" id="rs" />
                <datalist id="RecordScheduleList"> </datalist>
            </p>
        </div>
        
    </div>
    
</div>
-->


<script>
	jQuery(document).ready(function(){
		//console.log( 'The JAVA has SCRIPTED' );
		//
		// Accordion code
		//
		
// 		let current_status = '<?php echo Patt_Custom_Func::get_box_file_details_by_id($item_ids[0])->box_status ?>';
		let request_restriction_bool = <?php echo json_encode($request_restriction_bool); ?>;		
		let current_status = '<?php echo $old_status ?>';		
		let is_single_item = <?php echo json_encode($is_single_item); ?>;
		let restriction_reason = '<?php echo $restriction_reason ?>';
		//let restricted_reason_array = <?php echo json_encode( $restricted_reason_array ); ?>;
		let restricted_reason_obj = <?php echo json_encode( $restricted_reason_array ); ?>;
		console.log({restricted_reason_obj:restricted_reason_obj});
		let accordion_notification = '';
		const restricted_reason_array = Object.entries( restricted_reason_obj );
		
		
		// Construct accordion style notification for each box. 
		//console.log( 'yo' );
		//console.log( restricted_reason_array );
		
		let accordion_pre_message = '<strong><div class="" id="alert-message">Click the Box number to view the restrictions.</div></strong><br />';
		let accordion_start = '<div class="accordion" id="the-accordion">';

		//let accordion_item_start = '<div class="accordion-item"><h2 class="accordion-header" id="headingOne"><button class="accordion-button" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">';
		
		let accordion_item_start_1 = '<div class="accordion-item"><h2 class="accordion-header" id="';
		let accordion_item_start_2 = '"><button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#';
		let accordion_item_start_3 = '" aria-expanded="false" aria-controls="';
		let accordion_item_start_4 = '">';

		//let accordion_item_mid = '</button></h2><div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-parent="#accordionExample"><div class="accordion-body">';
		let accordion_item_mid_1 = '</button></h2><div id="';
		let accordion_item_mid_2 = '" class="accordion-collapse collapse section-content" aria-labelledby="';
		let accordion_item_mid_3 = '" data-parent="#the-accordion"><div class="accordion-body">';
		
		
		let accordion_item_end = '</div></div></div>';
		let accordion_end = '</div>';
		
		accordion_notification += accordion_pre_message;
		accordion_notification += accordion_start;
		
		//Object.entries( restricted_reason_array ).forEach( function( item, index ) {
		restricted_reason_array.forEach( function( item, index ) {	
			
			let heading = 'heading' + index;
			let collapse = 'collapse' + index;
			
			
			accordion_notification += accordion_item_start_1;
			accordion_notification += 'heading-' + index;
			accordion_notification += accordion_item_start_2;
			accordion_notification += 'collapse-' + index;
			accordion_notification += accordion_item_start_3;
			accordion_notification += 'collapse-' + index;
			accordion_notification += accordion_item_start_4;
			accordion_notification += item[0];
			accordion_notification += accordion_item_mid_1;
			accordion_notification += 'collapse-' + index;
			accordion_notification += accordion_item_mid_2;
			accordion_notification += 'heading-' + index;
			accordion_notification += accordion_item_mid_3;
			accordion_notification += item[1];
			accordion_notification += accordion_item_end;
			
			
		});
		
		accordion_notification += accordion_end;
		
		console.log( accordion_notification );

		// Set the value of the select to the current value. 
		if( is_single_item ) {
			jQuery('#new_box_status').val(current_status);
		}
		
		// Only allow saving after change
		jQuery('#new_box_status').change( function() {
			console.log('New Status: '+jQuery('#new_box_status').val());
			if( jQuery('#new_box_status').val() == '' ) {
				jQuery("#button_box_status_submit").hide();
			} else {
				if( !request_restriction_bool ) {
				  jQuery("#button_box_status_submit").show();
				}
			}
				
		});
			
		
/*		// OLD: for text restriction_reason
		if( restriction_reason.length > 0 ) {
			set_alert('warning', restriction_reason);				
		}
*/
		//jQuery('#alert_status').html( 'test' );
		
		console.log( Array.isArray(restricted_reason_array) );
		console.log({length:restricted_reason_array.length});
		
		
		
		//console.log( Array.isArray(entries) );
		//console.log({entries_length:entries.length});
		
		// If one of the request statuses is in a restricted status, alert user and hide submit button.
		if( request_restriction_bool ) {
  		const request_status_restriction = 'One of the boxes selected is in one of the following request statuses: New Request, Tabled, Initial Review Rejected, Cancelled, or Completed/ Dispositioned. <strong>Saving is disabled</strong>.';
  		jQuery('#request_alert_status').html( request_status_restriction ); 
			jQuery('#request_alert_status').addClass('alert_spacing');
			jQuery('#request_alert_status').addClass('alert');
			jQuery('#request_alert_status').addClass('alert-danger');	
			
			jQuery("#button_box_status_submit").hide();
		}
		
		if( restricted_reason_array.length > 0 ) {
			console.log('in');
			//set_alert('warning', accordion_notification);	
			let alert_style = 'alert-warning';
			//jQuery('#alert_status').html('<div id="alert-1" class=" alert '+alert_style+'">'+accordion_notification+'</div>'); 
			//jQuery('#alert_status').html( 'test2' ); 
			jQuery('#alert_status').html( accordion_notification ); 
			jQuery('#alert_status').addClass('alert_spacing');
			jQuery('#alert_status').addClass('alert');
			jQuery('#alert_status').addClass('alert-warning');				
		}
		
		
		// additional Accordion code - start
		jQuery('.section-title').click(function(e) {
	    // Get current link value
		    var currentLink = jQuery(this).attr('href');
		    if(jQuery(e.target).is('.active')) {
		    	close_section();
		    }else {
			     close_section();
			    // Add active class to section title
			    jQuery(this).addClass('active');
			    // Display the hidden content
			    jQuery('.accordion ' + currentLink).slideDown(350).addClass('open');
		    }
			e.preventDefault();
		});
	 
		function close_section() {
		    jQuery('.accordion .section-title').removeClass('active');
		    jQuery('.accordion .section-content').removeClass('open').slideUp(350);
		}
		// Accordion - end
		
		
	});

// Simple hash function based on java's. Used for set_alert.
function hashCode( str ) {
	var hash = 0;
    for (var i = 0; i < str.length; i++) {
        var character = str.charCodeAt(i);
        hash = ((hash<<5)-hash)+character;
        hash = hash & hash; // Convert to 32bit integer
    }
    return hash;
}

// Sets an alert
function set_alert( type, message ) {
	
	let alert_style = '';
	let hash = hashCode( message );
	console.log({hash:hash});
	
	switch( type ) {
		case 'success':
			alert_style = 'alert-success';		
			break;
		case 'warning':
			alert_style = 'alert-warning';
			break;
		case 'danger':
			alert_style = 'alert-danger';
			break;		
	}

	//jQuery('#alert_status').html('<div class=" alert '+alert_style+'">'+message+'</div>'); 
	jQuery('#alert_status').html('<div id="alert-' + hash + '" class=" alert '+alert_style+'">'+message+'</div>'); 
	jQuery('#alert_status').addClass('alert_spacing');	
	
	alert_dismiss( hash );
	
}

// Sets the time for dismissing the error notification
function alert_dismiss( hash ) {
	// No timeout desired for this modal's alerts.
	//setTimeout( function(){ jQuery( '#alert-'+hash ).fadeOut( 1000 ); }, 9000 );	
}

</script>


<?php

$body = ob_get_clean();

ob_start();

?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();window.location.reload();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" id="button_box_status_submit" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_box_status();"><?php _e('Save','supportcandy');?></button>

<script>
jQuery("#button_box_status_submit").hide();

function wppatt_set_box_status(){
	let item_ids = <?php echo json_encode($item_ids); ?>;	
	let term_id_array = <?php echo json_encode($term_id_array); ?>;
	let is_single_item = <?php echo json_encode($is_single_item); ?>;
	var new_status = jQuery('#new_box_status').val();
	let old_status = '<?php echo $old_status ?>';
	
	console.log('setting box status for items: ');
	console.log(item_ids);
	
	
	// Audit Log
	// Get list of all old box statuses
 	// 
 	

 
	//console.log(new_requestors);
	console.log('term id array: ');
	console.log(term_id_array);
	console.log('new status ');
	console.log(new_status);
	console.log('item_ids ');
	console.log(item_ids);
	

	jQuery.post(
	   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_box_status.php',{
	    type: 'box_status',
	    new_status: new_status,
	    old_status: old_status,
	    item_ids: item_ids,
	    is_single_item: is_single_item 
	}, 
    function (response) {
		//alert('updated: '+response);
		console.log('The Response:');
		console.log(response);
		window.location.reload(); 
		
		jQuery('#tbl_templates_boxes').DataTable().ajax.reload();
		


    });

	wpsc_modal_close();
} 

// Sets an alert
function set_alert( type, message ) {
	
	let alert_style = '';
	
	switch( type ) {
		case 'success':
			alert_style = 'alert-success';		
			break;
		case 'warning':
			alert_style = 'alert-warning';
			break;
		case 'danger':
			alert_style = 'alert-danger';
			break;		
	}

// 	jQuery('#alert_status').html('<span class=" alert '+alert_style+'">'+message+'</span>'); //badge badge-danger
	jQuery('#alert_status').html('<div class=" alert '+alert_style+'">'+message+'</div>'); //badge badge-danger
	jQuery('#alert_status').addClass('alert_spacing');	
	
}
</script>



<?php 
$footer = ob_get_clean();

$output = array(
  'body'   => $body,
  'footer' => $footer
);
echo json_encode($output);

