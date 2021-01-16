<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$path = preg_replace('/wp-content.*$/','',__DIR__);
//include($path.'wp-load.php');

global $current_user, $wpscfunction, $wpdb;

//update user display name
$get_all_users = $wpdb->get_results("SELECT a.ID as user_id, 
a.user_login as username, 
(SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'first_name' AND user_id = a.ID) as first_name, 
(SELECT meta_value FROM wpqa_usermeta WHERE meta_key = 'last_name' AND user_id = a.ID) as last_name 
FROM wpqa_users a");
foreach($get_all_users as $user) {
    $user_id = $user->user_id;
    $username = $user->username;
    $first_name = $user->first_name;
    $last_name = $user->last_name;
    $full_name = $first_name . ' ' . $last_name . ' (' . $username . ')';
    if($first_name != '' && $last_name != '') {
        wp_update_user( array( 'ID' => $user_id, 'display_name' => $full_name ) );
    }
    else {
        wp_update_user( array( 'ID' => $user_id, 'display_name' => $username ) );
    }
}

$request_no_files = $wpdb->get_results(
"
SELECT a.id as id
FROM wpqa_wpsc_ticket a
WHERE 
(SELECT 
count(b.id) from wpqa_wpsc_epa_folderdocinfo b INNER JOIN wpqa_wpsc_epa_boxinfo c ON b.box_id = c.id WHERE a.id = c.ticket_id) = 0 AND id != '-99999'
"
);

foreach ($request_no_files as $data) {
$ticket_id = $data->id;

// DELETE BOX
$box_count = $wpdb->get_row(
"SELECT count(a.id) as count
FROM wpqa_wpsc_epa_boxinfo a 
INNER JOIN wpqa_wpsc_ticket b ON a.ticket_id = b.id
WHERE b.id = '" . $ticket_id . "'"
);

if ($box_count > 0) {

$get_box_ids = $wpdb->get_results(
"SELECT id, storage_location_id
FROM wpqa_wpsc_epa_boxinfo
WHERE 
ticket_id = '" . $ticket_id . "'"
);

foreach ($get_box_ids as $data) {
    $box_id = $data->id;
	$storage_location_id = $data->storage_location_id;
    $box_table = 'wpqa_wpsc_epa_boxinfo';
    $wpdb->delete( $box_table, array( 'id' => $box_id) );
	$storage_loc_table = 'wpqa_wpsc_epa_storage_location';
    $wpdb->delete( $storage_loc_table, array( 'id' => $storage_location_id) );
}

}

// DELETE REQUEST
$ticket_table = 'wpqa_wpsc_ticket';
$wpdb->delete( $ticket_table, array( 'id' => $ticket_id) );
}


?>