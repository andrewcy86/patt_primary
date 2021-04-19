<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

$subfolder_path = site_url( '', 'relative'); 

$GLOBALS['id'] = $_POST['id'];
$GLOBALS['pid'] = $_POST['pid'];
$GLOBALS['page'] = $_POST['page'];

$agent_permissions = $wpscfunction->get_current_agent_permissions();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$request_id = $wpdb->get_row("SELECT ".$wpdb->prefix."wpsc_ticket.request_id, ".$wpdb->prefix."wpsc_epa_boxinfo.box_id, ".$wpdb->prefix."wpsc_ticket.ticket_status
FROM ".$wpdb->prefix."wpsc_epa_boxinfo, ".$wpdb->prefix."wpsc_ticket 
WHERE ".$wpdb->prefix."wpsc_ticket.id = ".$wpdb->prefix."wpsc_epa_boxinfo.ticket_id AND ".$wpdb->prefix."wpsc_epa_boxinfo.box_id = '" . $GLOBALS['id'] . "'"); 
$location_request_id = $request_id->request_id;
$box_id_error_check = $request_id->box_id;
$request_status_id = $request_id->ticket_status;


$is_active = Patt_Custom_Func::request_status( $location_request_id );

if($is_active == 1) {
    $type = 'box';
}
else {
    $type = 'box_archive';
}

?>


<div class="row wpsc_tl_action_bar" style="background-color:<?php echo $general_appearance['wpsc_action_bar_color']?> !important;">
  
  <div class="col-sm-12">
    	<button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=wpsc-tickets';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fa fa-list-ul"></i> <?php _e('Ticket List','supportcandy')?> <a href="#" aria-label="Request list button" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-request-list-button'); ?>" aria-label="Request Help"><i class="far fa-question-circle"></i></a></button>
        	    <button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" style="<?php echo $action_default_btn_css?> margin-right: 30px !important;"><i class="fas fa-retweet"></i> <?php _e('Reset Filters','supportcandy')?></button>
        <?php		
        if ((($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1)
        {
        ?>
		<!--<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_validation_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-check-circle"></i> Validate</button>-->
		<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_destruction_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-flag"></i> Unauthorized Destruction <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-unauthorized-destruction'); ?>" aria-label="Unauthorized Destruction Help"><i class="far fa-question-circle"></i></a></button>
		<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_damaged_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-bolt"></i> Damaged </button>
		<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_freeze_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-snowflake"></i> Freeze <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-freeze-button'); ?>" aria-label="Freeze Help"><i class="far fa-question-circle"></i></a></button>
		<?php
		$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
		$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
		$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
		if($request_status_id != $new_request_tag->term_id && $request_status_id != $initial_review_rejected_tag->term_id && $request_status_id != $cancelled_tag->term_id) {
		?>
		<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_label_btn" style="<?php echo $action_default_btn_css?>"><i class="fas fa-tags"></i> Reprint Labels</button>
		<?php
		}
        }
        ?>

<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'requestdetails' && !empty($box_id_error_check)) {
?>
<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=wpsc-tickets&id=<?php echo Patt_Custom_Func::convert_box_request_id($GLOBALS['id']); ?>';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left"></"></i> Back to Request</button>
<?php
}
?>
<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'boxsearch' && !empty($box_id_error_check)) {
?>
<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=boxes';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left"></"></i> Back to Box Dashboard</button>
<?php
}
?>
<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'docsearch' && !empty($box_id_error_check)) {
?>
<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=folderfile';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left"></i> Back to Folder/File Dashboard</button>
<?php
}
?>
		
		
  </div>
	
</div>

<div class="row" style="background-color:<?php echo $general_appearance['wpsc_bg_color']?> !important;color:<?php echo $general_appearance['wpsc_text_color']?> !important;">

