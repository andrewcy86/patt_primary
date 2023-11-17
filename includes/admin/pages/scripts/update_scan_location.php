<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(isset($_POST['postvarsboxid']) && isset($_POST['postvarslocation'])){

        // JM - 2/26/2021 - Issue a check for pallet_id's
        $ispallet_id = false;
        $recalls_found = false;
        $returns_found = false;
        $record_updated = false;
        $evaluated = false;
        $message = "";
        $response_arr = array();
        
        $box_id = $_POST['postvarsboxid'];
        $location = $_POST['postvarslocation']; 
        
        /* the variables */
        $array_location = array($location);
        $count = count($box_id);
        $newArray_location = array();
        
        /* create a new array with AT LEAST the desired number of elements by joining the array at the end of the new array */
        while(count($newArray_location) <= $count){
            $newArray_location = array_merge($newArray_location, $array_location);
        }
        // reduce the new array to the desired length (as there might be too many elements in the new array)
        $array_location = array_slice($newArray_location, 0, $count);

        $box_insert = array_combine($box_id, $array_location);
 
        /* Identify the table */
        $table_name = $wpdb->prefix . 'wpsc_epa_scan_list';

        $column_name = '';
        $error_flag = 0;
        $date = date('Y-m-d H:i:s');
        
        /* Get the ticket ID aka Request ID */
        $ticket_id = "";
        $current_box_id = "";
        
        // An id can be either a box_id or pallet_id, so we must make the 
        // search key column identifier a variable here, to use in the 
        // $get_ticketid= procedure below      
        $search_columnid = "";
        
        foreach ($box_insert as $key => $value) {
            
            // JM - 2/25/2021 - In order to treat the pallet_id the same as the
            // box_id, we need to get the box_id from the pallet_id in the array
            // that has been sent from the submit on the scanning.php page
            
            //Check if Box ID
            if (preg_match("/^([0-9]{7}-[0-9]{1,4})(?:,\s*(?1))*$/", $value)) {
            $search_column_key = "box_id";
            }
            //Check if Pallet ID
            if (preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $value)) {
            $search_column_key = "pallet_id";
            }
            
                $get_ticket_id = $wpdb->get_row("
                                        SELECT ticket_id
                                        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
                                        WHERE
                                        $search_key_id = '" . $key . "'
                                        ");
                        
            $ticket_id = $get_ticket_id->ticket_id;         
            
            if($ticket_id == ""){
  
                $GLOBALS[$evaluated] = false;
                $GLOBALS[$record_updated] = false;
                $message = "Not Updated: The Box ID does not exist.\n\n";
                echo $message; 
            }else{
            
            $strrecall = checkforRecall($key);
            $strdecline = checkforDecline($key);
            
                if($strrecall == "" && $strdecline == ""){
            
                    if(preg_match('/\b(SCN-\d\d-e|SCN-\d\d-w)\b/i', $value)) {
                        $column_name = 'scanning_id';
                        
                                            /* Change the box status has been changed to "Assigned to Scanner"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'Assigned to Scanner'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }
                    
                    // JM - 2/25/2021 - Changed preg_match for the staging area to support pallets per Region 3 reqs
                    //  /^\b(sa-e-\d+|sa-w-\d+)\b$/i   from    /^\b(sa-e|sa-w)\b$/i
                    if(preg_match('/^\b(sa-e-\d+|sa-w-\d+)\b$/i', $value)) {
                        
                        $column_name = 'stagingarea_id';
        
                                            /* Change the box status has been changed to "In Staging Area"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'In Staging Area'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Update the pallet_id value in the boxinfo table */
                                            /* Add if statement to determine if the value is */
                                            if($ispallet_id = true){
                                                
                                                            /* Set status value for pallet_id to the returned value from above statement*/
                                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                                            $loc_status_boxinfo_data_where = array('pallet_id' => $key);
                                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                                                                          $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                                                                          $wpdb->insert($scan_table_name, array(
                                                                          'pallet_id' => esc_sql($key),
                                                                          $column_name => esc_sql(strtoupper($value)),
                                                                          'date_modified' => $date,
                                                                        ));
                                    
                                                            $message = "Updated: Pallet ID " . $key . " is " . $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                                            echo $message;
                                                
                                                
                                                
                                            }else{
                                            
                                                            /* Set status value for box id to the returned value from above statement*/
                                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                                                                          $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                                                                          $wpdb->insert($scan_table_name, array(
                                                                          'box_id' => esc_sql($key),
                                                                          $column_name => esc_sql(strtoupper($value)),
                                                                          'date_modified' => $date,
                                                                        ));
                                    
                                                            $message = "Updated: Box ID " . $key . " is " . $$location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                                            echo $message;  
                                            
                                            }
                        $GLOBALS[$record_updated] = true;  
                        $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }
                    
                    if(preg_match_all('/(\bCID-\d\d-e\b|\bCID-\d\d-w\b)|(\bCID-\d\d-east\scui\b|\bCID-\d\d-west\scui\b)|(\bCID-\d\d-east\b|\bCID-\d\d-west\b)|(\bCID-\d\d-eastcui\b|\bCID-\d\d-westcui\b)/im', $value)) {
                        
                        $column_name = 'cart_id';
                        
                                            /* Change the box status has been changed to "on cart"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'On Cart'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($scan_table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
        
                            $message = "Updated: Box ID " . $key . " with the following Cart ID: " . strtoupper($value) . "\n\n";
                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);
                            echo $message; 
                        $GLOBALS[$record_updated] = true;  
                        $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }
                  
                  	if(preg_match('/\b(VAL-\d\d-e|VAL-\d\d-w)\b/i', $value)) {
                        $column_name = 'validation_location_area_id';
                        
                                            /* Change the box status has been changed to "In Validation Area"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'In Validation Area'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }

                    if(preg_match('/\b(QAQC-\d\d-e|QAQC-\d\d-w)\b/i', $value)) {
                        $column_name = 'qaqc_location_area_id';
                        
                                            /* Change the box status has been changed to "In QA/QC Area"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'In QA/QC Area'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }
                  
                  	if(preg_match('/\b(SPA-\d\d-e|SPA-\d\d-w)\b/i', $value)) {
                        $column_name = 'scanning_prep_location_area_id';
                        
                                            /* Change the box status has been changed to "In Validation Area"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'In Scanning Prep Area'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }

                    if(preg_match('/\b(SLA-\d\d-e|SLA-\d\d-w)\b/i', $value)) {
                        $column_name = 'scanning_location_area_id';
                        
                                            /* Change the box status has been changed to "In Scanning Area"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'In Scanning Area'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }

                    if(preg_match('/\b(RD-\d\d-e|RD-\d\d-w)\b/i', $value)) {
                        $column_name = 'receiving_dock';
                        
                                            /* Change the box status has been changed to "Receiving Dock"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'Receiving Dock'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }

                    if(preg_match('/\b(OS-\d\d-e|OS-\d\d-w)\b/i', $value)) {
                        $column_name = 'oversized_tube_shelves';
                        
                                            /* Change the box status has been changed to "Oversized Tube Shelves"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'Oversized Tube Shelves'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }

                    if(preg_match('/\b(DES-\d\d-e|DES-\d\d-w)\b/i', $value)) {
                        $column_name = 'destruction';
                        
                                            /* Change the box status has been changed to "Destruction"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'Destruction'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }

                    if(preg_match('/\b(SHP-\d\d-e|SHP-\d\d-w)\b/i', $value)) {
                        $column_name = 'shipping_dock_area';
                        
                                            /* Change the box status has been changed to "Shipping Dock Area"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'Shipping Dock Area'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }

                    if(preg_match('/\b(DIS-\d\d-e|DIS-\d\d-w)\b/i', $value)) {
                        $column_name = 'discrepancy';
                        
                                            /* Change the box status has been changed to "Shipping Dock Area"  */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'Discrepancy'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            /* Set status value for box id to the returned value from above statement*/
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                        
                        $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                        $wpdb->insert($table_name, array(
                                        'box_id' => esc_sql($key),
                                        $column_name => esc_sql(strtoupper($value)),
                                        'date_modified' => $date,
                                    ));
                                            $message = "Updated: Box ID " . $key . " has been ". $location_statuses_locations . " " . strtoupper($value). ".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);  
                                            echo $message;  
                                        
                                    $GLOBALS[$record_updated] = true;
                                    $GLOBALS[$evaluated] = true;
                    }else{
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;     
                    }
                    
                    if(preg_match('/^\d{1,3}A_[A-O]B_\d{1,3}S_\d{1,3}P_(E|W|ECUI|WCUI)$/i', $value)) {
                        $alphabet = range('A', 'O');
                        /* Restrict physical location scanning assignments to one box */
                        if($count == 1){
                        
                            $column_name = 'shelf_location';
                            $position_array = explode('_', $value);
            				
                            $aisle = substr($position_array[0], 0, -1);
                            $pre_bay_letter = substr($position_array[1], 0, -1);
                          	$bay = array_search($pre_bay_letter, $alphabet)+1;
                          
                          
                            $shelf = substr($position_array[2], 0, -1);
                            $position = substr($position_array[3], 0, -1);
                            $dc = $position_array[4];
                            $center_term_id = term_exists($dc);
                            $new_term_object = get_term( $center_term_id );
                            $new_position_id_storage_location = $aisle.'A_'.$bay.'B_'.$shelf.'S_'.$position.'P_'.strtoupper($dc); 
                            $new_A_B_S_only_storage_location = $aisle.'_'.$bay.'_'.$shelf;
            
                            /* Add logic to determine if a location is in the facility. */
            
            			    $storage_location_details = $wpdb->get_row(
            			                                                "SELECT shelf_id 
            			                                                FROM " . $wpdb->prefix . "wpsc_epa_storage_status
                                                                        WHERE shelf_id = '" . esc_sql($new_A_B_S_only_storage_location) . "'"
                                            			              );
                                            			              
                			$facility_shelfid = $storage_location_details->shelf_id;
    
    
                			if($facility_shelfid == $new_A_B_S_only_storage_location){
                		        
                		        $box_id_new_scan = $key;
                		         
                		        /* Determine if the position is occupied */
                		        $existing_boxinfo_details = $wpdb->get_row(
                			                                                "SELECT b.aisle as aisle,b.bay as bay,b.shelf as shelf,b.position as position,b.digitization_center as dc
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a                
                                                                            LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location b ON a.storage_location_id = b.id
                                                                            WHERE a.box_id = '" . esc_sql($box_id_new_scan) . "'
                                            			                ");
                                      			              
                		        $existing_boxinfo_aisle = $existing_boxinfo_details->aisle;
                			    $existing_boxinfo_bay = $existing_boxinfo_details->bay;
                			    $existing_boxinfo_shelf = $existing_boxinfo_details->shelf;
                			    $existing_boxinfo_position = $existing_boxinfo_details->position;
                			    $term_object = get_term( $existing_boxinfo_details->dc);
                			    $existing_boxinfo_dc =  $term_object->slug;
                		        $existing_boxinfo_position_id_storage_location = $existing_boxinfo_aisle.'A_'.$existing_boxinfo_bay.'B_'.$existing_boxinfo_shelf.'S_'.$existing_boxinfo_position.'P_'.strtoupper($existing_boxinfo_dc);
    
                		   		if($existing_boxinfo_position_id_storage_location != "0A_0B_0S_0P_NOT-ASSIGNED" ){
                		   		    
                		   		    /* If the proposed storage position scanned on the shelf matches the initial auto=selected/manual location storage position (assigned) */
                                    if($new_position_id_storage_location == $existing_boxinfo_position_id_storage_location){
                                            
                                            /* Change the box status has been changed from "on cart" to "on shelf" */
                                            $location_statuses = $wpdb->get_row(
                			                                                "SELECT id as id,locations as locations
                                                                            FROM " . $wpdb->prefix . "wpsc_epa_location_status                
                                                                            WHERE locations = 'On Shelf'
                                            			                ");
                                            			                
                                            $location_statuses_id = $location_statuses->id;
                			                $location_statuses_locations = $location_statuses->locations;
                                            
                                            $loc_status_boxinfo_table_name = $wpdb->prefix . 'wpsc_epa_boxinfo';
                                            $loc_status_boxinfo_data_update = array('location_status_id' => $location_statuses_id);
                                            $loc_status_boxinfo_data_where = array('box_id' => $key);
                                            $wpdb->update($loc_status_boxinfo_table_name , $loc_status_boxinfo_data_update, $loc_status_boxinfo_data_where);
                                            
                        				    /* Update the scanning table */
                                            $scan_table_name = $wpdb->prefix . 'wpsc_epa_scan_list';
                                            $wpdb->insert($scan_table_name, array(
                                                            'box_id' => esc_sql($key),
                                                            $column_name => esc_sql(strtoupper($value)),
                                                            'date_modified' => $date,
                                                         ));
                                            
                                            /* Notify the user that the box status has been changed from "on cart" to "on shelf" */
                                            $message = "Updated: Box ID " . $key . " has been placed " . $location_statuses_locations . " " . $new_position_id_storage_location .".\n\n";
                                            do_action('wpppatt_after_shelf_location', $ticket_id, $key, $message);
                                            echo $message; 
                                            $GLOBALS[$record_updated] = true; 
                                            $GLOBALS[$evaluated] = true;        
                                            
                                            
                                    } else {
                            		    
                            			$message = "Not Updated: The scanned location ". $new_position_id_storage_location . " does not match the assigned shelf location for the box. Please select another location and try again.\n\n";
                                        echo $message;    
                                        $GLOBALS[$record_updated] = false; 
                                        $GLOBALS[$evaluated] = true;     
    
                                    }
                		   		}else{
                		   		    
                   		   		    $message = "Not Updated: The location ". $existing_boxinfo_position_id_storage_location ." is already assigned. Please select another location and try again.\n\n";
                                    echo $message;               
                                    $GLOBALS[$record_updated] = false; 
                                    $GLOBALS[$evaluated] = true;     
    
                		   		}
            				}else{
            				    $message = "Not Updated: The location ". $new_position_id_storage_location . " does not exist in the facility. Please select another location and try again.\n\n";
                                echo $message;  
                                $GLOBALS[$record_updated] = false; 
                                $GLOBALS[$evaluated] = true;     
                			}	
                        }else{
                            $message = "Not Updated. The Location Scan cannot be assigned to multiple Box ID's.\n\n";
                            echo $message; 
                            $GLOBALS[$record_updated] = false; 
                            $GLOBALS[$evaluated] = true;     
                        }
                    }
                }else{
                    
                    if($strdecline == "decline"){
                        $message = "Not Updated. Box " . $key . " is associated with a Decline. \n\n";
                        echo $message;
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;   
                    }
                    if($strrecall == "recall"){
                        $message = "Not Updated. Box " . $key . " is associated with a Recall. \n\n";
                        echo $message;
                        $GLOBALS[$record_updated] = false; 
                        $GLOBALS[$evaluated] = true;   
                    } 
                }
            }
        }
        if($GLOBALS[$record_updated] == false && $GLOBALS[$evaluated] == true){
            $message = "Not Updated: The location value is invalid.\n\n";
            echo $message; 
        }

}        
function checkforRecall($box_id_key){
    
    $proceed = "";
    $type = 'box';
    $item_id = $box_id_key;
	$request_recall_check = Patt_Custom_Func::id_in_recall($item_id,$type);
                    
                    if($request_recall_check > 0){
                        $proceed = "recall";
                    }   

    return $proceed;            
}
function checkforDecline($box_id_key){

    $proceed = "";
    $type = 'box';
    $item_id = $box_id_key;
    $return_check = Patt_Custom_Func::id_in_return($item_id, $type);
                   
                    if($return_check > 0){
                        $proceed = "decline";
                    }
                    
    return $proceed;            
}
    
?>