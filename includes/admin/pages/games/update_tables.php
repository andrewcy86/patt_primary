<?php
header('Content-Type: application/json; charset=utf-8');

global $wpdb;

include 'db_connection.php';
$conn = OpenCon();

//Check API Key to make sure it is valid
$api_key = htmlspecialchars($_GET['api_key']);
$type = htmlspecialchars($_GET['type']);
$table = htmlspecialchars($_GET['table']);

$dt = date('Y-m-d h:i:s');

// Check for field values
$db_id = htmlspecialchars($_GET['id']);
$name = htmlspecialchars($_GET['name']);
$description = htmlspecialchars($_GET['description']);
$active = htmlspecialchars($_GET['active']);
$image_url = htmlspecialchars($_GET['image_url']);
$rewards_id = htmlspecialchars($_GET['rewards_id']);
$start_date = htmlspecialchars($_GET['start_date']);
$end_date = htmlspecialchars($_GET['end_date']);
$value = htmlspecialchars($_GET['value']);
$rule_id = htmlspecialchars($_GET['rule_id']);
$operation = htmlspecialchars($_GET['operation']);
$event_id = htmlspecialchars($_GET['event_id']);
$expression = htmlspecialchars($_GET['expression']);

$set_api = '';
$set_type = 0;
$set_table = 0;

$old_name = '';
$old_description = '';
$old_active = '';
$old_image_url = '';
$old_rewards_id = '';
$old_start_date = '';
$old_end_date = '';
$old_value = '';
$old_rule_id = '';
$old_operation = '';
$old_event_id = '';
$old_expression = '';

$type_array = array('insert', 'update');
$table_array = array('rewards', 'rules', 'conditions', 'events');

