<?php
header('Content-Type: application/json; charset=utf-8');

include 'db_connection.php';
$conn = OpenCon();

//Check API Key to make sure it is valid
$api_key     = htmlspecialchars($_GET['api_key']);
$lan_id = htmlspecialchars($_GET['lan_id']);
$employee_id = htmlspecialchars($_GET['employee_id']);
$office_code = htmlspecialchars($_GET['office_code']);

//Check validity of event id/////////
$event_id = htmlspecialchars($_GET['event_id']);

$set_api              = '';
$set_event_id         = '';
$set_office_code      = '';
$parent_office_code = '';
$set_employee_id      = '';
$check_office_code    = '';
$receiver_db_id       = '';
$app_id               = '';
$get_event_value      = '';
$get_r_points_value   = '';
$get_r_level_id_value = '';
$get_level_id_value   = '';

$condition_id_value       = '';
$rule_id_value            = '';
$operation_value          = '';
$expression_value         = '';
$condition_value          = '';
$get_rewards_id           = '';
$get_rewards_active       = '';
$get_event_activity_count = '';
$get_activity_counter     = '';
$get_max_value            = '';
$get_achivement_count     = '';

$get_rules_count = '';
$rules_start_date     = '';
$rules_end_date       = '';

$dt = date('Y-m-d h:i:s');

