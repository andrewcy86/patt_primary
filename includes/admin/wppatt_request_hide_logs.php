<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

$agent_permissions = $wpscfunction->get_current_agent_permissions();

/*if (!($current_user->ID && $current_user->has_cap('wpsc_agent'))) {
		exit;
}*/
$ticket_id 	 = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0 ;
$raisedby_email = $wpscfunction->get_ticket_fields($ticket_id, 'customer_email');
$wpsc_appearance_modal_window = get_option('wpsc_modal_window');
$wpsc_appearance_ticket_list = get_option('wpsc_appearance_ticket_list');

$current_agent_id  = $wpscfunction->get_current_user_agent_id();

$request_id = Patt_Custom_Func::convert_request_db_id($ticket_id);
$is_active = Patt_Custom_Func::request_status( $request_id );


$restrict_rules = array(
	'relation' => 'AND',
	array(
		'key'            => 'customer_email',
		'value'          => $raisedby_email,
		'compare'        => '='
	),
	array(
		'key'            => 'active',
		'value'          => 1,
		'compare'        => '='
	)
);
$ticket_permission = array(
	'relation' => 'OR'
);
if ($agent_permissions['view_unassigned']) {
	$ticket_permission[] = array(
		'key'            => 'assigned_agent',
		'value'          => 0,
		'compare'        => '='
	);
}

if ($agent_permissions['view_assigned_me']) {
	$ticket_permission[] = array(
		'key'            => 'assigned_agent',
		'value'          => $current_agent_id,
		'compare'        => '='
	);
}

if ($agent_permissions['view_assigned_others']) {
	$ticket_permission[] = array(
		'key'            => 'assigned_agent',
		'value'          => array(0,$current_agent_id),
		'compare'        => 'NOT IN'
	);
}

$restrict_rules [] = $ticket_permission;
$select_str        = 'DISTINCT t.*';
$sql               = $wpscfunction->get_sql_query( $select_str, $restrict_rules);
$tickets           = $wpdb->get_results($sql);
$ticket_list       = json_decode(json_encode($tickets), true);

$ticket_list_items = get_terms([
  'taxonomy'   => 'wpsc_ticket_custom_fields',
  'hide_empty' => false,
  'orderby'    => 'meta_value_num',
  'meta_key'	 => 'wpsc_tl_agent_load_order',
  'order'    	 => 'ASC',
  'meta_query' => array(
    'relation' => 'AND',
    array(
      'key'       => 'wpsc_allow_ticket_list',
      'value'     => '1',
      'compare'   => '='
    ),
    array(
      'key'       => 'wpsc_agent_ticket_list_status',
      'value'     => '1',
      'compare'   => '='
    ),
  ),
]);
ob_start();
?>
<div class="wpsc_thread_audit wpsc_it_body">
		<div class="row wpsc_threads_container">
		    <?php
		    //audit log backup placeholder
            $get_all_csv_files = $wpdb->get_results("SELECT b.post_date, b.post_date_gmt, a.meta_value
            FROM wpqa_postmeta a
            INNER JOIN wpqa_posts b ON b.ID = a.post_id
            WHERE a.meta_value LIKE '%".$ticket_id."_log_backup%' ORDER BY b.post_date DESC");
            
            if(count($get_all_csv_files) > 0) { ?>
                <div class="wpsc_thread_audit"><h4>Archived History: </h4>
                <ul>
            <?php
            foreach($get_all_csv_files as $key) { 
                $filename = explode("/", $key->meta_value);
                $date = new DateTime($key->post_date, new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('America/New_York'));

                ?>
               <li><a href="<?php echo WPPATT_UPLOADS_URL."backups/audit/".$filename[2] ?>" target="_blank">Audit log archived on <?php echo $date->format('m-d-Y h:i:s a'); ?> EST
                <i class="fas fa-download" title="Download Archived Request Log"></i></a></li>
            <?php }
            }
		    ?>
		    </ul>
		    </div>
		    <?php 
		    if ($is_active == 1) {
		    ?>
		    <div class="wpsc_thread_audit logtitle"><h4>Request History: <a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/print_log.php?id=' . htmlentities($ticket_id); ?>" target="_blank"><i class="fas fa-print" title="Print Request Log"></i></a></h4></div>

			<?php
			$order = $reply_form_position ? 'ASC' : 'DESC';
			$args = array(
				'post_type'      => 'wpsc_ticket_thread',
				'post_status'    => 'publish',
				'orderby'        => 'post_date',
				'order'          => $order,
				'posts_per_page' => -1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'ticket_id',
			      'value'   => $ticket_id,
			      'compare' => '='
					)
				)
			);
			$threads = get_posts($args);
			
			if(apply_filters('wpsc_print_thread',true)){	
			foreach ($threads as $thread):
				$reply = stripslashes(htmlspecialchars_decode($thread->post_content, ENT_QUOTES));
				$reply = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $reply);

				$thread_type    = get_post_meta( $thread->ID, 'thread_type', true);
				$customer_name  = get_post_meta( $thread->ID, 'customer_name', true);
				$customer_email = get_post_meta( $thread->ID, 'customer_email', true);
				$attachments    = get_post_meta( $thread->ID, 'attachments', true);
				$ticket_id      = get_post_meta( $thread->ID,'ticket_id',true);
				$seen      			= get_post_meta( $thread->ID,'user_seen',true);
				
				if( $seen && $current_user->user_email == $ticket['customer_email'] && ($thread_type == 'report' || $thread_type == 'reply') ){
					update_post_meta($thread->ID, 'user_seen', date("Y-m-d H:i:s"));
				}

				if ( $thread_type == 'log' && ($current_user->ID || ($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))):
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
				
			  endforeach;
			?>
		</div>
		<?php } ?>