if (!empty($api_key) && !empty($type) && !empty($table)) {

    //Check API Key to make sure it is valid
    $query_api_key = "SELECT COUNT(id) AS COUNT
 FROM arms_game_application 
 WHERE api_key = '" . $api_key . "' LIMIT 1";
    
    $result_api_key = mysqli_query($conn, $query_api_key);
    
    while ($api_result = mysqli_fetch_array($result_api_key)) {
        $set_api = $api_result["COUNT"];
    }
    
    // Check action type
    if(in_array(strtolower($type), $type_array)) {
        $set_type = 1;
    }
    
    // Check table name
    if(in_array(strtolower($table), $table_array)) {
        $set_table = 1;
    }
    
    if ($set_api == 1 && $set_type == 1 && $set_table == 1) {
        switch($table) {
            case "rewards": 
                if(strtolower($type) == 'insert') {
                    
                    if(empty($name) || empty($description) || empty($image_url)) {
                        print_r( Patt_Custom_Func::json_response(400, 'Missing one or more of the following fields: name, description, or image_url.'));
                    } else {
                        $query_rewards_insert = "INSERT INTO arms_game_rewards (name,description,active,image_url,created_date,updated_date)
                        VALUES ('" . $name . "','" . $description . "', 1, '" . $image_url . "' , '" . $dt . "', '" . $dt . "')";
                        if ($conn->query($query_rewards_insert) === true) {
                            print_r(Patt_Custom_Func::json_response(200, 'Success'));
                        } else {
                            print_r(Patt_Custom_Func::json_response(500, 'Could not insert into rewards table'));
                        }

                        
                    }
                }
                if(strtolower($type) == 'update' && !empty($db_id) ) {
                    $query_rewards_select_all = "SELECT * FROM arms_game_rewards WHERE id = " . $db_id;
                    $result_rewards_select_all = mysqli_query($conn, $query_rewards_select_all);
                    
                    while ($rewards_select_all_result = mysqli_fetch_array($result_rewards_select_all)) {
                        $old_name = $rewards_select_all_result["name"];
                        $old_description = $rewards_select_all_result["description"];
                        $old_active = $rewards_select_all_result["active"];
                        $old_image_url = $rewards_select_all_result["image_url"];
                    }
                    
                    // IF fields are empty use old value
                    if(empty($name) || $name == $old_name) {
                        $name = $old_name;
                    }
                    if(empty($description) || $description == $old_description) {
                        $description = $old_description;
                    }
                    if(empty($active) || $active == $old_active) {
                        $active = $old_active;
                    }
                    
                    if(empty($image_url) || $image_url == $old_image_url) {
                        $image_url = $old_image_url;
                    }
                    
                    // Execute update statement
                    $query_rewards_update = "UPDATE arms_game_rewards 
                    SET name = '" . $name . "', description = '" . $description . "', active = " . $active . ", image_url = '" . $image_url . "', updated_date = '" . $dt . "' 
                    WHERE id = " . $db_id;

                    if ($conn->query($query_rewards_update) === true) {
                        print_r(Patt_Custom_Func::json_response(200, 'Success'));
                    } else {
                        print_r(Patt_Custom_Func::json_response(500, 'Could not update rewards table'));
                    }
                }
                else {
                    print_r( Patt_Custom_Func::json_response(400, 'Missing id field.'));
                }
            break;

            case "rules":
                if(strtolower($type) == 'insert') {
                    
                    if(empty($name) || empty($rewards_id)) {
                        print_r( Patt_Custom_Func::json_response(400, 'Missing one of the following fields: name or rewards_id.'));
                    } else {

                        if(empty($start_date)) {
                            $start_date = '0000-00-00 00:00:00';
                        }
                        if(empty($end_date)) {
                            $end_date = '0000-00-00 00:00:00';
                        }

                        $query_rules_insert = "INSERT INTO arms_game_rules (name,rewards_id,active,start_date,end_date,created_date,updated_date)
                        VALUES ('" . $name . "'," . $rewards_id . ", 1, '" . $start_date . "' , '" . $end_date . "', '" . $dt . "', '" . $dt . "')";
                        if ($conn->query($query_rules_insert) === true) {
                            print_r(Patt_Custom_Func::json_response(200, 'Success'));
                        } else {
                            print_r(Patt_Custom_Func::json_response(500, 'Could not insert into rules table'));
                        }
                    }
                }
                if(strtolower($type) == 'update' && !empty($db_id) ) {
                    $query_rules_select_all = "SELECT * FROM arms_game_rules WHERE id = " . $db_id;
                    $result_rules_select_all = mysqli_query($conn, $query_rules_select_all);
                    
                    while ($rules_select_all_result = mysqli_fetch_array($result_rules_select_all)) {
                        $old_name = $rules_select_all_result["name"];
                        $old_rewards_id = $rules_select_all_result["rewards_id"];
                        $old_active = $rules_select_all_result["active"];
                        $old_start_date = $rules_select_all_result["start_date"];
                        $old_end_date = $rules_select_all_result["end_date"];
                    }
                    
                    // IF fields are empty use old value
                    if(empty($name) || $name == $old_name) {
                        $name = $old_name;
                    }
                    if(empty($rewards_id) || $rewards_id == $old_rewards_id) {
                        $rewards_id = $old_rewards_id;
                    }
                    if(empty($active) || $active == $old_active) {
                        $active = $old_active;
                    }
                    if(empty($start_date) || $start_date == $old_start_date) {
                        $start_date = $old_start_date;
                    }
                    if(empty($end_date) || $end_date == $old_end_date) {
                        $end_date = $old_end_date;
                    }

                    // Execute update statement
                    $query_rules_update = "UPDATE arms_game_rules
                    SET name = '" . $name . "', rewards_id = " . $rewards_id . ", active = " . $active . ", start_date = '" . $start_date . "', end_date = '" . $end_date . "', updated_date = '" . $dt . "' 
                    WHERE id = " . $db_id;

                    if ($conn->query($query_rules_update) === true) {
                        print_r(Patt_Custom_Func::json_response(200, 'Success'));
                    } else {
                        print_r(Patt_Custom_Func::json_response(500, 'Could not update rules table'));
                    }
                }
                else {
                    print_r( Patt_Custom_Func::json_response(400, 'Missing id field.'));
                }
            break; 
            
            case "conditions": 
                if(strtolower($type) == 'insert') {
                    
                    if(empty($rule_id) || empty($operation) || empty($event_id) || empty($expression) || empty($value)) {
                        print_r( Patt_Custom_Func::json_response(400, 'Missing one or more of the following fields: rule_id, operation event_id, expression or value.'));
                    } else {
                        $query_conditions_insert = "INSERT INTO arms_game_conditions (rule_id,operation,event_id,expression,value,created_date,updated_date)
                        VALUES (" . $rule_id . ",'" . $operation . "', " . $event_id . " , '" . $expression . "' , " . $value . ", '" . $dt . "', '" . $dt . "')";
                        if ($conn->query($query_conditions_insert) === true) {
                            print_r(Patt_Custom_Func::json_response(200, 'Success'));
                        } else {
                            print_r(Patt_Custom_Func::json_response(500, 'Could not insert into conditions table'));
                        }

                        
                    }
                }
                if(strtolower($type) == 'update' && !empty($db_id) ) {
                    $query_conditions_select_all = "SELECT * FROM arms_game_conditions WHERE id = " . $db_id;
                    $result_conditions_select_all = mysqli_query($conn, $query_conditions_select_all);
                    
                    while ($conditions_select_all_result = mysqli_fetch_array($result_conditions_select_all)) {
                        $old_rule_id = $conditions_select_all_result["rule_id"];
                        $old_operation = $conditions_select_all_result["operation"];
                        $old_event_id = $conditions_select_all_result["event_id"];
                        $old_expression = $conditions_select_all_result["expression"];
                        $old_value = $conditions_select_all_result["value"];
                    }
                    
                    // IF fields are empty use old value
                    if(empty($rule_id) || $rule_id == $old_rule_id) {
                        $rule_id = $old_rule_id;
                    }
                    if(empty($operation) || $operation == $old_operation) {
                        $operation = $old_operation;
                    }
                    if(empty($event_id) || $event_id == $old_event_id) {
                        $event_id = $old_event_id;
                    }
                    if(empty($expression) || $expression == $old_expression) {
                        $expression = $old_expression;
                    }
                    if(empty($value) || $value == $old_value) {
                        $value = $old_value;
                    }

                    // Execute update statement
                    $query_conditions_update = "UPDATE arms_game_conditions 
                    SET rule_id = " . $rule_id . ", operation = '" . $operation . "', event_id = " . $event_id . ", expression = '" . $expression . "', value = " . $value . ", updated_date = '" . $dt . "' 
                    WHERE id = " . $db_id;

                    if ($conn->query($query_conditions_update) === true) {
                        print_r(Patt_Custom_Func::json_response(200, 'Success'));
                    } else {
                        print_r(Patt_Custom_Func::json_response(500, 'Could not update conditions table'));
                    }
                }
                else {
                    print_r( Patt_Custom_Func::json_response(400, 'Missing id field.'));
                }
            break;

            case "events": 
                if(strtolower($type) == 'insert') {
                    
                    if(empty($name) || empty($description) || empty($value)) {
                        print_r( Patt_Custom_Func::json_response(400, 'Missing one or more of the following fields: name, description, or value.'));
                    } else {
                        $query_events_insert = "INSERT INTO arms_game_events (name,description,value,created_date,updated_date)
                        VALUES ('" . $name . "','" . $description . "', " . $value . ", '" . $dt . "', '" . $dt . "')";
                        if ($conn->query($query_events_insert) === true) {
                            print_r(Patt_Custom_Func::json_response(200, 'Success'));
                        } else {
                            print_r(Patt_Custom_Func::json_response(500, 'Could not insert into events table'));
                        }

                        
                    }
                }
                if(strtolower($type) == 'update' && !empty($db_id) ) {
                    $query_events_select_all = "SELECT * FROM arms_game_events WHERE id = " . $db_id;
                    $result_events_select_all = mysqli_query($conn, $query_events_select_all);
                    
                    while ($events_select_all_result = mysqli_fetch_array($result_events_select_all)) {
                        $old_name = $events_select_all_result["name"];
                        $old_description = $events_select_all_result["description"];
                        $old_value = $events_select_all_result["value"];
                    }
                    
                    // IF fields are empty use old value
                    if(empty($name) || $name == $old_name) {
                        $name = $old_name;
                    }
                    if(empty($description) || $description == $old_description) {
                        $description = $old_description;
                    }
                    if(empty($value) || $value == $old_value) {
                        $value = $old_value;
                    }

                    // Execute update statement
                    $query_events_update = "UPDATE arms_game_events 
                    SET name = '" . $name . "', description = '" . $description . "', value = " . $value . ", updated_date = '" . $dt . "' 
                    WHERE id = " . $db_id;

                    if ($conn->query($query_events_update) === true) {
                        print_r(Patt_Custom_Func::json_response(200, 'Success'));
                    } else {
                        print_r(Patt_Custom_Func::json_response(500, 'Could not update events table'));
                    }
                }
                else {
                    print_r( Patt_Custom_Func::json_response(400, 'Missing id field.'));
                }
            break;

            default:
            print_r( Patt_Custom_Func::json_response(422, 'table of ' . $table . ' not found'));
        }
    }
    else {
        if($set_api == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'api_key of ' . $api_key . ' not found'));
        }
        if($set_type == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'type of ' . $type . ' not found'));
        }
        if($set_table == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'table of ' . $table . ' not found'));
        }
    }
    
}
else {
    if(empty($api_key)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing api_key field'));
    }
    if(empty($type)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing type field'));
    }
    if(empty($table)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing table field'));
    }
    if(empty($db_id) && strtolower($type) == 'update') {
        print_r( Patt_Custom_Func::json_response(400, 'Missing id field'));
    }
}
?>