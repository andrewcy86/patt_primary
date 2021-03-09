<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

$GLOBALS['id'] = $_GET['id'];
$GLOBALS['pid'] = $_GET['pid'];
$GLOBALS['page'] = $_GET['page'];

$agent_permissions = $wpscfunction->get_current_agent_permissions();

//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';

function create_nonce($optional_salt='')
{
    return hash_hmac('sha256', session_id().$optional_salt, date("YmdG").'someSalt'.$_SERVER['REMOTE_ADDR']);
}

$_SESSION['current_page'] = $_SERVER['SCRIPT_NAME'];
?>

<div class="bootstrap-iso">
  
  <h3>Shipping Status Editor</h3>
  
 <div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important;"></div>

<script>

jQuery.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)')
                      .exec(window.location.search);

    return (results !== null) ? results[1] || 0 : false;
}

var page = jQuery.urlParam('page');
var pid = jQuery.urlParam('pid');
var id = jQuery.urlParam('id');

var wpsc_setting_action = 'shipping';
var attrs = {"page":page, "pid":pid, "id":id};

       jQuery(document).ready(function(){
         wpsc_init(wpsc_setting_action,attrs);
       });
  </script>
</div>