<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

//$GLOBALS['id'] = $_GET['id'];

$agent_permissions = $wpscfunction->get_current_agent_permissions();

//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';


// Get Box Status
// Register Box Status Taxonomy
if( !taxonomy_exists('wpsc_box_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
}

// $box_statuses = get_tax();

// Get List of Box Statuses
$box_statuses = get_terms([
	'taxonomy'   => 'wpsc_box_statuses',
	'hide_empty' => false,
	'orderby'    => 'meta_value_num',
	'order'    	 => 'ASC',
	'meta_query' => array('order_clause' => array('key' => 'wpsc_box_status_load_order')),
]);

// List of box status that do not need agents assigned.
// $ignore_box_status = ['Pending', 'Ingestion', 'Completed', 'Dispositioned'];
$ignore_box_status = []; //show all box status

$term_id_array = array();
foreach( $box_statuses as $key=>$box ) {
	if( in_array( $box->name, $ignore_box_status ) ) {
		unset($box_statuses[$key]);
		
	} else {
		$term_id_array[] = $box->term_id;
	}
}
array_values($box_statuses);
?>


<div class="row wpsc_tl_action_bar" style="background-color:<?php echo $general_appearance['wpsc_action_bar_color']?> !important;">
  
  <div class="col-sm-12">
    	<button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=wpsc-tickets';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fa fa-list-ul" aria-hidden="true" title="Request List"></i><span class="sr-only">Request List</span> <?php _e('Ticket List','supportcandy')?> <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-request-list-button'); ?>" aria-label="Request Help"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a></button>
		<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" style="<?php echo $action_default_btn_css?> margin-right: 30px !important;"><i class="fas fa-retweet" aria-hidden="true" title="Reset Filters"></i><span class="sr-only">Reset Filters</span> <?php _e('Reset Filters','supportcandy')?></button>
<?php		
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
{
?>
		<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_box_destruction_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-ban" aria-hidden="true" title="Destruction Completed"></i><span class="sr-only">Reverse Destruction</span> Reverse Destruction</button>
<?php } 
if($agent_permissions['label'] == 'Administrator' || $agent_permissions['label'] == 'Manager') {
?>
        <button type="button" id="wppatt_assign_staff_btn" style="background-color:#FF7A33 !important;color:black !important;" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fa fa-user-plus" aria-hidden="true" title="Assign Staff"></i><span class="sr-only">Assign Staff</span> Assign Staff</button>
<?php } ?>

