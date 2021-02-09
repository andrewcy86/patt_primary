<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

$subfolder_path = site_url( '', 'relative'); 

if (!isset($_SESSION)) {
    session_start();    
}

$doc_id = $_POST["doc_id"];
        
ob_start();
//echo $doc_id;
			/*$folderfile_details = $wpdb->get_row(
				"SELECT *
            FROM wpqa_wpsc_epa_folderdocinfo WHERE id = '" . $doc_id . "'"
			);
            */
            
            $folderfile_details = $wpdb->get_row("SELECT 
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
			a.access_type as access_type,
			b.source_format as source_format,
			a.folderdocinfo_id as folderdocinfo_id,
			a.essential_record as essential_record,
			a.folder_identifier as folder_identifier,
			a.addressee as addressee,
			b.folderdocinfofile_id as folderdocinfofile_id,
			b.id as folderdocinfofileid,
            b.description,
            b.tags,
            b.access_restriction,
            b.use_restriction,
            b.specific_use_restriction,
            b.rights_holder,
            b.source_dimensions
			
            FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo a
            INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON a.id = b.folderdocinfo_id
            WHERE b.id = '" . $doc_id . "'");
            
            $folderfile_id = $folderfile_details->id;
			$folderfile_boxid = $folderfile_details->box_id;
			$folderfile_folderdocinfoid = $folderfile_details->folderdocinfo_id;
			$folderfile_il = $folderfile_details->index_level;
			$folderfile_title = $folderfile_details->title;
			$folderfile_date = $folderfile_details->date;
			$folderfile_author = $folderfile_details->author;
			$folderfile_record_type = $folderfile_details->record_type;
			$folderfile_site_name = $folderfile_details->site_name;
			$folderfile_site_id = $folderfile_details->site_id;
			$folderfile_close_date = $folderfile_details->close_date;
			$folderfile_access_type = $folderfile_details->access_type;
			$folderfile_source_format = $folderfile_details->source_format;
			//$folderfile_rights = $folderfile_details->rights;
			//$folderfile_contract_number = $folderfile_details->contract_number;
			//$folderfile_grant_number = $folderfile_details->grant_number;
			$folderfile_file_location = $folderfile_details->file_location;
			$folderfile_file_name = $folderfile_details->file_name;
			$folderfile_essential_record = $folderfile_details->essential_record;
			$folderfile_identifier = $folderfile_details->folder_identifier;
			$folderfile_addressee = $folderfile_details->addressee;
			
			$folderfile_folderdocinfofile_id = $folderfile_details->folderdocinfofile_id;
			$folderdocinfofileid = $folderfile_details->folderdocinfofileid;
			
			$folderfile_description = $folderfile_details->description;
            $folderfile_tags = $folderfile_details->tags;
            $folderfile_access_restriction = $folderfile_details->access_restriction;
            $folderfile_use_restriction = $folderfile_details->use_restriction;
            $folderfile_specific_use_restriction = $folderfile_details->specific_use_restriction;
            $folderfile_rights_holder = $folderfile_details->rights_holder;
            $folderfile_source_dimensions = $folderfile_details->source_dimensions;
?>

<form>
<strong>Index Level:</strong><br />
<select id="il" name="il">
  <option value="1" <?php if ($folderfile_il == 1 ) echo 'selected' ; ?>>Folder</option>
  <option value="2" <?php if ($folderfile_il == 2 ) echo 'selected' ; ?>>File</option>
</select></br></br>

<?php
//}
//placeholders with 'Enter...' only appear if that field is empty in the database, otherwise show current data

if(!empty($folderfile_title)) {
    echo "<strong>Title:</strong><br /><input type='text' id='title' placeholder= '$folderfile_title'></br></br>";
}
else {
    echo "<strong>Title:</strong><br /><input type='text' id='title' placeholder= 'Enter title...'></br></br>";
}

if(!empty($folderfile_date)) {
    echo "<strong>Date:</strong><br /><input type='date' id='date' placeholder= '$folderfile_date'></br></br>";
}
else {
    echo "<strong>Date:</strong><br /><input type='date' id='date' placeholder= 'mm/dd/yyyy'></br></br>";
}

if(!empty($folderfile_author)) {
    echo "<strong>Author:</strong><br /><input type='text' id='author' placeholder= '$folderfile_author'></br></br>";
}
else {
    echo "<strong>Author:</strong><br /><input type='text' id='author' placeholder= 'Enter author...'></br></br>";
}

if(!empty($folderfile_addressee)) {
    echo "<strong>Addressee:</strong><br /><input type='text' id='addressee' placeholder= '$folderfile_addressee'></br></br>";
}
else {
    echo "<strong>Addressee:</strong><br /><input type='text' id='addressee' placeholder= 'Enter addressee...'></br></br>";
}

if(!empty($folderfile_record_type)) {
echo "<strong>Record Type:</strong><br /><input type='text' id='record_type' placeholder= '$folderfile_record_type'></br></br>";
}
else {
    echo "<strong>Record Type:</strong><br /><input type='text' id='record_type' placeholder= 'Enter record type...'></br></br>";
}

if(!empty($folderfile_site_name)) {
    echo "<strong>Site Name:</strong><br /><input type='text' id='site_name' placeholder= '$folderfile_site_name'></br></br>";
}
else {
    echo "<strong>Site Name:</strong><br /><input type='text' id='site_name' placeholder= 'Enter site name...'></br></br>";
}

if(!empty($folderfile_site_id)) {
    echo "<strong>Site ID:</strong><br /><input type='text' id='site_id' placeholder= '$folderfile_site_id'></br></br>";
}
else {
    echo "<strong>Site ID:</strong><br /><input type='text' id='site_id' placeholder= 'Enter site ID...'></br></br>";
}

if(!empty($folderfile_close_date)) {
    echo "<strong>Close Date:</strong><br /><input type='date' id='close_date' placeholder= '$folderfile_close_date'></br></br>";
}
else {
    echo "<strong>Close Date:</strong><br /><input type='date' id='close_date' placeholder= 'Enter close date...'></br></br>";
}

//use_restriction may be replacing the access_type
if(!empty($folderfile_access_type)) {
    echo "<strong>Access Type:</strong><br /><input type='text' id='access_type' placeholder= '$folderfile_access_type'></br></br>";
}
else {
    echo "<strong>Access Type:</strong><br /><input type='text' id='access_type' placeholder= 'Enter access type...'></br></br>";
}

/*if(!empty($folderfile_source_format)) {
    echo "<strong>Source Format:</strong><br /><input type='text' id='source_format' placeholder= '$folderfile_source_format'></br></br>";
}
else {
    echo "<strong>Source Format:</strong><br /><input type='text' id='source_format' placeholder= 'Enter source format...'></br></br>";
}
*/

//source format is now a datalist
?>
<strong>Source Format:</strong></br>
<input type="search" list="SourceFormatList" placeholder='Enter source format' id='sf'/>
    <datalist id = 'SourceFormatList'>
        <option value='Paper'></option>
        <option value='Audio'></option>
        <option value='Thumb Drive'></option>
        <option value='CD [includes mini CD]'></option>
        <option value='DVD [includes mini DVD]'></option>
        <option value='Negatives [includes microfiche]'></option>
        <option value='Photos'></option>
        <option value='Mylar'></option>
        <option value='Oversize'></option>
        <option value='Bound Book'></option>
        <option value='Electronic File [Audio, visual, zip, etc.]'></option>
        <option value='E-Mail'></option>
        <option value='Blu-ray'></option>
        <option value='UHD'></option>
        <option value='4K'></option>
        <option value='ZIP Drive'></option>
        <option value='Floppy Disk [3.5" or 5"]'></option>
        <option value='VHS'></option>
        <option value='Hard Drive'></option>
        <option value='LaserDisc'></option>
        <option value='Vinyl record'></option>
    </datalist>
</br></br>
<?php
if(!empty($folderfile_identifier)) {
    echo "<strong>Folder Identifier:</strong><br /><input type='text' id='folder_identifier' placeholder= '$folderfile_identifier'><br />";
}
else {
    echo "<strong>Folder Identifier:</strong><br /><input type='text' id='folder_identifier' placeholder= 'Enter folder identifier...'><br />";
}

?>
<br><strong>Essential Record:</strong><br />
<select id="er" name="er">
  <option value="1" <?php if ($folderfile_essential_record == 1 ) echo 'selected' ; ?>>Yes</option>
  <option value="0" <?php if ($folderfile_essential_record == 0) echo 'selected' ; ?>>No</option>
</select></br></br>

<?php 
if(!empty($folderfile_description)) {
    echo "<strong>Description:</strong><br /><input type='text' id='description' placeholder= '$folderfile_description'><br /><br />";
}
else {
    echo "<strong>Description:</strong><br /><input type='text' id='description' placeholder= 'Enter description...'><br /><br />";
}

if(!empty($folderfile_tags)) {
    echo "<strong>Tags:</strong><br /><input type='text' id='tags' placeholder= '$folderfile_tags'><br /><br />";
}
else {
    echo "<strong>Tags:</strong><br /><input type='text' id='tags' placeholder= 'Enter tags...'><br /><br />";
}

if(!empty($folderfile_access_restriction)) {
    echo "<strong>Access Restriction:</strong><br /><input type='text' id='access_restriction' placeholder= '$folderfile_access_restriction'><br />";
}
else {
    echo "<strong>Access Restriction:</strong><br /><input type='text' id='access_restriction' placeholder= 'Enter access restriction...'><br />";
}
?>
<br><strong>Use Restriction:</strong><br />
<select id="use_restriction" name="use_restriction">
  <option value="Shared" <?php if ($folderfile_use_restriction == 'Shared' || $folderfile_use_restriction == 'shared') echo 'selected' ; ?>>Shared</option>
  <option value="Private" <?php if ($folderfile_use_restriction == 'Private' || $folderfile_use_restriction == 'private') echo 'selected' ; ?>>Private</option>
</select></br><br />

<?php
if(!empty($folderfile_specific_use_restriction)) {
    echo "<strong>Specific Use Restriction:</strong><br /><input type='text' id='specific_use_restriction' placeholder= '$folderfile_specific_use_restriction'><br /><br />";
}
else {
    echo "<strong>Specific Use Restriction:</strong><br /><input type='text' id='specific_use_restriction' placeholder= 'Enter specific use restriction...'><br /><br />";
}

if(!empty($folderfile_rights_holder)) {
    echo "<strong>Rights Holder:</strong><br /><input type='text' id='rights_holder' placeholder= '$folderfile_rights_holder'><br /><br />";
}
else {
    echo "<strong>Rights Holder:</strong><br /><input type='text' id='rights_holder' placeholder= 'Enter rights holder...'><br /><br />";
}

if(!empty($folderfile_source_dimensions)) {
    echo "<strong>Source Dimensions:</strong><br /><input type='text' id='source_dimensions' placeholder= '$folderfile_source_dimensions'><br />";
}
else {
    echo "<strong>Source Dimensions:</strong><br /><input type='text' id='source_dimensions' placeholder= 'Enter source dimensions...'><br />";
}
?>

<input type="hidden" id="folderfileid" name="folderfileid" value="<?php echo $folderfile_id; ?>">
<input type="hidden" id="pattdocid" name="pattdocid" value="<?php echo $folderfile_folderdocinfoid; ?>">
<input type="hidden" id="folderdocinfofile_id" name="folderdocinfofile_id" value="<?php echo $folderdocinfofileid; ?>">
</form>
<?php 
$body = ob_get_clean();
ob_start();
?>

<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wpsc_edit_folder_file_details();"><?php _e('Save','supportcandy');?></button>

<script>

function wpsc_edit_folder_file_details(){
//var source_format_value = jQuery('#sf').val();
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_folder_file_details.php',{
//postvarssf: jQuery('#SourceFormatList [value="' + source_format_value + '"]').data('value'),
postvarsffid: jQuery("#folderfileid").val(),
postvarspdid: jQuery("#pattdocid").val(),
postvarsfdiid: jQuery("#folderdocinfofile_id").val(),
postvarsil: jQuery("#il").val(),
postvarsrs: jQuery("#record_schedule").val(),
postvarstitle: jQuery("#title").val(),
postvarsdate: jQuery("#date").val(),
postvarsauthor: jQuery("#author").val(),
postvarsrt: jQuery("#record_type").val(),
postvarssn: jQuery("#site_name").val(),
postvarssid: jQuery("#site_id").val(),
postvarscd: jQuery("#close_date").val(),
postvarsce: jQuery("#contact_email").val(),
postvarsat: jQuery("#access_type").val(),
postvarssf: jQuery('#sf').val(),
//postvarssf: jQuery('#SourceFormatList').val(),
//postvarsrights: jQuery("#rights").val(),
//postvarscn: jQuery("#contract_number").val(),
//postvarsgn: jQuery("#grant_number").val(),
postvarser: jQuery("#er").val(),
postvarsfi: jQuery("#folder_identifier").val(),
postvarsaddressee: jQuery("#addressee").val(),
postvarsdescription: jQuery("#description").val(),
postvarstags: jQuery("#tags").val(),
apostvarsaccessrestriction: jQuery("#access_restriction").val(),
postvarsuserestriction: jQuery("#use_restriction").val(),
postvarsspecificuserestriction: jQuery("#specific_use_restriction").val(),
postvarsrightsholder: jQuery("#rights_holder").val(),
postvarssourcedimensions: jQuery("#source_dimensions").val()
}, 

   function (response) {
      //if(!alert(response)){window.location.reload();}

if(jQuery("#il").val() == 1) {
window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?pid=boxsearch&page=filedetails&id=<?php

$strings = explode('-',$folderfile_folderdocinfofile_id);
if(count($strings) == 4) {
echo $strings[0] . '-' . $strings[1] . '-' . '01' . '-' . $strings[3];
}
else{
echo $strings[0] . '-' . $strings[1] . '-' . '01' . '-' . $strings[3] . '-' . $strings[4];    
}

?>");
} 

if(jQuery("#il").val() == 2) {
window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?pid=boxsearch&page=filedetails&id=<?php

if(count($strings) == 4) {
echo $strings[0] . '-' . $strings[1] . '-' . '02' . '-' . $strings[3];
}
else{
echo $strings[0] . '-' . $strings[1] . '-' . '02' . '-' . $strings[3] . '-' . $strings[4];    
}
?>");
}
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
