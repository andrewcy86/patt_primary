<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $wpscfunction, $current_user;

// Get tiket id
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
//if(!$ticket_id) die( 'no ticket_id' );

$reply_body = isset($_POST['reply_body']) ? sanitize_text_field($_POST['reply_body']) : 0;
if( !$reply_body ) die( 'no reply body');

$recall_id = isset($_POST['recall_id']) ? sanitize_text_field($_POST['recall_id']) : 0;
if(!$recall_id) die( 'no recall id' );

$nonce_check = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : 0;


$wpsc_allow_rich_text_editor = get_option('wpsc_allow_rich_text_editor');

// Check nonce
// if( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $recall_id) ){
if( !$nonce_check || !wp_verify_nonce( $nonce_check, $recall_id ) ){	
    die(__('Cheating huh?', 'supportcandy'));
}

if ($current_user->ID) {
  $customer_name  = $current_user->display_name;
  $customer_email = $current_user->user_email;
}
else {
	$customer_name  = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
	$customer_email = isset($_POST['customer_email']) ? sanitize_text_field($_POST['customer_email']) : '';
}

// Get reply body
$rich_editing =  $wpscfunction->rich_editing_status($current_user);

$flag = false;
if((in_array('register_user',$wpsc_allow_rich_text_editor) && !$current_user->has_cap('wpsc_agent')) && $rich_editing){
	$flag = true;
}elseif($current_user->has_cap('wpsc_agent') && $rich_editing){
	$flag = true;
}

if ($flag) {
	$reply_body = isset($_POST['reply_body']) ? wp_kses_post(htmlspecialchars_decode($_POST['reply_body'], ENT_QUOTES)) : '';
} else {
	$reply_body = isset($_POST['reply_body']) ? sanitize_textarea_field($_POST['reply_body']) : '';
}

// Get reply attachments
$description_attachment = isset($_POST['desc_attachment']) ? $_POST['desc_attachment'] : array();
$attachments = array();
foreach ($description_attachment as $key => $value) {
	$attachment_id = intval($value);
	$attachments[] = $attachment_id;
	update_term_meta ($attachment_id, 'active', '1');
}

$ip_address	= isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'';
if (strlen($ip_address)>28) {
	$ip_address = '';
} 
$os_platform = $wpscfunction->get_os();
$browser		 = $wpscfunction->get_browser();
// Prepare arguments
$args = array(
//   'ticket_id'     => $ticket_id,
	'recall_id'      => $recall_id,
	'reply_body'     => $wpscfunction->replace_macro($reply_body,$ticket_id),
	'customer_name'  => $customer_name,
	'customer_email' => $customer_email,
	'attachments'    => $attachments,
	'thread_type'    => 'note',
	'ip_address'	 => $ip_address,
	'reply_source'	 => 'browser',
	'os'             => $os_platform,
	'browser'		 => $browser
);

echo '<br>args: <br>';
echo '<pre>';
print_r($args);
echo '</pre>';

$args = apply_filters( 'wpsc_thread_args', $args );
//$thread_id = $wpscfunction->submit_ticket_thread($args);
$thread_id = Patt_Custom_Func::submit_recall_thread( $args ); 

//PATT BEGIN - PM Notificaitons.

    if ( isset($_POST['reply_bcc']) ) {
    	$reply_bcc = explode(',', sanitize_text_field($_POST['reply_bcc']));
    } else {
    	$reply_bcc = array();
    }
    
    $comment = $wpscfunction->replace_macro($reply_body,$ticket_id);
    
    $user_ids = array();
    $current_users_id = $wpdb->get_row( "SELECT ID from " . $wpdb->prefix . "users WHERE user_email = '" .  $customer_email . "'"); 
    array_push($user_ids, $current_users_id->ID);
    
    $agent_ids = Patt_Custom_Func::agents_assigned_request($ticket_id);
    $user_ids_final = array_unique(array_merge($user_ids, $agent_ids));

	Patt_Custom_Func::insert_new_comment_notification( $ticket_id, $comment, $user_ids_final, $reply_bcc );
		
//PATT END

// PATT BEGIN - Audit Log



// PATT END - Audit Log

do_action( 'wpsc_after_submit_note', $thread_id, $ticket_id );
