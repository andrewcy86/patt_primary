<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

$agent_permissions = $wpscfunction->get_current_agent_permissions(); 
$agent_permissions['label']; 
$agent_type = $agent_permissions['label']; // Administrator, Agent, Manager

$subfolder_path = site_url( '', 'relative');
//echo 'subfolder_path';

if (!isset($_SESSION)) {
    session_start();    
}

$box_id = $_POST["box_id"];
//need to pass ticket id to push metadata      
ob_start();

$patt_box_id_arr = array();

    $box_patt_id = $wpdb->get_row("SELECT box_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE id = '" . $box_id . "'");
    $patt_box_id = $box_patt_id->box_id;
    array_push($patt_box_id_arr,$patt_box_id);
    
    $box_program_office = $wpdb->get_row("SELECT b.office_acronym as acronym 
    FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a INNER JOIN " . $wpdb->prefix . "wpsc_epa_program_office as b ON a.program_office_id = b.office_code
    WHERE box_id = '" . $box_id . "'");
    $program_office = $box_program_office->acronym;
    
    $box_record_schedule = $wpdb->get_row("SELECT c.Record_Schedule_Number as record_schedule_number 
    FROM " . $wpdb->prefix . "wpsc_epa_boxinfo as a INNER JOIN " . $wpdb->prefix . "epa_record_schedule as c ON record_schedule_id = c.id
    WHERE box_id = '" . $box_id . "'");
    $record_schedule = $box_record_schedule->record_schedule_number;
    
    $box_dc = $wpdb->get_row("SELECT a.box_destroyed, SUM(fdif.validation = 1) as validated, COUNT(fdif.validation) as validation_total
	FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
	INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo b ON a.id = b.box_id 
	INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files fdif ON fdif.folderdocinfo_id = b.id 
	WHERE a.id = '" . $box_id . "'");
							
/*	OLD: before moving validation to fdi_files table
    $box_dc = $wpdb->get_row("SELECT a.box_destroyed, SUM(b.validation = 1) as validated, COUNT(b.validation) as validation_total
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo b ON a.id = b.box_id
WHERE a.id = '" . $box_id . "'");
*/

    $dc = $box_dc->box_destroyed;
    $validated = $box_dc->validated;
    $validation_total = $box_dc->validation_total;
    
    $box_status = $wpdb->get_row("SELECT " . $wpdb->prefix . "terms.term_id as box_status FROM " . $wpdb->prefix . "terms, " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.box_status AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = '" . $box_id . "'");
    $status_id = $box_status->box_status;
    
    $box_destruction_approval = $wpdb->get_row("SELECT destruction_approval 
    FROM " . $wpdb->prefix . "wpsc_ticket, " . $wpdb->prefix . "wpsc_epa_boxinfo 
    WHERE " . $wpdb->prefix . "wpsc_ticket.id = " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id 
    AND " . $wpdb->prefix . "wpsc_epa_boxinfo.id = '" . $box_id . "'");
    $destruction_approval = $box_destruction_approval->destruction_approval;
?>   
<!--converts program office and record schedules into a datalist-->
<form autocomplete='off'>
    
<?php
$status_list = Patt_Custom_Func::get_restricted_box_status_list( $patt_box_id_arr, $agent_type );
$restriction_reason = $status_list['restriction_reason']; // string with warnings (multiple lines)
//echo $restriction_reason;
?>
<div id='alert_status' class=''></div> 
<strong>Box Status: <a href="#" aria-label="Box Status" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-box-status'); ?>"><i class="far fa-question-circle"></i></a></strong><br />

		<select id="box_status" name="box_status">
			<?php
// Register Box Status Taxonomy
if( !taxonomy_exists('wpsc_box_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
}

$box_statuses = $status_list['box_statuses']; // list of acceptable statuses

      foreach ( $box_statuses as $term=>$status ) :

if ($status_id == $term ) {
    $selected = 'selected'; 
} else {
    $selected = ''; 
}

echo '<option '.$selected.' value="'.$term.'">'.$status.'</option>';
			endforeach;
			?>
		</select>

<?php
$desruction_approval_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); //68
$desruction_approval_term_id = $desruction_approval_tag->term_id;

if( ($validated == $validation_total) && ($status_id == $desruction_approval_term_id) && ($destruction_approval == 1) ) {
?>
<br /><br />
<strong>Destruction Completed:</strong><br />
<select id="dc" name="dc">
  <option value="1" <?php if ($dc == 1 ) echo 'selected' ; ?>>Yes</option>
  <option value="0" <?php if ($dc == 0 ) echo 'selected' ; ?>>No</option>
</select>
<?php 
    
} else { 

?>

<input type="hidden" id="dc" name="dc" value="<?php echo $dc; ?>">
<?php } 

if (($agent_permissions['label'] == 'Administrator')  || ($agent_permissions['label'] == 'Manager')) { 
?>
<br /><br />


<div class="accordion">
    
<div class="section">
<strong><a class="section-title" style="text-decoration: none;" href="#accordion-1" style="Color:#174EB5">Edit More</a></strong>
<div id="accordion-1" class="section-content">
<p>
<strong>Program Office: <a href="#" aria-label="Program office" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-program-office'); ?>"><i class="far fa-question-circle"></i></a></strong><br />
<?php
    $po_array = Patt_Custom_Func::fetch_program_office_array(); ?>
    <input type="search" list="ProgramOfficeList" placeholder='Enter program office' id='po'/>
    <datalist id = 'ProgramOfficeList'>
     <?php foreach($po_array as $key => $value) { 
     
    $program_office = $wpdb->get_row("SELECT office_code, office_name
FROM " . $wpdb->prefix . "wpsc_epa_program_office 
WHERE office_acronym  = '" . $value . "'");
    $program_office_id = $program_office->office_code;
    $program_office_name = $program_office->office_name;
    
    //Remove - if no characters after -
    $preg_replace_program_office = preg_replace("/\([^)]+\)/","",$value);
    if(substr($preg_replace_program_office, -1) == '-') {
        $new_program_office = substr($preg_replace_program_office, 0, -1);
    } else {
        $new_program_office = $preg_replace_program_office;
    }
    ?>
        <option data-value='<?php echo $program_office_id; ?>' value='<?php echo $new_program_office . ' : ' . $program_office_name; ?>'></option>
     <?php } ?>
     </datalist>

<br></br>

<strong>Record Schedule: <a href="#" aria-label="Record Schedule" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-record-schedule'); ?>"><i class="far fa-question-circle"></i></a></strong><br />
<?php
    $rs_array = Patt_Custom_Func::fetch_record_schedule_array(); ?>
    <input type="search" list="RecordScheduleList" placeholder='Enter record schedule' id='rs'/>
    <datalist id = 'RecordScheduleList'>
     <?php foreach($rs_array as $key => $value) { 
     
     //No longer have a 10 year retention requirement
     $record_schedule = $wpdb->get_row("SELECT id, Schedule_Title
FROM " . $wpdb->prefix . "epa_record_schedule 
WHERE Record_Schedule_Number = '" . $value . "'");
    $record_schedule_id = $record_schedule->id;
    $record_schedule_title = $record_schedule->Schedule_Title;
     ?>
        <option data-value='<?php echo $record_schedule_id; ?>' value='<?php echo $value . ' : ' . $record_schedule_title; ?>'></option>
     <?php } ?>
     </datalist>
</p>
</div><!-- section-content end -->
</div><!-- section end -->
   
</div><!-- accordion end -->
<?php 
}
?>
 

<input type="hidden" id="boxid" name="boxid" value="<?php echo $box_id; ?>">
<input type="hidden" id="pattboxid" name="pattboxid" value="<?php echo $patt_box_id; ?>">
</form>
<?php 
$body = ob_get_clean();
ob_start();
?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wpsc_edit_box_details();"><?php _e('Save','supportcandy');?></button>
<style>
input {
    width: 65%;
}

.alert_spacing {
	margin: 0px 0px 0px 0px;
}

/* Accordion */
.accordion, .accordion * {
    box-sizing:border-box;
    -webkit-box-sizing:border-box;
    -moz-box-sizing:border-box;
}
 
.accordion {
    overflow:hidden;
    box-shadow:0px 1px 3px rgba(0,0,0,0.25);
    border-radius:3px;
    background:#f6f6f6;
}
 
/* Section Title */
.section-title {
    background:#AFAFAF;
    display:inline-block;
    border-bottom:1px solid #1a1a1a;
    width:100%;
    padding:15px;
    transition:all linear 0.15s;
    color:#fff;
}
 
.section-title.active,
.section-title:hover {
    background:#AFAFAF;
}
 
.section:last-child .section-title {
    border-bottom:none;
}
 
.section-title:after {
/* Unicode character for "plus" sign (+) */ 
    content: '\02795';
    font-size: 13px;
    color: #FFF;
    float: right;
    margin-left: 5px;
}
 
.section-title.active:after {
/* Unicode character for "minus" sign (-) */
    content: "\2796";
}
 
/* Section Content */
.section-content {
    display:none;
    padding:20px;
}


.wpsc_loading_icon {
	margin-top: 0px !important;
}

</style>


<script>
	jQuery(document).ready(function(){
	    jQuery('[data-toggle="tooltip"]').tooltip();
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

		let restriction_reason = '<?php echo $restriction_reason ?>';		
		
		if( restriction_reason.length > 0 ) {
			console.log({restriction_reason:restriction_reason});
			set_alert('warning', restriction_reason);				
		}

	});  // END Doc Ready





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
	jQuery('#alert_status').show();
// 		jQuery('#alert_status').html('<div class=" alert '+alert_style+'">'+message+'</div>'); //badge badge-danger
	jQuery('#alert_status').html('<div id="alert-' + hash + '" class=" alert '+alert_style+'">'+message+'</div>'); //badge badge-danger
	jQuery('#alert_status').addClass('alert_spacing');
	
	alert_dismiss( hash );
}



// Sets the time for dismissing the error notification
function alert_dismiss( hash ) {
	// No timeout desired for this modal's alerts.
	//setTimeout( function(){ jQuery( '#alert-'+hash ).fadeOut( 1000 ); }, 9000 );	
}

function wpsc_edit_box_details(){	

    var po_value = jQuery('#po').val();
    var rs_value = jQuery('#rs').val();
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_box_details.php',{
postvarspattboxid: jQuery("#pattboxid").val(),
postvarsboxid: jQuery("#boxid").val(),
postvarsdc: jQuery('#dc').val(),
postvarsbs: jQuery('#box_status').val(),
postvarspo: jQuery('#ProgramOfficeList [value="' + po_value + '"]').data('value'),
postvarsrs: jQuery('#RecordScheduleList [value="' + rs_value + '"]').data('value')
}, 
   function (response) {
      if(!alert(response)){window.location.reload();}
       window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?pid=boxsearch&page=boxdetails&id=<?php echo $patt_box_id; ?>");
   });
   
   
}
</script>
<?php 
$footer = ob_get_clean();

$output = array(
  'body'   => $body,
  'footer' => $footer
);
echo json_encode($output);