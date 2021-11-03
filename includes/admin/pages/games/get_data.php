<?php
header('Content-Type: application/json; charset=utf-8');

include 'db_connection.php';
$conn = OpenCon();

//Check API Key to make sure it is valid
$api_key     = htmlspecialchars($_GET['api_key']);
$lookup_table = htmlspecialchars($_GET['table']);

$set_api = '';

if (!empty($api_key)) {

    //Check API Key to make sure it is valid
    $query_api_key = "SELECT COUNT(id) AS COUNT
 FROM arms_game_application 
 WHERE api_key = '" . $api_key . "' LIMIT 1";
    
    $result_api_key = mysqli_query($conn, $query_api_key);
    
    while ($api_result = mysqli_fetch_array($result_api_key)) {
        $set_api = $api_result["COUNT"];
    }

    if ($set_api == 1 && !empty($lookup_table)) {

switch ($lookup_table) {
  case "events":
                $query_events = "SELECT id, name, description, value
FROM arms_game_events";
                
                $result_events_val = mysqli_query($conn, $query_events);
                $rows = array();
                while($r = mysqli_fetch_assoc($result_events_val)) {
                    $rows[] = $r;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved for table of ' . $lookup_table . '.'));
}

    break;
  case "levels":

                $query_levels = "SELECT id, name, description, value, image_url
FROM arms_game_levels";
                
                $result_levels_val = mysqli_query($conn, $query_levels);
                $rows = array();
                while($r = mysqli_fetch_assoc($result_levels_val)) {
                    $rows[] = $r;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved for table of ' . $lookup_table . '.'));
}
    break;
  case "rewards":
                $query_rewards = "SELECT id, name, description, image_url
FROM arms_game_rewards WHERE active = 1";
                
                $result_rewards_val = mysqli_query($conn, $query_rewards);
                $rows = array();
                while($r = mysqli_fetch_assoc($result_rewards_val)) {
                    $rows[] = $r;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved for table of ' . $lookup_table . '.'));
}
    break;
  case "rules":
                $query_rules = "SELECT id, name, rewards_id FROM arms_game_rules WHERE CURDATE() BETWEEN start_date AND end_date AND active = 1";
                
                $result_rules_val = mysqli_query($conn, $query_rules);
                $rows = array();
                while($r = mysqli_fetch_assoc($result_rules_val)) {
                    $rows[] = $r;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved for table of ' . $lookup_table . '.'));
}
    break;
  default:
    //echo "Incorrect table value passed.";
    print_r( Patt_Custom_Func::json_response(422, 'table of ' . $lookup_table . ' not found'));
}

        
        
    }
    else {
        if($set_api == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'api_key of ' . $api_key . ' not found'));
        }
        if(empty($lookup_table)) {
            print_r( Patt_Custom_Func::json_response(400, 'Missing table field'));
        }
    }
    
}
else {
    print_r( Patt_Custom_Func::json_response(400, 'Missing api_key field'));
}
?>