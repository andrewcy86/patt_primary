<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

// Monitor the status of the Datasync
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -2)));

// $dir = $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/app/mu-plugins/pattracking/includes/admin/pages/scripts';
 $dir = '/public/server/htdocs/web/app/mu-plugins/pattracking/includes/admin/pages/scripts';

require_once($dir."/vendor/autoload.php");

// Check Datasync Table last row Status column before proceeding
$get_datasync_status = $wpdb->get_row("SELECT MAX(id) as last_row, execution_arn_id, status
    FROM " . $wpdb->prefix . "epa_datasync_status");

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
        'endpoint' => 'https://vpce-07a469e9e500866e6-wrptt4b4.sts.us-east-1.vpce.amazonaws.com'
    ]);
    
    $ARN = "arn:aws:iam::114892021311:role/Customer-PATT-Datasync-Access";
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
                'endpoint' => 'https://vpce-07a469e9e500866e6-wrptt4b4.sts.us-east-1.vpce.amazonaws.com'
            ]);
        
            $ARN = "arn:aws:iam::114892021311:role/Customer-PATT-Datasync-Access";
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
            $stateMachineArn = 'arn:aws:states:us-east-1:114892021311:stateMachine:MyStateMachine-hw0c9jta8';
        
            $inputData = '{"Comment": "Executed"}';
        
            $result = $sfnClient->startExecution([
                'stateMachineArn' => $stateMachineArn,
                'input'           => $inputData,
            ]);

            // POPULATING Map RunTable
            $wpdb->insert($epa_map_run_table, array( 'execution_arn_id_name' => $executionArnID, 'map_run_execution_arn_id' => $result['executionArn'], 'status' => '' ) );

        
    
        
        
        
    }
    
    

}



?>