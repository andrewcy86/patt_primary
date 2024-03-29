<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

// Monitor the status of the Datasync
$dir = $_SERVER['DOCUMENT_ROOT'].'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

require_once($dir."/vendor/autoload.php");

// Check Datasync Table last row Status column before proceeding
$get_datasync_status = $wpdb->get_row("SELECT id as last_row, execution_arn_id, status
	FROM " . $wpdb->prefix . "epa_datasync_status
     ORDER BY id DESC LIMIT 1");

$datasync_status = $get_datasync_status->status;

$executionArnID = $get_datasync_status->execution_arn_id;

$epa_datasync_status_table = $wpdb->prefix . 'epa_datasync_status';
$epa_map_run_table = $wpdb->prefix . 'epa_datasync_map_run';

if($datasync_status == ''){
    function region() {
        return 'us-east-1';
    }
    
    $client = new Aws\Sts\StsClient([
        'version'     => 'latest',
        'region'  => region(),
        'endpoint' => STS_VPC_ENDPOINT
    ]);
    
    $ARN = PATT_CUSTOMER_ROLE;
    $sessionName = "AssumedRoleSession";
    
    $new_role = $client->AssumeRole([
        'RoleArn' => $ARN,
        'RoleSessionName' => $sessionName,
    ]);
    
    // Initialize the DataSync client
    $dataSyncClient = new Aws\DataSync\DataSyncClient([
        'version'     => 'latest',
        'region'  => region(),
        'credentials' =>  [
            'key'    => $new_role['Credentials']['AccessKeyId'],
            'secret' => $new_role['Credentials']['SecretAccessKey'],
            'token'  => $new_role['Credentials']['SessionToken']
        ]
    ]);
    
    
    
$TaskExecutionArnIDResults = $dataSyncClient->describeTaskExecution(['TaskExecutionArn' => $executionArnID]);
$datasyncResultsStatus = $TaskExecutionArnIDResults['Status'];

    if($datasyncResultsStatus == 'SUCCESS') {
        // Update Datasync Status Table
        $data_update = array('status' => $datasyncResultsStatus);
        $data_where = array('execution_arn_id' => $executionArnID);
        $wpdb->update($epa_datasync_status_table, $data_update, $data_where);

        
        // Execute State Machine/Step Function if status has changed from Running to Available
            $client = new Aws\Sts\StsClient([
                'version'     => 'latest',
                'region'  => region(),
                'endpoint' => STS_VPC_ENDPOINT
            ]);
        
            $ARN = PATT_CUSTOMER_ROLE;
            $sessionName = "AssumedRoleSession";
        
            $new_role = $client->AssumeRole([
                'RoleArn' => $ARN,
                'RoleSessionName' => $sessionName,
            ]);
        
            // Initialize the DataSync client
        
            $sfnClient = new Aws\Sfn\SfnClient([
                'version'     => 'latest',
                'region'  => region(),
                'credentials' =>  [
                    'key'    => $new_role['Credentials']['AccessKeyId'],
                    'secret' => $new_role['Credentials']['SecretAccessKey'],
                    'token'  => $new_role['Credentials']['SessionToken']
                ]
            ]);


            // Specify the ARN of the Step Function
            $stateMachineArn = PATT_STEP_FUNCTION_ARN;

            $mapRunArn = '';

            $inputData = '{"Comment": "Executed"}';

            $result = $sfnClient->startExecution([
                'stateMachineArn' => $stateMachineArn,
                'input'           => $inputData,
            ]);

            While(empty($mapRunArn)){
                //sleep for 5 seconds
                sleep(5);
                $listMapRuns = $sfnClient->listMapRuns([
                    'executionArn' => $result['executionArn'],
                ]);
            
                // echo 'Map Run Arn: ' . $listMapRuns['mapRuns'][0]['mapRunArn'];

                $mapRunArn = $listMapRuns['mapRuns'][0]['mapRunArn'];
                $stepFunctionArn = $listMapRuns['mapRuns'][0]['executionArn'];
            }

            $currentDateTime = new DateTime('now', new DateTimeZone('America/New_York'));
            $formatted = $currentDateTime->format('Y-m-d H:i:s');

            if(!empty($mapRunArn)){
                // POPULATING Map RunTable
                $wpdb->insert($epa_map_run_table, array( 'datasync_execution_arn' => $executionArnID, 'execution_arn_id_name' => $stepFunctionArn, 'map_run_execution_arn_id' => $mapRunArn, 'status' => '', 'start_time' => $formatted ) );
            }
        
    }
    
    

}



?>