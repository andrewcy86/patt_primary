<?php


$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$host = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
$connect = new PDO($host, DB_USER, DB_PASSWORD);

$method = $_SERVER['REQUEST_METHOD'];

global $wpdb, $current_user, $wpscfunction;

if($method == 'GET')
{

 $data = array(
  ':company_name'   => "%" . $_GET['company_name'] . "%",
  ':tracking_number'   => "%" . $_GET['tracking_number'] . "%",
  ':status'     => "%" . $_GET['status'] . "%",
  ':ticket_id'    => $_GET['ticket_id']
 );

 $query = 'SELECT * FROM ' . $wpdb->prefix . 'wpsc_epa_shipping_tracking WHERE company_name LIKE :company_name AND tracking_number LIKE :tracking_number AND status LIKE :status AND ticket_id = :ticket_id ORDER BY id DESC';

 $statement = $connect->prepare($query);
 $statement->execute($data);
 $result = $statement->fetchAll();
 foreach($result as $row)
 {

  $tracking_num = '';
  
  if (substr( strtoupper($row['tracking_number']), 0, 4 ) === "DHL:") {
    $tracking_num = substr($row['tracking_number'], 4);
  } else {
    $tracking_num = $row['tracking_number'];
  }

  $output[] = array(
   'id'    => $row['id'],
   'ticket_id'    => $row['ticket_id'], 
   'company_name'  => $row['company_name'],
   'tracking_number'   =>  $tracking_num,
   'status'    => $row['status']
  );
 }
 header("Content-Type: application/json");
 echo json_encode($output);
}

if($method == "POST")
{
  
  // Check for Duplicates
  if( $_POST["type"] == 'check_dups' ) {
    
    $table = $wpdb->prefix . "wpsc_epa_shipping_tracking";
    $sql = "SELECT id, tracking_number, company_name FROM " . $table . " WHERE ticket_id = " . $_GET['ticket_id'];
    
    $tracking_nums = $wpdb->get_results( $sql );
    
    //$is_dup = false;
/*
    $test = '';
    
    foreach( $tracking_nums as $tn ) {
      if( $_POST["tracking_number"] == $tn->tracking_number ) {
        $is_dup = true;
      }
      
      $test .= $tn->tracking_number . ' - ';
    }
*/
    
    $output = array(
     'ticket_id'    => $_GET['ticket_id'],
     //'is_duplicate'    => $is_dup,
     'results' => $tracking_nums,
     'tracking_nums' => $tracking_nums,
     //'test' => $test
    );
   
    header("Content-Type: application/json");
    echo json_encode($output);
    
    exit();
    
    
  } else {  
  // INSERT tracking number
  
    $tracking_num = '';
  
    $data = array(
      ':ticket_id'  => $_GET['ticket_id'],
      //':company_name'  => Patt_Custom_Func::get_shipping_carrier($_POST["tracking_number"]),
      ':company_name'  => $_POST["company_name"],
      ':tracking_number'    => $_POST["tracking_number"]
    );
   
    $prev_status = $wpscfunction->get_ticket_fields( $ticket_id, 'ticket_status' );
    $initial_review_term = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses'); 
    
    // if using External shipping service, update shipped column as well. 
    if( $_POST["tracking_number"] == WPPATT_EXT_SHIPPING_TERM && $prev_status == $initial_review_term ) {
      $query = "INSERT INTO " . $wpdb->prefix . "wpsc_epa_shipping_tracking (ticket_id, company_name, status, shipped, tracking_number, recallrequest_id, return_id) VALUES (:ticket_id, :company_name, '', 1, :tracking_number, '-99999',  '-99999')";
    } else {
      $query = "INSERT INTO " . $wpdb->prefix . "wpsc_epa_shipping_tracking (ticket_id, company_name, status, tracking_number, recallrequest_id, return_id) VALUES (:ticket_id, :company_name, '', :tracking_number, '-99999',  '-99999')";
  }
  
   $statement = $connect->prepare($query);
   $statement->execute($data);
   do_action('wpppatt_after_add_request_shipping_tracking', $_GET['ticket_id'], strtoupper($_POST["company_name"]).' - '.$_POST["tracking_number"]);
   
  }
 
}

if($method == 'PUT')
{
 parse_str(file_get_contents("php://input"), $_PUT);

 $data = array(
  ':id'   => $_PUT['id'],
  //':company_name'  => Patt_Custom_Func::get_shipping_carrier($_POST["tracking_number"]),
  ':company_name'  => $_POST["company_name"],
  ':tracking_number' => $_PUT['tracking_number']
 );
 $query = "
 UPDATE " . $wpdb->prefix . "wpsc_epa_shipping_tracking 
 SET
 company_name = :company_name, 
 tracking_number = :tracking_number
 WHERE id = :id
 ";
 $statement = $connect->prepare($query);
 $statement->execute($data);
  do_action('wpppatt_after_modify_request_shipping_tracking', $_GET['ticket_id'], strtoupper($_PUT["company_name"]).' - '.$_PUT["tracking_number"]);
}

if($method == "DELETE")
{
 parse_str(file_get_contents("php://input"), $_DELETE);
 $query = "DELETE FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking WHERE id = '".$_DELETE["id"]."'";
 $statement = $connect->prepare($query);
 $statement->execute();
  do_action('wpppatt_after_remove_request_shipping_tracking', $_GET['ticket_id'], strtoupper($_DELETE["company_name"]).' - '.$_DELETE["tracking_number"]);
}

?>