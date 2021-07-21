<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// set default filter for agents and customers //not true
global $wpdb, $current_user, $wpscfunction;

if (!$current_user->ID) die();

$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '';
$status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
$ext_shipping_r3 = isset($_POST['ext_shipping_r3_bool']) ? sanitize_text_field($_POST['ext_shipping_r3_bool']) : '';

$ext_shipping_r3_bool = ( $ext_shipping_r3 == 'true' ) ? true : false;

$prev_status = $wpscfunction->get_ticket_fields( $ticket_id, 'ticket_status' );
$initial_review_term = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses'); 
$shipping_term = get_term_by('slug', 'awaiting-agent-reply', 'wpsc_statuses'); 

// Only change status if it is in Initial review complete. AND the status is the term_id for shipping
if( $prev_status == $initial_review_term->term_id && $shipping_term->term_id == $status ) {
  
  // Set ticket table to status shipped
  $table = $wpdb->prefix . "wpsc_ticket";
  $data_where = array( 'id' => $ticket_id );
  $data_update = array( 'ticket_status' => $shipping_term->term_id );

  $check = $wpdb->update( $table, $data_update, $data_where );
  
  // Set shipping table to mark 'shipped' as 1
  $table = $wpdb->prefix . "wpsc_epa_shipping_tracking";
  $data_where = array( 'ticket_id' => $ticket_id );
  $data_update = array( 'shipped' => 1 );

  $check2 = $wpdb->update( $table, $data_update, $data_where );

}


//
// if Shipping Tracking number is "r3 external" then update ticketmeta 
//

// check if need insert or update
$table = $wpdb->prefix . "wpsc_ticketmeta";
$sql = "SELECT * FROM " . $table . " WHERE ticket_id = " . $ticket_id . " AND meta_key = 'r3_preset'";

$ticketmeta = $wpdb->get_row( $sql );

$add_or_update = '';

if( isset( $ticketmeta->meta_key) ) {
  $add_or_update = 'update';
} else {
  $add_or_update = 'add';
}
  
// if 'r3 external' used, then add or update 'true', else add or update 'false'
if( $ext_shipping_r3_bool == true ) {
  
  // Set ticket table to status shipped
  $table = $wpdb->prefix . "wpsc_ticketmeta";
  
  if( $add_or_update == 'update' ) {
  
    $data = array( 
      'meta_value' => 1
    );
    
    $where = array( 
      'ticket_id' => $ticket_id,
      'meta_key' => 'r3_preset',
    );
    
    $check3 = $wpdb->update( $table, $data, $where );
    
  } elseif( $add_or_update == 'add' ) {  
    
    $data = array( 
      'ticket_id' => $ticket_id,
      'meta_key' => 'r3_preset',
      'meta_value' => 1
    );

    $check3 = $wpdb->insert( $table, $data );
  }
} else {
  
  $table = $wpdb->prefix . "wpsc_ticketmeta";
  
  if( $add_or_update == 'update' ) {
  
    $data = array( 
      'meta_value' => 0
    );
    
    $where = array( 
      'ticket_id' => $ticket_id,
      'meta_key' => 'r3_preset',
    );
    
    $check3 = $wpdb->update( $table, $data, $where );
    
  } elseif( $add_or_update == 'add' ) {  
    
    $data = array( 
      'ticket_id' => $ticket_id,
      'meta_key' => 'r3_preset',
      'meta_value' => 0
    );

    $check3 = $wpdb->insert( $table, $data );
  }
}


$output = array(
  'ticket_id'   => $ticket_id,
  'status' => $status,
  'prev_status' => $prev_status,
  'initial_review_term' => $initial_review_term,
  'shipping_term' => $shipping_term,
  'check' => $check,
  'check2' => $check2,
  'check3' => $check3,
  'ticketmeta' => $ticketmeta,
  'add_or_update' => $add_or_update
);
echo json_encode($output);