<div class="wpsc_thread_audit load">
<a href="#" id="loadMore"><i class="fas fa-sync"></i> Load More</a>
</div>
			<?php 
            }
            ?>
  </div>

</div>

<style>

.wpsc_thread_audit {
    display: table;
    width: 75%;
    padding: 10px;
    background-color: #FFFFFF;
}

.wpsc_thread_log {
    display:none;
}
.logtitle {
    display:none;
    margin-bottom: 10px;
    overflow: hidden;
}
.load {
    display:none;
    padding-left: 0px !important;
    text-align: center;
    overflow: hidden;
}
.display {
	display: inline-block;
}
.totop {
    position: fixed;
    bottom: 10px;
    right: 20px;
}
.totop a {
    display: none;
}

#loadMore {
    float: left;
    padding: 10px;
    margin: 0 0 20px 20px;
    background-color: #33739E;
    color: #fff;
    border-width: 0 1px 1px 0;
    border-style: solid;
    border-color: #fff;
    box-shadow: 0 1px 1px #ccc;
    transition: all 600ms ease-in-out;
    -webkit-transition: all 600ms ease-in-out;
    -moz-transition: all 600ms ease-in-out;
    -o-transition: all 600ms ease-in-out;
}
#loadMore:hover {
    background-color: #fff;
    color: #33739E;
}
</style>

<script>
if (jQuery(".wpsc_thread_log")[0]){
jQuery(".logtitle").addClass('display');
}

if (jQuery(".wpsc_thread_log")[7]){
jQuery(".load").addClass('display');
}

jQuery(function () {
    jQuery(".wpsc_thread_log").slice(0, 8).addClass('display');
    jQuery("#loadMore").on('click', function (e) {
        e.preventDefault();
        jQuery(".wpsc_thread_log:hidden").slice(0, 8).addClass('display');
        if (jQuery(".wpsc_thread_log:hidden").length == 0) {
            jQuery("#load").fadeOut('slow');
        }
        jQuery('html,body').animate({
            scrollTop: jQuery(this).offset().top
        }, 1500);
    });
});

jQuery(window).scroll(function () {
    if (jQuery(this).scrollTop() > 50) {
        jQuery('.totop a').fadeIn();
    } else {
        jQuery('.totop a').fadeOut();
    }
});
</script>