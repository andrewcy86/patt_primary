<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

require WPPATT_ABSPATH . 'includes/admin/pages/scripts/vendor/autoload.php';

$subfolder_path = site_url( '', 'relative');
include_once( WPPATT_UPLOADS . 'api_authorization_strings.php' );

$GLOBALS['id'] = $_GET['id'];
$GLOBALS['pid'] = $_GET['pid'];
$GLOBALS['page'] = $_GET['page'];

$agent_permissions = $wpscfunction->get_current_agent_permissions();
$agent_permissions['label'];

//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';

$slug = basename(get_permalink());
echo $slug;

//echo $GLOBALS['id'];
?>


<div class="bootstrap-iso">
<?php
	$s3 = new Aws\S3\S3Client([
		'region'  => $s3_region,
		'version' => 'latest',
		'credentials' => [
			'key'    => $s3_key,
			'secret' => $s3_secret,
		]
	]);	

	$folderfile_details = $wpdb->get_row(
		"SELECT 
	a.id as id,
	b.index_level as index_level,
	a.box_id as box_id,
	b.title as title,
	b.date as date,
	a.author as author,
	a.record_type as record_type,
	a.site_name as site_name,
	a.siteid as site_id,
	a.close_date as close_date,
	a.epa_contact_email as epa_contact_email,
	a.access_type as access_type,
	b.source_format as source_format,
	a.folderdocinfo_id as folderdocinfo_id,
	a.essential_record as essential_record,
	b.validation as validation,
	b.validation_user_id as validation_user_id,
	b.rescan as rescan,
	b.unauthorized_destruction as unauthorized_destruction,
	a.folder_identifier as folder_identifier,
	b.freeze as freeze,
	a.addressee as addressee,
	b.DOC_REGID as sems_reg_id,
	b.DOC_ID as sems_doc_id,
	b.folderdocinfofile_id as folderdocinfofile_id,
	b.id as folderfileid,
	b.attachment,
	b.file_name,
	b.object_key,
	b.object_location,
	b.source_file_location,
	b.file_object_id,
	b.file_size
	
    FROM wpqa_wpsc_epa_folderdocinfo a
    INNER JOIN wpqa_wpsc_epa_folderdocinfo_files b ON a.id = b.folderdocinfo_id
    WHERE b.folderdocinfofile_id = '" . $GLOBALS['id'] . "'"
	);
    
    //folderdocinfo table id
    $folderfile_id = $folderfile_details->id;
    
    //folderdocinfofiles table id
    $folderdocinfofileid = $folderfile_details->folderfileid;
    
    $folderfile_index_level = $folderfile_details->index_level;
	$folderfile_boxid = $folderfile_details->box_id;
	$folderfile_title = $folderfile_details->title;
	$folderfile_date = $folderfile_details->date;
	$folderfile_author = $folderfile_details->author;
	$folderfile_record_type = $folderfile_details->record_type;
	$folderfile_site_name = $folderfile_details->site_name;
	$folderfile_site_id = $folderfile_details->site_id;
	$folderfile_close_date = $folderfile_details->close_date;
	$folderfile_epa_contact_email = $folderfile_details->epa_contact_email;
	$folderfile_access_type = $folderfile_details->access_type;
	$folderfile_source_format = $folderfile_details->source_format;
	
	$folderfile_sems_doc_id = $folderfile_details->sems_doc_id;
	$folderfile_sems_reg_id = $folderfile_details->sems_reg_id;
	//$folderfile_rights = $folderfile_details->rights;
	//$folderfile_contract_number = $folderfile_details->contract_number;
	//$folderfile_grant_number = $folderfile_details->grant_number;
	
	//$folderfile_file_location = $folderfile_details->file_location;
	//$folderfile_file_object_id = $folderfile_details->file_object_id;
	//$folderfile_file_name = $folderfile_details->file_name;
	
	$folderfile_folderdocinfo_id = $folderfile_details->folderdocinfo_id;
	$folderfile_folderdocinfofile_id = $folderfile_details->folderdocinfofile_id;
	
	$folderfile_essential_record = $folderfile_details->essential_record;
	$folderfile_validation = $folderfile_details->validation;
	$folderfile_validation_user = $folderfile_details->validation_user_id;	
	$folderfile_rescan = $folderfile_details->rescan;
    $folderfile_destruction = $folderfile_details->unauthorized_destruction;
    $folderfile_identifier = $folderfile_details->folder_identifier;
    $folderfile_freeze = $folderfile_details->freeze;
    $folderfile_addressee = $folderfile_details->addressee;

    $user = get_user_by( 'id', $folderfile_validation_user);
    
    /*$box_details = $wpdb->get_row("SELECT wpqa_terms.name, wpqa_wpsc_epa_boxinfo.id, wpqa_wpsc_epa_boxinfo.box_status, wpqa_wpsc_epa_boxinfo.box_destroyed, wpqa_wpsc_ticket.request_id as request_id, wpqa_wpsc_epa_boxinfo.box_id as box_id, wpqa_wpsc_epa_boxinfo.ticket_id as ticket_id, wpqa_wpsc_epa_boxinfo.lan_id
FROM wpqa_wpsc_epa_boxinfo, wpqa_wpsc_epa_folderdocinfo, wpqa_wpsc_ticket, wpqa_terms
WHERE wpqa_wpsc_epa_boxinfo.box_status = wpqa_terms.term_id AND wpqa_wpsc_ticket.id = wpqa_wpsc_epa_boxinfo.ticket_id AND wpqa_wpsc_epa_folderdocinfo.box_id = wpqa_wpsc_epa_boxinfo.id AND wpqa_wpsc_epa_boxinfo.id = '" . $folderfile_boxid . "'");*/
    
    $box_details = $wpdb->get_row("SELECT d.name, a.id, a.box_status, a.box_destroyed, c.request_id as request_id, a.box_id as box_id, a.ticket_id as ticket_id, a.lan_id, c.ticket_priority as ticket_priority, 
(SELECT name as ticket_priority FROM wpqa_terms WHERE term_id = c.ticket_priority) as ticket_priority_name, c.ticket_status as ticket_status, 
(SELECT name as ticket_status FROM wpqa_terms WHERE term_id = c.ticket_status) as ticket_status_name
FROM wpqa_wpsc_epa_boxinfo a
INNER JOIN wpqa_wpsc_epa_folderdocinfo b ON b.box_id = a.id
INNER JOIN wpqa_wpsc_ticket c ON c.id = a.ticket_id
INNER JOIN wpqa_terms d ON a.box_status = d.term_id
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files e ON e.folderdocinfo_id = b.id
WHERE a.id = '" . $folderfile_boxid . "' 
LIMIT 1");
    
    $box_boxid = $box_details->box_id;
	$box_ticketid = $box_details->ticket_id;
	$box_requestid = $box_details->request_id;
	$box_destruction = $box_details->box_destroyed;
	$box_status = $box_details->box_status;
	$box_ticket_priority = $box_details->ticket_priority;
	$box_ticket_priority_name = $box_details->ticket_priority_name;
	$box_ticket_status = $box_details->ticket_status;
	$box_ticket_status_name = $box_details->ticket_status_name;

	// Display box status
	$status_background = get_term_meta($box_status, 'wpsc_box_status_background_color', true);
	$status_color = get_term_meta($box_status, 'wpsc_box_status_color', true);
	$status_style = "background-color:".$status_background.";color:".$status_color.";";
	$box_status_name = $box_details->name;
	$box_status_display = "<span class='wpsp_admin_label' style='".$status_style."'>".$box_status_name."</span>";
	
	// Display request status
	$request_status_background = get_term_meta($box_ticket_status, 'wpsc_status_background_color', true);
	$request_status_color = get_term_meta($box_ticket_status, 'wpsc_status_color', true);
	$request_status_style = "background-color:".$request_status_background.";color:".$request_status_color.";";
	$request_status = "<span class='wpsp_admin_label' style='".$request_status_style."'>".$box_ticket_status_name."</span>";
	
	// Display request priority
	$priority_background = get_term_meta($box_ticket_priority, 'wpsc_priority_background_color', true);
	$priority_color = get_term_meta($box_ticket_priority, 'wpsc_priority_color', true);
	$priority_style = "background-color:".$priority_background.";color:".$priority_color.";";
	$priority = "<span class='wpsp_admin_label' style='".$priority_style."'>".$box_ticket_priority_name."</span>";

	$request_id = substr($box_boxid, 0, 7);
	
	$lan_id = $box_details->lan_id;
    
    //record schedule
    $box_record_schedule = $wpdb->get_row("SELECT b.Record_Schedule_Number as rsnum 
FROM wpqa_wpsc_epa_boxinfo a 
INNER JOIN wpqa_epa_record_schedule b ON b.id = a.record_schedule_id
WHERE a.id = '" . $folderfile_boxid . "'");
    $box_rs = $box_record_schedule->rsnum;
    
    //program office
    $box_program_office = $wpdb->get_row("SELECT a.office_acronym as program_office 
FROM wpqa_wpsc_epa_program_office a 
INNER JOIN wpqa_wpsc_epa_boxinfo b ON b.program_office_id = a.office_code
WHERE b.id = '" . $folderfile_boxid . "'");
    $box_po = $box_program_office->program_office;
    
    //box location
    $location = $wpdb->get_row("SELECT e.name as location, c.aisle as aisle, c.bay as bay, c.shelf as shelf, c.position as position
FROM wpqa_wpsc_epa_boxinfo a 
INNER JOIN wpqa_wpsc_epa_folderdocinfo b ON b.box_id = a.id
INNER JOIN wpqa_wpsc_epa_storage_location c ON c.id = a.storage_location_id
INNER JOIN wpqa_terms e ON e.term_id = c.digitization_center
INNER JOIN wpqa_wpsc_epa_folderdocinfo_files f ON f.folderdocinfo_id = b.id
WHERE a.id = '" . $folderfile_boxid . "'
LIMIT 1");
    $box_location = $location->location;
	$box_aisle = $location->aisle;
	$box_bay = $location->bay;
	$box_shelf = $location->shelf;
	$box_position = $location->position;
?>
<style>
div.dataTables_wrapper {
        width: 100%;
        margin: 0;
    }
	
.datatable_header {
	background-color: rgb(66, 73, 73) !important; 
	color: rgb(255, 255, 255) !important; 
}

.bootstrap-iso .alert {
    padding: 8px;
}

.wpsc_loading_icon {
	margin-top: 0px !important;
}

</style>
<?php
	if ($folderfile_index_level == '1') {
?>
		<h3>Folder Details</h3>
<?php
	} else {
?>
  		<h3>File Details</h3>
<?php
	}
?>

<div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important;">

<div class="row wpsc_tl_action_bar" style="background-color:<?php echo $general_appearance['wpsc_action_bar_color']?> !important;">
  
	<div class="col-sm-12">
    	<button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=wpsc-tickets';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?> margin-right: 30px !important;"><i class="fa fa-list-ul"></i> <?php _e('Ticket List','supportcandy')?> <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-request-list-button'); ?>" aria-label="Request Help"><i class="far fa-question-circle"></i></a></button>
    	
    	<?php	
    	
        if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
        {
        ?>
        <?php 
        $validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); //674
        $rescan_tag = get_term_by('slug', 're-scan', 'wpsc_box_statuses'); //743
        if ($box_status == $validation_tag->term_id || $box_status == $rescan_tag->term_id) {
        ?>
        <!-- language of buttons change based on 0 or 1 -->
        <?php
        if($folderfile_validation == 0) { ?>
        <button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_validation_btn" style="<?php echo $action_default_btn_css?>"<?php echo (($folderfile_rescan == 1 || $folderfile_destruction == 1) || ($folderfile_rescan == 1 && $box_destruction == 1) || $folderfile_destruction == 1 || $box_destruction == 1)? "disabled" : ""; ?>><i class="fas fa-check-circle"></i> Validate <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-validate-button'); ?>" onclick="wpsc_help_filters();"><i class="far fa-question-circle"></i></a></button>
        <?php
        }
        else { ?>
        <button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_validation_btn" style="<?php echo $action_default_btn_css?>"<?php echo (($folderfile_rescan == 1 || $folderfile_destruction == 1) || ($folderfile_rescan == 1 && $box_destruction == 1) || $folderfile_destruction == 1 || $box_destruction == 1)? "disabled" : ""; ?>><i class="fas fa-check-circle"></i> Un-Validate</button>
        <?php
        }
        ?>
        <?php 
        }
        ?>

        <?php 
        $completed_tag = get_term_by('slug', 'completed', 'wpsc_box_statuses'); //66
        $dispositioned_tag = get_term_by('slug', 'stored', 'wpsc_box_statuses'); //67
        $destruction_approval_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); //68
        //$status_array = array(66, 67, 68);
        $status_array = array($completed_tag->term_id, $dispositioned_tag->term_id, $destruction_approval_tag->term_id);
        if (!in_array($box_status, $status_array)){
        ?>        
        <?php
        if($folderfile_rescan == 0) { ?>
        <button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_rescan_btn" style="<?php echo $action_default_btn_css?>"<?php echo (($folderfile_validation == 1 || $folderfile_destruction == 1) || ($folderfile_validation == 1 && $box_destruction == 1) || $folderfile_destruction == 1 || $box_destruction == 1)? "disabled" : ""; ?>><i class="fas fa-times-circle"></i> Re-Scan <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-re-scan-button'); ?>"><i class="far fa-question-circle"></i></a></button>
       	<?php }
       	else { ?>
       	<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_rescan_btn" style="<?php echo $action_default_btn_css?>"<?php echo (($folderfile_validation == 1 || $folderfile_destruction == 1) || ($folderfile_validation == 1 && $box_destruction == 1) || $folderfile_destruction == 1 || $box_destruction == 1)? "disabled" : ""; ?>><i class="fas fa-times-circle"></i> Undo Re-Scan <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-re-scan-button'); ?>"><i class="far fa-question-circle"></i></a></button>
        <?php } ?>
        <?php 
        }
        ?>
        
       	<?php
       	if($folderfile_destruction == 0) { ?>
       	<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_destruction_btn" style="<?php echo $action_default_btn_css?>"<?php echo ($box_destruction == 1 || $folderfile_freeze == 1)? "disabled" : ""; ?>><i class="fas fa-flag"></i> Unauthorized Destruction <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-unauthorized-destruction'); ?>" aria-label="Unauthorized Destruction Help"><i class="far fa-question-circle"></i></a></button>
    	<?php }
    	else { ?>
    	<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_destruction_btn" style="<?php echo $action_default_btn_css?>"<?php echo ($box_destruction == 1 || $folderfile_freeze == 1)? "disabled" : ""; ?>><i class="fas fa-flag"></i> Undo Unauthorized Destruction <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-undo-unauthorized-destruction-button'); ?>" aria-label="Undo Unauthorized Destruction Help"><i class="far fa-question-circle"></i></a></button>
    	<?php } ?>
    	
    	<?php 
    	if($folderfile_freeze == 0) { ?>
    	<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_freeze_btn" style="<?php echo $action_default_btn_css?>"<?php echo ($folderfile_destruction == 1 || $box_destruction == 1)? "disabled" : ""; ?>><i class="fas fa-snowflake"></i> Freeze  <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-freeze-button'); ?>" aria-label="Freeze Help"><i class="far fa-question-circle"></i></a></button>
        <?php }
        else { ?>
        <button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_freeze_btn" style="<?php echo $action_default_btn_css?>"<?php echo ($folderfile_destruction == 1 || $box_destruction == 1)? "disabled" : ""; ?>><i class="fas fa-snowflake"></i> Un-Freeze</button>
        <?php } ?>
        <?php
        }
        ?>	
<?php
// originally /^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}$/
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}(-[a][0-9]{1,4})?$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'requestdetails') {
?>
<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=boxdetails&pid=requestdetails&id=<?php echo $box_boxid ?>';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left"></"></i> Back to Box Details</button>
<?php
}
?>
<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}(-[a][0-9]{1,4})?$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'boxsearch') {
?>
<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=boxdetails&pid=requestdetails&id=<?php echo $box_boxid ?>';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left"></"></i> Back to Box Details</button>
<?php
}
?>
<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}(-[a][0-9]{1,4})?$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'docsearch') {
?>
<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=folderfile';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left"></i> Back to Folder/File Dashboard</button>
<?php
}
?>   	
  </div>
	
