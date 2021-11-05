<?php
header('Content-Type: application/json; charset=utf-8');

global $wpdb;

include 'db_connection.php';
$conn = OpenCon();

//Check API Key to make sure it is valid
$api_key     = htmlspecialchars($_GET['api_key']);
$employee_id = htmlspecialchars($_GET['employee_id']);
$lan_id = htmlspecialchars($_GET['lan_id']);
$type = htmlspecialchars($_GET['type']);
$set_api = '';
$set_employee_id = '';
$set_lan_id = '';
$both_identifiers = '';

if ( !empty($api_key) && !empty($type) && (!empty($employee_id) || !empty($lan_id)) ) {

    //Check API Key to make sure it is valid
    $query_api_key = "SELECT COUNT(id) AS COUNT
 FROM arms_game_application 
 WHERE api_key = '" . $api_key . "' LIMIT 1";
    
    $result_api_key = mysqli_query($conn, $query_api_key);
    
    while ($api_result = mysqli_fetch_array($result_api_key)) {
        $set_api = $api_result["COUNT"];
    }

    $query_employee_id = "SELECT COUNT(id) AS COUNT
 FROM arms_game_receivers
 WHERE employee_id = '" . $employee_id . "' LIMIT 1";
    
    $result_employee_id = mysqli_query($conn, $query_employee_id);
    
    while ($employee_id_result = mysqli_fetch_array($result_employee_id)) {
        $set_employee_id = $employee_id_result["COUNT"];
    }
    
    $query_lan_id = "SELECT COUNT(id) AS COUNT
 FROM arms_game_receivers
 WHERE lan_id = '" . $lan_id . "' LIMIT 1";
    
    $result_lan_id = mysqli_query($conn, $query_lan_id);
    
    while ($lan_id_result = mysqli_fetch_array($result_lan_id)) {
        $set_lan_id = $lan_id_result["COUNT"];
    }
    
    if ($set_api == 1 && ($set_employee_id > 0 || $set_lan_id > 0) ) {

// Check if both employee_id and lan_id are provided
if( !empty($employee_id) && !empty($lan_id) && $set_employee_id > 0 && $set_lan_id > 0) {
    $both_identifiers = " AND ";
}
else {
    $both_identifiers = " OR ";
}

//echo "employee_id : " . $employee_id . "<br/>";
//echo "lan_id : " . $lan_id . "<br/>";
//echo "employee found " .  $set_employee_id . "<br/>";
//echo "lanid found " .  $set_lan_id . "<br/>";

switch ($type) {
  case "profile":
    $query_receiver_info = "

SELECT a.id, a.lan_id, a.employee_id, a.office_code, a.points, b.name as level, a.rank as office_rank, a.overall_rank from (    
SELECT id, lan_id, employee_id, office_code, points, level_id, RANK() OVER( PARTITION BY office_code ORDER BY points DESC) AS Rank, FIND_IN_SET( points, (
SELECT GROUP_CONCAT( DISTINCT points
ORDER BY points DESC ) FROM arms_game_receivers)
) AS overall_rank
 FROM arms_game_receivers
ORDER BY office_code, points
) as a 
LEFT OUTER JOIN arms_game_levels b on a.level_id = b.id
where ( a.employee_id = '".$employee_id."' ".$both_identifiers." a.lan_id = '".$lan_id."' )
";
echo $query_receiver_info;
    
    $result_receiver_info = mysqli_query($conn, $query_receiver_info);
    
    $rows = array();
                while($r = mysqli_fetch_assoc($result_receiver_info)) {
                    $rows[] = $r;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved for this user.'));
}
    break;
  case "badges":
    $query_badges = "

SELECT a.id, b.lan_id, b.employee_id, b.office_code, b.points, c.name as badge_title, c.description as badge_description, c.image_url as badge_image from
arms_game_achievements a
LEFT JOIN arms_game_receivers b on a.receiver_id = b.id
LEFT JOIN arms_game_rewards c on a.rewards_id = c.id
where ( b.employee_id = '".$employee_id."' ".$both_identifiers." b.lan_id = '".$lan_id."' )
";
    
    $result_badges = mysqli_query($conn, $query_badges);
    
    $rows = array();
                while($r = mysqli_fetch_assoc($result_badges)) {
                    $rows[] = $r;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved for this user.'));
}
    break;
  default:
    //echo "Please specify either profile or badges.";
    print_r( Patt_Custom_Func::json_response(422, 'type of ' . $type . ' not found'));
}


}
    else {
        if($set_api == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'api_key of ' . $api_key . ' not found'));
        }
        // employee_id OR lan_id acceptable
        if( ($set_employee_id == 0 && empty($lan_id)) ) {
            print_r( Patt_Custom_Func::json_response(422, 'employee_id of ' . $employee_id . ' not found'));
        }
        if( ($set_lan_id == 0 && empty($employee_id)) ) {
            print_r( Patt_Custom_Func::json_response(422, 'lan_id of ' . $lan_id . ' not found'));
        }
        if($set_employee_id == 0 && $set_lan_id == 0 && !empty($employee_id) && !empty($set_lan_id)) {
            print_r( Patt_Custom_Func::json_response(422, 'employee_id of ' . $employee_id . ' not found and lan_id of ' . $lan_id . ' not found'));
        }
    }
    
}
else {
    if(empty($api_key)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing api_key field'));
    }
    if(empty($employee_id) && empty($lan_id)){
        print_r( Patt_Custom_Func::json_response(400, 'Missing employee_id and lan_id field, must include one or the other in request'));
    }
    if(empty($type)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing type field'));
    }
}
?>