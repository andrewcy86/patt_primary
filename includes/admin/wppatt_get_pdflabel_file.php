<?php
// Code to inject label button

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;
include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

$flag_btn = false;

$current_agent_id      = $wpscfunction->get_current_user_agent_id();

$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '' ;
$ticket_data = $wpscfunction->get_ticket($ticket_id);
$status_id   	= $ticket_data['ticket_status'];

$is_active = Patt_Custom_Func::ticket_active( $ticket_id );
// Change Status ID when going to production to reflect the term_id of the "New" status

//using slug instead of status ID
$status_array = array($request_new_request_tag->term_id, $request_initial_review_rejected_tag->term_id, $request_cancelled_tag->term_id, $request_completed_dispositioned_tag->term_id);
if (!in_array($status_id, $status_array)) {
    $flag_btn = true;
}

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

$request_id_sql = $wpdb->get_row("SELECT a.request_id as request_id
FROM " . $wpdb->prefix . "wpsc_ticket a
WHERE a.id = " . $ticket_id);
$request_id = $request_id_sql->request_id;

if($flag_btn):

?>

<?php
if($is_active == 1 && $request_id) {
?>
	<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_pdf_label_btn" style="<?php echo $action_default_btn_css ?>" onclick="wpsc_get_pdf_label_field(<?php echo $ticket_id?>)"><i class="fas fa-tags" aria-hidden="true" title="Print Label"></i><span class="sr-only">Print Label</span> Print Label</button>
<?php } ?>

<?php if($recall_obj->recall_id){ ?>
  			<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_pdf_label_btn" style="<?php echo $action_default_btn_css ?>" onclick="wpsc_get_recall_pdf_label_field(<?php echo $recall_obj->recall_id . ", " . $recall_obj->ticket_id?>)"><i class="fas fa-tags" aria-hidden="true" title="Print Label"></i><span class="sr-only">Print Label</span> Print Label</button>
	<?php } ?>
		<script>
		function wpsc_get_pdf_label_field(ticket_id){
		  wpsc_modal_open('Labels');
		  var data = {
		    action: 'wpsc_get_pdf_label_field',
		    ticket_id: ticket_id
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });  
		}
          
        function wpsc_get_recall_pdf_label_field(recall_id, ticket_id){
		  wpsc_modal_open('Labels');
		  var data = {
		    action: 'wpsc_get_pdf_label_field',
            recall_id: recall_id,
            ticket_id: ticket_id
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });  
		}
	</script>
	<?php
endif;
