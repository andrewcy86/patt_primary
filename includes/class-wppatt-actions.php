<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'WPPATT_Actions' ) ) :
  
  final class wppatt_Actions {

    // constructor
    public function __construct() {
      add_action( 'init', array( $this, 'load_actions') );
    }
    
     // Load actions
    function load_actions() {
      
      // PATT Log Entry
      add_action( 'wpppatt_after_freeze', array($this,'freeze_document'), 10, 2 );
      add_action( 'wpppatt_after_freeze_unflag', array($this,'unfreeze_document'), 10, 2 );
      add_action( 'wpppatt_after_damaged', array($this,'damaged_document'), 10, 2 );
      add_action( 'wpppatt_after_damaged_unflag', array($this,'reverse_damaged_document'), 10, 2 );
      add_action( 'wpppatt_after_box_destruction', array($this,'box_destruction'), 10, 2 );
      add_action( 'wpppatt_after_box_destruction_unflag', array($this,'unflag_box_destruction'), 10, 2 );
           
      add_action( 'wpppatt_after_unauthorized_destruction', array($this,'unauthorized_destruction'), 10, 2 );
      add_action( 'wpppatt_after_unauthorized_destruction_unflag', array($this,'unauthorized_destruction_unflag'), 10, 2 );
      add_action( 'wpppatt_after_shelf_location', array($this,'shelf_location'), 10, 3 );   
      add_action( 'wpppatt_after_digitization_center', array($this,'digitization_center'), 10, 3 );   
      add_action( 'wpppatt_after_validate_document', array($this,'validate_document'), 10, 2 );
      add_action( 'wpppatt_after_invalidate_document', array($this,'invalidate_document'), 10, 2 );
      add_action( 'wpppatt_after_rescan_document', array($this,'rescan_document'), 10, 2 );
      add_action( 'wpppatt_after_undo_rescan_document', array($this,'undo_rescan_document'), 10, 2 );
      add_action( 'wpppatt_after_add_request_shipping_tracking', array($this,'add_request_shipping_tracking'), 10, 2 );
      add_action( 'wpppatt_after_modify_request_shipping_tracking', array($this,'modify_request_shipping_tracking'), 10, 2 );
      add_action( 'wpppatt_after_remove_request_shipping_tracking', array($this,'remove_request_shipping_tracking'), 10, 2 );
      
      add_action( 'wpppatt_after_box_metadata', array($this,'box_metadata'), 10, 3 );
      add_action( 'wpppatt_after_folder_doc_metadata', array($this,'folder_doc_metadata'), 10, 3 );
      
      add_action( 'wpppatt_after_recall_request_date', array( $this, 'recall_request_date' ), 10, 3); 
      add_action( 'wpppatt_after_recall_received_date', array( $this, 'recall_received_date' ), 10, 3); 
      add_action( 'wpppatt_after_recall_returned_date', array( $this, 'recall_returned_date' ), 10, 3); 
      add_action( 'wpppatt_after_recall_requestor', array( $this, 'recall_requestor' ), 10, 3); 
      add_action( 'wpppatt_after_recall_details_shipping', array( $this, 'recall_details_shipping' ), 10, 3);  
      add_action( 'wpppatt_after_recall_cancelled', array( $this, 'recall_cancelled' ), 10, 2); 
      add_action( 'wpppatt_after_recall_approved', array( $this, 'recall_approved' ), 10, 2); 
      add_action( 'wpppatt_after_recall_denied', array( $this, 'recall_denied' ), 10, 2); 
      add_action( 'wpppatt_after_recall_created', array( $this, 'recall_created' ), 10, 4); 
      add_action( 'wpppatt_after_recall_completed', array( $this, 'recall_completed' ), 10, 3); 
      add_action( 'wpppatt_after_return_cancelled', array( $this, 'return_cancelled' ), 10, 2); 
      add_action( 'wpppatt_after_return_created', array( $this, 'return_created' ), 10, 3);
      add_action( 'wpppatt_after_return_expiration_date_extended', array( $this, 'return_extended' ), 10, 2);
      add_action( 'wpppatt_after_return_completed', array( $this, 'return_completed' ), 10, 2);
      
      add_action( 'wpppatt_after_box_status_update', array( $this, 'box_status_update' ), 10, 3);      
      add_action( 'wpppatt_after_box_status_agents', array( $this, 'box_status_agents_update' ), 10, 3);             
      add_action( 'wpppatt_after_return_details_shipping', array( $this, 'return_details_shipping' ), 10, 3);        
      
      add_action( 'wpppatt_after_assign_pallet', array($this,'assign_pallet'), 10, 3 );
      add_action( 'wpppatt_after_unassign_pallet', array($this,'unassign_pallet'), 10, 3 ); 
      
      add_action( 'wpppatt_after_assign_box_location', array($this,'assign_box_location'), 10, 4 );
      add_action( 'wpppatt_after_assign_pallet_location', array($this,'assign_pallet_location'), 10, 4 ); 
      
    }
    
    // Re-scan
    function rescan_document ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s flagged Document ID: %2$s for re-scanning','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s flagged for re-scanning','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }

    // Undo Re-scan
    function undo_rescan_document ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s unflagged Document ID: %2$s for re-scanning','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s unflagged for re-scanning','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }

    // Damaged document
    function damaged_document ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s flagged Document ID: %2$s as damaged','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s flagged as damaged','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Reverse damaged
    function reverse_damaged_document ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s un-flagged Document ID: %2$s as damaged','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s un-flagged as damaged','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Freeze document
    function freeze_document ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s froze Document ID: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s frozen','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Reverse freeze
    function unfreeze_document ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s unfroze Document ID: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s has been unfrozen','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }

    // Destroy Box
    function box_destruction ( $ticket_id, $box_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s destroyed Box ID: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $box_id .'</strong>');
      } else {
        $log_str = sprintf( __('Box ID %1$s has been destroyed','supportcandy'), '<strong>'.$box_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Reverse Box Destruction
    function unflag_box_destruction ( $ticket_id, $box_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s removed destruction flag on Box ID: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $box_id .'</strong>');
      } else {
        $log_str = sprintf( __('Box ID %1$s destruction flag removed','supportcandy'), '<strong>'.$box_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Change destruction
    function unauthorized_destruction ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s flagged Document ID: %2$s as unauthorized destruction','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s flagged as unauthorized destruction','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Reverse destruction
    function unauthorized_destruction_unflag ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s un-flagged Document ID: %2$s as unauthorized destruction','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s un-flagged as unauthorized destruction','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Change Shelf Location
    function shelf_location ( $ticket_id, $box_id, $shelf_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s changed shelf location of Box ID: %2$s to %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $box_id .'</strong>','<strong>'. $shelf_id .'</strong>');
      } else {
        $log_str = sprintf( __('Box ID: %1$s has changed shelf location to %1$s ','supportcandy'), '<strong>'.$box_id.'</strong>','<strong>'. $shelf_id .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }

    // Change Digitization Center
    function digitization_center ( $ticket_id, $box_id, $dc ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s changed digitization center of Box ID: %2$s to %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $box_id .'</strong>','<strong>'. $dc .'</strong>');
      } else {
        $log_str = sprintf( __('Box ID: %1$s has changed digitization center to %1$s ','supportcandy'), '<strong>'.$box_id.'</strong>','<strong>'. $dc .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }

    // Validate
    function validate_document ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s validated Document ID: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s validated','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }

    // Invalidate
    function invalidate_document ( $ticket_id, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s invalidated Document ID: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('Document ID %1$s invalidated','supportcandy'), '<strong>'.$doc_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Add Shipping Tracking on Request
    function add_request_shipping_tracking ( $ticket_id, $tracking_number ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s added tracking number %2$s to request','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $tracking_number .'</strong>');
      } else {
        $log_str = sprintf( __('Tracking number %1$s added to request','supportcandy'), '<strong>'.$tracking_number.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }

    // Modified Shipping Tracking on Request
    function modify_request_shipping_tracking ( $ticket_id, $tracking_number ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s updated tracking number %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $tracking_number .'</strong>');
      } else {
        $log_str = sprintf( __('Tracking number %1$s updated','supportcandy'), '<strong>'.$tracking_number.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    // Removed Shipping Tracking on Request
    function remove_request_shipping_tracking ( $ticket_id, $tracking_number ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s removed tracking number %2$s from request','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $tracking_number .'</strong>');
      } else {
        $log_str = sprintf( __('Tracking number %1$s removed from request','supportcandy'), '<strong>'.$tracking_number.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    // Box Metadata edit
    function box_metadata ( $ticket_id, $metadata, $box_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s edited the following metadata %2$s on Box ID: %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $metadata .'</strong>','<strong>'. $box_id .'</strong>');
      } else {
        $log_str = sprintf( __('The following metadata %1$s on Box ID: %2$s has been edited','supportcandy'), '<strong>'.$metadata.'</strong>','<strong>'. $box_id .'</strong>');
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    // Folder/Document Metadata edit
    function folder_doc_metadata ( $ticket_id, $metadata, $doc_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s edited the following metadata %2$s on Document ID: %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $metadata .'</strong>','<strong>'. $doc_id .'</strong>');
      } else {
        $log_str = sprintf( __('The following metadata %1$s on Document ID: %2$s has been edited','supportcandy'), '<strong>'.$metadata.'</strong>','<strong>'. $doc_id .'</strong>');
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    // Recall Request Date Changed 
    function recall_request_date ( $ticket_id, $recall_id, $request_date ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s changed Recall Request Date of Recall ID: %2$s to %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>','<strong>'. $request_date .'</strong>');
      } else {
        $log_str = sprintf( __('Recall ID: %1$s has changed Request Date to %2$s ','supportcandy'), '<strong>'.$recall_id.'</strong>','<strong>'. $request_date .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    
    function recall_received_date ( $ticket_id, $recall_id, $received_date ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s changed Recall Received Date of Recall ID: %2$s to %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>','<strong>'. $received_date .'</strong>');
      } else {
        $log_str = sprintf( __('Recall ID: %1$s has changed Received Date to %2$s ','supportcandy'), '<strong>'.$recall_id.'</strong>','<strong>'. $received_date .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    
    function recall_returned_date ( $ticket_id, $recall_id, $returned_date ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s changed Recall Returned Date of Recall ID: %2$s to %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>','<strong>'. $returned_date .'</strong>');
      } else {
        $log_str = sprintf( __('Recall ID: %1$s has changed Returned Date to %2$s ','supportcandy'), '<strong>'.$recall_id.'</strong>','<strong>'. $returned_date .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    
    function recall_requestor ( $ticket_id, $recall_id, $recall_requestors ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s changed Recall Requestor of Recall ID: %2$s to %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>','<strong>'. $recall_requestors .'</strong>');
      } else {
        $log_str = sprintf( __('Recall ID: %1$s has changed Requestor to %2$s ','supportcandy'), '<strong>'.$recall_id.'</strong>','<strong>'. $recall_requestors .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    function recall_details_shipping ( $ticket_id, $recall_id, $new_shipping_tracking_carrier_string ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s changed Recall Shipping Details of Recall ID: %2$s to %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>','<strong>'. $new_shipping_tracking_carrier_string .'</strong>');
      } else {
        $log_str = sprintf( __('Recall ID: %1$s has changed Recall Shipping Details to %2$s ','supportcandy'), '<strong>'.$recall_id.'</strong>','<strong>'. $new_shipping_tracking_carrier_string .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
        
    
    function recall_cancelled ( $ticket_id, $recall_id ){
		global $wpscfunction, $current_user;
		if($current_user->ID){
			$log_str = sprintf( __('%1$s cancelled the Recall of: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>');
		} else {
			$log_str = sprintf( __('Recall ID: %1$s has been cancelled.','supportcandy'), '<strong>'.$recall_id.'</strong>' );
		}
		$args = array(
			'ticket_id'      => $ticket_id,
			'reply_body'     => $log_str,
			'thread_type'    => 'log'
		);
		$args = apply_filters( 'wpsc_thread_args', $args );
		$wpscfunction->submit_ticket_thread($args);
    }
    
    function recall_approved ( $ticket_id, $recall_id ){
		global $wpscfunction, $current_user;
		if($current_user->ID){
			$log_str = sprintf( __('%1$s approved the Recall of: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>');
		} else {
			$log_str = sprintf( __('Recall ID: %1$s has been approved.','supportcandy'), '<strong>'.$recall_id.'</strong>' );
		}
		$args = array(
			'ticket_id'      => $ticket_id,
			'reply_body'     => $log_str,
			'thread_type'    => 'log'
		);
		$args = apply_filters( 'wpsc_thread_args', $args );
		$wpscfunction->submit_ticket_thread($args);
    }
    
    function recall_denied ( $ticket_id, $recall_id ){
		global $wpscfunction, $current_user;
		if($current_user->ID){
			$log_str = sprintf( __('%1$s denied the Recall of: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>');
		} else {
			$log_str = sprintf( __('Recall ID: %1$s has been denied.','supportcandy'), '<strong>'.$recall_id.'</strong>' );
		}
		$args = array(
			'ticket_id'      => $ticket_id,
			'reply_body'     => $log_str,
			'thread_type'    => 'log'
		);
		$args = apply_filters( 'wpsc_thread_args', $args );
		$wpscfunction->submit_ticket_thread($args);
    }
    

    function recall_created ( $ticket_id, $recall_id, $item_id, $box_status_message ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s has recalled %3$s. Recall ID: %2$s. <br> %4$s ','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>', '<strong>'.$item_id.'</strong>', $box_status_message );
      } else {
        $log_str = sprintf( __('%1$s has been recalled. Recall ID: %2$s. %3$s ','supportcandy'), '<strong>'.$item_id.'</strong>', '<strong>'.$recall_id.'</strong>', $box_status_message );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    function recall_completed ( $ticket_id, $recall_id, $item_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('Recall ID: %2$s is completed. ','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $recall_id .'</strong>', '<strong>'.$item_id.'</strong>' );
      } else {
        $log_str = sprintf( __('Recall ID: %2$s is completed. ','supportcandy'), '<strong>'.$item_id.'</strong>', '<strong>'.$recall_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    function return_cancelled ( $ticket_id, $return_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s cancelled the Decline of: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $return_id .'</strong>');
      } else {
        $log_str = sprintf( __('Decline ID: %1$s has been cancelled.','supportcandy'), '<strong>'.$return_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    function return_created ( $ticket_id, $return_id, $item_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s has Declined %3$s. Decline ID: %2$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $return_id .'</strong>', '<strong>'.$item_id.'</strong>' );
      } else {
        $log_str = sprintf( __('%1$s has been Declined. Decline ID: %2$s ','supportcandy'), '<strong>'.$item_id.'</strong>', '<strong>'.$return_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    function return_completed ( $ticket_id, $return_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('Decline ID: %2$s is completed. ','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>', '<strong>'. $return_id .'</strong>' );
      } else {
        $log_str = sprintf( __('Decline ID: %1$s is completed. ','supportcandy'), '<strong>'.$recall_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    function box_status_update ( $ticket_id, $status, $item_id ){ 
		global $wpscfunction, $current_user,$wpdb;
		if($current_user->ID){
			$log_str = sprintf( __('%1$s has changed the status of Box: %3$s from %2$s.','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $status .'</strong>', '<strong>'.$item_id.'</strong>' );
		} else {
			$log_str = sprintf( __('%1$s status has been changed from %2$s.','supportcandy'), '<strong>'.$item_id.'</strong>', '<strong>'.$status.'</strong>' );
		}
		
		$args = array(
		'ticket_id'      => $ticket_id,
		'reply_body'     => $log_str,
		'thread_type'    => 'log'
		);
		
		$args = apply_filters( 'wpsc_thread_args', $args );
		$wpscfunction->submit_ticket_thread($args);
        
        //PATT BEGIN FOR REPORTING
      // Define current time
      $date_time = date('Y-m-d H:i:s');
      // This is for Time to process intial request report
      // Check to see if timestamp exists
      $box_id = Patt_Custom_Func::convert_box_id($item_id);
     $table_timestamp = $wpdb->prefix . 'wpsc_epa_timestamps_box';
     $get_box_timestamp = $wpdb->get_row("select id, count(id) as count from " . $table_timestamp . " where box_id = '".$box_id."'");
     $box_timestamp_id = $get_box_timestamp->id;
     $box_timestamp_count = $get_box_timestamp->count;
     $status_new = substr($status, strpos($status, "to") + 3);
     /*
     // Delete previous value
      if($box_timestamp_count > 0) {
          $wpdb->delete( $table_timestamp, array( 'id' => $box_timestamp_id ) );
      }
     */
      $wpdb->insert($table_timestamp, array('box_id' => $box_id, 'type' => $status_new, 'user' => $current_user->display_name, 'timestamp' => $date_time) ); 
    }
    
    
    function box_status_agents_update ( $ticket_id, $status_and_users, $item_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s has changed the Assigned Staff of Box: %3$s. Assigned Staff per status: %2$s.','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $status_and_users .'</strong>', '<strong>'.$item_id.'</strong>' );
      } else {
        $log_str = sprintf( __('Box: %1$s Assigned Staff has been changed to %2$s.','supportcandy'), '<strong>'.$item_id.'</strong>', '<strong>'.$status_and_users.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    function return_details_shipping ( $ticket_id, $return_id, $new_shipping_tracking_carrier_string ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s changed Decline Shipping Details of Decline ID: %2$s to %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $return_id .'</strong>','<strong>'. $new_shipping_tracking_carrier_string .'</strong>');
      } else {
        $log_str = sprintf( __('Decline ID: %1$s Decline Shipping Details have changed to %2$s ','supportcandy'), '<strong>'.$return_id.'</strong>','<strong>'. $new_shipping_tracking_carrier_string .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
/*    
    function return_created( $ticket_id, $return_id, $item_name_and_id_str ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s Declined %3$s. Decline ID: %2$s.','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $return_id .'</strong>','<strong>'. $item_name_and_id_str .'</strong>');
      } else {
        $log_str = sprintf( __('Declined: %2$s. Decline ID: %1$s.','supportcandy'), '<strong>'.$return_id.'</strong>','<strong>'. $item_name_and_id_str .'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
   */ 
    function return_extended( $ticket_id, $return_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s extended Decline ID: %2$s expiration date by 30 days. ','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $return_id .'</strong>');
      } else {
        $log_str = sprintf( __('Decline ID: %1$s expiration date extended by 30 days','supportcandy'), '<strong>'.$return_id.'</strong>' );
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }

    //Box Physical Location Assignment
    function assign_box_location ( $ticket_id, $location_id, $box_id, $user_name ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s assigned Box ID: %2$s to Location ID: %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($user_name).'</strong>','<strong>'. $box_id .'</strong>', '<strong>'. $location_id .'</strong>');
      } else {
        $log_str = sprintf( __('Box ID %1$s assigned to Location ID: %2$s','supportcandy'), '<strong>'.$box_id.'</strong>', '<strong>'.$location_id.'</strong>');
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    //Pallet Physical Location Assignment
    function assign_pallet_location ( $ticket_id, $location_id, $pallet_id, $user_name ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s assigned Pallet ID: %2$s to Location ID: %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($user_name).'</strong>','<strong>'. $pallet_id .'</strong>', '<strong>'. $location_id .'</strong>');
      } else {
        $log_str = sprintf( __('Pallet ID %1$s assigned to Location ID: %2$s','supportcandy'), '<strong>'.$pallet_id.'</strong>', '<strong>'.$location_id.'</strong>');
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    //Bulk Pallet Assignment
    function assign_pallet ( $ticket_id, $box_id, $pallet_id ){
      global $wpscfunction, $current_user;
      if($current_user->ID){
        $log_str = sprintf( __('%1$s assigned Box ID: %2$s to Pallet ID: %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $box_id .'</strong>', '<strong>'. $pallet_id .'</strong>');
      } else {
        $log_str = sprintf( __('Box ID %1$s assigned to Pallet ID: %2$s','supportcandy'), '<strong>'.$box_id.'</strong>', '<strong>'.$pallet_id.'</strong>');
      }
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    //Bulk Pallet Unassignment
    function unassign_pallet ( $ticket_id, $box_id, $pallet_id){
      global $wpscfunction, $current_user;
      

       if($current_user->ID){
        $log_str = sprintf( __('%1$s unassigned Box ID: %2$s from Pallet ID: %3$s','supportcandy'), '<strong>'.Patt_Custom_Func::get_full_name_by_customer_name($current_user->display_name).'</strong>','<strong>'. $box_id .'</strong>', '<strong>'. $pallet_id .'</strong>');
      } else {
        $log_str = sprintf( __('Box ID %1$s unassigned from Pallet ID: %2$s','supportcandy'), '<strong>'.$box_id.'</strong>', '<strong>'.$pallet_id.'</strong>');
      }         
          
      
      $args = array(
        'ticket_id'      => $ticket_id,
        'reply_body'     => $log_str,
        'thread_type'    => 'log'
      );
      $args = apply_filters( 'wpsc_thread_args', $args );
      $wpscfunction->submit_ticket_thread($args);
    }
    
    
}
  
endif;

new wppatt_Actions();