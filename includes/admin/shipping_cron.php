<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $current_user, $wpscfunction, $wpdb;

//Change Status to Cancelled if Review Complete Timelapse exceeds 14 days
$timelapse_query = $wpdb->get_results(
"SELECT ticket_id, meta_value
FROM " . $wpdb->prefix . "wpsc_ticketmeta
WHERE meta_key = 'review_complete_timestamp'"
);
foreach ($timelapse_query as $item) {

$t=time();
$timestamp = $item->meta_value;

$date1 = date('Y-m-d',$timestamp);
$date2 = date('Y-m-d',$t);

$diff = abs(strtotime($date2) - strtotime($date1));

$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));

//$days_remaining = abs($days-14);

$cancelled_tag = get_term_by('slug', 'destroyed', 'wpsc_statuses');

if($days >= 14) {
// Change Request Status
$wpscfunction->change_status($item->ticket_id, $cancelled_tag->term_id);

// Change Box Status
$data_update = array('box_status' => 1057);
$data_where = array('ticket_id' => $item->ticket_id);
$wpdb->update($wpdb->prefix.'wpsc_epa_boxinfo', $data_update, $data_where);


}

}


$shippingArray = ["usps", "fedex", "ups", "dhl"];

// Begin going through the different shipping carriers
foreach ($shippingArray as $shippingCompany)  {

switch ($shippingCompany) {
    case "usps":

$shipping_query = $wpdb->get_results(
"SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE company_name = 'usps' AND (shipped = 0 OR delivered = 0)"
);

foreach ($shipping_query as $item) {

$trackingNumber = $item->tracking_number;

if($item->shipped == 0) {

$url = "http://production.shippingapis.com/shippingAPI.dll";
$service = "TrackV2";

$xml = rawurlencode("
<TrackFieldRequest USERID='".USPS."'>
    <TrackID ID=\"".$trackingNumber."\"></TrackID>
    </TrackFieldRequest>");

$request = $url . "?API=" . $service . "&XML=" . $xml;
// send the POST values to USPS
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL,$request);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_HTTPGET, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
// parameters to post

$result = curl_exec($curl);
//var_dump($result);

$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

curl_close($curl);

$err = Patt_Custom_Func::convert_http_error_code($status);

if ($status != 200) {
Patt_Custom_Func::insert_api_error('usps-shipping-cron',$status,$err);
}

$response = new SimpleXMLElement($result);

$deliveryStatus = $response->TrackInfo->TrackSummary->Event;

$status_shipped_array = array('CANCELLED', 'PRE-SHIPMENT', 'LABEL', 'EXPECTS', 'MERCHANT');

$status_delivered_array = array('DELIVERED');

$status_shipped = strtoupper($deliveryStatus);

$table_name = $wpdb->prefix .'wpsc_epa_shipping_tracking';


switch ($status_shipped) {
  case 'PICKED UP BY SHIPPING PARTNER, USPS AWAITING ITEM':
  // Shipping Status: display nothing if $deliveryStatus contains: Picked Up by Shipping Partner, USPS Awaiting Item
    break; 
  case (preg_match('('.implode('|',$status_shipped_array).')', strtoupper($deliveryStatus)) ? true : false):
  // Shipping Status: display nothing if $deliveryStatus contains: Cancelled/pre-shipment/label/expects/merchant
    break;
  case (preg_match('('.implode('|',$status_delivered_array).')', strtoupper($deliveryStatus)) ? true : false):
  // Shipping Status: display "Delivered"  if $deliveryStatus contains: Delivered 
$wpdb->update( $table_name, array( 'delivered' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'shipped' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'status' => $status_shipped),array('ID'=>$item->id));
    break;
   default:
  // Shipping Status: display "Shipped" if $deliveryStatus contains: <<none of the above values
$wpdb->update( $table_name, array( 'shipped' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'status' => $status_shipped),array('ID'=>$item->id));
      break;
}

}
}
        break;
    case "fedex":
$shipping_query = $wpdb->get_results(
"SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE company_name = 'fedex' AND (shipped = 0 OR delivered = 0)"
);

foreach ($shipping_query as $item) {
    //Set SuperGlobal ID variable to be used in all functions below

$trackingNumber = $item->tracking_number;

if($item->shipped == 0) {
    
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://ws.fedex.com:443/web-services",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS =>"
  <SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns=\"http://fedex.com/ws/track/v18\">\r\n
  <SOAP-ENV:Body>\r\n
  <TrackRequest>\r\n
  <WebAuthenticationDetail>\r\n
  <UserCredential>\r\n
  <Key>".FEDEX."</Key>\r\n
  <Password>".FEDEX_PASS."</Password>\r\n
  </UserCredential>\r\n
  </WebAuthenticationDetail>\r\n
  <ClientDetail>\r\n
  <AccountNumber>".FEDEX_ACCT."</AccountNumber>\r\n
  <MeterNumber>".FEDEX_METER."</MeterNumber>\r\n
  </ClientDetail>\r\n
  <TransactionDetail>\r\n
  <CustomerTransactionId>Track By Number_v18</CustomerTransactionId>\r\n
  <Localization>\r\n
  <LanguageCode>EN</LanguageCode>\r\n
  </Localization>\r\n
  </TransactionDetail>\r\n
  <Version>\r\n
  <ServiceId>trck</ServiceId>\r\n
  <Major>18</Major>\r\n
  <Intermediate>0</Intermediate>\r\n
  <Minor>0</Minor>\r\n
  </Version>\r\n
  <SelectionDetails>\r\n
  <PackageIdentifier>\r\n
  <Type>TRACKING_NUMBER_OR_DOORTAG</Type>\r\n
  <Value>".$trackingNumber."</Value>\r\n
  </PackageIdentifier>\r\n
  </SelectionDetails>\r\n
  <ProcessingOptions>INCLUDE_DETAILED_SCANS</ProcessingOptions>\r\n
  </TrackRequest>\r\n
  </SOAP-ENV:Body>\r\n
  </SOAP-ENV:Envelope>",
  CURLOPT_HTTPHEADER => array(
    "Content-Type: application/xml"
  ),
));

$response = curl_exec($curl);

$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

curl_close($curl);

$err = Patt_Custom_Func::convert_http_error_code($status);

if ($status != 200) {
Patt_Custom_Func::insert_api_error('fedex-shipping-cron',$status,$err);
}

$xml = new SimpleXMLElement($response);
$body = $xml->xpath('//SOAP-ENV:Body')[0];
$array = json_decode(json_encode((array)$body), TRUE);

$deliveryCode = $array['TrackReply']['CompletedTrackDetails']['TrackDetails']['StatusDetail']['Code'];
$deliveryStatus = $array['TrackReply']['CompletedTrackDetails']['TrackDetails']['StatusDetail']['Description'].' : '
.$array['TrackReply']['CompletedTrackDetails']['TrackDetails']['StatusDetail']['CreationTime'];

//New status codes, needs testing
$status_delivered_array = array('AD', 'AR', 'DL');
$status_shipped_array = array('AA','AC','AF','AP','AR','AX','CC','CD','CH','CP','DD','DE','DR','DS','DY','EA','ED','EO','EP','FD','HL','IT','IX','LO','OC','OD','OF','OX','IP','PD','PF','PL','PM','PU','PX','SE','SF','SP','TR');
//$status_delivered_array = array('AD', 'AR', 'DL');
//$status_shipped_array = array('PF', 'AA', 'PL', 'AC', 'PM', 'PU', 'AF', 'PX', 'AP', 'AR', 'CH', 'DD', 'DE', 'SE', 'DR', 'SF', 'DY', 'TR', 'EA', 'ED', 'CC', 'EO', 'CD', 'CP', 'IP');
$table_name = $wpdb->prefix . 'wpsc_epa_shipping_tracking';

if ( preg_match('('.implode('|',$status_shipped_array).')', strtoupper($deliveryCode))){
$wpdb->update( $table_name, array( 'shipped' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'status' => $deliveryStatus),array('ID'=>$item->id));
}

if ( preg_match('('.implode('|',$status_delivered_array).')', strtoupper($deliveryCode))){
$wpdb->update( $table_name, array( 'delivered' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'shipped' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'status' => $deliveryStatus),array('ID'=>$item->id));
}

}

}
        break;
    case "ups":
