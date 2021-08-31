<?php
// Code to inject label button

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction;

$current_agent_id      = $wpscfunction->get_current_user_agent_id();
$agent_permissions = $wpscfunction->get_current_agent_permissions();

$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '' ;
$ticket_data = $wpscfunction->get_ticket($ticket_id);
$status_id   	= $ticket_data['ticket_status'];

$is_active = Patt_Custom_Func::ticket_active( $ticket_id );

// Change Status ID when going to production to reflect the term_id of the "New" status

$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses');
$initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses');
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses');
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003

//$status_id_arr = array('3','670','69');
$status_id_arr = array($new_request_tag->term_id, $initial_review_rejected_tag->term_id, $cancelled_tag->term_id, $completed_dispositioned_tag->term_id);

 if (!(in_array($status_id, $status_id_arr)) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet')) && $is_active == 1) {
?>
<button type="button" class="btn btn-sm wpsc_action_btn" id="wppatt_change_pallet_btn" style="<?php echo $action_default_btn_css ?>" ><i class="fas fa-dolly-flatbed" aria-hidden="true" title="Pallet Assignment"></i><span class="sr-only">Pallet Assignment</span> Pallet Assignment</button>
<?php
}   
?>    