<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}$/", $GLOBALS['id']) && !empty($box_id_error_check)) {
//START REVIEW
if($is_active == 1) {
$convert_box_id = $wpdb->get_row("SELECT a.id, e.lan_id, sum(a.box_destroyed) as box_destroyed, sum(e.freeze) as freeze, c.name as box_status, a.box_status as box_status_id, a.box_id, d.ticket_priority as ticket_priority, (SELECT name as ticket_priority FROM ".$wpdb->prefix."terms WHERE term_id = d.ticket_priority) as priority_name, d.ticket_status as ticket_status, (SELECT name as ticket_status FROM ".$wpdb->prefix."terms WHERE term_id = d.ticket_status) as ticket_status_name
FROM ".$wpdb->prefix."wpsc_epa_boxinfo a
INNER JOIN ".$wpdb->prefix."terms c ON a.box_status = c.term_id
INNER JOIN ".$wpdb->prefix."wpsc_ticket d ON d.id = a.ticket_id
INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo_files e ON e.box_id = a.id
WHERE a.box_id = '" .  $GLOBALS['id'] . "'");
} else {
$convert_box_id = $wpdb->get_row("SELECT a.id, e.lan_id, sum(a.box_destroyed) as box_destroyed, sum(e.freeze) as freeze, c.name as box_status, a.box_status as box_status_id, a.box_id, d.ticket_priority as ticket_priority, (SELECT name as ticket_priority FROM ".$wpdb->prefix."terms WHERE term_id = d.ticket_priority) as priority_name, d.ticket_status as ticket_status, (SELECT name as ticket_status FROM ".$wpdb->prefix."terms WHERE term_id = d.ticket_status) as ticket_status_name
FROM ".$wpdb->prefix."wpsc_epa_boxinfo a
INNER JOIN ".$wpdb->prefix."terms c ON a.box_status = c.term_id
INNER JOIN ".$wpdb->prefix."wpsc_ticket d ON d.id = a.ticket_id
INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo_files_archive e ON e.box_id = a.id
WHERE a.box_id = '" .  $GLOBALS['id'] . "'");  
}
//END REVIEW
$the_real_box_id = $convert_box_id->box_id;
$box_id = $convert_box_id->id;
$box_lan_id = $convert_box_id->lan_id;
$box_destroyed = $convert_box_id->box_destroyed;
$box_freeze = $convert_box_id->freeze;
$box_status_id = $convert_box_id->box_status_id;
$ticket_priority_id = $convert_box_id->ticket_priority;
$ticket_status_id = $convert_box_id->ticket_status;

$box_pallet_id = Patt_Custom_Func::get_pallet_id_by_id($GLOBALS['id'], $type);

$status_background = get_term_meta($box_status_id, 'wpsc_box_status_background_color', true);
$status_color = get_term_meta($box_status_id, 'wpsc_box_status_color', true);
$status_style = "background-color:".$status_background.";color:".$status_color.";";
$box_status_name = $convert_box_id->box_status;
$box_status = "<span class='wpsp_admin_label' style='".$status_style."'>".$box_status_name."</span>";

$request_status_background = get_term_meta($ticket_status_id, 'wpsc_status_background_color', true);
$request_status_color = get_term_meta($ticket_status_id, 'wpsc_status_color', true);
$request_status_style = "background-color:".$request_status_background.";color:".$request_status_color.";";
$request_status_name = $convert_box_id->ticket_status_name;
$request_status = "<span class='wpsp_admin_label' style='".$request_status_style."'>".$request_status_name."</span>";

$priority_background = get_term_meta($ticket_priority_id, 'wpsc_priority_background_color', true);
$priority_color = get_term_meta($ticket_priority_id, 'wpsc_priority_color', true);
$priority_style = "background-color:".$priority_background.";color:".$priority_color.";";
$priority_name = $convert_box_id->priority_name;
$priority = "<span class='wpsp_admin_label' style='".$priority_style."'>".$priority_name."</span>";

?>

  <div class="col-sm-8 col-md-9 wpsc_it_body">
    <div class="row wpsc_it_subject_widget">
      <h3>
	 	 <?php if(apply_filters('wpsc_show_hide_ticket_subject',true)){
	 	 ?>
	 	 <?php if($box_destroyed > 0) { ?>
        	<span style="color:#FF0000 !important;<?php if($box_destroyed > 0 && $box_freeze == 0) { ?>text-decoration: line-through;<?php } ?>">
        <?php } ?>
        	[Box ID # <?php
            echo $GLOBALS['id']; ?>]<?php if($box_destroyed > 0) { ?></span> 
            <span style="font-size: .8em; color:#FF0000;"> <i class="fas fa-ban" title="Box Destroyed"></i></span>
            <?php } ?>
  
		  <?php } ?>	
		  
		  <?php
if($is_active == 0){
echo '<br /><span style="font-size: .8em; color:#FF0000;"><i class="fas fa-archive"></i> This box is archived</span><br />';
}
?>
      </h3>

    </div>
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
	width: 204px;
}
.bootstrap-iso .alert {
    padding: 8px;
}
#searchGeneric {
    padding: 0 30px !important;
}

.assign_agents_icon {
	cursor: pointer;
	margin: 0px 0px 5px 0px;
}

.wpsc_loading_icon {
	margin-top: 0px !important;
}

</style>

<div class="alert alert-danger" role="alert" id="ud_alert">
<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" title="Unauthorized Destruction"></i></span> One or more documents within this box contains an unauthorized destruction flag.
</div>

