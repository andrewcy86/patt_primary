<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

// $GLOBALS['id'] = $_GET['id'];
$GLOBALS['recall_id'] = $_GET['id'];
$GLOBALS['pid'] = $_GET['pid'];
$subfolder_path = site_url( '', 'relative'); 


$prefix = 'R-';
$str = $GLOBALS['recall_id'];
if (substr($str, 0, strlen($prefix)) == $prefix) {
    $GLOBALS['recall_id'] = substr($str, strlen($prefix));
} 

// Recall variables from Support Candy
$reply_form_position = get_option('wpsc_reply_form_position');
$wpsc_redirect_to_ticket_list  = get_option('wpsc_redirect_to_ticket_list');
$wpsc_allow_rich_text_editor   = get_option('wpsc_allow_rich_text_editor');
$rich_editing = $wpscfunction->rich_editing_status($current_user);
$wpsc_allow_reply_confirmation = get_option('wpsc_allow_reply_confirmation');
$ticket_status       = $wpscfunction->get_ticket_status($ticket_id);

// Icons
$icons = '';
$freeze_icon = ' <i class="fas fa-snowflake" title="Freeze"></i>';


//include_once WPPATT_ABSPATH . 'includes/class-wppatt-functions.php';
//$load_styles = new wppatt_Functions();
//$load_styles->addStyles();

$agent_permissions = $wpscfunction->get_current_agent_permissions();

$general_appearance = get_option('wpsc_appearance_general_settings');

$action_default_btn_css = 'background-color:'.$general_appearance['wpsc_default_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_default_btn_action_bar_text_color'].' !important;';

//$cancel_recall_btn_css       = 'background-color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_bg_color'].' !important;color:'.$general_appearance['wpsc_crt_ticket_btn_action_bar_text_color'].' !important;';

$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');

$edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';

$action_admin_btn_css = 'background-color:#5cbdea !important;color:#FFFFFF !important;';
$cancel_recall_btn_css = $action_default_btn_css;

?>


<div class="bootstrap-iso">
<?php

			/*
			* Get Data
			*/
		
			$where = [
				'recall_id' => $GLOBALS['recall_id']
			];
			$recall_array = Patt_Custom_Func::get_recall_data($where);
			//echo 'Current PHP version: ' . phpversion();
			//echo "<br>";
			
			//$recall_obj = $recall_array[0];
			
			//Added for servers running < PHP 7.3
			if (!function_exists('array_key_first')) {
			    function array_key_first(array $arr) {
			        foreach($arr as $key => $unused) {
			            return $key;
			        }
			        return NULL;
			    }
			}
			
			$recall_array_key = array_key_first($recall_array);	
			$recall_obj = $recall_array[$recall_array_key];
			
			// DEBUG	
			//
			//echo 'Current user: '.$current_user->ID.'<br>';
			//echo 'Current user term id: '.$assigned_agents[0];
			//echo "<br>Recall Object: <br><pre>";	
			//print_r( $recall_obj );
			//echo '</pre>';
			//echo "count of array: ".count($recall_array);	
			//echo "<br>first index of array: ".array_key_first($recall_array);	
			//echo "<br>";	
			//echo "<br>Recall Array: <br>";
			//print_r($recall_array);

			
			//
			// REAL DATA
			//
			$db_null = -99999;
			$db_empty = '';
			$blank_date = '0000-00-00 00:00:00';
			//$db_null = '';			
			//$db_null = null;
			
			// OLD: $recall_obj->folderdoc_id == $db_null
			
			if($recall_obj->box_id > 0 && $recall_obj->folderdoc_id_parent == $db_empty ) {
				$recall_type = "Box";
				$title = "[Boxes Do Not Have Titles]";
				$recall_item_id = $recall_obj->box_id;
				
				$box_freeze = Patt_Custom_Func::id_in_freeze( $recall_item_id, 'box' );
				if( $box_freeze ) {
					$icons = $freeze_icon;
				}
				
				$recall_item_id_link = '<a href="'.$subfolder_path.'/wp-admin/admin.php?pid=boxsearch&page=boxdetails&id='.$recall_item_id.'" >' .$recall_item_id. '</a>' . $icons;
			} elseif ($recall_obj->box_id > 0 && $recall_obj->folderdoc_id !== $db_null ) {
				$recall_type = "Folder/File";
				$title = $recall_obj->title;
				$recall_item_id = $recall_obj->folderdoc_id;
				
				$file_freeze = Patt_Custom_Func::id_in_freeze( $recall_item_id, 'folderfile' );
				if( $file_freeze ) {
					$icons = $freeze_icon;
				}
				
				$recall_item_id_link = '<a href="'.$subfolder_path.'/wp-admin/admin.php?pid=boxsearch&page=filedetails&id='.$recall_item_id.'" >' .$recall_item_id. '</a>' . $icons;			
			} elseif( $recall_obj->box_id > 0 && $recall_obj->folderdoc_id > 0 ) {
				$recall_type = "Test Data";
				$title = $recall_obj->title;	
				$recall_item_id = $recall_obj->folderdoc_id;			
			} elseif( $recall_obj->box_id == $db_empty && $recall_obj->folderdoc_id == $db_empty ) {
				$recall_type = "Not Real";
				$recall_item_id = 'Not Real Data';
			}
			
			//echo $recall_type;
			//echo '<br>';
			//echo $recall_item_id;
			//echo '<br>';			
			
			//User Info - always put into an array.
//			if( !is_array($recall_obj->user_id))
			
			$user_obj = get_user_by('id', $recall_obj->user_id);
			//echo "<br><br>";
			//print_r($user_obj);
			$user_name = $user_obj->user_nicename;
			$user_email = $user_obj->user_email;
			
			if( is_array($recall_obj->user_id)) {	
				$real_array_of_users = $recall_obj->user_id;	
			} else {	
				$real_array_of_users = [$recall_obj->user_id];					
			}

			//$real_array_of_users = [$recall_obj->user_id];
			
			//Make Status Pretty
			$status_term_id = $recall_obj->recall_status_id;
			$status_background = get_term_meta($status_term_id, 'wppatt_recall_status_background_color', true);
			$status_color = get_term_meta($status_term_id, 'wppatt_recall_status_color', true);
			$status_style = "background-color:".$status_background.";color:".$status_color.";";
			//echo "<br>status style: ".$status_style."<br>";
			
			//Tracking Info
			$tracking_url = Patt_Custom_Func::get_tracking_url($recall_obj->tracking_number);
			$tracking_num = '<a href="' . $tracking_url.'" target="_blank">'.$recall_obj->tracking_number.'</a>';
			if ($tracking_num == $db_empty) {
				$tracking_num = "[No Tracking Number]";
			}
			
			
			//$recall_type = "Box";
			//$title = "The Most Important Document, Ever.";
// 			$record_schedule = $recall_obj->Record_Schedule_Number;
			$record_schedule = $recall_obj->Record_Schedule;
			$program_office = $recall_obj->office_acronym;
