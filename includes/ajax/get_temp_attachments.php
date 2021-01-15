<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

$subfolder_path = site_url( '', 'relative');
//echo 'subfolder_path';

if (!isset($_SESSION)) {
    session_start();    
}

$doc_id = $_POST["doc_id"];

ob_start();

$get_folderdocinfo_id = $wpdb->get_row("SELECT folderdocinfo_id
FROM wpqa_wpsc_epa_folderdocinfo
WHERE id = '" . $doc_id . "'");
$folderdocinfo_id = $get_folderdocinfo_id->folderdocinfo_id;
?>

<strong>Are you sure you want to upload these attachments to ECMS? Click save to continue.</strong>

<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wpsc_upload_temp_attachments();"><?php _e('Save','supportcandy');?></button>

<input type="hidden" id="folderdocinfo_id" name="folderdocinfo_id" value="<?php echo $folderdocinfo_id; ?>">

<script>
function wpsc_upload_temp_attachments() {
    jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_temp_attachments.php',{
       postvarsffid: jQuery("#folderdocinfo_id").val()
},
function (response) {
      if(!alert(response)){window.location.reload();}
       window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?pid=boxsearch&page=filedetails&id=<?php echo $folderdocinfo_id; ?>");
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