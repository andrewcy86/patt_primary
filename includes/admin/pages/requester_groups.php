<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

//$GLOBALS['id'] = $_GET['id'];

$agent_permissions = $wpscfunction->get_current_agent_permissions();


		if (($agent_permissions['label'] == 'Administrator')  || ($agent_permissions['label'] == 'Manager'))
		{
		?>

<div class="bootstrap-iso">

<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#requester_groups_tab">Requester Groups</a></li>
  <li><a data-toggle="tab" href="#requester_reassignment_tab">Requester Reassignment</a></li>
</ul>

<div class="tab-content">
  <div id="requester_groups_tab" class="tab-pane fade in active">
	  <!-- Requester Groups Tab Start -->
	<div>

	<?php

		//print_r(Patt_Custom_Func::get_requestor_group($current_user->display_name));


		$get_aa_ships = $wpdb->get_results("SELECT DISTINCT organization_acronym
		FROM " . $wpdb->prefix . "wpsc_epa_program_office
		WHERE id <> '-99999' ORDER BY organization_acronym ASC");

		$org_id_array = array();

		foreach($get_aa_ships as $data) {
			
			$org_id_array[] = $data->organization_acronym;
			
			echo '<br /><br /><strong>'.$data->organization_acronym.'</strong><br />';
		?>

		<form id="frm_get_ticket_assign_agent">
							<div id="assigned_agent">
								<div class="form-group wpsc_display_assign_agent ">
									<input class="form-control  wpsc_assign_agents_filter ui-autocomplete-input org-<?php echo $data->organization_acronym; ?>" name="assigned_agent"  type="text" autocomplete="off" placeholder="<?php _e('Search user ...','supportcandy')?>" />
									<ui class="wpsp_filter_display_container"></ui>
								</div>
							</div>
							<div id="assigned_agents" class="form-group col-md-12 org-<?php echo $data->organization_acronym; ?>">
								<?php
		$assigned_agents = [];

		$get_user_requestor_group_meta = $wpdb->get_results("
		SELECT user_id
		FROM " . $wpdb->prefix . "usermeta
		WHERE meta_key = 'user_requestor_group' AND meta_value = '".$data->organization_acronym."'
		");

		foreach($get_user_requestor_group_meta as $user_id) {
			
		$patt_agent_id = Patt_Custom_Func::translate_user_id(array($user_id->user_id),'agent_term_id');
		array_push($assigned_agents,$patt_agent_id[0]);

		}

										foreach ( $assigned_agents as $agent ) {
											$agent_name = get_term_meta( $agent, 'label', true);
                                          	$agent_user_id = get_term_meta( $agent, 'user_id', true);
												if($agent && $agent_name):
								?>
														<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">
		<!-- 													<div class="flex-container searched-user" style="padding:10px;font-size:1.0em;"> -->
															<div class="flex-container searched-user staff-badge" style="<?php echo (get_user_meta($agent_user_id, 'RLO', $single =  true) == '1') ? "background-color:#006600;color:#ffffff;" : "" ?>">															
																<?php echo htmlentities($agent_name)?><span class="remove-user staff-close"><i class="fa fa-times" aria-hidden="true" title="Remove User"></i><span class="sr-only">Remove User</span></span>
		<!-- 														<?php echo htmlentities($agent_name)?><span class="staff-close"><i class="fa fa-times"></i></span>														 -->
																<input type="hidden" name="assigned_agent[<?php echo $data->organization_acronym; ?>]" value="<?php echo htmlentities($agent) ?>" />
							<!-- 									  <input type="hidden" name="new_requestor" value="<?php echo htmlentities($agent) ?>" /> -->
															</div>
														</div>
								<?php
												endif;
										}
								?>
						</div>
								<input type="hidden" name="action" value="wpsc_tickets" />
								<input type="hidden" name="setting_action" value="set_change_assign_agent" />
						</form>
		<?php
		}
		?>
		<br /><br />
		<button type="submit" id="button_agent_submit" class="btn wpsc_popup_action"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_org();"><?php _e('Save','supportcandy');?></button>				
					
		<?php		
		} else {
		echo "<br><strong>Please contact a Administrator or Manager to adjust these settings.</strong>";
		}
		
		
		// End $agent_permissions
		?>
	</div>
	<!-- Requester Groups Tab End -->
  </div>


  <div id="requester_reassignment_tab" class="tab-pane fade">
	<div style="margin-right: auto; margin-left: auto; margin-top: 50px;">
		<!-- RLO Reassignment Form Start -->
		
		<form id="frm_reassign_ticket_raised_by" action="">
			<div id="reassigned_agent">
				<div class="form-group wpsc_display_assign_agent ">
					<label for="prev_customer_name"><strong>Current User Assigned</strong></label>
					<input id="prev_customer_name" class="form-control  wpsc_reassign_agents_filter ui-autocomplete-input org-<?php echo $data->organization_acronym; ?>" name="current_assigned_agent"  type="text" autocomplete="off" placeholder="<?php _e('Search user ...','supportcandy')?>" require />
					<ui class="wpsp_filter_display_container"></ui>
				</div>
				
				<strong style="margin: auto 20px"> To </strong>

				<div class="form-group wpsc_display_assign_agent ">
					<label for="new_customer_name"><strong>New User Assigned</strong></label>
					<input id="new_customer_name" class="form-control  wpsc_reassign_agents_filter ui-autocomplete-input org-<?php echo $data->organization_acronym; ?>" name="new_assigned_agent"  type="text" autocomplete="off" placeholder="<?php _e('Search user ...','supportcandy')?>" require />
					<ui class="wpsp_filter_display_container"></ui>
				</div>
			</div>
			
			<div class="form-check">
				<input class="form-check-input" type="radio" name="reassinment_type" id="reassign_all" value="apply_to_all" checked>
				<label class="form-check-label" for="reassign_all">
					Apply to all Requests
				</label>
			</div>

			<div class="form-check">
				<input class="form-check-input" type="radio" name="reassinment_type" id="reassign_by_date" value="apply_to_dates">
				<label class="form-check-label" for="reassign_by_date">
					Apply to Requests Between
				</label>
			</div>

			<div id="reassignment_dates">
				<div class="form-group wpsc_display_assign_agent date_picker_start">
					<label for="start_date"><strong>Start Date</strong></label>
					<input type='date' id='start_date' class="form-control  wpsc_assign_agents_filter ui-autocomplete-input" aria-label='Start Date' autocomplete="off" placeholder= ''>
					<ui class="wpsp_filter_display_container"></ui>
				</div>
				
				<!-- <strong style="margin: auto 20px"> To </strong> -->

				<div class="form-group wpsc_display_assign_agent ">
					<label for="end_date"><strong>End Date</strong></label>
					<input type='date' id='end_date' class="form-control  wpsc_assign_agents_filter ui-autocomplete-input" aria-label='End Date' autocomplete="off" placeholder= ''>
					<ui class="wpsp_filter_display_container"></ui>
				</div>
			</div>
		
			</div>

		</form>

		<br /><br />
		<button type="submit" id="" class="btn wpsc_popup_action"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wpsc_set_reassign_raised_by();"><?php _e('Save','supportcandy');?></button>	
		</div>
		<!-- RLO Reassignment Form Start -->
	</div>	
  </div>
 
</div>





<style>
.bootstrap-tagsinput {
   width: 100%;
  }



.staff-badge {
	padding: 3px 3px 3px 5px;
	font-size:1.0em !important;
	vertical-align: middle;
}

.staff-close {
	margin-left: 3px;
	margin-right: 3px;
}

.bootstrap-iso .form-control {
	width: auto !important;
}

#frm_reassign_ticket_raised_by #reassigned_agent {
	display: flex;
	justify-content: flex-start;
    width: 100% !important;
}

#frm_reassign_ticket_raised_by .form-check {
	margin-bottom: 15px;
}

