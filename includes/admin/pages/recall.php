<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;



//$GLOBALS['id'] = $_GET['id'];
$GLOBALS['id'] = '0000001-1';
$GLOBALS['pid'] = 'boxsearch';
$GLOBALS['page'] = 'boxdetails';


//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$general_appearance = get_option('wpsc_appearance_general_settings');


$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$create_recall_btn_css       = 'background-color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_text_color'].' !important;';

//$create_recall_btn_css = $action_default_btn_css;


$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';

$action_admin_btn_css = 'background-color:#5cbdea !important;color:#FFFFFF !important;';

$agent_permissions = $wpscfunction->get_current_agent_permissions();

?>


<div class="bootstrap-iso">
  
	<h3>Recall Dashboard</h3>
  
	<div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important;"></div>
	<script>
	
	var wpsc_setting_action = 'recall';
	var attrs = {"page":"recall-init"};
	jQuery(document).ready(function() { 
		wpsc_init(wpsc_setting_action,attrs); 
	});
	
	</script>

</div>