</div>

<div class="row" style="background-color:<?php echo $general_appearance['wpsc_bg_color']?> !important;color:<?php echo $general_appearance['wpsc_text_color']?> !important;">

<?php
//BEGIN folder/file ID check
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}(-[a][0-9]{1,4})?$/", $GLOBALS['id'])) {
?>
  <div class="col-sm-8 col-md-9 wpsc_it_body">
    <div class="row wpsc_it_subject_widget">

<!--only appears if document is marked as unauthorized destruction-->
<?php
if($folderfile_destruction > 0){
?>
<div class="alert alert-danger" role="alert">
<span style="font-size: 1em; color: #8b0000;"><i class="fas fa-flag" title="Unauthorized Destruction"></i></span> This 
<?php if ($folderfile_index_level == '1') {?>folder <?php }else{ ?>file <?php } ?>
is flagged as unauthorized destruction.
</div>
<?php
}
?>

<!--only appears if document is marked as frozen-->
<?php
if($folderfile_freeze > 0){
?>
<div class="alert alert-info" role="alert">
<span style="font-size: 1em; color: #009ACD;"><i class="fas fa-snowflake" title="Freeze"></i></span> This 
<?php if ($folderfile_index_level == '1') {?>folder <?php }else{ ?>file <?php } ?>
is marked as frozen.
</div>
<?php
}
?>

