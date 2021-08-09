<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;



//$GLOBALS['id'] = $_GET['id'];
$GLOBALS['id'] = '0000001-1';
$GLOBALS['pid'] = 'boxsearch';
$GLOBALS['page'] = 'boxdetails';


//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$general_appearance = get_option('wpsc_appearance_general_settings');


$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$create_recall_btn_css       = 'background-color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_text_color'].' !important;';

//$create_recall_btn_css = $action_default_btn_css;


$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';

$action_admin_btn_css = 'background-color:#5cbdea !important;color:#FFFFFF !important;';

$agent_permissions = $wpscfunction->get_current_agent_permissions();

?>

<div class="row wpsc_tl_action_bar" style="background-color:<?php echo $general_appearance['wpsc_action_bar_color']?> !important;">
  
  <div class="col-sm-12">
      
      <!-- PATT Begin -->
      <button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=recallcreate';" class="btn btn-sm wpsc_action_btn" style="<?php echo $create_recall_btn_css?>"><i class="fa fa-plus" aria-hidden="true" title="New Recall"></i><span class="sr-only">New Recall</span> New Recall</button>
      <button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=recall';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fa fa-list-ul" aria-hidden="true" title="Recall List"></i><span class="sr-only">Recall List</span> Recall List</button>
      <button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" style="<?php echo $action_default_btn_css?> margin-right: 30px !important;"><i class="fas fa-retweet" aria-hidden="true" title="Reset Filters"></i><span class="sr-only">Reset Filters</span> <?php _e('Reset Filters','supportcandy')?></button>
<!--       <button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=boxdetails';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-cloud-download-alt" title="Export"></i><span class="sr-only">Export</span> Export</button> -->

        <!--PATT End -->
