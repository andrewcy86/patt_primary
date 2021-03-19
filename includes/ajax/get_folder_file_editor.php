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

<!-- All inputs/dropdowns/datalists should be the same width -->

<style>

select {
    width: 400px;
    height: 33px;
}

input {
    width: 400px;
    height: 33px;
}

.bootstrap-select {
  width: 400px !important;
}

/*
Changes button styling for multiselect dropdown
*/
.bootstrap-select .btn {
  
  font-weight: 200;
  padding: 12px 12px;
  /*margin-bottom: 10px;*/
}

/*
Hides specific_access_restriction and specific_use_restriction when option == 'No'
*/
#hidden_div {
    display: none;
}

#hidden_div_2 {
    display: none;
}
</style>

<form>
<strong>Index Level</strong><br />
<select id="il" name="il">
  <option value="1" <?php if ($folderfile_il == 1 ) echo 'selected' ; ?>>Folder</option>
  <option value="2" <?php if ($folderfile_il == 2 ) echo 'selected' ; ?>>File</option>
</select></br></br>

<?php
//}
//placeholders with 'Enter...' only appear if that field is empty in the database, otherwise show current data

if(!empty($folderfile_identifier)) {
    echo "<strong>Folder Identifier</strong><br /><input type='text' id='folder_identifier' placeholder= '$folderfile_identifier'><br /><br />";
}
else {
    echo "<strong>Folder Identifier</strong><br /><input type='text' id='folder_identifier' placeholder= 'Enter folder identifier...'><br /><br />";
}

if(!empty($folderfile_title)) {
    echo "<strong>Title</strong><br /><input type='text' id='title' placeholder= '$folderfile_title'></br></br>";
}
else {
    echo "<strong>Title</strong><br /><input type='text' id='title' placeholder= 'Enter title...'></br></br>";
}

if(!empty($folderfile_description)) {
    echo "<strong>Description</strong><br /><input type='text' id='description' placeholder= '$folderfile_description'><br /><br />";
}
else {
    echo "<strong>Description</strong><br /><input type='text' id='description' placeholder= 'Enter description...'><br /><br />";
}

if(!empty($folderfile_date)) {
    echo "<strong>Creation Date</strong><br /><input type='date' id='date' placeholder= '$folderfile_date'></br></br>";
}
else {
    echo "<strong>Creation Date</strong><br /><input type='date' id='date' placeholder= 'mm/dd/yyyy'></br></br>";
}
?>

<strong>Creator</strong><br />
<input type='text' name='author' value='' id='author' class='tags'>
</br>

<strong>Addressee</strong><br />
<input type='text' name='addressee' value="" id='addressee' class='tags'>
</br>

<strong>Record Type</strong></br>
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
    echo "<strong>Site Name</strong><br /><input type='text' id='site_name' placeholder= '$folderfile_site_name'></br></br>";
}
else {
    echo "<strong>Site Name</strong><br /><input type='text' id='site_name' placeholder= 'Enter site name...'></br></br>";
}

if(!empty($folderfile_site_id)) {
    echo "<strong>Site ID</strong><br /><input type='text' id='site_id' placeholder= '$folderfile_site_id'></br></br>";
}
else {
    echo "<strong>Site ID</strong><br /><input type='text' id='site_id' placeholder= 'Enter site ID...'></br></br>";
}

if(!empty($folderfile_close_date)) {
    echo "<strong>Close Date</strong><br /><input type='date' id='close_date' placeholder= '$folderfile_close_date'></br></br>";
}
else {
    echo "<strong>Close Date</strong><br /><input type='date' id='close_date' placeholder= 'Enter close date...'></br></br>";
}
?>

<strong>Access Restriction</strong><br />
<!--<select id="access_restriction" name="access_restriction" onclick="showDiv('hidden_div', this)">-->
  <select id="access_restriction" name="access_restriction">
  <option value="Yes" <?php if ($folderfile_access_restriction == 'Yes' || $folderfile_use_restriction == 'yes') echo 'selected' ; ?>>Yes</option>
  <option value="No" <?php if ($folderfile_access_restriction == 'No' || $folderfile_use_restriction == 'no') echo 'selected' ; ?>>No</option>
</select></br>

<!--<div id="hidden_div">-->
<br><strong>Specific Access Restriction</strong><br />
<select id="specific_access_restriction" class="selectpicker" multiple data-live-search="true" data-none-selected-text>
  <option value="Controlled / Copyright">Controlled / Copyright</option>
  <option value="Controlled / Critical Infrastructure - Water Assessments">Controlled / Critical Infrastructure - Water Assessments</option>
  <option value="Controlled / General Law Enforcement">Controlled / General Law Enforcement</option>
  <option value="Controlled / Legal-Administrative Proceedings">Controlled / Legal-Administrative Proceedings</option>
  <option value="Controlled / Legal-Privilege">Controlled / Legal-Privilege</option>
  <option value="Controlled / Legal-Protective Order">Controlled / Legal-Protective Order</option>
  <option value="Controlled / General Privacy">Controlled / General Privacy</option>
  <option value="Controlled / Privacy-Personnel">Controlled / Privacy-Personnel</option>
  <option value="Controlled / General Proprietary Business Information">Controlled / General Proprietary Business Information</option>
  <option value="Controlled / Proprietary Business Information-Claimed">Controlled / Proprietary Business Information-Claimed</option>
  <option value="Controlled-Undetermined">Controlled-Undetermined</option>
  <option value="Uncontrolled">Uncontrolled</option>
