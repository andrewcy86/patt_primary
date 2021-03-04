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
			b.record_type as record_type,
			a.site_name as site_name,
			a.siteid as site_id,
			a.close_date as close_date,
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
            b.specific_access_restriction,
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
			$folderfile_addressee = $folderfile_details->addressee;
			//$folderfile_record_type = $folderfile_details->record_type;
			$folderfile_site_name = $folderfile_details->site_name;
			$folderfile_site_id = $folderfile_details->site_id;
			$folderfile_close_date = $folderfile_details->close_date;
			//$folderfile_access_type = $folderfile_details->access_type;
			$folderfile_source_format = $folderfile_details->source_format;
			//$folderfile_rights = $folderfile_details->rights;
			//$folderfile_contract_number = $folderfile_details->contract_number;
			//$folderfile_grant_number = $folderfile_details->grant_number;
			$folderfile_file_location = $folderfile_details->file_location;
			$folderfile_file_name = $folderfile_details->file_name;
			$folderfile_essential_record = $folderfile_details->essential_record;
			$folderfile_identifier = $folderfile_details->folder_identifier;
			
			$folderfile_folderdocinfofile_id = $folderfile_details->folderdocinfofile_id;
			$folderdocinfofileid = $folderfile_details->folderdocinfofileid;
			
			$folderfile_description = $folderfile_details->description;
            $folderfile_tags = $folderfile_details->tags;
            $folderfile_access_restriction = $folderfile_details->access_restriction;
            $folderfile_specific_access_restriction = $folderfile_details->specific_access_restriction;
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

?>
<strong>Record Type:</strong></br>
<input type="search" list="RecordTypeList" placeholder='Enter record type...' id='record_type'/>
    <datalist id = 'RecordTypeList'>
        <option value='Administrative Record Index'></option>
        <option value='Agreement'></option>
        <option value='Analytical Data Document'></option>
        <option value='Chart / Table'></option>
        <option value='Contract'></option>
        <option value='Contract Documentation'></option>
        <option value='Correspondence'></option>
        <option value='Document Packet'></option>
        <option value='Email'></option>
        <option value='Figure / Map / Drawing'></option>
        <option value='Financial Documentation'></option>
        <option value='Form'></option>
        <option value='Laws / Regulations / Guidance'></option>
        <option value='Legal Instrument'></option>
        <option value='Letter'></option>
        <option value='List / Index'></option>
        <option value='Meeting Document'></option>
        <option value='Memo'></option>
        <option value='Memorandum'></option>
        <option value='Notes'></option>
        <option value='Other'></option>
        <option value='Photograph'></option>        
        <option value='Publication'></option>        
        <option value='Record of Communication'></option>        
        <option value='Report'></option>
        <option value='Shipping Manifest'></option>
        <option value='System Documentation'></option>
        <option value='Work Plan'></option>
    </datalist>
</br></br>

<?php
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

//source format is now a datalist
?>
<strong>Source Type:</strong></br>
<input type="search" list="SourceFormatList" placeholder='Enter source type...' id='sf'/>
    <datalist id = 'SourceFormatList'>
        <option value='4K'></option>
        <option value='Audio'></option>
        <option value='AVI'></option>
        <option value='Blu-ray'></option>
        <option value='Bound Book'></option>
        <option value='CD [includes mini CD]'></option>
        <option value='CSV'></option>
        <option value='DOC'></option>
        <option value='DOCX'></option>
        <option value='DVD [includes mini DVD]'></option>
        <option value='Electronic File [Audio, visual, zip, etc.]'></option>
        <option value='E-Mail'></option>
        <option value='Floppy Disk [3.5" or 5"]'></option>
        <option value='GIF'></option>
        <option value='Hard Drive'></option>
        <option value='HTM'></option>
        <option value='HTML'></option>
        <option value='JPG'></option>
        <option value='LaserDisc'></option>
        <option value='MDB'></option>
        <option value='MOV'></option>
        <option value='MPG'></option>        
        <option value='Mylar'></option>        
        <option value='Negatives [includes microfiche]'></option>        
        <option value='Oversize'></option>
        <option value='Paper'></option>
        <option value='PDF'></option>
        <option value='Photos'></option>
        <option value='PNG'></option>
        <option value='PPT'></option>
        <option value='PPTX'></option>
        <option value='RTF'></option>
        <option value='Thumb Drive'></option>
        <option value='TIF'></option>
        <option value='TXT'></option>
        <option value='UHD'></option>        
        <option value='VHS'></option>        
        <option value='Vinyl record'></option>
        <option value='VSD'></option>
        <option value='WAV'></option>
        <option value='WMV'></option>
        <option value='WPD'></option>
        <option value='XLS'></option>
        <option value='XLSM'></option>
        <option value='XLSX'></option>
        <option value='XML'></option>
        <option value='ZIP'></option>
        <option value='ZIP Drive'></option>
        <option value='ZIPX'></option>
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
?>

