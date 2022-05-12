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

$profile_count = 0;
$badges_count = 0;

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
                    $profile_count++;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved for this user.'));
}
    break;

  case "office_proximity":
      
      
                $query_employee_rank = "SELECT a.office_code as office_code, a.rank as office_rank from (    
SELECT id, lan_id, employee_id, office_code, points, level_id, RANK() OVER( PARTITION BY office_code ORDER BY points DESC) AS Rank, FIND_IN_SET( points, (
SELECT GROUP_CONCAT( DISTINCT points
ORDER BY points DESC ) FROM arms_game_receivers)
) AS overall_rank
 FROM arms_game_receivers
ORDER BY office_code, points
) as a 
LEFT OUTER JOIN arms_game_levels b on a.level_id = b.id
where ( a.employee_id = '".$employee_id."' ".$both_identifiers." a.lan_id = '".$lan_id."' )";
                
                $employee_rank = mysqli_query($conn, $query_employee_rank);
                
                while ($employee_id_result = mysqli_fetch_array($employee_rank)) {
                    $set_employee_rank = $employee_id_result["office_rank"];
                    $parent_office_code = $employee_id_result["office_code"];
                }
                
$employee_rank_arr = array();


if($set_employee_rank != 0){
$query_equal_rank = "

SELECT a.id, a.lan_id, a.employee_id as employee_id, a.points, b.name as level, a.rank as office_rank from (    
SELECT id, lan_id, employee_id, office_code, points, level_id, RANK() OVER( PARTITION BY office_code ORDER BY points DESC) AS Rank, FIND_IN_SET( points, (
SELECT GROUP_CONCAT( DISTINCT points
ORDER BY points DESC ) FROM arms_game_receivers)
) AS overall_rank
 FROM arms_game_receivers
ORDER BY office_code, points
) as a 
LEFT OUTER JOIN arms_game_levels b on a.level_id = b.id
where a.office_code = '".$parent_office_code."' and a.rank = ".$set_employee_rank;

                $employee_equal_rank = mysqli_query($conn, $query_equal_rank);
                
                while ($employee_equal_rank_result = mysqli_fetch_array($employee_equal_rank)) {
                    $equal_rank_employee_id = $employee_equal_rank_result["employee_id"];
                }
    
if(!empty($equal_rank_employee_id)){
array_push($employee_rank_arr,$equal_rank_employee_id);  
}
}


$foward_rank = $set_employee_rank - 1;

if($foward_rank != 0){
$query_forward_rank = "

SELECT a.id, a.lan_id, a.employee_id as employee_id, a.points, b.name as level, a.rank as office_rank from (    
SELECT id, lan_id, employee_id, office_code, points, level_id, RANK() OVER( PARTITION BY office_code ORDER BY points DESC) AS Rank, FIND_IN_SET( points, (
SELECT GROUP_CONCAT( DISTINCT points
ORDER BY points DESC ) FROM arms_game_receivers)
) AS overall_rank
 FROM arms_game_receivers
ORDER BY office_code, points
) as a 
LEFT OUTER JOIN arms_game_levels b on a.level_id = b.id
where a.office_code = '".$parent_office_code."' and a.rank = ".$foward_rank;

                $employee_forward_rank = mysqli_query($conn, $query_forward_rank);
                
                while ($employee_forward_rank_result = mysqli_fetch_array($employee_forward_rank)) {
                    $forward_rank_employee_id = $employee_forward_rank_result["employee_id"];
                }

//echo $foward_rank;             
    
if(!empty($forward_rank_employee_id)){
array_push($employee_rank_arr,$forward_rank_employee_id);  
}
}


$reverse_rank = $set_employee_rank + 1;

$query_reverse_rank = "

SELECT a.id, a.lan_id, a.employee_id as employee_id, a.points, b.name as level, a.rank as office_rank from (    
SELECT id, lan_id, employee_id, office_code, points, level_id, RANK() OVER( PARTITION BY office_code ORDER BY points DESC) AS Rank, FIND_IN_SET( points, (
SELECT GROUP_CONCAT( DISTINCT points
ORDER BY points DESC ) FROM arms_game_receivers)
) AS overall_rank
 FROM arms_game_receivers
ORDER BY office_code, points
) as a 
LEFT OUTER JOIN arms_game_levels b on a.level_id = b.id
where a.office_code = '".$parent_office_code."' and a.rank = ".$reverse_rank;

                $employee_reverse_rank = mysqli_query($conn, $query_reverse_rank);
                
                while ($employee_reverse_rank_result = mysqli_fetch_array($employee_reverse_rank)) {
                    $reverse_rank_employee_id = $employee_reverse_rank_result["employee_id"];
                }

//echo $foward_rank;     
if(!empty($reverse_rank_employee_id)){
array_push($employee_rank_arr,$reverse_rank_employee_id);     
}


//print_r($employee_rank_arr);

$result_arr = array();

foreach($employee_rank_arr as $item) {
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
where ( a.employee_id = '".$item."' )
";
    
    $result_receiver_info = mysqli_query($conn, $query_receiver_info);
    

                while($r = mysqli_fetch_assoc($result_receiver_info)) {
                    $result_arr[] = $r;
                }
}

print json_encode($result_arr);

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
                    $badges_count++;
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
        print_r( Patt_Custom_Func::json_response(400, 'Missing employee_id and lan_id field'));
    }
    if(empty($type)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing type field'));
    }
}
?>