$shipping_query = $wpdb->get_results(
"SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE company_name = 'ups' AND (shipped = 0 OR delivered = 0)"
);

foreach ($shipping_query as $item) {
    //Set SuperGlobal ID variable to be used in all functions below

$trackingNumber = $item->tracking_number;

if($item->shipped == 0) {

$data ="<?xml version=\"1.0\"?>
        <AccessRequest xml:lang='en-US'>
                <AccessLicenseNumber>".UPS_LICENSE."</AccessLicenseNumber>
                <UserId>".UPS."</UserId>
                <Password>".UPS_PASS."</Password>
        </AccessRequest>
        <?xml version=\"1.0\"?>
        <TrackRequest>
                <Request>
                        <TransactionReference>
                                <CustomerContext>
                                        <InternalKey>blah</InternalKey>
                                </CustomerContext>
                                <XpciVersion>1.0</XpciVersion>
                        </TransactionReference>
                        <RequestAction>Track</RequestAction>
                </Request>
        <TrackingNumber>".$trackingNumber."</TrackingNumber>
        </TrackRequest>";
$curl = curl_init("https://onlinetools.ups.com/ups.app/xml/Track");
curl_setopt($curl, CURLOPT_HEADER, 1);
curl_setopt($curl,CURLOPT_POST,1);
curl_setopt($curl,CURLOPT_TIMEOUT, 60);
curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
$result=curl_exec ($curl);
// echo '<!-- '. $result. ' -->';
$data = strstr($result, '<?');
$xml_parser = xml_parser_create();
xml_parse_into_struct($xml_parser, $data, $vals, $index);
xml_parser_free($xml_parser);
$array = array();
$level = array();
foreach ($vals as $xml_elem) {
 if ($xml_elem['type'] == 'open') {
if (array_key_exists('attributes',$xml_elem)) {
         list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
} else {
         $level[$xml_elem['level']] = $xml_elem['tag'];
}
 }
 if ($xml_elem['type'] == 'complete') {
$start_level = 1;
$php_stmt = '$array';
while($start_level < $xml_elem['level']) {
         $php_stmt .= '[$level['.$start_level.']]';
         $start_level++;
}
$php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
eval($php_stmt);
 }
}

$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

curl_close($curl);

$err = Patt_Custom_Func::convert_http_error_code($status);

if ($status != 200) {
Patt_Custom_Func::insert_api_error('ups-shipping-cron',$status,$err);
}

//print_r($array);

$deliveryCode = $array['TRACKRESPONSE']['SHIPMENT']['PACKAGE']['ACTIVITY']['STATUS']['STATUSTYPE']['CODE'];
$deliveryStatus = $array['TRACKRESPONSE']['SHIPMENT']['PACKAGE']['ACTIVITY']['STATUS']['STATUSTYPE']['DESCRIPTION'].' : '
.$array['TRACKRESPONSE']['SHIPMENT']['PACKAGE']['ACTIVITY']['GMTDATE'].'T'
.$array['TRACKRESPONSE']['SHIPMENT']['PACKAGE']['ACTIVITY']['GMTTIME'];

$status_delivered_array = array('D');
$status_shipped_array = array('I', 'X', 'P', 'M');
$table_name = $wpdb->prefix . 'wpsc_epa_shipping_tracking';

if ( preg_match('('.implode('|',$status_shipped_array).')', strtoupper($deliveryCode))){
$wpdb->update( $table_name, array( 'shipped' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'status' => $deliveryStatus),array('ID'=>$item->id));
}

if ( preg_match('('.implode('|',$status_delivered_array).')', strtoupper($deliveryCode))){
$wpdb->update( $table_name, array( 'delivered' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'shipped' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'status' => $deliveryStatus),array('ID'=>$item->id));
}

}

}
    case "dhl":
