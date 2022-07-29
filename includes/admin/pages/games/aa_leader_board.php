<?php
header('Content-Type: application/json; charset=utf-8');

global $wpdb;

include 'db_connection.php';
$conn = OpenCon();

//Check API Key to make sure it is valid
$api_key     = htmlspecialchars($_GET['api_key']);
$set_api = '';
$set_office_code = 0;
$parent_office_code = '';
$empty_office_code = 0;

if (!empty($api_key)) {

    //Check API Key to make sure it is valid
    $query_api_key = "SELECT COUNT(id) AS COUNT
 FROM arms_game_application 
 WHERE api_key = '" . $api_key . "' LIMIT 1";
    
    $result_api_key = mysqli_query($conn, $query_api_key);
    
    while ($api_result = mysqli_fetch_array($result_api_key)) {
        $set_api = $api_result["COUNT"];
    }
    
    if ($set_api == 1) {
        
    $query_receiver_info = "
SELECT
  SUM(a.points) AS sum_points,
  b.organization_acronym
FROM arms_game_receivers a
LEFT JOIN wpqa_wpsc_epa_program_office b on a.office_code = b.office_code
GROUP BY b.organization_acronym
ORDER BY SUM(a.points) DESC
";

    $result_receiver_info = mysqli_query($conn, $query_receiver_info);
    
    $rows = array();
                while($r = mysqli_fetch_assoc($result_receiver_info)) {
                    $rows[] = $r;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved from the AA leaderboard.'));
}
}
    else {
        if($set_api == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'api_key of ' . $api_key . ' not found'));
        }
    }
    
}
else {
    if(empty($api_key)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing api_key field'));
    }
}
?>