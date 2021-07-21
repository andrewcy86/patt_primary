<?php
// Code to inject label button

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction;

$flag_btn = false;

$current_agent_id      = $wpscfunction->get_current_user_agent_id();

$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '' ;
$ticket_data = $wpscfunction->get_ticket($ticket_id);
$status_id   	= $ticket_data['ticket_status'];

$is_active = Patt_Custom_Func::ticket_active( $ticket_id );
// Change Status ID when going to production to reflect the term_id of the "New" status

//using slug instead of status ID
$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses');
$new_request_term_id = $new_request_tag->term_id;

$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses');
$initial_review_rejected_term_id = $initial_review_rejected_tag->term_id;

$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses');
$cancelled_term_id = $cancelled_tag->term_id;

$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
$tabled_term_id = $tabled_tag->term_id;

$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003
$completed_dispositioned_term_id = $completed_dispositioned_tag->term_id;

//$status_array = array(3, 670, 69, 2763);
$status_array = array($new_request_term_id, $initial_review_rejected_term_id, $cancelled_term_id, $completed_dispositioned_term_id);
if (!in_array($status_id, $status_array)) {
    $flag_btn = true;
}

if($flag_btn):

?>

<?php
if($is_active == 1) {
?>
	<button type="button" class="btn btn-sm wpsc_action_btn" id="wpsc_pdf_label_btn" style="<?php echo $action_default_btn_css ?>" onclick="wpsc_get_pdf_label_field(<?php echo $ticket_id?>)"><i class="fas fa-tags"></i> Print Label</button>
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
	</script>
	<?php
endif;