<?php
if($folderfile_validation > 0 && $folderfile_rescan == 0){
echo '
<div class="alert alert-success" role="alert">
<span style="font-size: 1.3em; color: #008000;"><i class="fas fa-check-circle" title="Validated"></i></span>';
if ($folderfile_index_level == '1') { echo' Folder validated ['. $user->display_name. ']'; }else{ echo' File validated ['.$user->display_name.'].'; }
echo '</div>';
} elseif ($folderfile_rescan == 1) {
echo '
<div class="alert alert-danger" role="alert">
<span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" title="Re-scan Needed"></i></span>';
if ($folderfile_index_level == '1') { echo' Folder requires re-scanning.'; }else{ echo' File requires re-scanning.'; }
echo '</div>';
} else {
echo '
<div class="alert alert-danger" role="alert">
<span style="font-size: 1.3em; color: #8b0000;"><i class="fas fa-times-circle" title="not validated"></i></span>';
if ($folderfile_index_level == '1') { echo' Folder not validated.'; }else{ echo' File not validated.'; }
echo '</div>';    
}
?>

      <h3>
	 	 <?php if(apply_filters('wpsc_show_hide_ticket_subject',true)){?>
	 	 <?php if($box_destruction > 0 && $folderfile_freeze == 0){?>
	 	 <span style="color: #FF0000 !important; text-decoration: line-through;">
	 	 <?php } ?>
        	<?php 
        	//If a file is electronic it should always be a file
        	if ($folderfile_index_level == '1') {?>[Folder ID #<?php }else{ ?>[File ID #<?php } ?> <?php
            echo $GLOBALS['id'];
            ?>]<?php if($box_destruction > 0 && $folderfile_freeze == 0){?></span> <span style="font-size: .8em; color: #FF0000;"><i class="fas fa-ban" title="Box Destroyed"></i></span><?php } ?>
		  <?php } ?>		

		  <?php 
$decline_icon = '';
$recall_icon = '';
$type = 'folderfile';

if(Patt_Custom_Func::id_in_return($GLOBALS['id'],$type) == 1){
$decline_icon = '<span style="color: #FF0000;margin-left:4px;"><i class="fas fa-undo" title="Declined"></i></span>';
}

if(Patt_Custom_Func::id_in_recall($GLOBALS['id'],$type) == 1){
$recall_icon = '<span style="color: #000;margin-left:4px;"><i class="far fa-registered" title="Recall"></i></span>';
}
echo $decline_icon.$recall_icon;
		  ?>
		  
		  <?php 
		  if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
                {
			         echo '<a href="#" onclick="wpsc_get_folderfile_editor(' . $folderdocinfofileid . ')"><i class="fas fa-edit fa-xs"></i></a>';
			    }
		  ?>
		  <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-folder-id-edit-icon'); ?>"><i class="far fa-question-circle"></i></a>
      </h3>

    </div>

<?php
//echo "<strong style='color:purple'>doc_id:</strong> " . $folderdocinfofileid . "<br />";
			if(!empty($box_po)) {
			    echo "<strong>Program Office:</strong> " . $box_po . "<br />";
			}
			else {
			    echo "<strong style='color:red'>Program Office: REASSIGN IMMEDIATELY</strong> <br />";
			}
            
            if(!empty($box_rs)) {
                echo "<strong>Record Schedule:</strong> " . $box_rs ."<br />";
            }
            else {
			    echo "<strong style='color:red'>Record Schedule: REASSIGN IMMEDIATELY</strong> <br />";
			}
  
  			if (!empty($folderfile_title)) {
				echo "<strong>Title:</strong> " . $folderfile_title . "<br />";
			}

			if (!empty($folderfile_date)) {
				echo "<strong>Date:</strong> " . $folderfile_date . "<br />";
			}
			if (!empty($folderfile_author)) {
				echo "<strong>Author:</strong> " . $folderfile_author . "<br />";
			}
			if(!empty($folderfile_addressee)) {
			    echo "<strong>Addressee:</strong> " . $folderfile_addressee . "<br />";
			}
			if (!empty($folderfile_record_type)) {
				echo "<strong>Record Type:</strong> " . $folderfile_record_type . "<br />";
			}
			if (!empty($folderfile_site_name)) {
				echo "<strong>Site Name:</strong> " . $folderfile_site_name . "<br />";
			}
			if (!empty($folderfile_site_id)) {
				echo "<strong>Site ID #:</strong> " . $folderfile_site_id . "<br />";
			}
			if (!empty($folderfile_close_date)) {
				echo "<strong>Close Date:</strong> " . $folderfile_close_date . "<br />";
			}
	
			if (!empty($folderfile_access_type)) {
				echo "<strong>Access Type:</strong> " . $folderfile_access_type . "<br />";
			}
			if (!empty($folderfile_source_format)) {
				echo "<strong>Source Format:</strong> " . stripslashes($folderfile_source_format) . "<br />";
			}
			/*if (!empty($folderfile_rights)) {
				echo "<strong>Rights:</strong> " . $folderfile_rights . "<br />";
			}
			if (!empty($folderfile_contract_number)) {
				echo "<strong>Contract #:</strong> " . $folderfile_contract_number . "<br />";
			}
			if (!empty($folderfile_grant_number)) {
				echo "<strong>Grant #:</strong> " . $folderfile_grant_number . "<br />";
			}*/
			
			if(!empty($folderfile_identifier)) {
			    echo "<strong>Folder Identifier:</strong> " . $folderfile_identifier . "<br />";
			}
			
			if($folderfile_essential_record == 1) {
			    echo "<strong>Essential Record:</strong> Yes";
			}
			else {
			    echo "<strong>Essential Record:</strong> No";
			}

wp_get_current_user();
echo "<br /><br />";
$ticket_details = $wpdb->get_row("SELECT customer_name
FROM wpqa_wpsc_ticket
WHERE id = '" . $box_ticketid . "'");

$ticket_user = $ticket_details->customer_name;
?>

<?php

//
// New DropZone File Uploader for Single Files
//

// Prepare Data

if( $folderfile_details->file_size == null || $folderfile_details->file_size == '' ) {
	$file_size = '0 KB';
	$file_size = 'No File Uploaded -';
	$file_message = '<span class="red-alert" > Please Upload the File.</span>';

} else {	
	$file_size =Patt_Custom_Func::bytes_to_readable_string( $folderfile_details->file_size );
	
}





// Display Upload File Section
$protocol = 'https://';
$host = '.s3.us-gov-west-1.amazonaws.com';
echo '<div id="upload-file-section" >';
echo '<hr>';
echo '<h3>Upload File</h3>';
echo '</div>';
//echo 'folderfile_details: ' . $folderfile_details->attachment . '<br>';
//echo 'check: ' . ($folderfile_details->attachment == '0') ? 'true' : 'false';
//echo '<br>';

echo '<div id="single-file-uploader-details" >';
echo '<span class="details-name" >File Name: </span><span class="" >' . $folderfile_details->file_name . '</span><br>';
echo '<span class="details-name" >File Location: </span><span class="" >' . $folderfile_details->source_file_location . '</span><br>';
echo '<span class="details-name" >File Size: </span><span class="" >' . $file_size . '</span>' . $file_message . '<br>';
echo '<span class="details-name" id="file-preview" ><a href="' . $protocol . $folderfile_details->object_location . $host . '/' . $folderfile_details->object_key . '" target="_blank" >Preview File</a></span><br>';
//echo '<span class="details-name" id="file-delete" ><a href="" onclick="" >Delete File</a></span><br>';
echo '</div>';

echo '<div id="alert_status_filefolder" class="alert_spacing"></div>';

echo '<div id="single-file-uploader-dropzone" >';
include WPPATT_ABSPATH . 'includes/admin/pages/scripts/s3_modal_slice.php';    
echo '</div>';

// Hidden Stuff
echo '<input type="hidden" id="mdocs-name-single-file" />';
echo '<input type="hidden" name="folderdocinfo_files_id" id="folderdocinfo_files_id" />';


// Hide DropZone if no file attachment aka no digital file.
if( $folderfile_details->attachment == '0' ) {  ?> 
	<script type="text/javascript">
		$('#single-file-uploader-dropzone').hide();
		$('#single-file-uploader-details').hide();
		$('#upload-file-section').hide();
	</script> 
	
<?php
	
}

// hide preview if not uploaded yet. 
if( $folderfile_details->file_size == null || $folderfile_details->file_size == '' ) {
		?> 
	<script type="text/javascript">
		$('#file-preview').hide();
	</script>
	<?php
} else {	
	
}	
	
?> 	
<script type="text/javascript">	
	// Sets the PK for folderdocinfo_files to be used by the s3upload.js file
	let folderfileid = '<?php echo $folderfile_details->folderfileid; ?>';
	$('input[name=folderdocinfo_files_id]').val( folderfileid ); 
</script>
<?php


// DEBUG - START

echo '<hr>';
echo '<h3>DEBUG</h3>';
echo 'FolderDocInfo: <br><pre>';
print_r( $folderfile_details );
echo '</pre>';

// DEBUG - END


?>
<style>
	.details-name {
		font-weight: bold;
	}
	
	.red-alert {
		color: red;
		font-weight: bold;
	}

</style>
<?php

//
// New DropZone File Uploader for Single Files - END
//

?>

<?php

// BEGIN ECMS/SEMS TABLES

			$sems_check = $wpscfunction->get_ticket_meta($box_ticketid,'super_fund');
                
            if(in_array("true", $sems_check)) {
?>

<?php

if ($folderfile_sems_reg_id != '' || $folderfile_sems_doc_id != '') {
?>

<h3 style="display: inline;">Links to Electronic Records in SEMS <a href="#" aria-label="Help" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-links-to-ecms'); ?>"><i class="far fa-question-circle"></i></a></h3>

<br /><br />
<div id="wpsc_raised_extra_info">
	<table class="table table-hover">
		<tbody>
		<!--<tr>
			<td style="width:200px;"><strong>Date</strong></td>
			<td><?php echo $folderfile_date; ?></td>
		</tr>-->
		<tr>
						<td><strong>Title</strong></td>
					<td><a href="https://semspub.epa.gov/work/<?php echo $folderfile_sems_reg_id; ?>/<?php echo $folderfile_sems_doc_id; ?>.pdf" target="_blank"><?php echo $folderfile_title; ?></a></td>
		</tr>
		<tr>
			<td><strong>Doc ID</strong></td>
			<td><?php echo $folderfile_sems_doc_id; ?></td>
		</tr>
	</tbody></table>
</div>

<?php
}

            } else {
// Table displayed for paper records split files on both folder and files

/*
$get_all_ecms_attachments = $wpdb->get_results("SELECT *
FROM wpqa_wpsc_epa_folderdocinfo_files
WHERE object_key != '' AND file_object_id != '' AND folderdocinfo_id = '" . $GLOBALS['id'] . "'");
*/

$get_all_ecms_attachments = $wpdb->get_results("SELECT *
FROM wpqa_wpsc_epa_folderdocinfo_files
WHERE file_object_id != '' AND folderdocinfofile_id = '" . $GLOBALS['id'] . "'");

if (count($get_all_ecms_attachments)> 0 && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent')  || ($agent_permissions['label'] == 'Manager') || $current_user->nickname == $ticket_user)) {

?>
<style>
.datatable_header {
    background-color: rgb(66, 73, 73) !important;
    color: rgb(255, 255, 255) !important;
}
</style>
<h3 style="display: inline;">Links to Electronic Records in ECMS <a href="#" aria-label="Help" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-links-to-ecms'); ?>"><i class="far fa-question-circle"></i></a>
</h3>
<div class="table-responsive" style="overflow-x:auto;">
<br />
<?php
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')) {
echo '<button type="button" class="button" id="wpsc_ecms_delete_btn"><i class="fa fa-trash"></i> Submit ECMS Removal Request </button><br /><br />';
}
//class="table table-striped table-bordered"
$tbl = '
	<table id="tbl_templates_ecms" class="display nowrap" cellspacing="5" cellpadding="5">
<thead>
  <tr>';
 
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')) {
$tbl .= '<th class="datatable_header"></th>';
}

$tbl .='
  <th class="datatable_header">ID</th>
  <th class="datatable_header">Name</th>
  </tr>
 </thead><tbody>';
 
foreach ($get_all_ecms_attachments as $info) {
$attach_id = $info->id;
$attach_post_id = $info->post_id;
$attach_file_id = $info->folderdocinfofile_id;
$attach_file_title = $info->title;
$attach_file_key = $info->object_key;
$attach_file_location = $info->object_location;
$attach_file_object_id = $info->file_object_id;
$attach_file_name = $info->file_name;

$attach_ecms_timestamp = $info->ecms_delete_timestamp;
$attach_ecms_comment = $info->ecms_delete_comment;

//echo $attach_post_id . '<br />';

// register stream wrapper method
$s3->registerStreamWrapper();
// does file exist
$keyExists = file_exists("s3://".$s3_bucket."/".$attach_file_key);
//echo $keyExists;
if ($keyExists) {

//echo $attach_file_key.' File exists!<br />';
//} else {

// Grab Child Attachment ID
$post_type ="attachment"; //page, or custom_post_type
$post_status = "any"; //published, draft, etc
$num_of_posts = -1; // -1 for all, or amount # to return
$post_parent = 0; //0 for parents, or and id
 
$args = array('post_parent' => $attach_post_id, 'post_type' => $post_type, 'numberposts' => $num_of_posts, 'post_status' => $post_status);
 
$parents = get_children($args);
 
foreach ($parents as $child) {

$_REQUEST['mdocs-id'] = $child->ID;

//echo $child->ID;
} 

$_REQUEST['s3-ecms'] = 1;

//Perform the post deletion
mdocs_delete_file();

//echo $attach_file_key.' File does not exists!<br />';   

$tbl .= '<tr class="wpsc_tl_row_item">';
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')) {
$tbl .= '<td>'.$attach_id.'</td>';
}


 if(strtotime($attach_ecms_timestamp) > 0 && $attach_ecms_comment != ''){
$tbl .= '<td style="color:red"><del>'.$attach_file_id.'</del></td>';
$tbl .= '<td style="color:red"><del><a href="https://lippizzan3.rtpnc.epa.gov/ecms/download/1.0?apiKey='. $ecms_apikey .'&object-id='. $attach_file_object_id . '">'.$attach_file_title.'</a> - '.$attach_file_name.'</del></td>';
$tbl .= '</tr>';

 }else{
$tbl .= '<td>'.$attach_file_id.'</td>';
$tbl .= '<td><a href="https://lippizzan3.rtpnc.epa.gov/ecms/download/1.0?apiKey='. $ecms_apikey .'&object-id='. $attach_file_object_id . '">'.$attach_file_title.'</a> - '.$attach_file_name.'</td>';
$tbl .= '</tr>';

 }

}

}
$tbl .= '</tbody></table>';
echo $tbl;
?>
</div>
<?php
}

} 
// END ECMS/SEMS TABLES
?>

