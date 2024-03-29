<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly 
}

global $wpdb, $current_user, $wpscfunction;

//$GLOBALS['id'] = $_GET['id'];

$agent_permissions = $wpscfunction->get_current_agent_permissions();

$current_user = wp_get_current_user();

//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';

?>

<div class="bootstrap-iso">
  
  <h3><?php echo esc_html( $current_user->user_login ); ?>'s To-Do List</h3>

 <div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important;"></div>

<script>

  var wpsc_setting_action = 'todo';
           var attrs = {"page":"todo"};
       jQuery(document).ready(function(){
         wpsc_init(wpsc_setting_action,attrs);
       });
  </script>
</div>