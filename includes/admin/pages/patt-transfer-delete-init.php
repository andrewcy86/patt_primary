<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

$GLOBALS['id'] = $_POST['id'];

$agent_permissions = $wpscfunction->get_current_agent_permissions();

//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';
$create_ticket_btn_css = 'background-color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_text_color'].' !important;';
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
{
?>

<style>

div.dataTables_wrapper {
        width: 100%;
        margin: 0;
    }

.bootstrap-iso label {
    margin-top: 5px;
}
.datatable_header {
background-color: rgb(66, 73, 73) !important; 
color: rgb(255, 255, 255) !important; 
}

.bootstrap-tagsinput {
   width: 100%;
  }

#searchGeneric {
    padding: 0 30px !important;
}

.remove-user {
    padding-left:5px;
}
</style>

<div class="bootstrap-iso">
  
<h3>PATT Transfer Archive</h3>
  
  <div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important;">

  <div class="row wpsc_tl_action_bar" style="background-color:<?php echo $general_appearance['wpsc_action_bar_color']?> !important;">
  
  <div class="col-sm-12">
         <button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=patt-transfer';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fa fa-list-ul" aria-hidden="true" title="PATT Transfer List"></i><span class="sr-only">PATT Transfer List</span> <?php _e('PATT Transfer List','supportcandy')?> <a href="#" aria-label="Patt Transfer list button" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-request-list-button'); ?>" aria-label="Request Help"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a></button>
		 <button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" style="<?php echo $action_default_btn_css?>  margin-right: 30px !important;"><i class="fas fa-retweet" aria-hidden="true" title="Reset Filters"></i><span class="sr-only">Reset Filters</span> <?php _e('Reset Filters','supportcandy')?></button>
         <button type="button" class="btn btn-sm wpsc_btn_bulk_action wpsc_action_btn" id="btn_restore_tickets" style="<?php echo $action_default_btn_css?>"><i class="fa fa-window-restore" aria-hidden="true" title="Restore"></i><span class="sr-only">Restore</span> <?php _e('Restore','supportcandy')?></button>
<?php
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager'))
{
?>
         <button type="button" class="btn btn-sm wpsc_btn_bulk_action wpsc_action_btn" id="btn_delete_permanently_bulk_ticket" style="<?php echo $action_default_btn_css?>; background-color:#FF7A33 !important;color:black !important;"><i class="fa fa-trash" aria-hidden="true" title="Delete Permanently"></i><span class="sr-only">Delete Permanently</span> <?php _e('Delete Permanently','supportcandy')?></button>
<?php
}
?>	
  </div>

</div>

<div class="row" style="background-color:<?php echo $general_appearance['wpsc_bg_color']?> !important;color:<?php echo $general_appearance['wpsc_text_color']?> !important;">

	<div class="col-sm-4 col-md-3 wpsc_sidebar individual_ticket_widget">

							<div class="row" id="wpsc_status_widget" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_border_color']?> !important;">
					      <h4 class="widget_header"><i class="fa fa-filter" aria-hidden="true" title="Filters"></i><span class="sr-only">Filters</span> Filters
								</h4>
								<hr class="widget_divider">

	                            <div class="wpsp_sidebar_labels">
Enter one or more Request IDs:<br />
         <input type='text' id='searchByRequestID' class="form-control" data-role="tagsinput" aria-label="Enter one or more Request IDs:">
<br />

<?php
//Request statuses
$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses');
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
$initial_review_complete_tag = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses');
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses');
$shipped_tag = get_term_by('slug', 'awaiting-agent-reply', 'wpsc_statuses');
$received_tag = get_term_by('slug', 'received', 'wpsc_statuses');
$in_progress_tag = get_term_by('slug', 'in-process', 'wpsc_statuses');
$ecms_tag = get_term_by('slug', 'ecms', 'wpsc_statuses');
$sems_tag = get_term_by('slug', 'sems', 'wpsc_statuses');
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses');
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses');

//Priorities
$not_assigned_tag = get_term_by('slug', 'not-assigned', 'wpsc_priorities');
$normal_tag = get_term_by('slug', 'low', 'wpsc_priorities');
$high_tag = get_term_by('slug', 'medium', 'wpsc_priorities');
$critical_tag = get_term_by('slug', 'high', 'wpsc_priorities');
?>

