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

<?php

//print_r(Patt_Custom_Func::get_requestor_group($current_user->display_name));

$get_aa_ships = $wpdb->get_results("SELECT DISTINCT organization_acronym
FROM " . $wpdb->prefix . "wpsc_epa_program_office
WHERE id <> '-99999' ");

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
										if($agent && $agent_name):
						?>
												<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">
<!-- 													<div class="flex-container searched-user" style="padding:10px;font-size:1.0em;"> -->
													<div class="flex-container searched-user staff-badge" style="">														
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
<button type="button" id="button_agent_submit" class="btn wpsc_popup_action"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_org();"><?php _e('Save','supportcandy');?></button>				
				
		<?php		
		} else {
		echo "<br><strong>Please contact a Administrator or Manager to adjust these settings.</strong>";
		}
		
		
		// End $agent_permissions
		?>
				

	

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
	
</script>