</select></br>
<!--</div>-->

<br><strong>Use Restriction</strong><br />
<!--<select id="use_restriction" name="use_restriction" onclick="showDiv('hidden_div_2', this)">-->
<select id="use_restriction" name="use_restriction">
  <option value="Yes" <?php if ($folderfile_use_restriction == 'Yes' || $folderfile_use_restriction == 'yes') echo 'selected' ; ?>>Yes</option>
  <option value="No" <?php if ($folderfile_use_restriction == 'No' || $folderfile_use_restriction == 'no') echo 'selected' ; ?>>No</option>
</select></br>

<!--<div id="hidden_div_2">-->
<br><strong>Specific Use Restriction</strong><br />
<select id="specific_use_restriction" class="selectpicker" multiple data-live-search="true" data-none-selected-text>
  <option value="Controlled / Copyright">Controlled / Copyright</option>
  <option value="Controlled / Critical Infrastructure - Water Assessments">Controlled / Critical Infrastructure - Water Assessments</option>
  <option value="Controlled / General Law Enforcement">Controlled / General Law Enforcement</option>
  <option value="Controlled / Legal-Administrative Proceedings">Controlled / Legal-Administrative Proceedings</option>
  <option value="Controlled / Legal-Privilege">Controlled / Legal-Privilege</option>
  <option value="Controlled / Legal-Protective Order">Controlled / Legal-Protective Order</option>
  <option value="Controlled / General Privacy">Controlled / General Privacy</option>
  <option value="Controlled / Privacy-Personnel">Controlled / Privacy-Personnel</option>
  <option value="Controlled / General Proprietary Business Information">Controlled / General Proprietary Business Information</option>
  <option value="Controlled / Proprietary Business Information-Claimed">Controlled / Proprietary Business Information-Claimed</option>
  <option value="Controlled-Undetermined">Controlled-Undetermined</option>
  <option value="Uncontrolled">Uncontrolled</option>
</select></br>
<!--</div>-->
</br>

<strong>Rights Holder</strong><br />
<input type='text' name='rights_holder' value="" id='rights_holder' class='tags'>
</br>

<strong>Source Type</strong></br>
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

if(!empty($folderfile_source_dimensions)) {
    echo "<strong>Source Dimensions</strong><br /><input type='text' id='source_dimensions' placeholder= '$folderfile_source_dimensions'><br />";
}
else {
    echo "<strong>Source Dimensions</strong><br /><input type='text' id='source_dimensions' placeholder= 'Enter source dimensions...'><br />";
}
?>
<br><strong>Essential Record</strong><br />
<select id="er" name="er">
  <option value="1" <?php if ($folderfile_essential_record == 1 ) echo 'selected' ; ?>>Yes</option>
  <option value="0" <?php if ($folderfile_essential_record == 0) echo 'selected' ; ?>>No</option>
</select></br></br>

<strong>Tags</strong><br />
<input type='text' name='tags' class='tags' value="" id='tags'>
</br>

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

//Setting placeholder text for multiselect dropdown
jQuery('.selectpicker').selectpicker({
        noneSelectedText : 'Please select...', // by this default 'Nothing selected' -->will change to Please Select...
        multipleSeparator: ';'
    });

//Hides specific access restriction and specific use destriction on default, unless choice of 'Yes' is chosen
function showDiv(divId, element)
{
    document.getElementById(divId).style.display = element.value == 'Yes' ? 'block' : 'none';
}

//Setting delimiter semicolon for author, addressee and rights holder
jQuery("#author,#addressee,#rights_holder").tagsInput({
   'height':'33px',
   'width':'400px',
   'interactive':true,
   'defaultText':'',
   'delimiter': ';',   // Or a string with a single delimiter. Ex: ';'
   'removeWithBackspace' : true,
   'minChars' : 0,
   'maxChars' : 0, // if not provided there is no limit
   'placeholderColor' : '#666666'
});

//Setting delimiter comma for tags
jQuery('#tags').tagsInput({
   'height':'33px',
   'width':'400px',
   'interactive':true,
   'defaultText':'',
   'delimiter': ',',   // Or a string with a single delimiter. Ex: ';'
   'removeWithBackspace' : true,
   'minChars' : 0,
   'maxChars' : 0, // if not provided there is no limit
   'placeholderColor' : '#666666'
});

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
postvarsspecificaccessrestriction: jQuery('[data-id="specific_access_restriction"]').attr('title'),
postvarsuserestriction: jQuery("#use_restriction").val(),
postvarsspecificuserestriction: jQuery('[data-id="specific_use_restriction"]').attr('title'),
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
