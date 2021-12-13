<?php
header('Content-Type: application/json; charset=utf-8');

global $wpdb;

include 'db_connection.php';
$conn = OpenCon();

//Check API Key to make sure it is valid
$api_key     = htmlspecialchars($_GET['api_key']);
$office_code = htmlspecialchars($_GET['office_code']);
$set_api = '';
$set_office_code = 0;
$parent_office_code = '';
$empty_office_code = 0;

if (!empty($api_key) && !empty($office_code)) {

    //Check API Key to make sure it is valid
    $query_api_key = "SELECT COUNT(id) AS COUNT
 FROM arms_game_application 
 WHERE api_key = '" . $api_key . "' LIMIT 1";
    
    $result_api_key = mysqli_query($conn, $query_api_key);
    
    while ($api_result = mysqli_fetch_array($result_api_key)) {
        $set_api = $api_result["COUNT"];
    }
    
    if(strlen($office_code) != 8) {
        $office_code = Patt_Custom_Func::company_name_conversion($office_code);
    }
            
    
    // Get AA Ship from office_code
    $query_organization = "SELECT organization
    FROM " . $wpdb->prefix . "wpsc_epa_program_office 
    WHERE office_code = '" . $office_code . "' LIMIT 1";
    $result_organization = mysqli_query($conn, $query_organization);
    
    while ($organization_result = mysqli_fetch_array($result_organization)) {
        $organization_code = $organization_result["organization"];
    }
    
    $query_parent_office_code = "SELECT office_code
    FROM " . $wpdb->prefix . "wpsc_epa_program_office 
    WHERE organization = '" . $organization_code . "' AND parent_office_code = '0' LIMIT 1";
    $result_parent_office_code = mysqli_query($conn, $query_parent_office_code);
    
    while ($parent_office_code_result = mysqli_fetch_array($result_parent_office_code)) {
        $parent_office_code = $parent_office_code_result["office_code"];
    }
    
    //Cross check office_code with receivers table to make sure it is valid
    $query_office_code = "SELECT COUNT(id) AS COUNT
    FROM arms_game_receivers
    WHERE office_code = '" . $parent_office_code . "' 
    LIMIT 1";
                
    $result_office_code = mysqli_query($conn, $query_office_code);
    
    while ($office_code_result = mysqli_fetch_array($result_office_code)) {
        $set_office_code = $office_code_result["COUNT"];
    }
    
    if($set_office_code > 0) {
        $set_office_code = 1;
    }

    if ($set_api == 1 && !empty($office_code) && $set_office_code == 1) {
        
    $query_receiver_info = "

SELECT a.id, a.lan_id, a.employee_id, a.points, b.name as level, a.rank as office_rank from (    
SELECT id, lan_id, employee_id, office_code, points, level_id, RANK() OVER( PARTITION BY office_code ORDER BY points DESC) AS Rank, FIND_IN_SET( points, (
SELECT GROUP_CONCAT( DISTINCT points
ORDER BY points DESC ) FROM arms_game_receivers)
) AS overall_rank
 FROM arms_game_receivers
ORDER BY office_code, points
) as a 
LEFT OUTER JOIN arms_game_levels b on a.level_id = b.id
where a.office_code = '".$parent_office_code."'  LIMIT 10";

    $result_receiver_info = mysqli_query($conn, $query_receiver_info);
    
    $rows = array();
                while($r = mysqli_fetch_assoc($result_receiver_info)) {
                    $rows[] = $r;
                }
if(!empty($rows)) {
    print json_encode($rows);
}
else {
    print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved from the office leaderboard.'));
}
}
    else {
        if($set_api == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'api_key of ' . $api_key . ' not found'));
        }
        if($set_office_code == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'office_code of ' . $office_code . ' not found'));
        }
    }
    
}
else {
    if(empty($api_key)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing api_key field'));
    }
    if(empty($office_code)) {
        print_r( Patt_Custom_Func::json_response(400, 'Missing office_code field'));
    }
}
?>