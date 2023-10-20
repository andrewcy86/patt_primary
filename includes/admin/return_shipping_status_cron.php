<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// UPDATE to update database based on list of items that are listed as shipped '1'.

global $current_user, $wpscfunction, $wpdb;

//Get term_ids for recall status slugs - OLD
/*
$status_decline_initiated_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-initiated' );	 
$status_decline_shipped_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-shipped' ); 
$status_decline_pending_cancel_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-pending-cancel' );
$status_decline_shipped_back_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-shipped-back' ); 
$status_decline_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-complete' ); 
$status_decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' ); 
*/

// Set Dates
$current_date = date("Y-m-d"); 
$two_weeks_ago = Date('Y-m-d', strtotime('-14 days'));
$four_weeks_ago = Date('Y-m-d', strtotime('-30 days'));
$two_weeks_ahead = Date('Y-m-d', strtotime('14 days'));
$four_weeks_ahead = Date('Y-m-d', strtotime('30 days'));

//Get term_ids for Decline status slugs
if( !taxonomy_exists('wppatt_return_statuses') ) {
	$args = array(
		'public' => false,
		'rewrite' => false
	);
	register_taxonomy( 'wppatt_return_statuses', 'wpsc_ticket', $args );
} 

$status_decline_initiated_term_id = get_term_by( 'slug', 'decline-initiated', 'wppatt_return_statuses' );	 
$status_decline_shipped_term_id = get_term_by( 'slug', 'decline-shipped', 'wppatt_return_statuses' ); 
$status_decline_pending_cancel_term_id = get_term_by( 'slug', 'decline-pending-cancel', 'wppatt_return_statuses' );
$status_decline_shipped_back_term_id = get_term_by( 'slug', 'decline-shipped-back', 'wppatt_return_statuses' );
$status_decline_received_at_ndc_term_id = get_term_by( 'slug', 'decline-received-at-ndc', 'wppatt_return_statuses' ); 
$status_decline_complete_term_id = get_term_by( 'slug', 'decline-complete', 'wppatt_return_statuses' ); 
$status_decline_cancelled_term_id = get_term_by( 'slug', 'decline-cancelled', 'wppatt_return_statuses' ); 
$status_decline_expired_term_id = get_term_by( 'slug', 'decline-expired', 'wppatt_return_statuses' ); 

$status_decline_initiated_term_id = $status_decline_initiated_term_id->term_id;
$status_decline_shipped_term_id = $status_decline_shipped_term_id->term_id;
$status_decline_pending_cancel_term_id = $status_decline_pending_cancel_term_id->term_id;
$status_decline_shipped_back_term_id = $status_decline_shipped_back_term_id->term_id;
$status_decline_received_at_ndc_term_id = $status_decline_received_at_ndc_term_id->term_id;
$status_decline_complete_term_id = $status_decline_complete_term_id->term_id;
$status_decline_cancelled_term_id = $status_decline_cancelled_term_id->term_id;
$status_decline_expired_term_id = $status_decline_expired_term_id->term_id;


// Checking the status of shipping tracking number that is being used
$shippingArray = ["external", "usps", "fedex", "ups", "dhl"];

