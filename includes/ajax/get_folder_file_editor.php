<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

//include_once( WPPATT_UPLOADS . 'api_authorization_strings.php' );

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
        a.box_id as box_id,
        a.title as title,
        a.date as date,
        a.author as author,
        a.folderdocinfofile_id as folderdocinfofile_id,
        a.tags,
        a.lan_id as lan_id
        
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
        WHERE a.id = '" . $each_doc_id . "'");
        
        $folderfile_id = $folderfile_details->id;
        $folderfile_folderdocinfofile_id = $folderfile_details->folderdocinfofile_id;
        $folderfile_boxid = $folderfile_details->box_id;
        $folderfile_title = $folderfile_details->title;
        $folderfile_date = $folderfile_details->date;
        $folderfile_author = $folderfile_details->author;
        $folderfile_tags = $folderfile_details->tags;
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
echo "<strong>LAN ID:</strong><br /><input type='text' id='lanid' placeholder= 'Enter a LAN ID...'></br></br>";

if(!empty($folderfile_title)) {
    echo "<strong>Title</strong><br /><input type='text' id='title' aria-label='Title' placeholder= ''></br></br>";
}
else {
    echo "<strong>Title</strong><br /><input type='text' id='title' aria-label='Title' placeholder= 'Enter title...'></br></br>";
}


if(!empty($folderfile_date)) {
    echo "<strong>Creation Date</strong> 
    	<br />
        <input type='checkbox' id='default-date' name='default-date' aria-label='Default Date' placeholder= '' style='margin-bottom: 4px;' >
        <label for='default-date' class='lbl-creation-date'>Checking this box will show the date as 00/00/0000</label>
    	<br />
        <input type='date' id='date' aria-label='Creation Date' placeholder= '' >
        </br></br>";
}
else {
    echo "<strong>Creation Date</strong> 
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
<button type="button" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wpsc_edit_epa_contact();"><?php _e('Save','supportcandy');?></button>

<script>
  
jQuery('[data-toggle="tooltip"]').tooltip(); 

//Prevent page redirect on pressing enter in input field
jQuery(document).on('keypress',function(e) {
  if(e.which == 13) {
    //prevents page redirect on enter
    e.preventDefault();
    dataTable.state.save();
    dataTable.draw();
  }
});
  
//Setting placeholder text for multiselect dropdown
jQuery('.selectpicker').selectpicker({
  noneSelectedText : 'Please select...', // by this default 'Nothing selected' -->will change to Please Select...
  multipleSeparator: ';'
});

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
 

function wpsc_edit_epa_contact(){
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
  
  
  /*if((jQuery("#date").val() == '' || jQuery("#date").val() == undefined) && jQuery("#default-date").is(":checked") != true){
    creationDate = jQuery("#folderfile_date").val();
  } */
  if(jQuery("#default-date").is(":checked") == true){
    creationDate = "0001-01-01";
  } 
  else if(jQuery("#date").val() != '' || jQuery("#date").val() != undefined){
    creationDate = jQuery("#date").val();
  } 

  
  //console.log('creation date checkbox ' + jQuery("#default-date").checked);
  console.log('title ' +  jQuery("#title").val());
  console.log('lan id ' + jQuery("#lanid").val());
  console.log('creation date ' + creationDate);

	jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_folder_file_details.php',{
        //START REVIEW
        docidarray: jQuery("#doc_id_array").val(),
        postvarspdid: jQuery("#pattdocid").val(),
        postvarsfdiid: jQuery("#folderdocinfofile_id").val(),
        postvarstitle: jQuery("#title").val(),
        postvarsdate: creationDate,
        postvarsauthor: jQuery("#author").val(),
        //END REVIEW

        // LanID Post Variables
        postvarsfolderdocid: jQuery("#folderdocid").val(),       
        postvarsboxid: jQuery("#boxid").val(),
        postvarslanid: jQuery("#lanid").val()
    }, 
    function (response) {
      if(!alert(response)){
        window.location.reload();
      }

      window.location.reload();
        //window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?page=<?php echo $page_id; ?>&pid=<?php echo $pid;?>&id=<?php echo $box_id;?>")
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