<div class="alert alert-warning" role="alert" id="damaged_alert">
<span style="font-size: 1em; color: #FFC300;"><i class="fas fa-bolt" title="Damaged"></i></span> One or more documents within this box contains a damaged folder/file.
</div>

<div class="alert alert-info" role="alert" id="freeze_alert">
<span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" title="Freeze"></i></span> One or more documents within this box contains a frozen folder/file.
</div>

<div class="table-responsive" style="overflow-x:auto;">
<input type="text" id="searchGeneric" class="form-control" name="custom_filter[s]" value="" autocomplete="off" placeholder="Search...">
<i class="fa fa-search wpsc_search_btn wpsc_search_btn_sarch"></i>
<br />
<form id="frm-example" method="POST">
<table id="tbl_templates_box_details" class="display nowrap" cellspacing="5" cellpadding="5" width="100%">
        <thead>
            <tr>
                    <?php		
                        if ((($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1)
                    {
                    ?>
                    <th class="datatable_header"></th>
                    <?php
                    }
                    ?>
    	  			<th class="datatable_header">ID</th>
                    <th class="datatable_header">DB ID</th>
    	  			<th class="datatable_header">Title</th>
    	  			<th class="datatable_header">Date</th>
    	  			<th class="datatable_header">EPA Contact</th>
    	  			<th class="datatable_header">Validation</th>
            </tr>
        </thead>
    </table>
</div>
<br /><br />

<input type='hidden' id='box_id' value='<?php echo $box_id; ?>' />
<input type='hidden' id='page' value='<?php echo $GLOBALS['page']; ?>' />
<input type='hidden' id='p_id' value='<?php echo $GLOBALS['pid']; ?>' />
<input type='hidden' id='is_active' value='<?php echo $is_active; ?>' />
</form>
<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>

<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>


<script>

jQuery(document).ready(function(){
  var dataTable = jQuery('#tbl_templates_box_details').DataTable({
    'autoWidth': true,
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'stateSave': false,
    //'scrollX' : true,
    "initComplete": function (settings, json) {
        jQuery("#tbl_templates_box_details").wrap("<div style='overflow:auto; width:100%;position:relative;'></div>");
    },
    'paging' : true,
    'drawCallback': function( settings ) {
        jQuery('[data-toggle="tooltip"]').tooltip();
        	var response = settings.json;
	        console.log(response);
     },
    //'stateSaveParams': function(settings, data) {
    //  data.sg = jQuery('#searchGeneric').val();
    //},
    //'stateLoadParams': function(settings, data) {
    //  jQuery('#searchGeneric').val(data.sg);
    //},
    'searching': false, // Remove default Search Control
    'ajax': {
       'url':'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/box_details_processing.php',
       'data': function(data){
          // Read values
          var sg = jQuery('#searchGeneric').val();
          var boxid = jQuery('#box_id').val();
          var page = jQuery('#page').val();
          var pid = jQuery('#p_id').val();
          var isactive = jQuery('#is_active').val();
          // Append to data
          data.searchGeneric = sg;
          data.BoxID = boxid;
          data.PID = pid;
          data.page = page;
          data.isactive = isactive;
       }
    },
    "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    <?php		
    if ((($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1)
    {
    ?>
    'columnDefs': [
		{	'width' : 5,
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
      { width: '25%', targets: 1 },
      { width: '25%', targets: 3 },
      { width: '25%', targets: 4 },
      { width: '20%', targets: 5 },
      { width: '5%', targets: 6 }
      ],
      'select': {
         'style': 'multi'
      },
      'order': [[1, 'asc']],
      <?php
      }
      ?>	
    'columns': [
        <?php		
            if ((($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1)
        {
        ?>
       { data: 'folderdocinfo_id' },
       <?php
        }
        ?>
       { data: 'folderdocinfo_id_flag' },
       { data: 'dbid', visible: false},
       { data: 'title' }, 
       { data: 'date' },
       { data: 'epa_contact_email' },
       { data: 'validation' },
    ]
  });
  
  jQuery( window ).unload(function() {
  dataTable.column(0).checkboxes.deselectAll();
});

  jQuery(document).on('keypress',function(e) {
    if(e.which == 13) {
        //dataTable.state.save();
        dataTable.draw();
    }
});

jQuery('#searchGeneric').on('keypress', function () {
    if(e.which == 13) {
        //dataTable.state.save();
        dataTable.draw();
    }
});

/*
		function onAddTag(tag) {
		    dataTable.state.save();
			dataTable.draw();
		}
		function onRemoveTag(tag) {
		    dataTable.state.save();
		    dataTable.draw();
		}
*/
jQuery('#wpsc_individual_refresh_btn').on('click', function(e){
    jQuery('#searchGeneric').val('');
    dataTable.column(0).checkboxes.deselectAll();
	dataTable.state.clear();
	dataTable.destroy();
	location.reload();
});

    <?php
    // BEGIN ADMIN BUTTONS
        if ((($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1)
    {
    ?>
    //validation button
	jQuery('#wpsc_individual_validation_btn').on('click', function(e){
     var form = this;
     var rows_selected = dataTable.column(0).checkboxes.selected();
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_validate.php',{
postvarsfolderdocid : rows_selected.join(","),
postvarsuserid : <?php $user_ID = get_current_user_id(); echo $user_ID; ?>,
postvarpage : jQuery('#page').val()
}, 
   function (response) {
      //if(!alert(response)){
          wpsc_modal_open('Validation');
		  var data = {
		    action: 'wpsc_get_validate_bd',
		    response_data: response
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
          dataTable.ajax.reload( null, false );
      //}
   });
});
// Code block for toggling edit buttons on/off when checkboxes are set
	jQuery('#tbl_templates_box_details tbody').on('click', 'input', function () {        
	// 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	jQuery('.dt-checkboxes-select-all').on('click', 'input', function () {        
	 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	jQuery('#wpsc_individual_destruction_btn').attr('disabled', 'disabled');
	jQuery('#wpsc_individual_damaged_btn').attr('disabled', 'disabled');
	jQuery('#wpsc_individual_freeze_btn').attr('disabled', 'disabled');
	jQuery('#wpsc_individual_label_btn').attr('disabled', 'disabled');
	
	function toggle_button_display() {
	//	var form = this;
		var rows_selected = dataTable.column(0).checkboxes.selected();
		if(rows_selected.count() > 0) {
			jQuery('#wpsc_individual_destruction_btn').removeAttr('disabled');
			jQuery('#wpsc_individual_damaged_btn').removeAttr('disabled');
			jQuery('#wpsc_individual_freeze_btn').removeAttr('disabled');
        	jQuery('#wpsc_individual_label_btn').removeAttr('disabled');
	  	} else {
	    	jQuery('#wpsc_individual_destruction_btn').attr('disabled', 'disabled');
	    	jQuery('#wpsc_individual_damaged_btn').attr('disabled', 'disabled');
	    	jQuery('#wpsc_individual_freeze_btn').attr('disabled', 'disabled');
        	jQuery('#wpsc_individual_label_btn').attr('disabled', 'disabled');
	  	}
	}
	
jQuery('#wpsc_individual_destruction_btn').on('click', function(e){
     var form = this;
     var rows_selected = dataTable.column(0).checkboxes.selected();
		  wpsc_modal_open('Unauthorized Destruction');
		  var data = {
		    action: 'wpsc_unauthorized_destruction_ffd',
		    postvarsfolderdocid :  rows_selected.join(","),
            postvarpage : jQuery('#page').val(),
            boxid : '<?php echo $the_real_box_id; ?>',
            pid : jQuery('#p_id').val()
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
		  dataTable.ajax.reload( null, false );
		  dataTable.column(0).checkboxes.deselectAll();
});

/*//unauthorized destruction button
jQuery('#wpsc_individual_destruction_btn').on('click', function(e){
     var form = this;
     var rows_selected = dataTable.column(0).checkboxes.selected();
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_unauthorize_destruction.php',{
postvarsfolderdocid : rows_selected.join(","),
postvarpage : jQuery('#page').val(),
boxid : jQuery('#box_id').val()
}, 
   function (response) {
      //if(!alert(response)){
      
      	  wpsc_modal_open('Unauthorized Destruction');
		  var data = {
		    action: 'wpsc_get_unauthorized_destruction_bd',
		    response_data: response
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
		  
       var substring_removed = "removed";
       var substring_select = "select";
       dataTable.ajax.reload( null, false );
       
       if(response.indexOf(substring_removed) !== -1 || response.indexOf(substring_select) >= 0) {
       jQuery('#ud_alert').hide();
       } else {
       jQuery('#ud_alert').show(); 
       }
       
      //}
   });
});
*/

//damaged button
jQuery('#wpsc_individual_damaged_btn').on('click', function(e){
     var form = this;
     var rows_selected = dataTable.column(0).checkboxes.selected();
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_damaged.php',{
postvarsfolderdocid : rows_selected.join(","),
postvarpage : jQuery('#page').val(),
boxid : jQuery('#box_id').val()
}, 
   function (response) {
      //if(!alert(response)){
      wpsc_modal_open('Damaged');
		  var data = {
		    action: 'wpsc_get_damaged_bd',
		    response_data: response,
		    response_page: '<?php echo $GLOBALS['page']; ?>'
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
      
       var substring_removed = "removed";
       var substring_select = "select";
       dataTable.ajax.reload( null, false );
       dataTable.column(0).checkboxes.deselectAll();
       if(response.indexOf(substring_removed) !== -1 || response.indexOf(substring_select) >= 0) {
       jQuery('#damaged_alert').hide();
       } else {
       jQuery('#damaged_alert').show(); 
       }
       
      //}
   });
});

//freeze button
jQuery('#wpsc_individual_freeze_btn').on('click', function(e){
     var form = this;
     var rows_selected = dataTable.column(0).checkboxes.selected();
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_freeze.php',{
postvarsfolderdocid : rows_selected.join(","),
postvarpage : jQuery('#page').val(),
boxid : jQuery('#box_id').val()
}, 
   function (response) {
      //if(!alert(response)){
      wpsc_modal_open('Freeze');
		  var data = {
		    action: 'wpsc_get_freeze_bd',
		    response_data: response,
		    response_page: '<?php echo $GLOBALS['page']; ?>'
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
      
       var substring_removed = "removed";
       var substring_select = "select";
       dataTable.ajax.reload( null, false );
       dataTable.column(0).checkboxes.deselectAll();
       if(response.indexOf(substring_removed) !== -1 || response.indexOf(substring_select) >= 0) {
       jQuery('#freeze_alert').hide();
       } else {
       jQuery('#freeze_alert').show(); 
       }
       
      //}
   });
});

//reprint labels button
jQuery('#wpsc_individual_label_btn').on('click', function(e){
     var form = this;
     var rows_selected = dataTable.column(0).checkboxes.selected();
     var arr = {};
     jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/documentlabels_processing.php',{
postvarsfolderdocid : rows_selected.join(",")
}, 
   function (response) {
       
       var folderdocinfo = response.split('|')[1];
       var folderdocinfo_array = folderdocinfo.split(',');
       var substring_false = "false";
       var substring_warn = "warn";
       var substring_true = "true";

       if(response.indexOf(substring_false) >= 0) {
       alert('Cannot print folder/file labels for documents that have been destroyed or are not assigned to a location.');
       }
       
       if(response.indexOf(substring_warn) >= 0) {
       alert('One or more documents that you have selected have been destroyed or do not have an assigned location and it\'s label will not generate.');
           // Loop through array
    [].forEach.call(folderdocinfo_array, function(inst){
        var x = inst.split("-")[2].substr(1);
        // Check if arr already has an index x, if yes then push
        if(arr.hasOwnProperty(x)) 
            arr[x].push(inst);
        // Or else create a new one with inst as the first element.
        else 
            arr[x] = [inst];
    });
if(Array.isArray(arr[1]) || Array.isArray(arr[2]) ) {
if (Array.isArray(arr[1]) && arr[1].length) {
window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/folder_separator_sheet.php?id="+arr[1].toString(), "_blank");
}
if (Array.isArray(arr[2]) && arr[2].length) {
window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/file_separator_sheet.php?id="+arr[2].toString(), "_blank");
}
} else {
alert('Please select a folder/file.');
}
       }
       
       if(response.indexOf(substring_true) >= 0) {
       //alert('Success! All labels available.');
           // Loop through array
    [].forEach.call(folderdocinfo_array, function(inst){
        var x = inst.split("-")[2].substr(1);
        // Check if arr already has an index x, if yes then push
        if(arr.hasOwnProperty(x)) 
            arr[x].push(inst);
        // Or else create a new one with inst as the first element.
        else 
            arr[x] = [inst];
    });
if(Array.isArray(arr[1]) || Array.isArray(arr[2]) ) {
if (Array.isArray(arr[1]) && arr[1].length) {
window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/folder_separator_sheet.php?id="+arr[1].toString(), "_blank");
}
if (Array.isArray(arr[2]) && arr[2].length) {
window.open("<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/file_separator_sheet.php?id="+arr[2].toString(), "_blank");
}
} else {
alert('Please select a folder/file.');
}
       }
      
   });

});

<?php
}
//END ADMIN BUTTONS
?>

	 jQuery('#toplevel_page_wpsc-tickets').removeClass('wp-not-current-submenu'); 
	 jQuery('#toplevel_page_wpsc-tickets').addClass('wp-has-current-submenu'); 
	 jQuery('#toplevel_page_wpsc-tickets').addClass('wp-menu-open'); 
	 jQuery('#toplevel_page_wpsc-tickets a:first').removeClass('wp-not-current-submenu');
	 jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-has-current-submenu'); 
	 jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-menu-open');
	 jQuery('#menu-dashboard').removeClass('current');
	 jQuery('#menu-dashboard a:first').removeClass('current');

<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'boxsearch') {
?>
	 jQuery('.wp-submenu li:nth-child(3)').addClass('current');
<?php
}
?>
<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'docearch') {
?>
	 jQuery('.wp-submenu li:nth-child(4)').addClass('current');
<?php
}
?>

<?php
//unauthorized destruction notification
$unauthorized_destruction_count = Patt_Custom_Func::id_in_unauthorized_destruction($GLOBALS['id'], $type);
if($unauthorized_destruction_count != 1){
?>
jQuery('#ud_alert').hide();
<?php
}

//damaged notification
$damaged_count = Patt_Custom_Func::id_in_damaged($GLOBALS['id'], $type);
if($damaged_count != 1) { ?>
jQuery('#damaged_alert').hide();
<?php
}
?>

<?php
//freeze notification
$freeze_count = Patt_Custom_Func::id_in_freeze($GLOBALS['id'], $type);
if($freeze_count != 1) {
?>
jQuery('#freeze_alert').hide();
<?php
}
?>

});
       
</script>


  </div>
 
 <?php
    $location_program_office = Patt_Custom_Func::get_program_office_by_id($GLOBALS['id'],$type);
    $location_record_schedule = Patt_Custom_Func::get_record_schedule_by_id($GLOBALS['id'],$type); 
    
    $box_location = $wpdb->get_row("SELECT ".$wpdb->prefix."terms.name as digitization_center, aisle, bay, shelf, position FROM ".$wpdb->prefix."terms, ".$wpdb->prefix."wpsc_epa_storage_location, ".$wpdb->prefix."wpsc_epa_boxinfo WHERE ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."wpsc_epa_storage_location.digitization_center AND ".$wpdb->prefix."wpsc_epa_storage_location.id = ".$wpdb->prefix."wpsc_epa_boxinfo.storage_location_id AND ".$wpdb->prefix."wpsc_epa_boxinfo.box_id = '" . $GLOBALS['id'] . "'");
    $location_digitization_center = $box_location->digitization_center;
    $location_aisle = $box_location->aisle;
    $location_bay = $box_location->bay;
    $location_shelf = $box_location->shelf;
    $location_position = $box_location->position;
    
    $general_box_location = $wpdb->get_row("SELECT locations FROM ".$wpdb->prefix."wpsc_epa_location_status, ".$wpdb->prefix."wpsc_epa_boxinfo WHERE ".$wpdb->prefix."wpsc_epa_boxinfo.location_status_id = ".$wpdb->prefix."wpsc_epa_location_status.id AND ".$wpdb->prefix."wpsc_epa_boxinfo.box_id = '" . $GLOBALS['id'] . "'");
    $location_general = $general_box_location->locations;
    
    
    //
    // Box Statuses
    //
    
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
	$ignore_box_status = ['Pending', 'Ingestion', 'Completed', 'Dispositioned'];
	
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
 
	<div class="col-sm-4 col-md-3 wpsc_sidebar individual_ticket_widget">
<?php
$lan_id = $wpdb->get_row("SELECT DISTINCT count(lan_id) as count FROM ".$wpdb->prefix."wpsc_epa_folderdocinfo_files WHERE lan_id != '' AND box_id = '" . $box_id . "'");
$lan_id_count = $lan_id->count;

if ($lan_id_count >=1) {
?>
		<div class="row" id="wpsc_status_widget" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_border_color']?> !important;">
      <h4 class="widget_header"><i class="fa fa-user"></i> EPA Contact(s)</h4>
			<hr class="widget_divider">
<?php
$lan_id = $wpdb->get_results("SELECT DISTINCT lan_id, lan_id_details FROM ".$wpdb->prefix."wpsc_epa_folderdocinfo_files WHERE lan_id != '' AND box_id = '" . $box_id . "'");
$details_count = 0;
$contact_details = '';
foreach ($lan_id as $info) {
$lan_id_details = $info->lan_id_details;

if($lan_id_details == '') {
$details_count++;
}

$obj = json_decode($lan_id_details);
$lan_id_username = $info->lan_id;
$contact_details .= '<li>'.$obj->{'name'}. ' ('.$lan_id_username.')</li>';
} 

if($details_count == 0) {
echo '<ul style="list-style-type: disc !important; padding-left: 15px;">';
echo $contact_details;
echo '</ul>';
} else {
echo '<div class="wpsp_sidebar_labels" style="color: red;"><strong>Pending update...</strong></div>';
}
?>
	</div>
<?php
}
?>
<!-- 	</div> -->
	
<!-- 	<div class="col-sm-4 col-md-3 wpsc_sidebar individual_ticket_widget"> -->
		<div class="row" id="wpsc_status_widget" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_border_color']?> !important;">
      <h4 class="widget_header"><i class="fa fa-arrow-circle-right"></i> Edit Box Details
			<!--only admins/agents have the ability to edit box details-->
			<?php
			    $agent_permissions = $wpscfunction->get_current_agent_permissions();
                $agent_permissions['label'];
                if ( (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1)
                {
                  echo '<button id="wpsc_individual_change_ticket_status" onclick="wpsc_get_box_editor('.$box_id.');" class="btn btn-sm wpsc_action_btn" style="background-color:#FFFFFF !important;color:#000000 !important;border-color:#C3C3C3!important"><i class="fas fa-edit"></i></button>';
                } 
			?>
			
			</h4>
			<hr class="widget_divider">
			<!--error handling implemented, will not display a field if it is empty/null-->
			<?php 
				
            if(!empty($location_request_id)) {
                echo "<div class='wpsp_sidebar_labels'><strong>Request ID: </strong><br /> <a href='admin.php?page=wpsc-tickets&id=" . $location_request_id . "'>" . $location_request_id . "</a></div>";
            }
            
            if(!empty($request_status)) {
                echo '<div class="wpsp_sidebar_labels"><strong >Request Status: </strong><br />' . $request_status . '</div>';
            }
            
            if(!empty($priority)) {
                echo '<div class="wpsp_sidebar_labels"><strong >Priority: </strong><br />' . $priority . '</div>';
            }
            
            if(!empty($box_status)) {
                echo '<div class="wpsp_sidebar_labels"><strong >Box Status: </strong><br />' . $box_status . '</div>';
            }
            
            if(!empty($box_pallet_id)) {
                echo '<div class="wpsp_sidebar_labels"><strong >Pallet ID: </strong><br />' . $box_pallet_id . '</div>';
            }
            
            if(!empty($location_program_office)) {
                /*
                //Remove - if no characters after -
                $preg_replace_program_office = preg_replace("/\([^)]+\)/","",$location_program_office);
                if(substr($preg_replace_program_office, -1) == '-') {
                    $new_program_office = substr($preg_replace_program_office, 0, -1);
                } else {
                    $new_program_office = $preg_replace_program_office;
                }
                */
                echo '<div class="wpsp_sidebar_labels"><strong>Program Office: </strong><br />' . $location_program_office . '</div>';
            }
            else {
                echo '<div class="wpsp_sidebar_labels"><strong style="color:red">Program Office:<br /> REASSIGN IMMEDIATELY</strong> </div>';
                
                //if program office is empty send a notification to all admins/managers and email ecms@epa.gov
                $agent_admin_group_name = 'Administrator';
                $pattagentid_admin_array = Patt_Custom_Func::agent_from_group($agent_admin_group_name);
                $agent_manager_group_name = 'Manager';
                $pattagentid_manager_array = Patt_Custom_Func::agent_from_group($agent_manager_group_name);
                $pattagentid_array = array_merge($pattagentid_admin_array,$pattagentid_manager_array);
                $data = [];
                
                $email = 1;
                Patt_Custom_Func::insert_new_notification('email-malformed-data-for-program-office',$pattagentid_array,$the_real_box_id,$data,$email);
            }
            
            if(!empty($location_record_schedule)) {
                echo '<div class="wpsp_sidebar_labels"><strong >Record Schedule: </strong><br />' . $location_record_schedule . '</div>';
            }
            else {
                echo '<div class="wpsp_sidebar_labels"><strong style="color:red">Record Schedule:<br /> REASSIGN IMMEDIATELY</strong> </div>';
            
                //if record schedule is empty send a notification to all admins/managers and email ecms@epa.gov
                $agent_admin_group_name = 'Administrator';
                $pattagentid_admin_array = Patt_Custom_Func::agent_from_group($agent_admin_group_name);
                $agent_manager_group_name = 'Manager';
                $pattagentid_manager_array = Patt_Custom_Func::agent_from_group($agent_manager_group_name);
                $pattagentid_array = array_merge($pattagentid_admin_array,$pattagentid_manager_array);
                $data = [];
                
                $email = 1;
                Patt_Custom_Func::insert_new_notification('email-malformed-data-for-record-schedule',$pattagentid_array,$the_real_box_id,$data,$email);
            }
            
            if(!empty($location_digitization_center)) {
                echo '<div class="wpsp_sidebar_labels"><strong>Digitization Center: </strong><br />' . $location_digitization_center . '</div>';
                
                if(!empty($location_general) && Patt_Custom_Func::id_in_physical_location($GLOBALS['id'], $type) != '') {
			        echo '<div class="wpsp_sidebar_labels"><strong>Physical Location: </strong><br />' . $location_general . ' (' . Patt_Custom_Func::id_in_physical_location($GLOBALS['id'], $type) . ') </div>';
			    }
			    else {
			        echo '<div class="wpsp_sidebar_labels"><strong>Physical Location: </strong><br />' . $location_general . '</div>';
			    }
			    
			    //checks to make sure location of box is 'On Shelf' and that aisle/bay/shelf/position != 0
			       //for testing physical location can only be 'Pending', so 'On Shelf' won't be a requirement
			       if($location_general == 'On Shelf' && (!($location_aisle <= 0 || $location_bay <= 0 || $location_shelf <= 0 || $location_position <= 0))) {
			       //if((!($location_aisle <= 0 || $location_bay <= 0 || $location_shelf <= 0 || $location_position <= 0))) {
    			        echo '<div class="wpsp_sidebar_labels"><strong>Aisle: </strong>' . $location_aisle . '</div>';
    			        echo '<div class="wpsp_sidebar_labels"><strong>Bay: </strong>' . $location_bay . '</div>';
    			        echo '<div class="wpsp_sidebar_labels"><strong>Shelf: </strong>' . $location_shelf . '</div>';
    			        echo '<div class="wpsp_sidebar_labels"><strong>Position: </strong>' . $location_position . '</div>';
			        } 
            }
			?>
			
			
	</div>
<!-- 	</div> -->
	
	
	
	
<!-- 	<div class="col-sm-4 col-md-3 wpsc_sidebar individual_ticket_widget"> -->
		<div class="row" id="wpsc_status_widget" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_border_color']?> !important;">
      <h4 class="widget_header"><i class="fa fa-user-plus"></i> Assigned Staff
			<!--only admins/agents have the ability to edit box details-->
			<?php
			    $agent_permissions = $wpscfunction->get_current_agent_permissions();
                $agent_permissions['label'];
                if ( (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1)
                {
                  echo '<button id="wpsc_individual_change_ticket_status" onclick="wpsc_get_assigned_staff_editor(\''.$the_real_box_id.'\');" class="btn btn-sm wpsc_action_btn" style="background-color:#FFFFFF !important;color:#000000 !important;border-color:#C3C3C3!important"><i class="fas fa-edit"></i></button>';
                } 
			?>
			
			</h4>
			<hr class="widget_divider">
			<div style="font-size: 1.0em; color: #1d1f1d;" onclick="view_assigned_agents( '<?php echo $the_real_box_id ?>' )" class="assign_agents_icon"><i class="fas fa-user-friends" title="Assigned Agents"></i>    View Assigned Staff</div>
			<!--error handling implemented, will not display a field if it is empty/null-->
<!--
			<?php 
		        foreach($box_statuses as $status ) {
		            echo '<div class="wpsp_sidebar_labels"><strong>'.$status->name.': </strong>'.'[name]'.'</div>';
		        }
            ?>	
-->		
	</div>
	</div>	
	
<?php
} else {

echo '<span style="padding-left: 10px">Please pass a valid Box ID</span>';

}
?>
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

<script>

jQuery(document).ready(function(){
  jQuery('[data-toggle="tooltip"]').tooltip();
});

function wpsc_get_box_editor(box_id){

	wpsc_modal_open('Edit Box Details');
	var data = {
		action: 'wpsc_get_box_editor',
		box_id: box_id
	};
	jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		var response = JSON.parse(response_str);
		jQuery('#wpsc_popup_body').html(response.body);
		jQuery('#wpsc_popup_footer').html(response.footer);
		jQuery('#wpsc_cat_name').focus();
	});  
}

function wpsc_get_assigned_staff_editor(box_id){
	
	let arr = [box_id];
	console.log('The Arr');
	console.log(arr);
	
	wpsc_modal_open('Edit Assigned Staff');
	var data = {
		action: 'wppatt_assign_agents',
	    item_ids: arr,
	    page: 'boxdetails',
	    type: 'edit'
	};
	jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		var response = JSON.parse(response_str);
		jQuery('#wpsc_popup_body').html(response.body);
		jQuery('#wpsc_popup_footer').html(response.footer);
		jQuery('#wpsc_cat_name').focus();
	});  
}

// Open Modal for viewing assigned staff
function view_assigned_agents( box_id ) {	
	
    var arr = [box_id];
	
	wpsc_modal_open('View Assigned Staff');
	
	var data = {
	    action: 'wppatt_assign_agents',
	    item_ids: arr,
	    type: 'view'
	};
	jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
	    var response = JSON.parse(response_str);
	    jQuery('#wpsc_popup_body').html(response.body);
	    jQuery('#wpsc_popup_footer').html(response.footer);
	    jQuery('#wpsc_cat_name').focus();
	}); 
}
</script>