<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

require WPPATT_ABSPATH . 'includes/admin/pages/scripts/vendor/autoload.php';

use Aws\S3\S3Client;  
use Aws\Exception\AwsException;

$s3_exist = 0;

$subfolder_path = site_url( '', 'relative');
//include_once( WPPATT_UPLOADS . 'api_authorization_strings.php' );
include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

$GLOBALS['id'] = sanitize_text_field($_GET['id']);
$GLOBALS['pid'] = sanitize_text_field($_GET['pid']);
$GLOBALS['page'] = sanitize_text_field($_GET['page']);

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

$ticket_string = substr($GLOBALS['id'],0,7);
$is_active = Patt_Custom_Func::request_status( $ticket_string );

if($is_active == 1) {
    $type = 'folderfile';
}
else {
    $type = 'folderfile_archive';
}

//Request statuses
$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003

//Box statuses
$box_pending_tag = get_term_by('slug', 'pending', 'wpsc_box_statuses'); //748
$box_scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); //672
$box_destruction_approved_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); //68
$box_destruction_of_source_tag = get_term_by('slug', 'destruction-of-source', 'wpsc_box_statuses'); //1272
$box_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
$box_cancelled_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses'); //1057

$validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); //674
$rescan_tag = get_term_by('slug', 're-scan', 'wpsc_box_statuses'); //743

$status_id_arr = array($initial_review_rejected_tag->term_id, $completed_dispositioned_tag->term_id);
$request_freeze_status_id_arr = array($initial_review_rejected_tag->term_id, $completed_dispositioned_tag->term_id);
$box_freeze_arr = array($box_pending_tag->term_id, $box_completed_dispositioned_tag->term_id, $box_cancelled_tag->term_id);
$damaged_unauthorized_destruction_status_id_arr = array($new_request_tag->term_id, $tabled_tag->term_id, $initial_review_rejected_tag->term_id, $completed_dispositioned_tag->term_id);
$rescan_validate_status_id_arr = array($new_request_tag->term_id, $tabled_tag->term_id, $initial_review_rejected_tag->term_id, $cancelled_tag->term_id, $completed_dispositioned_tag->term_id);
$box_rescan_arr = array($box_pending_tag->term_id, $box_scanning_preparation_tag->term_id, $box_destruction_approved_tag->term_id, $box_destruction_of_source_tag->term_id, $box_completed_dispositioned_tag->term_id, $box_cancelled_tag->term_id);
?>



