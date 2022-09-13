<?php
header('Content-Type: application/json; charset=utf-8');

include 'db_connection.php';
$conn = OpenCon();

//Check API Key to make sure it is valid
$api_key     = htmlspecialchars($_GET['api_key']);
$filter = htmlspecialchars($_GET['filter']);

$filter_arry = explode(',', $filter);


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

    if ($set_api == 1 && !empty($filter)) {

        $query_rs = "SELECT *
        FROM wpqa_epa_record_schedule 
        WHERE Schedule_Item_Number = '" . $filter_arry[2] . "' LIMIT 1";

            $result_rs = mysqli_query($conn, $query_rs);
            
            $rows = array();
                        while($r = mysqli_fetch_assoc($result_rs)) {
                            $rows[] = $r;
                        }
        if(!empty($rows)) {
          	$json_results = json_encode($rows);
          
            print '{"records":'.$json_results.'}';
        }
        else {
            print_r(Patt_Custom_Func::json_response(500, 'No results could be retrieved from the record schedule table.'));
        }

        
    }
    else {
        if($set_api == 0) {
            print_r( Patt_Custom_Func::json_response(422, 'api_key of ' . $api_key . ' not found'));
        }
        if(empty($filter)) {
            print_r( Patt_Custom_Func::json_response(400, 'Missing filter'));
        }
    }
    
}
else {
    print_r( Patt_Custom_Func::json_response(400, 'Missing api_key field'));
}
?>