<strong>Access Restriction:</strong><br />
<select id="access_restriction" name="access_restriction">
  <option value="Yes" <?php if ($folderfile_access_restriction == 'Yes' || $folderfile_use_restriction == 'yes') echo 'selected' ; ?>>Yes</option>
  <option value="No" <?php if ($folderfile_access_restriction == 'No' || $folderfile_use_restriction == 'no') echo 'selected' ; ?>>No</option>
</select></br>

<br><strong>Specific Access Restriction:</strong><br />
<select id="specific_access_restriction" name="specific_access_restriction">
  <option value="Controlled / Copyright" <?php if ($folderfile_specific_access_restriction == 'Controlled / Copyright') echo 'selected' ; ?>>Controlled / Copyright</option>
  <option value="Controlled / Critical Infrastructure - Water Assessments" <?php if ($folderfile_specific_access_restriction == 'Controlled / Critical Infrastructure - Water Assessments') echo 'selected' ; ?>>Controlled / Critical Infrastructure - Water Assessments</option>
  <option value="Controlled / General Law Enforcement" <?php if ($folderfile_specific_access_restriction == 'Controlled / General Law Enforcement') echo 'selected' ; ?>>Controlled / General Law Enforcement</option>
  <option value="Controlled / Legal-Administrative Proceedings" <?php if ($folderfile_specific_access_restriction == 'Controlled / Legal-Administrative Proceedings') echo 'selected' ; ?>>Controlled / Legal-Administrative Proceedings</option>
  <option value="Controlled / Legal-Privilege" <?php if ($folderfile_specific_access_restriction == 'Controlled / Legal-Privilege') echo 'selected' ; ?>>Controlled / Legal-Privilege</option>
  <option value="Controlled / Legal-Protective Order" <?php if ($folderfile_specific_access_restriction == 'Controlled / Legal-Protective Order') echo 'selected' ; ?>>Controlled / Legal-Protective Order</option>
  <option value="Controlled / General Privacy" <?php if ($folderfile_specific_access_restriction == 'Controlled / General Privacy') echo 'selected' ; ?>>Controlled / General Privacy</option>
  <option value="Controlled / Privacy-Personnel" <?php if ($folderfile_specific_access_restriction == 'Controlled / Privacy-Personnel') echo 'selected' ; ?>>Controlled / Privacy-Personnel</option>
  <option value="Controlled / General Proprietary Business Information" <?php if ($folderfile_specific_access_restriction == 'Controlled / General Proprietary Business Information') echo 'selected' ; ?>>Controlled / General Proprietary Business Information</option>
  <option value="Controlled / Proprietary Business Information-Claimed" <?php if ($folderfile_specific_access_restriction == 'Controlled / Proprietary Business Information-Claimed') echo 'selected' ; ?>>Controlled / Proprietary Business Information-Claimed</option>
  <option value="Controlled-Undetermined" <?php if ($folderfile_specific_access_restriction == 'Controlled-Undetermined') echo 'selected' ; ?>>Controlled-Undetermined</option>
  <option value="Uncontrolled" <?php if ($folderfile_specific_access_restriction == 'Uncontrolled') echo 'selected' ; ?>>Uncontrolled</option>
</select></br>

<br><strong>Use Restriction:</strong><br />
<select id="use_restriction" name="use_restriction">
  <option value="Yes" <?php if ($folderfile_use_restriction == 'Yes' || $folderfile_use_restriction == 'yes') echo 'selected' ; ?>>Yes</option>
  <option value="No" <?php if ($folderfile_use_restriction == 'No' || $folderfile_use_restriction == 'no') echo 'selected' ; ?>>No</option>
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
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_folder_file_details.php',{
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
postvarssf: jQuery('#sf').val(),
postvarser: jQuery("#er").val(),
postvarsfi: jQuery("#folder_identifier").val(),
postvarsaddressee: jQuery("#addressee").val(),
postvarsdescription: jQuery("#description").val(),
postvarstags: jQuery("#tags").val(),
postvarsaccessrestriction: jQuery("#access_restriction").val(),
postvarsspecificaccessrestriction: jQuery("#specific_access_restriction").val(),
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