<div class="bootstrap-iso">
<?php
    //switch out SQL statement depending on if the request is archived
    //START REVIEW
    if($is_active == 1) {
	$folderfile_details = $wpdb->get_row(
		"SELECT 
	a.id as id,
	a.index_level as index_level,
	a.box_id as box_id,
	a.title as title,
	a.date as date,
	a.author as author,
	a.record_type as record_type,
	a.site_name as site_name,
	a.siteid as site_id,
	a.close_date as close_date,
	a.source_format as source_format,
	a.essential_record as essential_record,
	a.validation as validation,
	a.validation_user_id as validation_user_id,
	a.rescan as rescan,
	a.unauthorized_destruction as unauthorized_destruction,
	a.folder_identifier as folder_identifier,
	a.freeze as freeze,
	a.damaged as damaged,
	a.addressee as addressee,
	a.DOC_REGID as sems_reg_id,
	a.folderdocinfofile_id as folderdocinfofile_id,
	a.attachment,
	a.file_name,
	a.object_key,
	a.object_location,
	a.source_file_location,
	a.file_object_id,
	a.file_size,
    a.description,
    a.tags,
    a.access_restriction,
    a.specific_access_restriction,
    a.use_restriction,
    a.specific_use_restriction,
    a.rights_holder,
    a.source_dimensions,
    a.program_area
	
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
    WHERE a.folderdocinfofile_id = '" . $GLOBALS['id'] . "'"
	);
    }
    else {
	$folderfile_details = $wpdb->get_row(
		"SELECT 
	a.id as id,
	a.index_level as index_level,
	a.box_id as box_id,
	a.title as title,
	a.date as date,
	a.author as author,
	a.record_type as record_type,
	a.site_name as site_name,
	a.siteid as site_id,
	a.close_date as close_date,
	a.source_format as source_format,
	a.essential_record as essential_record,
	a.validation as validation,
	a.validation_user_id as validation_user_id,
	a.rescan as rescan,
	a.unauthorized_destruction as unauthorized_destruction,
	a.folder_identifier as folder_identifier,
	a.freeze as freeze,
	a.damaged as damaged,
	a.addressee as addressee,
	a.DOC_REGID as sems_reg_id,
	a.folderdocinfofile_id as folderdocinfofile_id,
	a.attachment,
	a.file_name,
	a.object_key,
	a.object_location,
	a.source_file_location,
	a.file_object_id,
	a.file_size,
    a.description,
    a.tags,
    a.access_restriction,
    a.specific_access_restriction,
    a.use_restriction,
    a.specific_use_restriction,
    a.rights_holder,
    a.source_dimensions,
    a.program_area
	
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive a
    WHERE a.folderdocinfofile_id = '" . $GLOBALS['id'] . "'"
	);
    }
    //folderdocinfofiles table id
    $folderdocinfofileid = $folderfile_details->id;
    //folderdocinfofiles folderdocinfofile_id
	$folderfile_folderdocinfofile_id = $folderfile_details->folderdocinfofile_id;    
    $folderfile_index_level = $folderfile_details->index_level;
	$folderfile_boxid = $folderfile_details->box_id;
	$folderfile_title = $folderfile_details->title;
	$folderfile_date = $folderfile_details->date;
	$folderfile_author = $folderfile_details->author;
	$folderfile_record_type = $folderfile_details->record_type;
	$folderfile_site_name = $folderfile_details->site_name;
	$folderfile_site_id = $folderfile_details->site_id;
	$folderfile_close_date = $folderfile_details->close_date;
	$folderfile_source_format = $folderfile_details->source_format;
	$folderfile_sems_reg_id = $folderfile_details->sems_reg_id;
	$folderfile_file_object_id = $folderfile_details->file_object_id;
	$folderfile_essential_record = $folderfile_details->essential_record;
	$folderfile_validation = $folderfile_details->validation;
	$folderfile_validation_user = $folderfile_details->validation_user_id;	
	$folderfile_rescan = $folderfile_details->rescan;
    $folderfile_destruction = $folderfile_details->unauthorized_destruction;
    $folderfile_identifier = $folderfile_details->folder_identifier;
    $folderfile_freeze = $folderfile_details->freeze;
    $folderfile_damaged = $folderfile_details->damaged;
    $folderfile_addressee = $folderfile_details->addressee;
    $folderfile_description = $folderfile_details->description;
    $folderfile_tags = $folderfile_details->tags;
    $folderfile_access_restriction = $folderfile_details->access_restriction;
    $folderfile_specific_access_restriction = $folderfile_details->specific_access_restriction;
    $folderfile_use_restriction = $folderfile_details->use_restriction;
    $folderfile_specific_use_restriction = $folderfile_details->specific_use_restriction;
    $folderfile_rights_holder = $folderfile_details->rights_holder;
    $folderfile_source_dimensions = $folderfile_details->source_dimensions;
    $folderfile_program_area = $folderfile_details->program_area;
    //END REVIEW
    $user = get_user_by( 'id', $folderfile_validation_user);

    $box_details = $wpdb->get_row("SELECT c.name, a.id, a.box_previous_status, a.box_status, a.box_destroyed, b.request_id as request_id, a.box_id as box_id, a.ticket_id as ticket_id, b.ticket_priority as ticket_priority, 
(SELECT name as ticket_priority FROM " . $wpdb->prefix . "terms WHERE term_id = b.ticket_priority) as ticket_priority_name, b.ticket_status as ticket_status, 
(SELECT name as ticket_status FROM " . $wpdb->prefix . "terms WHERE term_id = b.ticket_status) as ticket_status_name
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON b.id = a.ticket_id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = a.box_status
WHERE a.id = '" . $folderfile_boxid . "'");
    
    $box_boxid = $box_details->box_id;
	$box_ticketid = $box_details->ticket_id;
	$box_requestid = $box_details->request_id;
	$box_destruction = $box_details->box_destroyed;
	$box_status = $box_details->box_status;
	$box_ticket_priority = $box_details->ticket_priority;
	$box_ticket_priority_name = $box_details->ticket_priority_name;
	$box_ticket_status = $box_details->ticket_status;
	$box_ticket_status_name = $box_details->ticket_status_name;
	$box_previous_status = $box_details->box_previous_status;

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
    
    //record schedule
    $box_rs = Patt_Custom_Func::get_record_schedule_by_id($folderfile_folderdocinfofile_id, $type);
    
    //program office
   $box_po = Patt_Custom_Func::get_program_office_by_id($folderfile_folderdocinfofile_id, $type);
    
    //box location
    $location = $wpdb->get_row("SELECT c.name as location, b.aisle as aisle, b.bay as bay, b.shelf as shelf, b.position as position, d.locations
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a 
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b ON b.id = a.storage_location_id
INNER JOIN " . $wpdb->prefix . "terms c ON c.term_id = b.digitization_center
INNER JOIN " . $wpdb->prefix . "wpsc_epa_location_status d ON d.id = a.location_status_id
WHERE a.id ='" . $folderfile_boxid . "'");
    $box_location = $location->location;
	$box_aisle = $location->aisle;
	$box_bay = $location->bay;
	$box_shelf = $location->shelf;
	$box_position = $location->position;
	$box_physical_location = $location->locations;
?>
<style>

#doc_relationship {
    background-color: transparent !important;
    border-width: 0px;
    padding: 0px;
}

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

/*
.wpsc_loading_icon {
	margin-top: 0px !important;
}
*/

</style>

	<h3>PATT Folder/File Transfer Details</h3>

<div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important;">

<div class="row wpsc_tl_action_bar" style="background-color:<?php echo $general_appearance['wpsc_action_bar_color']?> !important;">
  
	<div class="col-sm-12">
    	<button type="button" id="wpsc_individual_ticket_list_btn" aria-label="Request Help Button" onclick="location.href='admin.php?page=wpsc-tickets';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?> margin-right: 30px !important;"><i class="fa fa-list-ul" aria-hidden="true" title="Request List"></i><span class="sr-only">Request List</span> <?php _e('Ticket List','supportcandy')?> <a href="#" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-request-list-button'); ?>" aria-label="Request Help"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a></button>
<?php
// originally /^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}$/
//REVIEW
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'requestdetails' && !empty($folderdocinfofileid)) {
?>
<button type="button" aria-label="Back to Box Details Button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=boxdetails&pid=requestdetails&id=<?php echo $box_boxid ?>';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left" aria-hidden="true" title="Back to Box Details"></i><span class="sr-only">Back to Box Details</span> Back to Box Details</button>
<?php
}
?>
<?php
//REVIEW
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'boxsearch' && !empty($folderdocinfofileid)) {
?>
<button type="button" aria-label="Back to Box Details Button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=boxdetails&pid=requestdetails&id=<?php echo $box_boxid ?>';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left" aria-hidden="true" title="Back to Box Details"></i><span class="sr-only">Back to Box Details</span> Back to Box Details</button>
<?php
}
?>
<?php
//REVIEW
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $GLOBALS['id']) && $GLOBALS['pid'] == 'docsearch' && !empty($folderdocinfofileid)) {
?>
<button type="button" aria-label="Back to PATT Transfer Dashboard Button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="location.href='admin.php?page=patt-transfer';" style="<?php echo $action_default_btn_css?>"><i class="fas fa-chevron-circle-left" aria-hidden="true" title="Back to PATT Transfer Dashboard Button"></i><span class="sr-only">Back to PATT Transfer Dashboard</span> Back to PATT Transfer Dashboard</button>
<?php
}
?>   	
  </div>
	
