<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// set default filter for agents and customers //not true
global $current_user, $wpscfunction;

if (!$current_user->ID) die();

$setting_action = isset($_POST['setting_action']) ? sanitize_text_field($_POST['setting_action']) : '';
$return_id = isset($_POST['return_id']) ? sanitize_text_field($_POST['return_id']) : '';
//$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '';

ob_start();

echo 'Are you sure you want to extend Decline ID: D-'.$return_id.'\'s Expiration Date to 30 days from today? This action cannot be undone.';
echo "<br>";

$body = ob_get_clean();


ob_start();
?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('No','wpsc-export-ticket');?></button>

<button type="button" id="button_extend_expiration_yes" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_extend_return();"><?php _e('Yes','supportcandy');?></button>

<script>

	function wppatt_extend_return() {
		console.log('extending ');
		var return_id = '<?php echo $return_id ?>';

		console.log('return id: '+return_id);
		console.log('ticket id: '+ticket_id);
		
		jQuery.post(
		   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_return_details.php',{
		    return_id: return_id,
		    type: 'extend_expiration',
		}, 
	    function (response) {
			console.log(response);
			window.location.reload();
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