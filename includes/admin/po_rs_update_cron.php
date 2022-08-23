<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $current_user, $wpscfunction, $wpdb;

// Endpoint needs to be added into environment variable for staging environment
$endpoint = "https://data.epa.gov/dmapservice/gateway-query. ";
$count = 0;

$po_table = $wpdb->prefix . 'wpsc_epa_program_office';
$rs_table = $wpdb->prefix . 'epa_record_schedule';

$po_query = "query programOffice {

ecms__program_office {

     __all_columns__

}

}";

$po_data = array ('query' => $po_query);
$po_data = http_build_query($po_data);

$po_options = array(
  'http' => array(
    'method'  => 'POST',
    'content' => $po_data
  )
);

$po_context  = stream_context_create($po_options);
$po_result = file_get_contents($endpoint, false, $po_context);
// var_dump('po results: ' . $po_result);
// var_dump($po_context);

if ($po_result === FALSE) { 

    $err = Patt_Custom_Func::convert_http_error_code($http_response_header[0]);
    Patt_Custom_Func::insert_api_error('datacommons-po-rs-cron',$http_response_header[0],$err);
    // var_dump($err); 
} else {

    //TRUNCATE STATEMENT
    $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

    $wpdb->query("TRUNCATE TABLE $po_table");

    $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

$po_json = json_decode($po_result, true);
// var_dump($po_json);

//$po_json_api_count = $po_json['data']['ecms__program_office__aggregate'][0];
// var_dump($po_json['data']['ecms__program_office__aggregate'][0]);

// $organizationDescription = $po_json['data']['programOffices']['nodes'][0]['organizationDescription'];
$organizationDescription = $po_json['data']['ecms__program_office'][0]['organization_description'];

// var_dump($po_json['data']['ecms__program_office']);

//echo $organizationDescription;

//Insert -99999
$wpdb->insert($po_table, array(
    'id' => '-99999',
    'organization' => '-99999',
    'organization_description' => '-99999',
    'organization_acronym' => '-99999',
    'office_code' => '-99999',
    'office_acronym' => '-99999',
    'office_name' => '-99999',
    'parent_office_code' => ''
));

$po_count = 0;
  
foreach ($po_json['data']['ecms__program_office'] as $po_item)
{

$po_count++;
  
// print_r('test' . $id);

foreach ($po_item as $key => $value) {

$organization = $po_item['organization'];
$organizationDescription = $po_item['organization_description'];
$organizationAcronym = $po_item['organization_acronym'];
$officeCode = $po_item['office_code'];
$officeAcronym = $po_item['office_acronym'];
// $officeName = $po_item['office_name'];
$officeName = RemoveSpecialChar($po_item['office_name']);
$parentOfficeCode = $po_item['parent_office_code'];
}

//INSERT STATEMENT
$wpdb->insert($po_table, array(
    'organization' => $organization,
    'organization_description' => $organizationDescription,
    'organization_acronym' => $organizationAcronym,
    'office_code' => $officeCode,
    'office_acronym' => $officeAcronym,
    'office_name' => $officeName,
    'parent_office_code' => $parentOfficeCode
));

echo $organization.', '.$organizationDescription.', '.$organizationAcronym.', '.$officeCode.', '.$officeAcronym.', '.$officeName.', '.$parentOfficeCode.'<br />';

echo '<hr />';
}

//COUNT STATEMENT
$po_query_count = $wpdb->get_results('SELECT COUNT(*) - 1 as total_records from ' . $po_table);
$po_query_count = (int)$po_query_count[0]->total_records;
// var_dump((int)$po_query_count[0]->total_records);

if ($po_count != $po_query_count) { 

    $err = Patt_Custom_Func::convert_http_error_code($http_response_header[0]);
    Patt_Custom_Func::insert_api_error('datacommons-po-rs-cron',$http_response_header[0],$err);
    // var_dump($err);
}

}
//Record Schedule

$rs_query = "query recSched {

ecms__record_schedule {

     __all_columns__

}

}";
    

$rs_data = array ('query' => $rs_query);
$rs_data = http_build_query($rs_data);

$rs_options = array(
  'http' => array(
    'method'  => 'POST',
    'content' => $rs_data
  )
);

$rs_context  = stream_context_create($rs_options);
$rs_result = file_get_contents($endpoint, false, $rs_context);