$shipping_query = $wpdb->get_results(
"SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE company_name = 'dhl' AND (shipped = 0 OR delivered = 0)"
);

foreach ($shipping_query as $item) {
    //Set SuperGlobal ID variable to be used in all functions below

$trackingNumber = substr($item->tracking_number, 4);

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api-eu.dhl.com/track/shipments?trackingNumber=".$trackingNumber,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_HTTPHEADER => array(
    "DHL-API-Key: ".DHL."",
    "cache-control: no-cache"
  ),
));

$response = curl_exec($curl);
$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

curl_close($curl);

$err = Patt_Custom_Func::convert_http_error_code($status);

if ($status != 200) {
  Patt_Custom_Func::insert_api_error('dhl-shipping-cron',$status,$err);
}

$json = json_decode($response, true);

$deliveryStatus = $json['shipments']['0']['status']['description'].' : '
.$json['shipments']['0']['status']['timestamp'];

$status_delivered_array = array('DELIVERY', 'DELIVERED', 'HOME', 'PICKED', 'FRONT', 'DOOR', 'PORCH');
$status_shipped_array = array('PICKED','ARRIVAL','ARRIVED','RECEIVED','PROCESSED','ACCEPTED','DEPARTED','DEPARTURE','DEPART','EN ROUTE', 'PROCESSED', 'TENDERED', 'TRANSIT','ARRIVAL','ARRIVED','PROCESSED','LOADED','CUSTOMS');
$table_name = $wpdb->prefix . 'wpsc_epa_shipping_tracking';

