<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

// Monitor the status of the Datasync
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -2)));

$dir = $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

require_once($dir."/vendor/autoload.php");

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

// Specify the ARN of the DataSync task
$taskArn = 'arn:aws:datasync:us-east-1:114892021311:task/task-0f1bfec48faf20b0b';

$datasync_status = '';


try {
    $result = $dataSyncClient->describeTask([
        'TaskArn' => $taskArn,
    ]);

    foreach($result as $key => $value)
    {
      if ($key == 'Status')    {
        echo "<strong>".$value."</strong>";
        $datasync_status = $value;
    }

    }

    //echo '<pre>'; print_r($result); echo '</pre>';

    //var_dump($result);

    //print_r($result);

} catch (Aws\Exception\AwsException $e) {
    // Handle exceptions
    echo "Error: " . $e->getMessage();
}



// Execute State Machine/Step Function if status has changed from Running to Available
if($datasync_status == 'Available'){
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

    echo 'Execution ARN: ' . $result['executionArn'];

    $epa_datasync_status_table = $wpdb->prefix . 'epa_datasync_status';

	// POPULATING Datasync Status Table

    $wpdb->insert($epa_datasync_status_table, array(
    'execution_arn_id' => $result['executionArn'],
    'status' => $datasync_status ));

}

?>