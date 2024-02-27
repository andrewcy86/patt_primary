<?php
/**
 * Template Name: AWS Trigger DataSync
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0 
 */

use Aws\RoboMaker\RoboMakerClient;

global $wpdb, $current_user, $wpscfunction;

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
//$stateMachineArn = 'arn:aws:states:us-east-1:114892021311:stateMachine:MyStateMachine-hw0c9jta8';

// Specify the ARN of the MapRun
$mapRunArn = 'arn:aws:states:us-east-1:114892021311:mapRun:MyStateMachine-hw0c9jta8/S3objectkeys:2d40542b-f042-4805-a29c-d64eba7cf865';

$describeMapRunParams = [
    'mapRunArn' => $mapRunArn
];

$status = '';

$pending_count = 0;
$running_count = 0;
$succeeded_count = 0;
$failed_count = 0;
$timedout_count = 0;
$aborted_count = 0;
$total_count = 0;

$start_date = '';
$end_date = '';

$describeMapRunResult = $sfnClient->describeMapRun($describeMapRunParams);

foreach($describeMapRunResult as $key => $value)
{   
    // Gets overall MapRun status
    if($key == 'status') {
        $status = $value;
    }

    // Gets the start and end datetimes
    if($key == 'startDate') {
        $start_date = $value;
    }
    if($key == 'stopDate') {
        $end_date = $value;
    }

    // Gets the invdividual item counts of MapRun
    foreach($value as $item_key => $item_value) 
    {
        if($item_key == 'pending') {
            $pending_count = $item_value;
        }
        if($item_key == 'running') {
            $running_count = $item_value;
        }
        if($item_key == 'succeeded') {
            $succeeded_count = $item_value;
        }
        if($item_key == 'failed') {
            $failed_count = $item_value;
        }
        if($item_key == 'timedOut') {
            $timedout_count = $item_value;
        }
        if($item_key == 'aborted') {
            $aborted_count = $item_value;
        }
        if($item_key == 'total') {
            $total_count = $item_value;
        }

    }
}

  echo "MapRun Arn: " . $mapRunArn . "</br>";
  echo "MapRun Status: " . $status . "</br>";

  echo "Start Date: " . $start_date->format('Y-m-d H:i:s') . "</br>";
  echo "End Date: " . $end_date->format('Y-m-d H:i:s') . "</br>";

  echo "Pending Count: " . $pending_count . "</br>";
  echo "Running Count: " . $running_count . "</br>";
  echo "Succeeded Count: " . $succeeded_count . "</br>";
  echo "Failed Count: " . $failed_count . "</br>";
  echo "Timed Out Count: " . $timedout_count . "</br>";
  echo "Aborted Count: " . $aborted_count . "</br>";
  echo "Total Count: " . $total_count . "</br>";


  $datasync_map_run_table = $wpdb->prefix . 'epa_datasync_map_run';

	// POPULATING Datasync Map Run Table
	// $datasync_map_run_table = $wpdb->get_results("SELECT DISTINCT(Schedule_Number) AS Schedule_Number
	// FROM " . $wpdb->prefix . "epa_record_schedule
	// WHERE Superseded_Flag = 0 and Deleted_Flag = 0 and Draft_Flag = 0 and Reserved_Flag = 0 and id != '-99999'");

    // $rs = $item->Schedule_Number;
    //echo $rs . "<br />";
    $wpdb->insert($datasync_map_run_table, array(
    'execution_arn_id_name' => '',
    'map_run_execution_arn_id' => $mapRunArn,
    'status' => $status,
    'pending_count' => $pending_count,
    'running_count' => $running_count,
    'succeeded_count' => $succeeded_count, 
    'failed_count' => $failed_count,
    'timedout_count' => $timedout_count, 
    'aborted_count' => $aborted_count,
    'total_count' => $total_count,
    'start_date' => $start_date->format('Y-m-d H:i:s'),
    'end_date' => $end_date->format('Y-m-d H:i:s') ));
?>