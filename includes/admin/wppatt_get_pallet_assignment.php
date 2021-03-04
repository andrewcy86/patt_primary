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

$new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69
$inital_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670

?>

    <button type="button" class="btn btn-sm wpsc_action_btn" id="wppatt_change_pallet_btn" style="<?php echo $action_default_btn_css ?>" ><i class="fas fa-dolly-flatbed"></i> Pallet Assignment</button>

