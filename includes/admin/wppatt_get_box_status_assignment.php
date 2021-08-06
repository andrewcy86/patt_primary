<?php
// Code to inject label button

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction;

$flag_btn = false;

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
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003

//$status_array = array(3, 670, 69);
$status_array = array($new_request_tag->term_id, $inital_review_rejected_tag->term_id, $cancelled_tag->term_id, $tabled_tag->term_id, $completed_dispositioned_tag->term_id);
if (!in_array($status_id, $status_array)) {
    $flag_btn = true;
}

if($flag_btn):

?>

<?php //echo $status_id ?>
    <?php		
    if ( (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Manager')) && $is_active == 1)
    {
    ?>
	<button type="button" class="btn btn-sm wpsc_action_btn" id="wppatt_assign_staff_btn" style="<?php echo $action_default_btn_css ?>" ><i class="fas fa-user-plus" aria-hidden="true" title="Assign Staff"></i><span class="sr-only">Assign Staff</span> Assign Staff</button>
	<?php
    }
    ?>
    
    <?php
    if ( (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))  && $is_active == 1) {
    ?>
    <button type="button" class="btn btn-sm wpsc_action_btn" id="wppatt_change_status_btn" style="<?php echo $action_default_btn_css ?>" ><i class="fas fa-heartbeat" aria-hidden="true" title="Assign Box Status"></i><span class="sr-only">Assign Box Status</span> Assign Box Status <a href="#" aria-label="Assign Box Status" data-toggle="tooltip" data-placement="right" data-html="true" title="<?php echo Patt_Custom_Func::helptext_tooltip('help-assign-box-status'); ?>"><i class="far fa-question-circle" aria-hidden="true" title="Help"></i><span class="sr-only">Help</span></a></button>
    <?php } ?>
	<?php
endif;
?>