if (!empty($api_key)) {
    
    //Get App ID
    $query_app_id = "SELECT id
 FROM arms_game_application 
 WHERE api_key = '" . $api_key . "' LIMIT 1";
    
    $result_app_id = mysqli_query($conn, $query_app_id);
    
    while ($app_id_result = mysqli_fetch_array($result_app_id)) {
        $app_id = $app_id_result["id"];
    }
    
    //Check API Key to make sure it is valid
    $query_api_key = "SELECT COUNT(id) AS COUNT
 FROM arms_game_application 
 WHERE api_key = '" . $api_key . "' LIMIT 1";
    
    $result_api_key = mysqli_query($conn, $query_api_key);
    
    while ($api_result = mysqli_fetch_array($result_api_key)) {
        $set_api = $api_result["COUNT"];
    }
    
    //Check to make sure the employee_id, office_code and event_id is present
    if ($set_api == 1 && !empty($employee_id) && !empty($lan_id) && !empty($office_code) && !empty($event_id)) {
        //echo 'API key exists, please proceed. <br/>';
        //Cross check event_id with the arms_game_events table to ensure it is valid
        $query_event_id  = "SELECT COUNT(id) AS COUNT
        FROM arms_game_events
        WHERE id = '" . $event_id . "' LIMIT 1";
        $result_event_id = mysqli_query($conn, $query_event_id);
        while ($event_id_result = mysqli_fetch_array($result_event_id)) {
            $set_event_id = $event_id_result["COUNT"];
        }
        
        if ($set_event_id == 1) {
            //Cross check office_code with wpqa_wpsc_epa_program_office to make sure it is valid
            $query_office_code = "SELECT COUNT(id) AS COUNT, parent_office_code
 FROM " . $wpdb->prefix . "wpsc_epa_program_office 
 WHERE office_code = '" . $office_code . "' LIMIT 1";
            
            $result_office_code = mysqli_query($conn, $query_office_code);
            
            while ($office_code_result = mysqli_fetch_array($result_office_code)) {
                $set_office_code = $office_code_result["COUNT"];
                $parent_office_code = $office_code_result["parent_office_code"];
            }
            
            if ($set_office_code > 0) {
                //Check if user exists in the receivers table
                $query_employee_id = "SELECT COUNT(id) AS COUNT
 FROM arms_game_receivers 
 WHERE employee_id = '" . $employee_id . "' LIMIT 1";
                
                $result_employee_id = mysqli_query($conn, $query_employee_id);
                
                while ($employee_id_result = mysqli_fetch_array($result_employee_id)) {
                    $set_employee_id = $employee_id_result["COUNT"];
                }
                
                if ($set_employee_id == 0) {
                    $insert_receiver = "INSERT INTO arms_game_receivers (lan_id,employee_id,office_code,points,level_id,created_date,updated_date)
VALUES ('" . $lan_id . "','" . $employee_id . "', '" . $parent_office_code . "', 0 , 0 , '" . $dt . "', '" . $dt . "')";
                    
                    if ($conn->query($insert_receiver) === true) {
                        //echo 'New receiver created successfully <br/>';
                    } else {
                        //echo 'Error <br/>';
                    }
                    
                } else {
                    //Check if office code needs updating
                    $query_office_code_check = "SELECT COUNT(id) AS COUNT
 FROM arms_game_receivers 
 WHERE employee_id = '" . $employee_id . "' AND office_code = '" . $office_code . "' LIMIT 1";
                    
                    $result_office_code_check = mysqli_query($conn, $query_office_code_check);
                    
                    while ($office_code_check_result = mysqli_fetch_array($result_office_code_check)) {
                        $check_office_code = $office_code_check_result["COUNT"];
                    }
                    
                    //echo $check_office_code;
                    if ($check_office_code == 0) {
                        //Update office Code
                        $update_receiver_po = "UPDATE arms_game_receivers SET office_code = '" . $parent_office_code . "', updated_date = '" . $dt . "' WHERE employee_id = " . $employee_id;
                        
                        if ($conn->query($update_receiver_po) === true) {
                            //echo 'Updated receiver program office successfully <br/>';
                        } else {
                            //echo 'Error <br/>';
                        }
                        
                    }
                    
                }
                
                
                //Continue with Event ID. Insert into activities table
                //Get DB ID from employee ID
                $query_receiver_id = "SELECT id, points, level_id
 FROM arms_game_receivers
 WHERE employee_id = '" . $employee_id . "' LIMIT 1";
                
                $result_receiver_id = mysqli_query($conn, $query_receiver_id);
                
                while ($receiver_id_result = mysqli_fetch_array($result_receiver_id)) {
                    $receiver_db_id       = $receiver_id_result["id"];
                    $get_r_points_value   = $receiver_id_result["points"];
                    $get_r_level_id_value = $receiver_id_result["level_id"];
                }
                
                //echo $receiver_db_id;
                
                //Get count of event ID for specific receiver
                $query_event_activity_count = "SELECT COUNT(id) as COUNT, counter
 FROM arms_game_activities
 WHERE receiver_id = '" . $receiver_db_id . "' AND event_id = '" . $event_id . "' LIMIT 1";
                
                $result_event_activity_count = mysqli_query($conn, $query_event_activity_count);
                
                while ($event_activity_count_result = mysqli_fetch_array($result_event_activity_count)) {
                    $get_event_activity_count = $event_activity_count_result["COUNT"];
                    $get_activity_counter     = $event_activity_count_result["counter"];
                }
                //echo 'count ' . $get_event_activity_count;
                
                
                if ($get_event_activity_count == 0) {
                    //Insert into activities table
                    $insert_activity = "INSERT INTO arms_game_activities (receiver_id,event_id,app_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $event_id . "', '" . $app_id . "', '" . $dt . "', '" . $dt . "')";
                    
                    if ($conn->query($insert_activity) === true) {
                        //echo 'New activity created successfully <br/>';
                    } else {
                        //echo 'Error: activity created issue. <br/>';
                    }
                } else {
                    $new_counter_val = $get_activity_counter + 1;
                    
                    $update_activity_counter = "UPDATE arms_game_activities SET counter = '" . $new_counter_val . "', updated_date = '" . $dt . "' WHERE event_id = '" . $event_id . "' AND receiver_id = " . $receiver_db_id;
                    
                    if ($conn->query($update_activity_counter) === true) {
                        //echo 'Updated receiver points successfully <br/>';
                    } else {
                        //echo 'Error <br/>';
                    }
                }
                
                //Determine event ID point value
                $query_event_value = "SELECT value
 FROM arms_game_events 
 WHERE id = '" . $event_id . "' LIMIT 1";
                
                $result_event_value = mysqli_query($conn, $query_event_value);
                
                while ($event_value_result = mysqli_fetch_array($result_event_value)) {
                    $get_event_value = $event_value_result["value"];
                }
                
                //Update Points on the user table
                $new_points_value = $get_event_value + $get_r_points_value;
                //echo $new_points_value;
                
                $update_receiver_points = "UPDATE arms_game_receivers SET points = '" . $new_points_value . "', updated_date = '" . $dt . "' WHERE employee_id = " . $employee_id;
                
                if ($conn->query($update_receiver_points) === true) {
                    //echo 'Updated receiver points successfully <br/>';
                } else {
                    //echo 'Error <br/>';
                }
                
                //Check if level up is needed
                $query_max_val = "SELECT MAX(value) as max_value
FROM arms_game_levels
WHERE '" . $new_points_value . "' >= value";
                
                $result_query_max_val = mysqli_query($conn, $query_max_val);
                
                while ($query_max_val_result = mysqli_fetch_array($result_query_max_val)) {
                    $get_max_value = $query_max_val_result["max_value"];
                }
                
                
                //Check if level up is needed
                $query_level_id = "SELECT id
 FROM arms_game_levels 
 WHERE value = '" . $get_max_value . "' LIMIT 1";
                
                $result_query_level_id_value = mysqli_query($conn, $query_level_id);
                
                while ($query_level_id_result = mysqli_fetch_array($result_query_level_id_value)) {
                    $get_level_id_value = $query_level_id_result["id"];
                }
                
                
                
                //Level up update
                
                if ($get_level_id_value != 0) {
                    $update_receiver_level = "UPDATE arms_game_receivers SET level_id = '" . $get_level_id_value . "', updated_date = '" . $dt . "' WHERE employee_id = " . $employee_id;
                    
                    if ($conn->query($update_receiver_level) === true) {
                        //echo 'Updated receiver level successfully <br/>';
                    } else {
                        //echo 'Error <br/>';
                    }
                }
                
                ///Determine if eligible for an achivement///
                
                //Determine what conditions apply to the event
                
                $query_conditions = "SELECT id, rule_id, operation, expression, value
 FROM arms_game_conditions 
 WHERE event_id = '" . $event_id . "'";
                
                $result_conditions_value = mysqli_query($conn, $query_conditions);
                
                while ($conditions_result = mysqli_fetch_array($result_conditions_value)) {
                    $condition_id_value   = $conditions_result["id"];
                    $rule_id_value        = $conditions_result["rule_id"];
                    $operation_value      = $conditions_result["operation"];
                    $expression_value     = $conditions_result["expression"];
                    $condition_value      = $conditions_result["value"];
                    
                    //Points will sum the value of the activity while Counter will count the number of activities.
                    
                    //Check count of activities matching event $get_activity_counter
                    
                    //Get corresponding rewards information
                    $query_rewards_id = "SELECT count(id) as COUNT, rewards_id, active, start_date, end_date
 FROM arms_game_rules 
 WHERE active = 1 AND id = '" . $rule_id_value . "'";
                    
                    $result_rewards_id = mysqli_query($conn, $query_rewards_id);
                    
                    while ($rewards_id_result = mysqli_fetch_array($result_rewards_id)) {
                        $get_rules_count    = $rewards_id_result["COUNT"];
                        $get_rewards_id     = $rewards_id_result["rewards_id"];
                        $get_rewards_active = $rewards_id_result["active"];
                        $rules_start_date   = $rewards_id_result["start_date"];
                        $rules_end_date     = $rewards_id_result["end_date"];
                        
                        
                        //Ensure there are active rules
                        if($get_rules_count >= 1) {
                            

                        $set_active_rule = 0;
                        
                        if (!($dt >= $rules_start_date && $dt <= $rules_end_date) && ($rules_start_date != 0 && $rules_end_date != 0)) {

                            $set_active_rule = 1;
                            //echo 'Rule is no longer active';
                        
                        }
                        
                        //Check if reward is active
                        $query_reward_active = "SELECT active
 FROM arms_game_rewards
 WHERE id = '" . $get_rewards_id . "' LIMIT 1";
                        
                        $result_reward_active = mysqli_query($conn, $query_reward_active);
                        
                        while ($reward_active_result = mysqli_fetch_array($result_reward_active)) {
                            $get_reward_active = $reward_active_result["active"];
                        }
                        
                        // Check the start_date and end_date and if achievement has already been awarded to the user
                        
                        $query_achivement_assigned = "SELECT COUNT(id) as COUNT
 FROM arms_game_achievements
 WHERE receiver_id = '" . $receiver_db_id . "' AND rewards_id = '" . $get_rewards_id . "' LIMIT 1";
                        
                        $result_achivement_assigned = mysqli_query($conn, $query_achivement_assigned);
                        
                        while ($achivement_assigned_result = mysqli_fetch_array($result_achivement_assigned)) {
                            $get_achivement_count = $achivement_assigned_result["COUNT"];
                        }
                        
                        //echo 'achivement count'.$get_achivement_count.'<br/>';
                        
                        if ($get_rewards_active != 0 && $get_reward_active != 0 && $get_achivement_count == 0 && $set_active_rule == 0) {
                            
                            if ($operation_value == 'points') {
                                
                                $points_val = $get_event_value * $get_activity_counter;
                                
                                //echo 'points: ' . $points_val;
                                
                                switch ($expression_value) {
                                    case "gte":
                                        if ($points_val >= $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    case "gt":
                                        if ($points_val > $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    case "eq":
                                        if ($points_val == $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    case "lt":
                                        if ($points_val < $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    case "lte":
                                        if ($points_val <= $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    default:
                                        echo "Incorrect expression in database. <br/>";
                                }
                            } elseif ($operation_value == 'counter') {
                                
                                $counts_val = $get_activity_counter + 1;
                                
                                
                                //echo 'count: ' . $counts_val;
                                
                                switch ($expression_value) {
                                    case "gte":
                                        if ($counts_val >= $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    case "gt":
                                        if ($counts_val > $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    case "eq":
                                        if ($counts_val == $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    case "lt":
                                        if ($counts_val < $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    case "lte":
                                        if ($counts_val <= $condition_value) {
                                            $insert_achivement = "INSERT INTO arms_game_achievements (receiver_id,rewards_id,created_date,updated_date)
VALUES ('" . $receiver_db_id . "', '" . $get_rewards_id . "','" . $dt . "', '" . $dt . "')";
                                            
                                            if ($conn->query($insert_achivement) === true) {
                                                //echo 'New Achivement Added. <br/>';
                                            } else {
                                                //echo 'Error <br/>';
                                            }
                                        }
                                        break;
                                    default:
                                        echo "Incorrect expression in database. <br/>";
                                }
                            }
                            
                        }
                    }
                    
                    
                    
                    
                    //Evalue associated conditions
                    //Get associated rule
                    
                    
                    }   
                }
            //JSON response if all fields are correct
            print_r( Patt_Custom_Func::json_response(200, 'Success'));
            } else {
                //JSON response if office_code incorrect
                print_r( Patt_Custom_Func::json_response(422, 'office_code of ' . $office_code . ' not found'));
            }
            
        } else {
            //JSON response if event_id is incorrect
            print_r( Patt_Custom_Func::json_response(422, 'event_id of ' . $event_id . ' not found'));
        }
    } else {
        //JSON response if api_key incorrect
        if($set_api == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'api_key of ' . $api_key . ' not found'));
        }
        if(empty($employee_id)) {
            print_r( Patt_Custom_Func::json_response(400, 'Missing employee_id field'));
        }
        if(empty($office_code)) {
            print_r( Patt_Custom_Func::json_response(400, 'Missing office_code field'));
        }
        if(empty($event_id)) {
            print_r( Patt_Custom_Func::json_response(400, 'Missing event_id field'));
        }
        if(empty($lan_id)) {
            print_r( Patt_Custom_Func::json_response(400, 'Missing lan_id field'));
        }
    }
    
}
else {
    print_r( Patt_Custom_Func::json_response(400, 'Missing api_key field'));
}

CloseCon($conn);

?>