<!-- ECMS has been updated to be called ARMS instead -->
<!-- <select id='searchByOverallStatus' aria-label='Search by Status'>
			  <option value=''>-- Select A Status --</option>
			  <option value='Processing'>Processing</option>
			  <option value='Error'>Error</option>
			  <option value='Transferred'>Completed/Transferred</option>
			  <option value='Published'>Published</option>
		  </select>
		<br /><br />

		<select id='searchByStage' aria-label='Search by Stage'>
			  <option value=''>-- Select A Stage --</option>
			  <option value='received'>Received from Digitization Center</option>
			  <option value='text_extraction'>Text Extraction</option>
			  <option value='keyword_id'>Keyword/Identifier Extraction</option>
			  <option value='metadata'>Metadata Preparation</option>
			  <option value='arms'>ARMS Connection</option>
			  <option value='published'>Publishing of Record</option>
		</select>
		<br /><br /> -->

		<?php	
$user_digitization_center = get_user_meta( $current_user->ID, 'user_digization_center',true);

if ( !empty($user_digitization_center) && $user_digitization_center == 'East' && $agent_permissions['label'] == 'Agent') { 
?>
<input type="hidden" id="searchByDigitizationCenter" value="East" />
<?php 
} 
?>

<?php
if ( !empty($user_digitization_center) && $user_digitization_center == 'West' && $agent_permissions['label'] == 'Agent') { 
?>
<input type="hidden" id="searchByDigitizationCenter" value="West" />
<?php 
} 
?>

<?php
if ( !empty($user_digitization_center) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager'))) { 
?>
				<select id='searchByDigitizationCenter' aria-label="Search by Digitization Center">
					<option value=''>-- Select Digitization Center --</option>
					<option value='East' <?php if(!empty($user_digitization_center) && $user_digitization_center == 'East'){ echo 'selected'; } ?>>East</option>
					<option value='West' <?php if(!empty($user_digitization_center) && $user_digitization_center == 'West'){ echo 'selected'; } ?>>West</option>
					<option value='Not Assigned'>Not Assigned</option>
				</select>
    <br /><br />
<?php 
} elseif(($agent_permissions['label'] == 'Requester') || ($agent_permissions['label'] == 'Requester Pallet')) {
?>
				<select id='searchByDigitizationCenter' aria-label="Search by Digitization Center">
					<option value=''>-- Select Digitization Center --</option>
					<option value='East'>East</option>
					<option value='West'>West</option>
					<option value='Not Assigned'>Not Assigned</option>
				</select>
    <br /><br />
<?php
}
?>

	<!-- <br /><br />				 -->		
				<form id="frm_get_ticket_assign_agent">
					<div id="assigned_agent">
						<div class="form-group wpsc_display_assign_agent ">
						    <input class="form-control  wpsc_assign_agents ui-autocomplete-input " id="assigned_agent" aria-label="Search digitization staff" name="assigned_agent"  type="text" autocomplete="off" placeholder="<?php _e('Search agent ...','supportcandy')?>" />
							<ui class="wpsp_filter_display_container"></ui>
						</div>
					</div>
					<div id="assigned_agents" class="form-group col-md-12 ">
						<?php
						    if($is_single_item) {
							    foreach ( $assigned_agents as $agent ) {
									$agent_name = get_term_meta( $agent, 'label', true);
									 	
										if($agent && $agent_name):
						?>
												<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">
													<div class="flex-container searched-user" style="padding:5px;font-size:1.0em;">
														<?php echo htmlentities($agent_name)?><span class="remove-user"><i class="fa fa-times" aria-hidden="true" title="Remove User"></i><span class="sr-only">Remove User</span></span>
														  <input type="hidden" name="assigned_agent[]" value="<?php echo htmlentities($agent) ?>" />
					<!-- 									  <input type="hidden" name="new_requestor" value="<?php echo htmlentities($agent) ?>" /> -->
													</div>
												</div>
						<?php
										endif;
								}
							}
						?>
				  </div>
						<input type="hidden" name="action" value="wpsc_tickets" />
						<input type="hidden" name="setting_action" value="set_change_assign_agent" />
						<input type="hidden" id="current_user" name="current_user" value="<?php wp_get_current_user(); echo $current_user->display_name; ?>">
                        <input type="hidden" id="user_search" name="user_search" value="">
				</form>	

	                            </div>
			    		</div>
	
	</div>


	
  <div class="col-sm-8 col-md-9 wpsc_it_body">

