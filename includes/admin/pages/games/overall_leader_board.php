<?php
header('Content-Type: application/json; charset=utf-8');

global $wpdb;

include 'db_connection.php';
$conn = OpenCon();

    //Check API Key to make sure it is valid
    $query_receiver_info = "

SELECT a.id, a.lan_id, a.employee_id, a.points, b.name as level, a.overall_rank as rank from (    
SELECT id, lan_id, employee_id, office_code, points, level_id, RANK() OVER( PARTITION BY office_code ORDER BY points DESC) AS Rank, FIND_IN_SET( points, (
SELECT GROUP_CONCAT( DISTINCT points
ORDER BY points DESC ) FROM arms_game_receivers)
) AS overall_rank
 FROM arms_game_receivers
ORDER BY office_code, points
) as a 
LEFT OUTER JOIN arms_game_levels b on a.level_id = b.id
ORDER BY overall_rank
LIMIT 10
";
    
    $result_receiver_info = mysqli_query($conn, $query_receiver_info);
    
    $rows = array();
                while($r = mysqli_fetch_assoc($result_receiver_info)) {
                    $rows[] = $r;
                }
                
if(empty($rows)){
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved from the leaderboard.'));
}
else {
    print json_encode($rows);
}

?>