<?php

//Digitization Center Tags
$dc_not_assigned_tag = get_term_by('slug', 'not-assigned-digi-center', 'wpsc_categories'); //666
$dc_east_tag = get_term_by('slug', 'e', 'wpsc_categories'); //62
$dc_west_tag = get_term_by('slug', 'w', 'wpsc_categories'); //2
$dc_east_cui_tag = get_term_by('slug', 'ecui', 'wpsc_categories'); //663
$dc_west_cui_tag = get_term_by('slug', 'wcui', 'wpsc_categories'); //664

//Request Level Status Tags
$request_new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
$request_tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses'); //2763
$request_initial_review_complete_tag = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses'); //4
$request_initial_review_rejected_tag = get_term_by('slug', 'initial-review-rejected', 'wpsc_statuses'); //670
$request_shipped_tag = get_term_by('slug', 'awaiting-agent-reply', 'wpsc_statuses'); //5
$request_received_tag = get_term_by('slug', 'received', 'wpsc_statuses'); //63
$request_in_process_tag = get_term_by('slug', 'in-process', 'wpsc_statuses'); //997
$request_ecms_tag = get_term_by('slug', 'ecms', 'wpsc_statuses'); //998
$request_sems_tag = get_term_by('slug', 'sems', 'wpsc_statuses'); //1010
$request_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_statuses'); //1003
$request_cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses'); //69

//Box Level Status Tags
$box_pending_tag = get_term_by('slug', 'pending', 'wpsc_box_statuses');
$box_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses');
$box_cancelled_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses');
$box_destruction_of_source_tag = get_term_by('slug', 'destruction-of-source', 'wpsc_box_statuses'); //1272

//Priority Tags
$priority_not_assigned_tag = get_term_by('slug', 'not-assigned', 'wpsc_priorities');
$priority_normal_tag = get_term_by('slug', 'low', 'wpsc_priorities');
$priority_high_tag = get_term_by('slug', 'medium', 'wpsc_priorities');
$prioritycritical_tag = get_term_by('slug', 'high', 'wpsc_priorities');

//Recall Tags

//Decline Tags