// Begin going through the different shipping carriers
foreach ($shippingArray as $shippingCompany) {
    switch ($shippingCompany) {
        case "external":
            $table_name = $wpdb->prefix . "wpsc_epa_shipping_tracking";
            $shipping_r3_query = $wpdb->get_results("SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE tracking_number = UPPER('" . WPPATT_EXT_SHIPPING_TERM_R3 . "')");
            foreach ($shipping_r3_query as $item) {
                $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
            }
        
            $shipping_query = $wpdb->get_results("SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE tracking_number LIKE UPPER('%" . WPPATT_EXT_SHIPPING_TERM . "%')");
        
            $shipped_tag = get_term_by("slug", "awaiting-agent-reply", "wpsc_statuses");
            $received_tag = get_term_by("slug", "received", "wpsc_statuses");
            $inprocess_tag = get_term_by("slug", "in-process", "wpsc_statuses");
            foreach ($shipping_query as $item) {
                $get_ticket_id = $item->ticket_id;
                $get_tracking_number = $item->tracking_number;
                //$status_id = Patt_Custom_Func::get_ticket_status($get_ticket_id);
                $ticket = $wpscfunction->get_ticket($get_ticket_id);
                $status_id = $ticket["ticket_status"];
              
                //Fixes bug where if the received status if skipped and in process is selected the request still gets marked as delieved in the database. Only applies to external shipping.
                // IS IT A R3 REQUEST...IF R3 AND RECEIVED SET FLAG TO RECEIVED
                if (strtoupper($get_tracking_number) == strtoupper(WPPATT_EXT_SHIPPING_TERM_R3) && ($status_id == $received_tag->term_id || $status_id == $inprocess_tag->term_id)) {
                    $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                }
                //GET STATUS OF REQUEST
                if (strtoupper($get_tracking_number) == strtoupper(WPPATT_EXT_SHIPPING_TERM) && ($status_id == $shipped_tag->term_id || $status_id == $inprocess_tag->term_id)) {
                    $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                }
                if (strtoupper($get_tracking_number) == strtoupper(WPPATT_EXT_SHIPPING_TERM) && ($status_id == $received_tag->term_id || $status_id == $inprocess_tag->term_id)) {
                    $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                }
            }
        break;
        case "usps":
            $shipping_query = $wpdb->get_results("SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE company_name = 'usps' AND (shipped = 0 OR delivered = 0)");
            foreach ($shipping_query as $item) {
                $trackingNumber = $item->tracking_number;

                if ($item->shipped == 0) {
                    $url = USPS_ENDPOINT;
                    $service = "TrackV2";
                    $xml = rawurlencode("
<TrackFieldRequest USERID='" . USPS . "'>
    <TrackID ID=\"" . $trackingNumber . "\"></TrackID>
    </TrackFieldRequest>");
                    $request = $url . "?API=" . $service . "&XML=" . $xml;
                    // send the POST values to USPS
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $request);
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt($curl, CURLOPT_HTTPGET, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    // parameters to post
                    $result = curl_exec($curl);
                    //var_dump($result);
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);
                    $err = Patt_Custom_Func::convert_http_error_code($status);
                    if ($status != 200 && $status != NULL) {
                        Patt_Custom_Func::insert_api_error("usps-shipping-cron", $status, $err);
                    }
                    $response = new SimpleXMLElement($result);
                    $deliveryStatus = $response->TrackInfo->TrackSummary->Event;
                    $status_shipped_array = ["CANCELLED", "PRE-SHIPMENT", "LABEL", "EXPECTS", "MERCHANT", ];
                    $status_delivered_array = ["DELIVERED"];
                    $status_shipped = strtoupper($deliveryStatus);
                    $table_name = $wpdb->prefix . "wpsc_epa_shipping_tracking";
                    switch ($status_shipped) {
                        case "PICKED UP BY SHIPPING PARTNER, USPS AWAITING ITEM":
                            // Shipping Status: display nothing if $deliveryStatus contains: Picked Up by Shipping Partner, USPS Awaiting Item
                            
                        break;
                        case preg_match("(" . implode("|", $status_shipped_array) . ")", strtoupper($deliveryStatus)) ? true : false:
                            // Shipping Status: display nothing if $deliveryStatus contains: Cancelled/pre-shipment/label/expects/merchant
                            
                        break;
                        case preg_match("(" . implode("|", $status_delivered_array) . ")", strtoupper($deliveryStatus)) ? true : false:
                            // Shipping Status: display "Delivered"  if $deliveryStatus contains: Delivered
                            $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                            $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                            $wpdb->update($table_name, ["status" => $status_shipped], ["ID" => $item->id]);
                        break;
                        default:
                            // Shipping Status: display "Shipped" if $deliveryStatus contains: <<none of the above values
                            $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                            $wpdb->update($table_name, ["status" => $status_shipped], ["ID" => $item->id]);
                        break;
                    }
                } else {
                    $url = USPS_ENDPOINT;
                    $service = "TrackV2";
                    $xml = rawurlencode("
    <TrackFieldRequest USERID='" . USPS . "'>
        <TrackID ID=\"" . $trackingNumber . "\"></TrackID>
        </TrackFieldRequest>");
                    $request = $url . "?API=" . $service . "&XML=" . $xml;
                    // send the POST values to USPS
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $request);
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt($curl, CURLOPT_HTTPGET, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    // parameters to post
                    $result = curl_exec($curl);
                    //var_dump($result);
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);
                  
                    $err = Patt_Custom_Func::convert_http_error_code($status);
                  
                    if ($status != 200 && $status != NULL) {
                        Patt_Custom_Func::insert_api_error("usps-shipping-cron", $status, $err);
                    }
                  
                    $response = new SimpleXMLElement($result);
                    $deliveryStatus = $response->TrackInfo->TrackSummary->Event;
                    $status_shipped_array = ["CANCELLED", "PRE-SHIPMENT", "LABEL", "EXPECTS", "MERCHANT", ];
                    $status_delivered_array = ["DELIVERED"];
                    $status_shipped = strtoupper($deliveryStatus);
                    $table_name = $wpdb->prefix . "wpsc_epa_shipping_tracking";
                    switch ($status_shipped) {
                        case "PICKED UP BY SHIPPING PARTNER, USPS AWAITING ITEM":
                            // Shipping Status: display nothing if $deliveryStatus contains: Picked Up by Shipping Partner, USPS Awaiting Item
                            
                        break;
                        case preg_match("(" . implode("|", $status_delivered_array) . ")", strtoupper($deliveryStatus)) ? true : false:
                            // Shipping Status: display "Delivered"  if $deliveryStatus contains: Delivered
                            $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                            $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                            $wpdb->update($table_name, ["status" => $status_shipped], ["ID" => $item->id]);
                        break;
                        default:
                            // Shipping Status: display "Delivered" if $deliveryStatus contains: <<none of the above values
                            $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                            $wpdb->update($table_name, ["status" => $status_shipped], ["ID" => $item->id]);
                        break;
                    }
                }
            }
        break;
        case "fedex":
            $shipping_query = $wpdb->get_results("SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE company_name = 'fedex' AND (shipped = 0 OR delivered = 0)");
            foreach ($shipping_query as $item) {
                //Set SuperGlobal ID variable to be used in all functions below
                $trackingNumber = $item->tracking_number;

                if ($item->shipped == 0) {
                    $curl = curl_init();
                    curl_setopt_array($curl, [CURLOPT_URL => FEDEX_ENDPOINT, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => "
  <SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns=\"http://fedex.com/ws/track/v18\">\r\n
  <SOAP-ENV:Body>\r\n
  <TrackRequest>\r\n
  <WebAuthenticationDetail>\r\n
  <UserCredential>\r\n
  <Key>" . FEDEX . "</Key>\r\n
  <Password>" . FEDEX_PASS . "</Password>\r\n
  </UserCredential>\r\n
  </WebAuthenticationDetail>\r\n
  <ClientDetail>\r\n
  <AccountNumber>" . FEDEX_ACCT . "</AccountNumber>\r\n
  <MeterNumber>" . FEDEX_METER . "</MeterNumber>\r\n
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
  <Value>" . $trackingNumber . "</Value>\r\n
  </PackageIdentifier>\r\n
  </SelectionDetails>\r\n
  <ProcessingOptions>INCLUDE_DETAILED_SCANS</ProcessingOptions>\r\n
  </TrackRequest>\r\n
  </SOAP-ENV:Body>\r\n
  </SOAP-ENV:Envelope>", CURLOPT_HTTPHEADER => ["Content-Type: application/xml"], ]);
                  
                    $response = curl_exec($curl);
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);
                    $err = Patt_Custom_Func::convert_http_error_code($status);
                    if ($status != 200 && $status != NULL) {
                        Patt_Custom_Func::insert_api_error("fedex-shipping-cron", $status, $err);
                    }
                  
                    $xml = new SimpleXMLElement($response);
                    $body = $xml->xpath("//SOAP-ENV:Body") [0];
                    $array = json_decode(json_encode((array)$body), true);
                    $deliveryCode = $array["TrackReply"]["CompletedTrackDetails"]["TrackDetails"]["StatusDetail"]["Code"];
                    $deliveryStatus = $array["TrackReply"]["CompletedTrackDetails"]["TrackDetails"]["StatusDetail"]["Description"] . " : " . $array["TrackReply"]["CompletedTrackDetails"]["TrackDetails"]["StatusDetail"]["CreationTime"];
                    //New status codes, needs testing
                    $status_delivered_array = ["AD", "AR", "DL"];
                    $status_shipped_array = ["AA", "AC", "AF", "AP", "AR", "AX", "CC", "CD", "CH", "CP", "DD", "DE", "DR", "DS", "DY", "EA", "ED", "EO", "EP", "FD", "HL", "IT", "IX", "LO", "OC", "OD", "OF", "OX", "IP", "PD", "PF", "PL", "PM", "PU", "PX", "SE", "SF", "SP", "TR", ];
                    //$status_delivered_array = array('AD', 'AR', 'DL');
                    //$status_shipped_array = array('PF', 'AA', 'PL', 'AC', 'PM', 'PU', 'AF', 'PX', 'AP', 'AR', 'CH', 'DD', 'DE', 'SE', 'DR', 'SF', 'DY', 'TR', 'EA', 'ED', 'CC', 'EO', 'CD', 'CP', 'IP');
                    $table_name = $wpdb->prefix . "wpsc_epa_shipping_tracking";
                  
                    if (preg_match("(" . implode("|", $status_shipped_array) . ")", strtoupper($deliveryCode))) {
                        $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["status" => $deliveryStatus], ["ID" => $item->id]);
                    }
                    if (preg_match("(" . implode("|", $status_delivered_array) . ")", strtoupper($deliveryCode))) {
                        $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["status" => $deliveryStatus], ["ID" => $item->id]);
                    }
                } else {
                    $curl = curl_init();
                    curl_setopt_array($curl, [CURLOPT_URL => FEDEX_ENDPOINT, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => "
    <SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns=\"http://fedex.com/ws/track/v18\">\r\n
    <SOAP-ENV:Body>\r\n
    <TrackRequest>\r\n
    <WebAuthenticationDetail>\r\n
    <UserCredential>\r\n
    <Key>" . FEDEX . "</Key>\r\n
    <Password>" . FEDEX_PASS . "</Password>\r\n
    </UserCredential>\r\n
    </WebAuthenticationDetail>\r\n
    <ClientDetail>\r\n
    <AccountNumber>" . FEDEX_ACCT . "</AccountNumber>\r\n
    <MeterNumber>" . FEDEX_METER . "</MeterNumber>\r\n
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
    <Value>" . $trackingNumber . "</Value>\r\n
    </PackageIdentifier>\r\n
    </SelectionDetails>\r\n
    <ProcessingOptions>INCLUDE_DETAILED_SCANS</ProcessingOptions>\r\n
    </TrackRequest>\r\n
    </SOAP-ENV:Body>\r\n
    </SOAP-ENV:Envelope>", CURLOPT_HTTPHEADER => ["Content-Type: application/xml"], ]);
                    $response = curl_exec($curl);
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);
                    $err = Patt_Custom_Func::convert_http_error_code($status);
                    if ($status != 200 && $status != NULL) {
                        Patt_Custom_Func::insert_api_error("fedex-shipping-cron", $status, $err);
                    }
                    $xml = new SimpleXMLElement($response);
                    $body = $xml->xpath("//SOAP-ENV:Body") [0];
                    $array = json_decode(json_encode((array)$body), true);
                    $deliveryCode = $array["TrackReply"]["CompletedTrackDetails"]["TrackDetails"]["StatusDetail"]["Code"];
                    $deliveryStatus = $array["TrackReply"]["CompletedTrackDetails"]["TrackDetails"]["StatusDetail"]["Description"] . " : " . $array["TrackReply"]["CompletedTrackDetails"]["TrackDetails"]["StatusDetail"]["CreationTime"];
                    //New status codes, needs testing
                    $status_delivered_array = ["AD", "AR", "DL"];
                    $status_shipped_array = ["AA", "AC", "AF", "AP", "AR", "AX", "CC", "CD", "CH", "CP", "DD", "DE", "DR", "DS", "DY", "EA", "ED", "EO", "EP", "FD", "HL", "IT", "IX", "LO", "OC", "OD", "OF", "OX", "IP", "PD", "PF", "PL", "PM", "PU", "PX", "SE", "SF", "SP", "TR", ];
                    //$status_delivered_array = array('AD', 'AR', 'DL');
                    //$status_shipped_array = array('PF', 'AA', 'PL', 'AC', 'PM', 'PU', 'AF', 'PX', 'AP', 'AR', 'CH', 'DD', 'DE', 'SE', 'DR', 'SF', 'DY', 'TR', 'EA', 'ED', 'CC', 'EO', 'CD', 'CP', 'IP');
                    $table_name = $wpdb->prefix . "wpsc_epa_shipping_tracking";
                    if (preg_match("(" . implode("|", $status_delivered_array) . ")", strtoupper($deliveryCode))) {
                        $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["status" => $deliveryStatus], ["ID" => $item->id]);
                    }
                }
            }
        break;
        case "ups":
            $shipping_query = $wpdb->get_results("SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE company_name = 'ups' AND (shipped = 0 OR delivered = 0)");
            foreach ($shipping_query as $item) {
                //Set SuperGlobal ID variable to be used in all functions below
                $trackingNumber = $item->tracking_number;

                if ($item->shipped == 0) {
                    $data = "<?xml version=\"1.0\"?>
        <AccessRequest xml:lang='en-US'>
                <AccessLicenseNumber>" . UPS_LICENSE . "</AccessLicenseNumber>
                <UserId>" . UPS . "</UserId>
                <Password>" . UPS_PASS . "</Password>
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
        <TrackingNumber>" . $trackingNumber . "</TrackingNumber>
        </TrackRequest>";
                    $curl = curl_init(UPS_ENDPOINT);
                    curl_setopt($curl, CURLOPT_HEADER, 1);
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    $result = curl_exec($curl);
                    // echo '<!-- '. $result. ' -->';
                    $data = strstr($result, "<?");
                    $xml_parser = xml_parser_create();
                    xml_parse_into_struct($xml_parser, $data, $vals, $index);
                    xml_parser_free($xml_parser);
                    $array = [];
                    $level = [];
                    foreach ($vals as $xml_elem) {
                        if ($xml_elem["type"] == "open") {
                            if (array_key_exists("attributes", $xml_elem)) {
                                list($level[$xml_elem["level"]], $extra,) = array_values($xml_elem["attributes"]);
                            } else {
                                $level[$xml_elem["level"]] = $xml_elem["tag"];
                            }
                        }
                        if ($xml_elem["type"] == "complete") {
                            $start_level = 1;
                            $php_stmt = '$array';
                            while ($start_level < $xml_elem["level"]) {
                                $php_stmt.= '[$level[' . $start_level . "]]";
                                $start_level++;
                            }
                            $php_stmt.= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                            eval($php_stmt);
                        }
                    }
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);

                    $err = Patt_Custom_Func::convert_http_error_code($status);
                    if ($status != 200 && $status != NULL) {
                        Patt_Custom_Func::insert_api_error("decline-ups-shipping-cron", $status, $err);
                    }
                    
                    //print_r($array);
                    $deliveryCode = $array["TRACKRESPONSE"]["SHIPMENT"]["PACKAGE"]["ACTIVITY"]["STATUS"]["STATUSTYPE"]["CODE"];
                    $deliveryStatus = $array["TRACKRESPONSE"]["SHIPMENT"]["PACKAGE"]["ACTIVITY"]["STATUS"]["STATUSTYPE"]["DESCRIPTION"] . " : " . $array["TRACKRESPONSE"]["SHIPMENT"]["PACKAGE"]["ACTIVITY"]["GMTDATE"] . "T" . $array["TRACKRESPONSE"]["SHIPMENT"]["PACKAGE"]["ACTIVITY"]["GMTTIME"];
                    $status_delivered_array = ["D"];
                    $status_shipped_array = ["I", "X", "P", "M"];
                    $table_name = $wpdb->prefix . "wpsc_epa_shipping_tracking";
                    if (preg_match("(" . implode("|", $status_shipped_array) . ")", strtoupper($deliveryCode))) {
                        $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["status" => $deliveryStatus], ["ID" => $item->id]);
                    }
                    if (preg_match("(" . implode("|", $status_delivered_array) . ")", strtoupper($deliveryCode))) {
                        $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["status" => $deliveryStatus], ["ID" => $item->id]);
                    }
                } else {
                    $data = "<?xml version=\"1.0\"?>
        <AccessRequest xml:lang='en-US'>
                <AccessLicenseNumber>" . UPS_LICENSE . "</AccessLicenseNumber>
                <UserId>" . UPS . "</UserId>
                <Password>" . UPS_PASS . "</Password>
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
        <TrackingNumber>" . $trackingNumber . "</TrackingNumber>
        </TrackRequest>";
                    $curl = curl_init(UPS_ENDPOINT);
                    curl_setopt($curl, CURLOPT_HEADER, 1);
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    $result = curl_exec($curl);
                    // echo '<!-- '. $result. ' -->';
                    $data = strstr($result, "<?");
                    $xml_parser = xml_parser_create();
                    xml_parse_into_struct($xml_parser, $data, $vals, $index);
                    xml_parser_free($xml_parser);
                    $array = [];
                    $level = [];
                    foreach ($vals as $xml_elem) {
                        if ($xml_elem["type"] == "open") {
                            if (array_key_exists("attributes", $xml_elem)) {
                                list($level[$xml_elem["level"]], $extra,) = array_values($xml_elem["attributes"]);
                            } else {
                                $level[$xml_elem["level"]] = $xml_elem["tag"];
                            }
                        }
                        if ($xml_elem["type"] == "complete") {
                            $start_level = 1;
                            $php_stmt = '$array';
                            while ($start_level < $xml_elem["level"]) {
                                $php_stmt.= '[$level[' . $start_level . "]]";
                                $start_level++;
                            }
                            $php_stmt.= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                            eval($php_stmt);
                        }
                    }
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);
                    $err = Patt_Custom_Func::convert_http_error_code($status);
                    if ($status != 200 && $status != NULL) {
                        Patt_Custom_Func::insert_api_error("decline-ups-shipping-cron", $status, $err);
                    }
                    //print_r($array);
                    $deliveryCode = $array["TRACKRESPONSE"]["SHIPMENT"]["PACKAGE"]["ACTIVITY"]["STATUS"]["STATUSTYPE"]["CODE"];
                    $deliveryStatus = $array["TRACKRESPONSE"]["SHIPMENT"]["PACKAGE"]["ACTIVITY"]["STATUS"]["STATUSTYPE"]["DESCRIPTION"] . " : " . $array["TRACKRESPONSE"]["SHIPMENT"]["PACKAGE"]["ACTIVITY"]["GMTDATE"] . "T" . $array["TRACKRESPONSE"]["SHIPMENT"]["PACKAGE"]["ACTIVITY"]["GMTTIME"];
                    $status_delivered_array = ["D"];
                    $status_shipped_array = ["I", "X", "P", "M"];
                    $table_name = $wpdb->prefix . "wpsc_epa_shipping_tracking";
                    if (preg_match("(" . implode("|", $status_delivered_array) . ")", strtoupper($deliveryCode))) {
                        $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                        $wpdb->update($table_name, ["status" => $deliveryStatus], ["ID" => $item->id]);
                    }
                }
            }
        case "dhl":
            $shipping_query = $wpdb->get_results("SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_shipping_tracking
WHERE company_name = 'dhl' AND (shipped = 0 OR delivered = 0)");
            foreach ($shipping_query as $item) {
                //Set SuperGlobal ID variable to be used in all functions below
                $trackingNumber = substr($item->tracking_number, 4);
                $curl = curl_init();
                curl_setopt_array($curl, [CURLOPT_URL => DHL_ENDPOINT . $trackingNumber, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 30, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_POSTFIELDS => "", CURLOPT_HTTPHEADER => ["DHL-API-Key: " . DHL . "", "cache-control: no-cache", ], ]);
                $response = curl_exec($curl);
                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                $err = Patt_Custom_Func::convert_http_error_code($status);
                if ($status != 200 || $status != 404 && ($status != NULL)) {
                    Patt_Custom_Func::insert_api_error("dhl-shipping-cron", $status, $err);
                }
                $json = json_decode($response, true);
                $deliveryStatus = $json["shipments"]["0"]["status"]["description"] . " : " . $json["shipments"]["0"]["status"]["timestamp"];
                $status_delivered_array = ["DELIVERY", "DELIVERED", "HOME", "PICKED", "FRONT", "DOOR", "PORCH", ];
                $status_shipped_array = ["PICKED", "ARRIVAL", "ARRIVED", "RECEIVED", "PROCESSED", "ACCEPTED", "DEPARTED", "DEPARTURE", "DEPART", "EN ROUTE", "PROCESSED", "TENDERED", "TRANSIT", "ARRIVAL", "ARRIVED", "PROCESSED", "LOADED", "CUSTOMS", ];
                $table_name = $wpdb->prefix . "wpsc_epa_shipping_tracking";
                if (preg_match("(" . implode("|", $status_shipped_array) . ")", strtoupper($deliveryStatus))) {
                    $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                    $wpdb->update($table_name, ["status" => $deliveryStatus], ["ID" => $item->id]);
                }
                if (preg_match("(" . implode("|", $status_delivered_array) . ")", strtoupper($deliveryStatus))) {
                    $wpdb->update($table_name, ["delivered" => 1], ["ID" => $item->id]);
                    $wpdb->update($table_name, ["shipped" => 1], ["ID" => $item->id]);
                    $wpdb->update($table_name, ["status" => $deliveryStatus], ["ID" => $item->id]);
                }
            }
        break;
    }
}