<form>

<input type='hidden' id='doc_id' value='<?php echo $GLOBALS['id']; ?>' />
<input type='hidden' id='page' value='<?php echo $GLOBALS['page']; ?>' />
<input type='hidden' id='pid' value='<?php echo $GLOBALS['pid']; ?>' />
</form>
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

<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>

<script>
 jQuery(document).ready(function() {

	 var dataTable = jQuery('#tbl_templates_ecms').DataTable({
	     "autoWidth": true,
	     "paging" : true,
	     "scrollX" : true,
		 "aLengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
        'columnDefs': [	
         {	
            'width': '5px',
            'targets': 0,	
            'checkboxes': {	
               'selectRow': true	
            },
         },
            { 'width': '50%', 'targets': 1 },
            { 'width': '50%', 'targets': 2 }
      ],
      'select': {	
         'style': 'multi'	
      },	
      'order': [[1, 'asc']],
		});

	// Code block for toggling edit buttons on/off when checkboxes are set
	jQuery('#tbl_templates_ecms tbody').on('click', 'input', function () {        
	// 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	jQuery('.dt-checkboxes-select-all').on('click', 'input', function () {        
	 	console.log('checked');
		setTimeout(toggle_button_display, 1); //delay otherwise 
	});
	
	jQuery('#wpsc_ecms_delete_btn').attr('disabled', 'disabled');

	function toggle_button_display() {
	//	var form = this;
		var rows_selected = dataTable.column(0).checkboxes.selected();
		if(rows_selected.count() > 0) {
			jQuery('#wpsc_ecms_delete_btn').removeAttr('disabled');
	  	} else {
	    	jQuery('#wpsc_ecms_delete_btn').attr('disabled', 'disabled');
	  	}
	}
	
jQuery('[data-toggle="tooltip"]').tooltip(); 
<?php
// BEGIN ADMIN BUTTONS
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
{
?>

jQuery('#wpsc_upload_temp_attachment_btn').on('click', function(e){
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_temp_attachments.php',{
postvarsfolderdocid : jQuery('#doc_id').val(),
postvarpage : jQuery('#page').val()
}, 
   function (response) {
      //if(!alert(response)){window.location.reload();}
      wpsc_modal_open('Upload Attachments to ECMS');
		  var data = {
		    action: 'wpsc_upload_temp_attachments',
		    response_data: response,
		    response_page: '<?php echo $GLOBALS['page']; ?>'
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
   });
});

jQuery('#wpsc_individual_rescan_btn').on('click', function(e){
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_rescan.php',{
postvarsfolderdocid : jQuery('#doc_id').val(),
postvarpage : jQuery('#page').val()
}, 
   function (response) {
      //if(!alert(response)){window.location.reload();}
      wpsc_modal_open('Re-scan');
		  var data = {
		    action: 'wpsc_get_rescan_ffd',
		    response_data: response,
		    response_page: '<?php echo $GLOBALS['page']; ?>'
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
   });
});

//ECMS delete button

jQuery('#wpsc_ecms_delete_btn').on('click', function(e){
     var form = this;
     var rows_selected = dataTable.column(0).checkboxes.selected();
		  wpsc_modal_open('Submit ECMS Delete Request');
		  var data = {
		    action: 'wpsc_ecms_delete_request',
		    attach_id: rows_selected.join(","),
		    folderdoc_id: '<?php echo $GLOBALS['id']; ?>'
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });
});

