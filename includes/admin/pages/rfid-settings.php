<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

//$GLOBALS['id'] = $_GET['id'];
$subfolder_path = site_url( '', 'relative'); 

$agent_permissions = $wpscfunction->get_current_agent_permissions();

//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';

?>

<div class="bootstrap-iso">
  
  <h3>RFID Settings</h3>

 <div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important; padding:10px 0 0 10px; !important;">

<?php
$rfid_settings_init_array= $wpscfunction->get_ticket_meta(0,'rfid_settings_locations');

$rfid_settings_array = explode(',', $rfid_settings_init_array[0]);
?>

<label for="rfidlocations">Provide a comma delimited list of check-in locations with RFID readers<br /> (e.g. SCN-01-E,SCN-02-E,SCN-01-W,SCN-02-W):</label>
<br />

<textarea id="rfidlocations" name="rfidlocations" rows="4" cols="50"><?php echo $rfid_settings_init_array[0]; ?></textarea><br />
<button type="button" id="button_box_status_submit" class="btn wpsc_popup_action" style="" onclick="wppatt_set_rfid_settings();">Save</button>
<br />
<br />
<strong>Active RFID Dashboards (bookmark these locations):</strong><br /><br />
<ul>
<?php
foreach ($rfid_settings_array as $items) {
echo '<li><strong>'.$items.' - </strong><a href="'.WP_SITEURL.'/wp-admin/admin.php?page=rfid&reader='.$items.'" target="_blank">'.WP_SITEURL.'/wp-admin/admin.php?page=rfid&reader='.$items.'</a></li>';
}
?>
</ul>
 </div>

</div>
</div>

<script>
function wppatt_set_rfid_settings(){		
		   jQuery.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_rfid_settings.php',{
postvarrfidsettings: jQuery('#rfidlocations').val()
}, 
   function (response) {
      if(!alert(response)){window.location.reload();}
   });
}
</script>