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
$selected_doc_ids = implode(",",$doc_id);

// Variables for LanID
$folderdocid_string = $_POST['postvarsfolderdocid'];
$page_id = $_POST['postvarpage'];
$pid = $_POST['pid'];
$box_id = $_POST['boxid'];
        
ob_start();
		foreach ($doc_id as $each_doc_id) {
            //START REVIEW
            $folderfile_details = $wpdb->get_row("SELECT 
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
			a.folder_identifier as folder_identifier,
			a.addressee as addressee,
			a.folderdocinfofile_id as folderdocinfofile_id,
            a.description,
            a.tags,
            a.access_restriction,
            a.specific_access_restriction,
            a.use_restriction,
            a.specific_use_restriction,
            a.rights_holder,
            a.source_dimensions,
            a.program_area,
            a.lan_id as lan_id
			
            FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
            WHERE a.id = '" . $each_doc_id . "'");
            
            $folderfile_id = $folderfile_details->id;
			$folderfile_folderdocinfofile_id = $folderfile_details->folderdocinfofile_id;
			$folderfile_boxid = $folderfile_details->box_id;
			$folderfile_il = $folderfile_details->index_level;
			$folderfile_title = $folderfile_details->title;
			$folderfile_date = $folderfile_details->date;
			$folderfile_author = $folderfile_details->author;
			$folderfile_addressee = $folderfile_details->addressee;
			$folderfile_site_name = $folderfile_details->site_name;
			$folderfile_site_id = $folderfile_details->site_id;
			$folderfile_close_date = $folderfile_details->close_date;
			$folderfile_source_format = $folderfile_details->source_format;
			$folderfile_file_location = $folderfile_details->file_location;
			$folderfile_file_name = $folderfile_details->file_name;
			$folderfile_essential_record = $folderfile_details->essential_record;
			$folderfile_identifier = $folderfile_details->folder_identifier;
			$folderfile_description = $folderfile_details->description;
            $folderfile_tags = $folderfile_details->tags;
            $folderfile_access_restriction = $folderfile_details->access_restriction;
            $folderfile_specific_access_restriction = $folderfile_details->specific_access_restriction;
            $folderfile_use_restriction = $folderfile_details->use_restriction;
            $folderfile_specific_use_restriction = $folderfile_details->specific_use_restriction;
            $folderfile_rights_holder = $folderfile_details->rights_holder;
            $folderfile_source_dimensions = $folderfile_details->source_dimensions;
            $folderfile_program_area = $folderfile_details->program_area;
          	$folderfile_lan_id = $folderfile_details->lan_id;
            //END REVIEW
        }
?>

<!-- All inputs/dropdowns/datalists should be the same width -->

<style>
.datalist::-webkit-calendar-picker-indicator {
  display: none;
}
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
  
.bootstrap-iso .lbl-creation-date {
  font-weight: 400; 
  margin: 0;
}
</style>

<form>

<?php
//placeholders with 'Enter...' only appear if that field is empty in the database, otherwise show current data

echo "<strong>LAN ID:</strong><br /><input type='text' id='lanid' placeholder= 'Enter a LAN ID...'><br/><br/>";

if(!empty($folderfile_title)) {
    echo "<strong>Title</strong><br /><input type='text' id='title' aria-label='Title' placeholder= ''></br></br>";
}
else {
    echo "<strong>Title</strong><br /><input type='text' id='title' aria-label='Title' placeholder= 'Enter title...'></br></br>";
}


if(!empty($folderfile_date)) {
    echo "<strong>Creation Date</strong> 
    	<!--<a href='#' data-toggle='tooltip' data-placement='right' data-html='true' title='Leaving the creation date field blank will show the date as 00/00/0000' aria-label='Request Help'><i class='far fa-question-circle' aria-hidden='true' title='Help'></i><span class='sr-only'>Help</span></a>-->
    	<br />
        <input type='checkbox' id='default-date' name='default-date' aria-label='Default Date' placeholder= '' style='margin-bottom: 4px;' >
        <label for='default-date' class='lbl-creation-date'>Checking this box will show the date as 00/00/0000</label>
    	<br />
        <input type='date' id='date' aria-label='Creation Date' placeholder= '' >
        </br></br>";
}
else {
    echo "<strong>Creation Date</strong> 
    	<!--<a href='#' data-toggle='tooltip' data-placement='right' data-html='true' title='Leaving the creation date field blank will show the date as 00/00/0000' aria-label='Request Help'><i class='far fa-question-circle' aria-hidden='true' title='Help'></i><span class='sr-only'>Help</span></a>-->
    	<br />
        <input type='checkbox' id='default-date' name='default-date' aria-label='Default Date' placeholder= '' style='margin-bottom: 4px;' >
        <label for='default-date' class='lbl-creation-date'>Checking this box will show the date as 00/00/0000</label>
        <br />
        <input type='date' id='date' aria-label='Creation Date' placeholder= 'mm/dd/yyyy' >
        </br></br>";
}
?>

<strong>Creator</strong><br />
<input type='text' name='author' value='' aria-label='Creator' id='author' class='tags'>
<br />


<?php
/*if(!empty($folderfile_site_name)) {
    echo "<strong>Site Name</strong><br /><input type='text' id='site_name' aria-label='Site Name' placeholder= ''></br></br>";
}
else {
    echo "<strong>Site Name</strong><br /><input type='text' id='site_name' aria-label='Site Name' placeholder= 'Enter site name...'></br></br>";
}

if(!empty($folderfile_site_id)) {
    echo "<strong>Site ID</strong><br /><input type='text' id='site_id'aria-label='Site ID'  placeholder= ''></br></br>";
}
else {
    echo "<strong>Site ID</strong><br /><input type='text' id='site_id' aria-label='Site ID' placeholder= 'Enter site ID...'></br></br>";
}*/
  
?>

<!-- START REVIEW -->
<input type="hidden" id="pattdocid" name="pattdocid" value="<?php echo $folderfile_id; ?>">
<input type="hidden" id="folderdocinfofile_id" name="folderdocinfofile_id" value="<?php echo $folderfile_folderdocinfofile_id; ?>">
<input type="hidden" id="folderdocinfofile_title" name="folderdocinfofile_title" value="<?php echo $folderfile_title; ?>">
<input type="hidden" id="folderfile_date" name="folderfile_date" value="<?php echo $folderfile_date; ?>">
<input type="hidden" id="doc_id_array" name="doc_id_array" value="<?php echo $selected_doc_ids; ?>">
<!-- END REVIEW -->
  
<!-- LanID Fields -->  
<input type="hidden" id="folderdocid" name="folderdocid" value="<?php echo $folderdocid_string; ?>">
<input type="hidden" id="boxid" name="boxid" value="<?php echo $box_id; ?>">
<input type="hidden" id="pageid" name="pageid" value="<?php echo $page_id; ?>"> 
<input type="hidden" id="pid" name="pid" value="<?php echo $pid; ?>">
<input type="hidden" id="folderfile_lan_id" name="folderfile_lan_id" value="<?php echo $folderfile_lan_id; ?>"> 
</form>
<?php 
$body = ob_get_clean();
ob_start();
?>

<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wpsc_edit_folder_file_details();"><?php _e('Save','supportcandy');?></button>

<script>

jQuery('[data-toggle="tooltip"]').tooltip(); 
  
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
 
  
jQuery("#default-date").click(function(){
  if(jQuery("#default-date").is(":checked") == true){
    jQuery("#date").hide();
  } else {
    jQuery("#date").show();
  }
});
  
jQuery("#date").change(function(){
  	if(jQuery("#date").val() != ''){
      jQuery("#default-date").hide();
      jQuery(".lbl-creation-date").hide();
    } else {
      jQuery("#default-date").show();
      jQuery(".lbl-creation-date").show();
    }  
});

jQuery("#date").keypress(function(){
    if(jQuery("#date").val() != ''){
      jQuery("#default-date").hide();
      jQuery(".lbl-creation-date").hide();
    } else {
      jQuery("#default-date").show();
      jQuery(".lbl-creation-date").show();
    }
});
  
  console.log(jQuery("#folderdocinfofile_title").val());
  
  
  
function wpsc_edit_folder_file_details(){
  var creationDate = "";
  var title = "";
  var lanID = "";
  
  
  if(jQuery("#title").val() == '' || jQuery("#title").val() == undefined) {
    title = jQuery("#folderdocinfofile_title").val();
  } else if(jQuery("#title").val() != '' || jQuery("#title").val() != undefined){
    title = jQuery("#title").val();
  } 
  
  if(jQuery("#lanid").val() == '' || jQuery("#lanid").val() == undefined) {
    lanID = jQuery("#folderfile_lan_id").val();
  }
  else if(jQuery("#lanid").val() != '' || jQuery("#lanid").val() != undefined){
    lanID = jQuery("#lanid").val();
  } 
  
  
  if((jQuery("#date").val() == '' || jQuery("#date").val() == undefined) && jQuery("#default-date").is(":checked") != true){
    creationDate = jQuery("#folderfile_date").val();
  } 
  else if(jQuery("#default-date").is(":checked") == true){
    creationDate = "0001-01-01";
  } 
  else if(jQuery("#date").val() != '' || jQuery("#date").val() != undefined){
    creationDate = jQuery("#date").val();
  } 

  
  //console.log('creation date checkbox ' + jQuery("#default-date").checked);
  console.log('title ' +  title );
  console.log('lan id ' + lanID);
  console.log('creation date ' + creationDate);
  
		   /*jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_folder_file_details.php',{
//START REVIEW
docidarray: jQuery("#doc_id_array").val(),
postvarspdid: jQuery("#pattdocid").val(),
postvarsfdiid: jQuery("#folderdocinfofile_id").val(),
postvarsil: jQuery("#il").val(),
postvarstitle: title,
postvarsdate: creationDate,
postvarsauthor: jQuery("#author").val(),
postvarsrt: jQuery("#record_type").val(),
postvarssn: jQuery("#site_name").val(),
postvarssid: jQuery("#site_id").val(),
postvarscd: jQuery("#close_date").val(),
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
postvarssourcedimensions: jQuery("#source_dimensions").val(),
postvarsprogramarea: jQuery("#program_area").val(),
//END REVIEW

// LanID Post Variables
postvarsfolderdocid: jQuery("#folderdocinfofile_id").val(),       
postvarsboxid: jQuery("#boxid").val(),
postvarslanid: lanID
}, 

   function (response) {
      //if(!alert(response)){window.location.reload();}
     console.log('the response ' + JSON.stringify(response));
     //return;
	window.location.reload(true);
     //window.location.href = window.location.href;
   });*/
}

</script>
<?php 
$footer = ob_get_clean();

$output = array(
  'body'   => $body,
  'footer' => $footer
);
echo json_encode($output);