#frm_reassign_ticket_raised_by .form-check .form-check-label {
	margin: auto;
}

#frm_reassign_ticket_raised_by #reassignment_dates {
	display: flex;
	justify-content: flex-start;
    width: 100% !important;
}

#frm_reassign_ticket_raised_by #reassignment_dates .date_picker_start {
	margin-right: 70px;
}


</style>
 
<script>

jQuery(document).ready(function(){

	// User Seach
	
	// Show search box on page load - from save state
	if( jQuery('#searchByUser').val() == 'search for user' ) {
		jQuery('#frm_get_ticket_assign_agent').show();
	}


	// Autocomplete for user search
	jQuery( ".wpsc_assign_agents_filter" ).autocomplete({
		minLength: 0,
		appendTo: jQuery('.wpsc_assign_agents_filter').parent(),
		source: function( request, response ) {
			var term = request.term;
			//console.log('term: ');
			//console.log(term);
			request = {
				action: 'wpsc_tickets',
				setting_action : 'filter_autocomplete',
				term : term,
				field : 'assigned_agent',
				//no_requesters : true,
			}
			jQuery.getJSON( wpsc_admin.ajax_url, request, function( data, status, xhr ) {
				response(data);
			});
		},
		select: function (event, ui) {
			//console.log('label: '+ui.item.label+' flag_val: '+ui.item.flag_val); 							
			//html_str = get_display_user_html(ui.item.label, ui.item.flag_val);
// 			jQuery('#assigned_agents').append(html_str);	
			
			
			
				let list = jQuery(':focus').prop("classList");
				let the_org = '';
				list.forEach( function(y) {
					console.log(y);
					if ( y.startsWith('org-') ) {
						the_org = y.replace('org-','');
					}
				});
				 							
				html_str = get_display_user_html(ui.item.label, ui.item.flag_val, the_org, ui.item.wp_user_obj);
	
			// when adding new item, event listener functon must be added. 
			jQuery('#assigned_agents.org-'+the_org+'').append(html_str).on('click','.remove-user',function(){	
				//console.log('This click worked.');
				wpsc_remove_filter(this);
			});
			

			
		    jQuery(this).val(''); return false;
		}
	}).focus(function() {
			jQuery(this).autocomplete("search", "");
	});
	

	// Reassign agent autocompletion
	// Autocomplete for user search
	jQuery( ".wpsc_reassign_agents_filter" ).autocomplete({
		minLength: 0,
		appendTo: jQuery('.wpsc_reassign_agents_filter').parent(),
		source: function( request, response ) {
			var term = request.term;
			//console.log('term: ');
			//console.log(term);
			request = {
				action: 'wpsc_tickets',
				setting_action : 'filter_autocomplete',
				term : term,
				field : 'assigned_agent',
				//no_requesters : true,
			}
			jQuery.getJSON( wpsc_admin.ajax_url, request, function( data, status, xhr ) {
				response(data);
			});
		},
	}).focus(function() {
			jQuery(this).autocomplete("search", "");
	});
	


	jQuery('.searched-user').on('click','.remove-user', function(e){
		//console.log('Removed a user 1');
		wpsc_remove_filter(this);
	}); 


}); // END Document READY


