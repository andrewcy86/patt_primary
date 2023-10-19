<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// UPDATE to update database based on list of items that are listed as shipped '1'.

global $current_user, $wpscfunction, $wpdb;

//Get term_ids for recall status slugs
$status_recalled_term_id = Patt_Custom_Func::get_term_by_slug( 'recalled' );
$status_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-cancelled' );
$status_denied_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-denied' );	
$status_approved_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-approved' );	
$status_shipped_term_id = Patt_Custom_Func::get_term_by_slug( 'shipped' );	
$status_on_loan_term_id = Patt_Custom_Func::get_term_by_slug( 'on-loan' );	
$status_shipped_back_term_id = Patt_Custom_Func::get_term_by_slug( 'shipped-back' );
$status_received_at_ndc_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-received-at-ndc' );
$status_complete_term_id = Patt_Custom_Func::get_term_by_slug( 'recall-complete' );


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
                    if ($status != 200) {
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
                  
                    if ($status != 200) {
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
                    if ($status != 200) {
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
                    if ($status != 200) {
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
                    if ($status != 200) {
                        Patt_Custom_Func::insert_api_error("recall-ups-shipping-cron", $status, $err);
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
                    if ($status != 200) {
                        Patt_Custom_Func::insert_api_error("recall-ups-shipping-cron", $status, $err);
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
                if ($status != 200 || $status != 404) {
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

// For Recall Status to change from Recall Approved [877] to Shipped [730]
/*
$shipped_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = 877
      ORDER BY shipping.id ASC"
	);
*/
$shipped_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as id_recall_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as recall_box_id
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = " . $status_approved_term_id .
      " ORDER BY shipping.id ASC"
	);

/* // OLD before Recall Approve / Recall Deny statuses.
// For Recall Status to change from Recalled [729] to Shipped [730]
$shipped_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = 729
      ORDER BY shipping.id ASC"
	);
*/
	
// For Recall Status to change from Recall Approved [729] to Shipped [730]
foreach ($shipped_recall_status_query as $item) {
	
	// update recall status to Shipped [730]
	$recall_id = $item->recall_id;	
	$where = [ 'id' => $recall_id ];
// 	$data_status = [ 'recall_status_id' => 730 ]; //change status from Recall Approved to Shipped 
	$data_status = [ 'recall_status_id' => $status_shipped_term_id ]; //change status from Recall Approved to Shipped 
	$obj = Patt_Custom_Func::update_recall_data( $data_status, $where );
	
	// Update recall db request_receipt_date when shipped. 
	$where = [ 'id' => $recall_id ];
	$current_datetime = date("Y-m-d H:i:s");
 	$data = [ 'request_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_recall_data( $data, $where );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
/*
	$data = [
		'company_name' => '',
		'tracking_number' => '',
 		'shipped' => 0,
		'status' => ''
	];
	$where = [
		'recall_id' => $recall_id
	];

	$recall_array = Patt_Custom_Func::update_recall_shipping( $data, $where );	
*/
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->recall_box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->id_recall_id,   
		'type' => 'Shipped',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
	
}




// For Recall Status to change from Shipped [730] to On Loan [731]
/*
$on_loan_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        rr.recall_status_id = 730
      ORDER BY shipping.id ASC"
	);
*/

$on_loan_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as id_recall_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as recall_box_id
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        rr.recall_status_id = " . $status_shipped_term_id .
      " ORDER BY shipping.id ASC"
	);
	
// For Recall Status to change from Shipped [730] to On Loan [731]
foreach ($on_loan_recall_status_query as $item) {
	
	// update recall status to On Loan [731]
	$recall_id = $item->recall_id;	
	$where = [ 'id' => $recall_id ];
// 	$data_status = [ 'recall_status_id' => 731 ]; //change status from Shipped to On Loan 
	$data_status = [ 'recall_status_id' => $status_on_loan_term_id ]; //change status from Shipped to On Loan 
	$obj = Patt_Custom_Func::update_recall_data( $data_status, $where );
	
	// Reset the shipping details as the same id is used for shipping to requestor and back to digitization center.
	$data = [
		'company_name' => '',
		'tracking_number' => '',
		'shipped' => 0,
		'delivered' => 0,		
		'status' => ''
	];
	$where = [
		'recall_id' => $recall_id
	];

	$recall_array = Patt_Custom_Func::update_recall_shipping( $data, $where );	
	
	// Update Recall DB Received date 
	$where = [ 'id' => $recall_id ];
	$current_datetime = date("Y-m-d H:i:s");
	$data = [ 'return_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_recall_data( $data, $where );
	
	// Need to update Recall shipping dates in recallrequest table.
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'recall_id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->recall_box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->id_recall_id,   
		'type' => 'On Loan',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
	
}





// For Recall Status to change from On Loan [731] to Shipped Back [732]
/*
$shipped_back_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = 731
      ORDER BY shipping.id ASC"
	);
*/

$shipped_back_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as id_recall_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as recall_box_id
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = " . $status_on_loan_term_id .
      " ORDER BY shipping.id ASC"
	);
	
// For Recall Status to change from On Loan [731] to Shipped Back [732]
foreach ($shipped_back_recall_status_query as $item) {
	
	// update recall status to Shipped Back [732]
	$recall_id = $item->recall_id;	
	$where = [ 'id' => $recall_id ];
// 	$data_status = [ 'recall_status_id' => 732 ]; //change status from On Loan to Shipped Back
	$data_status = [ 'recall_status_id' => $status_shipped_back_term_id ]; //change status from On Loan to Shipped Back
	$obj = Patt_Custom_Func::update_recall_data( $data_status, $where );
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
	
	// Update recall db request_receipt_date when shipped. 
	$where = [ 'id' => $recall_id ];
	$current_datetime = date("Y-m-d H:i:s");
 	$data = [ 'request_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
	Patt_Custom_Func::update_recall_data( $data, $where );
	
	
	
	
	// Set PM Notifications 
	$notification_post = 'email-recall-id-has-been-shipped-back';
	
	// Get digitization staff
	$agent_admin_group_name = 'Administrator';
	$pattagentid_admin_array = Patt_Custom_Func::agent_from_group( $agent_admin_group_name );
	 
	$agent_manager_group_name = 'Manager';
	$pattagentid_manager_array = Patt_Custom_Func::agent_from_group( $agent_manager_group_name );
	
	// Get people on Recall 
	$where = [
		'recall_id' => $recall_id
	];
	$recall_data = Patt_Custom_Func::get_recall_data( $where );

	$agent_id_array = Patt_Custom_Func::translate_user_id( $recall_data[0]->user_id, 'agent_term_id' );;
	
	// Merge the 3 arrays, and remove any duplicates
	$pattagentid_array = array_unique(array_merge( $agent_id_array, $pattagentid_admin_array, $pattagentid_manager_array ));
	
	$requestid = 'R-'.$recall_id; 			
	$data = [
        'action_initiated_by' => $current_user->display_name
    ];
	$email = 0;
	
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
	
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'recall_id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->recall_box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->id_recall_id,   
		'type' => 'Shipped Back',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
}


// For Recall Status to change from Shipped Back [732] to Received at NDC [4801]
$recall_received_at_ndc_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as id_recall_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as recall_box_id
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.shipped = 1
      AND 
        rr.recall_status_id = " . $status_shipped_back_term_id .
      " ORDER BY shipping.id ASC"
	);


// For Recall Status to change from Shipped Back [732] to Received at NDC [4801]
foreach ($recall_received_at_ndc_status_query as $item) {
	if($item->delivered == 1) {
      // update recall status to Received at NDC [4801]
      $recall_id = $item->recall_id;	
      $where = [ 'id' => $recall_id ];
  // 	$data_status = [ 'recall_status_id' => 2945 ]; //change status from On Loan to Shipped Back
      $data_status = [ 'recall_status_id' => $status_received_at_ndc_term_id ]; //change status from On Loan to Shipped Back
      $obj = Patt_Custom_Func::update_recall_data( $data_status, $where );

      // No need to clear shipped status as all shipping data will need to be preserved for Delivered column

      // Update recall db request_receipt_date when shipped. 
      $where = [ 'id' => $recall_id ];
      $current_datetime = date("Y-m-d H:i:s");
      $data = [ 'request_receipt_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
      Patt_Custom_Func::update_recall_data( $data, $where );
      
     
      
    // Set PM Notifications 
	$notification_post = 'email-recall-id-has-been-received-at-ndc';
	
	// Get digitization staff
	$agent_admin_group_name = 'Administrator';
	$pattagentid_admin_array = Patt_Custom_Func::agent_from_group( $agent_admin_group_name );
	 
	$agent_manager_group_name = 'Manager';
	$pattagentid_manager_array = Patt_Custom_Func::agent_from_group( $agent_manager_group_name );
	
	// Get people on Recall 
	$where = [
		'recall_id' => $recall_id
	];
	$recall_data = Patt_Custom_Func::get_recall_data( $where );

	$agent_id_array = Patt_Custom_Func::translate_user_id( $recall_data[0]->user_id, 'agent_term_id' );;
	
	// Merge the 3 arrays, and remove any duplicates
	$pattagentid_array = array_unique(array_merge( $agent_id_array, $pattagentid_admin_array, $pattagentid_manager_array ));
	
	$requestid = 'R-'.$recall_id; 			
	$data = [
        'action_initiated_by' => $current_user->display_name
    ];
	$email = 1;
	
	$new_notification = Patt_Custom_Func::insert_new_notification( $notification_post, $pattagentid_array, $requestid, $data, $email );
    }
	
	
	
	
	
	
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'recall_id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->recall_box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->id_recall_id,   
		'type' => 'Received at NDC',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
}



// For Recall Status to change from Received at NDC [4801] to Recall Complete [733]
/*
$recall_complete_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status
    FROM 
	    wpqa_wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		wpqa_wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        rr.recall_status_id = 732
      ORDER BY shipping.id ASC"
	);
*/

$recall_complete_recall_status_query = $wpdb->get_results(
	"SELECT 
      shipping.id,
      shipping.tracking_number,
      shipping.shipped,
      shipping.delivered,
      shipping.recallrequest_id,
      rr.id as the_id,
      rr.recall_id as recall_id,
      rr.recall_status_id as recall_status,
      rr.box_id as box_id,
      rr.recall_complete as recall_complete,
      rr.saved_box_status as saved_box_status
    FROM 
	    " . $wpdb->prefix . "wpsc_epa_shipping_tracking AS shipping
    INNER JOIN 
		" . $wpdb->prefix . "wpsc_epa_recallrequest AS rr 
	ON (
        shipping.recallrequest_id = rr.id
	   )
	WHERE 
        shipping.recallrequest_id <> -99999
      AND 
        shipping.company_name <> ''
      AND
        shipping.delivered = 1
      AND 
        rr.recall_status_id = " . $status_received_at_ndc_term_id .
      " ORDER BY shipping.id ASC"
	);



// For Recall Status to change from Received at NDC [4801] to Recall Complete [733]
foreach ($recall_complete_recall_status_query as $item) {
	
	// Data for Audit logs
	$dub = array( 'id' => $item->the_id );
	$recall = Patt_Custom_Func::get_recall_data( $dub );
	$recall_data = $recall[0];
	
	$ticket_id = $recall_data->ticket_id;
	$box_id = $recall_data->box_id; 
	$folderdoc_id = $recall_data->folderdoc_id; 
	$status_id = $recall_data->saved_box_status; 
	$recall_id = $recall_data->recall_id;
	
	
	
	//
	// Restore the saved Box Status
	//	
	$saved_box_status = Patt_Custom_Func::existing_recall_box_status( $item->box_id );
	// if only 1 recalled file, restore status
	if( $saved_box_status['num'] == 1)  {
		$box_status = $item->saved_box_status;
		
		$table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
		$data_where = array( 'id' => $item->box_id );
		$data_update = array( 'box_status' => $box_status );
		$wpdb->update( $table_name, $data_update, $data_where );
		
		
		// Audit log for changed box status
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'terms WHERE term_id = '.$status_id;
		$status_info = $wpdb->get_row( $sql );
		$status_name = $status_info->name;
		
		$sql = 'SELECT box_id FROM ' . $wpdb->prefix . 'wpsc_epa_boxinfo WHERE box_id = "'.$box_id . '"';
		$box_info = $wpdb->get_row( $sql );
		$item_id = $box_info->box_id;
		
		$status_full = 'Waiting on RLO to ' . $status_name;
		
		do_action('wpppatt_after_box_status_update', $ticket_id, $status_full, $item_id );

	} 
	
	
	if($item->recall_complete == 1) {
  	//if($item->delivered == 1) {
    	// update recall status to Recall Complete [733]
    	$recall_id = $item->recall_id;	
    	$where = [ 'id' => $recall_id ];
    // 	$data_status = [ 'recall_status_id' => 733 ]; //change status from On Loan to Shipped Back
    	$data_status = [ 'recall_status_id' => $status_complete_term_id ]; //change status from On Loan to Shipped Back
    	$obj = Patt_Custom_Func::update_recall_data( $data_status, $where );
    	
    	// Update Recall DB Received date 
    	$where = [ 'id' => $recall_id ];
    	$current_datetime = date("Y-m-d H:i:s");
    	$data = [ 'return_date' => $current_datetime, 'updated_date' => $current_datetime ]; 
    	Patt_Custom_Func::update_recall_data( $data, $where );
	}
	
	// No need to clear shipped status as all shipping data will need to be preserved for Delivered column
	
	// Audit log for Recall Complete
	if( $folderdoc_id == null || $folderdoc_id == '' ) {
		$item_id = $box_id;
	} else {
		$item_id = $folderdoc_id;
	}
	
	do_action('wpppatt_after_recall_completed', $ticket_id, 'R-'.$recall_id, $item_id );
	
	// Prep Timestmp Table data. 
	// Get Recall obj
	
	$where = [
		'recall_id' => $recall_id
	];
	$recall_array = Patt_Custom_Func::get_recall_data( $where );
	
	//Added for servers running < PHP 7.3
	if (!function_exists( 'array_key_first' )) {
	    function array_key_first( array $arr ) {
	        foreach( $arr as $key => $unused ) {
	            return $key;
	        }
	        return NULL;
	    }
	}
	
	$recall_array_key = array_key_first( $recall_array );
	$recall_obj = $recall_array[ $recall_array_key ];
	$recall_user_array = $recall_obj->user_id;
	$recall_names_array = [];
	
	
	foreach( $recall_user_array as $wp_user_num ) {
		
		$user_obj = get_user_by( 'id', $wp_user_num );
		$user_login = $user_obj->data->display_name;
		$recall_names_array[] = $user_login;
		
	}
	
	$recal_names_str = implode( ', ', $recall_names_array );
	
	//
	// Timestamp Table
	//
	
	$dc = Patt_Custom_Func::get_dc_array_from_box_id( $item->box_id );
	$dc_str = Patt_Custom_Func::dc_array_to_readable_string( $dc );
	
	$data = [
		'recall_id' => $item->the_id,   
		'type' => 'Recall Complete',
		'user' => $recal_names_str,
		'digitization_center' => $dc_str
	];
	
	Patt_Custom_Func::insert_recall_timestamp( $data );
}



?>