<?php		
if ( ($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') )
{
?>      
<!-- PATT Begin -->
      <button type="button" id="wppatt_change_shipping_btn"  class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fa fa-truck" aria-hidden="true" title="Change Shipping Tracking Number"></i> Change Shipping Tracking Number <a href="#" aria-label="Change shipping tracking number" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-change-shipping'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Question"></i><span class="sr-only">Question</span></a></button>

<?php
}
?>	      
<!--       <button type="button" id="wppatt_change_status_btn" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fa fa-retweet"></i><span class="sr-only" title="Change Status">Change Status</span> Change Status</button>       -->
  
<!--       <button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=recalldetails&id=R-0000001';" class="btn btn-sm wpsc_action_btn" style="<?php echo $create_recall_btn_css?>"><i class="fas fa-vial" title="Recall Details"></i><span class="sr-only">Recall Details</span> Recall Details: R-0000001 </button> -->
      
<!--       <button type="button" id="wppatt_return_btn"  class="btn btn-sm wpsc_action_btn" style="<?php echo $create_recall_btn_css?>"><i class="fas fa-truck-loading" title="Recall"></i><span class="sr-only">Recall </span> Recall </button> -->
<!-- PATT End -->      
      
  </div>

</div>

<div class="row" style="background-color:<?php echo $general_appearance['wpsc_bg_color']?> !important;color:<?php echo $general_appearance['wpsc_text_color']?> !important;">

	<div class="col-sm-4 col-md-3 wpsc_sidebar individual_ticket_widget">

    	<div class="row" id="wpsc_status_widget" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_border_color']?> !important;">
			
			<!-- PATT Begin -->
			<h4 class="widget_header"><i class="fa fa-filter" aria-hidden="true" title="Filters"></i><span class="sr-only">Filters</span> Filters <a href="#" aria-label="Filter" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-filters'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Help Tooltip"></i><span class="sr-only">Help Tooltip</span></a></h4> 
            
            <!-- PATT End -->
            
            <hr class="widget_divider">
			<div class="wpsp_sidebar_labels">Enter one or more Recall IDs:<br>
				<input type='text' id='searchByRecallID' class="form-control" data-role="tagsinput"><br>
				
				
				<?php
					$po_array = Patt_Custom_Func::fetch_program_office_array(); 
				?>
				<input type="text" list="searchByProgramOfficeList" name="program_office" placeholder='Enter program office' id="searchByProgramOffice"/>
    <datalist id='searchByProgramOfficeList'>
     <?php foreach($po_array as $key => $value) { ?>
      
    <?php 
        $program_office = $wpdb->get_row("SELECT office_name FROM " . $wpdb->prefix . "wpsc_epa_program_office WHERE office_acronym  = '" . $value . "'");
        $office_name = $program_office->office_name;
        
        //Remove - if no characters after -
        $preg_replace_program_office = preg_replace("/\([^)]+\)/","",$value);
        if(substr($preg_replace_program_office, -1) == '-') {
            $new_program_office = substr($preg_replace_program_office, 0, -1);
        } else {
            $new_program_office = $preg_replace_program_office;
        }
    ?>
        <option data-value='<?php echo $value; ?>' value='<?php echo $new_program_office . ' : ' . $office_name; ?>'></option>
     <?php } ?>
     </datalist>
				<br><br>
				<select id='searchByDigitizationCenter' aria-label='Search by Digitization Center'>
					<option value=''>-- Select Digitization Center --</option>
					<option value='East'>East</option>
					<option value='West'>West</option>
					<option value='Not Assigned'>Not Assigned</option>
				</select>
				
				
<?php		
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') )
{
?>
<?php	
} else {
?>
<input type="hidden" id="current_user" name="current_user" value="<?php wp_get_current_user(); echo $current_user->nickname; ?>">
<input type="hidden" id="searchByUser" name="searchByUser" value="mine">
<?php		
}
?>	
				
				
			</div>
		</div>
	</div>
  
	<div class="col-sm-8 col-md-9 wpsc_it_body">

	<style>

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
	
	#searchByProgramOffice {
		width: 83%;
	}
	
	.fa-snowflake {
		color: #005C7A;
	}
	
	</style>
    

<div class="table-responsive" style="overflow-x:auto;">
<input type="text" id="searchGeneric" class="form-control" name="custom_filter[s]" value="" autocomplete="off" placeholder="Search...">

<!-- PATT Begin -->
<i class="fa fa-search wpsc_search_btn wpsc_search_btn_sarch" aria-hidden="true" title="Search Button"></i><span class="sr-only">Search Button</span>
<!-- PATT End -->

<br /><br />
<form id="frm-example" method="POST">
<table id="tbl_templates_recall" class="display nowrap" cellspacing="5" cellpadding="5">
        <thead>
            <tr>
<?php		
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') )
{
?>
                <th class="datatable_header" scope="col" ></th>
<?php
}
?>
	  			<th class="datatable_header" scope="col" >Recall ID</th>
	  			<th class="datatable_header" scope="col" >Status</th>
	  			<th class="datatable_header" scope="col" >Date Update</th>
	  			<th class="datatable_header" scope="col" >Request Date</th>
	  			<th class="datatable_header" scope="col" >Shipped Date</th>  <!-- Received Date -->
	  			<th class="datatable_header" scope="col" >Received Date</th>  <!-- Returned Date -->
<!-- 	  			<th class="datatable_header" scope="col" >Notification</th> -->
	  			<th class="datatable_header" scope="col" >Shipping Tracking Number</th>	  			
            </tr>
        </thead>
    </table>
</div>
<br><br>

<?php
$convert_box_id = $wpdb->get_row(
"SELECT id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE box_id = '" .  $GLOBALS['id'] . "'");

$box_id = $convert_box_id->id;
//echo 'new box id: '.$box_id;
?>
<input type='hidden' id='box_id' value='<?php echo $box_id; ?>' />
<input type='hidden' id='page' value='<?php echo $GLOBALS['page']; ?>' />
<input type='hidden' id='p_id' value='<?php echo $GLOBALS['pid']; ?>' />
</form>
<div id="test_test"></div>


<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>
<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.3/jquery.tagsinput.css" crossorigin="anonymous">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.3/jquery.tagsinput.js" crossorigin="anonymous"></script>
  
<script>


jQuery(document).ready(function(){

jQuery('[data-toggle="tooltip"]').tooltip(); 

	var agent_permission_label = '<?php echo $agent_permissions["label"] ?>';
	var is_requester = false;
	if( agent_permission_label == 'Requester' || agent_permission_label == 'Requester Pallet' ) {
		is_requester = true;
	}


  //NEW: dataTable for Recall
    var dataTable = jQuery('#tbl_templates_recall').DataTable({
	    'autoWidth': true,
	    'processing': true,
		'stateSave': true,
		//'scrollX' : true,
		"initComplete": function (settings, json) {     
		    jQuery("#tbl_templates_recall").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
		},
		'paging' : true,
			'stateSaveParams': function(settings, data) {
			data.sg = jQuery('#searchGeneric').val();
			data.bid = jQuery('#searchByRecallID').val();
			data.po = jQuery('#searchByProgramOffice').val();
			data.dc = jQuery('#searchByDigitizationCenter').val();
			data.page = jQuery('tbl_templates_boxes_length').val();
		},
		'stateLoadParams': function(settings, data) {
			jQuery('#searchGeneric').val(data.sg);
			jQuery('#searchByRecallID').val(data.bid);
			jQuery('#searchByProgramOffice').val(data.po);
			jQuery('#searchByDigitizationCenter').val(data.dc);
			jQuery('tbl_templates_boxes_length').val(data.page);
		},
	    'serverSide': true,
	    'serverMethod': 'post',
	    'searching': false, // Remove default Search Control
	    'ajax': {
	       'url':'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/recall_processing.php',
	       'data': function(data){
	          // Read values
	          var sbu = jQuery('#searchByUser').val();
			  var rs_user = jQuery('#current_user').val();	          
	          var po_value = jQuery('#searchByProgramOffice').val();
	          var po = jQuery('#searchByProgramOfficeList [value="' + po_value + '"]').data('value'); 
	          var sg = jQuery('#searchGeneric').val();
	          var boxid = jQuery('#searchByRecallID').val();
	          var dc = jQuery('#searchByDigitizationCenter').val();
	          // Append to data
	          data.searchGeneric = sg;
	          data.searchByRecallID = boxid;
// 	          data.searchByProgramOffice = po_value;
	          data.searchByProgramOffice = po;
	          data.searchByDigitizationCenter = dc;
	          data.searchByUser = sbu;
			  data.currentUser = rs_user;
			  data.is_requester = is_requester;	          
	       }
	    },
	    'drawCallback': function (settings) { 
	        // Here the response
	        var response = settings.json;
	        console.log(response);
    	},
		'lengthMenu': [[10, 25, 50, 100], [10, 25, 50, 100]],
	    'columnDefs': [
<?php		
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') )
{
?>	        
	        {
		    width: '2px',
	            'targets': 0,
	            'checkboxes': {
	               'selectRow': true
	            }	       
	        },
<?php
}
?>	        
	      { width: '65px', targets: 1 },
	      { width: '100px', targets: 2 },
	      { width: '5px', targets: 3 },
	      { width: '5px', targets: 4 },
	      { width: '5px', targets: 5 },
	      { width: '5px', targets: 6 },
// 	      { width: '5px', targets: 7 },
<?php		
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') )
{
?>		      
// 	      { width: '5px', targets: 8 }
	      { width: '5px', targets: 7 }
<?php
}
?>		      
	      ],
<?php		
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') )
{
?>
	      
	      'select': {
	         'style': 'multi'
	      },
// 	      'order': [[1, 'asc']],
//		  'order': [[3, 'desc']],
<?php
}
?>	      
	    'order': [[3, 'desc']],
	    'columns': [
<?php		
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
{
?>	 	    
	       { data: 'recall_id_flag', 'title': 'Select All Checkbox' },
<?php
}
?>	      
	       { data: 'recall_id', 'class' : 'text_highlight' },	
	              
	       { data: 'status' }, 
	       { data: 'updated_date' },
	       { data: 'request_date' },
	       { data: 'request_receipt_date' },
	       { data: 'return_date' },
// 	       { data: 'expiration_date' },
	       { data: 'tracking_number', 'class' : 'text_highlight' },

	    ]
	});
  
  
	jQuery('#toplevel_page_wpsc-tickets').removeClass('wp-not-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets').addClass('wp-has-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets').addClass('wp-menu-open'); 
	jQuery('#toplevel_page_wpsc-tickets a:first').removeClass('wp-not-current-submenu');
	jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-has-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-menu-open');
	jQuery('#menu-dashboard').removeClass('current');
	jQuery('#menu-dashboard a:first').removeClass('current');
  

	//
	// Code block for toggling edit buttons on/off when checkboxes are set
	//
	jQuery('#tbl_templates_recall tbody').on('click', 'input', function () {        
	// 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	// removes checkboxes when page is reloaded
	jQuery( window ).unload(function() {
		dataTable.column(0).checkboxes.deselectAll();
	});
	
	// allows the 'select all' checkbox to toggle the buttons	
	jQuery('.dt-checkboxes-select-all').on('click', 'input', function () {        
	 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});



	jQuery('#wppatt_change_status_btn').attr('disabled', 'disabled');
	jQuery('#wppatt_change_shipping_btn').attr('disabled', 'disabled');
	jQuery('#wppatt_return_btn').attr('disabled', 'disabled');
	
	function toggle_button_display() {
	//	var form = this;
		var rows_selected = dataTable.column(0).checkboxes.selected();
		if(rows_selected.count() > 0) {
	    	//console.log('boxes checked '+rows_selected.count());
			jQuery('#wppatt_change_status_btn').removeAttr('disabled');
			jQuery('#wppatt_change_shipping_btn').removeAttr('disabled');
			jQuery('#wppatt_return_btn').removeAttr('disabled');		
	  	} else {
	    	//console.log('no checks boxed '+rows_selected.count());
	    	jQuery('#wppatt_change_status_btn').attr('disabled', 'disabled');
	    	jQuery('#wppatt_change_shipping_btn').attr('disabled', 'disabled');    	
	    	jQuery('#wppatt_return_btn').attr('disabled', 'disabled');    	    	
	  	}
	}


	// function wppatt_get_status_editor() {
	jQuery('#wppatt_change_status_btn').click( function() {		
		
		var rows_selected = dataTable.column(0).checkboxes.selected();
	    var arr = [];
	
	    // Loop through array
	    [].forEach.call(rows_selected, function(inst){
	        console.log('the inst: '+inst);
	        arr.push(inst);
	    });
	
	    
	    console.log('arr: '+arr);
	    console.log(arr);
		
		wpsc_modal_open('Edit Status Details');
		
		var data = {
		    action: 'wppatt_recall_status_change',
		    recall_ids: arr
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
	// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		    //window.location.reload();
		}); 
	});
	
	
	
	jQuery('#wppatt_change_shipping_btn').click( function() {	
	
		var rows_selected = dataTable.column(0).checkboxes.selected();
	    var arr = [];
	
	    // Loop through array
	    [].forEach.call(rows_selected, function(inst){
	        console.log('the inst: '+inst);
	        arr.push(inst);
	    });
	    
	    console.log('arr: '+arr);
	    console.log(arr);
		
		wpsc_modal_open('Edit Shipping Details');
		
		var data = {
		    action: 'wppatt_recall_shipping_change',
		    recall_ids: arr,
		    from_page: 'recall-dashboard'
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
	// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
	});
	
	
	jQuery('#wppatt_return_btn').click( function() {	
	
		var rows_selected = dataTable.column(0).checkboxes.selected();
	    var arr = [];
	
	    // Loop through array
	    [].forEach.call(rows_selected, function(inst){
	        console.log('the inst: '+inst);
	        arr.push(inst);
	    });
	    
	    console.log('arr: '+arr);
	    console.log(arr);
		
		wpsc_modal_open('Initiate Return');
		
		var data = {
		    action: 'wppatt_initiate_return',
		    return_ids: arr
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
	// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 		
	
	});
	
	
	
	
	
	
	
	
	
	
	jQuery(document).on('keypress',function(e) {
	    if(e.which == 13) {
	        dataTable.draw();
	    }
	});
	
	jQuery("#searchByProgramOffice").change(function(){
	    dataTable.state.save();
	    dataTable.draw();
	});
	
	jQuery("#searchByDigitizationCenter").change(function(){
	    dataTable.draw();
	});
	
	//jQuery('#searchGeneric').on('input keyup paste', function () {
	//            dataTable.state.save();
	//            dataTable.draw();
	//});


    function onAddTag(tag) {
    	dataTable.draw();
    }
    function onRemoveTag(tag) {
    	dataTable.draw();
    }


	jQuery("#searchByRecallID").tagsInput({
	   'defaultText':'',
	   'onAddTag': onAddTag,
	   'onRemoveTag': onRemoveTag,
	   'width':'100%'
	});
	
	
	
	
	jQuery("#searchByRecallID_tag").on('paste',function(e){
	    var element=this;
	    setTimeout(function () {
	        var text = jQuery(element).val();
	        var target=jQuery("#searchByRecallID");
	        var tags = (text).split(/[ ,]+/);
	        for (var i = 0, z = tags.length; i<z; i++) {
	              var tag = jQuery.trim(tags[i]);
	              if (!target.tagExist(tag)) {
	                    target.addTag(tag);
	              }
	              else
	              {
	                  jQuery("#searchByRecallID_tag").val('');
	              }
	                
	         }
	    }, 0);
	});
	
	
	jQuery('#wpsc_individual_refresh_btn').on('click', function(e){
	    jQuery('#searchGeneric').val('');
	    jQuery('#searchByProgramOffice').val('');
	    jQuery('#searchByDigitizationCenter').val('');
	    jQuery('#searchByRecallID').importTags('');
	    jQuery('tbl_templates_boxes_length').val('10');
	    dataTable.column(0).checkboxes.deselectAll();
		dataTable.state.clear();
		//dataTable.draw(); // Not in Boxes.php. Try without.
		dataTable.destroy(); // NEW from Boxes.php
		location.reload(); // NEW from Boxes.php
		//return false; // Not in Boxes.php. Try without.
	});
	  
	
	
	// Handle form submission event 
   jQuery('#frm-example').on('submit', function(e){
      var form = this;
      console.log('this is never used, right - frm-example submit');
      var rows_selected = dataTable.column(0).checkboxes.selected();
      // Iterate over all selected checkboxes
      jQuery.each(rows_selected, function(index, rowId){
         // Create a hidden element 
         jQuery(form).append(
             jQuery('<input>')
                .attr('type', 'hidden')
                .attr('name', 'id[]')
                .val(rowId)
         );
         
        wpsc_modal_open('Edit Box Information');
      var data = {
        action: 'wpsc_get_rfid_box_editor',
        box_id : rows_selected.join(",")
      };
      jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
        var response = JSON.parse(response_str);
        jQuery('#wpsc_popup_body').html(response.body);
        jQuery('#wpsc_popup_footer').html(response.footer);
        jQuery('#wpsc_cat_name').focus();
      });  
      
      });
      // FOR DEMONSTRATION ONLY
      // The code below is not needed in production
      
      // Output form data to a console     
      //jQuery('#example-console-rows').text(rows_selected.join(","));
      
      // Output form data to a console     
      //jQuery('#example-console-form').text(jQuery(form).serialize());
       
      // Remove added elements
      //jQuery('input[name="id\[\]"]', form).remove();
       
      // Prevent actual form submission
      e.preventDefault();
   });   




  
});


function wppatt_return_editor() {
    console.log('return');
	wpsc_modal_open('Initiate Return');
	var data = {
	    action: 'wppatt_initiate_return',
	    //ticket_ids: ticket_ids
	};
	jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
	    var response = JSON.parse(response_str);
// 		    jQuery('#wpsc_popup_body').html(response_str);		    
	    jQuery('#wpsc_popup_body').html(response.body);
	    jQuery('#wpsc_popup_footer').html(response.footer);
	    jQuery('#wpsc_cat_name').focus();
	}); 	
}

</script>

  </div>
 

 

</div>



<!-- Pop-up snippet start from RFID.php-->
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