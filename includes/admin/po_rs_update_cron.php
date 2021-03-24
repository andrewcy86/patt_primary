<?php
//if ( ! defined( 'ABSPATH' ) ) {
//  exit; // Exit if accessed directly
//}

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $current_user, $wpscfunction, $wpdb;

$endpoint = "https://api.edap-cluster.com/ecms-graphql/graphql";
$count = 0;
$error_table = $wpdb->prefix . 'epa_error_log';

//Program Office
$po_query = "query officeQuery {
  programOffices(
    orderBy: [ID_ASC, ORGANIZATION_DESC]
  ) {
    nodes {
      id
      organization
      organizationDescription
      organizationAcronym
      officeCode
      officeAcronym
      officeName
      parentOfficeCode
    }
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

if ($po_result === FALSE) { 

$wpdb->insert($error_table, array(
    'Status_Code' => $http_response_header[0],
    'Error_Message' => '',
    'Service_Type' => 'PO RS Cron'
));
    
}

echo $http_response_header[0];
var_dump($http_response_header);
//var_dump($result);

$po_json = json_decode($po_result, true);

$organizationDescription = $po_json['data']['programOffices']['nodes'][0]['organizationDescription'];

//echo $organizationDescription;

$po_table = $wpdb->prefix . 'wpsc_epa_program_office';

//TRUNCATE STATEMENT

$wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

$wpdb->query("TRUNCATE TABLE $po_table");

$wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
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

foreach ($po_json['data']['programOffices']['nodes'] as $po_item)
{
//print_r($id);

foreach ($po_item as $key => $value) {

$organization = $po_item['organization'];
$organizationDescription = $po_item['organizationDescription'];
$organizationAcronym = $po_item['organizationAcronym'];
$officeCode = $po_item['officeCode'];
$officeAcronym = $po_item['officeAcronym'];
$officeName = $po_item['officeName'];
$parentOfficeCode = $po_item['parentOfficeCode'];

/*
//Remove - if no characters after -
$preg_replace_program_office = preg_replace("/\([^)]+\)/","",$officeAcronym);
if(substr($preg_replace_program_office, -1) == '-') {
    $office_acronym = substr($preg_replace_program_office, 0, -1);
} else {
    $office_acronym = $preg_replace_program_office;
}
*/
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

//echo $organization.', '.$organizationDescription.', '.$organizationAcronym.', '.$officeCode.', '.$officeAcronym.', '.$officeName.', '.$parentOfficeCode.'<br />';

//echo '<hr />';
}

//Record Schedule

$rs_query = "query schedulesQuery {
  recordSchedules(
    orderBy: [ID_ASC]
  ) {
    nodes {
      id
      recordScheduleNumber
      scheduleNumber
      scheduleTitle
      dispositionNumber
      dispositionTitle
      functionNumber
      functionTitle
      program
      applicability
      naraDisposalAuthorityRecordScheduleLevel
      naraDisposalAuthorityItemLevel
      finalDisposition
      cutoffInstructions
      close
      description
      reservedFlag
      dispositionInstructions
      guidance
      retention
      tenYear
      epaApproval
      naraApproval
      previousNaraDisposalAuthority
      status
      custodians
      reasonsForDisposition
      relatedSchedules
      entry
      revised
      keywords
      keywordsTitle
      keywordsSubject
      keywordsOrg
      relatedTerms
    }
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
$wpdb->insert($error_table, array(
    'Status_Code' => $http_response_header[0],
    'Error_Message' => '',
    'Service_Type' => 'PO RS Cron'
));
}

//var_dump($result);

$rs_json = json_decode($rs_result, true);

$rs_table = $wpdb->prefix . 'epa_record_schedule';

//TRUNCATE STATEMENT

$wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

$wpdb->query("TRUNCATE TABLE $rs_table");

$wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

//Insert -99999
$wpdb->insert($rs_table, array(
'id' => '-99999',
'Record_Schedule_Number' => '-99999',
'Schedule_Number' => '-99999',
'Schedule_Title' => '',
'Disposition_Number' => '',
'Disposition_Title' => '',
'Function_Number' => '',
'Function_Title' => '',
'Program' => '',
'Applicability' =>'', 
'NARA_Disposal_Authority_Record_Schedule_Level' => '',
'NARA_Disposal_Authority_Item_Level' => '',
'Final_Disposition' => '',
'Cutoff_Instructions' => '',
'Close' => '',
'Description' => '',  
'Reserved_Flag' => 0,
'Disposition_Instructions' => '',
'Guidance' => '',
'Retention' => NULL,
'Ten_Year' => '-99999',
'EPA_Approval' => '', 
'NARA_Approval' => '', 
'Previous_NARA_Disposal_Authority' => '', 
'Status' => '',
'Custodians' => '', 
'Reasons_For_Disposition' => '', 
'Related_Schedules' => '', 
'Entry' => '', 
'Revised' => '',
'Keywords' => '',
'Keywords_Title' => '',
'Keywords_Subject' => '',
'Keywords_Org' => '',
'Related_Terms' => ''
));

foreach ($rs_json['data']['recordSchedules']['nodes'] as $rs_item)
{
foreach ($rs_item as $key => $value) {

$Record_Schedule_Number = $rs_item['recordScheduleNumber'];
$Schedule_Number = $rs_item['scheduleNumber'];
$Schedule_Title = addcslashes($rs_item['scheduleTitle'], "'");
$Disposition_Number = $rs_item['dispositionNumber'];
$Disposition_Title = addcslashes($rs_item['dispositionTitle'], "'");
$Function_Number = addcslashes($rs_item['functionNumber'], "'");
$Function_Title = $rs_item['functionTitle'];
$Program = $rs_item['program'];
$Applicability = $rs_item['applicability'];
$NARA_Disposal_Authority_Record_Schedule_Level = addcslashes($rs_item['naraDisposalAuthorityRecordScheduleLevel'], "'");
$NARA_Disposal_Authority_Item_Level = $rs_item['naraDisposalAuthorityItemLevel'];
$Final_Disposition = addcslashes($rs_item['finalDisposition'], "'");
$Cutoff_Instructions = addcslashes($rs_item['cutoffInstructions'], "'");
$Close = addcslashes($rs_item['close'], "'");
$Description = addcslashes($rs_item['description'], "'");
$Reserved_Flag = $rs_item['reservedFlag'];
$Disposition_Instructions = addcslashes($rs_item['dispositionInstructions'], "'");
$Guidance = addcslashes($rs_item['guidance'], "'");
$Retention = $rs_item['retention'];
$Ten_Year = $rs_item['tenYear'];
$EPA_Approval = $rs_item['epaApproval'];
$NARA_Approval = $rs_item['naraApproval'];
$Previous_NARA_Disposal_Authority = addcslashes($rs_item['previousNaraDisposalAuthority'], "'");
$Status = $rs_item['status'];
$Custodians = addcslashes($rs_item['custodians'], "'");
$Reasons_For_Disposition = addcslashes($rs_item['reasonsForDisposition'], "'");
$Related_Schedules = addcslashes($rs_item['relatedSchedules'], "'");
$Entry = $rs_item['entry'];
$Revised = $rs_item['revised'];
$Keywords = addcslashes($rs_item['keywords'], "'");
$Keywords_Title = addcslashes($rs_item['keywordsTitle'], "'");
$Keywords_Subject = addcslashes($rs_item['keywordsSubject'], "'");
$Keywords_Org = addcslashes($rs_item['keywordsOrg'], "'");
$Related_Terms = addcslashes($rs_item['relatedTerms'], "'");
}

//INSERT STATEMENT

$wpdb->query("INSERT INTO ".$rs_table." 
(Record_Schedule_Number,
Schedule_Number,
Schedule_Title,
Disposition_Number,
Disposition_Title,
Function_Number,
Function_Title,
Program,
Applicability, 
NARA_Disposal_Authority_Record_Schedule_Level,
NARA_Disposal_Authority_Item_Level,
Final_Disposition,
Cutoff_Instructions,
Close,
Description,  
Reserved_Flag,
Disposition_Instructions,
Guidance,
Retention,
Ten_Year,
EPA_Approval, 
NARA_Approval, 
Previous_NARA_Disposal_Authority, 
Status,
Custodians, 
Reasons_For_Disposition, 
Related_Schedules, 
Entry, 
Revised,
Keywords,
Keywords_Title,
Keywords_Subject,
Keywords_Org,
Related_Terms) 
VALUES (
'$Record_Schedule_Number',
'$Schedule_Number',
'$Schedule_Title',
'$Disposition_Number',
'$Disposition_Title',
'$Disposition_Title',
'$Function_Title',
'$Program',
'$Applicability', 
'$NARA_Disposal_Authority_Record_Schedule_Level',
'$NARA_Disposal_Authority_Item_Level',
'$Final_Disposition',
'$Cutoff_Instructions',
'$Close',
'$Description',  
'$Reserved_Flag',
'$Disposition_Instructions',
'$Guidance',
'$Retention',
'$Ten_Year',
'$EPA_Approval', 
'$NARA_Approval', 
'$Previous_NARA_Disposal_Authority', 
'$Status',
'$Custodians', 
'$Reasons_For_Disposition', 
'$Related_Schedules', 
'$Entry', 
'$Revised',
'$Keywords',
'$Keywords_Title',
'$Keywords_Subject',
'$Keywords_Org',
'$Related_Terms'
)"  );

}
?>