//
// For Decline Status to change from Decline Initiated to Decline Shipped
//
$shipped_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        ( ret.return_status_id = " . $status_decline_initiated_term_id . " OR ret.return_status_id = " . $status_decline_cancelled_term_id
      . ") ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Initiated [752] to Decline Shipped [753]
foreach ($shipped_return_status_query as $item) {
	
	// update Decline status to Decline Shipped [753]
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_shipped_term_id ]; //change status from Decline Initiated to Decline Shipped
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	
	// The return_complete column is being updated when the decline status is changed 
// 	$data_update_decline_completion = array('return_complete' => 1);
//     $data_where_decline_completions = array('id' => $return_id);
//     $wpdb->update($wpdb->prefix . 'wpsc_epa_return', $data_update_decline_completion, $data_where_decline_completions);
    
    
	// Update Decline (Return) ship date  when it is shipped.
	$where = [ 'id' => $return_id ];
	$current_datetime = date( "Y-m-d H:i:s" );
 	$data = [ 'return_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	
	// Prep Timestmp Table data. 
	// Get Decline obj
	
	$where = [
		'return_id' => $return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$return_array_key = array_key_first( $return_array );
	$return_obj = $return_array[ $return_array_key ];

	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	// WP user info.
	$user_num = $return_obj->user_id[0];
	$user_obj = get_user_by( 'id', $user_num );
	$user_login = $user_obj->data->display_name;
	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Decline Shipped',
		'user' => $user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
	// No PM Notification for shipping Decline
	
	// CORNER CASE
	// If return status was cancelled, but was still picked up, use the normal statuses, but send PM Message to admin and requestor
	if( $item->return_status == $status_decline_cancelled_term_id ) {
		
		//
		// PM Notification :: Decline Cancelled item shipped
		//
	
		// Get ticket_id based on return_id
		$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
		
		// Get owner of ticket. 
		$where = [ 'ticket_id' => $ticket_id ];
		$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
		$role_array_requester = [ 'Requester', 'Requester Pallet' ];
		$agent_id_array = Patt_Custom_Func::return_agent_ids_in_role( $agent_id_array, $role_array_requester);
		
		// Get all users on Decline (currently only the person who submitted it, and no way to add others)
		$where = [ 'return_id' => $return_id ]; // format: '0000002'
		$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
		$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
		
		// Redundant as only one initiator allowed on Decline currently (which is the person who submitted decline). 
		$role_array_admin = [ 'Administrator', 'Manager' ];
		$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_admin);
		
		// Combine Requester on Request with Admin's on Decline
		$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
		$pattagentid_array = array_unique( $pattagentid_array );
		
		$requestid = 'D-' . $return_id; 
		
		$data = [
	        //'item_id' => $requestid
	    ];
		$email = 1;
		
		$notification_post = 'email-decline-cancelled-but-shipped';
			
		// PM Notification to the Requestor / owner
		$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	

		
	}
	
}


//
// For Decline Status to change from Decline Shipped to Decline Received (equivolent to On Loan) (OLD: Decline Complete)
//
$return_complete_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        ret.return_status_id = " . $status_decline_shipped_term_id . 
      " ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Shipped to Received
foreach ($return_complete_return_status_query as $item) {
	
	// update Decline status to Decline Pending Cancelled
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_pending_cancel_term_id ]; 
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	

	
	// Clear shipping table data 
	// Reset the shipping details as the same id is used for shipping to requestor and back to digitization center.
	$data = [
		'company_name' => '',
		'tracking_number' => '',
		'shipped' => 0,
		'delivered' => 0,		
		'status' => ''
	];
	$where = [
		'return_id' => $return_id
	];

	$return_array = Patt_Custom_Func::update_return_shipping( $data, $where );	
	
	// Update Decline DB Received date
	$where = [ 'id' => $return_id ];
	$current_datetime = date("Y-m-d H:i:s");
	$data = [ 'received_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	// Update Decline DB expiration_date
	$where = [ 'id' => $return_id ];
	$data = [ 'expiration_date' => $four_weeks_ahead ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	
	
	
	// Prep Timestmp Table data. 
	// Get Decline obj
	
	$where = [
		'return_id' => $return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$return_array_key = array_key_first( $return_array );
	$return_obj = $return_array[ $return_array_key ];

	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	// WP user info.
	$user_num = $return_obj->user_id[0];
	$user_obj = get_user_by( 'id', $user_num );
	$user_login = $user_obj->data->display_name;

	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Received',
		'user' => $user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );

	

	//
	// PM Notification :: 4 week timer started
	//

	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-arrived-at-requester';
		
	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
}


//
// Decline 2 week PM Notification to fix box and ship back to Digitization Center
//
$return_pending_cancel_2week_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        ret.return_status_id = " . $status_decline_pending_cancel_term_id . 
    " AND 
    	expiration_date LIKE '" . $two_weeks_ahead . "%'  
      ORDER BY shipping.id ASC"
	);

	
// Decline 2 week PM Notification to fix box and ship back to Digitization Center
foreach ($return_pending_cancel_2week_status_query as $item) {
	
	//
	// PM Notification :: 2 week notice
	//
	$return_id = $item->return_id;	
	
	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-2-week-notification';
		
	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
}


//
// Check and Expire Declines that have not been updated after 4 weeks. 
//
$return_complete_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        ret.return_status_id = " . $status_decline_pending_cancel_term_id . 
    " AND 
    	expiration_date LIKE '" . $current_date . "%'  
      ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Pending Cancel to Decline Expired
foreach ($return_complete_return_status_query as $item) {
	
	// update Decline status from Decline Pending Cancel to Expired
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_expired_term_id ]; //change status from Decline Pending Cancel to Expired 
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	
	// Update Decline DB updated_date
	$where = [ 'id' => $return_id ];
	$current_datetime = date("Y-m-d H:i:s");
	$data = [ 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	// Set all boxes inside the Decline to have Box Status: Cancelled. 
	$where = [
		'return_id' => $item->return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	$return_obj = $return_array[0];
	$return_box_array = $return_obj->box_id;
	
	$cancelled_status_id = get_term_by( 'slug', 'cancelled', 'wpsc_box_statuses' ); //get id from slug
	foreach( $return_box_array as $box ) {
		$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
		$data_update = array('box_status' => $cancelled_status_id->term_id );
		
		$box_id = Patt_Custom_Func::get_box_file_details_by_id( $box )->Box_id_FK;
		$data_where = array('id' => $box_id);
		$wpdb->update($table_name, $data_update, $data_where);
	}
		
	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	// WP user info.
	$user_num = $return_obj->user_id[0];
	$user_obj = get_user_by( 'id', $user_num );
	$user_login = $user_obj->data->display_name;
	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Decline Cancelled',
		'user' => $user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	//
	// PM Notification :: Decline Cancelled
	//

	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-cancelled';
		
	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
}


//
// For Decline Status to change from Decline Pending Cancel to Decline Shipped Back
//
$shipped_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        ret.return_status_id = " . $status_decline_pending_cancel_term_id .
      " ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Pending Cancel to Decline Shipped Back
foreach ($shipped_return_status_query as $item) {
	
	// update Decline status to Decline Shipped Back
	$return_id = $item->return_id;	
	$where = [ 'id' => $return_id ];
	$data_status = [ 'return_status_id' => $status_decline_shipped_back_term_id ]; 
	$obj = Patt_Custom_Func::update_return_data( $data_status, $where );
	
	// Update Decline db table (Return) when it is shipped.
	$where = [ 'id' => $return_id ];
	$current_datetime = date("Y-m-d H:i:s");
 	$data = [ 'return_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_return_data( $data, $where );
	
	
	// Prep Timestmp Table data. 
	// Get Decline obj
	
	$where = [
		'return_id' => $return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$return_array_key = array_key_first( $return_array );
	$return_obj = $return_array[ $return_array_key ];

	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	// WP user info.
	$user_num = $return_obj->user_id[0];
	$user_obj = get_user_by( 'id', $user_num );
	$user_login = $user_obj->data->display_name;
	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Decline Shipped Back',
		'user' => $user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
	// No PM Notification for shipping Decline Back
}


//
// For Decline Status to change from Decline Shipped Back to Received at NDC
//
$shipped_received_at_ndc_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        ret.return_status_id = " . $status_decline_shipped_back_term_id .
      " ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Shipped Back to Received at NDC
foreach ($shipped_received_at_ndc_status_query as $item) {
	if($item->delivered == 1) {
      // update Decline status to Decline Shipped Back
      $return_id = $item->return_id;	
      $where = [ 'id' => $return_id ];
      $data_status = [ 'return_status_id' => $status_decline_received_at_ndc_term_id ]; 
      $obj = Patt_Custom_Func::update_return_data( $data_status, $where );

      // Update Decline db table (Return) when it is shipped.
      $where = [ 'id' => $return_id ];
      $current_datetime = date("Y-m-d H:i:s");
      $data = [ 'return_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
      Patt_Custom_Func::update_return_data( $data, $where );
      
      
      
      // PM Notification :: Requester & Digitization Staff - Decline Complete
	//

	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Get digitization staff
	$agent_admin_group_name = 'Administrator';
	$pattagentid_admin_array = Patt_Custom_Func::agent_from_group( $agent_admin_group_name );
	 
	$agent_manager_group_name = 'Manager';
	$pattagentid_manager_array = Patt_Custom_Func::agent_from_group( $agent_manager_group_name );
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array, $pattagentid_admin_array, $pattagentid_manager_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
// 	$requestid = 'D-' . $decline->return_id; 
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-received-at-ndc';
  
   	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
    }
	
	// Prep Timestmp Table data. 
	// Get Decline obj
	
	$where = [
		'return_id' => $return_id
	];
	$return_array = Patt_Custom_Func::get_return_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$return_array_key = array_key_first( $return_array );
	$return_obj = $return_array[ $return_array_key ];

	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $return_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	// WP user info.
	$user_num = $return_obj->user_id[0];
	$user_obj = get_user_by( 'id', $user_num );
	$user_login = $user_obj->data->display_name;
	
	$data = [
		'decline_id' => $return_obj->id,   
		'type' => 'Received at NDC',
		'user' => $user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
  
  		//
	
}


//
// For Decline Status to change from Received at NDC to Decline Complete
//
$return_complete_return_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.return_id,
      ret.id,
      ret.return_id as return_id,
      ret.return_status_id as return_status,
      ret.return_complete as return_complete
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_return AS ret 
	ON (
        shipping.return_id = ret.id
	   )
	WHERE 
        shipping.return_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        ret.return_status_id = " . $status_decline_received_at_ndc_term_id . 
      " ORDER BY shipping.id ASC"
	);

	
// For Return Status to change from Decline Received at NDC to Decline Complete
foreach ($return_complete_return_status_query as $item) {
	if($item->return_complete == 1) {
      // update Decline status to Decline Complete
      $return_id = $item->return_id;	
      $where = [ 'id' => $return_id ];
      $data_status = [ 'return_status_id' => $status_decline_complete_term_id ]; 
      $obj = Patt_Custom_Func::update_return_data( $data_status, $where );

      // Update Decline DB Received date
      $where = [ 'id' => $return_id ];
      $current_datetime = date("Y-m-d H:i:s");
      $data = [ 'received_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
      Patt_Custom_Func::update_return_data( $data, $where );
    }
	
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	$ticket_id = ltrim( $ticket_id, '0' );
	
	//
	// Set Box status back to original status before Decline
	//  
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_obj = $decline_obj_array[0];
	
	foreach( $decline_obj->box_id as $key => $box_id ) {
		
		$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
		$data_where = array( 'box_id' => $box_id );
		$data_update = array( 'box_status' => $decline_obj->saved_box_status[$key] );
		$wpdb->update( $table_name, $data_update, $data_where );
		
		// Box Status Audit Log
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'terms WHERE term_id = ' . $decline_obj->saved_box_status[$key];
		$status_info = $wpdb->get_row( $sql );
		$status_name = $status_info->name;
		
		$status_full = 'Waiting on RLO to ' . $status_name;
		do_action('wpppatt_after_box_status_update', $ticket_id, $status_full, $box_id );	
		
	}


	
	//
	// Timestamp Table
	//

	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $decline_obj->box_id_fk[0] );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	// WP user info.
	$user_num = $return_obj->user_id[0];
	$user_obj = get_user_by( 'id', $user_num );
	$user_login = $user_obj->data->display_name;
	
	$data = [
		'decline_id' => $decline_obj->id,   
		'type' => 'Decline Complete',
		'user' => $user_login,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_decline_timestamp( $data );
	
	
	
	// Decline Audit Log
	
	do_action('wpppatt_after_return_completed', $ticket_id, 'D-'.$return_id );

	//
	// PM Notification :: Requester & Digitization Staff - Decline Complete
	//

	// Get ticket_id based on return_id
	$ticket_id = Patt_Custom_Func::get_ticket_id_from_decline_id( $return_id );
	
	// Get owner of ticket. 
	$where = [ 'ticket_id' => $ticket_id ];
	$agent_id_array = Patt_Custom_Func::get_ticket_owner_agent_id( $where );
	
	// Get all users on Decline (currently only the person who submitted it, and no way to add others)
	$where = [ 'return_id' => $return_id ]; // format: '0000002'
	$decline_obj_array = Patt_Custom_Func::get_return_data( $where );
	$decline_agent_id_array = Patt_Custom_Func::translate_user_id( $decline_obj_array[0]->user_id, 'agent_term_id' ); 
	
	// Redundant as only one requester allowed on Decline currently. (Mirrors Recall)
	$role_array_requester = [ 'Requester', 'Requester Pallet' ];
	$agent_id_requesters_array = Patt_Custom_Func::return_agent_ids_in_role( $decline_agent_id_array, $role_array_requester);
	
	// Get digitization staff
	$agent_admin_group_name = 'Administrator';
	$pattagentid_admin_array = Patt_Custom_Func::agent_from_group( $agent_admin_group_name );
	 
	$agent_manager_group_name = 'Manager';
	$pattagentid_manager_array = Patt_Custom_Func::agent_from_group( $agent_manager_group_name );
	
	// Combine Requester on Request with Requesters on Decline
	$pattagentid_array = array_merge( $agent_id_array, $agent_id_requesters_array, $pattagentid_admin_array, $pattagentid_manager_array );
	$pattagentid_array = array_unique( $pattagentid_array );
	
// 	$requestid = 'D-' . $decline->return_id; 
	$requestid = 'D-' . $return_id; 
	
	$data = [
        //'item_id' => $requestid
    ];
	$email = 1;
	
	$notification_post = 'email-decline-complete';
		
	// PM Notification to the Requestor / owner
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
}






?>
