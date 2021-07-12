<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$subfolder_path = site_url( '', 'relative'); 

global $wpdb, $current_user, $wpscfunction;
if (!($current_user->ID && $current_user->has_cap('wpsc_agent'))) {
		exit;
}
ob_start();
?>

<div class="wpsc_padding_space"></div> 
<strong>Pending Users</strong><br /><br />
<?php

$agents = get_terms([
	'taxonomy'   => 'wpsc_agents',
	'hide_empty' => false,
	'meta_query' => array(
    array(
      'key'       => 'agentgroup',
      'value'     => '0',
      'compare'   => '='
    )
  )
]);

$active_user_list = array();
$user_list = array();

$user_details = $wpdb->get_results("SELECT ID FROM " . $wpdb->prefix . "users");

foreach ($user_details as $info) {
			    $user_id = $info->ID;
			    array_push($user_list, $user_id);
}

foreach ($agents as $agent) {
array_push($active_user_list, Patt_Custom_Func::translate_user_id(array($agent->term_id),'wp_user_id')[0]);
}

$result=array_diff($user_list,$active_user_list);

$tbl ='<table class="table table-striped table-hover">
  <tr>
    <th>Username</th>
  </tr>';

foreach ($result as $info) {

                $get_username = $wpdb->get_row("SELECT display_name FROM " . $wpdb->prefix . "users WHERE id = '" . $info . "'");
			    $user_name = $get_username->display_name;
			    $tbl .='<tr><td>'.$user_name.'</td></tr>';
}

$tbl .='</table>';

if (count($result) != 0) {		
echo $tbl;
} else {
echo 'No Pending Users.';
}

?>