function get_display_user_html(user_name, termmeta_user_val, org_id, is_wp_user) {
	//console.log("in display_user");
// 	var requestor_list = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();
	var requestor_list = jQuery("input[name='assigned_agent["+org_id+"]']").map(function(){return jQuery(this).val();}).get();
	
		// Checks if user is a wp_user (corner case error checking), if they aren't, do not add them. 
	if( !is_wp_user ) {
		
		set_alert( 'danger', 'User: <b>' + user_name + '</b> will not be added as the PATT Agent is not associated with a valid WP User. Try deleting the PATT Agent and re-adding them.' );
		
		return false;	
	}
	
	
	if( requestor_list.indexOf(termmeta_user_val.toString()) >= 0 ) {
		//console.log('termmeta_user_val: '+termmeta_user_val+' is already listed');
		html_str = '';
	} else {

/*
		var html_str = '<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">'
						+'<div class="flex-container staff-badge" style="">'
							+user_name
							+'<span class="staff-close" ><i class="fa fa-times"></i></span>'
						+'<input type="hidden" name="assigned_agent[]" value="'+termmeta_user_val+'" />'
						+'</div>'
					+'</div>';
*/

        //search for user autocomplete results are displayed here
        
		var html_str = '<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">'
						+'<div class="flex-container searched-user staff-badge" style="">'
							+user_name
							+'<span  class="remove-user staff-close" ><i class="fa fa-times" aria-hidden="true" title="Remove User"></i><span class="sr-only">Remove User</span></span>'
						+'<input type="hidden" name="assigned_agent['+org_id+']" value="'+termmeta_user_val+'" />'
						+'</div>'
					+'</div>';		

	}
			
	return html_str;		

}


