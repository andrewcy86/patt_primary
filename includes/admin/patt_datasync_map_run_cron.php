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


$dir = $_SERVER['DOCUMENT_ROOT'].'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

require_once($dir."/vendor/autoload.php");

// Check Datasync Table last row Status column before proceeding
$get_datasync_status = $wpdb->get_row("SELECT id as last_row, execution_arn_id, status
	FROM " . $wpdb->prefix . "epa_datasync_status
     ORDER BY id DESC LIMIT 1");
	
	$latest_datasync_record_status = $get_datasync_status->status;
	$latest_datasync_execution_arn = $get_datasync_status->execution_arn_id;
	
$get_map_run_status = $wpdb->get_row("SELECT id as last_row, map_run_execution_arn_id, status
    FROM " . $wpdb->prefix . "epa_datasync_map_run
     WHERE datasync_execution_arn ='" . $latest_datasync_execution_arn . "'");

$map_run_status = $get_map_run_status->status;

$map_run_executionArnID = $get_map_run_status->map_run_execution_arn_id;

$map_run_table = $wpdb->prefix . 'epa_datasync_map_run';

if($map_run_status == ''){


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
    $sfnClient = new Aws\Sfn\SfnClient([
        'version'     => 'latest',
        'region'  => region(),
        'credentials' =>  [
            'key'    => $new_role['Credentials']['AccessKeyId'],
            'secret' => $new_role['Credentials']['SecretAccessKey'],
            'token'  => $new_role['Credentials']['SessionToken']
        ]
    ]);

    
   
    $describeMapRunParams = [
        'mapRunArn' => $map_run_executionArnID
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
        
        
        
        if($status != 'RUNNING' && !empty($status)) {
            $end_date = new DateTime($end_date);
            $end_date->setTimezone(new DateTimeZone('America/New_York'));
            $new_end_date = $end_date->format('Y-m-d H:i:s');

            $data_update = array(
            'status' => $status,
            'pending_count' => $pending_count,
            'running_count' => $running_count,
            'succeeded_count' => $succeeded_count, 
            'failed_count' => $failed_count,
            'timed_out_count' => $timedout_count, 
            'aborted_count' => $aborted_count,
            'total_count' => $total_count,
            'end_time' => $new_end_date );

            $data_where = array('map_run_execution_arn_id' => $map_run_executionArnID);
            $wpdb->update($map_run_table, $data_update, $data_where);

    }

}
?>