<style>
.datatable_header {
background-color: rgb(66, 73, 73) !important; 
color: rgb(255, 255, 255) !important; 
}

.bootstrap-tagsinput {
   width: 100%;
  }

#searchGeneric {
    padding: 0 30px !important;
}

.wpsc_loading_icon {
	margin-top: 0px !important;
}
</style>

<div class="table-responsive" style="overflow-x:auto;">
<input type="text" id="searchGeneric" class="form-control" name="custom_filter[s]" value="" autocomplete="off" placeholder="Search..." aria-label="Search">
<i class="fa fa-search wpsc_search_btn wpsc_search_btn_sarch" aria-hidden="true" title="Search"></i><span class="sr-only">Search</span>
<br /><br />
<table id="tbl_templates_requests_delete" class="display nowrap" cellspacing="5" cellpadding="5" width="100%">
        <thead>
            <tr>
                <th class="datatable_header" scope="col"></th>
                <th class="datatable_header" scope="col">Doc ID</th>
                <!-- <th class="datatable_header" scope="col">Priority</th> -->
                <th class="datatable_header" scope="col">Status</th>
                <th class="datatable_header" scope="col">Name</th>
                <th class="datatable_header" scope="col">Location</th>
                <th class="datatable_header" scope="col">Duration</th>
            </tr>
        </thead>
    </table>
<br /><br />
<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.3/jquery.tagsinput.css" crossorigin="anonymous">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.3/jquery.tagsinput.js" crossorigin="anonymous"></script>

<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>

<script>