// 			$program_office = $recall_obj->office_code;
			$shipping_carrier = $recall_obj->shipping_carrier;
// 			$tracking_num = $recall_obj->tracking_number;
			$status = $recall_obj->recall_status;
			$requestor = $user_name;
			$requestor_email = $user_email;
			$comment = stripslashes($recall_obj->comments);
			$request_date = $recall_obj->request_date;
			$received_date = $recall_obj->request_receipt_date;
			$returned_date = $recall_obj->return_date;
			$ticket_id = $recall_obj->ticket_id;
			
			// Update Date Format
			//$request_date = date('m/d/yy h:m', strtotime($request_date));
			$request_date = date('m/d/Y', strtotime($request_date));
			if( $received_date == $blank_date ) {
				$received_date = '[Not Yet Received]';
			} else {
				//$received_date = date('m/d/yy h:m', strtotime($received_date));
				$received_date = date('m/d/Y', strtotime($received_date));
			}
			
			if( $returned_date == $blank_date ) {
				$returned_date = '[Not Yet Returned]';
			} else {
				//$returned_date = date('m/d/yy h:m', strtotime($returned_date));
				$returned_date = date('m/d/Y', strtotime($returned_date));
			}
			
			
			// Set icons for shipping carriers
			$shipping_carrier_icon = '';
			if ($shipping_carrier == 'fedex' ) {
				$shipping_carrier_icon = '<i class="padding fab fa-fedex fa-lg"></i>';
			} elseif ($shipping_carrier == 'ups' ) {
				$shipping_carrier_icon = '<i class="padding fab fa-ups fa-lg"></i>';
			} elseif ($shipping_carrier == 'dhl' ) {
				$shipping_carrier_icon = '<i class="padding fab fa-dhl fa-lg"></i>';
			} elseif ($shipping_carrier == 'usps' ) {
				$shipping_carrier_icon = '<i class="padding fab fa-usps fa-lg"></i>';
			}
						
			// Role and user checks for editing restriciton
			// Checks if current user is on this request.
			$current_user_on_request = 0;
			foreach( $real_array_of_users as $user ) {
				if( $user == $current_user->ID ) {
					$current_user_on_request = 1;
				}
			}
			
			// Cancel button restriction 
			// if admin or on request
			$user_can_cancel = 0;
			if ( $agent_permissions['label'] == 'Administrator' || $agent_permissions['label'] == 'Manager' || $agent_permissions['label'] == 'Agent' || $current_user_on_request ) {
				$user_can_cancel = 1;
			}
			
			$status_cancelled = 0;
			if ( $status == 'Recall Cancelled' ) {
				$status_cancelled = 1;
			}
			
			// Set print button link based if Recall is for a Box ID or a Folder/File ID. 
			if( $recall_type == 'Box' ) {
				$print_button_link = WPPATT_PLUGIN_URL . 'includes/ajax/pdf/box_label.php?id=' . $recall_item_id;
			} elseif( $recall_type == 'Folder/File' ) {
				
				$folderdoc_array = explode( '-', $recall_obj->folderdoc_id );
				if( $folderdoc_array[2] == '02' ) {
					$print_button_link = WPPATT_PLUGIN_URL . 'includes/ajax/pdf/file_separator_sheet.php?id=' . $recall_item_id;
				} elseif( $folderdoc_array[2] == '01' ) {
					$print_button_link = WPPATT_PLUGIN_URL . 'includes/ajax/pdf/folder_separator_sheet.php?id=' . $recall_item_id;
				}
			}
			
			// Icons

			
			//echo '<br>Current user is on request: '.$current_user_on_request.'<br>';
			//echo 'Current user can cancel: '.$user_can_cancel.'<br>';
			//echo 'Cancelled?: '.$status_cancelled.'<br>';
			//echo 'User Label: ' . $agent_permissions['label'] .'<br>';
/*
			echo 'Real user array: ' . '<pre>';
			print_r( $real_array_of_users );
			echo '</pre>';
*/
			
			//
			// END REAL DATA
			//
		
			
?>


  <h3>Recall Details</h3>

 <div id="wpsc_tickets_container" class="row" style="border-color:#1C5D8A !important;">

<div class="row wpsc_tl_action_bar" style="background-color:<?php echo $general_appearance['wpsc_action_bar_color']?> !important;">
  
	<div class="col-sm-12">
    	<button type="button" id="wpsc_individual_ticket_list_btn" onclick="location.href='admin.php?page=recall';" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css?>"><i class="fa fa-list-ul"></i> Recall List</button>