<?php
if(($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
{
?>
		<button type="button" id="wppatt_change_status_btn"  class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-heartbeat" aria-hidden="true" title="Assign Box Status"></i><span class="sr-only">Assign Box Status</span> Assign Box Status <a href="#" class="notab" aria-label="Assign Box Status" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-assign-box-status'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a></button>
<?php } ?>
		<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_label_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-tags" aria-hidden="true" title="Reprint Box Labels"></i><span class="sr-only">Reprint Box Labels</span> Reprint Box Labels</button>
		<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_pallet_label_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-tags" aria-hidden="true" title="Reprint Pallet Labels"></i><span class="sr-only">Reprint Pallet Labels</span> Reprint Pallet Labels</button>
  </div>

</div>


<div class="row" style="background-color:<?php echo $general_appearance['wpsc_bg_color']?> !important;color:<?php echo $general_appearance['wpsc_text_color']?> !important;">

	<div class="col-sm-4 col-md-3 wpsc_sidebar individual_ticket_widget">
	
		<div class="row" id="wpsc_status_widget" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_border_color']?> !important;">
			<h4 class="widget_header"><i class="fa fa-filter" aria-hidden="true" title="Filters"></i><span class="sr-only">Filters</span> Filters <a href="#" aria-label="Filter" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-filters'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a>
			</h4>
			<hr class="widget_divider">
			
			<div class="wpsp_sidebar_labels">
				Enter a series of either Box IDs or Pallet IDs:<br />
				<input type='text' id='searchByBoxID' class="form-control" data-role="tagsinput">
				<br />
				
				<?php
					$po_array = Patt_Custom_Func::fetch_program_office_array(); 
				?>
				<input type="search" list="searchByProgramOfficeList" aria-label="Enter program office" placeholder='Enter program office' id='searchByProgramOffice' autocomplete='off'/>
				<datalist id='searchByProgramOfficeList'>
					<?php foreach($po_array as $key => $value) { ?>
					<?php 
					    $program_office = $wpdb->get_row("SELECT office_name FROM " . $wpdb->prefix . "wpsc_epa_program_office WHERE office_acronym  = '" . $value . "'");
					    $office_name = $program_office->office_name;
					?>
					<option data-value='<?php echo $value; ?>' value='<?php echo $value . ' : ' . $office_name; ?>'></option>
					<?php } ?>
				</datalist>
				
				<br /><br />
				
				<select id='searchByStatus' aria-label="Search by Status"> 
					<option value=''>-- Select Box Status --</option>
					<?php 
						foreach( $box_statuses as $status ) {
							echo "<option value='".$status->name."'>".$status->name."</option>";
						}
						
					?>

				</select>
				
				<br><br>
				
				
				<?php
				//Priority slugs
				$not_assigned_tag = get_term_by('slug', 'not-assigned', 'wpsc_priorities');
                $normal_tag = get_term_by('slug', 'low', 'wpsc_priorities');
                $high_tag = get_term_by('slug', 'medium', 'wpsc_priorities');
                $critical_tag = get_term_by('slug', 'high', 'wpsc_priorities');
				?>
				
				<select id='searchByPriority' aria-label="Search by Priority">
               <option value=''>-- Select Priority --</option>
    			<option value="<?php echo $not_assigned_tag->term_id; ?>">Not Assigned</option>
    			<option value="<?php echo $normal_tag->term_id; ?>">Normal</option>
    			<option value="<?php echo $high_tag->term_id; ?>">High</option>
    			<option value="<?php echo $critical_tag->term_id; ?>">Critical</option>
             </select>
    <br /><br />

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
				
			   <select id='searchByRecallDecline' aria-label='Search by Recall or Decline'>
               <option value=''>-- Select Recall or Decline --</option>
               <option value='Recall'>Recall</option>
               <option value='Decline'>Decline</option>
             </select>
    <br /><br />
    	
               <!-- ECMS has been updated to be called ARMS instead -->
               <select id='searchByECMSSEMS' aria-label='Search by ARMS or SEMS'>
               <option value=''>-- Select ARMS or SEMS --</option>
               <option value='ECMS'>ARMS</option>
               <option value='SEMS'>SEMS</option>
             </select>
<br /><br />
		<?php		
		if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
		{
		?>
				<select id='searchByUser' aria-label="Search by User">
					<option value=''>-- Select User --</option>
					<option value='mine'>Mine</option>
					<option value='not assigned'>Not Assigned</option>
					<option value='search for user'>Search for User</option>
				</select>		

				<br><br>				
				<form id="frm_get_ticket_assign_agent">
					<div id="assigned_agent">
						<div class="form-group wpsc_display_assign_agent ">
						    <input class="form-control  wpsc_assign_agents_filter ui-autocomplete-input " name="assigned_agent"  type="text" autocomplete="off" placeholder="<?php _e('Search agent ...','supportcandy')?>" />
							<ui class="wpsp_filter_display_container"></ui>
						</div>
					</div>
					<div id="assigned_agents" class="form-group col-md-12 ">
						<?php
						$is_single_item = '';
						    if($is_single_item) {
							    foreach ( $assigned_agents as $agent ) {
									$agent_name = get_term_meta( $agent, 'label', true);
										if($agent && $agent_name):
						?>
												<div class="form-group wpsp_filter_display_element wpsc_assign_agents ">
<!-- 													<div class="flex-container searched-user" style="padding:10px;font-size:1.0em;"> -->
													<div class="flex-container searched-user staff-badge" style="">														
														<?php echo htmlentities($agent_name)?><span class="remove-user staff-close"><i class="fa fa-times" aria-hidden="true" title="Remove User"></i><span class="sr-only">Remove User</span></span>
<!-- 														<?php echo htmlentities($agent_name)?><span class="staff-close"><i class="fa fa-times"></i></span>														 -->
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
						<input type="hidden" name="recall_id" value="<?php echo htmlentities($recall_id) ?>" />
				</form>
				
		<?php		
		} // End $agent_permissions for search user
		?>
				
				
				
			</div>
		</div>

	</div>



	
  <div class="col-sm-8 col-md-9 wpsc_it_body">



<div class="table-responsive" style="overflow-x:auto;">
<input type="text" id="searchGeneric" class="form-control" name="custom_filter[s]" value="" autocomplete="off" aria-label="Search..." placeholder="Search...">
<i class="fa fa-search wpsc_search_btn wpsc_search_btn_sarch" aria-hidden="true" title="Search"></i><span class="sr-only">Search</span>
<br /><br />
<table id="tbl_templates_boxes" class="display nowrap" cellspacing="5" cellpadding="5" width="100%">
        <thead>
            <tr>
<?php		
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet'))
{
?>
                <th class="datatable_header" scope="col" id="selectall"></th>
<?php
}
?>
                <th class="datatable_header" scope="col" >Box ID</th>
                <th class="datatable_header" scope="col" >DB ID</th>
                <th class="datatable_header" scope="col" >Pallet ID</th>
                <th class="datatable_header" scope="col" >Priority</th>
                <th class="datatable_header" scope="col" >Request ID</th>
                <th class="datatable_header" scope="col" >Box Status</th>                
                <th class="datatable_header" scope="col" >Digitization Center</th>
                <th class="datatable_header" scope="col" >Program Office</th>
                <th class="datatable_header" scope="col" >Validation</th>
            </tr>
        </thead>
    </table>
<br /><br />

<style>

input::-webkit-calendar-picker-indicator {
  display: none;
}

div.dataTables_processing { 
    z-index: 1; 
}

div.dataTables_wrapper {
        width: 100%;
        margin: 0;
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

.assign_agents_icon {
	cursor: pointer;
}

#searchByProgramOffice {
	width: 83%;
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

.wpsc_loading_icon {
	margin-top: 0px !important;
}

</style>
 
<script>

jQuery(document).ready(function(){
  
 jQuery('[data-toggle="tooltip"]').tooltip();
/*
	if( typeof data == 'undefined' ) {
		console.log('undefined!');
		data = {aaVal: []};

	}
	console.log('data.aaVal: ');
	console.log(data.aaVal);
*/
	var agent_permission_label = '<?php echo $agent_permissions["label"] ?>';
	var is_requester = false;
  	var is_requester_pallet = false;
  	if(agent_permission_label == 'Requester Pallet' ){
      is_requester_pallet = true;
    }
  
	if( agent_permission_label == 'Requester' || agent_permission_label == 'Requester Pallet' ) {
		is_requester = true;
	}
	
	
	var dataTable = jQuery('#tbl_templates_boxes').DataTable({
	    
	    'autoWidth': true,
		'processing': true,
		'serverSide': true,
		'stateSave': true,
		//'scrollX' : true,
		
		"initComplete": function (settings, json) {
		    jQuery("#tbl_templates_boxes").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
		    jQuery('#selectall').append('<span class="sr-only">Select All</span>');
		},
		'paging' : true,
		'stateSaveParams': function(settings, data) {
			data.sg = jQuery('#searchGeneric').val();
			data.bid = jQuery('#searchByBoxID').val();
			data.po = jQuery('#searchByProgramOffice').val();
			<?php
			if (($agent_permissions['label'] == 'Requester') || ($agent_permissions['label'] == 'Requester Pallet'))
            {
			?>
		    data.dc = jQuery('#searchByDigitizationCenter').val(); 
			<?php
            }
			?>
			data.sp = jQuery('#searchByPriority').val();
			data.sbs = jQuery('#searchByStatus').val();
			data.rd = jQuery('#searchByRecallDecline').val();
			data.es = jQuery('#searchByECMSSEMS').val();
			data.sbu = jQuery('#searchByUser').val(); 
			data.aaVal = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();     
			data.aaName = jQuery(".searched-user").map(function(){return jQuery(this).text();}).get();                   
		},
		'stateLoadParams': function(settings, data) {
			jQuery('#searchGeneric').val(data.sg);
			jQuery('#searchByBoxID').val(data.bid);
			jQuery('#searchByProgramOffice').val(data.po);
			<?php
			if (($agent_permissions['label'] == 'Requester') || ($agent_permissions['label'] == 'Requester Pallet'))
            {
			?>
			jQuery('#searchByDigitizationCenter').val(data.dc);
			<?php
            }
			?>
			jQuery('#searchByPriority').val(data.sp);
			jQuery('#searchByRecallDecline').val(data.rd);
			jQuery('#searchByECMSSEMS').val(data.es);
			jQuery('#searchByStatus').val(data.sbs); 
			jQuery('#searchByUser').val(data.sbu); 
			
			// If data values aren't defined then set them as blank arrays.
			if( typeof data.aaVal == 'undefined' ) {
				data.aaVal = [];
				data.aaName = [];				
			}
			
			data.aaVal.forEach( function(val, key) {
				let html_str = get_display_user_html(data.aaName[key], val); 
				jQuery('#assigned_agents').append(html_str);
			});
			//let html_str = get_display_user_html(ui.item.label, ui.item.flag_val);
			//jQuery('#assigned_agents').append(html_str);
			//jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get(); //load saved users   
		},
		'serverMethod': 'post',
		'searching': false, // Remove default Search Control
		'ajax': {
			'url':'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/box_processing.php',
			'data': function(data){
				// Read values
				var po_value = jQuery('#searchByProgramOffice').val();
				var po = jQuery('#searchByProgramOfficeList [value="' + po_value + '"]').data('value');
				var sg = jQuery('#searchGeneric').val();
				var boxid = jQuery('#searchByBoxID').val();
				var dc = jQuery('#searchByDigitizationCenter').val();
				var sp = jQuery('#searchByPriority').val();
				var rd = jQuery('#searchByRecallDecline').val();
				var es = jQuery('#searchByECMSSEMS').val();
				var sbs = jQuery('#searchByStatus').val(); 
				var sbu = jQuery('#searchByUser').val();  
				var aaVal = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();     
				var aaName = jQuery(".searched-user").map(function(){return jQuery(this).text();}).get();
              	//var is_requester = is_requester;
				console.log({is_requester:is_requester});
				// Append to data
				data.searchGeneric = sg;
				data.searchByBoxID = boxid;
				data.searchByProgramOffice = po;
				data.searchByDigitizationCenter = dc;
				data.searchByPriority = sp;
				data.searchByRecallDecline = rd;
				data.searchByECMSSEMS = es;
				data.searchByStatus = sbs;
				data.searchByUser = sbu;
				data.searchByUserAAVal = aaVal;
				data.searchByUserAAName = aaName;
				data.is_requester = is_requester;
              	data.is_requester_pallet = is_requester_pallet;
			
			}
		},
		'drawCallback': function (settings) { 
		    jQuery('[data-toggle="tooltip"]').tooltip();
	        // Here the response
	        var response = settings.json;
	       	        console.log(response);
    	},
        'lengthMenu': [[10, 25, 50, 100], [10, 25, 50, 100]],
		'fixedColumns': true,
	<?php		
	if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet'))
	{
	?>
		'columnDefs': [
		{	'width' : 0,
			'targets': 0,
			'checkboxes': {	
			   'selectRow': true	
			},
		},
		{
            'targets': [ 1 ],
            'orderData': [ 2 ]
        },
        {
            'targets': [ 2 ],
            'visible': false,
            'searchable': false
        },
		{ 'width': '100%', 'targets': 4 },
		{ 'width': '5%', 'targets': 6 }
		],
		'select': {	
			'style': 'multi'
		},
		'order': [[1, 'asc']],
// 		'order': [[1, 'desc']],
	<?php
	}
	?>
		'columns': [
	<?php		
	if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet'))
	{
	?>
			{ data: 'box_id', 'title': 'Select All Checkbox'},

	<?php
	}
	?>

			{ data: 'box_id_flag', 'class' : 'text_highlight'},
			{ data: 'dbid', visible: false},
			{ data: 'pallet_id' },
			{ data: 'ticket_priority' },
			{ data: 'request_id', 'class' : 'text_highlight' },
			{ data: 'status' },       
			{ data: 'location' },
			{ data: 'acronym' },
			{ data: 'validation' },
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
	
	jQuery("#searchByProgramOffice").change(function(){
		dataTable.state.save();
		dataTable.draw();
	});
	
	jQuery("#searchByDigitizationCenter").change(function(){
		dataTable.state.save();
		dataTable.draw();
	});
	
	jQuery("#searchByPriority").change(function(){
        dataTable.state.save();
        dataTable.draw();
    });
    
    jQuery("#searchByRecallDecline").change(function(){
        dataTable.state.save();
        dataTable.draw();
    });
    
  	// ECMS has been updated to be called ARMS instead
    jQuery("#searchByECMSSEMS").change(function(){
        dataTable.state.save();
        dataTable.draw();
    });

	jQuery("#searchByStatus").change(function(){
		dataTable.state.save();
		dataTable.draw();
	});
	
	jQuery("#searchByUser").change(function(){
		dataTable.state.save();
		dataTable.draw();
	});
	
	//jQuery('#searchGeneric').on('input keyup paste', function () {
	//		dataTable.state.save();
	//		dataTable.draw();
	//});
	
	
	function onAddTag(tag) {
		dataTable.state.save();
		dataTable.draw();
	}
	function onRemoveTag(tag) {
		dataTable.state.save();
		dataTable.draw();
	}


	jQuery("#searchByBoxID").tagsInput({
	   'defaultText':'',
	   'onAddTag': onAddTag,
	   'onRemoveTag': onRemoveTag,
	   'width':'100%'
	});

	jQuery("#searchByBoxID_tag").on('paste',function(e){
	    var element=this;
	    setTimeout(function () {
	        var text = jQuery(element).val();
	        var target=jQuery("#searchByBoxID");
	        var tags = (text).split(/[ ,]+/);
	        for (var i = 0, z = tags.length; i<z; i++) {
	              var tag = jQuery.trim(tags[i]);
	              if (!target.tagExist(tag)) {
	                    target.addTag(tag);
	              }
	              else
	              {
	                  jQuery("#searchByBoxID_tag").val('');
	              }
	                
	         }
	    }, 0);
	});

	jQuery('#wpsc_individual_refresh_btn').on('click', function(e){
	    jQuery('#searchGeneric').val('');
	    jQuery('#searchByProgramOffice').val('');
	    jQuery('#searchByDigitizationCenter').val('');
	    jQuery('#searchByPriority').val('');
	    jQuery('#searchByRecallDecline').val('');
	    jQuery('#searchByECMSSEMS').val('');
	    jQuery('#searchByUser').val('');
        jQuery('#searchByStatus').val('');
	    jQuery('#searchByBoxID').importTags('');
	    dataTable.column(0).checkboxes.deselectAll();
		dataTable.state.clear();
		dataTable.destroy();
		location.reload();
	});
	

	
	//
	// Agent Users
	//
	
	// Code block for toggling edit buttons on/off when checkboxes are set
	jQuery('#tbl_templates_boxes tbody').on('click', 'input', function () {        

		//let rows_selected = dataTable.column(0).checkboxes.selected();
		let rows_selected = dataTable.column().checkboxes.selected();
		console.log( rows_selected );
		console.log( rows_selected.length );
		
		check_assign_box_status( rows_selected );
		
		
		
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	jQuery('.dt-checkboxes-select-all').on('click', 'input', function () {        
	 	//console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	jQuery('#wpsc_box_destruction_btn').attr('disabled', 'disabled'); 
	jQuery('#wppatt_assign_staff_btn').attr('disabled', 'disabled'); 
	jQuery('#wppatt_change_status_btn').attr('disabled', 'disabled');
	jQuery('#wpsc_individual_label_btn').attr('disabled', 'disabled');
	jQuery('#wpsc_individual_pallet_label_btn').attr('disabled', 'disabled');
	jQuery('.notab').attr('tabindex', '-1');
	
	function toggle_button_display() {
	//	var form = this;
	
		// Get the destroyed boxes link
		var destroyed_boxes = document.querySelectorAll(".text_highlight a[style*='color: #B4081A !important; text-decoration: underline line-through;']");
		
		console.log({checks:dataTable.column(0)});
		var rows_selected = dataTable.column(0).checkboxes.selected();
		if(rows_selected.count() > 0) {
		    rows_selected.each((el)=> {
				destroyed_boxes.forEach( function(element){
				
					if(el == element.textContent){
						console.log("there's a match: " + el + " " + element.textContent);
						jQuery('#wpsc_box_destruction_btn').removeAttr('disabled');
					}
				});
			});
		    // jQuery('#wpsc_box_destruction_btn').removeAttr('disabled');
			jQuery('#wppatt_assign_staff_btn').removeAttr('disabled');	
			jQuery('#wppatt_change_status_btn').removeAttr('disabled');
			jQuery('#wpsc_individual_label_btn').removeAttr('disabled');	
			jQuery('#wpsc_individual_pallet_label_btn').removeAttr('disabled');
	        jQuery('.notab').removeAttr('tabindex');			
	  	} else {
	  	    jQuery('#wpsc_box_destruction_btn').attr('disabled', 'disabled'); 
	    	jQuery('#wppatt_assign_staff_btn').attr('disabled', 'disabled');    	
	    	jQuery('#wppatt_change_status_btn').attr('disabled', 'disabled');    
	    	jQuery('#wpsc_individual_label_btn').attr('disabled', 'disabled');
	    	jQuery('#wpsc_individual_pallet_label_btn').attr('disabled', 'disabled');
	    	jQuery('.notab').attr('tabindex', '-1');
	  	}
	}
	
	// Assign Box Status Button Click
	jQuery('#wppatt_change_status_btn').click( function() {	
	
		let rows_selected = dataTable.column(0).checkboxes.selected();
	    let arr = [];
	    
	    let agent_type = '<?php echo $agent_permissions["label"] ?>';
		
		console.log( rows_selected );
		
	    // Loop through array
	    [].forEach.call(rows_selected, function(inst) {
	        console.log('the inst: '+inst);
	        arr.push(inst);
	    });
		
		wpsc_modal_open('Edit Box Status');
		
		var data = {
		    action: 'wppatt_change_box_status',
		    item_ids: arr,
		    type: 'edit',
		    agent_type: agent_type
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
	// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
	});
	
	
	// Assign Staff Button Click
	jQuery('#wppatt_assign_staff_btn').click( function() {	
	
		var rows_selected = dataTable.column(0).checkboxes.selected();
    var arr = [];

    // Loop through array
    [].forEach.call(rows_selected, function(inst){
        //console.log('the inst: '+inst);
        arr.push(inst);
    });
    
    console.log('arr: '+arr);
    console.log(arr);
		
		wpsc_modal_open('Edit Assigned Staff');
		
		var data = {
		    action: 'wppatt_assign_agents',
		    item_ids: arr,
		    page: 'boxes',
		    type: 'edit'
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
	// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
		dataTable.column(0).checkboxes.deselectAll();
	});


	<?php	
	// BEGIN ADMIN BUTTONS
	if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet'))
	{
	?>
	
	jQuery('#wpsc_individual_label_btn').on('click', function(e){
	     var form = this;
	     var rows_selected = dataTable.column(0).checkboxes.selected();
	     var rows_string = rows_selected.join(",");
	          console.log(rows_string);
	     jQuery.post(
	   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/boxlabels_processing.php',{
	postvarsboxid : rows_selected.join(",")
	}, 
	   function (response) {
	       
	       var boxidinfo = response.split('|')[1];
	       var substring_false = "false";
	       var substring_warn = "warn";
	       var substring_true = "true";
	       var substring_true_tabled = "true_tabled";
	       
	       if(response.indexOf(substring_true_tabled) >= 0) {
	       //alert('Success! All labels available.');
	       window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/preliminary_box_label.php?id="+boxidinfo, "_blank");
	       } else {
	       
	       if(response.indexOf(substring_false) >= 0) {
	       alert('Cannot print box labels that are part of a request(s) in the following statuses: New Request, Initial Review Rejected, Cancelled, Completed/ Dispositioned or not assigned a digitization center/destroyed or a mix of Tabled and other request statuses.');
	       }


	       if(response.indexOf(substring_warn) >= 0) {
	       alert('One or more boxes that you selected are part of a request(s) in the following statuses: New Request, Initial Review Rejected, Cancelled, Completed/ Dispositioned or not assigned a digitization center/destroyed and it\'s label will not generate.');
	       window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/box_label.php?id="+boxidinfo, "_blank");
	       }
	       
	       if(response.indexOf(substring_true) >= 0) {
	       //alert('Success! All labels available.');
	       window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/box_label.php?id="+boxidinfo, "_blank");
	       }
	       
	       }
	      
	   });
	
	});

	jQuery('#wpsc_individual_pallet_label_btn').on('click', function(e){
	     var form = this;
	     var rows_selected = dataTable.column(0).checkboxes.selected();
	     var rows_string = rows_selected.join(",");
	          console.log(rows_string);
	     jQuery.post(
	   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/palletlabels_processing.php',{
	postvarsboxid : rows_selected.join(",")
	}, 
	   function (response) {
	       //alert(response);
	       //window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/pallet_label.php?id="+response, "_blank");
	       
	       var palletinfo = response.split('|')[1];
	       var pallet_substring_false = "false";
	       var pallet_substring_true = "true";
	
	        
	       if(response.indexOf(pallet_substring_false) >= 0) {
	       alert('One or more boxes selected is in a status (New Request, Tabled, Initial Review Rejected, Cancelled, Completed/Dispositioned) that does not allow printing of pallet labels/does not have a pallet assigned.');
	       }
	       
	       if(response.indexOf(pallet_substring_true) >= 0) {
	       window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/pallet_label.php?id="+palletinfo, "_blank");
	       }
	   });
	
	});
	
	jQuery('#wpsc_box_destruction_btn').on('click', function(e){
	     var form = this;
	     var rows_selected = dataTable.column(0).checkboxes.selected();
			   jQuery.post(
	   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_destruction.php',{
	postvarsboxid : rows_selected.join(",")
	}, 
	   function (response) {
	      //if(!alert(response)){
	      
	      wpsc_modal_open('Destruction Completed');
			  var data = {
			    action: 'wpsc_get_destruction_completed_b',
			    response_data: response
			  };
			  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
			    var response = JSON.parse(response_str);
			    jQuery('#wpsc_popup_body').html(response.body);
			    jQuery('#wpsc_popup_footer').html(response.footer);
			    jQuery('#wpsc_cat_name').focus();
			  }); 
			  
	          dataTable.ajax.reload( null, false );
              dataTable.column(0).checkboxes.deselectAll();
	      //}
	   });
	});

	// User Seach
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
				no_requesters : true,
			}
			jQuery.getJSON( wpsc_admin.ajax_url, request, function( data, status, xhr ) {
				response(data);
			});
		},
		select: function (event, ui) {
			//console.log('label: '+ui.item.label+' flag_val: '+ui.item.flag_val); 							
			html_str = get_display_user_html(ui.item.label, ui.item.flag_val);
// 			jQuery('#assigned_agents').append(html_str);	
			
			// when adding new item, event listener functon must be added. 
			jQuery('#assigned_agents').append(html_str).on('click','.remove-user',function(){	
				//console.log('This click worked.');
				wpsc_remove_filter(this);
				dataTable.state.save();
				dataTable.draw();
			});
			
			dataTable.state.save();
			dataTable.draw();

			
			jQuery("#button_agent_submit").show();
		    jQuery(this).val(''); return false;
		}
	}).focus(function() {
			jQuery(this).autocomplete("search", "");
	});
	
	


	jQuery('.searched-user').on('click','.remove-user', function(e){
		//console.log('Removed a user 1');
		wpsc_remove_filter(this);
		dataTable.state.save();
		dataTable.draw();
	}); 


/*
	jQuery('.remove-user').on('click', function(e){
		console.log('Removed a user 1');
		wpsc_remove_filter(this);
		dataTable.state.save();
		dataTable.draw();
	}); 
*/

	
/*
	jQuery('.remove-user').click( function(x){
		console.log('Removed a user 2');
		console.log(x);
		wpsc_remove_filter(this);
		dataTable.state.save();
		dataTable.draw();
	});
*/
	


	<?php
	}
	// END ADMIN BUTTONS
	?>
}); // END Document READY

function get_display_user_html(user_name, termmeta_user_val) {
	//console.log("in display_user");
// 	var requestor_list = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();
	var requestor_list = jQuery("input[name='assigned_agent[]']").map(function(){return jQuery(this).val();}).get();
	
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
							+'<span  class="remove-user staff-close" ><i class="fa fa-times" aria-hidden="true" title="Remove User"></i><span class="sr-only"></span></span>'
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
	//console.log(requestor_list);
	//console.log('length: '+requestor_list.length);
	//console.log('single item? '+is_single_item);
	
	if( is_single_item ) {
		//console.log('doing single item stuff');
		if( requestor_list.length > 0 ) {
			jQuery("#button_agent_submit").show();
		} else {
			jQuery("#button_agent_submit").hide();
		}
	}
}


// Open Modal for viewing assigned staff
function view_assigned_agents( box_id ) {	
	
	//console.log('Icon!');
    var arr = [box_id];
    
    //console.log('arr: '+arr);
    //console.log(arr);
	
	wpsc_modal_open('View Assigned Staff');
	
	var data = {
	    action: 'wppatt_assign_agents',
	    item_ids: arr,
	    type: 'view'
	};
	jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
	    var response = JSON.parse(response_str);
// 		    jQuery('#wpsc_popup_body').html(response_str);		    
	    jQuery('#wpsc_popup_body').html(response.body);
	    jQuery('#wpsc_popup_footer').html(response.footer);
	    jQuery('#wpsc_cat_name').focus();
	}); 
// });
}

// Open Modal for editting todo items
function edit_to_do( box_id ) {	
	
	//console.log('Icon!');
    var arr = [box_id];
    
    //console.log('arr: '+arr);
    //console.log(arr);
	
	wpsc_modal_open('Edit To-Do List');
	
	var data = {
	    action: 'wppatt_assign_agents',
	    item_ids: arr,
	    type: 'todo'
	};
	jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
	    var response = JSON.parse(response_str);
// 		    jQuery('#wpsc_popup_body').html(response_str);		    
	    jQuery('#wpsc_popup_body').html(response.body);
	    jQuery('#wpsc_popup_footer').html(response.footer);
	    jQuery('#wpsc_cat_name').focus();
	}); 
// });
}

function wpsc_help_filters(){

	wpsc_modal_open('Information on Filters');
	var data = {
		action: 'wpsc_help_alert',
		post_name: 'help-filters'
	};
	jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		var response = JSON.parse(response_str);
		jQuery('#wpsc_popup_body').html(response.body);
		jQuery('#wpsc_popup_footer').html(response.footer);
		jQuery('#wpsc_cat_name').focus();
	});  
}
		
		
function check_assign_box_status( id_array ) { 
	
	
	let new_arr = [];
	
	
	//id_array.forEach( function( item, index ) {
	let i = 0;	
	while( i < id_array.length ) {
		new_arr.push( id_array[i] );
		i++;
	};
	
	var stuff = {
	    action: 'wppatt_box_status_changable_due_to_request_status',
	    id_array: new_arr
	};
	
	
	console.log({ id_array:id_array });
	console.log({new_arr:new_arr });
	console.log({stuff:stuff});
	
	jQuery.post( wpsc_admin.ajax_url, stuff, function( response_str ) {
	    let response = JSON.parse(response_str);
		console.log( response );
		
		if( response.in_restricted_status ) {
			//jQuery('#wppatt_change_status_btn').attr('disabled', 'disabled');
		}
	});
	
/*
	jQuery.ajax({
		type: "POST",
		url: wpsc_admin.ajax_url,
		data: data,
		//dataType: "json",
		//cache: false,
		success: function( response ) {
			
			console.log('the response I care about');
			console.log(response);
			
		}
	});
*/
	
}	
		
		
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