jQuery(document).ready(function(){

  var dataTable = jQuery('#tbl_templates_requests_delete').DataTable({
    'autoWidth': false,
    'drawCallback': function( settings ) {
        jQuery('[data-toggle="tooltip"]').tooltip();
    },
    'processing': true,
    'serverSide': true,
    'stateSave': true,
    'scrollX': true,
    'paging' : true,
    'stateSaveParams': function(settings, data) {
      data.ss = jQuery('#searchByStatus').val();
      data.sp = jQuery('#searchByPriority').val();
      data.sg = jQuery('#searchGeneric').val();
      data.rid = jQuery('#searchByRequestID').val();
      data.po = jQuery('#searchByProgramOffice').val();
			<?php
			if (($agent_permissions['label'] == 'Requester') || ($agent_permissions['label'] == 'Requester Pallet'))
            {
			?>      
      data.dc = jQuery('#searchByDigitizationCenter').val();
      		<?php
            }
			?>
      data.es = jQuery('#searchByECMSSEMS').val();
	  data.aaVal = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();     
	  data.aaName = jQuery(".searched-user").map(function(){return jQuery(this).text();}).get(); 
    },
    'stateLoadParams': function(settings, data) {
      jQuery('#searchByStatus').val(data.ss);
      jQuery('#searchByPriority').val(data.sp);
      jQuery('#searchGeneric').val(data.sg);
      jQuery('#searchByRequestID').val(data.rid);
      jQuery('#searchByProgramOffice').val(data.po);
			<?php
			if (($agent_permissions['label'] == 'Requester') || ($agent_permissions['label'] == 'Requester Pallet'))
            {
			?>
      jQuery('#searchByDigitizationCenter').val(data.dc);
			<?php
            }
			?>
      jQuery('#searchByECMSSEMS').val(data.es);
      jQuery('#searchByUser').val(data.sbu);
			jQuery('#user_search').val(data.aaName);

			data.aaVal.forEach( function(val, key) {
				let html_str = get_display_user_html(data.aaName[key], val); 
				jQuery('#assigned_agents').append(html_str);
			});
    },
    'serverMethod': 'post',
    'searching': false, // Remove default Search Control
    'ajax': {
       'url':'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/patt_transfer_delete_processing.php',
       'data': function(data){
        console.log('the data');
	      console.log(data);
          // Read values
		  var po_value = jQuery('#searchByProgramOffice').val();
          var po = jQuery('#searchByProgramOfficeList [value="' + po_value + '"]').data('value');
          var sg = jQuery('#searchGeneric').val();

          var docid = jQuery('#searchByDocID').val();
          var dc = jQuery('#searchByDigitizationCenter').val();
          var sp = jQuery('#searchByPriority').val();
          var rd = jQuery('#searchByRecallDecline').val();
          var sbos = jQuery('#searchByOverallStatus').val();
          // Create a jquery object to hold the value of the searchByStage select field
          var sbst = jQuery('#searchByStage').val();
          var sbs = jQuery('#searchByStatus').val(); 
          var sbu = jQuery('#searchByUser').val();  
          var aaVal = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();     
          var aaName = jQuery(".searched-user").map(function(){return jQuery(this).text();}).get(); 
         //console.log({is_requester:is_requester});
         // Append to data
         data.searchGeneric = sg;
         // Append the value of the searchByStage select field to data object
         data.searchByDocID = docid;
         data.searchByProgramOffice = po;
         data.searchByDigitizationCenter = dc;
         data.searchByPriority = sp;
         data.searchByRecallDecline = rd;
         data.searchByOverallStatus = sbos;
         data.searchByStage = sbst
         data.searchByStatus = sbs;
         data.searchByUser = sbu;
         data.searchByUserAAVal = aaVal;
         data.searchByUserAAName = aaName;
       }
    },
    'lengthMenu': [[10, 25, 50, 100], [10, 25, 50, 100]],
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
    'columns': [
       { data: 'folderdocinfo_id' }, 
       { data: 'doc_id', 'class' : 'text_highlight' },
       { data: 'status' },
       { data: 'customer_name' },
       { data: 'location' },
       { data: 'duration' },
    ]
  });
    
  jQuery( window ).unload(function() {
  dataTable.column(0).checkboxes.deselectAll();
});

  jQuery(document).on('keypress',function(e) {
    if(e.which == 13) {
        //prevents page redirect on enter
        e.preventDefault();
        dataTable.state.save();
        dataTable.draw();
    }
});

	jQuery("#searchByUser").change(function(){
		dataTable.state.save();
		dataTable.draw();
	});

jQuery("#searchByStatus").change(function(){
    dataTable.state.save();
    dataTable.draw();
});

  jQuery("#searchByPriority").change(function(){
    dataTable.state.save();
    dataTable.draw();
});

  jQuery("#searchByDigitizationCenter").change(function(){
    dataTable.state.save();
    dataTable.draw();
});

// ECMS has been updated to be called ARMS instead
jQuery("#searchByECMSSEMS").change(function(){
    dataTable.state.save();
    dataTable.draw();
});

//jQuery('#searchGeneric').on('input keyup paste', function () {
//            dataTable.state.save();
//            dataTable.draw();
//});

function onAddTag(tag) {
    dataTable.state.save();
    dataTable.draw();

    var target = jQuery("#searchByRequestID");
    var tags = (tag).match(/id=(\d+)/);

    if (tags != null) {
        if (!target.tagExist(tags[1])) {
            target.addTag(tags[1]);
            target.removeTag(tag);

        }
    }
}

function onRemoveTag(tag) {
    dataTable.state.save();
    dataTable.draw();
}

jQuery("#searchByRequestID").tagsInput({
   'defaultText':'',
   'onAddTag': onAddTag,
   'onRemoveTag': onRemoveTag,
   'width':'100%'
});

jQuery("#searchByRequestID_tag").on('paste',function(e){
    var element=this;
    setTimeout(function () {
        var text = jQuery(element).val();
        var target=jQuery("#searchByRequestID");
        var tags = (text).split(/[ ,]+/);
        for (var i = 0, z = tags.length; i<z; i++) {
              var tag = jQuery.trim(tags[i]);
              if (!target.tagExist(tag)) {
                    target.addTag(tag);
              }
              else
              {
                  jQuery("#searchByRequestID_tag").val('');
              }
                
         }
    }, 0);
});


jQuery('#wpsc_individual_refresh_btn').on('click', function(e){
    jQuery('#searchByUser').val('');
    jQuery('#searchByStatus').val('');
    jQuery('#searchByPriority').val('');
    jQuery('#searchGeneric').val('');
    jQuery('#searchByProgramOffice').val('');
    jQuery('#searchByDigitizationCenter').val('');
    jQuery('#searchByECMSSEMS').val('');
    jQuery('#searchByBoxID').importTags('');
    dataTable.column(0).checkboxes.deselectAll();
	dataTable.state.clear();
	dataTable.destroy();
	location.reload();
});

function wpsc_get_restore_bulk_ticket_1(){
  wpsc_modal_open('Restore Request');
   var form = this;
      var rows_selected = dataTable.column(0).checkboxes.selected();

        var ticket_id=String(rows_selected.join(","));

        // console.log('ticket id ' + ticket_id);

      var data = {
          action: 'wpsc_tickets',
          setting_action : 'patt_transfer_get_bulk_restore_ticket',
          ticket_id: ticket_id
      }

      jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
       var response = JSON.parse(response_str);
      
       jQuery('#wpsc_popup_body').html(response.body);
       jQuery('#wpsc_popup_footer').html(response.footer);
     });

  }
  