function wpsc_remove_filterX(x) {
	setTimeout(wpsc_remove_filter(x), 10);
}

function wppatt_set_org() {
    
    let org_id_array = <?php echo json_encode($org_id_array); ?>;

	let new_requestor_group_array = [];
 	org_id_array.forEach( function(x) {
	 	new_requestor_group_array.push( {org:x, users:jQuery("input[name='assigned_agent["+x+"]']").map(function(){return jQuery(this).val();}).get()} );	 	
 	});	
 
	console.log(new_requestor_group_array);
	
	jQuery.post(
      '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_requestor_group.php',{
        type: 'set_requestor_group',
        new_requestor_group_array: new_requestor_group_array
		  }, 
	    function (response) {
  			//alert('updated: '+response);
  			console.log('The Response:');
  			alert(response);
  			window.location.reload();
	  });
	  
}

function wpsc_set_reassign_raised_by() {

	let dataform = new FormData(jQuery('#frm_reassign_ticket_raised_by')[0]);
	let prev_user = jQuery('#prev_customer_name').val();
	let new_user = jQuery('#new_customer_name').val();

	let reassignment_start_date = jQuery('#start_date').val();
	let reassignment_end_date = jQuery('#end_date').val();


	console.log('prev user: ' + prev_user);
	console.log('new user: ' + new_user);

	if(prev_user === '' || new_user === '') {
		alert('Please enter a current requester and a new requester.');
		return;
	}

	if(prev_user === new_user) {
		alert('Error: the same user cannot be entered in both fields.');
		return;
	}


	if(jQuery('#reassign_all').is(':checked')) {
		console.log('the apply to all radio button was chosen');
		jQuery.post(
		'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_requestor_group.php',{
			type: 'set_reassign_user',
			prev_user: prev_user,
			new_user: new_user
			}, 
			function (response) {
				//alert('updated: '+response);
				console.log('The Response:');
				alert(response);
				// window.location.reload();
				// location.href = "#requester_reassignment_tab";
				jQuery('#frm_reassign_ticket_raised_by')[0].reset();
		});
	}

	if (jQuery('#reassign_by_date').is(':checked')) {
		console.log('start date: ' + reassignment_start_date + ' and end date: ' + reassignment_end_date );

		if(reassignment_start_date === '' || reassignment_end_date === '') {
			alert('Please enter a start and end date.');
			return;
		}

		jQuery.post(
		'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_requestor_group.php',{
			type: 'set_reassign_user_by_date',
			prev_user: prev_user,
			new_user: new_user,
			start_date: reassignment_start_date,
			end_date: reassignment_end_date
			}, 
			function (response) {
				//alert('updated: '+response);
				console.log('The Response:');
				alert(response);
				// window.location.reload();
				// location.href = "#requester_reassignment_tab";
				jQuery('#frm_reassign_ticket_raised_by')[0].reset();
		});
	}

	
	  
}
	
</script>