</div>

<div class="row" style="background-color:<?php echo $general_appearance['wpsc_bg_color']?> !important;color:<?php echo $general_appearance['wpsc_text_color']?> !important;">


<?php
$patt_arms_tranfer_log = $wpdb->get_row("SELECT *
FROM " . $wpdb->prefix . "epa_patt_arms_logs
WHERE folderdocinfofile_id = '" . $GLOBALS['id'] . "'");

$start_stage = Date_Create($patt_arms_tranfer_log->received_stage_timestamp);
$end_stage = Date_Create($patt_arms_transfer_log->metadata_stage_timestamp);
$duration = date_diff($end_stage,$start_stage);

// print_r($patt_arms_tranfer_log->folderdocinfofile_id); die();
//BEGIN folder/file ID check
//REVIEW
if (preg_match("/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,4}(-[a][0-9]{1,4})?$/", $GLOBALS['id']) && !empty($folderdocinfofileid)) {
?>
  <div class="col-sm-8 col-md-9 wpsc_it_body">
    <div class="row wpsc_it_subject_widget">
    </div>

	<h3>
        	
				<!-- //If a file is electronic it should always be a file -->
				<?php if ($folderfile_index_level == '1') {?>
					[Folder ID # <a href="admin.php?pid=docsearch&page=filedetails&id=<?php echo $GLOBALS['id']; ?>" > <?php echo $GLOBALS['id']; ?></a>]
				<?php } 
					else {?>
					[File ID # <a href="admin.php?pid=docsearch&page=filedetails&id=<?php echo $GLOBALS['id']; ?>" > <?php echo $GLOBALS['id']; ?></a>]
				<?php } ?>
			<?php
			?>
		
	</h3>

	<!-- Banner of Overall Duration of the entire process -->
	<div class="alert alert-info mt-1" role="alert">
		Overall Process Duration: <?php echo $duration->format('%H:%I:%S'); ?> <span style="font-size: 1.2em; margin-left: 4px; color: #111;"><i class="fas fa-clock" aria-hidden="true" title="Duration"></i><span class="sr-only"></span></span>
	</div>

<!-- <?php
            //get character count of description and tags
            $description_length = strlen($folderfile_description);
            $tags_length = strlen($folderfile_tags);
            
            
			if(!empty($box_po)) {
			    echo "<strong>Program Office:</strong> <p class='normal_p'>" . $box_po . "</p> <br />";
			}
			else {
			    echo "<strong style='color:red'>Program Office: REASSIGN IMMEDIATELY</strong> <br />";
			}
            
            if(!empty($box_rs)) {
                echo "<strong>Record Schedule:</strong> <p class='normal_p'>" . $box_rs ."</p> <br />";
            }
            else {
			    echo "<strong style='color:red'>Record Schedule: REASSIGN IMMEDIATELY</strong> <br />";
			}
			
			if(!empty($folderfile_identifier)) {
			    echo "<strong>Folder Identifier:</strong> <p class='normal_p'>" . $folderfile_identifier . "</p><br />";
			}
  
  			if (!empty($folderfile_title)) {
				echo "<strong>Title:</strong> <p class='normal_p'>" . $folderfile_title . " </p><br />";
			}
			
			
			if(!empty($folderfile_description)) {
    			if( $description_length > 255 ) {
    			    echo "<strong>Description:</strong> <p class='text_cutoff'>" . $folderfile_description . "</p>";
    			}
    			else {
    			    echo "<strong>Description:</strong> <p class='normal_p'>" . $folderfile_description . "</p><br />";
    			}
			}

			if (!empty($folderfile_date)) {
			    echo "<strong>Creation Date:</strong> <p class='normal_p'>" . Patt_Custom_Func::get_converted_date($folderfile_date) . "</p><br />";
			}
			
			$folderfile_author_array = array();
			$folderfile_author_explode = explode(';', $folderfile_author);
            foreach ($folderfile_author_explode as $creator) {
                array_push($folderfile_author_array, $creator);
            }
			
			if(!empty($folderfile_author)) {
			    echo "<strong>Creator:</strong> <p class='normal_p'>" . implode("; ", $folderfile_author_array) . "</p><br />";
			}
			
			$folderfile_addressee_array = array();
			$folderfile_addressee_explode = explode(';', $folderfile_addressee);
			foreach ($folderfile_addressee_explode as $addressee) {
                array_push($folderfile_addressee_array, $addressee);
            }
			
			if(!empty($folderfile_addressee)) {
			    echo "<strong>Addressee:</strong> <p class='normal_p'>" . implode("; ", $folderfile_addressee_array) . "</p><br />";
			}
			if (!empty($folderfile_record_type)) {
				echo "<strong>Record Type:</strong> <p class='normal_p'>" . $folderfile_record_type . "</p><br />";
			}
			if (!empty($folderfile_site_name)) {
				echo "<strong>Site Name:</strong> <p class='normal_p'>" . $folderfile_site_name . "</p><br />";
			}
			if (!empty($folderfile_site_id)) {
				echo "<strong>Site ID #:</strong> <p class='normal_p'>" . $folderfile_site_id . "</p><br />";
			}
			if (!empty($folderfile_close_date)) {
			    echo "<strong>Close Date:</strong> <p class='normal_p'>" . Patt_Custom_Func::get_converted_date($folderfile_close_date) . "</p><br />";
			}
			
			if(!empty($folderfile_access_restriction)) {
			    echo "<strong>Access Restriction:</strong> <p class='normal_p'>" . $folderfile_access_restriction . "</p><br />";
			}
			
			$folderfile_specific_access_restrictions_array = array();
			$folderfile_specific_access_restrictions_explode = explode(';', $folderfile_specific_access_restriction);
			foreach ($folderfile_specific_access_restrictions_explode as $specific_access_restriction) {
                array_push($folderfile_specific_access_restrictions_array, $specific_access_restriction);
            }

			if(!empty($folderfile_specific_access_restriction)) {
			    echo "<strong>Specfic Access Restriction:</strong> <p class='normal_p'>" . implode("; ", $folderfile_specific_access_restrictions_array) . "</p><br />";
			}
			
			
			if(!empty($folderfile_use_restriction)) {
			    echo "<strong>Use Restriction:</strong> <p class='normal_p'>" . $folderfile_use_restriction . "</p><br />";
			}
			
			$folderfile_specific_use_restrictions_array = array();
			$folderfile_specific_use_restrictions_explode = explode(';', $folderfile_specific_use_restriction);
			foreach ($folderfile_specific_use_restrictions_explode as $specific_use_restriction) {
                array_push($folderfile_specific_use_restrictions_array, $specific_use_restriction);
            }
			
			if(!empty($folderfile_specific_use_restriction)) {
			    echo "<strong>Specific Use Restriction:</strong> <p class='normal_p'>" . implode("; ", $folderfile_specific_use_restrictions_array) . "</p><br />";
			}
			
			$folderfile_rights_holder_array = array();
			$folderfile_rights_holder_explode = explode(';', $folderfile_rights_holder);
			foreach ($folderfile_rights_holder_explode as $rights_holder) {
                array_push($folderfile_rights_holder_array, $rights_holder);
            }

			if(!empty($folderfile_rights_holder)) {
			    echo "<strong>Rights Holder:</strong> <p class='normal_p'>" . implode("; ", $folderfile_rights_holder_array) . "</p><br />";
			}
			
			if (!empty($folderfile_source_format)) {
				echo "<strong>Source Type:</strong> <p class='normal_p'>" . stripslashes($folderfile_source_format) . "</p><br />";
			}
			
			if(!empty($folderfile_source_dimensions)) {
			    echo "<strong>Source Dimensions:</strong> <p class='normal_p'>" . stripslashes($folderfile_source_dimensions) . "</p><br />";
			}
			
			if(!empty($folderfile_program_area)) {
			    echo "<strong>Program Area:</strong> <p class='normal_p'>" . $folderfile_program_area . "</p><br />";
			}
			
			if($folderfile_essential_record == 1) {
			    echo "<strong>Essential Record:</strong> <p class='normal_p'>Yes" . "</p><br />";
			}
			else {
			    echo "<strong>Essential Record:</strong> <p class='normal_p'>No" . "</p><br />";
			}
			
			$folderfile_tags_array = array();
			$folderfile_tags_explode = explode(',', $folderfile_tags);
			foreach ($folderfile_tags_explode as $tag) {
                array_push($folderfile_tags_array, $tag);
            }
			
			if(!empty($folderfile_tags)) {
    			if($tags_length > 255) {
    			    echo "<strong>Tags:</strong> <p class='text_cutoff'>" . implode(", ", $folderfile_tags_array) . "</p><br />";
    			}
    			else {
    			    echo "<strong>Tags:</strong> <p class='normal_p'>" . implode(", ", $folderfile_tags_array) . "</p><br />";
    			}
			}

wp_get_current_user();
//echo "<br /><br />";
$ticket_details = $wpdb->get_row("SELECT customer_name
FROM " . $wpdb->prefix . "wpsc_ticket
WHERE id = '" . $box_ticketid . "'");

$ticket_user = $ticket_details->customer_name;


$received_timestamp = Date_Create($patt_arms_tranfer_log->received_stage_timestamp);
$extraction_timestamp = Date_Create($patt_arms_tranfer_log->extraction_stage_timestamp);
$keyword_id_timestamp = Date_Create($patt_arms_tranfer_log->keyword_id_stage_timestamp);
$metadata_timestamp = Date_Create($patt_arms_tranfer_log->metadata_stage_timestamp);
$arms_timestamp = Date_Create($patt_arms_tranfer_log->arms_stage_timestamp);
$published_timestamp = Date_Create($patt_arms_tranfer_log->published_stage_timestamp);

?> -->


<!-- Accordion 6 stage detail buttons/tabs html and css -->
	<hr />
	
		
	<!-- Buttons that show more details about the process of each stage -->
	<button class="btn wpsc_action_btn btn-patt-transfer" type="button" data-toggle="collapse" data-target="#Received_stage" aria-expanded="false" aria-controls="collapseExample">
		<?php if($patt_arms_tranfer_log->received_stage == 0) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Pending"><span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span></a>
		<?php } ?>
		<?php if($patt_arms_tranfer_log->received_stage == 1) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Completed " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #2f631d; margin-left:4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i></span></a>	
		<?php } ?>
		<?php if($patt_arms_tranfer_log->received_stage == 2) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Error " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #B4081A; margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i></span></a>	
		<?php } ?>
		<?php if($patt_arms_tranfer_log->received_stage == 3) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Warning"><span class="badge" style="font-size: 1.1em; color: #dba617; margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i></span></a>	
		<?php } ?>
			<span>Received</span>
		<!-- <span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span> -->
	</button>
	<button class="btn wpsc_action_btn btn-patt-transfer" type="button" data-toggle="collapse" data-target="#Extraction_stage" aria-expanded="false" aria-controls="collapseExample">
	<?php if($patt_arms_tranfer_log->extraction_stage == 0) { ?>
		<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Pending"><span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span></a>
		<?php } ?>
		<?php if($patt_arms_tranfer_log->extraction_stage == 1) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Completed " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #2f631d; margin-left:4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i></span></a>	
		<?php } ?>
		<?php if($patt_arms_tranfer_log->extraction_stage == 2) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Error " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #B4081A; margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i></span></a>	
		<?php } ?>
		<?php if($patt_arms_tranfer_log->extraction_stage == 3) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Warning"><span class="badge" style="font-size: 1.1em; color: #dba617; margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i></span></a>	
		<?php } ?>
			Text Extraction 
		<!-- <span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span> -->
	</button>
	<button class="btn wpsc_action_btn btn-patt-transfer" type="button" data-toggle="collapse" data-target="#Keyword_id_stage" aria-expanded="false" aria-controls="collapseExample">
		<?php if($patt_arms_tranfer_log->keyword_id_stage == 0) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Pending"><span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span></a>
		<?php } ?>
		<?php if($patt_arms_tranfer_log->keyword_id_stage == 1) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Completed " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #2f631d; margin-left:4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i></span></a>	
		<?php } ?>
		<?php if($patt_arms_tranfer_log->keyword_id_stage == 2) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Error " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #B4081A; margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i></span></a>	
		<?php } ?>
		<?php if($patt_arms_tranfer_log->keyword_id_stage == 3) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Warning"><span class="badge" style="font-size: 1.1em; color: #dba617; margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i></span></a>	
		<?php } ?>
			Keyword ID 
		<!-- <span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span> -->
	</button>
	<button class="btn wpsc_action_btn btn-patt-transfer" type="button" data-toggle="collapse" data-target="#Metadata_stage" aria-expanded="false" aria-controls="collapseExample">
		<?php if($patt_arms_tranfer_log->metadata_stage == 0) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Pending"><span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span></a>
		<?php } 
		elseif($patt_arms_tranfer_log->metadata_stage == 1) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Completed " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #2f631d; margin-left:4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i></span></a>	
		<?php } 
		elseif($patt_arms_tranfer_log->metadata_stage == 2) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Error " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #B4081A; margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i></span></a>	
		<?php } 
		else { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Warning"><span class="badge" style="font-size: 1.1em; color: #dba617; margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i></span></a>	
		<?php } ?>
			Metadata
		<!-- <span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span> -->
	</button>
	<button class="btn wpsc_action_btn btn-patt-transfer" type="button" data-toggle="collapse" data-target="#ARMS_stage" aria-expanded="false" aria-controls="collapseExample">
		<?php if($patt_arms_tranfer_log->arms_stage == 0) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Pending"><span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span></a>
		<?php } 
		elseif($patt_arms_tranfer_log->arms_stage == 1) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Completed " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #2f631d; margin-left:4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i></span></a>	
		<?php } 
		elseif($patt_arms_tranfer_log->arms_stage == 2) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Error " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #B4081A; margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i></span></a>	
		<?php } 
		else { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Warning"><span class="badge" style="font-size: 1.1em; color: #dba617; margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i></span></a>	
		<?php } ?>
			ARMS 
		<!-- <span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span> -->
	</button>
	<button class="btn wpsc_action_btn btn-patt-transfer" type="button" data-toggle="collapse" data-target="#Published_stage" aria-expanded="false" aria-controls="collapseExample">
		<?php if($patt_arms_tranfer_log->published_stage == 0) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Pending"><span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span></a>
		<?php } 
		elseif($patt_arms_tranfer_log->published_stage == 1) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Completed " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #2f631d; margin-left:4px;"><i class="fas fa-check-circle" aria-hidden="true" title="Success"></i></span></a>	
		<?php } 
		elseif($patt_arms_tranfer_log->published_stage == 2) { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?php echo "Error " . $received_timestamp->format('m/d/Y') . "\n" . $received_timestamp->format('h:ia'); ?>"><span class="badge" style="font-size: 1.1em; color: #B4081A; margin-left:4px;"><i class="fas fa-times-circle" aria-hidden="true" title="Failed"></i></span></a>	
		<?php } 
		else { ?>
			<a class="truncate-text" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="Received Stage: Warning"><span class="badge" style="font-size: 1.1em; color: #dba617; margin-left:4px;"><i class="fas fa-exclamation-circle" aria-hidden="true" title="Warning"></i></span></a>	
		<?php } ?>
			Published 
		<!-- <span class="badge" style="font-size: 1.1em; color: #1C5D8A; margin-left: 4px;"><i class="fas fa-sync" aria-hidden="true" title="Pending"></i></span> -->
	</button>

	<!-- All of the stages logs content will be printed out within the collapsible divs -->
	<?php if($patt_arms_tranfer_log->received_stage == 2 || $patt_arms_tranfer_log->received_stage == 3) { ?>
		<div class="collapse mt-1" id="Received_stage">
			<div class="well">
				<?php echo $patt_arms_tranfer_log->received_stage_log; ?>
			</div>
		</div>
	<?php } ?>
	<?php if($patt_arms_tranfer_log->extraction_stage == 2 || $patt_arms_tranfer_log->extraction_stage == 3) { ?>
		<div class="collapse mt-1" id="Extraction_stage">
			<div class="well">
				<?php echo $patt_arms_tranfer_log->extraction_stage_log; ?>
			</div>
		</div>
	<?php } ?>
	<?php if($patt_arms_tranfer_log->keyword_id_stage == 2 || $patt_arms_tranfer_log->keyword_id_stage == 3) { ?>
		<div class="collapse mt-1" id="Keyword_id_stage">
			<div class="well">
				<?php echo $patt_arms_tranfer_log->keyword_id_stage_log; ?>
			</div>
		</div>
	<?php } ?>
	<?php if($patt_arms_tranfer_log->metadata_stage == 2 || $patt_arms_tranfer_log->metadata_stage == 3) { ?>
		<div class="collapse mt-1" id="Metadata_stage">
			<div class="well">
				<?php echo $patt_arms_tranfer_log->metadata_stage_log; ?>
			</div>
		</div>
	<?php } ?>
	<?php if($patt_arms_tranfer_log->arms_stage == 2 || $patt_arms_tranfer_log->arms_stage == 3) { ?>
		<div class="collapse mt-1" id="ARMS_stage">
			<div class="well">
				<?php echo $patt_arms_tranfer_log->arms_stage_log; ?>
			</div>
		</div>
	<?php } ?>
	<?php if($patt_arms_tranfer_log->published_stage == 2 || $patt_arms_tranfer_log->published_stage == 3) { ?>
	<div class="collapse mt-1" id="Published_stage">
		<div class="well">
			<?php echo $patt_arms_tranfer_log->published_stage_log; ?>
		</div>
	</div>
	<?php } ?>


