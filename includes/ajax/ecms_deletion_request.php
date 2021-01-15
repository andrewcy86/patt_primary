<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;
$subfolder_path = site_url( '', 'relative'); 

if (!isset($_SESSION)) {
    session_start();    
}


ob_start();

$dbid_string = $_POST['attach_id'];
$folderdoc_id = $_POST['folderdoc_id'];
$dbid_arr = explode (",", $dbid_string);

?>

<?php
$number = 0;
foreach($dbid_arr as $key => $value):
$get_delete_flag = $wpdb->get_row("SELECT ecms_delete_timestamp, ecms_delete_comment FROM wpqa_wpsc_epa_folderdocinfo_files WHERE id = '".$value."'");
$attach_ecms_timestamp = $get_delete_flag->ecms_delete_timestamp;
$attach_ecms_comment = $get_delete_flag->ecms_delete_comment;
 if(strtotime($attach_ecms_timestamp) > 0 && $attach_ecms_comment != ''){
$number++;
 }
endforeach;

if($number == 0) {
?>
<h4>Enter Comment Information. Notice this action can't be undone!</h4>
<form>
<textarea style="width: 100%; max-width: 100%; display: inline-block;" name="ecms_comment" id="ecms_comment" placeholder="Enter ECMS deletion request reason here..."></textarea>
</form>
<?php
}else{
echo "You have selected one or more attachments that has already be flagged for deletion.";    
}
$body = ob_get_clean();
ob_start();
?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wpsc_update_ecms_delete();">Confirm</button>
<script>
function wpsc_update_ecms_delete(){		
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_ecms_delete.php',{
postvarscomment: jQuery("#ecms_comment").val(),
postvarsattachid: <?php echo $dbid_string; ?>,
postvarsfolderfileid: <?php echo $folderdoc_id; ?>
}, 
   function (response) {
      if(!alert(response)){window.location.reload();}
      window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?pid=docsearch&page=filedetails&id=<?php echo $folderdoc_id; ?>");
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