if ($rs_result === FALSE) { 
$err = Patt_Custom_Func::convert_http_error_code($http_response_header[0]);
Patt_Custom_Func::insert_api_error('datacommons-po-rs-cron',$http_response_header[0],$err);
} else {

//var_dump($rs_result);

$rs_json = json_decode($rs_result, true);

//var_dump($rs_json['data']['ecms__record_schedule'][0]);

//$rs_json_api_count = $rs_json['data']['ecms__record_schedule__aggregate'][0];
//var_dump($rs_json['data']['ecms__record_schedule__aggregate'][0]);

//TRUNCATE STATEMENT

$wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

$wpdb->query("TRUNCATE TABLE $rs_table");

$wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

//Insert -99999
$wpdb->insert($rs_table, array(
'id' => '-99999',
'Schedule_Item_Number' => '-99999',
'Schedule_Number' => '-99999',
'Schedule_Title' => '',
'Item_Number' => '',
'Item_Title' => '',
'Function_Code' => '',
'Function_Title' => '',
'Program' => '',
'Applicability' =>'', 
'NARA_Disposal_Authority_Record_Schedule_Level' => '',
'NARA_Disposal_Authority_Item_Level' => '',
'Final_Disposition' => '',
'Cutoff_Instructions' => '',
'Disposition_Instructions' => '',
'Schedule_Description' => '',
'Reserved_Flag' => 0,

'Superseded_Flag' => 0,
'Deleted_Flag' => 0,
'Draft_Flag' => 0,
'System_Flag' => 0,
'Calendar_Year_Flag' => 0,
'Fiscal_Year_Flag' => 0,

'Disposition_Summary' => '',  
'Guidance' => '',
'Retention_Year' => '',
'Retention_Month' => '',
'Retention_Day' => '',

'Ten_Year' => 0,

'DNUL_Flag' => 0,
'Last_Modified_Flag' => 0,

'EPA_Approval' => '',
'NARA_Approval' => '',
'Previous_NARA_Disposal_Authority' => '',

'Status' => '',
'Custodians' => '',
'Reasons_For_Disposition' => '', 
'Related_Schedules' => '', 
'Entry_Date' => '', 
'Revised_Date' => '',

'Action' => '',

'Keywords' => '',
'Keywords_Title' => '',
'Keywords_Subject' => '',
'Keywords_Org' => '',
'Related_Terms' => ''
));

$rs_count = 0;
  
foreach ($rs_json['data']['ecms__record_schedule'] as $rs_item)
{
$rs_count++;
foreach ($rs_item as $key => $value) {
//echo $key.' => '.$value;
$Schedule_Item_Number = $rs_item['schedule_item_number'];
$Schedule_Number = $rs_item['schedule_number'];
$Schedule_Title = addcslashes($rs_item['schedule_title'], "'");
$Item_Number = $rs_item['item_number'];
$Item_Title = addcslashes($rs_item['item_title'], "'");
$Function_Code = addcslashes(intval($rs_item['function_code']), "'");
$Function_Title = $rs_item['function_title'];
$Program = $rs_item['program'];
$Applicability = $rs_item['applicability'];
$NARA_Disposal_Authority_Record_Schedule_Level = addcslashes($rs_item['nara_disposal_authority_record_schedule_level'], "'");
$NARA_Disposal_Authority_Item_Level = $rs_item['nara_disposal_authority_item_level'];
$Final_Disposition = addcslashes($rs_item['final_disposition'], "'");
$Cutoff_Instructions = addcslashes($rs_item['cutoff_instructions'], "'");
$Disposition_Instructions = addcslashes($rs_item['disposition_instructions'], "'");
$Schedule_Description = addcslashes($rs_item['schedule_description'], "'");
$Reserved_Flag = $rs_item['reserved_flag'];

$Superseded_Flag = addcslashes($rs_item['superseded_flag'], "'");
$Deleted_Flag = addcslashes($rs_item['deleted_flag'], "'");
$Draft_Flag = addcslashes($rs_item['draft_flag'], "'");
$System_Flag = addcslashes($rs_item['system_flag'], "'");
$Calendar_Year_Flag = addcslashes($rs_item['calendar_year_flag'], "'");
$Fiscal_Year_Flag = addcslashes($rs_item['fiscal_year_flag'], "'");

$Disposition_Summary = addcslashes($rs_item['disposition_summary'], "'");
$Guidance = addcslashes($rs_item['guidance'], "'");

$Retention_Year = addcslashes($rs_item['retention_year'], "'");
$Retention_Month = addcslashes($rs_item['retention_month'], "'");
$Retention_Day = addcslashes($rs_item['retention_day'], "'");

$Ten_Year = $rs_item['ten_year'];

$DNUL_Flag = addcslashes($rs_item['dnul_flag'], "'");
$Last_Modified_Flag = addcslashes($rs_item['last_modified_flag'], "'");

$EPA_Approval = date('Y-m-d', strtotime($rs_item['epa_approval']));
$NARA_Approval = date('Y-m-d', strtotime($rs_item['nara_approval']));
$Previous_NARA_Disposal_Authority = addcslashes($rs_item['previous_nara_disposal_authority'], "'");
$Status = $rs_item['status'];
$Custodians = addcslashes($rs_item['custodians'], "'");
$Reasons_For_Disposition = addcslashes($rs_item['reasons_for_disposition'], "'");
$Related_Schedules = addcslashes($rs_item['related_schedules'], "'");
$Entry_Date = date('Y-m-d', strtotime($rs_item['entry_date']));
$Revised_Date = date('Y-m-d', strtotime($rs_item['revised_date']));

$Action = addcslashes($rs_item['action'], "'");

$Keywords = addcslashes($rs_item['keywords'], "'");
$Keywords_Title = addcslashes($rs_item['keywords_title'], "'");
$Keywords_Subject = addcslashes($rs_item['keywords_subject'], "'");
$Keywords_Org = addcslashes($rs_item['keywords_org'], "'");
$Related_Terms = addcslashes($rs_item['related_terms'], "'");
}

//INSERT STATEMENT

$wpdb->query("INSERT INTO ".$rs_table." 
(Schedule_Item_Number,
Schedule_Number,
Schedule_Title,
Item_Number,
Item_Title,
Function_Code,
Function_Title,
Program,
Applicability,
NARA_Disposal_Authority_Record_Schedule_Level,
NARA_Disposal_Authority_Item_Level,
Final_Disposition,
Cutoff_Instructions,
Disposition_Instructions,
Schedule_Description,
Reserved_Flag,

Superseded_Flag,
Deleted_Flag,
Draft_Flag,
System_Flag,
Calendar_Year_Flag,
Fiscal_Year_Flag,

Disposition_Summary,
Guidance,

Retention_Year,
Retention_Month,
Retention_Day,

Ten_Year,

DNUL_Flag,
Last_Modified_Flag,

Status,
Revised_Date,

Action,

Reasons_For_Disposition,
Custodians,
Related_Schedules,
Previous_NARA_Disposal_Authority,
Entry_Date,
EPA_Approval,
NARA_Approval,
Keywords,
Keywords_Title,
Keywords_Subject,
Keywords_Org,
Related_Terms) 
VALUES (
'$Schedule_Item_Number',
'$Schedule_Number',
'$Schedule_Title',
'$Item_Number',
'$Item_Title',
'$Function_Code',
'$Function_Title',
'$Program',
'$Applicability',
'$NARA_Disposal_Authority_Record_Schedule_Level',
'$NARA_Disposal_Authority_Item_Level',
'$Final_Disposition',
'$Cutoff_Instructions',
'$Disposition_Instructions',
'$Schedule_Description',
'$Reserved_Flag',

'$Superseded_Flag',
'$Deleted_Flag',
'$Draft_Flag',
'$System_Flag',
'$Calendar_Year_Flag',
'$Fiscal_Year_Flag',

'$Disposition_Summary',
'$Guidance',

'$Retention_Year',
'$Retention_Month',
'$Retention_Day',

'$Ten_Year',

'$DNUL_Flag',
'$Last_Modified_Flag',

'$Status',
'$Revised_Date',

'$Action',

'$Reasons_For_Disposition',
'$Custodians',
'$Related_Schedules',
'$Previous_NARA_Disposal_Authority',
'$Entry_Date',
'$EPA_Approval',
'$NARA_Approval',
'$Keywords',
'$Keywords_Title',
'$Keywords_Subject',
'$Keywords_Org',
'$Related_Terms'
)"  );

}

//COUNT STATEMENT
$rs_query_count = $wpdb->get_results('SELECT COUNT(*) - 1 as total_records from ' . $rs_table);
$rs_query_count = (int)$rs_query_count[0]->total_records;
// var_dump($rs_query_count);

if ($rs_count != $rs_query_count) { 

    $err = Patt_Custom_Func::convert_http_error_code($http_response_header[0]);
    Patt_Custom_Func::insert_api_error('datacommons-po-rs-cron',$http_response_header[0],$err);
    // var_dump($err);
}

}
// PHP program to Remove 
  // Special Character From String
  
  // Function to remove the spacial 
  function RemoveSpecialChar($str){
	$str = str_replace("&", ' And ', $str); // Replaces all ampersand symbols with "And" string text.
    $str = str_replace("Gov'T", 'Government', $str); // Replaces all instaces of "Gov't" to Government.
    
    // Using preg_replace() function 
    // to replace the word 
    $res = preg_replace('/[^a-zA-Z0-9_ -]/s',' ',$str);

    // Returning the result 
    return $res;
}
?>