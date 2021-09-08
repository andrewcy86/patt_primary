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


$folderdocid_string = $_POST['postvarsfolderdocid'];
$page_id = $_POST['postvarpage'];
$pid = $_POST['pid'];
$box_id = $_POST['boxid'];

ob_start();

?>

<form>
<?php
//placeholders with 'Enter...' only appear if that field is empty in the database, otherwise show current data

echo "<strong>LAN ID:</strong><br /><input type='text' id='lanid' placeholder= 'Enter a LAN ID...'>";

?>
<input type="hidden" id="folderdocid" name="folderdocid" value="<?php echo $folderdocid_string; ?>">
<input type="hidden" id="boxid" name="boxid" value="<?php echo $box_id; ?>">
<input type="hidden" id="pageid" name="pageid" value="<?php echo $page_id; ?>"> 
<input type="hidden" id="pid" name="pid" value="<?php echo $pid; ?>"> 
</form>
<?php 
$body = ob_get_clean();
ob_start();
?>

<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wpsc_edit_epa_contact();"><?php _e('Save','supportcandy');?></button>

<script>

//Prevent page redirect on pressing enter in input field
jQuery(document).on('keypress',function(e) {
		if(e.which == 13) {
		    //prevents page redirect on enter
		    e.preventDefault();
			dataTable.state.save();
			dataTable.draw();
		}
	});

function wpsc_edit_epa_contact(){
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_epa_contact.php',{
postvarsfolderdocid: jQuery("#folderdocid").val(),       
postvarsboxid: jQuery("#boxid").val(),
postvarslanid: jQuery("#lanid").val()
}, 
function (response) {
      if(!alert(response)){window.location.reload();}
        window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?page=<?php echo $page_id; ?>&pid=<?php echo $pid;?>&id=<?php echo $box_id;?>")
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