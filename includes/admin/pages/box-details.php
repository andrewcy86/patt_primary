<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

$subfolder_path = site_url( '', 'relative'); 

$GLOBALS['id'] = $_GET['id'];
$GLOBALS['pid'] = $_GET['pid'];
$GLOBALS['page'] = $_GET['page'];

$agent_permissions = $wpscfunction->get_current_agent_permissions();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$request_id = $wpdb->get_row("SELECT ".$wpdb->prefix."wpsc_ticket.request_id FROM ".$wpdb->prefix."wpsc_epa_boxinfo, ".$wpdb->prefix."wpsc_ticket WHERE ".$wpdb->prefix."wpsc_ticket.id = ".$wpdb->prefix."wpsc_epa_boxinfo.ticket_id AND ".$wpdb->prefix."wpsc_epa_boxinfo.box_id = '" . $GLOBALS['id'] . "'"); 
$location_request_id = $request_id->request_id;

$is_active = Patt_Custom_Func::request_status( $location_request_id );

if($is_active == 1) {
    $type = 'box';
}
else {
    $type = 'box_archive';
}

$get_request_status_id = $wpdb->get_row("SELECT ticket_status
FROM ".$wpdb->prefix."wpsc_ticket
WHERE request_id = ".$location_request_id);
$request_status_id = $get_request_status_id->ticket_status;
?>

<div class="bootstrap-iso">
  
  <h3>Box Details</h3>
  
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

var wpsc_setting_action = 'boxdetails';
var attrs = {"page":page, "pid":pid, "id":id};

       jQuery(document).ready(function(){
         wpsc_init(wpsc_setting_action,attrs);
       });
  </script>
</div>