function wpsc_get_delete_permanently_bulk_ticket_1(){
  wpsc_modal_open('Delete Request Permanently');
  var form = this;
      var rows_selected = dataTable.column(0).checkboxes.selected();

        var ticket_id=String(rows_selected.join(","));

        console.log('ticket id ' + ticket_id);

      var data = {
          action: 'wpsc_tickets',
          setting_action : 'patt_transfer_get_delete_permanently_bulk_ticket',
          ticket_id: ticket_id
      }

      jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
       var response = JSON.parse(response_str);
       jQuery('#wpsc_popup_body').html(response.body);
       jQuery('#wpsc_popup_footer').html(response.footer);
     });
     
  }
      
   jQuery('#btn_restore_tickets').on('click', function(e){
wpsc_get_restore_bulk_ticket_1();
  
      });
   jQuery('#btn_delete_permanently_bulk_ticket').on('click', function(e){
wpsc_get_delete_permanently_bulk_ticket_1();
  
      });
      
   jQuery('#btn_delete_tickets').on('click', function(e){
    wpsc_get_delete_bulk_ticket_1();
      });

	// User Search
	jQuery('#frm_get_ticket_assign_agent').hide();
	
	jQuery('#searchByUser').change( function() {
		if(jQuery(this).val() == 'search for user') {
			jQuery('#frm_get_ticket_assign_agent').show();
		} else {
			jQuery('#frm_get_ticket_assign_agent').hide();
		}
	});
	
	// Show search box on page load - from save state
	if( jQuery('#searchByUser').val() == 'search for user' ) {
		jQuery('#frm_get_ticket_assign_agent').show();
	}

	// Autocomplete for user search
	jQuery( ".wpsc_assign_agents" ).autocomplete({
		minLength: 0,
		appendTo: jQuery('.wpsc_assign_agents').parent(),
		source: function( request, response ) {
			var term = request.term;
			console.log('term: ');
			console.log(term);
			request = {
				action: 'wpsc_tickets',
				setting_action : 'filter_autocomplete',
				term : term,
				field : 'assigned_agent',
			}
			jQuery.getJSON( wpsc_admin.ajax_url, request, function( data, status, xhr ) {
				response(data);
			});
		},
		select: function (event, ui) {
			console.log('label: '+ui.item.label+' flag_val: '+ui.item.flag_val); 							
			html_str = get_display_user_html(ui.item.label, ui.item.flag_val);
// 			jQuery('#assigned_agents').append(html_str);	
			
			// when adding new item, event listener functon must be added. 
			jQuery('#assigned_agents').append(html_str).on('click','.remove-user',function(){	
				console.log('This click worked.');
				wpsc_remove_filter(this);
				jQuery('#user_search').val(jQuery(".searched-user").map(function(){return jQuery(this).text();}).get());
				dataTable.state.save();
				dataTable.draw();
			});
		    jQuery('#user_search').val(jQuery(".searched-user").map(function(){return jQuery(this).text();}).get());
			dataTable.state.save();
			dataTable.draw();
			// ADD CODE to go through every status and make sure that there is at least one name per, and if so, show SAVE.
			
			jQuery("#button_agent_submit").show();
		    jQuery(this).val(''); return false;
		}
	}).focus(function() {
			jQuery(this).autocomplete("search", "");
	});
	
	


	jQuery('.searched-user').on('click','.remove-user', function(e){
		console.log('Removed a user 1');
		wpsc_remove_filter(this);
		jQuery('#user_search').val(jQuery(".searched-user").map(function(){return jQuery(this).text();}).get());
		dataTable.state.save();
		dataTable.draw();
	});
	