<?php		
	if ( ($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') )
	{
?>    	
    	<button type="button" id="wppatt_recall_cancel" onclick="wppatt_cancel_recall();" class="btn btn-sm wpsc_action_btn" style="<?php echo $cancel_recall_btn_css?>"><i class="fa fa-ban"></i> Cancel Recall</button>
    	
    	
<?php
		if( $status == 'Recalled' ) 
		{
?>			
		
    	<button type="button" id="wppatt_recall_approve" onclick="wppatt_approve_recall();" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css ?>"><i class="fas fa-thumbs-up"></i> Approve Recall</button>
    	<button type="button" id="wppatt_recall_deny" onclick="wppatt_deny_recall();" class="btn btn-sm wpsc_action_btn" style="<?php echo $action_default_btn_css ?>"><i class="fas fa-thumbs-down"></i> Deny Recall</button>

<?php		
		}
	}
?>

<!-- <button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_pdf_label_btn" style="" onclick="window.open('<?php echo $print_button_link ?>','_blank')"><i class="fas fa-tags"></i> Print Label</button> -->
<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_pdf_label_btn" style="" onclick="print_label();"><i class="fas fa-tags"></i> Print Label</button>
		
		
		
  </div>
	
</div>

<div class="row" id="recall_details_container" style="background-color:<?php echo $general_appearance['wpsc_bg_color']?> !important;color:<?php echo $general_appearance['wpsc_text_color']?> !important;">


  <div class="col-sm-8 col-md-9 wpsc_it_body">
    <div class="row wpsc_it_subject_widget">
	    <?php if($GLOBALS['recall_id']) { ?>
    	<h3>[Recall ID # R-<?php echo $GLOBALS['recall_id']; ?>]</h3>
    </div>
	
	<div id="recall_details_sub_container">
		<div id="search_status"></div>
		<div class="">
			<label class="wpsc_ct_field_label">Recall ID:</label>
			<span id="recall_id" class=""><?php echo $GLOBALS['recall_id']; ?></span>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Recall Type: </label>
			<span id="recall_type" class=""><?php echo $recall_type; ?></span>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label"><?php echo $recall_type; ?> ID: </label>
			<span id="recall_type" class=""><?php echo $recall_item_id_link; ?></span> 
<!-- 			<span id="recall_type" class=""><?php echo $recall_item_id; ?></span>  -->
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Title: </label>
			<span id="recall_title" class=""><?php echo $title; ?></span>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Record Schedule: </label>
			<span id="record_schedule" class=""><?php echo $record_schedule; ?></span>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Program Office: </label>
			<span id="program_office" class=""><?php echo $program_office; ?></span>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Shipping Tracking Number: </label>
			<span id="shipping_tracking" class=""><?php echo $shipping_carrier_icon; echo $tracking_num; ?></span>
			
			<?php		
				// if ( status is Recalled && digitization staff)
				// OR 
				// if ( status is On Loan && requester && requestor on this Recall
				// OR admin
				
//				($agent_permissions['label'] == 'Administrator')
//				($agent_permissions['label'] == 'Agent')
//				($agent_permissions['label'] == 'Requester')		
				if( ($status == 'Recalled' && $agent_permissions['label'] == 'Agent') || ($status == 'On Loan' && $current_user_on_request) || $agent_permissions['label'] == 'Administrator' || ($status == 'Recalled' && $agent_permissions['label'] == 'Manager') ) 
				{
					if( $status_cancelled == 0 ) 
					{
		
			?>
			
			<a href="#" onclick="wppatt_get_shipping_tracking_editor()"><i class="fas fa-edit"></i></a>
			<?php
					}
				}
			?>
			
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Shipping Carrier: </label>
			<span id="shipping_carrier" class=""><?php echo $shipping_carrier_icon; echo strtoupper($shipping_carrier); ?></span>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Status: </label>
			<span id="status" class="wpsp_admin_label" style="<?php echo $status_style ?>"><?php echo $status; ?></span>
		</div>
		<div class="requestor">
			<label class="wpsc_ct_field_label">Recall Requester(s): </label>
		</div>
		<div class="requestor">	
			<?php 
				$j = 0;
				foreach($real_array_of_users as $a_requestor) {
					$user_obj = get_user_by('id', $a_requestor);
					//print_r($user_obj);
// 					$user_name = $user_obj->user_nicename;
					$user_name = $user_obj->display_name;
					$user_email = $user_obj->user_email;
					echo '<span id="recall_requestor" class="requestor_name">'.$user_name.'</span>';
					echo '<span id="requestor_email" class="requestor_email">['.$user_email.']</span>';
					if( $j == 0 ) {
						
						// if user is requester && requestor on this Recall
						// OR admin
// 						if ( $agent_permissions['label'] == 'Administrator' || $agent_permissions['label'] == 'Manager' || $current_user_on_request ) { 
						if ( $agent_permissions['label'] == 'Administrator' || $agent_permissions['label'] == 'Manager'  ) { 	
							if( $status_cancelled == 0 ) 
							{
								echo '<a href="#" onclick="wppatt_get_recall_requestor_editor()"><i class="fas fa-edit"></i></a>';
							}
						}
					}
					echo '<br>';
					$j++;
				}
				
			?>
<!--
			<span id="recall_requestor" class="requestor_name"><?php echo $requestor; ?></span>
			<span id="requestor_email" class="requestor_email">[<?php echo $requestor_email; ?>]</span>
			<a href="#" onclick="wppatt_get_recall_requestor_editor()"><i class="fas fa-edit"></i></a>
			<br>
			<span id="recall_requestor" class="requestor_name">Capt. John Yossarian</span>
			<span id="requestor_email" class="requestor_email">[Yossarian@epa.gov]</span>
-->			
			
		</div>
<!--
		<div class="">
			<label class="wpsc_ct_field_label">Recall Requestor Email: </label>
			<span id="requestor_email" class=""><?php echo $requestor_email; ?></span>
		</div>
-->
<!--
		<div class="clear">
			<div class="">
				<label class="wpsc_ct_field_label">Comment: </label>
				<span id="comment" class=""><?php echo $comment; ?></span>
			</div>
		</div>
-->
		<div class="clear">
			<label class="wpsc_ct_field_label">Request Date: </label>
			<span id="request_date" class=""><?php echo $request_date; ?></span>
			<?php		
				if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent'))
				{
					if( $status_cancelled == 0 ) 
					{
			?>
<!-- 			<a href="#" onclick="wppatt_get_date_editor('request_date')"><i class="fas fa-edit"></i></a> -->
			<div id="request_date_editor" class="calendar"></div>
			<?php
					}
				}
			?>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Received Date: </label>
			<span id="received_date" class=""><?php echo $received_date; ?></span>
			<?php
				
				//
				// if requester && requestor on this Recall && status == shipped
				// OR Admin 
				//
						
				if ( ($status == 'Shipped' && $current_user_on_request) || $agent_permissions['label'] == 'Administrator' ) 
				{
					if( $status_cancelled == 0 ) 
					{
			?>
<!-- 					<a href="#" onclick="wppatt_get_date_editor('received_date')"><i class="fas fa-edit"></i></a> -->

			<?php
					}
				}
			?>
		</div>
		<div class="">
			<label class="wpsc_ct_field_label">Returned Date: </label>
			<span id="returned_date" class=""><?php echo $returned_date; ?></span>
			<?php
				
				//
				// if digitzation staff && status == shipped back -- DONE
				// OR Admin 
				//
						
				if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent'))
				{
					if ( $status == 'Shipped Back' ) 
					{
			?>
						<a href="#" onclick="wppatt_get_date_editor('returned_date')"><i class="fas fa-edit"></i></a>	
						<div id="returned_date_editor" class="calendar"></div>	
			<?php
					}
				}
			?>
		</div>
		
	</div>	
		
		<div class="clear">
			<div id="recall-threaded-comments-container" class="">
				<?php include WPPATT_ABSPATH . 'includes/admin/pages/scripts/recall_comments_reply_form.php' ?>	
				
				<div class="row wpsc_threads_container">
					
					<?php
					$order = $reply_form_position ? 'DESC' : 'ASC';
					$args = array(
						'post_type'      => 'wppatt_recall_thread',
						'post_status'    => 'publish',
						'orderby'        => 'ID',
						'order'          => $order,
						'posts_per_page' => -1,
						'meta_query'     => array(
							'relation' => 'AND',
							array(
// 								'key'     => 'ticket_id',
								'key'     => 'recall_id', 
// 					      'value'   => $ticket_id,
					      'value'   => 'R-'.$GLOBALS['recall_id'],
					      'compare' => '='
							)
						)
					);
					$threads = get_posts($args);
					
					// Get auth_id to allow attachment downloads
					//$ticket_auth_code = $wpscfunction->get_ticket_fields($this->ticket_id,'ticket_auth_code');
					$ticket_auth_code = $wpscfunction->get_ticket_fields( $ticket_id, 'ticket_auth_code');
					$auth_id          = $ticket_auth_code;
					
					if(apply_filters('wpsc_print_thread',true)){	
					foreach ($threads as $thread):
						$reply = stripslashes(htmlspecialchars_decode($thread->post_content, ENT_QUOTES));
						$reply = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $reply);
		
						$thread_type    = get_post_meta( $thread->ID, 'thread_type', true);
						$customer_name  = get_post_meta( $thread->ID, 'customer_name', true);
						$customer_email = get_post_meta( $thread->ID, 'customer_email', true);
						$attachments    = get_post_meta( $thread->ID, 'attachments', true);
						//$ticket_id      = get_post_meta( $thread->ID,'ticket_id',true);
						$seen      			= get_post_meta( $thread->ID,'user_seen',true);
						
						if( $seen && $current_user->user_email == $ticket['customer_email'] && ($thread_type == 'report' || $thread_type == 'reply') ){
							update_post_meta($thread->ID, 'user_seen', date("Y-m-d H:i:s"));
						}
		
						if ($thread_type == 'report'):
							?>
							<div class="wpsc_thread" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_report_thread_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_report_thread_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_report_thread_border_color']?> !important;">
								<div class="thread_avatar">
									<?php echo get_avatar( $customer_email, 40 )?>
								</div>
								<?php 
								if($wpsc_thread_date_format == 'timestamp'){
									$date = sprintf( __('reported %1$s','supportcandy'), $wpscfunction->time_elapsed_timestamp($thread->post_date_gmt) );
								}else{
									$date = sprintf( __('reported %1$s','supportcandy'), $wpscfunction->time_elapsed_string($thread->post_date_gmt) );
								}
								?>
								<div class="thread_body">
									<div class="thread_user_name">
										<strong><?php echo $customer_name; ?></strong><small><i><?php echo $date?></i></small><br>
										<?php if ( apply_filters('wpsc_thread_email_visibility',$current_user->has_cap('wpsc_agent')) ) {?>
											<small><?php echo $customer_email?></small>
										<?php }?>
										<?php if ($wpscfunction->has_permission('edit_delete_ticket',$ticket_id) && $ticket_status):?>
											<i onclick="wpsc_get_delete_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);" class="fa fa-trash thread_action_btn wpsc_delete_thread" title="<?php _e('Delete this thread','supportcandy');?>"></i>
											<i onclick="wpsc_get_edit_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);"   class="fa fa-edit thread_action_btn wpsc_edit_thread"  title="<?php _e('Edit this thread','supportcandy');?>"></i>
<!-- 												<i onclick="wppatt_get_edit_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);"   class="fa fa-edit thread_action_btn wpsc_edit_thread"  title="<?php _e('Edit this thread','supportcandy');?>"></i> -->
										<?php endif;?>
										<?php if($current_user->has_cap('wpsc_agent')): ?>
										<!--PATT BEGIN
											<i onclick="wpsc_get_create_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);" class="fa fa-plus-square thread_action_btn wpsc_create_ticket_thread" title="<?php _e('Create new ticket from this thread','supportcandy');?>"></i>
										PATT END-->
											<i onclick="wpsc_get_thread_info(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>,'thread');" class="fas fa-info-circle thread_action_btn wpsc_thread_info" title="<?php _e('Thread Info','supportcandy');?>"></i>
											
										<?php endif;?>
									</div>
									<div class="thread_messege">
										<?php 
											if ($flag) {
												echo $reply;
											} else {
												echo nl2br($reply);
											}
										?>
									</div>
									<?php 
										$wpsc_view_more = get_option('wpsc_view_more');
										if($wpsc_view_more){?>
											<div onclick="wpsc_ticket_thread_expander_toggle(this);" class="col-md-12 wpsc_ticket_thread_expander" style="padding: 0px; display: none;">
												 <?php _e('View More ...','supportcandy')?>
											</div>
										<?php	
										}?>
										<?php if($attachments):?>
											<br>
											<strong class="wpsc_attachment_title"><?php _e('Attachments','supportcandy');?>:</strong><br>
											<table class="wpsc_attachment_tbl">
												<tbody>
													<?php
													foreach( $attachments as $attachment ):
														$attach      = array();
														$attach_meta = get_term_meta($attachment);
														foreach ($attach_meta as $key => $value) {
															$attach[$key] = $value[0];
														}	
														$download_url = site_url('/').'?wpsc_attachment='.$attachment.'&tid='.$ticket_id.'&tac='.$auth_id;
														// PATT addition to get correct file
														$table = $wpdb->prefix . 'termmeta';
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment;
														$term_array = $wpdb->get_results( $sql );
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment .' AND meta_key = "time_uploaded"';
														$time_array = $wpdb->get_results( $sql );
														$time_pieces = explode( ' ', $time_array[0]->meta_value );
														$date_pieces = explode( '-', $time_pieces[0] );
														$folder_year = $date_pieces[0];
														$folder_month = $date_pieces[1];
														
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment .' AND meta_key = "save_file_name"';
														$file_name_array = $wpdb->get_results( $sql );
														$file_name = $file_name_array[0]->meta_value;
														
														$download_url = str_replace( 'mu-plugins/pattracking/', '', WPPATT_PLUGIN_URL );
														$download_url .= 'uploads/wpsc/' . $folder_year . '/' . $folder_month . '/';
														$download_url .= $file_name;
														// PATT end
														?>
														<tr class="wpsc_attachment_tr">
															<td>
																<a class="wpsc_attachment_link" href="<?php echo $download_url?>" target="_blank">
															  <span class="wpsc_attachment_file_name" style="padding: 7px;"><?php echo $attach['filename'];?></span></a>
															  <?php if ($current_user->has_cap('edit_published_posts')) { ?>
																	<i onclick="wpsc_thread_attachment_remove(this,<?php echo $attachment; ?>,<?php echo $thread->ID; ?>,<?php echo $ticket_id; ?>); " class="fa fa-times thread_action_btn" style="padding-top:3px;" aria-hidden="true" title="<?php _e('Delete attachment','supportcandy');?>"></i>
																<?php } ?>
		
															</td>
													 </tr>
													<?php	endforeach;?>
												</tbody>
											</table>
											<?php do_action('wpsc_after_report_attachment',$ticket_id,$thread->ID);?>
		
										<?php endif;?>
										<?php if( $current_user->has_cap('wpsc_agent')){?>
											<div>
												<?php 
												if( $seen && $seen != 'null' ){ ?>
													<i class="fas fa-check-circle wpsc_seen_info" title="<?php _e("Seen: ". $wpscfunction->time_elapsed_timestamp($seen),"supportcandy");?>"></i>
													<?php
												} ?>
											</div>
										<?php } ?>
									</div>
								</div>
								<?php
						endif;
		
						if ($thread_type == 'reply'):
							if($wpsc_thread_date_format == 'timestamp'){
								$date = sprintf( __('replied %1$s','supportcandy'), $wpscfunction->time_elapsed_timestamp($thread->post_date_gmt) );
							}else{
								$date = sprintf( __('replied %1$s','supportcandy'), $wpscfunction->time_elapsed_string($thread->post_date_gmt) );
							}
							$user_info=get_user_by('email',$customer_email);
							$style = '';
							if($user_info && $user_info->has_cap('wpsc_agent')){
								$style = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_reply_thread_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_reply_thread_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_reply_thread_border_color'].' !important';
							}else{
								$style = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_reply_thread_customer_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_reply_thread_customer_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_reply_thread_customer_border_color'].' !important';
							}
							?>
							<div class="wpsc_thread" style="<?php echo $style;?>">
								<div class="thread_avatar">
									<?php echo get_avatar( $customer_email, 40 )?>
								</div>
								<div class="thread_body">
									<div class="thread_user_name">
										<strong><?php echo $customer_name?></strong><small><i><?php echo $date?></i></small><br>
										<?php if ( apply_filters('wpsc_thread_email_visibility',$current_user->has_cap('wpsc_agent')) ) {?>
											<small><?php echo $customer_email ?></small> 
										<?php }?>
										<?php if ($wpscfunction->has_permission('edit_delete_ticket',$ticket_id) /* && $ticket_status */):?>
											<i onclick="wpsc_get_delete_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);" class="fa fa-trash thread_action_btn wpsc_delete_thread" title="<?php _e('Delete this thread','supportcandy');?>"></i>
											<i onclick="wpsc_get_edit_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);"   class="fa fa-edit thread_action_btn wpsc_edit_thread recall_edit_thread"  title="<?php _e('Edit this thread','supportcandy');?>"></i>
<!-- 												<i onclick="wppatt_get_edit_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);"   class="fa fa-edit thread_action_btn wpsc_edit_thread"  title="<?php _e('Edit this thread','supportcandy');?>"></i> -->
										<?php endif;?>
										<?php if($current_user->has_cap('wpsc_agent')): ?>
										    <!--removes button from admin/staff role-->
											<!--<i onclick="wpsc_get_create_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);" class="fa fa-plus-square thread_action_btn wpsc_create_ticket_thread" title="<?php _e('Create new ticket from this thread','supportcandy');?>"></i>-->
											<i onclick="wpsc_get_thread_info(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>,'thread');" class="fas fa-info-circle thread_action_btn wpsc_thread_info" title="<?php _e('Thread Info','supportcandy');?>"></i>
										<?php endif;?>
									</div>
									<div class="thread_messege">
										<?php 
										if ($flag) {
											echo $reply;
										} else {
											echo nl2br($reply);
										} 
										?>
									</div>
									<?php 
										$wpsc_view_more = get_option('wpsc_view_more');
										if($wpsc_view_more){?>
											<div onclick="wpsc_ticket_thread_expander_toggle(this);" class="col-md-12 wpsc_ticket_thread_expander" style="padding: 0px; display: none;">
												 <?php _e('View More ...','supportcandy')?>
											</div>
										<?php	
										}?>
										<?php if($attachments):?>
											<strong class="wpsc_attachment_title"><?php _e('Attachments','supportcandy');?>:</strong><br>
											<table class="wpsc_attachment_tbl">
												<tbody>
													<?php
													foreach( $attachments as $attachment ):
														$attach      = array();
														$attach_meta = get_term_meta($attachment);
														foreach ($attach_meta as $key => $value) {
															$attach[$key] = $value[0];
														}
														$download_url = site_url('/').'?wpsc_attachment='.$attachment.'&tid='.$ticket_id.'&tac='.$auth_id;
														
														// PATT addition to get correct file
														$table = $wpdb->prefix . 'termmeta';
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment;
														$term_array = $wpdb->get_results( $sql );
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment .' AND meta_key = "time_uploaded"';
														$time_array = $wpdb->get_results( $sql );
														$time_pieces = explode( ' ', $time_array[0]->meta_value );
														$date_pieces = explode( '-', $time_pieces[0] );
														$folder_year = $date_pieces[0];
														$folder_month = $date_pieces[1];
														
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment .' AND meta_key = "save_file_name"';
														$file_name_array = $wpdb->get_results( $sql );
														$file_name = $file_name_array[0]->meta_value;
														
														$download_url = str_replace( 'mu-plugins/pattracking/', '', WPPATT_PLUGIN_URL );
														$download_url .= 'uploads/wpsc/' . $folder_year . '/' . $folder_month . '/';
														$download_url .= $file_name;
														// PATT end
														
														?>
														<tr class="wpsc_attachment_tr">
															<td>
																<a class="wpsc_attachment_link" href="<?php echo $download_url?>" target="_blank">
																<span class="wpsc_attachment_file_name" style="padding: 7px;"><?php echo $attach['filename'];?></span></a>
																<?php if ($current_user->has_cap('edit_published_posts')) { ?>
																	<i onclick="wpsc_thread_attachment_remove(this,<?php echo $attachment; ?>,<?php echo $thread->ID; ?>,<?php echo $ticket_id; ?>); " class="fa fa-times thread_action_btn" style="padding-top:3px;" aria-hidden="true" title="<?php _e('Delete attachment','supportcandy');?>"></i>
																<?php } ?>
		
															</td>
														</tr>
													<?php	endforeach;?>
												</tbody>
											</table>
											<?php do_action('wpsc_after_reply_attachment',$ticket_id,$thread->ID);?>
										<?php endif;?>
										<?php if( $current_user->has_cap('wpsc_agent')){?>
											<div>
												<?php 
												if( $seen && $seen != 'null' ){ ?>
													<i class="fas fa-check-circle wpsc_seen_info" title="<?php _e("Seen: " .$wpscfunction->time_elapsed_timestamp($seen),"supportcandy");?>"></i><?php 
												} ?>
											</div>
										<?php } ?>
									</div>
								</div>
							<?php
						endif;
		
						if ( $thread_type == 'note' && apply_filters('wpsc_private_note_visibility',$current_user->has_cap('wpsc_agent'), $thread) &&  $wpscfunction->has_permission('view_note',$ticket_id) ):
							?>
							<div class="wpsc_thread note" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_private_note_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_private_note_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_private_note_border_color']?> !important;">
								<div class="thread_avatar">
									<?php echo get_avatar( $customer_email, 40 )?>
								</div>
								<?php 
								if($wpsc_thread_date_format == 'timestamp'){
									$date = sprintf( __('added note %1$s','supportcandy'), $wpscfunction->time_elapsed_timestamp($thread->post_date_gmt) );
								}else{
									$date = sprintf( __('added note %1$s','supportcandy'), $wpscfunction->time_elapsed_string($thread->post_date_gmt) );
								}
								?>
								<div class="thread_body">
									<div class="thread_user_name">
										<strong><?php echo $customer_name?></strong><small><i><?php echo $date?></i></small><br>
										<?php if ( apply_filters('wpsc_thread_email_visibility',$current_user->has_cap('wpsc_agent')) ) {?>
											<small><?php echo $customer_email?></small>
										<?php }?>
										<?php if ($wpscfunction->has_permission('edit_delete_ticket',$ticket_id) && $ticket_status):?>
											<i onclick="wpsc_get_delete_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);" class="fa fa-trash thread_action_btn wpsc_delete_thread"></i>
											<i onclick="wpsc_get_edit_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);"  class="fa fa-edit thread_action_btn wpsc_edit_thread"></i>
<!-- 												<i onclick="wppatt_get_edit_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);"  class="fa fa-edit thread_action_btn wpsc_edit_thread"></i> -->
										<?php endif;?>
										<?php if($current_user->has_cap('wpsc_agent')): ?>
											<!--<i onclick="wpsc_get_create_thread(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>);" class="fa fa-plus-square thread_action_btn wpsc_create_ticket_thread" title="<?php _e('Create new ticket from this thread','supportcandy');?>"></i>-->
										<?php endif;?>
										<?php if($current_user->has_cap('wpsc_agent')):?>
											<i onclick="wpsc_get_thread_info(<?php echo $ticket_id ?>,<?php echo $thread->ID ?>,'thread');" class="fas fa-info-circle thread_action_btn wpsc_thread_info" title="<?php _e('Thread Info','supportcandy');?>"></i>
											
								        <?php endif;?>
		
									</div>
									<div class="thread_messege">
									<?php 
										if ($flag) {
											echo $reply;
										} else {
											echo nl2br($reply);
										}  
										?>
									</div>
									<?php 
										$wpsc_view_more = get_option('wpsc_view_more');
										if($wpsc_view_more){?>
											<div onclick="wpsc_ticket_thread_expander_toggle(this);" class="col-md-12 wpsc_ticket_thread_expander" style="padding: 0px; display: none;">
												 <?php _e('View More ...','supportcandy')?>
											</div>
										<?php	
										}?>
										<?php if($attachments):?>
											<strong class="wpsc_attachment_title"><?php _e('Attachments','supportcandy');?>:</strong><br>
											<table class="wpsc_attachment_tbl">
												<tbody>
													<?php
													foreach( $attachments as $attachment ):
														$attach      = array();
														$attach_meta = get_term_meta($attachment);
														
														foreach ($attach_meta as $key => $value) {
															$attach[$key] = $value[0];
														}
														$download_url = site_url('/').'?wpsc_attachment='.$attachment.'&tid='.$ticket_id.'&tac='.$auth_id;
														
														// PATT addition to get correct file
														$table = $wpdb->prefix . 'termmeta';
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment;
														$term_array = $wpdb->get_results( $sql );
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment .' AND meta_key = "time_uploaded"';
														$time_array = $wpdb->get_results( $sql );
														$time_pieces = explode( ' ', $time_array[0]->meta_value );
														$date_pieces = explode( '-', $time_pieces[0] );
														$folder_year = $date_pieces[0];
														$folder_month = $date_pieces[1];
														
														$sql = 'SELECT * FROM ' . $table . ' where term_id = ' . $attachment .' AND meta_key = "save_file_name"';
														$file_name_array = $wpdb->get_results( $sql );
														$file_name = $file_name_array[0]->meta_value;
														
														$download_url = str_replace( 'mu-plugins/pattracking/', '', WPPATT_PLUGIN_URL );
														$download_url .= 'uploads/wpsc/' . $folder_year . '/' . $folder_month . '/';
														$download_url .= $file_name;
														// PATT end
														
														?>
														<tr class="wpsc_attachment_tr">
															<td>
																<a class="wpsc_attachment_link" href="<?php echo $download_url?>" target="_blank">
															  <span class="wpsc_attachment_file_name" style="padding: 7px;"><?php echo $attach['filename'];?></span></a>
															  <?php if ($current_user->has_cap('edit_published_posts')) { ?>
																	<i onclick="wpsc_thread_attachment_remove(this,<?php echo $attachment; ?>,<?php echo $thread->ID; ?>,<?php echo $ticket_id; ?>); " class="fa fa-times thread_action_btn" style="padding-top:3px;" aria-hidden="true" title="<?php _e('Delete attachment','supportcandy');?>"></i>
																<?php } ?>
		
															</td>
														</tr>
													<?php	endforeach;?>
												</tbody>
											</table>
										<?php endif;?>
								</div>
							</div>
							<?php
						endif;
		/* PATT BEGIN
						if ( $thread_type == 'log' && apply_filters('wpsc_thread_log_visibility',$current_user->has_cap('wpsc_agent')) && $wpscfunction->has_permission('view_log',$ticket_id)):
							?>
							<div class="col-md-8 col-md-offset-2 wpsc_thread_log" style="background-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_logs_bg_color']?> !important;color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_logs_text_color']?> !important;border-color:<?php echo $wpsc_appearance_individual_ticket_page['wpsc_ticket_logs_border_color']?> !important;">
				          <?php 
									if($wpsc_thread_date_format == 'timestamp'){
										$date = sprintf( __('reported %1$s','supportcandy'), $wpscfunction->time_elapsed_timestamp($thread->post_date_gmt) );
									}else{
										$date = sprintf( __('reported %1$s','supportcandy'), $wpscfunction->time_elapsed_string($thread->post_date_gmt) );
									}
									echo $reply ?> <i><small><?php echo $date ?></small></i>
				      </div>
							<?php
						endif;
		PATT END */
						do_action( 'wpsc_print_thread_type', $thread_type, $thread );
					  endforeach;
					?>
				</div>
		
				<?php
				if( !$reply_form_position ){
//					include WPSC_ABSPATH . 'includes/admin/tickets/individual_ticket/reply_form.php';
					include WPPATT_ABSPATH . 'includes/admin/pages/scripts/recall_comments_reply_form.php';
				}
				?>
				<?php } ?>

				
				
				
				
				
				
				
				
				
			</div> <!-- id: recall-threaded-comments-container -->
		</div>
		
<!-- 	</div> -->



<!-- Pop-up snippet start -->
<div id="wpsc_popup_background" style="display:none;"></div>
<div id="wpsc_popup_container" style="display:none;">
  <div class="bootstrap-iso">
    <div class="row">
      <div id="wpsc_popup" class="col-xs-10 col-xs-offset-1 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
        <div id="wpsc_popup_title" class="row"><h3>Modal Title</h3></div>
        <div id="wpsc_popup_body" class="row">I am body!</div>
        <div id="wpsc_popup_footer" class="row">
          <button type="button" class="btn wpsc_popup_close"><?php _e('Close','supportcandy');?></button>
          <button type="button" class="btn wpsc_popup_action"><?php _e('Save Changes','supportcandy');?></button>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Pop-up snippet end -->

<br />

<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>
<script>
jQuery(document).ready(function() {
	 
	//Check if real data, otherwise set alert 
	let recall_type = '<?php echo $recall_type ?>';
	console.log({recall_type:recall_type});
	if( recall_type == 'Not Real' ) {
		let recall_id = '<?php echo $GLOBALS['recall_id'] ?>';
		console.log({recall_id:recall_id});
		alert( 'R-' + recall_id + ' is not a valid Recall ID');
	}
	 
	// Updates Admin Menu to highlight the submenu page that this page is under. 
	jQuery('#toplevel_page_wpsc-tickets').removeClass('wp-not-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets').addClass('wp-has-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets').addClass('wp-menu-open'); 
	jQuery('#toplevel_page_wpsc-tickets a:first').removeClass('wp-not-current-submenu');
	jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-has-current-submenu'); 
	jQuery('#toplevel_page_wpsc-tickets a:first').addClass('wp-menu-open');
	jQuery('#menu-dashboard').removeClass('current');
	jQuery('#menu-dashboard a:first').removeClass('current');
	 
	 <?php
    if ($_GET['page'] == 'recallcreate') {
    ?>
         jQuery('.wp-submenu li:nth-child(5)').addClass('current');
    <?php
    }
    ?>
    <?php
    if ($_GET['page'] == 'recalldetails') {
    ?>
         jQuery('.wp-submenu li:nth-child(5)').addClass('current');
    <?php
    }
    ?>
	 
	 
	 // disable cancel if status not recalled. Or is user doesn't have role. 
	 jQuery('#wppatt_recall_cancel').attr('disabled', 'disabled');
	 console.log(jQuery('#status').html());
	 var user_can_cancel = <?php echo $user_can_cancel ?>;
	 if(  jQuery('#status').html() == 'Recalled' && user_can_cancel) {
		jQuery('#wppatt_recall_cancel').removeAttr('disabled');	 
	 }


	// SETS the WYSIWYG editor
// 	let tinymce_toolbar = ["bold","italic","underline","blockquote"," | ","alignleft aligncenter alignright"," | ","bullist","numlist"," | ","rtl"," | ","link","image"];
	let tinymce_toolbar = ["bold","italic","underline","blockquote"," | ","alignleft aligncenter alignright"," | ","bullist","numlist"," | ","rtl"," | ","link"];
	var is_tinymce = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();

// 	if( is_tinymce ) {
	if( true ) {	
		tinymce.init({
			selector:'#wpsc_reply_box',
			body_id: 'wpsc_reply_box',
			menubar: false,
			statusbar: false,
			autoresize_min_height: 150,
			wp_autoresize_on: true,
			plugins: [
			  'wpautoresize lists link image directionality'
			],
			toolbar:  tinymce_toolbar.join() +' | wpsc_templates',
			branding: false,
			autoresize_bottom_margin: 20,
			browser_spellcheck : true,
			relative_urls : false,
			remove_script_host : false,
			convert_urls : true
		});
	}
	     
	        
} );

		function wpsc_get_folderfile_editor(doc_id){
<?php
			$box_il_val = '';
			if ($box_il == 1) {
?>
		  wpsc_modal_open('Edit Folder Metadata');
<?php
			} else {
?>
		  wpsc_modal_open('Edit File Metadata');
<?php
			}
?>

		  var data = {
		    action: 'wpsc_get_folderfile_editor',
		    doc_id: doc_id
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });  
		}
</script>


  </div>
 

<?php
} else {

echo '<span style="padding-left: 10px">Please pass a valid Recall ID</span>';

}
?>
</div>
</div>
<!-- </div> -->




<script>
	
	var recall_id = "<?php echo $GLOBALS['recall_id'] ?>";
	var ticket_id = "<?php echo $ticket_id ?>";
	//recall_id = 3; //Test data
	//IMPLEMENT: check to ensure that valid recall_id.
	
	
	
/*
	jQuery(document).ready(function() {
		var recall_id = "<?php echo $GLOBALS['recall_id'] ?>";
//  		var ticket_id = '<?php echo $ticket_id ?>';
 		//var ticket_id = 33;
		//setTimeout( function({var ticket_id = '<?php echo $ticket_id ?>';console.log( 'ready ticket_id: ' + ticket_id );}), 200 );
		
		//console.log( 'ready ticket_id: ' + ticket_id );
		
		let returned_date = '<?php echo $returned_date; ?>';
		console.log('returned_date: ' + returned_date);
		console.log(recall_id);
		
		//recall_id = 3; //Test data
		//IMPLEMENT: check to ensure that valid recall_id.
	});
*/
	
	
	function wppatt_get_shipping_tracking_editor() {
		//alert('edit tracking');
		var shipping_tracking = jQuery('#shipping_tracking').text();
		var shipping_carrier = jQuery('#shipping_carrier').text();
		//alert('shipping tracking: '+shipping_tracking+' carrier: '+shipping_carrier);
		
		wpsc_modal_open('Edit Shipping Details');
		
		var data = {
		    action: 'wppatt_recall_get_shipping',
		    recall_id: recall_id,
		    recall_ids: [recall_id],
		    shipping_tracking: shipping_tracking,
		    shipping_carrier: shipping_carrier,
		    ticket_id: ticket_id,
		    from_page: 'recall-details'		    
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
	}
	
	function wppatt_get_recall_requestor_editor() {
// 		alert('edit requestor');
		var recall_requestor_array = []; // not used?
//		console.log('requestor array: ');
//		console.log(recall_requestor_array);
		
		jQuery('.requestor_name').each(function() {
			
			recall_requestor_array.push(jQuery(this).text());
			//console.log(recall_requestor_array);
		});


		var requestor = jQuery('#recall_requestor').text(); // also not used. 
		
		//let new_ticket_id = parseInt( jQuery('input[name="ticket_id"]').val() );
		//console.log({new_ticket_id:new_ticket_id});
		
		// 		    ticket_id: new_ticket_id.value,
		// 			ticket_id: '33',
		
		wpsc_modal_open('Edit Requester Details');
		var data = {
		    action: 'wppatt_recall_get_requestor',
		    recall_id: recall_id,
		    ticket_id: ticket_id,
		    requestor: requestor  // not used
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
// 		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
	} 
	
	
	function wppatt_get_date_editor(date_type) {
 		//alert('Date Type: '+date_type);
		//jQuery('.datepicker').datepicker();
		
		switch (date_type) {
			case 'request_date':
				var title = 'Request';
				var old_date = jQuery('#request_date').text();
				console.log("old date: "+old_date);
				break;
			case 'received_date':
				 var title = 'Received';
				 var old_date = jQuery('#received_date').text();
				break;
			case 'returned_date':
				 var title = 'Returned';
				 var old_date = jQuery('#returned_date').text();
				break;
			default:
				var title = 'false';
		}
		
// 		alert('Date Title: '+title);
		
		
		wpsc_modal_open('Edit '+title+' Date Details');
		var data = {
		    action: 'wppatt_recall_get_date',
		    recall_id: recall_id,
		    date_type: date_type,
		    title: title,
		    old_date: old_date,
		    ticket_id: ticket_id
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
//		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 
		
	}
	
	function wppatt_cancel_recall(  ) {
		
		console.log('recall_id: '+recall_id);
		console.log('ticket id: '+ticket_id);
		
		wpsc_modal_open('Cancel Recall: R-'+recall_id);
		var data = {
		    action: 'wppatt_recall_cancel',
		    recall_id: recall_id,
		    ticket_id: ticket_id
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
//		    jQuery('#wpsc_popup_body').html(response_str);		    
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 

	}
	
	function wppatt_approve_recall() {
		
		console.log('recall_id: '+recall_id);
		console.log('ticket id: '+ticket_id);
		
		wpsc_modal_open('Approve Recall: R-'+recall_id);
		var data = {
		    action: 'wppatt_recall_approve_deny',
		    recall_id: recall_id,
		    ticket_id: ticket_id,
		    type: 'approve_recall'
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 

	}
	
	function wppatt_deny_recall() {
		
		console.log('recall_id: '+recall_id);
		console.log('ticket id: '+ticket_id);
		
		wpsc_modal_open('Deny Recall: R-'+recall_id);
		var data = {
		    action: 'wppatt_recall_approve_deny',
		    recall_id: recall_id,
		    ticket_id: ticket_id,
		    type: 'deny_recall'
		};
		jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		}); 

	}
	
	function print_label() {
		
		let print_button_link = '<?php echo $print_button_link ?>';
		let recall_item_id = '<?php echo $recall_item_id ?>';
		let recall_type = '<?php echo $recall_type ?>';
		let post_url;
		let post_var;
		
		console.log( {print_button_link:print_button_link, recall_item_id:recall_item_id, recall_type:recall_type} );
		
		if( recall_type == 'Box' ) {
			post_url = '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/boxlabels_processing.php';
			post_var = { postvarsboxid: recall_item_id };
		} else if( recall_type == 'Folder/File' ) {
			post_url = '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/documentlabels_processing.php';
			post_var = { postvarsfolderdocid: recall_item_id };
		}
		console.log({post_url:post_url, post_var:post_var});
		
		jQuery.post(
			post_url, post_var, 
			function (response) {
				console.log( {response:response});
				
				var folderdocinfo = response.split('|')[1];
				var folderdocinfo_array = folderdocinfo.split(',');
				var substring_false = "false";
				var substring_warn = "warn";
				var substring_true = "true";
				
				if(response.indexOf(substring_false) >= 0) {
					alert('Cannot print folder/file labels for documents that are destroyed or not assigned to a location.');
				}
				
				if(response.indexOf(substring_warn) >= 0) {
					alert('One or more documents that you selected has been destroyed or does not have an assigned location and it\'s label will not generate.');
				}
				
				if(response.indexOf(substring_true) >= 0) {
					window.open( print_button_link,'_blank');
				}
		});
		
		
	}
	
	
	//
	// Threaded Comment Functions - added from SupportCandy: load_individual_ticket.php
	//
	
	// Submit note
	function wppatt_submit_reply( save_type ){
		jQuery('.submit .btn-group').removeClass('open');
		<?php
		if(( in_array('register_user', $wpsc_allow_rich_text_editor ) && !$current_user->has_cap('wpsc_agent')) && $rich_editing ) {
				$flag = true;
			}else if( $current_user->has_cap( 'wpsc_agent' ) && $rich_editing ){
				$flag = true;
			}
			
			if( $flag ) { ?>
				var description = tinyMCE.activeEditor.getContent().trim();
				//var description = jQuery('#wppatt_reply_box').val().trim(); 
				console.log('description: ');
				console.log( description );				
				<?php 
			} else { ?>
// 				var description = jQuery('#wpsc_reply_box').val().trim(); 
				var description = jQuery('#wppatt_reply_box').val().trim(); 
				console.log('2nd description: ');
				console.log( description );	
				<?php 
			} ?>

		if( description.length == 0 ){
			alert( '<?php _e('Description empty!','supportcandy')?>' );
			return;
		}
		switch( save_type ){
			case 'note' :
				wppatt_post_note( description );
				break;
			case 'reply':
				wppatt_post_reply( description );
				break;
			case 'canned_reply':
				wpsc_save_canned_reply();
				break;
		}
	}
	
	
	function wppatt_post_reply( description ){
		<?php if( $wpsc_allow_reply_confirmation ) { ?>
			if(!confirm('<?php _e('Are you sure?','supportcandy')?>')) return;
		<?php } ?>
		
		//console.log('The #wppatt_form_recall_reply');
		//console.log( jQuery('#wppatt_form_recall_reply')[0] );
/*
		let ticket_id = '<?php echo $ticket_id; ?>';
		console.log('ticket_id: ');
		console.log( ticket_id );
		let ticket_id2 = "<?php echo $ticket_id; ?>";
		console.log( 'ticket_id2: ' + ticket_id2 );
		let returned_date = '<?php echo $returned_date; ?>';
		console.log('returned_date: ' + returned_date);
		console.log(recall_id);
*/
		
		
// 		var dataform = new FormData( jQuery( '#wpsc_frm_tkt_reply' )[0]);	
		var dataform = new FormData( jQuery('#wppatt_form_recall_reply')[0] );
		
		//console.log('recall post reply');
		//console.log( description );

		var redirect = <?php echo $wpsc_redirect_to_ticket_list?> ;
		jQuery('.wpsc_reply_widget').html(wpsc_admin.loading_html);
// 		dataform.append('action', 'wpsc_tickets');
		dataform.append('action', 'wppatt_recall_threaded_comment_reply');
		dataform.append('setting_action', 'submit_reply');
		dataform.append('reply_body', description);
		//dataform.append('ticket_id', '<?php echo $ticket_id ?>' );
		

		
		jQuery.ajax({
			url: wpsc_admin.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		})
		.done( function( response_str ) {
			//redirect = redirect ? wpsc_get_ticket_list() : wpsc_open_ticket(<?php echo $ticket_id?>);
			console.log('Submitted Recall Post Reply: ');
			console.log( response_str );
			location.reload();
		});
		var is_tinymce = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();
		if(is_tinymce) tinyMCE.activeEditor.setContent('');
	}
	
	
	
	function wppatt_post_note( description ){
// 		var dataform = new FormData( jQuery( '#wpsc_frm_tkt_reply' )[0]); 
		var dataform = new FormData( jQuery('#wppatt_form_recall_reply')[0]); 
		

		
		
		jQuery('.wpsc_reply_widget').html(wpsc_admin.loading_html);
// 		dataform.append('action','wpsc_tickets');
		dataform.append('action','wppatt_recall_threaded_comment_note');
		dataform.append('setting_action','submit_note');
		dataform.append('reply_body', description);
		dataform.append('ticket_id', '<?php echo $ticket_id ?>' );
		
		//console.log({dataform:dataform});
		
		jQuery.ajax({
			url: wpsc_admin.ajax_url,
			type: 'POST',
			data: dataform,
			processData: false,
			contentType: false
		})
		.done(function (response_str) {
			//wpsc_open_ticket(<?php echo $ticket_id?>);
			console.log('Submitted Recall Note: ');
			console.log( response_str );
			location.reload();
			

		});
		var is_tinymce = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();
		if(is_tinymce) tinyMCE.activeEditor.setContent('');
	}



	
</script>



<style type="text/css">

	#recall_details_sub_container div {
		margin-bottom: 10px;
		font-size: 15px;
	}
	
	#recall_details_sub_container div a {
		margin-left: 5px;
	}
	
	#recall_details_sub_container span {
		font-size: 15px;
		padding-left: 7px;
	}
	
	.calendar {
		display: inline-flex;
	}
	
	.requestor {
		float: left;
		display: inline-block;

	}
	
	.clear {
		clear: both;
	}
	
	.padding {
		padding-right: 5px;
	}
	
	.wpsc_loading_icon {
		margin-top: 0px !important;
	}
	
	.fa-snowflake {
		color: #009ACD;
	}
	
</style>