jQuery('#wpsc_individual_validation_btn').on('click', function(e){
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_validate.php',{
postvarsfolderdocid : jQuery('#doc_id').val(),
postvarsuserid : <?php $user_ID = get_current_user_id(); echo $user_ID; ?>,
postvarpage : jQuery('#page').val()
}, 
   function (response) {
      //if(!alert(response)){window.location.reload();}
      wpsc_modal_open('Validation');
		  var data = {
		    action: 'wpsc_get_validate_ffd',
		    response_data: response,
		    response_page: '<?php echo $GLOBALS['page']; ?>'
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
   });
});



jQuery('#wpsc_individual_destruction_btn').on('click', function(e){

		  wpsc_modal_open('Unauthorized Destruction');
		  var data = {
		    action: 'wpsc_unauthorized_destruction_ffd',
		    postvarsfolderdocid : jQuery('#doc_id').val(),
            postvarpage : jQuery('#page').val(),
            pid : jQuery('#pid').val(),
            boxid : ''
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });  
});
		
jQuery('#wpsc_individual_freeze_btn').on('click', function(e){
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_freeze.php',{
postvarsfolderdocid : jQuery('#doc_id').val(),
postvarpage : jQuery('#page').val()
}, 
   function (response) {
      //if(!alert(response)){window.location.reload();}
      //window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?pid=<?php echo $GLOBALS['pid']; ?>&page=filedetails&id=<?php echo $GLOBALS['id']; ?>");
      wpsc_modal_open('Freeze');
		  var data = {
		    action: 'wpsc_get_freeze_ffd',
		    response_data: response,
		    response_page: '<?php echo $GLOBALS['page']; ?>'
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
   });
});
<?php
}
// END ADMIN BUTTONS
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
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}/", $GLOBALS['id']) && $GLOBALS['pid'] == 'boxsearch') {
?>
	 jQuery('.wp-submenu li:nth-child(3)').addClass('current');