// Code block for toggling edit buttons on/off when checkboxes are set
	jQuery('#tbl_templates_requests_delete tbody').on('click', 'input', function () {        
	// 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	jQuery('.dt-checkboxes-select-all').on('click', 'input', function () {        
	 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	jQuery('#btn_restore_tickets').attr('disabled', 'disabled');
	jQuery('#btn_delete_permanently_bulk_ticket').attr('disabled', 'disabled');
	
	function toggle_button_display() {
	//	var form = this;
		var rows_selected = dataTable.column(0).checkboxes.selected();
		console.log(rows_selected);
		if(rows_selected.count() > 0) {
			jQuery('#btn_restore_tickets').removeAttr('disabled');
			jQuery('#btn_delete_permanently_bulk_ticket').removeAttr('disabled');
	  	} else {
	    	jQuery('#btn_restore_tickets').attr('disabled', 'disabled');    	
	    	jQuery('#btn_delete_permanently_bulk_ticket').attr('disabled', 'disabled'); 
	  	}
	}
});

function get_display_user_html(user_name, termmeta_user_val) {
	//console.log("in display_user");
// 	var requestor_list = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();
	var requestor_list = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();
	
	if( requestor_list.indexOf(termmeta_user_val.toString()) >= 0 ) {
		console.log('termmeta_user_val: '+termmeta_user_val+' is already listed');
		html_str = '';
	} else {

		var html_str = '<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">'
						+'<div class="flex-container searched-user" style="padding:5px;font-size:1.0em;">'
							+user_name
							+'<span  class="remove-user" ><i class="fa fa-times" aria-hidden="true" title="Remove User"></i><span class="sr-only">Remove User</span></span>'
						+'<input type="hidden" name="assigned_agent[]" value="'+termmeta_user_val+'" />'
						+'</div>'
					+'</div>';	

	}
			
	return html_str;		

}


function wpsc_remove_filterX(x) {
	setTimeout(wpsc_remove_filter(x), 10);
}


function remove_user() {
	//if zero users remove save
	//if more than 1 user show save
	var requestor_list = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();
	let is_single_item = <?php echo json_encode($is_single_item); ?>;
	//console.log('Remove user');
	console.log(requestor_list);
	console.log('length: '+requestor_list.length);
	console.log('single item? '+is_single_item);
	
	if( is_single_item ) {
		console.log('doing single item stuff');
		if( requestor_list.length > 0 ) {
			jQuery("#button_agent_submit").show();
		} else {
			jQuery("#button_agent_submit").hide();
		}
	}
}

	 jQuery('#toplevel_page_wpsc-tickets').removeClass('wp-not-current-submenu'); 
	 jQuery('#toplevel_page_wpsc-tickets').addClass('wp-has-current-submenu'); 
	 jQuery('#toplevel_page_wpsc-tickets').addClass('wp-menu-open'); 
	 jQuery('#toplevel_page_wpsc-tickets a:first').removeClass('wp-not-current-submenu');
	 jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-has-current-submenu'); 
	 jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-menu-open');
	 jQuery('#menu-dashboard').removeClass('current');
	 jQuery('#menu-dashboard a:first').removeClass('current');
	 jQuery('.wp-submenu li:nth-child(2)').addClass('current');
</script>


  </div>
 


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
<?php
}
?>