<style>

.focus-section:focus {
  background-color: pink; /* Something to get the user's attention */
}

.normal_p {
    display: inline;
}

.text_cutoff{
    text-overflow: ellipsis;
    cursor: pointer;
    overflow:hidden;
    white-space: nowrap;
    max-width: 1000px;
}
.text_cutoff:hover{
    overflow: visible; 
    white-space: normal;
}

	.details-name {
		font-weight: bold;
	}
	
	.red-alert {
		color: #a80000;
		font-weight: bold;
	}
	
	.wpsc_loading_icon {
		margin-top: 0px !important;
	}
	
	.dropzone .dz-preview .dz-progress {
		top: 70%;
		display: none;
	}
	
	.bootstrap-iso .btn-patt-transfer {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		border-radius: 30px;
		border: 1px solid #777;
		/* max-width: 130px; */
		background-color: transparent;
	}

	.bootstrap-iso .btn-patt-transfer .badge {
		background-color: transparent;
	}

	.mt-1 {
		margin-top: 1.3rem;
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

if ($folderfile_sems_reg_id != '' || $folderfile_folderdocinfofile_id != '') {
?>
<br /><br />
<h3 style="display: inline;">Links to Electronic Records in SEMS <a href="#" aria-label="Help" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-links-to-ecms'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a></h3>

<br /><br />
<div id="wpsc_raised_extra_info">
	<table class="table table-hover">
		<tbody>
		<!--<tr>
			<td style="width:200px;"><strong>Date</strong></td>
			<td><?php echo $folderfile_date; ?></td>
		</tr>-->
    		<th scope="col"><strong>Title</strong></th>
    		<th scope="col"><strong>Doc ID</strong></th>
    		<tr>
    			<td class="text_highlight"><a href="https://semspub.epa.gov/work/<?php echo $folderfile_sems_reg_id; ?>/<?php echo $folderfile_folderdocinfofile_id; ?>.pdf" target="_blank"><?php echo $folderfile_title; ?></a></td>
    			<td><?php echo $folderfile_folderdocinfofile_id; ?></td>
    		</tr>
    	    </tbody>
	</table>
</div>

<?php
}

            } else {
// Table displayed for paper records split files on both folder and files

//$get_all_ecms_attachments = $wpdb->get_results("SELECT *
//FROM wpqa_wpsc_epa_folderdocinfo_files
//WHERE object_key != '' AND file_object_id != '' AND folderdocinfo_id = '" . $GLOBALS['id'] . "'");

$get_all_ecms_attachments = $wpdb->get_results("SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE file_object_id != '' AND folderdocinfofile_id = '" . $GLOBALS['id'] . "'");

if (count($get_all_ecms_attachments)> 0 && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent')  || ($agent_permissions['label'] == 'Manager') || $current_user->nickname == $ticket_user)) {

?>
<style>
.datatable_header {
    background-color: rgb(66, 73, 73) !important;
    color: rgb(255, 255, 255) !important;
}
</style>
<h3 style="display: inline;">Links to Electronic Records in ECMS <a href="#" aria-label="Help" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-links-to-ecms'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a>
</h3>
<div class="table-responsive" style="overflow-x:auto;">
<br />
<?php
if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')) {
echo '<button type="button" class="button" id="wpsc_ecms_delete_btn"><i class="fa fa-trash" aria-hidden="true" title="Submit ECMS Removal Request"></i><span class="sr-only">Submit ECMS Removal Request</span> Submit ECMS Removal Request </button><br /><br />';
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
//Check to see if file exists in s3. If it does not exist  AND not flagged as SEMS display ECMS table.

if ($s3_exist == 0) {

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

<br />

<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>

<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>

<script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js?ver=5.6.1' id='bootstrap-cdn-js-js'></script>

<style>
#wpfooter {
    margin-left: -20px;
}

#doc_relationship {
    cursor: pointer;
}
</style>

<script>
 jQuery(document).ready(function() {

//jQuery('[data-toggle="tooltip"]').tooltip(); 

	var dataTable = jQuery('#tbl_templates_ecms').DataTable({
	     "autoWidth": true,
	     "paging" : true,
	     "scrollX" : true,
		 "aLengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
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

jQuery('#wpsc_individual_damaged_btn').on('click', function(e){
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_damaged.php',{
postvarsfolderdocid : jQuery('#doc_id').val(),
postvarpage : jQuery('#page').val()
}, 
   function (response) {
      wpsc_modal_open('Damaged');
		  var data = {
		    action: 'wpsc_get_damaged_ffd',
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

jQuery('#doc_relationship').on('click', function(){

       wpsc_modal_open('Document Relationship');
		  var data = {
		    action: 'wpsc_get_doc_relationship',
		    doc_id: '<?php echo $folderfile_folderdocinfofile_id; ?>',
		    is_active: '<?php echo $is_active; ?>'
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  }); 
});	


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

function wpsc_get_epa_contact_editor(folderdocinfofile_id) {
    wpsc_modal_open('Edit EPA Contact');
	var data = {
		action: 'wpsc_get_epa_contact_editor',
		folderdocinfofile_id: folderdocinfofile_id
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
			<h4 class="widget_header"><i class="fa fa-info-circle" aria-hidden="true" title="Information/Location"></i><span class="sr-only">Information/Location</span> Information/Location
			</h4>
				<hr class="widget_divider">
				<div class="wpsp_sidebar_labels"><strong>Request ID:</strong><br /> 
				<?php 
					echo "<a href='admin.php?page=wpsc-tickets&id=" . $box_requestid . "' style='color:#1d4289; text-decoration: underline;'>" . $box_requestid . "</a>";
				?>
				</div>
				
				<div class="wpsp_sidebar_labels"><strong>Request Status:</strong><br />
				<?php 
					echo $request_status;
				?>
				</div>
				
				<div class="wpsp_sidebar_labels"><strong>Priority:</strong><br />
				<?php 
					echo $priority;
				?>
				</div>
				
				<div class="wpsp_sidebar_labels"><strong>Box ID:</strong><br /> 
				<?php
				if (!empty($box_boxid)) {
					if ($GLOBALS['pid'] == 'requestdetails') {
					echo "<a href='admin.php?pid=requestdetails&page=boxdetails&id=" . $box_boxid . "' style='color:#1d4289; text-decoration: underline;'>" . $box_boxid . "</a>";
					}
					if ($GLOBALS['pid'] == 'boxsearch') {
					echo "<a href='admin.php?pid=boxsearch&page=boxdetails&id=" . $box_boxid . "' style='color:#1d4289; text-decoration: underline;'>" . $box_boxid . "</a>";
					}
					if ($GLOBALS['pid'] == 'docsearch') {
					echo "<a href='admin.php?pid=docsearch&page=boxdetails&id=" . $box_boxid . "' style='color:#1d4289; text-decoration: underline;'>" . $box_boxid . "</a>";
					}
					} ?>
					</div>
					
				<div class="wpsp_sidebar_labels"><strong>Box Status:</strong><br /> 
				<?php 
					$get_previous_box_status_name = $wpdb->get_row("SELECT name
					FROM wpqa_terms
					WHERE term_id = " . $box_previous_status);
					$previous_box_status_name = $get_previous_box_status_name->name;
					
					$show_previous_box_status_tooltip = array($box_waiting_shelved_tag->term_id, $box_waiting_on_rlo_tag->term_id, $box_cancelled_tag->term_id);
					$previous_box_status = "<a href='#' style='color: #000000 !important;' data-toggle='tooltip' data-placement='right' data-html='true' aria-label='Previous Box Status' title='Previous Box Status: ".$previous_box_status_name."'><span class='wpsp_admin_label' style='".$status_style."'>".$box_status_name."</span></a>";
					$only_box_status = "<span class='wpsp_admin_label' style='".$status_style."'>".$box_status_name."</span></a>";
					
					if(in_array($box_status, $show_previous_box_status_tooltip) && $box_previous_status != 0) {
						echo $previous_box_status;
					}
					else {
						echo $only_box_status;
					}
				?>
				</div>
				
				<?php 
					if(Patt_Custom_Func::get_pallet_id_by_id($folderfile_folderdocinfofile_id, $type) != false) {
					?>
					<div class="wpsp_sidebar_labels"><strong>Pallet ID:</strong><br />
					<?php 
					echo Patt_Custom_Func::get_pallet_id_by_id($folderfile_folderdocinfofile_id, $type);
					} 
					?>
				
				<?php 
				//Indicator of parent/child document
				$get_parent_child = Patt_Custom_Func::parent_child_indicator($folderfile_folderdocinfofile_id, $type);
				
				if($get_parent_child == 0) {
					$child_count = Patt_Custom_Func::get_count_of_children_for_parent($folderfile_folderdocinfofile_id, $type);
					if($child_count == 0) {
						$parent_child = 'Parent with no child documents';
					}
					else if($child_count == 1) {
						$parent_child = 'Parent to ' . $child_count . ' child document <button id="doc_relationship"><i id="doc_relationship_icon" class="fab fa-buffer fa-lg" aria-hidden="true" title="Document Relationship"></i><span class="sr-only">Document Relationship</span></button>';
					}
					else {
						$parent_child = 'Parent to ' . $child_count . ' child documents <button id="doc_relationship"><i id="doc_relationship_icon" class="fab fa-buffer fa-lg" aria-hidden="true" title="Document Relationship"></i><span class="sr-only">Document Relationship</span></button>';
					}
				}
				else {
					$get_parent_of_child = Patt_Custom_Func::get_parent_of_child($folderfile_folderdocinfofile_id, $type);
					$parent_child = 'Child document of <a href="'. $subfolder_path . '/wp-admin/admin.php?pid='.$GLOBALS['pid'].'&page=filedetails&id='.$get_parent_of_child.'">'. $get_parent_of_child . '</a> <button id="doc_relationship"><i id="doc_relationship_icon" class="fab fa-buffer fa-lg" aria-hidden="true" title="Document Relationship"></i><span class="sr-only">Document Relationship</span></button>';
				}
				?>
				<div class="wpsp_sidebar_labels"><strong>Parent/Child Relationship: </strong><br /> <?php echo $parent_child; ?></div>
				
				<hr class="widget_divider">
				<?php
				//if digitization_center field is empty, will not display location on front end
				if(!empty($box_location)) {
				echo '<div class="wpsp_sidebar_labels"><strong>Digitization Center: </strong><br />';
				echo $box_location . "<br />";
				if(!empty($box_physical_location) && Patt_Custom_Func::id_in_physical_location($folderfile_folderdocinfofile_id, $type) != '') {
					echo '<div class="wpsp_sidebar_labels"><strong>Physical Location: </strong><br />';
					echo $box_physical_location . ' (' . Patt_Custom_Func::id_in_physical_location($folderfile_folderdocinfofile_id, $type) . ')'.  "<br />";
				}
				else {
					echo '<div class="wpsp_sidebar_labels"><strong>Physical Location: </strong><br />';
					echo $box_physical_location;
				}
					//if aisle/bay/shelf/position <= 0 and not 'On Shelf', does not display location on front end
					if($box_physical_location == 'On Shelf' && !($box_aisle <= 0 && $box_bay <= 0 && $box_shelf <= 0 && $box_position <= 0))
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
		
		<!-- </div> -->
	
	</div>
<?php
} else {
// END folder/file ID Check
echo '<span style="padding-left: 10px">Please pass a valid Folder/File ID</span>';

}
?>
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