<?php
}
?>
<?php
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'docsearch') {
?>
	 jQuery('.wp-submenu li:nth-child(4)').addClass('current');
<?php
}
?>
} );

		function wpsc_get_folderfile_editor(doc_id){
<?php
			if ($folderfile_index_level == '1') { 
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
 
	<div class="col-sm-4 col-md-3 wpsc_sidebar individual_ticket_widget">

							<div class="row" id="wpsc_status_widget" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_widgets_border_color']?> !important;">
					      <h4 class="widget_header"><i class="fa fa-info-circle"></i> Information/Location
					      </h4>
								<hr class="widget_divider">
								<div class="wpsp_sidebar_labels"><strong>Request ID:</strong> 
	                            <?php 
	                                echo "<a href='admin.php?page=wpsc-tickets&id=" . $box_requestid . "'>" . $box_requestid . "</a>";
	                            ?>
	                            </div>
	                            
	                            <div class="wpsp_sidebar_labels"><strong>Request Status:</strong> 
	                            <?php 
	                                echo $request_status;
	                            ?>
	                            </div>
	                            
	                            <div class="wpsp_sidebar_labels"><strong>Priority:</strong> 
	                            <?php 
	                                echo $priority;
	                            ?>
	                            </div>
	                            
	                            <div class="wpsp_sidebar_labels"><strong>Box ID:</strong> 
	                            <?php 
	                            if (!empty($box_boxid)) {
	                                if ($GLOBALS['pid'] == 'requestdetails') {
	                                echo "<a href='admin.php?pid=requestdetails&page=boxdetails&id=" . $box_boxid . "'>" . $box_boxid . "</a>";
	                                }
	                                if ($GLOBALS['pid'] == 'boxsearch') {
	                                echo "<a href='admin.php?pid=boxsearch&page=boxdetails&id=" . $box_boxid . "'>" . $box_boxid . "</a>";
	                                }
	                                if ($GLOBALS['pid'] == 'docsearch') {
	                                echo "<a href='admin.php?pid=docsearch&page=boxdetails&id=" . $box_boxid . "'>" . $box_boxid . "</a>";
	                                }
	                                } ?>
	                             </div>
	                            <div class="wpsp_sidebar_labels"><strong>Box Status:</strong> 
	                            <?php 
	                                echo $box_status_display;
	                            ?>
	                            </div>
	                           <?php 
	                           if($lan_id != '' && $lan_id != 1) {
	                           ?>
	                           <div class="wpsp_sidebar_labels"><strong>EPA Contact/Custodian: </strong><?php echo $lan_id?></div>
	                           <?php }
	                           else { ?>
	                           <div class="wpsp_sidebar_labels" style="color: red;"><strong>EPA Contact/Custodian: Pending update...</strong></div>
	                           <?php } ?>
	                           <hr class="widget_divider">
	                            <?php
	                            //if digitization_center field is empty, will not display location on front end
	                            if(!empty($box_location)) {
	                            echo '<div class="wpsp_sidebar_labels"><strong>Digitization Center: </strong>';
	                            echo $box_location . "<br />";
	                                //if aisle/bay/shelf/position <= 0, does not display location on front end
    	                            if(!($box_aisle <= 0 && $box_bay <= 0 && $box_shelf <= 0 && $box_position <= 0))
    								{
        								echo '<div class="wpsp_sidebar_labels"><strong>Aisle: </strong>';
        	                            echo $box_aisle . "<br />";
        	                            echo '</div>';
        								echo '<div class="wpsp_sidebar_labels"><strong>Bay: </strong>';
        	                            echo $box_bay . "<br />";
        	                            echo '</div>';
        								echo '<div class="wpsp_sidebar_labels"><strong>Shelf: </strong>';
        	                            echo $box_shelf . "<br />";
        								echo '</div>';
        								echo '<div class="wpsp_sidebar_labels"><strong>Position: </strong>';
        	                            echo $box_position . "<br />";
        	                            echo '</div>';
    								}
	                            }
	                            ?> 
	                            </div>
			    		</div>
	
	</div>
<?php
} else {
// END folder/file ID Check
echo '<span style="padding-left: 10px">Please pass a valid Folder/File ID</span>';

}
?>
</div>
</div>
</div>