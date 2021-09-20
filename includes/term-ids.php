<?php

//Digitization Center Tags
$dc_not_assigned_tag = get_term_by('slug', 'not-assigned-digi-center', 'wpsc_categories'); //666
$dc_east_tag = get_term_by('slug', 'e', 'wpsc_categories'); //62
$dc_west_tag = get_term_by('slug', 'w', 'wpsc_categories'); //2
$dc_east_cui_tag = get_term_by('slug', 'ecui', 'wpsc_categories'); //663
$dc_west_cui_tag = get_term_by('slug', 'wcui', 'wpsc_categories'); //664

//Priority Tags
$priority_not_assigned_tag = get_term_by('slug', 'not-assigned', 'wpsc_priorities'); //621
$priority_normal_tag = get_term_by('slug', 'low', 'wpsc_priorities'); //7
$priority_high_tag = get_term_by('slug', 'medium', 'wpsc_priorities'); //8
$priority_critical_tag = get_term_by('slug', 'high', 'wpsc_priorities'); //9

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
$box_pending_tag = get_term_by('slug', 'pending', 'wpsc_box_statuses'); //748
$box_scanning_preparation_tag = get_term_by('slug', 'scanning-preparation', 'wpsc_box_statuses'); //672
$box_scanning_digitization_tag = get_term_by('slug', 'scanning-digitization', 'wpsc_box_statuses'); //671
$box_qa_qc_tag = get_term_by('slug', 'q-a', 'wpsc_box_statuses'); //65
$box_digitized_not_validated_tag = get_term_by('slug', 'closed', 'wpsc_box_statuses'); //6
$box_ingestion_tag = get_term_by('slug', 'ingestion', 'wpsc_box_statuses'); //673
$box_completed_permanent_records_tag = get_term_by('slug', 'completed', 'wpsc_box_statuses'); //66
$box_validation_tag = get_term_by('slug', 'verification', 'wpsc_box_statuses'); //674
$box_destruction_approved_tag = get_term_by('slug', 'destruction-approval', 'wpsc_box_statuses'); //68
$box_destruction_of_source_tag = get_term_by('slug', 'destruction-of-source', 'wpsc_box_statuses'); //1272
$box_completed_dispositioned_tag = get_term_by('slug', 'completed-dispositioned', 'wpsc_box_statuses'); //1258
$box_rescan_tag = get_term_by('slug', 're-scan', 'wpsc_box_statuses'); //743
$box_waiting_shelved_tag = get_term_by('slug', 'waiting-shelved', 'wpsc_box_statuses'); //816
$box_waiting_on_rlo_tag = get_term_by('slug', 'waiting-on-rlo', 'wpsc_box_statuses'); //1056
$box_cancelled_tag = get_term_by('slug', 'cancelled', 'wpsc_box_statuses'); //1057

//Recall Tags
$recall_recalled_tag = get_term_by('slug', 'recalled', 'wppatt_recall_statuses'); //729
$recall_recall_approved_tag = get_term_by('slug', 'recall-approved', 'wppatt_recall_statuses'); //877
$recall_recall_denied_tag = get_term_by('slug', 'recall-denied', 'wppatt_recall_statuses'); //878<<
$recall_shipped_tag = get_term_by('slug', 'shipped', 'wppatt_recall_statuses'); //730
$recall_on_loan_tag = get_term_by('slug', 'on-loan', 'wppatt_recall_statuses'); //731
$recall_shipped_back_tag = get_term_by('slug', 'shipped-back', 'wppatt_recall_statuses'); //732
$recall_recall_complete_tag = get_term_by('slug', 'recall-complete', 'wppatt_recall_statuses'); //733<<
$recall_recall_cancelled_tag = get_term_by('slug', 'recall-cancelled', 'wppatt_recall_statuses'); //734<<

//Decline Tags
$decline_decline_initiated_tag = get_term_by('slug', 'decline-initiated', 'wppatt_return_statuses'); //752
$decline_decline_shipped_tag = get_term_by('slug', 'decline-shipped', 'wppatt_return_statuses'); //753
$decline_received_tag = get_term_by('slug', 'decline-pending-cancel', 'wppatt_return_statuses'); //754<<
$decline_decline_shipped_back_tag = get_term_by('slug', 'decline-shipped-back', 'wppatt_return_statuses'); //1024
$decline_decline_complete_tag = get_term_by('slug', 'decline-complete', 'wppatt_return_statuses'); //1023<<
$decline_decline_cancelled_tag = get_term_by('slug', 'decline-cancelled', 'wppatt_return_statuses'); //791<<
$decline_decline_expired_tag = get_term_by('slug', 'decline-expired', 'wppatt_return_statuses'); //2726<<