if ( preg_match('('.implode('|',$status_shipped_array).')', strtoupper($deliveryStatus))){
$wpdb->update( $table_name, array( 'shipped' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'status' => $deliveryStatus),array('ID'=>$item->id));
}

if ( preg_match('('.implode('|',$status_delivered_array).')', strtoupper($deliveryStatus))){
$wpdb->update( $table_name, array( 'delivered' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'shipped' => 1),array('ID'=>$item->id));
$wpdb->update( $table_name, array( 'status' => $deliveryStatus),array('ID'=>$item->id));
}


}
        break;
}

        }
        
$get_unique_tickets = $wpdb->get_results(
	"SELECT DISTINCT ticket_id
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking WHERE ticket_id != '-99999'"
);

foreach ($get_unique_tickets as $item) {

// Change the status of request from Initial Review Complete to Shipped
$shipped_array = array();
$delivered_array = array();

$ticket_id = $item->ticket_id ;
$ticket_data = $wpscfunction->get_ticket($ticket_id);
$status_id   	= $ticket_data['ticket_status'];

$get_shipped_status = $wpdb->get_results(
 	"SELECT shipped
 FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
 WHERE ticket_id = " . $item->ticket_id
 );

$review_complete_tag = get_term_by('slug', 'awaiting-customer-reply', 'wpsc_statuses');
$shipped_tag = get_term_by('slug', 'awaiting-agent-reply', 'wpsc_statuses');
$received_tag = get_term_by('slug', 'received', 'wpsc_statuses');


foreach ($get_shipped_status as $shipped) {
	array_push($shipped_array, $shipped->shipped);
	}
	
/*if (($status_id == $review_complete_tag->term_id) && (!in_array(0, $shipped_array))) {
$wpscfunction->change_status($item->ticket_id, $shipped_tag->term_id);   
}*/

$get_delivered_status = $wpdb->get_results(
 	"SELECT delivered
 FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
 WHERE ticket_id = " . $item->ticket_id
 );

foreach ($get_delivered_status as $delivered) {
	array_push($delivered_array, $delivered->delivered);
	}

// Taking out ($status_id == 5) from the if statement for testing. 
// If a delivered tracking number is used the status does not update correctly. Typically, this won't be the case.
/*if (!in_array(0, $delivered_array) && ($status_id != $received_tag->term_id) && ($status_id == $shipped_tag->term_id)) {
$wpscfunction->change_status($item->ticket_id, $received_tag->term_id);  
}*/

// IF SHIPPED ONLY AND NOT DELIVERED
if ( $status_id != $shipped_tag->term_id && (!in_array(0, $shipped_array)) && (in_array(0, $delivered_array))) {
$wpscfunction->change_status($item->ticket_id, $shipped_tag->term_id);
// Remove review timestamp
sleep(1);
$wpscfunction->delete_ticket_meta($item->ticket_id,'review_complete_timestamp',true);

}

// IF SHIPPED AND DELIVERED 
if ((!in_array(0, $delivered_array)) && (!in_array(0, $shipped_array))) {
if ($status_id == $review_complete_tag->term_id) {
$wpscfunction->change_status($item->ticket_id, $shipped_tag->term_id);
sleep(1);
$wpscfunction->change_status($item->ticket_id, $received_tag->term_id);
// Remove review timestamp
sleep(1);
$wpscfunction->delete_ticket_meta($item->ticket_id,'review_complete_timestamp',true);

} elseif ($status_id == $shipped_tag->term_id) {
$wpscfunction->change_status($item->ticket_id, $received_tag->term_id);
// Remove review timestamp
sleep(1);
$wpscfunction->delete_ticket_meta($item->ticket_id,'review_complete_timestamp',true);

}
}






	echo $ticket_id;	
	print_r($shipped_array);
	print_r($delivered_array);
	}

?>