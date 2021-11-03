<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

$tmp_user_id_arr = array();

if( $type == 'set_requestor_group' ) {
		   
$new_requestor_group_array = $_REQUEST['new_requestor_group_array'];

		foreach( $new_requestor_group_array as $status_users ) {			
		    
// Query table and remove all user_requestor_group assignments 
$get_user_requestor_group_meta = $wpdb->get_results("
SELECT user_id
FROM " . $wpdb->prefix . "usermeta
WHERE meta_key = 'user_requestor_group' AND meta_value = '".$status_users['org']."'
");

foreach($get_user_requestor_group_meta as $user_id) {
delete_user_meta($user_id->user_id, 'user_requestor_group');
}

			if (!empty($status_users['users'])) {
			   
	            foreach($status_users['users'] as $agent_id) {
	                $wp_user_id = Patt_Custom_Func::translate_user_id(array($agent_id),'wp_user_id');
                    update_user_meta($wp_user_id[0], 'user_requestor_group', $status_users['org'] );
	                array_push($tmp_user_id_arr, $wp_user_id[0]);
            }
		}
}

//print_r($tmp_user_id_arr);

$arr_unique = array_unique($tmp_user_id_arr);
$check = count($tmp_user_id_arr) !== count($arr_unique);
$message = 'Requester assignments made.';
$arr_duplicates = [];
if($check == 1) {
    $message = "The following users cannot be assigned to multiple AA'Ships:\n";
    $arr_duplicates = array_diff_assoc($tmp_user_id_arr, $arr_unique);

    foreach($arr_duplicates as $dup_user_id) {
	  $user_info = get_userdata($dup_user_id);
      $message .= 'User: ' . $user_info->display_name . "\n";
    }
            
}

echo $message;

}
?>