<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $wpscfunction, $current_user;
$wpsc_guest_can_upload_files = get_option('wpsc_guest_can_upload_files');
// Get tiket id
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
if(!$ticket_id) die( 'no ticket_id' );

$reply_body = isset($_POST['reply_body']) ? sanitize_text_field($_POST['reply_body']) : 0;
if( !$reply_body ) die( 'no reply body');

$recall_id = isset($_POST['recall_id']) ? sanitize_text_field($_POST['recall_id']) : 0;
if(!$recall_id) die( 'no recall id' );

$wpsc_allow_rich_text_editor = get_option('wpsc_allow_rich_text_editor');

$nonce_check = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : 0;
//echo 'nonce check: '.$nonce_check.PHP_EOL;
//echo 'recall_id: '.$recall_id.PHP_EOL;

// Check nonce
// if( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'],$ticket_id) ){
if( !$nonce_check || !wp_verify_nonce( $nonce_check, $recall_id ) ) {	
    die(__('Cheating huh?', 'supportcandy'));
}
setcookie('wpsc_secure_code','123');

if ($current_user->ID) {
  $customer_name  = $current_user->display_name;
  $customer_email = $current_user->user_email;
} else {
  $customer_name  = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
  $customer_email = isset($_POST['customer_email']) ? sanitize_text_field($_POST['customer_email']) : '';
}

if ( !$customer_name || !$customer_email ) die();

// Get reply body
$rich_editing = $wpscfunction->rich_editing_status($current_user);

$flag = false;

if((in_array('register_user',$wpsc_allow_rich_text_editor) && !$current_user->has_cap('wpsc_agent')) && $rich_editing){
	$flag = true;
} elseif($current_user->has_cap('wpsc_agent') && $rich_editing){
	$flag = true;
} elseif ( in_array('guest_user',$wpsc_allow_rich_text_editor) && (!is_user_logged_in())) {
	$flag = true;
}

if ( $flag ) {
	$reply_body = isset($_POST['reply_body']) ? wp_kses_post(htmlspecialchars_decode($_POST['reply_body'], ENT_QUOTES)) : '';
} else {
	$reply_body = isset($_POST['reply_body']) ? sanitize_textarea_field($_POST['reply_body']) : '';
}

// Get reply attachments
$description_attachment = isset($_POST['desc_attachment']) ? $_POST['desc_attachment'] : array();
$attachments = array();
if(is_user_logged_in() || $wpsc_guest_can_upload_files ){
	foreach ($description_attachment as $key => $value) {
		$attachment_id = intval($value);
		$attachments[] = $attachment_id;
		update_term_meta ($attachment_id, 'active', '1');
	}
}

$signature = get_user_meta($current_user->ID,'wpsc_agent_signature',true);
if($signature){
	$signature= stripcslashes(htmlspecialchars_decode($signature, ENT_QUOTES));
	$reply_body.= $signature;
}

$ip_address	= isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'';
if (strlen($ip_address)>28) {
	$ip_address = '';
}
$os_platform = $wpscfunction->get_os();
$browser	 = $wpscfunction->get_browser();

$ticket_raised_by = $wpscfunction->get_ticket_fields($ticket_id,'customer_email');
$user_seen = 'null';
if($current_user->user_email == $ticket_raised_by){
	$user_seen = date("Y-m-d H:i:s");
}

// Prepare arguments
$args = array(
//   'ticket_id'      => $ticket_id, recall_id
	'recall_id'      => $recall_id, 
	'reply_body'     => $wpscfunction->replace_macro($reply_body,$ticket_id),
	'customer_name'  => $customer_name,
	'customer_email' => $customer_email,
	'attachments'    => $attachments,
	'thread_type'    => 'reply',
	'ip_address'	 => $ip_address,
	'reply_source'	 => 'browser',
	'os'			 => $os_platform,
	'browser'		 => $browser,
	'user_seen'		 => $user_seen
);

echo '<br>args: <br>';
print_r($args);
echo PHP_EOL;
echo 'POST[reply_bcc]: ' . $_POST['reply_bcc'].PHP_EOL;
echo 'ticket_id: ' . $ticket_id.PHP_EOL;

//die();

$args = apply_filters( 'wpsc_thread_args', $args );
// $thread_id = $wpscfunction->submit_ticket_thread($args);
$thread_id = Patt_Custom_Func::submit_recall_thread( $args ); 

//PATT BEGIN
	
	// Get details and send email

    if ( isset($_POST['reply_bcc']) ) {
      $reply_bcc = explode(',', sanitize_text_field($_POST['reply_bcc']));
    } else {
      $reply_bcc = array();
    }
	    $comment = $wpscfunction->replace_macro($reply_body,$ticket_id);
	    
	    // Get users for email
	    $user_ids = array();
	    
	    // curent user
	    $current_users_id = $wpdb->get_row( "SELECT ID from wpqa_users WHERE user_email = '" .  $customer_email . "'"); 
        array_push($user_ids, $current_users_id->ID);
        
        // owner of the request
        $owner_user_id = Patt_Custom_Func::get_wp_user_id_by_ticket_id( $ticket_id );
        array_push( $user_ids, $owner_user_id );
        
        // people on the recall
//         $agent_ids = Patt_Custom_Func::agents_assigned_request($ticket_id);
		$recall_id_num = ltrim( $recall_id , 'R-');
		$recall_id_num = ltrim( $recall_id_num , '0');
        $recall_agent_ids = Patt_Custom_Func::agents_assigned_recall( $recall_id_num );
        
        echo 'recall users: ' . PHP_EOL;
        print_r( $recall_agent_ids );
        echo PHP_EOL;
        echo 'recall_id_num' . $recall_id_num . PHP_EOL;
        
        $user_ids_final = array_unique( array_merge( $user_ids, $recall_agent_ids ) );
        
        //echo 'comment: ' . $comment . PHP_EOL;
        echo 'users: ' . PHP_EOL;
        print_r($user_ids_final);
        echo PHP_EOL;
        echo 'recall_id: ' . $recall_id . PHP_EOL;
        //echo 'recall_id: ' . $recall_id . PHP_EOL;        
        
		
		$type = 'recall_comment';
		
		Patt_Custom_Func::insert_new_comment_notification( $ticket_id, $comment, $user_ids_final, $reply_bcc, $recall_id, $type );
		
		

		// Set PM Notifications 
		$agent_user_id_array = Patt_Custom_Func::translate_user_id( $user_ids_final, 'agent_term_id' );
		
		$notifications = '';
		$notification_post = 'email-new-recall-comment';
		$where = [
			'ticket_id' => $ticket_id
		];
		$ticket_owner_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
		
		
		$pm_notificition_array = array_unique( array_merge( $agent_user_id_array, $ticket_owner_id_array));
		
		$pattagentid_array = $pm_notificition_array;
		$requestid = $recall_id; 
		
		$data = [
	        'item_id' => $recall_id,
	        'action_initiated_by' => $current_user->display_name
	    ];
		$email = 1;
		
		//$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
		
		echo PHP_EOL . 'new notification: ' . $new_notification;
		
		
//PATT END

do_action( 'wpsc_after_submit_reply', $thread_id, $ticket_id );






