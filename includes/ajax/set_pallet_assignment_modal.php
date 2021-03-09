<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// set default filter for agents and customers //not true
/*
global $current_user, $wpscfunction;
if (!$current_user->ID) die();
*/

global $current_user, $wpscfunction, $wpdb;

if (!($current_user->ID && $current_user->has_cap('wpsc_agent'))) {
	exit;
}

$subfolder_path = site_url( '', 'relative'); 
$save_enabled = true;

//
// Get items
//
$ticket_id = $_REQUEST['ticket_id']; 
$item_ids = $_REQUEST['item_ids']; 
$num_of_items = count($item_ids);
$is_single_item = ($num_of_items == 1) ? true : false;

?>
<div id='alert_status' class=''></div> 
<form>
<input type="radio" id="set" name="set_pallet" value="yes" checked onclick="handleSet(this);">
<label for="set">Assign Pallet ID</label><br />
<input type="radio" id="reset" name="set_pallet" value="no" onclick="handleReset(this);">
<label for="reset" style="color:red;">Reset Pallet ID/Unassign Physical Location</label><br>
</form>
<?php

$body = ob_get_clean();

ob_start();

?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" id="button_box_status_submit" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_pallet_assignment();"><?php _e('Save','supportcandy');?></button>

<script>

function handleReset(set_pallet) {
var restriction_reason = 'Pallet IDs cannot be easily re-assigned. They must be re-assigned at the box level.';
set_alert('danger', restriction_reason);
}


// Sets the time for dismissing the error notification
function handleSet(set_pallet) {
    jQuery('#alert_status').hide();
}

function hashCode( str ) {
	var hash = 0;
    for (var i = 0; i < str.length; i++) {
        var character = str.charCodeAt(i);
        hash = ((hash<<5)-hash)+character;
        hash = hash & hash; // Convert to 32bit integer
    }
    return hash;
}

function set_alert( type, message ) {
	
	let alert_style = '';
	let hash = hashCode( message );
	console.log({hash:hash});
	
	switch( type ) {
		case 'success':
			alert_style = 'alert-success';		
			break;
		case 'warning':
			alert_style = 'alert-warning';
			break;
		case 'danger':
			alert_style = 'alert-danger';
			break;		
	}
	jQuery('#alert_status').show();
// 		jQuery('#alert_status').html('<div class=" alert '+alert_style+'">'+message+'</div>'); //badge badge-danger
	jQuery('#alert_status').html('<div id="alert-' + hash + '" class=" alert '+alert_style+'">'+message+'</div>'); //badge badge-danger
	jQuery('#alert_status').addClass('alert_spacing');
}

function wppatt_set_pallet_assignment(){
	let item_ids = <?php echo json_encode($item_ids); ?>;
	var pallet_action = jQuery('input[name="set_pallet"]:checked').val();
	
	console.log('setting pallet assignment for items: ');
	console.log(item_ids);

	jQuery.post(
	   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_pallet_assignment.php',{
	    item_ids: item_ids,
	    ticket_id: <?php echo $ticket_id; ?>,
	    pallet_action: pallet_action 
	}, 
    function (response) {
		//alert('updated: '+response);
		alert(response);
		console.log('The Response:');
		console.log(response);
		//window.location.reload(); 
		wpsc_open_ticket(<?php echo $ticket_id; ?>);
		
		jQuery('#tbl_templates_boxes').DataTable().ajax.reload();

    });

	wpsc_modal_close();
} 

</script>



<?php 
$footer = ob_get_clean();

$output = array(
  'body'   => $body,
  'footer' => $footer
);
echo json_encode($output);

