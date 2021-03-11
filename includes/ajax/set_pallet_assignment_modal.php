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

$agent_permissions = $wpscfunction->get_current_agent_permissions();

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
$pallet_count = 0;

	foreach( $item_ids as $id ) {

	$get_pallet_id = $wpdb->get_row(
 "SELECT pallet_id
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
        WHERE box_id = '" . $id . "'"
			);

	if(!empty($get_pallet_id->pallet_id)) {
	$pallet_count++;
	}
	
	}

?>

<?php  
if($pallet_count >=1) {
?>
<div class="alert alert-warning" id="pallet_warn" role="alert">
One or more pallets that have been selected already have assigned Pallet IDs.<br />
Selecting Assign Pallet ID will reset the physical location of the Pallet and reset the Pallet ID.
</div>
<?php 
}
?>
<div id='alert_status' class=''></div> 
<form>
<label for="set"><input type="radio" id="set" name="set_pallet" value="yes">
Assign Pallet ID</label><br />


<label for="reassign"><input type="radio" id="reassign" name="set_pallet" value="reassign">
Re-Assign to existing Pallet</label>

<span id="pallet_datalist">
<?php
//List of all of the pallet IDs in the database to choose from
$box_pallet_array = $wpdb->get_results("SELECT DISTINCT pallet_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE pallet_id <> ''");
$get_pallet_id = Patt_Custom_Func::get_pallet_id_by_id($patt_box_id, 'box');

if(count($box_pallet_array) > 0) {
?>
<br /><br />
<input type="search" list="PalletList" placeholder='Enter pallet ID...' id='pallet_id'/>
<datalist id = 'PalletList'>
<?php

foreach ( $box_pallet_array as $pallet ) {
$pallet_id = $pallet->pallet_id;

/*
if ($get_pallet_id == $pallet ) {
    $selected = 'selected'; 
} else {
    $selected = '';
}

echo '<option '.$selected.' value="'.$pallet_id.'">'.$pallet_id.'</option>';
*/
echo '<option value="'.$pallet_id.'">'.$pallet_id.'</option>';
}

}
?>
</datalist>
<br />
</span>

<?php 
 if (($agent_permissions['label'] == 'Administrator')  || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Agent')) {
?>

<br />
<label for="reset" style="color:red;"><input type="radio" id="reset" name="set_pallet" value="no">
Reset Pallet ID/Unassign Physical Location</label><br />

<?php 
}
?>

</form>
<?php

$body = ob_get_clean();

ob_start();

?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<button type="button" id="button_box_status_submit" class="btn wpsc_popup_action" style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_action_button_text_color']?> !important;" onclick="wppatt_set_pallet_assignment();"><?php _e('Save','supportcandy');?></button>

<script>


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

jQuery('#pallet_datalist').hide();
jQuery('#pallet_warn').hide();

jQuery("input[id$='reassign']").click(function() {
  jQuery('#pallet_datalist').show();
  jQuery('#alert_status').hide();
  jQuery('#pallet_warn').hide();
});

jQuery("input[id$='set']").click(function() {
  jQuery('#pallet_datalist').hide();
  jQuery('#alert_status').hide();
  jQuery('#pallet_warn').show();
});

jQuery("input[id$='reset']").click(function() {
  jQuery('#pallet_datalist').hide();
  jQuery('#pallet_warn').hide();
var restriction_reason = 'Warning this action will reset all location information.';
set_alert('danger', restriction_reason);
});


function wppatt_set_pallet_assignment(){
	let item_ids = <?php echo json_encode($item_ids); ?>;
	var pallet_action = jQuery('input[name="set_pallet"]:checked').val();
	
	console.log('setting pallet assignment for items: ');
	console.log(item_ids);

	jQuery.post(
	   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_pallet_assignment.php',{
	    item_ids: item_ids,
	    ticket_id: <?php echo $ticket_id; ?>,
	    pallet_action: pallet_action,
	    pallet_id: jQuery('#pallet_id').val()
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

