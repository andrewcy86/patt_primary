<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

if (!isset($_SESSION)) {
    session_start();    
}

$subfolder_path = site_url( '', 'relative'); 

$box_ids = strip_tags($_POST["postvarsboxid"]);

//$box_id  = isset($_POST['box_id']) ? sanitize_text_field($_POST['box_id']) : '' ;
        
ob_start();


$box_loc_array = array();

$boxes = explode(",", $box_ids);

foreach($boxes as $items) {

$box_details = $wpdb->get_row(
"select Reader_Name from " . $wpdb->prefix . "wpsc_epa_rfid_data WHERE box_id = '".$items."'"
			);
//echo $box_details->Reader_Name;
array_push($box_loc_array,$box_details->Reader_Name);
}

//print_r($box_loc_array);

$results = array_unique($box_loc_array);
if(count($results) == 1){

echo '<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_destruction_completed_btn" onclick="wppatt_barcode_assignment_update();" style="background-color:#337ab7 !important;color:#FFFFFF !important;"><i class="fas fa-map-marker-alt"></i> Set location to: '.$box_loc_array[0].'</button>';
echo '<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="wppatt_remove_rfid_boxes();" style="background-color:#e31c3d !important;color:#FFFFFF !important; margin-right: 30px !important;"><i class="far fa-trash-alt"></i> Remove Selected Box(es) From List</button>';
} else {

echo '<span style="color: red;"><strong>Location cannot be set: Multiple locations have been selected.</strong></span><br /><br />';

   
}

?>
<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_individual_refresh_btn" onclick="window.open('<?php echo WPPATT_PLUGIN_URL; ?>includes/ajax/pdf/box_label.php?id=<?php echo $box_ids; ?>');" style="background-color:#337ab7 !important;color:#FFFFFF !important;"><i class="fas fa-tags"></i> Regenerate Box Labels</button>
<?php
$body = ob_get_clean();
ob_start();
?>

<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();window.location.reload();"><?php _e('Close','wpsc-export-ticket');?></button>
 
<script>

jQuery(document).ready(function() {

jQuery('#dialog').dialog({
       autoOpen: false,
        height: "auto",
        width: 350,
        modal: false,
        closeText: '',
        position: {
            my: "center",
            at: "center",
            of: window
        },
          close: function(event, ui) {
          location.reload();
     }
});

jQuery('#dialog_warn').dialog({
       autoOpen: false,
        height: "auto",
        width: 350,
        modal: false,
        closeText: '',
        position: {
            my: "center",
            at: "center",
            of: window
        }
});

});

function wppatt_barcode_assignment_update(){		
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_barcode_assignment.php',{
postvarslocation: '<?php echo $box_loc_array[0]; ?>',
postvarsboxpallet: '<?php echo $box_ids; ?>',
postvarsuser: '<?php echo $current_user->display_name; ?>',
postvarspage: 'rfid'
}, 
   function (response) {
wpsc_modal_close();
jQuery('.ui-dialog-title').text('Details');
jQuery('.ui-dialog-content').html(response);

jQuery('#dialog').dialog('open');

//if(!alert(response)){window.location.reload();}
//$('.ui-dialog-title').text('Details');
//$('.ui-dialog-content').html(response);

//$('#dialog').dialog('open');

//alert(response);
//window.location.reload();
   });
}


function wppatt_remove_rfid_boxes(){		
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/remove_rfid_boxes.php',{
postvarslocation: '<?php echo $box_loc_array[0]; ?>',
postvarsboxpallet: '<?php echo $box_ids; ?>'
}, 
   function (response) {
      if(!alert(response)){window.location.reload();}
      window.location.replace("<?php echo $subfolder_path; ?>/wp-admin/admin.php?page=rfid&reader=<?php echo $box_loc_array[0]; ?>");
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