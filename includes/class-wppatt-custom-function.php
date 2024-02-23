<?php


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


if (!class_exists('Patt_Custom_Func')) {

    class Patt_Custom_Func
    {

        public $table_prefix;
        /**
         * Get things started
         *
         * @access  public
         * @since   1.0
         */
        public function __construct()
        {            
            global $wpdb; 
            $this->table_prefix = $wpdb->prefix;
        }

public static function rescan_workflow($folderfile_id) {
    global $wpdb;

    $folderfiles_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
    $logs_table = $wpdb->prefix . 'epa_patt_arms_logs';
    $error_flag = 0;

    $get_folderdocinfofiles = $wpdb->get_row("SELECT object_key, binary_filepath, text_filepath
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE folderdocinfofile_id = '".$folderfile_id."'");

    if(!empty($get_folderdocinfofiles->object_key)) {
        
        // 1. Call the deleteProcessedFile lambda function and delete files in the SAN
        $curl_delete = curl_init();
        curl_setopt_array($curl_delete, [
            CURLOPT_URL => "https://jhj9wzuv69.execute-api.us-east-1.amazonaws.com/test",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"binary_directory": "' . $get_folderdocinfofiles->binary_filepath . '","text_directory": "' . $get_folderdocinfofiles->text_filepath . '","workflow": "rescan","nuxeo_id": "' . $get_folderdocinfofiles->object_key . '"}',
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        ]);

        $delete_response = curl_exec($curl_delete);
        $http_code_delete = curl_getinfo($curl_delete, CURLINFO_HTTP_CODE);
        curl_close($curl_delete);

        if ($http_code_delete != 200) {
            Patt_Custom_Func::insert_api_error("rescan-lambda-function",$http_code_delete,$delete_response);
            $error_flag = 1;
        }

        // Only execute if the delete lambda function ran successfully
        if($error_flag == 0) {
            // 2. Set object_key in folderdocinfofiles table to empty
            $date_time = date('Y-m-d H:i:s');
            $data_update_folderfile = array('object_key' => '', 'date_updated' => $date_time);
            $data_where_folderfile = array('folderdocinfofile_id' => $folderfile_id);
            $wpdb->update($folderfiles_table, $data_update_folderfile, $data_where_folderfile);

            // 3. Delete log for the folder/file ID
            $wpdb->delete($logs_table, array('folderdocinfofile_id' => $folderfile_id));
        }
    }
    else {
        Patt_Custom_Func::insert_api_error('rescan-missing-object-key',500,'Missing Nuxeo Document ID.');
    }
} 

public static function validation_workflow($folderfile_id) {
    global $wpdb;

    /* RESTRICTIONS
    1. Child document cannot be validated until the associated parent has been validated.
    2. Child documents cannot have children.
    */

    $logs_table = $wpdb->prefix . 'epa_patt_arms_logs';
    $disposition_date = '';
    $error_array = array();

    $get_folderdocinfofiles = $wpdb->get_row("SELECT a.id, a.parent_id, a.object_key, a.close_date, c.Schedule_Item_Number as record_schedule, d.freeze_approval, a.access_restriction, a.use_restriction, a.lan_id, b.program_office_id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id 
    INNER JOIN " . $wpdb->prefix . "epa_record_schedule c ON c.id = b.record_schedule_id
    INNER JOIN " . $wpdb->prefix . "wpsc_ticket d ON d.id = b.ticket_id
    WHERE a.folderdocinfofile_id = '".$folderfile_id."'");

    // Before beginning process make sure object_key is not empty
    if(!empty($get_folderdocinfofiles->object_key)) {
    
    // 1. Update parent/child relationship in ARMS before moving from the Unpublished folder

    // If document is a child then update parent/child metadata
    if(Patt_Custom_Func::parent_child_indicator($folderfile_id, 'folderfile') == 1) {

        $get_parent = $wpdb->get_row("SELECT object_key
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE id = '".$get_folderdocinfofiles->parent_id."'");  

        // 1a. Add link to the parent document
        $attach_child_curl = curl_init();
        curl_setopt_array($attach_child_curl, array(
        CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/api/v1/id/' . $get_folderdocinfofiles->object_key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS =>'{
            "entity-type": "document",
            "properties": {
                "arms:relation_is_part_of":["' . $get_parent->object_key . '"]
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
        ),
        ));

        $attach_child_response = curl_exec($attach_child_curl);
        $http_code_attach_child = curl_getinfo($attach_child_curl, CURLINFO_HTTP_CODE);
        curl_close($attach_child_curl);

        if(intval($http_code_attach_child) != 200) {
        $attach_child_error = Patt_Custom_Func::convert_http_error_code($http_code_attach_child);
        Patt_Custom_Func::insert_api_error('validation-update-parent-of-child',$http_code_attach_child,$attach_child_error);
        array_push($error_array, 1);
        }

        // 1b. Get all current children linked to a parent and add new child to list
        $curl_get_record = curl_init();
        curl_setopt_array($curl_get_record, array(
        CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/api/v1/id/' . $get_parent->object_key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'X-NXproperties: *',
            'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
        ),
        ));

        $response_get_record = curl_exec($curl_get_record);
        $http_code_get_record = curl_getinfo($curl_get_record, CURLINFO_HTTP_CODE);
        curl_close($curl_get_record);

        $get_record_obj = json_decode($response_get_record, true);
        $child_arr = $get_record_obj['properties']['arms:relation_has_part'];

        if(count($child_arr) == 0) {
        $child_arr = array($get_folderdocinfofiles->object_key);
        }
        else {
        array_push($child_arr, $get_folderdocinfofiles->object_key);
        }
        
        $update_parent_curl = curl_init();
        curl_setopt_array($update_parent_curl, array(
        CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/api/v1/id/' . $get_parent->object_key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS =>'{
            "entity-type": "document",
            "properties": {
                "arms:relation_has_part":' . json_encode($child_arr) . '
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
        ),
        ));

        $update_parent_response = curl_exec($update_parent_curl);
        $http_code_update_parent = curl_getinfo($update_parent_curl, CURLINFO_HTTP_CODE);
        curl_close($update_parent_curl);

        if(intval($http_code_update_parent) != 200) {
        $update_parent_error = Patt_Custom_Func::convert_http_error_code($http_code_update_parent);
        Patt_Custom_Func::insert_api_error('validation-update-children-of-parent',$http_code_attach_child,$update_parent_error);
        array_push($error_array, 0);
        }
    }
        
    // 2. Call disposition endpoint to get disposition date
    if(!empty($get_folderdocinfofiles->close_date) && $get_folderdocinfofiles->close_date != '0000-00-00') {
        $curl_disposition_date = curl_init();
        curl_setopt_array($curl_disposition_date,
        array(
            CURLOPT_URL => 'https://arms-uploader-api-pl.cis-dev.aws.epa.gov/disposition_calc?record_schedule='.$get_folderdocinfofiles->record_schedule.'&close_date='.$get_folderdocinfofiles->close_date,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET'));

        $response_disposition_date = curl_exec($curl_disposition_date);
        $http_code_disposition_date = curl_getinfo($curl_disposition_date, CURLINFO_HTTP_CODE);
        curl_close($curl_disposition_date);

        $obj = json_decode($response_disposition_date);

        if(intval($http_code_disposition_date) != 200) {
            $disposition_date_error = Patt_Custom_Func::convert_http_error_code($http_code_disposition_date);
            Patt_Custom_Func::insert_api_error('validation-disposition-date-endpoint',$http_code_disposition_date,$disposition_date_error);
            array_push($error_array, 0);
        }
        else {
            $disposition_date = new DateTime($obj->{'disposition_date'}, new DateTimeZone('EST'));
            $disposition_date->setTimezone(new DateTimeZone('UTC'));
            $disposition_date = $disposition_date->format('Y-m-d\TH:i:s.v\Z');
            array_push($error_array, 1);
        }
    }

    // 3. Get all metadata values for the POST request
    if($get_folderdocinfofiles->access_restriction == 'Yes' || $get_folderdocinfofiles->use_restriction == 'Yes') {
        $sensitivity = "true";
    }
    else {
        $sensitivity = "false";
    }

    if($get_folderdocinfofiles->freeze_approval == 1) {
        $lithold = "true";
    }
    else {
        $lithold = "false";
    }

    $get_org_val = $wpdb->get_row("SELECT organization_acronym FROM wpqa_wpsc_epa_program_office WHERE office_code = '".$get_folderdocinfofiles->program_office_id."'");
    $get_aaship = $wpdb->get_row("SELECT office_code FROM wpqa_wpsc_epa_program_office WHERE parent_office_code LIKE '0%' AND organization_acronym = '".$get_org_val->organization_acronym."'");

    // 4. Only apply disposition if a disposition date exists
    if(!empty($disposition_date)) {
        $nuxeo_request = '{"input": "' . $get_folderdocinfofiles->object_key . '","params": {"disposition": "' . $disposition_date . '","retention": "true","legalhold": "' . $lithold . '","sensitive": "' . $sensitivity . '","aaship": "' . $get_aaship->office_code . '","move": "true","custodian": "' . strtoupper($get_folderdocinfofiles->lan_id) . '"}}';
    }
    else {
        $nuxeo_request = '{"input": "' . $get_folderdocinfofiles->object_key . '","params": {"retention": "true","legalhold": "' . $lithold . '","sensitive": "' . $sensitivity . '","aaship": "' . $get_aaship->office_code . '","move": "true","custodian": "' . strtoupper($get_folderdocinfofiles->lan_id) . '"}}';
    }
    
    // 5. If a parent has no children then declare as a record
    if(Patt_Custom_Func::parent_child_indicator($folderfile_id, 'folderfile') == 0 && Patt_Custom_Func::get_count_of_children_for_parent($folderfile_id, 'folderfile') == 0) {

        $curl_declare_record = curl_init();
        curl_setopt_array($curl_declare_record, array(
        CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/site/automation/ARMSDeclareRecord',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$nuxeo_request,
        CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json+nxrequest',
        'Accept: application/json',
        'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
        ),
        ));

        $response_declare_record = curl_exec($curl_declare_record);
        $http_code_declare_record = curl_getinfo($curl_declare_record, CURLINFO_HTTP_CODE);
        curl_close($curl_declare_record);

        if(intval($http_code_declare_record) != 200) {
            $declare_record_error = Patt_Custom_Func::convert_http_error_code($http_code_declare_record);
            Patt_Custom_Func::insert_api_error('validation-declare-record-endpoint',$http_code_declare_record,$declare_record_error);
            array_push($error_array, 0);
        }
        else {
            $date_time = date('Y-m-d H:i:s');
            $data_update_logs = array('published_stage' => 1, 'published_stage_timestamp' => $date_time);
            $data_where_logs = array('folderdocinfofile_id' => $folderfile_id);
            $wpdb->update($logs_table, $data_update_logs, $data_where_logs);
            array_push($error_array, 1);
            // TODO Add lambda function call here
        }
    }

    // 6. If a parent has children then move the document from Unpublished to the Organization folder
    if(Patt_Custom_Func::parent_child_indicator($folderfile_id, 'folderfile') == 0 && Patt_Custom_Func::get_count_of_children_for_parent($folderfile_id, 'folderfile') > 0) {
        $curl_move_parent = curl_init();
        curl_setopt_array($curl_move_parent, array(
        CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/api/v1/automation/Document.Move',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "input": "doc:' . $get_folderdocinfofiles->object_key . '",
            "params": {
                "target": "/EPA Organization/' . $get_org_val->organization_acronym . '"
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic QWRtaW5pc3RyYXRvcjpBZG1pbmlzdHJhdG9y'
        ),
        ));

        $response_move_parent = curl_exec($curl_move_parent);
        $http_code_move_parent = curl_getinfo($curl_move_parent, CURLINFO_HTTP_CODE);
        curl_close($curl_move_parent);

        if(intval($http_code_move_parent) != 200) {
        $move_record_error = Patt_Custom_Func::convert_http_error_code($http_code_move_parent);
        Patt_Custom_Func::insert_api_error('validation-move-record',$http_code_move_parent,$move_record_error);
        array_push($error_array,0);
        }
    }
    
    // 7. If all children of a parent have been validated then declare as a record
    if(Patt_Custom_Func::parent_child_indicator($folderfile_id, 'folderfile') == 1) {
        $get_total_count = $wpdb->get_row("SELECT count(id) as total_count
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE parent_id = '" . $get_folderdocinfofiles->parent_id . "' AND folderdocinfofile_id <> '" . $folderfile_id . "' ");

        $get_validation_count = $wpdb->get_row("SELECT count(id) as validation_count
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE parent_id = '" . $get_folderdocinfofiles->parent_id . "' AND folderdocinfofile_id <> '" . $folderfile_id . "' AND validation = 1");
        // 7a. All children have been validated, declare as a record
        if($get_total_count->total_count == $get_validation_count->validation_count) {

        $get_child_ids = $wpdb->get_results("SELECT folderdocinfofile_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files 
        WHERE parent_id = '". $get_folderdocinfofiles->parent_id ."'");

        foreach($get_child_ids as $item) {
            $disposition_date = '';
            
            $get_all_documents = $wpdb->get_row("SELECT a.object_key, a.close_date, c.Schedule_Item_Number as record_schedule, d.freeze_approval, a.access_restriction, a.use_restriction, a.lan_id, b.program_office_id
            FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
            INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id 
            INNER JOIN " . $wpdb->prefix . "epa_record_schedule c ON c.id = b.record_schedule_id
            INNER JOIN " . $wpdb->prefix . "wpsc_ticket d ON d.id = b.ticket_id
            WHERE a.folderdocinfofile_id = '" . $item->folderdocinfofile_id . "'");

            // 2. Call disposition endpoint to get disposition date
            if(!empty($get_all_documents->close_date) && $get_all_documents->close_date != '0000-00-00') {
            $curl_disposition_date = curl_init();
            curl_setopt_array($curl_disposition_date,
            array(
                CURLOPT_URL => 'https://arms-uploader-api-pl.cis-dev.aws.epa.gov/disposition_calc?record_schedule='.$get_all_documents->record_schedule.'&close_date='.$get_all_documents->close_date,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET'));

                $response_disposition_date = curl_exec($curl_disposition_date);
                $http_code_disposition_date = curl_getinfo($curl_disposition_date, CURLINFO_HTTP_CODE);
                curl_close($curl_disposition_date);

                $obj = json_decode($response_disposition_date);

                if(intval($http_code_disposition_date) != 200) {
                    $disposition_date_error = Patt_Custom_Func::convert_http_error_code($http_code_disposition_date);
                    Patt_Custom_Func::insert_api_error('validation-disposition-date-endpoint',$http_code_disposition_date,$disposition_date_error);
                    array_push($error_array,0);
                }
                else {
                    $disposition_date = new DateTime($obj->{'disposition_date'}, new DateTimeZone('EST'));
                    $disposition_date->setTimezone(new DateTimeZone('UTC'));
                    $disposition_date = $disposition_date->format('Y-m-d\TH:i:s.v\Z');
                    array_push($error_array,1);
                }
            }

            if($get_all_documents->access_restriction == 'Yes' || $get_all_documents->use_restriction == 'Yes') {
                $sensitivity = "true";
            }
            else {
                $sensitivity = "false";
            }

            if($get_all_documents->freeze_approval == 1) {
                $lithold = "true";
            }
            else {
                $lithold = "false";
            }

            $get_org_val = $wpdb->get_row("SELECT organization_acronym FROM wpqa_wpsc_epa_program_office WHERE office_code = '".$get_all_documents->program_office_id."'");
            $get_aaship = $wpdb->get_row("SELECT office_code FROM wpqa_wpsc_epa_program_office WHERE parent_office_code LIKE '0%' AND organization_acronym = '".$get_org_val->organization_acronym."'");

            // 3. Only apply disposition if a disposition date exists
            if(!empty($disposition_date)) {
            $nuxeo_request = '{"input": "' . $get_all_documents->object_key . '","params": {"disposition": "' . $disposition_date . '","retention": "true","legalhold": "' . $lithold . '","sensitive": "' . $sensitivity . '","aaship": "' . $get_aaship->office_code . '","move": "true","custodian": "' . strtoupper($get_all_documents->lan_id) . '"}}';
            }
            else {
            $nuxeo_request = '{"input": "' . $get_all_documents->object_key . '","params": {"retention": "true","legalhold": "' . $lithold . '","sensitive": "' . $sensitivity . '","aaship": "' . $get_aaship->office_code . '","move": "true","custodian": "' . strtoupper($get_all_documents->lan_id) . '"}}';
            }

            $curl_declare_record = curl_init();
            curl_setopt_array($curl_declare_record, array(
            CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/site/automation/ARMSDeclareRecord',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>$nuxeo_request,
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json+nxrequest',
            'Accept: application/json',
            'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
            ),
            ));

            $response_declare_record = curl_exec($curl_declare_record);
            $http_code_declare_record = curl_getinfo($curl_declare_record, CURLINFO_HTTP_CODE);
            curl_close($curl_declare_record);

            if(intval($http_code_declare_record) != 200) {
                $declare_record_error = Patt_Custom_Func::convert_http_error_code($http_code_declare_record);
                Patt_Custom_Func::insert_api_error('validation-declare-record-endpoint',$http_code_declare_record,$declare_record_error);
                array_push($error_array,0);

            }
            else {
                $date_time = date('Y-m-d H:i:s');
                $data_update_logs = array('published_stage' => 1, 'published_stage_timestamp' => $date_time);
                $data_where_logs = array('folderdocinfofile_id' => $item->folderdocinfofile_id);
                $wpdb->update($logs_table, $data_update_logs, $data_where_logs);
                array_push($error_array,1);
                // TODO Add lambda function call here
            }
        }
        }
        //7b. Not all children have been validated, move from Unpublished to an organization folder
        else {
        $curl_move_child = curl_init();

        curl_setopt_array($curl_move_child, array(
            CURLOPT_URL => 'https://arms-dev-nuxeo.aws.epa.gov/nuxeo/api/v1/automation/Document.Move',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
            "input": "doc:' . $get_folderdocinfofiles->object_key . '",
            "params": {
                "target": "/EPA Organization/' . $get_org_val->organization_acronym . '"
            }
        }',
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic QWRtaW5pc3RyYXRvcjpBZG1pbmlzdHJhdG9y'
            ),
        ));

        $response_move_child = curl_exec($curl_move_child);
        $http_code_move_child = curl_getinfo($curl_move_child, CURLINFO_HTTP_CODE);
        curl_close($curl_move_child);

        if(intval($http_code_move_child) != 200) {
            $move_record_error = Patt_Custom_Func::convert_http_error_code($http_code_move_child);
            Patt_Custom_Func::insert_api_error('validation-move-record',$http_code_move_child,$move_record_error);
            array_push($error_array,0);
        }

        }
    }

    } else {
        Patt_Custom_Func::insert_api_error('validation-missing-object-key',500,'Missing Nuxeo Document ID.');
        array_push($error_array,0);
    }

    // 8. If any values in $error_array = 0 then folder/file will not be validated
    if(in_array(0,$error_array)){
        return 0;
    }
    else {
        return 1;
    }
}

public static function company_name_conversion($company_name)
{
    global $wpdb;

$get_office_code = $wpdb->get_row("
SELECT office_code
FROM " . $wpdb->prefix . "wpsc_epa_program_office
WHERE
office_acronym = '" . $company_name . "' AND
parent_office_code = 0
");

$office_code = $get_office_code->office_code;

return $office_code;
    
}

public static function check_initial_review_complete($ticket_id)
{
    global $wpdb;

$get_initial_review_complete = $wpdb->get_row("
SELECT COUNT(ID) as id_count
FROM " . $wpdb->prefix . "wpsc_epa_timestamps_request
WHERE
type = 'Initial Review Complete' AND
request_id = '" . $ticket_id . "'
");

  if ($get_initial_review_complete->id_count > 0)
  {
return true;
  } else {
return false;    
  }
    
}
      
public static function json_response($code = 200, $message = null)
{
    // clear the old headers
    header_remove();
    // set the actual code
    http_response_code($code);
    // set the header to make sure cache is forced
    header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
    // treat this as json
    header('Content-Type: application/json');
    $status = array(
        200 => '200 OK',
        400 => '400 Bad Request',
        422 => 'Unprocessable Entity',
        500 => '500 Internal Server Error'
        );
    // ok, validation error, or failure
    header('Status: '.$status[$code]);
    // return the encoded json
    return json_encode(array(
        'status' => $code, // success or not?
        'message' => $message
        ));
}

/**
* Convert display name to user ID
* @return user ID
*/

public static function get_user_id_by_display_name( $display_name ) {
    global $wpdb;

    if ( ! $user = $wpdb->get_row( $wpdb->prepare(
        "SELECT `ID` FROM $wpdb->users WHERE `display_name` = %s", $display_name
    ) ) )
        return false;

    return $user->ID;
}

/**
* Get AA'ship requestor group based on current logged in user
* @return array of user id's in the group, return false if user does not belong to a group
*/
		public static function get_requestor_group( $user_id ) {
	        global $wpdb;
	        
$final_user_arr = array();

if (!is_int($user_id)) {
$user_id = Patt_Custom_Func::get_user_id_by_display_name($user_id);
}

$get_requestor_group = $wpdb->get_row("
SELECT count(umeta_id) as count, meta_value
FROM " . $wpdb->prefix . "usermeta
WHERE
user_id = '" . $user_id . "' AND
meta_key = 'user_requestor_group'
");

$requestor_group_count = $get_requestor_group->count;

if ($requestor_group_count == 0) {

$error = array();

return $error;

} else {
$get_user_arr = $wpdb->get_results("
SELECT user_id FROM " . $wpdb->prefix . "usermeta 
WHERE 
meta_value = '" . $get_requestor_group->meta_value . "' 
AND meta_key = 'user_requestor_group'
");

foreach($get_user_arr as $user_id) {
array_push($final_user_arr, $user_id->user_id);
}

return $final_user_arr;

}
		}
		
/**
 * Update Counts: Update Remaining and Occupied Counts for East and West
 * @return true
 */


        public static function update_remaining_occupied($dc_final,$shelf_id_arr)		
{
global $wpdb;
include WPPATT_ABSPATH . 'includes/term-ids.php';

if($dc_final == $dc_east_tag->term_id) {
// Find first available slot for requests with boxes equal to 1

foreach($shelf_id_arr as $item) {

$get_shelf_dbid = $wpdb->get_row("
SELECT id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
shelf_id = '" . $item . "' AND
digitization_center = '" . $dc_final . "'
");

$get_shelf_dbid_val = $get_shelf_dbid->id;

$shelf_id = $item;

[$aisle, $bay, $shelf] = explode("_", $shelf_id);

//echo $aisle.'-'.$bay.'-'.$shelf.' - ';

$position_details = $wpdb->get_results("
SELECT position FROM " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE aisle = '" . $aisle . "' 
AND bay = '" . $bay . "' 
AND shelf = '" . $shelf . "' 
AND digitization_center = ".$dc_east_tag->term_id."
");

$position_count = count($position_details);

//echo $position_count.'<br />';

// Set Remaining to 0
// Updated 3 boxes to a shelf
if($position_count == 3) {

$data_update = array('remaining' => 0, 'occupied' => 1);
$data_where = array('id' => $get_shelf_dbid_val);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

// Set Remaining to the position count
// Updated 3 boxes to a shelf
if($position_count > 0 && $position_count <= 2) {


$data_update = array('remaining' => 3-$position_count, 'occupied' => 1);
$data_where = array('id' => $get_shelf_dbid_val);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

// Updated 3 boxes to a shelf
if($position_count == 0) {
    $data_update_occupied = array('occupied' => 0, 'remaining' => 3);
    $data_where_occupied = array('id' => $get_shelf_dbid_val);
    $wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update_occupied, $data_where_occupied);
}

}

return true;

} elseif($dc_final == $dc_west_tag->term_id) {
// Find first available slot for requests with boxes equal to 1

foreach($shelf_id_arr as $item) {

$get_shelf_dbid = $wpdb->get_row("
SELECT id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
shelf_id = '" . $item . "' AND
digitization_center = '" . $dc_final . "'
");

$get_shelf_dbid_val = $get_shelf_dbid->id;

$shelf_id = $item;

[$aisle, $bay, $shelf] = explode("_", $shelf_id);

//echo $aisle.'-'.$bay.'-'.$shelf.' - ';

$position_details = $wpdb->get_results("
SELECT position FROM " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE aisle = '" . $aisle . "' 
AND bay = '" . $bay . "' 
AND shelf = '" . $shelf . "' 
AND digitization_center = ".$dc_west_tag->term_id."
");

$position_count = count($position_details);

//echo $position_count.'<br />';

// Set Remaining to 0
// Updated 3 boxes to a shelf

if($position_count == 3) {

$data_update = array('remaining' => 0, 'occupied' => 1);
$data_where = array('id' => $get_shelf_dbid);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

// Set Remaining to the position count
// Updated 3 boxes to a shelf
if($position_count > 0 && $position_count <= 2) {

$data_update = array('remaining' => 3-$position_count, 'occupied' => 1);
$data_where = array('id' => $get_shelf_dbid);
$wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update, $data_where);

}

// Updated 3 boxes to a shelf
if($position_count == 0) {
    $data_update_occupied = array('occupied' => 0, 'remaining' => 3);
    $data_where_occupied = array('id' => $get_shelf_dbid);
    $wpdb->update($wpdb->prefix.'wpsc_epa_storage_status', $data_update_occupied, $data_where_occupied);
}


}

return true;
}
}

/**
 * Auto assignment: Assign boxes to single shelf
 * @return true
 */


public static function shelf_assignment($is_single,$box_shelf_id,$dc_final,$box_storage_id_assignment,$box_details_count)		
{

global $wpdb; 

//echo $is_single;
//echo print_r($box_shelf_id);
//echo $dc_final;
//print_r($box_storage_id_assignment);
//echo $box_details_count;
if($is_single == 1) {

//Explode Aisle, Bay, Shelf into indvidual variables
[$box_aisle, $box_bay, $box_shelf] = explode("_", $box_shelf_id);

//echo $box_shelf_id; 

// Get first available position
$box_position_details = $wpdb->get_results("
SELECT position FROM " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE aisle = '" . $box_aisle . "' 
AND bay = '" . $box_bay . "' 
AND shelf = '" . $box_shelf . "' 
AND digitization_center = '" . $dc_final . "'
");

$box_position_gap_array = array();
$box_aisle_bay_shelf_position = array();

foreach ($box_position_details as $info) {
	$box_position = $info->position;
	array_push($box_position_gap_array, $box_position);
}
//echo 'Box position gap array : ';
//print_r($box_position_gap_array) . '<br/>';

// Determine missing positions and push to an array     
// Updated 3 boxes to a shelf
$box_missing = array_diff(range(1, 3), $box_position_gap_array);

// Only use portion of array that equals the number of boxes that are unassigned
$box_missing_final = array_slice($box_missing, 0, $box_details_count);

foreach ($box_missing_final as &$box_missing_val) {
		$box_position_id_val = $box_shelf_id . '_' . $box_missing_val;
		array_push($box_aisle_bay_shelf_position, $box_position_id_val);
}

//print_r($box_aisle_bay_shelf_position);
// Determine aisle, bay, shelf, position

for ($i = 0; $i < count($box_aisle_bay_shelf_position); $i++) {
    
[$box_final_aisle, $box_final_bay, $box_final_shelf, $box_final_position] = explode("_", $box_aisle_bay_shelf_position[$i]);
//echo $box_aisle_bay_shelf_position[$i];
// Make auto-assignment in database
$storage_location_table_name = $wpdb->prefix . 'wpsc_epa_storage_location';
$sl_data_update = array('aisle' => $box_final_aisle, 'bay' => $box_final_bay, 'shelf' => $box_final_shelf, 'position' => $box_final_position, 'digitization_center' => $dc_final);
//echo $box_storage_id_assignment[$i];
$sl_data_where = array('id' => $box_storage_id_assignment[$i]);
$wpdb->update($storage_location_table_name, $sl_data_update, $sl_data_where);

$box_shelf_id_update = $box_final_aisle . '_' . $box_final_bay . '_' . $box_final_shelf;

// Update storage status table
//echo 'true';

}

Patt_Custom_Func::update_remaining_occupied($dc_final,array($box_shelf_id));


} else {
//Assignment requirng finding the gap
//Begin Gap Assignment
/*
echo $is_single.'<br />';
print_r($box_shelf_id);
echo '<br />';
echo $dc_final.'<br />';
print_r($box_storage_id_assignment);
echo '<br />';
echo $box_details_count;
echo '<br />';
*/

$missing_gap_array = array();

foreach ($box_shelf_id as &$value) {
[$gap_aisle, $gap_bay, $gap_shelf] = explode("_", $value);

$current_row_details = $wpdb->get_row("
SELECT remaining
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
shelf_id = '" . $value . "' AND
digitization_center = '" . $dc_final . "'
");

$get_current_row_details_value = $current_row_details->remaining;

// Updated 3 boxes to a shelf
if($get_current_row_details_value != 3) {
// Get all positions in an array to determine available positions

$position_gap_details = $wpdb->get_results("
SELECT position FROM " . $wpdb->prefix . "wpsc_epa_storage_location 
WHERE aisle = '" . $gap_aisle . "' 
AND bay = '" . $gap_bay . "' 
AND shelf = '" . $gap_shelf . "' 
AND digitization_center = '" . $dc_final . "'
");

$position_gap_array = array();

foreach ($position_gap_details as $item) {
    
$array_gap_val_final = $item->position;

array_push($position_gap_array, $array_gap_val_final);

}


} else {

$position_gap_array = array();
$array_gap_val_final = '';
array_push($position_gap_array, $array_gap_val_final);
}

// Determine missing positions and push to an array.  
// Updated 3 boxes to a shelf
$missing = array_diff(range(1, 3), $position_gap_array);
				
				foreach ($missing as &$missing_val) {
					$shelf_position_id_val = $value . '_' . $missing_val;
					array_push($missing_gap_array, $shelf_position_id_val);
				}
			}
			
// Only use portion of array that equals the number of boxes that are unassigned
$gap_aisle_bay_shelf_position = array_slice($missing_gap_array, 0, $box_details_count);

			foreach ($gap_aisle_bay_shelf_position as $key => $value) {
				[$gap_aisle, $gap_bay, $gap_shelf, $gap_position] = explode("_", $value);
// Make auto-assignment in database
				$gapsl_table_name = $wpdb->prefix . 'wpsc_epa_storage_location';
				$gapsl_data_update = array(
					'aisle' => $gap_aisle, 'bay' => $gap_bay, 'shelf' => $gap_shelf, 'position' => $gap_position, 'digitization_center' => $dc_final
				);
				$gapsl_data_where = array('id' => $box_storage_id_assignment[$key]);
				$wpdb->update($gapsl_table_name, $gapsl_data_update, $gapsl_data_where);
								
				$gap_shelf_id_update = $gap_aisle . '_' . $gap_bay . '_' . $gap_shelf;
// Update storage status table
                 Patt_Custom_Func::update_remaining_occupied($dc_final,array($gap_shelf_id_update));
			}
               

}

}


/**
 * Auto assignment: Get available shelfs in gap
 * @return array of shelf_ids sorted correctly
 */


        public static function auto_location_find_gap($dc_final,$begin_seq,$sequence_shelfid,$box_details_count)		
{
        global $wpdb;

include WPPATT_ABSPATH . 'includes/term-ids.php';       

if($dc_final == $dc_east_tag->term_id) {
    
$find_gaps = $wpdb->get_row("
WITH 
cte1 AS
(
SELECT id, shelf_id, remaining, SUM(remaining = 0) OVER (ORDER BY id) group_num
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE digitization_center = " . $dc_final . " AND
id BETWEEN ".$begin_seq." AND " . $sequence_shelfid . "
)
SELECT GROUP_CONCAT(id) as id,
       GROUP_CONCAT(shelf_id) as shelf_id,
       GROUP_CONCAT(remaining) as remaining,
       SUM(remaining) as total
FROM cte1
WHERE remaining != 0
GROUP BY group_num
HAVING total >= '".$box_details_count."'
LIMIT 1
");

$findgaps_id = $find_gaps->id;
$findgaps_total = $find_gaps->total;

$findgaps_array = explode(",", $findgaps_id);

asort($findgaps_array);

$shelfid_gaps_array = array();

foreach($findgaps_array as $item) {
$shelf_id_value = $wpdb->get_row("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
id = '" . $item . "'
");

$get_shelf_id_value = $shelf_id_value->shelf_id;

array_push($shelfid_gaps_array,$get_shelf_id_value);
				
}

if($findgaps_total >= $box_details_count){
return $shelfid_gaps_array;
} else {

$begin_seq_west = Patt_Custom_Func::begin_sequence($dc_west_tag->term_id);

$find_gaps_end = $wpdb->get_row("
WITH 
cte1 AS
(
SELECT id, shelf_id, remaining, SUM(remaining = 0) OVER (ORDER BY id) group_num
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE digitization_center = " . $dc_final . " AND
id BETWEEN ".$begin_seq." AND " . $begin_seq_west . "
)
SELECT GROUP_CONCAT(id) as id,
       GROUP_CONCAT(shelf_id) as shelf_id,
       GROUP_CONCAT(remaining) as remaining,
       SUM(remaining) as total
FROM cte1
WHERE remaining != 0
GROUP BY group_num
HAVING total >= '".$box_details_count."'
LIMIT 1
");

$findgaps_end_id = $find_gaps_end->id;
$findgaps_end_total = $find_gaps_end->total;

$findgaps_end_array = explode(",", $findgaps_end_id);

asort($findgaps_end_array);

$shelfid_gaps_end_array = array();

foreach($findgaps_end_array as $item) {
$shelf_id_end_value = $wpdb->get_row("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
id = '" . $item . "'
");

$get_shelf_id_end_value = $shelf_id_end_value->shelf_id;

array_push($shelfid_gaps_end_array,$get_shelf_id_end_value);

}

if($findgaps_end_total >= $box_details_count){
return $shelfid_gaps_end_array;
} else {
echo 'no more space in digitzation center east';
}

}

}

if($dc_final == $dc_west_tag->term_id) {

$find_gaps = $wpdb->get_row("
WITH 
cte1 AS
(
SELECT id, shelf_id, remaining, SUM(remaining = 0) OVER (ORDER BY id) group_num
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE digitization_center = " . $dc_final . " AND
id BETWEEN ".$begin_seq." AND " . $sequence_shelfid . "
)
SELECT GROUP_CONCAT(id) as id,
       GROUP_CONCAT(shelf_id) as shelf_id,
       GROUP_CONCAT(remaining) as remaining,
       SUM(remaining) as total
FROM cte1
WHERE remaining != 0
GROUP BY group_num
HAVING total >= '".$box_details_count."'
LIMIT 1
");

$findgaps_id = $find_gaps->id;
$findgaps_total = $find_gaps->total;

$findgaps_array = explode(",", $findgaps_id);

asort($findgaps_array);

$shelfid_gaps_array = array();

foreach($findgaps_array as $item) {
$shelf_id_value = $wpdb->get_row("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
id = '" . $item . "'
");

$get_shelf_id_value = $shelf_id_value->shelf_id;

array_push($shelfid_gaps_array,$get_shelf_id_value);
				
}

if($findgaps_total >= $box_details_count){
return $shelfid_gaps_array;
} else {

$get_final_storage_id_range = $wpdb->get_row("SELECT max(id) as maximum FROM " . $wpdb->prefix . "wpsc_epa_storage_status WHERE id <> '-99999' AND digitization_center = '".$dc_west_tag->term_id."' ");

$get_storage_id_range_max = $get_final_storage_id_range->maximum;


$begin_seq_west = Patt_Custom_Func::begin_sequence($dc_west_tag->term_id);

$find_gaps_end = $wpdb->get_row("
WITH 
cte1 AS
(
SELECT id, shelf_id, remaining, SUM(remaining = 0) OVER (ORDER BY id) group_num
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE digitization_center = " . $dc_final . " AND
id BETWEEN ".$begin_seq_west." AND " . $get_storage_id_range_max . "
)
SELECT GROUP_CONCAT(id) as id,
       GROUP_CONCAT(shelf_id) as shelf_id,
       GROUP_CONCAT(remaining) as remaining,
       SUM(remaining) as total
FROM cte1
WHERE remaining != 0
GROUP BY group_num
HAVING total >= '".$box_details_count."'
LIMIT 1
");

$findgaps_end_id = $find_gaps_end->id;
$findgaps_end_total = $find_gaps_end->total;

$findgaps_end_array = explode(",", $findgaps_end_id);

asort($findgaps_end_array);

$shelfid_gaps_end_array = array();

foreach($findgaps_end_array as $item) {
$shelf_id_end_value = $wpdb->get_row("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE
id = '" . $item . "'
");

$get_shelf_id_end_value = $shelf_id_end_value->shelf_id;

array_push($shelfid_gaps_end_array,$get_shelf_id_end_value);

}

if($findgaps_end_total >= $box_details_count){
return $shelfid_gaps_end_array;
} else {
echo 'no more space in digitzation center west';
}
}

}

}

/**
 * Auto assign shelf information given dc and ticket id
 * @return aisle,bay,shelf,position assignment
 */


        public static function auto_location_assignment($tkid,$dc_final,$destruction_flag,$destruction_boxes)		
{
        global $wpdb;
$box_id_assignment = array();

if($destruction_flag == 0) {
//DETERMINE NUMBER OF BOXES IN REQUEST

$box_id_assignment = Patt_Custom_Func::get_unassigned_boxes($tkid,$dc_final);
}

if($destruction_flag == 1) {
$box_id_assignment = explode(',', $destruction_boxes);
}

//print_r('Box IDs : ' . $box_id_assignment) . '<br/>';
$box_details_count = count($box_id_assignment);
//echo 'Box Count : ' . $box_details_count . '<br/>';
//PRINT BOX ID ASSIGNMENT
//print_r($box_id_assignment);

// Defines begining of sequence for digitzation center
$begin_seq = Patt_Custom_Func::begin_sequence($dc_final);
//echo 'Begin Sequence ' . $begin_seq . '<br/>';
// Finds Shelf ID of next available sequence for gap
$find_sequence = $wpdb->get_row("
WITH 
cte1 AS
(
SELECT id, 
       CASE WHEN     occupied  = LAG(occupied) OVER (ORDER BY id)
                 AND remaining = LAG(remaining) OVER (ORDER BY id)
            THEN 0
            ELSE 1 
            END values_differs
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE digitization_center = '" . $dc_final . "'
),
cte2 AS 
(
SELECT id,
       SUM(values_differs) OVER (ORDER BY id) group_num
FROM cte1
ORDER BY id
)
SELECT MIN(id) as id
FROM cte2
GROUP BY group_num
ORDER BY COUNT(*) DESC LIMIT 1;
");

$sequence_shelfid = $find_sequence->id;

$empty_space_shelf_arr_id = Patt_Custom_Func::auto_location_find_gap($dc_final,$begin_seq,$sequence_shelfid,$box_details_count);

if ($box_details_count == 1) {
//////////////////FOR 1 BOX
//IF Request has 1 box, look for 1 box gap
//IF 1 box gap does not exist, look for 2 box gap
//IF 2 box gap does not exist, look for 3 box gap
//IF 3 box gap does not exist, look for 4 box gap
// Updated 3 boxes to a shelf
$one_box_assignment = $wpdb->get_row("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE 
(occupied = 1 AND remaining = 1) OR (occupied = 1 AND remaining = 2) OR (occupied = 0 AND remaining = 3) AND
digitization_center = '" . $dc_final . "'
ORDER BY id asc
LIMIT 1
");

$one_box_assignment_shelf_id = $one_box_assignment->shelf_id;

//IF NOTE EMPTY THEN ASSIGN ELSE PROCEED TO SEQUENCE
if(!empty($one_box_assignment_shelf_id)) {

//EXPLODE SHELF ID
//MAKE ASSIGNMENT

$is_single = 1;
Patt_Custom_Func::shelf_assignment($is_single,$one_box_assignment_shelf_id,$dc_final,$box_id_assignment,$box_details_count);
return true;

} else {

//EXPLODE SHELF ID
//MAKE ASSIGNMENT
$is_single = 0;
Patt_Custom_Func::shelf_assignment($is_single,$empty_space_shelf_arr_id,$dc_final,$box_id_assignment,$box_details_count);
return true;

}

//IF 3 box gap does not exist, PROCEED TO SEQUENCE

} elseif($box_details_count == 2) {
//////////////////FOR 2 BOXES
//IF Request has 2 boxes, look for 2 box gap
//IF 2 box gap does not exist, look for 3 box gap
//IF 3 box gap does not exist, look for 4 box gap
//IF 4 box gap does not exist, PROCEED TO SEQUENCE
// Updated 3 boxes to a shelf

$two_box_assignment = $wpdb->get_row("
SELECT shelf_id
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE (occupied = 1 AND remaining = 2) OR (occupied = 0 AND remaining = 3) AND
digitization_center = '" . $dc_final . "'
ORDER BY id asc
LIMIT 1
");

$two_box_shelf_id = $two_box_assignment->shelf_id;

//IF NOTE EMPTY THEN ASSIGN ELSE PROCEED TO SEQUENCE
if(!empty($two_box_shelf_id)) {
    
//MAKE ASSIGNMENT
$is_single = 1;

//foreach($box_id_assignment as $item) {
Patt_Custom_Func::shelf_assignment($is_single,$two_box_shelf_id,$dc_final,$box_id_assignment,$box_details_count);
return true;
//}

} else {

//EXPLODE SHELF ID
//MAKE ASSIGNMENT
$is_single = 0;
Patt_Custom_Func::shelf_assignment($is_single,$empty_space_shelf_arr_id,$dc_final,$box_id_assignment,$box_details_count);
return true;
}



} elseif($box_details_count >= 3) {
//////////////////FOR 3 OR MORE BOXES

//IF Request has 3 or more boxes, PROCEED TO SEQUENCE
// Updated 3 boxes to a shelf

//EXPLODE SHELF ID
//MAKE ASSIGNMENT
$is_single = 0;
Patt_Custom_Func::shelf_assignment($is_single,$empty_space_shelf_arr_id,$dc_final,$box_id_assignment,$box_details_count);
return true;
} else {
echo "Nothing to assign.";
}
    
}




        /**
         * Truncate to the nearest word
         * @return truncated string with ellipsis
         */
         
    public static function wholeWordTruncate($string, $your_desired_width,$delimiter = '...') {
  $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
  $parts_count = count($parts);

  $length = 0;
  $last_part = 0;
  for (; $last_part < $parts_count; ++$last_part) {
    $length += strlen($parts[$last_part]);
    if ($length > $your_desired_width) { break; }
  }

    if ($length > $your_desired_width) { 
      return implode(array_slice($parts, 0, $last_part)).$delimiter;
    } else {
      return $string;
    }
}

        /**
         * Determine begin sequence for east/west storage location
         * @return db id
         */
         
        public static function begin_sequence($dc)		
{
        global $wpdb;
		
		$get_begin_sequence = $wpdb->get_row("SELECT id FROM ".$wpdb->prefix."wpsc_epa_storage_status 
        WHERE digitization_center = '" . $dc . "' LIMIT 1");

		$begin_sequence = $get_begin_sequence->id;
		
		return $begin_sequence;
		
    
}

        /**
         * Determine when to display box assigned users icon
         * @return URL
         */
         
        public static function display_box_user_icon($box_id)		
{
        global $wpdb;
		
		$get_box_user_count = $wpdb->get_row("SELECT count(id) as count FROM ".$wpdb->prefix."wpsc_epa_boxinfo_userstatus
        WHERE box_id = '" . $box_id . "'");

		$box_user_count = $get_box_user_count->count;
		
		if($box_user_count >= 1) {
		return true;
		} else {
		return false;
		}
		
    
}

        //Function to convert LAND ID to User Information JSON
        public static function lan_id_to_json( $lanid )
        {
            global $wpdb;

			$curl = curl_init();
			
			$url = EIDW_ENDPOINT.'userName%20eq%20'.$lanid;
			
			$lan_id_details = '';
			
			$eidw_authorization = 'Authorization: Basic '.EIDW;
			
			$headers = [
			    'Cache-Control: no-cache',
				$eidw_authorization
			];
			
			        curl_setopt($curl,CURLOPT_URL, $url);
			        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			        curl_setopt($curl,CURLOPT_MAXREDIRS, 10);
			        curl_setopt($curl,CURLOPT_TIMEOUT, 30);
			        curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
			        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, "GET");
			        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
					//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
					//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			
			$response = curl_exec($curl);
            $status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

            curl_close($curl);

            $err = Patt_Custom_Func::convert_http_error_code($status);

			if ($status != 200) {
            Patt_Custom_Func::insert_api_error('eidw-customfunc-lanidtojson',$status,$err);
			} else {
			
			$json = json_decode($response, true);
			
			$results = $json['totalResults'];
			$active = $json['Resources']['0']['active'];
			$full_name = $json['Resources']['0']['name']['givenName'].' '.$json['Resources']['0']['name']['familyName'];
			$email = $json['Resources']['0']['emails']['0']['value'];
			$phone = $json['Resources']['0']['phoneNumbers']['0']['value'];
			$org = $json['Resources']['0']['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User']['department'];
			$workforce_id = $json['Resources']['0']['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User']['employeeNumber'];
              
			if ($active == 1) {
			// Declare array  
			$lan_id_details_array = array(
			    "name"=>$full_name,
			    "email"=>$email,
			    "phone"=>$phone,
			    "org"=>$org,
			    "lan_id"=>$lanid,
              	"workforce_id"=>$workforce_id,
			); 
			   
			// Use json_encode() function 
			$lan_id_details = json_encode($lan_id_details_array); 
			   
			// Display the output 
			//echo($json); 	
			} else {
				$lan_id_details = 'Error';
			}
			
			}

            return $lan_id_details;
        }

        //Function to check LAND ID
        public static function lan_id_check($lan_id, $request_id)
        {
            global $wpdb;
			$lan_id_details = Patt_Custom_Func::lan_id_to_json($lan_id);
			
			$obj = json_decode($lan_id_details);
			
			$active_check = $obj->{'active'};
			
			if ($active_check == 1) {
				$active_check = $lan_id;
			} else {
				
				$find_requester = $wpdb->get_row("SELECT a.user_login as user_login FROM ".$wpdb->prefix."users a
	INNER JOIN ".$wpdb->prefix."wpsc_ticket b ON a.user_email = b.customer_email WHERE b.request_id = '" . $request_id . "'");
	
				$requester_lanid = $find_requester->user_login;
		
				$requester_id_details = Patt_Custom_Func::lan_id_to_json($requester_lanid);
				
				$obj = json_decode($requester_id_details);
				
				$requester_id_check = $obj->{'active'};
				
				if ($requester_id_check == 1) {
					$active_check = strtolower($requester_lanid);	
				} else {
					$active_check = 'LAN ID cannot be assigned';
				}
			}
			
            return $active_check;
        }
        
        /**
         * Check Shipping Tracking Number 
         * @return URL
         */
         
        public static function get_tracking_url($tracking_number, $company_name)		
{
	if (empty($tracking_number)) return false;
	if (!is_string($tracking_number)  &&  !is_int($tracking_number)) return false;

    switch ($company_name) {
        case $company_name == 'ups':
            return UPS_URL.$tracking_number;
            break;
        case $company_name == 'usps':
            return USPS_URL.$tracking_number;
            break;
        case $company_name == 'fedex':
            return FEDEX_URL.$tracking_number;
            break;
        case $company_name == 'dhl':
            return DHL_URL.$tracking_number;
            break;
        default:
            return false;
        

    }

    // OLD Shipping Tracking Generator By RegEx
	/*static $tracking_urls = [
		//UPS - UNITED PARCEL SERVICE
		[
			'url'=> UPS_URL,
			'reg'=>'/\b(1Z ?[0-9A-Z]{3} ?[0-9A-Z]{3} ?[0-9A-Z]{2} ?[0-9A-Z]{4} ?[0-9A-Z]{3} ?[0-9A-Z]|T\d{3} ?\d{4} ?\d{3})\b/i'
		],

		//USPS - UNITED STATES POSTAL SERVICE - FORMAT 1
		[
			'url'=> USPS_URL,
			'reg'=>'/\b((420 ?\d{5} ?)?(91|92|93|94|01|03|04|70|23|13)\d{2} ?\d{4} ?\d{4} ?\d{4} ?\d{4}( ?\d{2,6})?)\b/i'
		],

		//USPS - UNITED STATES POSTAL SERVICE - FORMAT 2
		[
			'url'=> USPS_URL,
			'reg'=>'/\b((M|P[A-Z]?|D[C-Z]|LK|E[A-C]|V[A-Z]|R[A-Z]|CP|CJ|LC|LJ) ?\d{3} ?\d{3} ?\d{3} ?[A-Z]?[A-Z]?)\b/i'
		],

		//USPS - UNITED STATES POSTAL SERVICE - FORMAT 3
		[
			'url'=> USPS_URL,
			'reg'=>'/\b(82 ?\d{3} ?\d{3} ?\d{2})\b/i'
		],
      
      	//USPS - UNITED STATES POSTAL SERVICE - FORMAT 4
		[
			'url'=> USPS_URL,
			'reg'=>'/((\d{4})(\s?\d{4}){4}\s?\d{2})|((\d{2})(\s?\d{3}){2}\s?\d{2})|((\D{2})(\s?\d{3}){3}\s?\D{2})((\d{4})(\s?\d{4}){4}\s?\d{2})/i'
		],

        //FEDEX - FEDERAL EXPRESS
            [
                    'url'=> FEDEX_URL,
                    'reg'=>'/\b(((96\d\d|6\d)\d{3} ?\d{4}|96\d{2}|\d{4}) ?\d{4} ?\d{4}( ?\d{3}| ?\d{15})?)\b/i'
            ],
	];

	//TEST EACH POSSIBLE COMBINATION
	foreach ($tracking_urls as $item) {
		$match = array();
		preg_match($item['reg'], $tracking_number, $match);
		if (count($match)) {
            //return count($match);
			return $item['url'] . preg_replace('/\s/', '', strtoupper($match[0]));
		} elseif (substr( strtoupper($tracking_number), 0, 4 ) === "DHL:") {
		$dhl_tracking_number = substr($tracking_number, 4);
        return DHL_URL.$dhl_tracking_number;
		}
	}*/


	// TRIM LEADING ZEROES AND TRY AGAIN
	if (substr($tracking_number, 0, 1) === '0') {
		return get_tracking_url(ltrim($tracking_number, '0'));
	}


	//NO MATCH FOUND, RETURN FALSE
	return false;
}


        /**
         * Determine Shipping Carrier from Tracking Number 
         * @return URL
         */
         
        public static function get_shipping_carrier($tracking_number)		
{
    
	if (empty($tracking_number)) return false;
	if (!is_string($tracking_number)  &&  !is_int($tracking_number)) return false;

    $shipping_url = Patt_Custom_Func::get_tracking_url($tracking_number);
    
    $shipping_carrier = '';
    
switch (true) {
    case strpos($shipping_url, 'ups') !== false:
        $shipping_carrier = 'ups';
    break;
    case strpos($shipping_url, 'usps') !== false:
        $shipping_carrier = 'usps';
    break;
    case strpos($shipping_url, 'fedex') !== false:
        $shipping_carrier = 'fedex';
    break;
    case strpos($shipping_url, 'dhl') !== false:
        $shipping_carrier = 'dhl';
    break; 

}

    return $shipping_carrier;
    
}

/**
 * Determine if external shipping carrier is being used on a given ticket
 */
public static function using_ext_shipping( $ticket_id ) {
  global $wpdb;
  $table = $wpdb->prefix . "wpsc_epa_shipping_tracking";
  $sql = "SELECT * FROM " . $table . " where ticket_id = " . $ticket_id;
  $shipping_details = $wpdb->get_results( $sql );
  
  $is_ext = false;
  
  foreach( $shipping_details as $row ) {
    $row->tracking_number = strtolower( $row->tracking_number );
    if( $row->tracking_number == WPPATT_EXT_SHIPPING_TERM || $row->tracking_number == WPPATT_EXT_SHIPPING_TERM_R3 ) {
      $is_ext = true;
    }
    
  }
  
  return $is_ext;
  

}


/**
 * Determine if pallet ID exists in scan_list, if not remove pallet_id from the boxinfo table
 */
public static function pallet_cleanup() {
global $wpdb;

// Cleanup Pallet Locations from Box Info Table
$get_box_ids_with_locations = $wpdb->get_results("SELECT box_id
FROM " . $wpdb->prefix . "wpsc_epa_scan_list
WHERE box_id IS NOT NULL");

//Empty pallet_id column in boxinfo
foreach ($get_box_ids_with_locations as $data) {
$box_id_with_location = $data->box_id;
$table_box = $wpdb->prefix . "wpsc_epa_boxinfo";
//$pallet_boxinfo_update = array('pallet_id' => '', 'location_status_id' => 1);
$pallet_boxinfo_update = array('pallet_id' => '');
$pallet_boxinfo_where = array('box_id' => $box_id_with_location);
$wpdb->update($table_box, $pallet_boxinfo_update, $pallet_boxinfo_where);
}

$scan_list_table = $wpdb->prefix . "wpsc_epa_scan_list";

//Delete from scan_list table if pallet ID not in boxinfo table
$scan_list_pallets = $wpdb->get_results("SELECT DISTINCT pallet_id FROM " . $wpdb->prefix . "wpsc_epa_scan_list WHERE pallet_id IS NOT NULL");
$boxinfo_pallets = $wpdb->get_results("SELECT DISTINCT pallet_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE pallet_id <> '' ");
$get_boxinfo_array = array();
$get_scan_list_array = array();

foreach($boxinfo_pallets as $info) {
    $boxinfo_pallet_id = $info->pallet_id;
    array_push($get_boxinfo_array, $boxinfo_pallet_id);
}

foreach($scan_list_pallets as $info) {
    $scan_list_pallet_id = $info->pallet_id;
    array_push($get_scan_list_array, $scan_list_pallet_id);
}
$result = array_diff($get_scan_list_array, $get_boxinfo_array);

foreach($result as $diff) {
    $wpdb->delete( $scan_list_table, array( 'pallet_id' => $diff) );
}

}

/**
 * Determine scanning_prep_location_area_id, scanning_location_area_id, validation_area_id, qaqc_location_area_id, scanning_id, cart_id, or stagingarea_id from physical location of a box/pallet
 */
public static function id_in_physical_location( $identifier, $type ) {
    global $wpdb;
    
    if($type == 'box' || $type == 'box_archive') {
        
        //If $identifier is a box ID
        $get_physical_location_id = $wpdb->get_row("SELECT DISTINCT a.scanning_id, a.stagingarea_id, a.cart_id, a.validation_location_area_id, a.qaqc_location_area_id, a.scanning_prep_location_area_id, a.scanning_location_area_id, 
        a.receiving_dock, a.oversized_tube_shelves, a.express_staging_area, a.express_pallet, a.destruction, a.shipping_dock_area, a.discrepancy, a.shelf_location, b.pallet_id
        FROM " . $wpdb->prefix . "wpsc_epa_scan_list a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.scan_list_id = a.id
        WHERE (a.scanning_id IS NOT NULL OR a.stagingarea_id IS NOT NULL OR a.cart_id IS NOT NULL OR a.validation_location_area_id IS NOT NULL OR a.qaqc_location_area_id IS NOT NULL OR a.scanning_prep_location_area_id IS NOT NULL OR a.scanning_location_area_id IS NOT NULL 
        OR a.receiving_dock IS NOT NULL OR a.oversized_tube_shelves IS NOT NULL OR a.express_staging_area IS NOT NULL OR a.express_pallet IS NOT NULL OR a.destruction IS NOT NULL OR a.shipping_dock_area IS NOT NULL OR a.discrepancy IS NOT NULL OR a.shelf_location IS NOT NULL)
        AND b.box_id = '" .  $identifier . "'");

        $scanning_id = $get_physical_location_id->scanning_id;
        $stagingarea_id = $get_physical_location_id->stagingarea_id;
        $cart_id = $get_physical_location_id->cart_id;
        $validation_location_area_id = $get_physical_location_id->validation_location_area_id;
        $qaqc_location_area_id = $get_physical_location_id->qaqc_location_area_id;
        $scanning_prep_location_area_id = $get_physical_location_id->scanning_prep_location_area_id;
        $scanning_location_area_id = $get_physical_location_id->scanning_location_area_id;
        $receiving_dock = $get_physical_location_id->receiving_dock;
        $oversized_tube_shelves = $get_physical_location_id->oversized_tube_shelves;
        $express_staging_area = $get_physical_location_id->express_staging_area;
        $express_pallet = $get_physical_location_id->express_pallet;
        $destruction = $get_physical_location_id->destruction;
        $shipping_dock_area = $get_physical_location_id->shipping_dock_area;
      	$discrepancy = $get_physical_location_id->discrepancy;
        $shelf_location = $get_physical_location_id->shelf_location;
        $pallet_id = $get_physical_location_id->pallet_id;
        
        if(empty($pallet_id)) {
            $get_physical_location_no_pallet = $wpdb->get_row("SELECT DISTINCT a.scanning_id, a.stagingarea_id, a.cart_id, a.validation_location_area_id, a.qaqc_location_area_id, a.scanning_prep_location_area_id, a.scanning_location_area_id,
            a.receiving_dock, a.oversized_tube_shelves, a.express_staging_area, a.express_pallet, a.destruction, a.shipping_dock_area, a.discrepancy, a.shelf_location
            FROM " . $wpdb->prefix . "wpsc_epa_scan_list a
            INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.box_id = a.box_id
            WHERE (a.scanning_id IS NOT NULL OR a.stagingarea_id IS NOT NULL OR a.cart_id IS NOT NULL OR a.validation_location_area_id IS NOT NULL OR a.qaqc_location_area_id IS NOT NULL OR a.scanning_prep_location_area_id IS NOT NULL OR a.scanning_location_area_id IS NOT NULL
            OR a.receiving_dock IS NOT NULL OR a.oversized_tube_shelves IS NOT NULL OR a.express_staging_area IS NOT NULL OR a.express_pallet IS NOT NULL OR a.destruction IS NOT NULL OR a.shipping_dock_area IS NOT NULL OR a.discrepancy IS NOT NULL OR a.shelf_location IS NOT NULL)
            AND b.box_id = '" .  $identifier . "'");
    
            $scanning_id = $get_physical_location_no_pallet->scanning_id;
            $stagingarea_id = $get_physical_location_no_pallet->stagingarea_id;
            $cart_id = $get_physical_location_no_pallet->cart_id;
            $validation_location_area_id = $get_physical_location_id->validation_location_area_id;
            $qaqc_location_area_id = $get_physical_location_id->qaqc_location_area_id;
            $scanning_prep_location_area_id = $get_physical_location_id->scanning_prep_location_area_id;
            $scanning_location_area_id = $get_physical_location_id->scanning_location_area_id;
            $receiving_dock = $get_physical_location_id->receiving_dock;
            $oversized_tube_shelves = $get_physical_location_id->oversized_tube_shelves;
            $express_staging_area = $get_physical_location_id->express_staging_area;
            $express_pallet = $get_physical_location_id->express_pallet;
            $destruction = $get_physical_location_id->destruction;
            $shipping_dock_area = $get_physical_location_id->shipping_dock_area;
          	$discrepancy = $get_physical_location_id->discrepancy;
            $shelf_location = $get_physical_location_no_pallet->shelf_location;
        }
        
        if(!empty($scanning_id)) {
            return $scanning_id;
        }
        elseif(!empty($stagingarea_id)) {
            return $stagingarea_id;
        }
        elseif(!empty($cart_id)) {
            return $cart_id;
        }
        elseif(!empty($validation_location_area_id)) {
            return $validation_location_area_id;
        }
        elseif(!empty($qaqc_location_area_id)) {
            return $qaqc_location_area_id;
        }
        elseif(!empty($scanning_prep_location_area_id)) {
            return $scanning_prep_location_area_id;
        }
        elseif(!empty($scanning_location_area_id)) {
            return $scanning_location_area_id;
        }
        elseif(!empty($receiving_dock)) {
            return $receiving_dock;
        }
        elseif(!empty($oversized_tube_shelves)) {
            return $oversized_tube_shelves;
        }
        elseif(!empty($express_staging_area)) {
            return $express_staging_area;
        }
        elseif(!empty($express_pallet)) {
            return $express_pallet;
        }
        elseif(!empty($destruction)) {
            return $destruction;
        }
        elseif(!empty($shipping_dock_area)) {
            return $shipping_dock_area;
        }
      	elseif(!empty($discrepancy)) {
            return $discrepancy;
        }
        elseif(!empty($shelf_location)) {
            return $shelf_location;
        }
        else {
            return false;
        }
    }
    
    if($type == 'folderfile') {
        $get_physical_location_id = $wpdb->get_row("SELECT DISTINCT a.scanning_id, a.stagingarea_id, a.cart_id, a.validation_location_area_id, a.qaqc_location_area_id, a.scanning_prep_location_area_id, a.scanning_location_area_id,
        a.receiving_dock, a.oversized_tube_shelves, a.express_staging_area, a.express_pallet, a.destruction, a.shipping_dock_area, a.discrepancy, a.shelf_location, b.pallet_id
        FROM wpqa_wpsc_epa_scan_list a
        INNER JOIN wpqa_wpsc_epa_boxinfo b ON b.scan_list_id = a.id
        INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
        WHERE (a.scanning_id IS NOT NULL OR a.stagingarea_id IS NOT NULL OR a.cart_id IS NOT NULL OR a.validation_location_area_id IS NOT NULL OR a.qaqc_location_area_id IS NOT NULL OR a.scanning_prep_location_area_id IS NOT NULL OR a.scanning_location_area_id IS NOT NULL 
        OR a.receiving_dock IS NOT NULL OR a.oversized_tube_shelves IS NOT NULL OR a.express_staging_area IS NOT NULL OR a.express_pallet IS NOT NULL OR a.destruction IS NOT NULL OR a.shipping_dock_area OR a.discrepancy IS NOT NULL OR a.shelf_location IS NOT NULL)
        AND c.folderdocinfofile_id = '" .  $identifier . "'");

        $scanning_id = $get_physical_location_id->scanning_id;
        $stagingarea_id = $get_physical_location_id->stagingarea_id;
        $cart_id = $get_physical_location_id->cart_id;
        $validation_location_area_id = $get_physical_location_id->validation_location_area_id;
        $qaqc_location_area_id = $get_physical_location_id->qaqc_location_area_id;
        $scanning_prep_location_area_id = $get_physical_location_id->scanning_prep_location_area_id;
        $scanning_location_area_id = $get_physical_location_id->scanning_location_area_id;
        $receiving_dock = $get_physical_location_id->receiving_dock;
        $oversized_tube_shelves = $get_physical_location_id->oversized_tube_shelves;
        $express_staging_area = $get_physical_location_id->express_staging_area;
        $express_pallet = $get_physical_location_id->express_pallet;
        $destruction = $get_physical_location_id->destruction;
        $shipping_dock_area = $get_physical_location_id->shipping_dock_area;
      	$discrepancy = $get_physical_location_id->discrepancy;
        $shelf_location = $get_physical_location_id->shelf_location;
        $pallet_id = $get_physical_location_id->pallet_id;
        
        if(empty($pallet_id)) {
            $get_physical_location_id = $wpdb->get_row("SELECT DISTINCT a.scanning_id, a.stagingarea_id, a.cart_id, a.validation_location_area_id, a.qaqc_location_area_id, a.scanning_prep_location_area_id, a.scanning_location_area_id, 
            a.receiving_dock, a.oversized_tube_shelves, a.express_staging_area, a.express_pallet, a.destruction, a.shipping_dock_area, a.discrepancy, a.shelf_location
            FROM wpqa_wpsc_epa_scan_list a
            INNER JOIN wpqa_wpsc_epa_boxinfo b ON b.box_id = a.box_id
            INNER JOIN wpqa_wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
            WHERE (a.scanning_id IS NOT NULL OR a.stagingarea_id IS NOT NULL OR a.cart_id IS NOT NULL OR a.validation_location_area_id IS NOT NULL OR a.qaqc_location_area_id IS NOT NULL OR a.scanning_prep_location_area_id IS NOT NULL OR a.scanning_location_area_id IS NOT NULL 
            OR a.receiving_dock IS NOT NULL OR a.oversized_tube_shelves IS NOT NULL OR a.express_staging_area IS NOT NULL OR a.express_pallet IS NOT NULL OR a.destruction IS NOT NULL OR a.shipping_dock_area IS NOT NULL OR a.discrepancy IS NOT NULL OR a.shelf_location IS NOT NULL)
            AND c.folderdocinfofile_id = '" .  $identifier . "'");
    
            $scanning_id = $get_physical_location_id->scanning_id;
            $stagingarea_id = $get_physical_location_id->stagingarea_id;
            $cart_id = $get_physical_location_id->cart_id;
            $validation_location_area_id = $get_physical_location_id->validation_location_area_id;
            $qaqc_location_area_id = $get_physical_location_id->qaqc_location_area_id;
            $scanning_prep_location_area_id = $get_physical_location_id->scanning_prep_location_area_id;
            $scanning_location_area_id = $get_physical_location_id->scanning_location_area_id;
            $receiving_dock = $get_physical_location_id->receiving_dock;
            $oversized_tube_shelves = $get_physical_location_id->oversized_tube_shelves;
            $express_staging_area = $get_physical_location_id->express_staging_area;
            $express_pallet = $get_physical_location_id->express_pallet;
            $destruction = $get_physical_location_id->destruction;
            $shipping_dock_area = $get_physical_location_id->shipping_dock_area;
          	$discrepancy = $get_physical_location_id->discrepancy;
            $shelf_location = $get_physical_location_id->shelf_location;
        }
        
        if(!empty($scanning_id)) {
            return $scanning_id;
        }
        elseif(!empty($stagingarea_id)) {
            return $stagingarea_id;
        }
        elseif(!empty($cart_id)) {
            return $cart_id;
        }
        elseif(!empty($validation_location_area_id)) {
            return $validation_location_area_id;
        }
        elseif(!empty($qaqc_location_area_id)) {
            return $qaqc_location_area_id;
        }
        elseif(!empty($scanning_prep_location_area_id)) {
            return $scanning_prep_location_area_id;
        }
        elseif(!empty($scanning_location_area_id)) {
            return $scanning_location_area_id;
        }
        elseif(!empty($receiving_dock)) {
            return $receiving_dock;
        }
        elseif(!empty($oversized_tube_shelves)) {
            return $oversized_tube_shelves;
        }
        elseif(!empty($express_staging_area)) {
            return $express_staging_area;
        }
        elseif(!empty($express_pallet)) {
            return $express_pallet;
        }
        elseif(!empty($destruction)) {
            return $destruction;
        }
        elseif(!empty($discrepancy)) {
            return $discrepancy;
        }
        elseif(!empty($shelf_location)) {
            return $shelf_location;
        }
        else {
            return false;
        }
    }
    if($type == 'folderfile_archive') {
        $get_physical_location_id = $wpdb->get_row("SELECT DISTINCT a.scanning_id, a.stagingarea_id, a.cart_id, a.validation_location_area_id, a.qaqc_location_area_id, a.scanning_prep_location_area_id, a.scanning_location_area_id,
        a.receiving_dock, a.oversized_tube_shelves, a.express_staging_area, a.express_pallet, a.destruction, a.shipping_dock_area, a.discrepancy, a.shelf_location, b.pallet_id
        FROM wpqa_wpsc_epa_scan_list a
        INNER JOIN wpqa_wpsc_epa_boxinfo b ON b.scan_list_id = a.id
        INNER JOIN wpqa_wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
        WHERE (a.scanning_id IS NOT NULL OR a.stagingarea_id IS NOT NULL OR a.cart_id IS NOT NULL OR a.validation_location_area_id IS NOT NULL OR a.qaqc_location_area_id IS NOT NULL OR a.scanning_prep_location_area_id IS NOT NULL OR a.scanning_location_area_id IS NOT NULL 
        OR a.receiving_dock IS NOT NULL OR a.oversized_tube_shelves IS NOT NULL OR a.express_staging_area IS NOT NULL OR a.express_pallet IS NOT NULL OR a.destruction IS NOT NULL OR a.shipping_dock_area IS NOT NULL OR a.shipping_dock_area OR a.discrepancy IS NOT NULL OR a.shelf_location IS NOT NULL)
        AND c.folderdocinfofile_id = '" .  $identifier . "'");

        $scanning_id = $get_physical_location_id->scanning_id;
        $stagingarea_id = $get_physical_location_id->stagingarea_id;
        $cart_id = $get_physical_location_id->cart_id;
        $validation_location_area_id = $get_physical_location_id->validation_location_area_id;
        $qaqc_location_area_id = $get_physical_location_id->qaqc_location_area_id;
        $scanning_prep_location_area_id = $get_physical_location_id->scanning_prep_location_area_id;
        $scanning_location_area_id = $get_physical_location_id->scanning_location_area_id;
        $receiving_dock = $get_physical_location_id->receiving_dock;
        $oversized_tube_shelves = $get_physical_location_id->oversized_tube_shelves;
        $express_staging_area = $get_physical_location_id->express_staging_area;
        $express_pallet = $get_physical_location_id->express_pallet;
        $destruction = $get_physical_location_id->destruction;
        $shipping_dock_area = $get_physical_location_id->shipping_dock_area;
      	$discrepancy = $get_physical_location_id->discrepancy;
        $shelf_location = $get_physical_location_id->shelf_location;
        $pallet_id = $get_physical_location_id->pallet_id;
        
        if(empty($pallet_id)) {
            $get_physical_location_id = $wpdb->get_row("SELECT DISTINCT a.scanning_id, a.stagingarea_id, a.cart_id, a.validation_location_area_id, a.qaqc_location_area_id, a.scanning_prep_location_area_id, a.scanning_location_area_id, 
            a.receiving_dock, a.oversized_tube_shelves, a.express_staging_area, a.express_pallet, a.destruction, a.shipping_dock_area, a.discrepancy, a.shelf_location
            FROM wpqa_wpsc_epa_scan_list a
            INNER JOIN wpqa_wpsc_epa_boxinfo b ON b.box_id = a.box_id
            INNER JOIN wpqa_wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
            WHERE (a.scanning_id IS NOT NULL OR a.stagingarea_id IS NOT NULL OR a.cart_id IS NOT NULL OR a.validation_location_area_id IS NOT NULL OR a.qaqc_location_area_id IS NOT NULL OR a.scanning_prep_location_area_id IS NOT NULL OR a.scanning_location_area_id IS NOT NULL
            OR a.receiving_dock IS NOT NULL OR a.oversized_tube_shelves IS NOT NULL OR a.express_staging_area IS NOT NULL OR a.express_pallet IS NOT NULL OR a.destruction IS NOT NULL OR a.shipping_dock_area IS NOT NULL OR a.discrepancy IS NOT NULL OR a.shelf_location IS NOT NULL)
            AND c.folderdocinfofile_id = '" .  $identifier . "'");

            $scanning_id = $get_physical_location_id->scanning_id;
            $stagingarea_id = $get_physical_location_id->stagingarea_id;
            $cart_id = $get_physical_location_id->cart_id;
            $validation_location_area_id = $get_physical_location_id->validation_location_area_id;
            $qaqc_location_area_id = $get_physical_location_id->qaqc_location_area_id;
            $scanning_prep_location_area_id = $get_physical_location_id->scanning_prep_location_area_id;
            $scanning_location_area_id = $get_physical_location_id->scanning_location_area_id;
            $receiving_dock = $get_physical_location_id->receiving_dock;
            $oversized_tube_shelves = $get_physical_location_id->oversized_tube_shelves;
            $express_staging_area = $get_physical_location_id->express_staging_area;
            $express_pallet = $get_physical_location_id->express_pallet;
            $destruction = $get_physical_location_id->destruction;
            $shipping_dock_area = $get_physical_location_id->shipping_dock_area;
          	$discrepancy = $get_physical_location_id->discrepancy;
            $shelf_location = $get_physical_location_id->shelf_location;
        }
            
        if(!empty($scanning_id)) {
            return $scanning_id;
        }
        elseif(!empty($stagingarea_id)) {
            return $stagingarea_id;
        }
        elseif(!empty($cart_id)) {
            return $cart_id;
        }
        elseif(!empty($validation_location_area_id)) {
            return $validation_location_area_id;
        }
        elseif(!empty($qaqc_location_area_id)) {
            return $qaqc_location_area_id;
        }
        elseif(!empty($scanning_prep_location_area_id)) {
            return $scanning_prep_location_area_id;
        }
        elseif(!empty($scanning_location_area_id)) {
            return $scanning_location_area_id;
        }
        elseif(!empty($receiving_dock)) {
            return $receiving_dock;
        }
        elseif(!empty($oversized_tube_shelves)) {
            return $oversized_tube_shelves;
        }
        elseif(!empty($express_staging_area)) {
            return $express_staging_area;
        }
        elseif(!empty($express_pallet)) {
            return $express_pallet;
        }
        elseif(!empty($destruction)) {
            return $destruction;
        }
        elseif(!empty($shipping_dock_area)) {
            return $shipping_dock_area;
        }
      	elseif(!empty($discrepancy)) {
            return $discrepancy;
        }
        elseif(!empty($shelf_location)) {
            return $shelf_location;
        }
        else {
            return false;
        }
    }
}

/**
 * Determine if box is destroyed by type 
 */
public static function id_in_box_destroyed( $identifier, $type ) {
    global $wpdb;
    
    if($type == 'request') {
        $get_box_destroyed_data = $wpdb->get_row("SELECT SUM(b.box_destroyed) as total_box_destroyed
        FROM " . $wpdb->prefix . "wpsc_ticket a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
        WHERE a.request_id = '" .  $identifier . "'");
        $box_destroyed_sum = $get_box_destroyed_data->total_box_destroyed;
	        
		if ($box_destroyed_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'box') {
        $get_box_destroyed_data = $wpdb->get_row("SELECT a.box_destroyed as total_box_destroyed
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        WHERE a.box_id = '" .  $identifier . "'");
        $box_destroyed = $get_box_destroyed_data->total_box_destroyed;
	        
		if ($box_destroyed > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'box_archive') {
        $get_box_destroyed_data = $wpdb->get_row("SELECT a.box_destroyed as total_box_destroyed
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        WHERE a.box_id = '" .  $identifier . "'");
        $box_destroyed = $get_box_destroyed_data->total_box_destroyed;
	        
		if ($box_destroyed > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'folderfile') {
        $get_box_destroyed_data = $wpdb->get_row("SELECT a.box_destroyed as total_box_destroyed
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
        WHERE b.folderdocinfofile_id = '" .  $identifier . "'");
        $box_destroyed = $get_box_destroyed_data->total_box_destroyed;
	        
		if ($box_destroyed > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    else if($type == 'folderfile_archive') {
        $get_box_destroyed_data = $wpdb->get_row("SELECT a.box_destroyed as total_box_destroyed
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON b.box_id = a.id
        WHERE b.folderdocinfofile_id = '" .  $identifier . "'");
        $box_destroyed = $get_box_destroyed_data->total_box_destroyed;
	        
		if ($box_destroyed > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else {
        return false;
    }
}

/**
 * Determine if ID (Request,Box,Folder/File) contains a document marked as freeze
 * @return Boolean
 */
 
public static function id_in_rescan( $identifier, $type ) {
    global $wpdb;
    
    if($type == 'folderfile') {
        $get_rescan_data = $wpdb->get_row("SELECT rescan
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $rescan = $get_rescan_data->rescan;
	        
		if ($rescan > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    else if($type == 'folderfile_archive') {
        $get_rescan_data = $wpdb->get_row("SELECT rescan
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $rescan = $get_rescan_data->rescan;
	        
		if ($rescan > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else {
        return false;
    }
}

/**
 * Determine if ID (Request,Box,Folder/File) contains a document that has been validated
 * @return Boolean
 */
 
public static function id_in_validation( $identifier, $type ) {
    global $wpdb;
    
    if($type == 'box') {
        $get_validation_data = $wpdb->get_row("SELECT SUM(b.validation) as total_validation
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $validation_sum = $get_validation_data->total_validation;
	   
	   $get_file_count = $wpdb->get_row("SELECT COUNT(b.id) as total
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a 
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $file_count = $get_file_count->total;
	   
		if ($validation_sum == $file_count) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'box_archive') {
        $get_validation_data = $wpdb->get_row("SELECT SUM(b.validation) as total_validation
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $validation_sum = $get_validation_data->total_validation;
	   
	   $get_file_count = $wpdb->get_row("SELECT COUNT(b.id) as total
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a 
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $file_count = $get_file_count->total;
	   
		if ($validation_sum == $file_count) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'folderfile') {
        $get_validation_data = $wpdb->get_row("SELECT validation
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $validation = $get_validation_data->validation;
	        
		if ($validation > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'folderfile_archive') {
        $get_validation_data = $wpdb->get_row("SELECT validation
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $validation = $get_validation_data->validation;
	        
		if ($validation > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else {
        return false;
    }
}

/**
 * Determine if document has been validated.
 * @return username of user who validated a folder/file
 */
 
public static function get_validation_user( $identifier ) {
    global $wpdb;
    
    $get_username_validated = $wpdb->get_row("SELECT b.user_login
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
    INNER JOIN " . $wpdb->prefix . "users b ON b.ID = a.validation_user_id
    WHERE a.folderdocinfofile_id = '" .  $identifier . "'");
    $username_validated = $get_username_validated->user_login;
        
	if (!empty($username_validated)) {
		return $username_validated;
	}
	else {
		return false;
	}
}

/**
 * Convert folderdocinfofile_id to dbid
 * @return db id
 */
 
public static function convert_folderdocinfofile_id( $identifier ) {
    global $wpdb;
    $get_dbid = $wpdb->get_row("SELECT id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE folderdocinfofile_id = '" .  $identifier . "'");
    $db_id = $get_dbid->id;
    
    return $db_id;
}

/**
 * Determine if Folder/File is a Parent or Child
 * @return Boolean
 */
 
public static function parent_child_indicator( $identifier, $type) {
    global $wpdb;
    
    if($type == 'folderfile') {
        $get_parent_child = $wpdb->get_row("SELECT id, parent_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $db_id = $get_parent_child->id;
        $parent_id = $get_parent_child->parent_id;
	        
		if ($db_id == $parent_id) {
			return 0;
		}
		else {
			return 1;
		}
    }
    
    else if($type == 'folderfile_archive') {
        $get_parent_child = $wpdb->get_row("SELECT id, parent_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $db_id = $get_parent_child->id;
        $parent_id = $get_parent_child->parent_id;
	        
		if ($db_id == $parent_id) {
			return 0;
		}
		else {
			return 1;
		}
    }
}

/**
 * Determine the parent of a child document
 * @return parent folderdocinfofile_id
 */

public static function get_parent_of_child( $identifier, $type ) {
    global $wpdb;
    
    if($type == 'folderfile') {
        $get_parent_id = $wpdb->get_row("SELECT parent_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $parent_id = $get_parent_id->parent_id;
        
        //Find parent by comparing parent id to db id
        $get_folderdocinfofile_id = $wpdb->get_row("SELECT folderdocinfofile_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE id = '" .  $parent_id . "'");
        $folderdocinfofile_id = $get_folderdocinfofile_id->folderdocinfofile_id;
        
        return $folderdocinfofile_id;
    }
    else if($type == 'folderfile_archive') {
        $get_parent_id = $wpdb->get_row("SELECT parent_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $parent_id = $get_parent_id->parent_id;
        
        //Find parent by comparing parent id to db id
        $get_folderdocinfofile_id = $wpdb->get_row("SELECT folderdocinfofile_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE id = '" .  $parent_id . "'");
        $folderdocinfofile_id = $get_folderdocinfofile_id->folderdocinfofile_id;
        
        return $folderdocinfofile_id;
    }
    else {
        return false;
    }
}

/**
 * Determine the total count of children for a single parent
 * @return count of children
 */
public static function get_count_of_children_for_parent( $identifier, $type ) {
    global $wpdb;
    
    if($type == 'folderfile') {
        $get_parent_id = $wpdb->get_row("SELECT parent_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $parent_id = $get_parent_id->parent_id;
        
        $get_child_count = $wpdb->get_row("SELECT count(id) as child_count
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE id <> '" .  $parent_id . "' AND
        parent_id = '" .  $parent_id . "'");
        $child_count = $get_child_count->child_count;
        
        return $child_count;
    }
    else if($type == 'folderfile_archive') {
        $get_parent_id = $wpdb->get_row("SELECT parent_id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $parent_id = $get_parent_id->parent_id;
        
        $get_child_count = $wpdb->get_row("SELECT count(id) as child_count
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE id <> '" .  $parent_id . "' AND
        parent_id = '" .  $parent_id . "'");
        $child_count = $get_child_count->child_count;
        
        return $child_count;
    }
    else {
        return false;
    }
}

/**
 * Determine if ID (Request,Box,Folder/File) contains a document marked as damaged
 * @return Boolean
 */
 
public static function get_converted_date( $identifier ) {
    global $wpdb;

    $date_explode = explode('-', $identifier);
    $converted_date = $date_explode[1] . "/" . $date_explode[2] . "/" . $date_explode[0];
	return $converted_date;
}

/**
 * Determine if ID (Request,Box,Folder/File) contains a document marked as damaged
 * @return Boolean
 */
 
public static function id_in_damaged( $identifier, $type) {
    global $wpdb;
    
    if($type == 'request') {
        $get_damaged_data = $wpdb->get_row("SELECT SUM(c.damaged) as total_damaged
        FROM " . $wpdb->prefix . "wpsc_ticket a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
        WHERE a.request_id = '" .  $identifier . "'");
        $damaged_sum = $get_damaged_data->total_damaged;
	        
		if ($damaged_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'request_archive') {
        $get_damaged_data = $wpdb->get_row("SELECT SUM(c.damaged) as total_damaged
        FROM " . $wpdb->prefix . "wpsc_ticket a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
        WHERE a.request_id = '" .  $identifier . "'");
        $damaged_sum = $get_damaged_data->total_damaged;
	        
		if ($damaged_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
     else if($type == 'box') {
        $get_damaged_data = $wpdb->get_row("SELECT SUM(b.damaged) as total_damaged
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $damaged_sum = $get_damaged_data->total_damaged;
	        
		if ($damaged_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'box_archive') {
        $get_damaged_data = $wpdb->get_row("SELECT SUM(b.damaged) as total_damaged
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $damaged_sum = $get_damaged_data->total_damaged;
	        
		if ($damaged_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    else if($type == 'folderfile') {
        $get_damaged_data = $wpdb->get_row("SELECT damaged
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $damaged = $get_damaged_data->damaged;
	        
		if ($damaged > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'folderfile_archive') {
        $get_damaged_data = $wpdb->get_row("SELECT damaged
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $damaged = $get_damaged_data->damaged;
	        
		if ($damaged > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else {
        return false;
    }
}

/**
 * Determine if ID (Request,Box,Folder/File) contains a document marked as freeze
 * @return Boolean
 */
 
public static function id_in_freeze( $identifier, $type ) {
    global $wpdb;
    
    if($type == 'request') {
        $get_freeze_data = $wpdb->get_row("SELECT SUM(c.freeze) as total_freeze
        FROM " . $wpdb->prefix . "wpsc_ticket a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
        WHERE a.request_id = '" .  $identifier . "'");
        $freeze_sum = $get_freeze_data->total_freeze;
	        
		if ($freeze_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'request_archive') {
        $get_freeze_data = $wpdb->get_row("SELECT SUM(c.freeze) as total_freeze
        FROM " . $wpdb->prefix . "wpsc_ticket a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
        WHERE a.request_id = '" .  $identifier . "'");
        $freeze_sum = $get_freeze_data->total_freeze;
	        
		if ($freeze_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'box') {
        $get_freeze_data = $wpdb->get_row("SELECT SUM(b.freeze) as total_freeze
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $freeze_sum = $get_freeze_data->total_freeze;
	        
		if ($freeze_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'box_archive') {
        $get_freeze_data = $wpdb->get_row("SELECT SUM(b.freeze) as total_freeze
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $freeze_sum = $get_freeze_data->total_freeze;
	        
		if ($freeze_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'folderfile') {
        $get_freeze_data = $wpdb->get_row("SELECT freeze
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $freeze = $get_freeze_data->freeze;
	        
		if ($freeze > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'folderfile_archive') {
        $get_freeze_data = $wpdb->get_row("SELECT freeze
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $freeze = $get_freeze_data->freeze;
	        
		if ($freeze > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else {
        return false;
    }
}

/**
 * Determine if ID (Request,Box,Folder/File) contains a document marked as unauthorized destruction
 * @return Boolean
 */
 
public static function id_in_unauthorized_destruction( $identifier, $type ) {
    global $wpdb;
    
    if($type == 'request') {
        $get_unauthorized_destruction_data = $wpdb->get_row("SELECT SUM(c.unauthorized_destruction) as total_unauthorized_destruction
        FROM " . $wpdb->prefix . "wpsc_ticket a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
        WHERE a.request_id = '" .  $identifier . "'");
        $unauthorized_destruction_sum = $get_unauthorized_destruction_data->total_unauthorized_destruction;
	        
		if ($unauthorized_destruction_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    if($type == 'request_archive') {
        $get_unauthorized_destruction_data = $wpdb->get_row("SELECT SUM(c.unauthorized_destruction) as total_unauthorized_destruction
        FROM " . $wpdb->prefix . "wpsc_ticket a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.ticket_id = a.id
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
        WHERE a.request_id = '" .  $identifier . "'");
        $unauthorized_destruction_sum = $get_unauthorized_destruction_data->total_unauthorized_destruction;
	        
		if ($unauthorized_destruction_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'box') {
        $get_unauthorized_destruction_data = $wpdb->get_row("SELECT SUM(b.unauthorized_destruction) as total_unauthorized_destruction
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
        WHERE a.box_id = '" .  $identifier . "'");
        $unauthorized_destruction_sum = $get_unauthorized_destruction_data->total_unauthorized_destruction;
	        
		if ($unauthorized_destruction_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'box_archive') {
        $get_unauthorized_destruction_data = $wpdb->get_row("SELECT SUM(b.unauthorized_destruction) as total_unauthorized_destruction
        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
        INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON b.box_id = b.id
        WHERE a.box_id = '" .  $identifier . "'");
        $unauthorized_destruction_sum = $get_unauthorized_destruction_data->total_unauthorized_destruction;
	        
		if ($unauthorized_destruction_sum > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'folderfile') {
        $get_unauthorized_destruction_data = $wpdb->get_row("SELECT unauthorized_destruction
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $unauthorized_destruction = $get_unauthorized_destruction_data->unauthorized_destruction;
	        
		if ($unauthorized_destruction > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else if($type == 'folderfile_archive') {
        $get_unauthorized_destruction_data = $wpdb->get_row("SELECT unauthorized_destruction
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive
        WHERE folderdocinfofile_id = '" .  $identifier . "'");
        $unauthorized_destruction = $get_unauthorized_destruction_data->unauthorized_destruction;
	        
		if ($unauthorized_destruction > 0) {
			return true;
		}
		else {
			return false;
		}
    }
    
    else {
        return false;
    }
}

/**
 * Determine if ID (Request,Box,Folder/File) contains a recall
 * @return Boolean
 */
 
public static function id_in_recall( $identifier, $type ) {
    global $wpdb;
	
	//Get term_ids for Recall status slugs
	$status_recall_denied_term_id = self::get_term_by_slug( 'recall-denied' );	 // 878
	$status_recall_cancelled_term_id = self::get_term_by_slug( 'recall-cancelled' ); //734
	$status_recall_complete_term_id = self::get_term_by_slug( 'recall-complete' ); //733
    $status_recall_received_at_ndc = self::get_term_by_slug( 'recall-received-at-ndc' ); //4801
	
	$get_entire_box_recall_data = '';
	$recall_id_array = array();
	
	if ($type == 'request')  {
/*
        $get_recall_data = $wpdb->get_results("
			SELECT DISTINCT LEFT(b.box_id, 7) as id
			FROM ".$wpdb->prefix."wpsc_epa_recallrequest a
			INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON a.box_id = b.id
			WHERE a.box_id <> '-99999' AND a.recall_status_id NOT IN ('" . $status_recall_complete_term_id . "','" . $status_recall_cancelled_term_id . "','" . $status_recall_denied_term_id . "')
			UNION
			SELECT DISTINCT LEFT(c.folderdocinfofile_id, 7) as request_id
			FROM ".$wpdb->prefix."wpsc_epa_recallrequest a
			INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo_files c on a.folderdoc_id = c.id
			INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo b on c.folderdocinfo_id = b.id
			WHERE a.folderdoc_id <> '-99999' AND a.recall_status_id NOT IN ('" . $status_recall_complete_term_id . "','" . $status_recall_cancelled_term_id . "','" . $status_recall_denied_term_id . "')
		 ");
*/
        $get_recall_data = $wpdb->get_results("
			SELECT DISTINCT LEFT(b.box_id, 7) as id
			FROM ".$wpdb->prefix."wpsc_epa_recallrequest a
			INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON a.box_id = b.id
			WHERE a.box_id <> '-99999' AND a.recall_status_id NOT IN ('" . $status_recall_complete_term_id . "','" . $status_recall_cancelled_term_id . "','" . $status_recall_received_at_ndc . "','" . $status_recall_denied_term_id . "')
			UNION
			SELECT DISTINCT LEFT(c.folderdocinfofile_id, 7) as request_id
			FROM ".$wpdb->prefix."wpsc_epa_recallrequest a
			INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo_files c on a.folderdoc_id = c.id
			WHERE a.folderdoc_id <> '-99999' AND a.recall_status_id NOT IN ('" . $status_recall_complete_term_id . "','" . $status_recall_cancelled_term_id . "','" . $status_recall_received_at_ndc . "','" . $status_recall_denied_term_id . "')
		 ");

        foreach ($get_recall_data as $recall_id_val) {
        	$recall_id_vals = $recall_id_val->id;
			array_push($recall_id_array, $recall_id_vals);
        }
	        
		if (in_array($identifier, $recall_id_array)) {
			return true;
		}
		else {
			return false;
		}
	} else if($type == 'box') {
	    $get_recall_data = $wpdb->get_results("
			SELECT DISTINCT b.box_id as id
			FROM ".$wpdb->prefix."wpsc_epa_recallrequest a
			INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON a.box_id = b.id
			WHERE a.box_id <> '-99999' AND a.recall_status_id NOT IN ('" . $status_recall_complete_term_id . "','" . $status_recall_cancelled_term_id . "','" . $status_recall_received_at_ndc . "','" . $status_recall_denied_term_id . "')
		 ");
	    
	    foreach ($get_recall_data as $recall_id_val) {
	        $recall_id_vals = $recall_id_val->id;
	        array_push($recall_id_array, $recall_id_vals);
	    }
	        
		if (in_array($identifier, $recall_id_array)) {
			return true;
		} else {
			return false;
		}
	} else if($type == 'folderfile') {
	        $get_entire_box_recall_data = $wpdb->get_results("
				SELECT DISTINCT b.box_id as id
				FROM ".$wpdb->prefix."wpsc_epa_recallrequest a
				INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON a.box_id = b.id
				WHERE a.box_id <> '-99999' AND a.folderdoc_id = '-99999' AND a.recall_status_id NOT IN ('" . $status_recall_complete_term_id . "','" . $status_recall_cancelled_term_id . "','" . $status_recall_received_at_ndc . "','" . $status_recall_denied_term_id . "')
	        ");
	
		// Determine if the entire box has been recalled
		if ( count( $get_entire_box_recall_data ) > 0 ) {
	        foreach ( $get_entire_box_recall_data as $box_recall_id_val ) {
	        	$recall_box_id_vals = $box_recall_id_val->id;
				
				$recall_box_id_array = array();
				        
/*				// OLD: does not account for attachment files in _files
				$get_box_contents = $wpdb->get_results("
					SELECT a.folderdocinfo_id as folderdocinfo
					FROM wpqa_wpsc_epa_folderdocinfo a 
					INNER JOIN wpqa_wpsc_epa_boxinfo b ON a.box_id = b.id WHERE b.box_id = '" . $recall_box_id_vals . "'");
*/

/*				// OLD: before removal of wpsc_epa_folderdocinfo
				$get_box_contents = $wpdb->get_results("
					SELECT
					    c.folderdocinfofile_id AS folderdocinfo
					FROM
					    ".$wpdb->prefix."wpsc_epa_folderdocinfo_files c
					INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo a ON
					    c.folderdocinfo_id = a.id    
					INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON
					    a.box_id = b.id
					WHERE
					    b.box_id = '" . $recall_box_id_vals . "'");
*/
				
				$get_box_contents = $wpdb->get_results("
					SELECT
					    c.folderdocinfofile_id AS folderdocinfo
					FROM
					    ".$wpdb->prefix."wpsc_epa_folderdocinfo_files c 
					INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON
					    c.box_id = b.id
					WHERE
					    b.box_id = '" . $recall_box_id_vals . "'");
				
				foreach ( $get_box_contents as $recall_folderdocinfo_id ) {
					$recall_box_id_content_vals = $recall_folderdocinfo_id->folderdocinfo;
					
					array_push($recall_box_id_array, $recall_box_id_content_vals);
				}
				
				if ( in_array( $identifier, $recall_box_id_array ) ) {
					return true;
				}
			}
		
		}
		
/*		// OLD: does not look for correct folderdocinfo_id which is now in _files table
		$get_recall_data = $wpdb->get_results("
			SELECT DISTINCT b.folderdocinfo_id as id
			FROM wpqa_wpsc_epa_recallrequest a
			INNER JOIN wpqa_wpsc_epa_folderdocinfo b ON a.folderdoc_id = b.id
			WHERE a.folderdoc_id <> '-99999' AND a.recall_status_id NOT IN ('" . $status_recall_complete_term_id . "','" . $status_recall_cancelled_term_id . "','" . $status_recall_denied_term_id . "')
		 ");
*/
		 
		$get_recall_data = $wpdb->get_results("
			SELECT DISTINCT
			    c.folderdocinfofile_id AS id
			FROM
			    ".$wpdb->prefix."wpsc_epa_recallrequest a
			INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo_files c ON
			    c.id = a.folderdoc_id
			WHERE
			    a.folderdoc_id <> '-99999' AND a.recall_status_id NOT IN ('" . $status_recall_complete_term_id . "','" . $status_recall_cancelled_term_id . "','" . $status_recall_received_at_ndc . "','" . $status_recall_denied_term_id . "')
		 "); 
		
		foreach( $get_recall_data as $recall_id_val ) {
	        $recall_id_vals = $recall_id_val->id;
	        array_push($recall_id_array, $recall_id_vals);
	    }
		        
		if( in_array( $identifier, $recall_id_array ) ) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
	
}


        /**
         * Determine if ID (Request,Box,Folder/File) contains a decline
         * @return Boolean
         */
         
        public static function id_in_return( $identifier, $type ) {
            global $wpdb;
			$return_id_array = array();
			
			$status_decline_cancelled_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-cancelled' ); 
			$status_decline_completed_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-complete' ); 
			$status_decline_expired_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-expired' ); 
			  
			if ( $type == 'request' )  {
		        $get_return_data = $wpdb->get_results("
						SELECT DISTINCT LEFT(b.box_id, 7) as id
						FROM ".$wpdb->prefix."wpsc_epa_return_items a
						INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON a.box_id = b.id
						INNER JOIN ".$wpdb->prefix."wpsc_epa_return c ON a.return_id = c.id
						WHERE a.box_id <> '-99999'AND c.return_status_id NOT IN (" . 
						$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.",".$status_decline_expired_term_id.")
		        ");
		
		        foreach ($get_return_data as $return_id_val) {
		        	$return_id_vals = $return_id_val->id;
					array_push($return_id_array, $return_id_vals);
		        }
			        
				if ( in_array($identifier, $return_id_array) ) {
					return true;
				}
				else {
					return false;
				}
			  
			} else if( $type == 'box' ) {
		        $get_return_data = $wpdb->get_results("
					SELECT DISTINCT b.box_id as id
					FROM ".$wpdb->prefix."wpsc_epa_return_items a
					INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON a.box_id = b.id
					INNER JOIN ".$wpdb->prefix."wpsc_epa_return c ON a.return_id = c.id
					WHERE a.box_id <> '-99999'AND c.return_status_id NOT IN (".$status_decline_cancelled_term_id.",".$status_decline_completed_term_id.",".$status_decline_expired_term_id.")
		        ");
		        
		        foreach( $get_return_data as $return_id_val ) {
			        $return_id_vals = $return_id_val->id;
			        array_push( $return_id_array, $return_id_vals );
		        }
			        
				if (in_array($identifier, $return_id_array)) {
					return true;
				} else {
					return false;
				}    
			} else if( $type == 'folderfile' ) {
		        $get_entire_box_return_data = $wpdb->get_results("
			        SELECT DISTINCT b.box_id as id
					FROM ".$wpdb->prefix."wpsc_epa_return_items a
					INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON a.box_id = b.id
					INNER JOIN ".$wpdb->prefix."wpsc_epa_return c ON a.return_id = c.id
					WHERE a.box_id <> '-99999'AND c.return_status_id NOT IN (" . $status_decline_cancelled_term_id.",".$status_decline_completed_term_id.")
			    ");
			
				// Determine if the entire box has been declined
				if ( count( $get_entire_box_return_data ) > 0 ) {
			        foreach( $get_entire_box_return_data as $box_return_id_val ) {
			        	$return_box_id_vals = $box_return_id_val->id;
			
						$return_box_id_array = array();
						
						// OLD: retrieves list of all files inside of box.
/*
						$get_box_contents = $wpdb->get_results("
							SELECT a.folderdocinfo_id as folderdocinfo
							FROM wpqa_wpsc_epa_folderdocinfo a 
							INNER JOIN wpqa_wpsc_epa_boxinfo b ON a.box_id = b.id WHERE b.box_id = '" . $return_box_id_vals . "'");
*/
						
						// OLD: before dropping wpsc_epa_folderdocinfo table
/*
						$get_box_contents = $wpdb->get_results("
							SELECT
							    c.folderdocinfofile_id AS folderdocinfo
							FROM
							    ".$wpdb->prefix."wpsc_epa_folderdocinfo a
							INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON
							    a.box_id = b.id
							INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo_files c ON 
								a.id = c.folderdocinfo_id
							WHERE
							    b.box_id = '" . $return_box_id_vals . "'");
*/
						
						// NEW: retrieves list of all files inside of box, including attachments
						$get_box_contents = $wpdb->get_results("
							SELECT
							    a.folderdocinfofile_id AS folderdocinfo
							FROM
							    ".$wpdb->prefix."wpsc_epa_folderdocinfo_files a
							INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON
							    a.box_id = b.id
							WHERE
							    b.box_id = '" . $return_box_id_vals . "'");
						
						foreach( $get_box_contents as $return_folderdocinfo_id ) {
							$return_box_id_content_vals = $return_folderdocinfo_id->folderdocinfo;
							array_push($return_box_id_array, $return_box_id_content_vals);
						}
						
						if ( in_array( $identifier, $return_box_id_array ) ) {
							return true;
							//return $get_box_contents; // DEBUG
						}
					}
			
				}
			
			} else {
				return false;
			}
		    
		}


        
        /**
         * Update user status data by status & box ID
         * @return Id
         */
        public static function update_status_by_id( $data ) {
            global $wpdb;
            $status_id = array_keys($data['status'])[0];// array_key_first($data['status']);
            if(!isset($status_id) || !isset($data['box_id'])) {
                return false;
            }

            $args = [
                'select' => 'id',
                'where' => [
                    ['box_id', $data['box_id']],
                    ['status_id', $status_id]
                ]
            ];

            $wpsc_epa_boxinfo_userstatus = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo_userstatus");
            $wpsc_epa_boxinfo_userstatus_data = $wpsc_epa_boxinfo_userstatus->get_results($args);
            
            if(count($wpsc_epa_boxinfo_userstatus_data) > 0){
                foreach($wpsc_epa_boxinfo_userstatus_data as $row_id){
                     $wpsc_epa_boxinfo_userstatus->delete($row_id->id);
                }
            }

            self::user_status_insert( $data );
            return true;
        }

        /**
         * Get all user status data
         */
        public static function get_user_status_data($where){
            global $wpdb;
            if(is_array($where) && count($where) > 0){
                foreach($where as $key => $whr) {
                    $args['where'][] = ["{$wpdb->prefix}wpsc_epa_boxinfo_userstatus.$key", "'{$whr}'"];
                }
            }
            $wpsc_epa_boxinfo_userstatus = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo_userstatus");
            $wpsc_epa_boxinfo_userstatus_records = $wpsc_epa_boxinfo_userstatus->get_results($args);
            // print_r($wpsc_epa_boxinfo_userstatus_records);
            $box_id = null;
            $status_id = null;
            $sorted_data = [];
            $counter = 1;
            $get_all_status = self::get_all_status();
            
            foreach($wpsc_epa_boxinfo_userstatus_records as $record){ 
                
                if(!isset($sorted_data[$record->box_id]['all_status'])) {
                    $sorted_data[$record->box_id]['all_status'] = $get_all_status;
                }
                
                if($record->box_id == $box_id) {
                    // echo '<br/>===B====' . $record->box_id;
                    if($record->status_id <> null){
                        // echo '<br/>===C====' . $record->box_id;
                        unset($sorted_data[$record->box_id]['all_status'][$record->status_id]);
                        $sorted_data[$record->box_id]['status'][$record->status_id][] = $record->user_id;
                        $sorted_data[$record->box_id]['other_status'] = $sorted_data[$record->box_id]['all_status'];
                    }
                } else {
                    // echo '<br/>===D====' . $record->box_id;
                    if($record->status_id <> null){
                        $sorted_data[$record->box_id]['box_id'] = $record->box_id;
                        unset($sorted_data[$record->box_id]['all_status'][$record->status_id]);
                        $sorted_data[$record->box_id]['status'][$record->status_id][] = $record->user_id;
                        $sorted_data[$record->box_id]['other_status'] = $sorted_data[$record->box_id]['all_status'];
                        $box_id = $record->box_id;
                        // print_r($sorted_data[$record->box_id]['other_status']);
                        // die($record->status_id);
                    }
                }
            }


                // die(print_r($sorted_data));
            // Add the un assigned statues to the box_id
            foreach($sorted_data as $box_id_key => $fresh_data){ 
                // print_r($sorted_data);
                // echo '==!'.$box_id_key.'!==';
                // print_r($fresh_data);
                // die($box_id_key);
                if(is_array($fresh_data['other_status']) && count($fresh_data['other_status']) > 0){
                    
                    foreach($fresh_data['other_status'] as $status_id => $other_status){
                      $sorted_data[$box_id_key]['status'][$status_id] = 'N/A'; 
                    }
                    unset($sorted_data[$box_id_key]['other_status']);
                    unset($sorted_data[$box_id_key]['all_status']);
                }
            }
            
			$sorted_data = reset($sorted_data);
            return $sorted_data;
        }

        /**
         * Insert to Userstatus
         */
        public static function user_status_insert( $data ) { 

            // die(print_r($get_all_status));
/*
            echo 'This is the data: ';
            die(print_r($data)); 
*/           
            if( is_array($data['status']) && count($data['status']) > 0 ) {
                foreach ($data['status'] as $status_id => $users) {
                    if( is_array($users) && count($users) > 0 ) {                
                        foreach($users as $user){
                            $inser_data = [
                                'box_id' => $data['box_id'],
                                'user_id' => $user,
                                'status_id' => $status_id
                            ];
                            $status_table_insert_id = self::insert_status_table($inser_data);
                            // unset($get_all_status[$status_id]);
                        }
                        return $status_table_insert_id; 
                        // die();
                    } elseif( isset($user))  {
                        $inser_data = [
                            'box_id' => $data['box_id'],
                            'user_id' => $user,
                            'status_id' => $status_id
                        ];
                        $status_table_insert_id = self::insert_status_table($inser_data);
                        // unset($get_all_status[$status_id]);
                        return $status_table_insert_id;
                    } else {
	                    //do nothing
                    }
                }
            }
        }

        /**
         * Insert Userstatus table
         */
        public static function insert_status_table($data) {
            global $wpdb;
            $insert_status_table = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo_userstatus");
            return $insert_status_table_id = $insert_status_table->insert($data);
        }

		// Gets all Box Statuses
		// Accepts array of box status to ignore (name, not id)
        public function get_all_status( $ignore_box_status = [] ) {
			global $wpdb;
            
            
            // Register Box Status Taxonomy
			if( !taxonomy_exists('wpsc_box_statuses') ) {
				$args = array(
					'public' => false,
					'rewrite' => false
				);
				register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
			}
			
			// Get List of Box Statuses
			$box_statuses = get_terms([
				'taxonomy'   => 'wpsc_box_statuses',
				'hide_empty' => false,
				'orderby'    => 'meta_value_num',
				'order'    	 => 'ASC',
				'meta_query' => array('order_clause' => array('key' => 'wpsc_box_status_load_order')),
			]);
			
			$sql = "SELECT
					    *
					FROM
					    " . $wpdb->prefix . "termmeta
					LEFT JOIN " . $wpdb->prefix . "terms ON " . $wpdb->prefix . "terms.term_id = " . $wpdb->prefix . "termmeta.term_id
					WHERE
					    meta_key = 'wpsc_box_status_load_order'
					ORDER BY length(meta_value), meta_value ASC ";
					
			$box_statuses = $wpdb->get_results( $sql );
					
			
			// List of box status that do not need agents assigned.
			// $ignore_box_status = ['Pending', 'Ingestion', 'Completed', 'Dispositioned'];
			//$ignore_box_status = [];
			
			$term_id_array = array();
			$term_and_name_array = array();
			foreach( $box_statuses as $key=>$box ) {
				if( in_array( $box->name, $ignore_box_status ) ) {
					unset($box_statuses[$key]);
				} else {
					$term_id_array[] = $box->term_id;
					$term_and_name_array[$box->term_id] = $box->name;
				}
			}
			array_values($box_statuses);
			
			return $term_and_name_array;
        }
        
        // Gets all statuses from supplied taxonomy
        public function get_all_status_from_tax( $tax ) {
			
			// Examples: 'wpsc_box_statuses', ''
			
            $tax = isset($tax) ? sanitize_text_field($tax) : '';
            $tax_prefix = $tax;
            if( $tax == 'wpsc_box_statuses') {
	            $tax_prefix = 'wpsc_box_status';
            } elseif ( $tax == 'wppatt_return_statuses') {
	            $tax_prefix = 'wppatt_return_status';
	        }
            
            // Ensure Taxonomy has been registered
			if( !taxonomy_exists($tax) ) {
				$args = array(
					'public' => false,
					'rewrite' => false
				);
				register_taxonomy( $tax, 'wpsc_ticket', $args );
			}
			
			// Get List of Statuses
			$statuses = get_terms([
				'taxonomy'   => $tax,
				'hide_empty' => false,
				'orderby'    => 'meta_value_num',
				'order'    	 => 'ASC',
				'meta_query' => array('order_clause' => array('key' => $tax_prefix.'_load_order')),
			]);
			

			$term_and_name_array = array();
			foreach( $statuses as $key=>$box ) {
					$term_and_name_array[$box->term_id] = $box->name;
				
			}
			array_values($term_and_name_array);
			
			return $term_and_name_array;
        }
        
        
         /**
         * Update return user data by return id
         * @return Id
         */
        public static function update_return_user_by_id( $data ){

            if(!isset($data['return_id']) || !isset($data['user_id'])) {
                return false;
            }

            global $wpdb;

            $args = [
                'select' => 'id',
                'where' => ['return_id', $data['return_id']]
            ];

            $wpsc_return_users = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return_users");
            $wpsc_return_user_data = $wpsc_return_users->get_results($args);
            if(count($wpsc_return_user_data) > 0){
                foreach($wpsc_return_user_data as $row_id){
                     $wpsc_return_users->delete($row_id->id);
                }
            }

            if(is_array($data['user_id']) && count($data['user_id']) > 0){
                foreach($data['user_id'] as $user_id){
                    $data_req = [
                        'return_id' => $data['return_id'],
                        'user_id'   => $user_id
                    ];
                   $insert_id = $wpsc_return_users->insert($data_req); 
                }
            } else {
                $data_req = [
                    'return_id' => $data['return_id'],
                    'user_id'   => $data['user_id']
                ];
               $insert_id =  $wpsc_return_users->insert($data_req); 
            }
            return true;
        }

        /**
         * Get return data
         * @return Id
         */
        public static function get_return_data( $where ){            
            global $wpdb;   

            if(is_array($where) && count($where) > 0){
                foreach($where as $key => $whr) {
                    if($key == 'custom') {
                       $args['where']['custom'] = $whr;
                    } elseif($key == 'filter') {                        
                        $orderby = isset($whr['orderby']) ? $whr['orderby'] : 'id';
                        $order = isset($whr['order']) ? $whr['order'] : 'DESC';
                        if($orderby == 'status') {
                            $orderby = "{$wpdb->prefix}terms.name";
                        }
                        $args['order'] = [$orderby, $order];
                        if(isset($whr['records_per_page']) && $whr['records_per_page'] > 0){  
                            $number_of_records =  isset($whr['records_per_page']) ? $whr['records_per_page'] : 20;
                            $start = isset($whr['paged']) ? $whr['paged'] : 0;
                            $args['limit'] = [$start, $number_of_records];        
                        }
                    } elseif($key == 'program_office_id') {
                            $args['where'][] = [
                                "{$wpdb->prefix}wpsc_epa_program_office.office_acronym", 
                                $whr
                            ];
                        // }
                    } elseif($key == 'digitization_center') {
                        $storage_location_id = self::get_storage_location_id_by_dc($whr);
                        if(is_array($storage_location_id) && count($storage_location_id) > 0) {
                            foreach($storage_location_id as $val){
                                if($val->id) {
                                    $dc_ids[] = $val->id;
                                }
                            }
                            $dc_ids = implode(', ', $dc_ids);
                            $args['where'][] = [
                                "{$wpdb->prefix}wpsc_epa_boxinfo.storage_location_id", 
                                "($dc_ids)",
                                "AND",
                                ' IN '
                            ];
                        }
                    } elseif($key == 'id' && is_array($whr) && count($whr) > 0) {
                        $args['where'][] = [
//                             "{$wpdb->prefix}wpsc_epa_returnrequest.id",   // table does not exist. Haven't seen error 9-22-2020
                           "{$wpdb->prefix}wpsc_epa_return.id",   // correct table? id = 1, 2, 3. return_id = 0000001, 0000002, 0000003                            
                            "(".implode(',', $whr).")",
                            "AND",
                            ' IN '
                        ];
                    } elseif($key == 'return_id' && is_array($whr) && count($whr) > 0) {
                        $args['where'][] = [
                            "{$wpdb->prefix}wpsc_epa_return.return_id", 
                            '("' . implode('", "', $whr) . '")',
                            "AND",
                            ' IN '
                        ];
                    } else {
                        $args['where'][] = ["{$wpdb->prefix}wpsc_epa_return.$key", "'{$whr}'"];
                    }
                }   
            }

            $args['where'][] = [
                                "{$wpdb->prefix}wpsc_epa_return.id", 
                                "0",
                                "AND",
                                ' > '
                            ];

            $select_fields = [
                "{$wpdb->prefix}wpsc_epa_return" => ['id', 'return_id', 'return_date', 'return_receipt_date', 'expiration_date', 'comments', 'return_status_id', 'updated_date', 'return_initiated', 'return_complete'],
                // "{$wpdb->prefix}wpsc_epa_boxinfo" => ['ticket_id', 'box_id', 'storage_location_id', 'location_status_id', 'box_destroyed', 'date_created', 'date_updated'],
//                "{$wpdb->prefix}wpsc_epa_folderdocinfo" => ['title', 'folderdocinfo_id as folderdoc_id'],
                "{$wpdb->prefix}wpsc_epa_return_items" => ['box_id', 'folderdoc_id', 'saved_box_status'],
                "{$wpdb->prefix}wpsc_epa_shipping_tracking" => ['company_name as shipping_carrier', 'tracking_number', 'status', 'shipped', 'delivered'],
                "{$wpdb->prefix}terms" => ['name as reason'],               
                "{$wpdb->prefix}wpsc_epa_return_users" => ['user_id'],
            ];

            foreach($select_fields as $key => $fields_array){
                foreach($fields_array as $field) {
                    if($key == "{$wpdb->prefix}wpsc_epa_return_users"){
                        $select[] = "GROUP_CONCAT($key.user_id) as $field";
                    } if($key == "{$wpdb->prefix}wpsc_epa_return_items"){
                        $select[] = "GROUP_CONCAT($key.$field) as $field";
                    } else {
                        $select[] = $key . '.' . $field;
                    }
                }
            }

            $args['groupby']  = "{$wpdb->prefix}wpsc_epa_return.return_id";
            $args['select']  = implode(', ', $select);
            $args['join']  = [

                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}terms", 
                            'key'  => 'term_id',
                            'compare' => '=',
                            'foreign_key' => 'return_reason_id'
                        ],
                        [
                            'type' => 'Inner JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_return_items", 
                            'key' => 'return_id',
                            'foreign_key'  => 'id',
                            'compare' => '=',
                        ],
                        // [
                        //     'type' => 'LEFT JOIN', 
                        //     'table' => "{$wpdb->prefix}wpsc_epa_boxinfo", 
                        //     'foreign_key'  => 'box_id',
                        //     'compare' => '=',
                        //     'key' => 'return_id',
                        //     'base_table' => "{$wpdb->prefix}wpsc_epa_return_items"
                        // ],
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_return_users", 
                            'foreign_key'  => 'id',
                            'compare' => '=',
                            'key' => 'return_id'
                        ],
/*
                         [
                             'type' => 'LEFT JOIN', 
                             'table' => "{$wpdb->prefix}wpsc_epa_folderdocinfo", 
                             'key'  => 'id',
                             'compare' => '=',
                             'foreign_key' => 'folderdoc_id'
                         ],
*/
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_shipping_tracking", 
                            'key'  => 'id',
                            'compare' => '=',
                            'foreign_key' => 'shipping_tracking_id'
                        ]
                        ];

            $wpsc_epa_box = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return");
            $box_details = $wpsc_epa_box->get_results($args);

            if(count($box_details) > 0 ){
                foreach($box_details as $key => $record){
                    if(!empty($record->user_id)) {
                        $record->user_id = explode(',', $record->user_id );
                    }
                    if(!empty($record->box_id)) {		
                        //$record->box_id = explode(',', $record->box_id );		
                        		
                        $temp_box_id = explode(',', $record->box_id );		
                        $the_array = [];
                        $box_id_fk_array = [];		
                        foreach( $temp_box_id as $id ) {		
	                    	$the_array[] = self::get_box_id_by_id($id)[0];	
	                    	$box_id_fk_array[] = $id;	
                        }		
                        $record->box_id = $the_array;	
                        $record->box_id_fk = $box_id_fk_array;		
                        		
                    }		
                    if(!empty($record->folderdoc_id)) {		
                        //$record->folderdoc_id = explode(',', $record->folderdoc_id );		
                        		
                        $temp_folderdoc_id = explode(',', $record->folderdoc_id );		
                        $the_array = [];		
                        foreach( $temp_folderdoc_id as $id ) {		
	                    	//$the_array[] = self::get_folderdoc_id_by_id($id)[0];		
                        }		
                        $record->folderdoc_id = $the_array;		
                    }
                    if(!empty($record->saved_box_status)) {		
                        //$record->box_id = explode(',', $record->box_id );		
                        		
                        $temp_box_status = explode(',', $record->saved_box_status );		
                        $the_array = [];		
                        foreach( $temp_box_status as $box_status ) {		
	                    	$the_array[] = $box_status;		
                        }		
                        $record->saved_box_status = $the_array;		
                        		
                    }
                }
            }
            
            return $box_details;
        }
        
        //Function to get program office acronym from folderdocinfofile_id or box_id
        public static function get_record_schedule_by_id($id, $type) {
            global $wpdb;
            
            if($type == 'box' || $type == 'box_archive') {
                $get_record_schedule = $wpdb->get_row("SELECT DISTINCT a.Schedule_Item_Number
                FROM " . $wpdb->prefix . "epa_record_schedule a
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.record_schedule_id = a.id
                WHERE b.box_id = '" . $id . "'");
                $record_schedule = $get_record_schedule->Schedule_Item_Number;
                
                return $record_schedule;
            }
            else if($type == 'folderfile') {
                $get_record_schedule = $wpdb->get_row("SELECT DISTINCT a.Schedule_Item_Number
                FROM " . $wpdb->prefix . "epa_record_schedule a
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.record_schedule_id = a.id
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
                WHERE c.folderdocinfofile_id = '" . $id . "'");
                $record_schedule = $get_record_schedule->Schedule_Item_Number;
                
                return $record_schedule;
            }
            else if($type == 'folderfile_archive') {
                $get_record_schedule = $wpdb->get_row("SELECT DISTINCT a.Schedule_Item_Number
                FROM " . $wpdb->prefix . "epa_record_schedule a
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.record_schedule_id = a.id
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
                WHERE c.folderdocinfofile_id = '" . $id . "'");
                $record_schedule = $get_record_schedule->Schedule_Item_Number;
                
                return $record_schedule;
            }
            else {
                return false;
            }
        }
        
            //Function to get record schedule name from folderdocinfofile_id or box_id
            public static function get_record_schedule_name_by_id($id, $type) {
            global $wpdb;
            
            if($type == 'box' || $type == 'box_archive') {
                $get_record_schedule = $wpdb->get_row("SELECT DISTINCT a.Schedule_Title
                FROM " . $wpdb->prefix . "epa_record_schedule a
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.record_schedule_id = a.id
                WHERE b.box_id = '" . $id . "'");
                $record_schedule = $get_record_schedule->Schedule_Title;
                
                return $record_schedule;
            }
            else if($type == 'folderfile') {
                $get_record_schedule = $wpdb->get_row("SELECT DISTINCT a.Schedule_Title
                FROM " . $wpdb->prefix . "epa_record_schedule a
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.record_schedule_id = a.id
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
                WHERE c.folderdocinfofile_id = '" . $id . "'");
                $record_schedule = $get_record_schedule->Schedule_Title;
                
                return $record_schedule;
            }
            else if($type == 'folderfile_archive') {
                $get_record_schedule = $wpdb->get_row("SELECT DISTINCT a.Schedule_Title
                FROM " . $wpdb->prefix . "epa_record_schedule a
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.record_schedule_id = a.id
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
                WHERE c.folderdocinfofile_id = '" . $id . "'");
                $record_schedule = $get_record_schedule->Schedule_Title;
                
                return $record_schedule;
            }
            else {
                return false;
            }
        }
        
        //Function to get pallet_id from folderdocinfofile_id or box_id
        public static function get_pallet_id_by_id($id, $type) {
            global $wpdb;
            
            if($type == 'box' || $type == 'box_archive') {
                $get_pallet_id = $wpdb->get_row("SELECT a.pallet_id
                FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a 
                WHERE a.box_id = '" . $id . "'");
                $pallet_id = $get_pallet_id->pallet_id;
                
                return $pallet_id;
            }
            else if($type == 'folderfile') {
                $get_pallet_id = $wpdb->get_row("SELECT DISTINCT a.pallet_id
                FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a 
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
                WHERE b.folderdocinfofile_id = '" . $id . "'");
                $pallet_id = $get_pallet_id->pallet_id;
                
                return $pallet_id;
            }
            else if($type == 'folderfile_archive') {
                $get_pallet_id = $wpdb->get_row("SELECT DISTINCT a.pallet_id
                FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive b ON b.box_id = a.id
                WHERE b.folderdocinfofile_id = '" . $id . "'");
                $pallet_id = $get_pallet_id->pallet_id;
                
                return $pallet_id;
            }
            else {
                return false;
            }
        }
        
        //Function to get program office acronym from folderdocinfofile_id or box_id
        public static function get_program_office_by_id($id, $type) {
            global $wpdb;
            
            if($type == 'box' || $type == 'box_archive') {
                $get_program_office = $wpdb->get_row("SELECT DISTINCT a.office_acronym
                FROM " . $wpdb->prefix . "wpsc_epa_program_office a 
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.program_office_id = a.office_code
                WHERE b.box_id = '" . $id . "'");
                $program_office = $get_program_office->office_acronym;
                
                return $program_office;
            }
            else if($type == 'folderfile') {
                $get_program_office = $wpdb->get_row("SELECT DISTINCT a.office_acronym
                FROM " . $wpdb->prefix . "wpsc_epa_program_office a 
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.program_office_id = a.office_code
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
                WHERE c.folderdocinfofile_id = '" . $id . "'");
                $program_office = $get_program_office->office_acronym;
                
                return $program_office;
            }
            else if($type == 'folderfile_archive') {
                $get_program_office = $wpdb->get_row("SELECT DISTINCT a.office_acronym
                FROM " . $wpdb->prefix . "wpsc_epa_program_office a 
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.program_office_id = a.office_code
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
                WHERE c.folderdocinfofile_id = '" . $id . "'");
                $program_office = $get_program_office->office_acronym;
                
                return $program_office;
            }
            else {
                return false;
            }
        }
        
        //Function to get program office name from folderdocinfofile_id or box_id
        public static function get_program_office_name_by_id($id, $type) {
            global $wpdb;
            
            if($type == 'box' || $type == 'box_archive') {
                $get_program_office = $wpdb->get_row("SELECT DISTINCT a.office_name
                FROM ".$wpdb->prefix."wpsc_epa_program_office a
                INNER JOIN ".$wpdb->prefix."wpsc_epa_boxinfo b ON b.program_office_id = a.office_code
                WHERE b.box_id = '" . $id . "'");
                $program_office = $get_program_office->office_name;
                
                return $program_office;
            }
            else if($type == 'folderfile') {
                $get_program_office = $wpdb->get_row("SELECT DISTINCT a.office_name
                FROM " . $wpdb->prefix . "wpsc_epa_program_office a 
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.program_office_id = a.office_code
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files c ON c.box_id = b.id
                WHERE c.folderdocinfofile_id = '" . $id . "'");
                $program_office = $get_program_office->office_name;
                
                return $program_office;
            }
            else if($type == 'folderfile_archive') {
                $get_program_office = $wpdb->get_row("SELECT DISTINCT a.office_name
                FROM " . $wpdb->prefix . "wpsc_epa_program_office a 
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON b.program_office_id = a.office_code
                INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive c ON c.box_id = b.id
                WHERE c.folderdocinfofile_id = '" . $id . "'");
                $program_office = $get_program_office->office_name;
                
                return $program_office;
            }
            else {
                return false;
            }
        }
        
        //Function to get first name, last name and username from the profile display name
        public static function get_full_name_by_customer_name($customer_name) {
            global $wpdb;
            
            $get_customer_id = $wpdb->get_row("SELECT a.ID as user_id FROM ".$wpdb->prefix."users as a WHERE a.display_name = '" . $customer_name . "'");
		    $customer_id = $get_customer_id->user_id;
		    $get_first_name = get_user_meta( $customer_id, 'first_name', true );
		    $get_last_name = get_user_meta( $customer_id, 'last_name', true );
		    $get_user_login = get_user_meta( $customer_id, 'nickname', true );
		    
		    if($get_first_name != '' && $get_last_name != '') {
		        $full_display_name = $get_first_name . ' ' . $get_last_name . ' (' . $get_user_login . ')';
		    }
		    else {
		        $full_display_name  = $get_user_login;
		    }
	        return $full_display_name;
        }
        
        //Function to obtain box ID from database based on ID		
        public static function get_box_id_by_id($id)		
        {		
            global $wpdb; 		
            // die(print_r($wpdb->prefix));		
            
            $array = array();		
            $args = [		
                'where' => ['id', $id],		
            ];		
            $wpqa_wpsc_epa_boxinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo");		
            $box_result = $wpqa_wpsc_epa_boxinfo->get_results($args, false);		
            foreach ($box_result as $box) {		
	            if( $box->box_id != null ) {		
	                array_push($array, $box->box_id);		
	            }		
            }		
            return $array;		
        }
        
        //Function to obtain  ID from database based on Box ID		
        public static function get_id_by_box_id($box_id)		
        {		
            global $wpdb; 		
            // die(print_r($wpdb->prefix));		
            $array = array();
            $box_id = '"'.$box_id.'"';
            $args = [		
                'where' => ['box_id', $box_id],		
            ];		
            $wpqa_wpsc_epa_boxinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo");		
            $box_result = $wpqa_wpsc_epa_boxinfo->get_results($args, false);		
            
            foreach ($box_result as $box) {	
	            //die(print_r($box));	
	            if( $box->box_id != null ) {		
	                array_push($array, $box->id);		
	            }		
            }		
            return $array[0];		
        }
        		
        /*
        //Function to obtain folderdoc ID from database based on ID		
        public static function get_folderdoc_id_by_id($id)		
        {		
            global $wpdb; 		
            // die(print_r($wpdb->prefix));		
            $array = array();		
            $args = [		
                'where' => ['id', $id],		
            ];		
            $wpqa_wpsc_epa_folderdocinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_folderdocinfo"); 		
            $folderdocinfo_result = $wpqa_wpsc_epa_folderdocinfo->get_results($args, false);		
            foreach ($folderdocinfo_result as $folderdocinfo) {		
	            if( $folderdocinfo->folderdocinfo_id != null ) {		
	                array_push($array, $folderdocinfo->folderdocinfo_id);		
	            }		
            }		
            return $array;		
        }	
        
        //Function to obtain ID from database based on folderdoc ID
        public static function get_id_by_folderdoc_id($folderdoc_id)		
        {		
            global $wpdb; 		
            // die(print_r($wpdb->prefix));		
            $array = array();	
            $folderdoc_id = '"'.$folderdoc_id.'"';	
            $args = [
                'where' => ['folderdocinfo_id', $folderdoc_id],		
            ];		
            $wpqa_wpsc_epa_folderdocinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_folderdocinfo"); 		
            $folderdocinfo_result = $wpqa_wpsc_epa_folderdocinfo->get_results($args, false);		
            foreach ($folderdocinfo_result as $folderdocinfo) {		
	            if( $folderdocinfo->folderdocinfo_id != null ) {		
	                array_push($array, $folderdocinfo->id);
	            }		
            }		
            return $array[0];		
        }	

        */
        
        //Function to obtain the ID of the Program Office from the office acryonm		
        public static function get_program_offic_id_by_acronym($acronym)		
        {		
            global $wpdb; 		
            // die(print_r($acronym));		
            $array = array();
            $acronym = '"'.$acronym.'"';		
            $args = [		
                'where' => ['office_acronym', $acronym],		
            ];		
            $wpqa_wpsc_epa_po = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_program_office");		
            $po_result = $wpqa_wpsc_epa_po->get_results($args, false);		
            //die(print_r($po_result));		
            foreach ($po_result as $po) {		
	            if( $po->id != null ) {		
	                array_push($array, $po->id);		
	            }		
            }		
            return $array[0];		
        }	

        /**
         * Insert return data
         * @return Id
         */
        public static function insert_return_data( $data ){            
            global $wpdb;   
           
            $user_id = $data['user_id'];
            unset($data['user_id']);

            $folderdoc_id = isset($data['folderdoc_id']) ? $data['folderdoc_id'] : -99999;;
            unset($data['folderdoc_id']);
            
            $box_id = isset($data['box_id']) ? $data['box_id'] : -99999;
            unset($data['box_id']);
            
            $box_status = isset($data['box_status']) ? $data['box_status'] : -99999;
            unset($data['box_status']);
            
            // Store tracking info
            $shipping_tracking_info = $data['shipping_tracking_info'];
            unset($data['shipping_tracking_info']);
            
            // New Test
/*
            $shipping_tracking_number = isset($data['shipping_tracking_id']) ? $data['shipping_tracking_id'] : '';  
            unset($data['shipping_tracking_id']);   
                
            $shipping_carrier = isset($data['shipping_carrier']) ? $data['shipping_carrier'] : '';  
            unset($data['shipping_carrier']);   
*/
            // New Test END

            // Updated At
            $data['updated_date'] = date("Y-m-d H:i:s");
            
            // DEFAULT ID
            $data['return_id'] = '000000';
            $data['shipping_tracking_id'] = -99999;
            
            // Default Return Status
           
			//$status_decline_initiated_term_id = Patt_Custom_Func::get_term_by_slug( 'decline-initiated' );
			$status_decline_initiated_term_id = self::get_term_by_slug( 'decline-initiated' );
//             $data['return_status_id'] = 752; // wp_terms 752 = Return Initiated.
            $data['return_status_id'] = $status_decline_initiated_term_id; // wp_terms 752 = Decline Initiated.

            //print_r($data); die();
            if ( $box_id == -99999 && $folderdoc_id == -99999 ) {
                die('Cannot insert a value without a Box and Folder/File ID - ERR-001');
            } elseif ( ( is_array($box_id) && count($box_id) == 0 ) && $folderdoc_id == -99999 ) {
                die('Cannot insert a value without a Box and Folder/File ID - ERR-002');
            } elseif ( $box_id == -99999 && ( is_array($folderdoc_id) && count($folderdoc_id) == 0) ) {
                die('Cannot insert a value without a Box and Folder/File ID - ERR-003');
            } elseif ( ( is_array($box_id) && count($box_id) == 0 ) && ( is_array($folderdoc_id) && count($folderdoc_id) == 0) ) {
                die('Cannot insert a value without a Box and Folder/File ID - ERR-004');
            }
            
            $wpsc_return_method = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return");  
            $return_insert_id = $wpsc_return_method->insert($data); 
                
            // Add row to Shipping Table    
            // $shipping_tracking_number = isset($data['shipping_tracking_id']) ? $data['shipping_tracking_id'] : '';   
            // unset($data['shipping_tracking_id']);    
                
            // $shipping_carrier = isset($data['shipping_carrier']) ? $data['shipping_carrier'] : '';   
            // unset($data['shipping_carrier']);
            $shipping_return_insert_id = -99999;
            $current_date = date("Y-m-d");
            if(isset($return_insert_id) && isset($shipping_tracking_info)) {
                $shipping_data = [  
                    'ticket_id' => -99999,  
                    'company_name' => $shipping_tracking_info['company_name'],  
                    'tracking_number' => $shipping_tracking_info['tracking_number'],
                    //  'tracking_number' => 4, 
                    'status' => '', 
                    'shipped' => 0, 
                    'delivered' => 0,                   
                    'recallrequest_id' => -99999,   
                    'return_id' => $return_insert_id,
                    'date_added' => $current_date
                ];  
                $wpsc_shipping_method = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_shipping_tracking"); 
                $shipping_return_insert_id = $wpsc_shipping_method->insert($shipping_data); 
            }
            // Update Shipping Tracking ID in Return Table  
            $update_shipping_id['shipping_tracking_id'] = $shipping_return_insert_id;   
            $shipping_recall_updated = $wpsc_return_method->update($update_shipping_id, ['id' => $return_insert_id]);   
                
            // Update the return ID with insert ID  
            $num = $return_insert_id;   
            $str_length = 7;    
            $update_data['return_id'] = substr("000000{$num}", -$str_length);   
            $return_updated = $wpsc_return_method->update($update_data, ['id' => $return_insert_id]);               

            // Add data to return_users 
            $wpsc_epa_box_user = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return_users");        
            if(is_array($user_id) && count($user_id) > 0){
                foreach($user_id as $user){
                    $user_data = [
                        'user_id' => $user,
                        'return_id' => $return_insert_id
                    ];
                    $box_details = $wpsc_epa_box_user->insert($user_data);
                }
            } else {
                $user_data = [
                    'user_id' => $user_id,
                    'return_id' => $return_insert_id
                ];
                $box_details = $wpsc_epa_box_user->insert($user_data);
            }

            // Add data to return items table
            $wpsc_epa_return_items = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return_items");
            // if($return_insert_id){
            //     $item_data = [
            //         'box_id' => $box_id,
            //         'folderdoc_id' => $folderdoc_id,
            //         'return_id' => $return_insert_id
            //     ];
            //     // print_r($item_data);
            //     $wpsc_epa_rec = $wpsc_epa_return_items->insert($item_data);
            // }

            if(is_array($box_id) && count($box_id) > 0){
                foreach($box_id as $key => $box){
                    $item_data = [
                        'box_id' => $box,
//                         'folderdoc_id' => -99999,
						'folderdoc_id' => null,
                        'return_id' => $return_insert_id,
                        'saved_box_status' => $box_status[$key]
                    ];
                    $wpsc_epa_rec = $wpsc_epa_return_items->insert($item_data);
                }
            } else { 
                // always an array now, this is not used.
                $item_data = [
                    'box_id' => $box_id,
                    'folderdoc_id' => -99999,
                    'return_id' => $return_insert_id
                ];
                if( $box_id != -99999 ) {
                    $wpsc_epa_rec = $wpsc_epa_return_items->insert($item_data);
                }
            }

			// always boxes now, no folderdoc decline. This is not used.
            if (is_array($folderdoc_id) && count($folderdoc_id) > 0) {
                foreach ($folderdoc_id as $folderdoc) {
                    $item_data = [
                        'box_id' => -99999,
                        'folderdoc_id' => $folderdoc,
                        'return_id' => $return_insert_id,
                    ];
                    $wpsc_epa_rec = $wpsc_epa_return_items->insert($item_data);
                }
            } else {
                $item_data = [
                    'box_id' => -99999,
                    'folderdoc_id' => $folderdoc_id,
                    'return_id' => $return_insert_id,
                ];
                if( $folderdoc_id != -99999 ) {
                    $wpsc_epa_rec = $wpsc_epa_return_items->insert($item_data);
                }
                
            }


            return $return_insert_id;
        }

        /**
         * Get Box details by BOX/FOLDER ID !!
         * @return Id, Title, Record Schedule, Programe Office !!
         */
        public static function get_box_file_details_by_id( $search_id ){            
            global $wpdb; 
            $box_details = [];
            $args = [
                'select' => "box_id, {$wpdb->prefix}wpsc_epa_boxinfo.id as Box_id_FK, program_office_id as box_prog_office_code, box_destroyed, box_status, storage_location_id,
                {$wpdb->prefix}wpsc_epa_program_office.id as Program_Office_id_FK, 
                {$wpdb->prefix}wpsc_epa_program_office.office_acronym,
                {$wpdb->prefix}wpsc_epa_program_office.office_name,
                {$wpdb->prefix}epa_record_schedule.id as Record_Schedule_id_FK,
                {$wpdb->prefix}epa_record_schedule.Schedule_Item_Number,
                {$wpdb->prefix}epa_record_schedule.Schedule_Title",
                'join'   => [
                    [
                        'type' => 'INNER JOIN', 
                        'table' => "{$wpdb->prefix}wpsc_epa_program_office", 
                        'foreign_key'  => 'program_office_id',
                        'compare' => '=',
                        'key' => 'office_code'
                    ],
                    [
                        'type' => 'INNER JOIN', 
                        'table' => "{$wpdb->prefix}epa_record_schedule", 
                        'foreign_key'  => 'record_schedule_id',
                        'compare' => '=',
                        'key' => 'id'
                    ]
                ],
                'where' => ['box_id', "'{$search_id}'"],
            ];
            $wpsc_epa_box = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo");
            $box_details = $wpsc_epa_box->get_row($args, false);            // Uncomment
            
            //
            // If result set is empty, search for file/folder
            //
//             if( !is_object($box_details) && count($box_details) < 1 ){
            if( !is_object($box_details) ){
				
				
/*				// Before dropping wpsc_epa_folderdocinfo table
				$args = [
                    'select' => "{$wpdb->prefix}wpsc_epa_folderdocinfo.box_id as Box_id_FK, 
                    {$wpdb->prefix}wpsc_epa_folderdocinfo.id as Folderdoc_Info_id_FK,
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.folderdocinfofile_id as Folderdoc_Info_id,
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.freeze,
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.unauthorized_destruction, 
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.title,  
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.id as Folderdoc_Info_Files_id_FK,  
                    {$wpdb->prefix}wpsc_epa_boxinfo.program_office_id,  
                    box_destroyed,
                    box_status,
                    {$wpdb->prefix}wpsc_epa_boxinfo.box_id,
                    {$wpdb->prefix}wpsc_epa_program_office.id as Program_Office_id_FK, 
                    {$wpdb->prefix}wpsc_epa_program_office.office_acronym,
                    {$wpdb->prefix}wpsc_epa_program_office.office_name,
                    {$wpdb->prefix}epa_record_schedule.id as Record_Schedule_id_FK,
                    {$wpdb->prefix}epa_record_schedule.Schedule_Item_Number,
                    {$wpdb->prefix}epa_record_schedule.Schedule_Title,
                    {$wpdb->prefix}wpsc_epa_boxinfo.record_schedule_id",
                    'join'   => [
                        [
                            'type' => 'INNER JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_folderdocinfo", 
                            'foreign_key'  => 'folderdocinfo_id',
                            'compare' => '=',
                            'key' => 'id'
                        ],
                        [
                            'base_table' => "{$wpdb->prefix}wpsc_epa_folderdocinfo",
                            'type' => 'INNER JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_boxinfo", 
                            'foreign_key'  => 'box_id',
                            'compare' => '=',
                            'key' => 'id'
                        ],
                        [
                            'base_table' => "{$wpdb->prefix}wpsc_epa_boxinfo",
                            'type' => 'INNER JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_program_office", 
                            'foreign_key'  => 'program_office_id',
                            'compare' => '=',
                            'key' => 'office_code'
                        ],
                        [
                            'base_table' => "{$wpdb->prefix}wpsc_epa_boxinfo",
                            'type' => 'INNER JOIN', 
                            'table' => "{$wpdb->prefix}epa_record_schedule", 
                            'foreign_key'  => 'record_schedule_id',
                            'compare' => '=',
                            'key' => 'id'
                        ]
                        
                    ],
//                     'where' => ['folderdocinfo_id', "'{$search_id}'"],
						'where' => ["{$wpdb->prefix}wpsc_epa_folderdocinfo_files.folderdocinfofile_id", "'{$search_id}'"],
                ];
*/

				
				$args = [
                    'select' => "{$wpdb->prefix}wpsc_epa_folderdocinfo_files.box_id as Box_id_FK, 
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.folderdocinfofile_id as Folderdoc_Info_id,
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.freeze,
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.unauthorized_destruction, 
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.title,  
                    {$wpdb->prefix}wpsc_epa_folderdocinfo_files.id as Folderdoc_Info_Files_id_FK,  
                    {$wpdb->prefix}wpsc_epa_boxinfo.program_office_id,  
                    box_destroyed,
                    box_status,
                    {$wpdb->prefix}wpsc_epa_boxinfo.box_id,
                    {$wpdb->prefix}wpsc_epa_program_office.id as Program_Office_id_FK, 
                    {$wpdb->prefix}wpsc_epa_program_office.office_acronym,
                    {$wpdb->prefix}wpsc_epa_program_office.office_name,
                    {$wpdb->prefix}epa_record_schedule.id as Record_Schedule_id_FK,
                    {$wpdb->prefix}epa_record_schedule.Schedule_Item_Number,
                    {$wpdb->prefix}epa_record_schedule.Schedule_Title,
                    {$wpdb->prefix}wpsc_epa_boxinfo.record_schedule_id",
                    'join'   => [
                        [
                            'type' => 'INNER JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_boxinfo", 
                            'foreign_key'  => 'box_id',
                            'compare' => '=',
                            'key' => 'id'
                        ],
                        [
                            'base_table' => "{$wpdb->prefix}wpsc_epa_boxinfo",
                            'type' => 'INNER JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_program_office", 
                            'foreign_key'  => 'program_office_id',
                            'compare' => '=',
                            'key' => 'office_code'
                        ],
                        [
                            'base_table' => "{$wpdb->prefix}wpsc_epa_boxinfo",
                            'type' => 'INNER JOIN', 
                            'table' => "{$wpdb->prefix}epa_record_schedule", 
                            'foreign_key'  => 'record_schedule_id',
                            'compare' => '=',
                            'key' => 'id'
                        ]
                        
                    ],
//                     'where' => ['folderdocinfo_id', "'{$search_id}'"],
						'where' => ["{$wpdb->prefix}wpsc_epa_folderdocinfo_files.folderdocinfofile_id", "'{$search_id}'"],
                ];

                $wpsc_epa_box = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_folderdocinfo_files");
                $box_details = $wpsc_epa_box->get_row($args, false);
                if($box_details) {
                    $box_details->type = 'Folder/Doc';
                }
                // $box_details->type = ($box_details->index_level == '02') ? 'File' : 'Folder';
            } else {
                if($box_details) {
                    $box_details->type = 'Box';
                }
                $box_details->title = '';
            }
            return $box_details;
        }


        /**
         * Update recall user data by recall id
         * Accepted recall_id format: 12, 13. No R-, no leading zeros. 
         * user_id expected to be wp_user id
         * @return Id
         */
        public static function update_recall_user_by_id( $data ){

            if(!isset($data['recall_id']) || !isset($data['user_id'])) {
                return false;
            }

            global $wpdb;

            $args = [
                'select' => 'id',
                'where' => ['recallrequest_id', $data['recall_id']]
            ];

            $wpsc_recall_users = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_recallrequest_users");
            $wpsc_recall_user_data = $wpsc_recall_users->get_results($args);
            if(count($wpsc_recall_user_data) > 0){
                foreach($wpsc_recall_user_data as $row_id){
                     $wpsc_recall_users->delete($row_id->id);
                }
            }

            if(is_array($data['user_id']) && count($data['user_id']) > 0){
                foreach($data['user_id'] as $user_id){
                    $data_req = [
                        'recallrequest_id' => $data['recall_id'],
                        'user_id'   => $user_id
                    ];
                   $insert_id = $wpsc_recall_users->insert($data_req); 
                }
            } else {
                $data_req = [
                    'recallrequest_id' => $data['recall_id'],
                    'user_id'   => $data['user_id']
                ];
               $insert_id =  $wpsc_recall_users->insert($data_req); 
            }
            return true;
        }

        /**
         * Insert recall data
         * @return Id
         */
        public static function insert_recall_data( $data ){            
            global $wpdb;   
            $user_id = $data['user_id'];
            unset($data['user_id']);

            // DEFAULT ID
            $data['recall_id'] = '000000';

            $wpsc_recall_method = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_recallrequest");
            $recall_insert_id = $wpsc_recall_method->insert($data);

            // Update the recall ID with insert ID
            $num = $recall_insert_id;
            $str_length = 7;
            $update_data['recall_id'] = substr("000000{$num}", -$str_length);
            $recall_updated = $wpsc_recall_method->update($update_data, ['id' => $recall_insert_id]);

            // Add data to recall_users 
            $wpsc_epa_box_user = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_recallrequest_users");        
            if(is_array($user_id) && count($user_id) > 0){
                foreach($user_id as $user){
                    $user_data = [
                        'user_id' => $user,
                        'recallrequest_id' => $recall_insert_id
                    ];
                    $box_details = $wpsc_epa_box_user->insert($user_data);
                }
            } else {
                $user_data = [
                    'user_id' => $user_id,
                    'recallrequest_id' => $recall_insert_id
                ];
                $box_details = $wpsc_epa_box_user->insert($user_data);
            }
            
            // Add row to Shipping Table 
            $current_date = date("Y-m-d");
            $shipping_data = [
				'ticket_id' => -99999,
				'company_name' => '',
				'tracking_number' => '',
				'status' => '',
				'shipped' => 0,
				'delivered' => 0,
				'recallrequest_id' => $recall_insert_id, 
				'return_id' => -99999,
				'date_added' => $current_date
			];
            
            $wpsc_shipping_method = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_shipping_tracking");
            $shipping_recall_insert_id = $wpsc_shipping_method->insert($shipping_data);
            
            $update_data['shipping_tracking_id'] = $shipping_recall_insert_id;
            $shipping_recall_updated = $wpsc_recall_method->update($update_data, ['id' => $recall_insert_id]);
            
            // Return recall_id
            $num = $box_details;
            $str_length = 7;
            //$recall_id = substr("000000{$num}", -$str_length);
            $recall_id = substr("000000{$recall_insert_id}", -$str_length);

            //return $box_details;           
            return $recall_id;
        }

        /**
         * Get shipping data
         * @return Id
         */
        public static function get_shipping_data_by_recall_id( $where ){           
            global $wpdb;   
            if(is_array($where) && count($where) > 0){
                foreach($where as $key => $whr) {
                    if($key == 'recallrequest_id' && is_array($whr) && count($whr) > 0) {
                        $args['where'][] = [
                            "{$wpdb->prefix}wpsc_epa_shipping_tracking.recallrequest_id", 
                            '("' . implode('", "', $whr) . '")',
                            "AND",
                            ' IN '
                        ];
                    } else {
                        $args['where'][] = ["{$wpdb->prefix}wpsc_epa_shipping_tracking.$key", "'{$whr}'"];
                    }
                }
            }

            $args['select'] = 'id, recallrequest_id, company_name, tracking_number, status';
            $shipping_data = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_shipping_tracking");
            $shipping_records = $shipping_data->get_results($args);
            return $shipping_records;
        }

        
        /**
         * Insert shipping data
         * @return Id
         */
        /*
        public static function add_shipping_data( $data ){            
            global $wpdb;   

            $add_shipping_data = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_shipping_tracking");
            $shipping_insert_id = $add_shipping_data->insert($data);
            return $shipping_insert_id;
        }
        */

        /**
         * Get recall data
         * @return Id
         */
        public static function get_recall_data( $where ){
            global $wpdb;   

            if(is_array($where) && count($where) > 0){
                foreach($where as $key => $whr) {
                    if($key == 'custom') {
                       $args['where']['custom'] = $whr;
                    } elseif($key == 'filter') {
                        
                        $orderby = isset($whr['orderby']) ? $whr['orderby'] : 'id';
                        $order = isset($whr['order']) ? $whr['order'] : 'DESC';
                        if($orderby == 'status') {
                            $orderby = "{$wpdb->prefix}terms.name";
                        }
                        $args['order'] = [$orderby, $order];
                        if(isset($whr['records_per_page']) && $whr['records_per_page'] > 0){  
                            $number_of_records =  isset($whr['records_per_page']) ? $whr['records_per_page'] : 20;
                            $start = isset($whr['paged']) ? $whr['paged'] : 0;
                            $args['limit'] = [$start, $number_of_records];        
                        }
                    } elseif($key == 'program_office_id') {
                        // $storage_location_id = self::get_storage_location_id_by_dc($whr);
                        // if(is_array($storage_location_id) && count($storage_location_id) > 0) {
                        //     foreach($storage_location_id as $val){
                        //         $dc_ids[] = $val->id;
                        //     }
                            $args['where'][] = [
                                "{$wpdb->prefix}wpsc_epa_program_office.office_acronym", 
                                $whr
                            ];
                        // }
                    } elseif($key == 'digitization_center') {
                        $storage_location_id = self::get_storage_location_id_by_dc($whr);
                        if(is_array($storage_location_id) && count($storage_location_id) > 0) {
                            foreach($storage_location_id as $val){
                                if($val->id) {
                                    $dc_ids[] = $val->id;
                                }
                            }
                            $dc_ids = implode(', ', $dc_ids);
                            $args['where'][] = [
                                "{$wpdb->prefix}wpsc_epa_boxinfo.storage_location_id", 
                                "($dc_ids)",
                                "AND",
                                ' IN '
                            ];
                        }
                    } elseif($key == 'id' && is_array($whr) && count($whr) > 0) {
                        $args['where'][] = [
                            "{$wpdb->prefix}wpsc_epa_recallrequest.id", 
                            "(".implode(',', $whr).")",
                            "AND",
                            ' IN '
                        ];
                    } elseif($key == 'recall_id' && is_array($whr) && count($whr) > 0) {
                        $args['where'][] = [
                            "{$wpdb->prefix}wpsc_epa_recallrequest.recall_id", 
                            '("' . implode('", "', $whr) . '")',
                            "AND",
                            ' IN '
                        ];
                    } else {
                        $args['where'][] = ["{$wpdb->prefix}wpsc_epa_recallrequest.$key", "'{$whr}'"];
                    }
                }   
            }

               $args['where'][] = [
                                "{$wpdb->prefix}wpsc_epa_recallrequest.recall_id", 
                                "0",
                                "AND",
                                ' > '
                            ];

                            // print_r($args['where']);
            // $args['where']['custom'] =  isset($args['where']['custom']) ? $args['where']['custom'] . " AND {$wpdb->prefix}wpsc_epa_recallrequest.recall_id > 0" : " {$wpdb->prefix}wpsc_epa_recallrequest.recall_id > 0";


            // $args['where'][] = ["{$wpdb->prefix}wpsc_epa_recallrequest.recall_id", 0, 'AND', '>'];


            // print_r($where);  
            // print_r($args);  

            $select_fields = [
                "{$wpdb->prefix}wpsc_epa_recallrequest" => ['id', 'recall_id', 'box_id as recall_box_id', 'expiration_date','request_date', 'request_receipt_date', 'return_date', 'updated_date', 'comments', 'recall_status_id', 'saved_box_status', 'recall_approved', 'recall_complete'],
                "{$wpdb->prefix}wpsc_epa_boxinfo" => ['ticket_id', 'box_id', 'storage_location_id', 'location_status_id', 'box_destroyed', 'date_created', 'date_updated'],
//                 "{$wpdb->prefix}wpsc_epa_folderdocinfo" => ['title', 'folderdocinfo_id as folderdoc_id'],
            //    "{$wpdb->prefix}wpsc_epa_folderdocinfo" => ['folderdocinfo_id as folderdoc_id_parent'], // New dropped table
//                 "{$wpdb->prefix}wpsc_epa_folderdocinfo_files" => ['title', 'folderdocinfo_id as folderdoc_id'],
                "{$wpdb->prefix}wpsc_epa_folderdocinfo_files" => ['title', 'folderdocinfofile_id as folderdoc_id'],
                "{$wpdb->prefix}wpsc_epa_program_office" => ['office_acronym'],
                "{$wpdb->prefix}wpsc_epa_shipping_tracking" => ['company_name as shipping_carrier', 'tracking_number', 'status', 'shipped', 'delivered'],
                "{$wpdb->prefix}epa_record_schedule" => ['Record_Schedule', 'Schedule_Item_Number', 'Schedule_Title'],
                "{$wpdb->prefix}terms" => ['name as recall_status'],
                "{$wpdb->prefix}wpsc_epa_recallrequest_users" => ['user_id'],
            ];

            foreach($select_fields as $key => $fields_array){
                foreach($fields_array as $field) {
                    if($key == "{$wpdb->prefix}wpsc_epa_recallrequest_users"){
                        $select[] = "GROUP_CONCAT($key.user_id) as $field";
                    } elseif($key == "{$wpdb->prefix}epa_record_schedule"){
                        $select[] = "CONCAT($key.Schedule_Item_Number, ': ' , $key.Schedule_Title) as $field";
                    } else {
                        $select[] = $key . '.' . $field;
                    }
                }
            }

            $args['groupby']  = "{$wpdb->prefix}wpsc_epa_recallrequest.recall_id";
            $args['select']  = implode(', ', $select);
            $args['join']  = [
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_boxinfo", 
                            'foreign_key'  => 'box_id',
                            'compare' => '=',
                            'key' => 'id'
                            ],
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_recallrequest_users", 
                            'foreign_key'  => 'id',
                            'compare' => '=',
                            'key' => 'recallrequest_id'
                        ],
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_folderdocinfo_files", 
                            'key'  => 'id',
                            'compare' => '=',
                            'foreign_key' => 'folderdoc_id'
                        ],
/*
                        [
                            'base_table' => "{$wpdb->prefix}wpsc_epa_folderdocinfo_files",
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_folderdocinfo", 
                            'key'  => 'id',
                            'compare' => '=',
                            'foreign_key' => 'folderdocinfo_id'
                        ],
*/
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_program_office", 
                            'key'  => 'id',
                            'compare' => '=',
                            'foreign_key' => 'program_office_id'
                        ],
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_shipping_tracking", 
                            'key'  => 'id',
                            'compare' => '=',
                            'foreign_key' => 'shipping_tracking_id'
                        ],
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}epa_record_schedule", 
                            'key'  => 'id',
                            'compare' => '=',
                            'foreign_key' => 'record_schedule_id'
                        ],
                        [
                            'type' => 'LEFT JOIN', 
                            'table' => "{$wpdb->prefix}terms", 
                            'key'  => 'term_id',
                            'compare' => '=',
                            'foreign_key' => 'recall_status_id'
                        ]
                    ];

            $wpsc_epa_box = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_recallrequest");
            $box_details = $wpsc_epa_box->get_results($args);

            if(count($box_details) > 0 ){
                foreach($box_details as $key => $record){
                    if(!empty($record->user_id)) {
                        $record->user_id = explode(',', $record->user_id );
                    }
                }
            }
            
            // if(count($box_details) > 0 ){
            //     $record_id = 0;
            //     $count = 0;
            //     foreach($box_details as $key => $record){
            //         if($record->id == $record_id) {
            //             unset($box_details[$key-1]);
            //             if(is_array($user_id)) {
            //                 $user_id[] = $record->user_id;
            //                 $box_details[$key]->user_id = $user_id;
            //             } else {
            //                 $box_details[$key]->user_id = [$user_id, $record->user_id];
            //             }
            //         }
            //         $record_id = $record->id;
            //         $user_id = $record->user_id;
            //         $count++;
            //     }
            // }
            return $box_details;
        }

        /**
         * Change shipping number
         * @return recall data
         */
        public static function get_storage_location_id_by_dc( $location ){            
            global $wpdb;  
            $args['select'] = "{$wpdb->prefix}wpsc_epa_storage_location.id" ;
            $args['join']  = [
                        [
                            'type' => 'INNER JOIN', 
                            'table' => "{$wpdb->prefix}wpsc_epa_storage_location", 
                            'key'  => 'digitization_center',
                            'compare' => '=',
                            'foreign_key' => 'term_id'
                        ]
            ];
            $args['where'] = [
                ["{$wpdb->prefix}terms.name", "$location"]
            ];
            $recall_req = new WP_CUST_QUERY("{$wpdb->prefix}terms");
            $storage_location_id = $recall_req->get_results( $args );
            return $storage_location_id;
        }

        /**
         * Change shipping number
         * @return recall data
         */
        public static function update_recall_shipping( $data, $where ){            
            global $wpdb;  
            
            // Get id from recall_id
            if(is_array($where) && count($where) > 0){
                foreach($where as $key => $whr) {
                    if($key == 'recall_id' && !empty($whr)) {
                        $args['where'][] = ["{$wpdb->prefix}wpsc_epa_recallrequest.$key", "'{$whr}'"];
                        $args['select'] = 'id';
                        $recall_data = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_recallrequest");
                        $recall_pk_id = $recall_data->get_row($args);

						$where2 = [ 'recallrequest_id' => $recall_pk_id->id ];
                    } 
                }

            }


            $recall_req = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_shipping_tracking");
            $recall_res = $recall_req->update( $data, $where2 );
            $get_recall_data = self::get_recall_data( ['id' => $where2['recallrequest_id']] );
            
            return $get_recall_data;
        }
        
        /**
         * Change shipping number for Returns
         * @return Return data
         */
        public static function update_return_shipping( $data, $where ){            
            global $wpdb;  
            
            // Get id from return_id
            if(is_array($where) && count($where) > 0){
                foreach($where as $key => $whr) {
                    if($key == 'return_id' && !empty($whr)) {
                        $args['where'][] = ["{$wpdb->prefix}wpsc_epa_return.$key", "'{$whr}'"];
                        $args['select'] = 'id';
                        $return_data = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return");
                        $return_pk_id = $return_data->get_row($args);

						$where2 = [ 'return_id' => $return_pk_id->id ];
                    } 
                }

            }


            $return_req = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_shipping_tracking");
            $return_res = $return_req->update( $data, $where2 );
            $get_return_data = self::get_return_data( ['id' => $where2['return_id']] );
            
            return $get_return_data;
        }


        
        /**
         * Delete shipping record by recall IDs
         * @return recall data
         */
        /*
        public static function delete_shipping_data_by_recall_id( $where ){            
            global $wpdb;  
            
            $args['select'] = 'id';
            if (is_array($where) && count($where) > 0) {
                foreach ($where as $key => $whr) {
                    if ($key == 'recallrequest_id' && is_array($whr) && count($whr) > 0) {
                        $args['where'][] = [
                            "{$wpdb->prefix}wpsc_epa_shipping_tracking.recallrequest_id",
                            '("' . implode('", "', $whr) . '")',
                            "AND",
                            ' IN ',
                        ];
                    } else {
                        $args['where'][] = ["{$wpdb->prefix}wpsc_epa_shipping_tracking.$key", "'{$whr}'"];
                    }
                }

                $shipping_data = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_shipping_tracking");
                $shipping_records = $shipping_data->get_results($args);
                foreach($shipping_records as $record){
                    $shipping_data->delete($record->id);
                }
            }
            return $where;

        }
        */
        
        /**
         * Change request date
         * @return recall data
         */
        public static function update_recall_dates( $data, $where ){            
            global $wpdb;  
            $recall_req = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_recallrequest");
            $recall_res = $recall_req->update( $data, $where );
            $get_recall_data = self::get_recall_data( $where );
            return $get_recall_data;
        }
        
        /**
         * Change request date
         * @return return data
         */
        public static function update_return_dates( $data, $where ){            
            global $wpdb;  
            $return_req = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return");
            $return_res = $return_req->update( $data, $where );
            $get_return_data = self::get_return_data( $where );
            return $get_return_data;
        }
		
		/**
         * Change request table data
         * @return recall data
         */
        public static function update_recall_data( $data, $where ){            
            global $wpdb;  
            $recall_req = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_recallrequest");
            $recall_res = $recall_req->update( $data, $where );
            $get_recall_data = self::get_recall_data( $where );
            return $get_recall_data;
        }
        
        /**
         * Change request table data
         * @return decline (return) data
         */
        public static function update_return_data( $data, $where ){            
            global $wpdb;  
            $return_req = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return");
            $return_res = $return_req->update( $data, $where );
            $get_return_data = self::get_return_data( $where );
            return $get_return_data;
        }

        
        /**
         * Get primary id by retun id
         * @return Id
         */
        public static function get_primary_id_by_retunid( $where ){           
            global $wpdb;   
            if(is_array($where) && count($where) > 0){
                foreach($where as $key => $whr) {
                    if($key == 'return_id' && !empty($whr)) {
                       $args['where'][] = ["{$wpdb->prefix}wpsc_epa_return.$key", "'{$whr}'"];
                        $args['select'] = 'id';
                        $retun_data = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_return");
                        $retun_data_records = $retun_data->get_row($args);
                    } 
                }

            }
            return $retun_data_records;
        }     

        /**
         * Get ticket id by retun id or recall_id ** NOT VALID ** ** NOT VALID ** ** NOT VALID ** 
         * @return Id
         * This function is attempting to grab the ticket_id from the shipping tracking table, 
         * but any recall or return (decline) in this table will have a ticket_id of -99999
         * 
         */
        public static function get_ticket_id_by( $where ){           
            global $wpdb;   
            if(is_array($where) && count($where) > 0){
                foreach($where as $key => $whr) {
                    if(($key == 'return_id' || $key == 'recallrequest_id') && !empty($whr)) {
                        $args['where'][] = ["{$wpdb->prefix}wpsc_epa_shipping_tracking.$key", "'{$whr}'"];
                        $args['select'] = 'ticket_id';
                        $shipping_data = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_shipping_tracking");
                        $ticket_id = $shipping_data->get_results($args);
                    } 
                }
            }
            return $ticket_id;
        }
        
        /**
         * Get ticket id (without leading zeros) by box id or folder/file id
         * @return Id
         */
        public static function get_ticket_id_from_box_folder_file( $where ){              
            
            $id = $where['box_folder_file_id'];
            
			if( substr_count($id, '-') == 1 ) {
				$type = 'Box';
				$arr = explode("-", $id, 2);
				$ticket_id = (int)$arr[0];
			} elseif( substr_count($id, '-') == 3 ) {
				$type = 'Folder/File';
				$arr = explode("-", $id, 2);
				$ticket_id = (int)$arr[0];
			} else {
				$type = 'Error';
				$ticket_id = null;
			}
			
			$return = [
				'type' => $type,
				'ticket_id' => $ticket_id,
				'item_id' => $id
			];
			
            return $return;
        }
        
        // Get the number of accessions associated to a request for validation purposes
        public static function get_accession_count( $ticket_id ) {
	        global $wpdb;

	        $get_count = $wpdb->get_row("SELECT COUNT(DISTINCT record_schedule_id) as count FROM {$wpdb->prefix}wpsc_epa_boxinfo WHERE ticket_id = ".$ticket_id);
	        
	        return $get_count->count;
        }
        
        // Get ticket status term id from non-zero'd ticket id. 
        public static function get_ticket_status( $ticket_id ) {
	        global $wpdb;
	        $get_request_status = $wpdb->get_row("SELECT ticket_status FROM {$wpdb->prefix}wpsc_ticket WHERE id = ".$ticket_id);

	        return $get_request_status->ticket_status;
        }
        
        public static function get_box_status( $box_id ) {
            global $wpdb;
	        $get_box_status = $wpdb->get_row("SELECT b.name
	        FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
	        INNER JOIN " . $wpdb->prefix . "terms b ON b.term_id = a.box_status
	        WHERE id = " . $box_id);
            $box_status = $get_box_status->name;
	        
	        return $box_status;
        }
        
        // Get ticket owner's customer_name (wp display name) id from non-zero'd ticket id. 
        public static function get_ticket_owner_agent_id( $where ) {
	        global $wpdb;
	        $id = (int)$where['ticket_id'];
	        $the_row = $wpdb->get_row("SELECT customer_email FROM {$wpdb->prefix}wpsc_ticket WHERE id = ".$id);
	        
	        //$the_user_row = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}users WHERE user_email = " . $the_row->customer_email );
	        $the_user_row = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}users WHERE user_email = '" . $the_row->customer_email . "'" );
	        
	        $user_obj = get_user_by( 'email', $the_row->customer_email );
	        
	        //$the_user_row->id;
	        $user_id_array = array($user_obj->ID);
	        $user_agent_id = self::translate_user_id( $user_id_array, 'agent_term_id');
	        
	        return $user_agent_id;
/*
	        $data = [
		        'id' => $id,
		        'customer_email' => $the_row->customer_email,
		        'user_id' => $user_obj->ID,
		        'user_array' => $user_id_array,
		        'agent_id' => $user_agent_id
	        ];
	        return $data;
*/
        }
        

        public static function calc_max_gap_val($dc_final){

        global $wpdb; 
        $find_sequence = $wpdb->get_row("
        WITH 
        cte1 AS
        (
        SELECT id, 
            CASE WHEN     occupied  = LAG(occupied) OVER (ORDER BY id)
                        AND remaining = LAG(remaining) OVER (ORDER BY id)
                    THEN 0
                    ELSE 1 
                    END values_differs
        FROM ".$wpdb->prefix."wpsc_epa_storage_status
        WHERE digitization_center = '" . $dc_final . "'
        ),
        cte2 AS 
        (
        SELECT id,
            SUM(values_differs) OVER (ORDER BY id) group_num
        FROM cte1
        ORDER BY id
        )
        SELECT MIN(id) as id
        FROM cte2
        GROUP BY group_num
        ORDER BY COUNT(*) DESC LIMIT 1;
        ");

        $sequence_shelfid = $find_sequence->id;

        $seq_shelfid_final = $sequence_shelfid-1;
                    
        // Determine largest Gap of consecutive shelf space
        $find_gaps = $wpdb->get_row("
        WITH 
        cte1 AS
        (
        SELECT shelf_id, remaining, SUM(remaining = 0) OVER (ORDER BY id) group_num
        FROM ".$wpdb->prefix."wpsc_epa_storage_status
        WHERE digitization_center = '" . $dc_final . "' AND
        id BETWEEN 1 AND '" . $seq_shelfid_final . "'
        )
        SELECT GROUP_CONCAT(shelf_id) as shelf_id,
            GROUP_CONCAT(remaining) as remaining,
            SUM(remaining) as total
        FROM cte1
        WHERE remaining != 0
        GROUP BY group_num
        ORDER BY total DESC
        LIMIT 1
        ");

        $max_gap_value = $find_gaps->total;
        return $max_gap_value;
    }

    //Return flag for any form
    public static function get_associated_document( $ticket_id, $form_type ) {
        global $wpdb;
        
        $get_form = $wpdb->get_row("SELECT " . $form_type . "
        FROM " . $wpdb->prefix . "wpsc_ticket
        WHERE id = " . $ticket_id);
        $form = $get_form->$form_type;
        
        return $form;
    }

    public static function get_unassigned_boxes($tkid,$dc_final){

        global $wpdb; 
        
        $box_id_array = array();
        
        $obtain_box_ids_details = $wpdb->get_results("
        SELECT a.storage_location_id
        FROM ".$wpdb->prefix."wpsc_epa_boxinfo a
        INNER JOIN ".$wpdb->prefix."wpsc_epa_storage_location b ON a.storage_location_id = b.id 
        WHERE
        (b.aisle = 0 OR 
        b.bay = 0 OR 
        b.shelf = 0 OR 
        b.position = 0) AND
        b.digitization_center = '" . $dc_final . "' AND
        a.ticket_id = '" . $tkid . "'
        ");
        
        foreach ($obtain_box_ids_details as $box_id_val) {
        $box_id_array_val = $box_id_val->storage_location_id;
        array_push($box_id_array, $box_id_array_val);
        }
        return $box_id_array;
    }

        public static function get_default_digitization_center($id)
        {
            global $wpdb, $wpscfunction;


            $sems_check = $wpscfunction->get_ticket_meta($id,'super_fund');
                
            if(in_array("true", $sems_check)) {
                
                
            // Get Distinct program office ID
            $get_program_office_id = $wpdb->get_results("
	            SELECT ".$wpdb->prefix."wpsc_epa_program_office.organization_acronym as acronym
	            FROM ".$wpdb->prefix."wpsc_epa_boxinfo 
	            LEFT JOIN ".$wpdb->prefix."wpsc_epa_program_office ON ".$wpdb->prefix."wpsc_epa_boxinfo.program_office_id = ".$wpdb->prefix."wpsc_epa_program_office.office_code 
	            WHERE ".$wpdb->prefix."wpsc_epa_boxinfo.ticket_id = '" . $id . "'
	            ");

            $program_office_east_array = array();
            $program_office_west_array = array();
            
            $count = 0;
            
            foreach ($get_program_office_id as $program_office_id_val) {
                $count++;
	            $program_office_val = $program_office_id_val->acronym;
	
	            $east_region = array("R03");
	            $west_region = array("AO", "OITA", "OCFO", "OCSPP", "ORD", "OAR", "OW", "OIG", "OGC", "OMS", "OLEM", "OECA", "R01", "R02", "R04", "R05", "R06", "R07", "R08", "R09", "R10");
	
	            if (in_array($program_office_val, $east_region))
	            {
	            	array_push($program_office_east_array, $program_office_val);
	            }
	
	            if (in_array($program_office_val, $west_region))
	            {
	            	array_push($program_office_west_array, $program_office_val);
	            }
            }

            $east_count = count($program_office_east_array);
            $west_count = count($program_office_west_array);

            $set_center = '';

            if ($east_count == $count) {
                $digi_east_term_id = self::get_term_by_slug( 'e' );	 // 62
//             	$set_center = 62;
				$set_center = $digi_east_term_id;
            }
            
            if ($east_count > $west_count)
            {
            	$digi_east_term_id = self::get_term_by_slug( 'e' );	 // 62
//             	$set_center = 62;
				$set_center = $digi_east_term_id;
            }

            if ($west_count > $east_count)
            {
            	$digi_west_term_id = self::get_term_by_slug( 'w' );	 // 2
//             	$set_center = 2;
				$set_center = $digi_west_term_id;
            }

            if ($west_count == $east_count)
            {
            	$digi_not_assigned_term_id = self::get_term_by_slug( 'not-assigned-digi-center' );	 // 666
//             	$set_center = 666;
				$set_center = $digi_not_assigned_term_id;
            }
            
            
            } else {
                
            // Get Distinct program office ID
            $get_program_office_id = $wpdb->get_results("
	            SELECT ".$wpdb->prefix."wpsc_epa_program_office.organization_acronym as acronym
	            FROM ".$wpdb->prefix."wpsc_epa_boxinfo 
	            LEFT JOIN ".$wpdb->prefix."wpsc_epa_program_office ON ".$wpdb->prefix."wpsc_epa_boxinfo.program_office_id = ".$wpdb->prefix."wpsc_epa_program_office.office_code 
	            WHERE ".$wpdb->prefix."wpsc_epa_boxinfo.ticket_id = '" . $id . "'
	            ");

            $program_office_east_array = array();
            $program_office_west_array = array();

            foreach ($get_program_office_id as $program_office_id_val) {
	            $program_office_val = $program_office_id_val->acronym;
	
	            $east_region = array("R01", "R02", "R03", "AO", "OITA", "OCFO", "OCSPP", "ORD", "OAR", "OW", "OIG", "OGC", "OMS", "OLEM", "OECA", "OEJECR");
	            $west_region = array("R04", "R05", "R06", "R07", "R08", "R09", "R10");
	
	            if (in_array($program_office_val, $east_region))
	            {
	            	array_push($program_office_east_array, $program_office_val);
	            }
	
	            if (in_array($program_office_val, $west_region))
	            {
	            	array_push($program_office_west_array, $program_office_val);
	            }
            }

            $east_count = count($program_office_east_array);
            $west_count = count($program_office_west_array);

            $set_center = '';

            if ($east_count > $west_count)
            {
            	$digi_east_term_id = self::get_term_by_slug( 'e' );	 // 62
//             	$set_center = 62;
				$set_center = $digi_east_term_id;
            }

            if ($west_count > $east_count)
            {
            	$digi_west_term_id = self::get_term_by_slug( 'w' );	 // 2
//             	$set_center = 2;
				$set_center = $digi_west_term_id;
            }

            if ($west_count == $east_count)
            {
            	$digi_not_assigned_term_id = self::get_term_by_slug( 'not-assigned-digi-center' );	 // 666
//             	$set_center = 666;
				$set_center = $digi_not_assigned_term_id;
            }

            }
            
            return $set_center;

        }

        public static function get_default_digitization_center_override($id, $manual_dc)
        {
            global $wpdb, $wpscfunction;


            $sems_check = $wpscfunction->get_ticket_meta($id,'super_fund');
                
            if(in_array("true", $sems_check)) {
                
                
            // Get Distinct program office ID
            $get_program_office_id = $wpdb->get_results("
	            SELECT ".$wpdb->prefix."wpsc_epa_program_office.organization_acronym as acronym
	            FROM ".$wpdb->prefix."wpsc_epa_boxinfo 
	            LEFT JOIN ".$wpdb->prefix."wpsc_epa_program_office ON ".$wpdb->prefix."wpsc_epa_boxinfo.program_office_id = ".$wpdb->prefix."wpsc_epa_program_office.office_code 
	            WHERE ".$wpdb->prefix."wpsc_epa_boxinfo.ticket_id = '" . $id . "'
	            ");

            $program_office_east_array = array();
            $program_office_west_array = array();
            
            $count = 0;
            
            foreach ($get_program_office_id as $program_office_id_val) {
                $count++;
	            $program_office_val = $program_office_id_val->acronym;
	
	            $east_region = array("R03");
	            $west_region = array("AO", "OITA", "OCFO", "OCSPP", "ORD", "OAR", "OW", "OIG", "OGC", "OMS", "OLEM", "OECA", "R01", "R02", "R04", "R05", "R06", "R07", "R08", "R09", "R10");
	
	            if (in_array($program_office_val, $east_region))
	            {
	            	array_push($program_office_east_array, $program_office_val);
	            }
	
	            if (in_array($program_office_val, $west_region))
	            {
	            	array_push($program_office_west_array, $program_office_val);
	            }
            }

            $east_count = count($program_office_east_array);
            $west_count = count($program_office_west_array);

            $set_center = '';

            if($manual_dc != 0) {
                $set_center = $manual_dc;
            } else {

                if ($east_count == $count) {
                    $digi_east_term_id = self::get_term_by_slug( 'e' );	 // 62
    //             	$set_center = 62;
                    $set_center = $digi_east_term_id;
                }
                
                if ($east_count > $west_count)
                {
                    $digi_east_term_id = self::get_term_by_slug( 'e' );	 // 62
    //             	$set_center = 62;
                    $set_center = $digi_east_term_id;
                }

                if ($west_count > $east_count)
                {
                    $digi_west_term_id = self::get_term_by_slug( 'w' );	 // 2
    //             	$set_center = 2;
                    $set_center = $digi_west_term_id;
                }

                if ($west_count == $east_count)
                {
                    $digi_not_assigned_term_id = self::get_term_by_slug( 'not-assigned-digi-center' );	 // 666
    //             	$set_center = 666;
                    $set_center = $digi_not_assigned_term_id;
                }

            }
            
            
            
            } else {
                
            // Get Distinct program office ID
            $get_program_office_id = $wpdb->get_results("
	            SELECT ".$wpdb->prefix."wpsc_epa_program_office.organization_acronym as acronym
	            FROM ".$wpdb->prefix."wpsc_epa_boxinfo 
	            LEFT JOIN ".$wpdb->prefix."wpsc_epa_program_office ON ".$wpdb->prefix."wpsc_epa_boxinfo.program_office_id = ".$wpdb->prefix."wpsc_epa_program_office.office_code 
	            WHERE ".$wpdb->prefix."wpsc_epa_boxinfo.ticket_id = '" . $id . "'
	            ");

            $program_office_east_array = array();
            $program_office_west_array = array();

            foreach ($get_program_office_id as $program_office_id_val) {
	            $program_office_val = $program_office_id_val->acronym;
	
	            $east_region = array("R01", "R02", "R03", "AO", "OITA", "OCFO", "OCSPP", "ORD", "OAR", "OW", "OIG", "OGC", "OMS", "OLEM", "OECA", "OEJECR");
	            $west_region = array("R04", "R05", "R06", "R07", "R08", "R09", "R10");
	
	            if (in_array($program_office_val, $east_region))
	            {
	            	array_push($program_office_east_array, $program_office_val);
	            }
	
	            if (in_array($program_office_val, $west_region))
	            {
	            	array_push($program_office_west_array, $program_office_val);
	            }
            }

            $east_count = count($program_office_east_array);
            $west_count = count($program_office_west_array);

            $set_center = '';

            // if($manual_dc != 0) {
            // $set_center = 2;
            // }

            if($manual_dc != 0) {
                $set_center = $manual_dc;
            } else {

                if ($east_count > $west_count)
                {
                    $digi_east_term_id = self::get_term_by_slug( 'e' );	 // 62
    //             	$set_center = 62;
                    $set_center = $digi_east_term_id;
                }

                if ($west_count > $east_count)
                {
                    $digi_west_term_id = self::get_term_by_slug( 'w' );	 // 2
    //             	$set_center = 2;
                    $set_center = $digi_west_term_id;
                }

                if ($west_count == $east_count)
                {
                    $digi_not_assigned_term_id = self::get_term_by_slug( 'not-assigned-digi-center' );	 // 666
    //             	$set_center = 666;
                    $set_center = $digi_not_assigned_term_id;
                }

            }

            }
            
            return $set_center;

        }

        public static function fetch_request_id($id)
        {
            global $wpdb; 
            $args = [
                'where' => ['id', $id],
            ];
            $wpqa_wpsc_ticket = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_ticket");
            $box_details = $wpqa_wpsc_ticket->get_row($args, false);
            $asset_id = $box_details->request_id;
            return $asset_id;
        }

        //Function to obtain serial number (box ID) from database based on Request ID
        public static function fetch_box_id($id)
        {
            global $wpdb; 
            // die(print_r($wpdb->prefix));
            $array = array();
            $args = [
                'where' => ['ticket_id', $id],
            ];
            $wpqa_wpsc_epa_boxinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo");
            $box_result = $wpqa_wpsc_epa_boxinfo->get_results($args, false);

            foreach ($box_result as $box) {
                array_push($array, $box->box_id);
            }
            return $array;
        }
        
        //Function to obtain full list of Program Offices
        public static function fetch_program_office_array()
        {
            global $wpdb;
            
        $po_result = $wpdb->get_results("
        SELECT DISTINCT office_acronym FROM ".$wpdb->prefix."wpsc_epa_program_office
        WHERE id <> -99999
        ");

            $array = array();

            foreach ($po_result as $po) {
                array_push($array, $po->office_acronym);
            }
            return $array;
        }
        
        //gets list of record schedules for the box-details page
        //No longer have 10 year retention requirement
        public static function fetch_record_schedule_array()
        {
            global $wpdb;
            $array = array();
            
            $record_schedule = $wpdb->get_results("SELECT * 
            FROM ".$wpdb->prefix."epa_record_schedule 
            WHERE Reserved_Flag = 0 AND id <> '-99999'
            ORDER BY Schedule_Item_Number");
            
            foreach($record_schedule as $rs)
            {
                array_push($array, $rs->Schedule_Item_Number);
            }
            return $array;
        }
        
        //Convert box patt id to id
        public static function convert_box_id( $id )
        {
            global $wpdb;
            $id = '"'.$id.'"';
            $args = [
                'select' => 'id',
                'where' => ['box_id',  $id],
            ];
            $wpqa_wpsc_box = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo");
            $request_key = $wpqa_wpsc_box->get_row($args, false);

            $key = $request_key->id;
            return $key;
        }
        
        //Convert box db id to box patt id
        public static function convert_box_id_to_dbid ( $dbid ) {
            global $wpdb;
            
            $get_box_id = $wpdb->get_row("SELECT box_id 
            FROM ".$wpdb->prefix."wpsc_epa_boxinfo
            WHERE id = '" . $dbid . "'");
            $box_id = $get_box_id->box_id;
            
            return $box_id;
        }
        
        //Convert box patt id to patt request id
        public static function convert_box_request_id( $id )
        {
            global $wpdb;

            $request_id_get = $wpdb->get_row("SELECT ".$wpdb->prefix."wpsc_ticket.request_id as request_id FROM ".$wpdb->prefix."wpsc_epa_boxinfo, ".$wpdb->prefix."wpsc_ticket WHERE ".$wpdb->prefix."wpsc_epa_boxinfo.box_id = '" . $id . "' AND ".$wpdb->prefix."wpsc_epa_boxinfo.ticket_id = ".$wpdb->prefix."wpsc_ticket.id");
            
            $request_id_val = $request_id_get->request_id;
            
            return $request_id_val;
        }
        
        //Convert patt request id to id
        public static function convert_request_id( $id )
        {
            global $wpdb;
            $get_db_id = $wpdb->get_row("SELECT id
            FROM ".$wpdb->prefix."wpsc_ticket
            WHERE request_id = '" .  $id . "'");
            $dbid = $get_db_id->id;
            
            return $dbid;
        }
        
        //Convert id to patt request id
        public static function convert_request_db_id( $id )
        {
            global $wpdb;
            $get_db_id = $wpdb->get_row("SELECT request_id
            FROM ".$wpdb->prefix."wpsc_ticket
            WHERE id = '" .  $id . "'");
            $dbid = $get_db_id->request_id;
            
            return $dbid;
        }
        
        //Function to obtain box ID, title, date and contact 
        /*
        public static function fetch_box_content($id)
        {
            global $wpdb; 
            // die(print_r($wpdb->prefix));
            $array = array();
            $args = [
                'where' => ['box_id', $id],
            ];
            $wpqa_wpsc_epa_folderdocinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_folderdocinfo");
            $box_content = $wpqa_wpsc_epa_folderdocinfo->get_results($args, false);

            foreach ($box_content as $box) {
                $parent = new stdClass;
                $parent->id = $box->folderdocinfo_id;
                $parent->title = $box->title;
                $parent->date = $box->date;
                $parent->contact = $box->epa_contact_email;
                $parent->source_format = $box->source_format;
                $parent->validation = $box->validation;
                $parent->validation_user = $box->validation_user_id;
                $parent->destruction = $box->unauthorized_destruction;
                $array[] = $parent;

            }
            return $array;
        }
        */
        //Function to obtain box ID, location, shelf, bay and index from ticket 
        /*
        public static function fetch_box_details($id)
        {
            global $wpdb; 
            // die(print_r($wpdb->prefix));
            $array = array();
            $args = [
                'where' => [
                    ['ticket_id',  $id],
                    ['wpqa_wpsc_epa_boxinfo.storage_location_id', 'wpqa_wpsc_epa_storage_location.id', 'AND'],
                ]
            ];
            $wpqa_wpsc_epa_boxinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo, {$wpdb->prefix}wpqa_wpsc_epa_storage_location");
            $box_result = $wpqa_wpsc_epa_boxinfo->get_results($args, false);
        
            foreach ($box_result as $box) {
                $box_shelf_location = $box->aisle . 'a_' .$box->bay .'b_' . $box->shelf . 's_' . $box->position .'p';
                $parent = new stdClass;
                $parent->id = $box->box_id;
                $parent->shelf_location = $box_shelf_location;
                $array[] = $parent;

            }
            return $array;
        }
*/
        //Function to obtain box details from box ID
        public static function fetch_box_id_a( $id )
        {
            $boxidArray = explode(',', $id);
            return $boxidArray;
        }

        //Function to obtain location value from database
        public static function fetch_location( $id )
        {
            global $wpdb;
            $array = array();
            // $box_digitization_center = $wpdb->get_results( "SELECT * FROM wpqa_wpsc_epa_boxinfo WHERE ticket_id = " . $GLOBALS['id']);
            $args = [
                'where' => ['ticket_id', $id],
            ];
            $wpqa_wpsc_epa_boxinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo");
            $box_digitization_center = $wpqa_wpsc_epa_boxinfo->get_results($args, false);

            foreach ($box_digitization_center as $location) {
                array_push($array, strtoupper($location->location));
            }
            return $array;
        }
        
        //Function to obtain location value from ticket_id
        public static function get_dc_array_from_ticket_id( $id ) {
            global $wpdb;
            $array = array();
            
            $sql = "SELECT 
            			Group_concat( DISTINCT term.term_id ) as dc
					FROM   
						" . $wpdb->prefix . "wpsc_epa_boxinfo box
					LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location AS loc
						ON loc.id = box.storage_location_id
					LEFT JOIN " . $wpdb->prefix . "terms AS term
					    ON term.term_id = loc.digitization_center
					WHERE  box.ticket_id = " . $id;
            $dc_num_results = $wpdb->get_results( $sql );
            $dc_num_obj = $dc_num_results[0];
            $dc_num_str = $dc_num_obj->dc;
            $dc_num_array = explode( ',', $dc_num_str );
            
            return $dc_num_array;
        }
        
        //Function to obtain location value from ticket_id
        public static function get_dc_array_from_box_id( $id ) {
            global $wpdb;
            $array = array();
            
            $sql = "SELECT 
            			Group_concat( DISTINCT term.term_id ) as dc
					FROM   
						" . $wpdb->prefix . "wpsc_epa_boxinfo box
					LEFT JOIN " . $wpdb->prefix . "wpsc_epa_storage_location AS loc
						ON loc.id = box.storage_location_id
					LEFT JOIN " . $wpdb->prefix . "terms AS term
					    ON term.term_id = loc.digitization_center
					WHERE  box.id = " . $id;
            $dc_num_results = $wpdb->get_results( $sql );
            $dc_num_obj = $dc_num_results[0];
            $dc_num_str = $dc_num_obj->dc;
            $dc_num_array = explode( ',', $dc_num_str );
            
            return $dc_num_array;
        }
        
        public static function dc_array_to_readable_string( $dc_array ) {
	        
	        $dc_check = '';
	        $dc_count = count( array_unique( $dc_array ) );
	        
	        $dc_west_tag = get_term_by('slug', 'w', 'wpsc_categories');
	        $dc_east_tag = get_term_by('slug', 'e', 'wpsc_categories');
	        $dc_east_cui_tag = get_term_by('slug', 'ecui', 'wpsc_categories');
	        $dc_west_cui_tag = get_term_by('slug', 'wcui', 'wpsc_categories');
	        $dc_no_assigned_tag = get_term_by('slug', 'not-assigned-digi-center', 'wpsc_categories');
	        
	        $dc_west_term = $dc_west_tag->term_id;
	        $dc_east_term = $dc_east_tag->term_id;
	        $dc_east_cui_term = $dc_east_cui_tag->term_id;
	        $dc_west_cui_term = $dc_west_cui_tag->term_id;
	        $dc_no_assigned_term = $dc_no_assigned_tag->term_id;
	        

    			//if( $dc_count == 1 && in_array('62', $dc_array )) {
      		if( $dc_count == 1 && in_array( $dc_east_term, $dc_array ) ) {
    				$dc_check = 'East';
    			}
    			
    			//if( $dc_count == 1 && in_array('2', $dc_array )) {
      		if( $dc_count == 1 && in_array( $dc_west_term, $dc_array ) ) {	
    				$dc_check = 'West';
    			}
    
    			//if( $dc_count == 1 && in_array('666', $dc_array )) {
      		if( $dc_count == 1 && in_array( $dc_no_assigned_term, $dc_array )) {	
    				$dc_check = 'Not assigned';
    			}
    
    			//if( $dc_count == 2 && in_array('62', $dc_array ) && in_array('2', $dc_array )) {
      		if( $dc_count == 2 && in_array( $dc_east_term, $dc_array ) && in_array( $dc_west_term, $dc_array )) {	
    				$dc_check = 'Both';
    			}
    			
    			//if( $dc_count == 2 && in_array('62', $dc_array ) && in_array('666', $dc_array )) {
      		if( $dc_count == 2 && in_array( $dc_east_term, $dc_array ) && in_array( $dc_no_assigned_term, $dc_array )) {	
    				$dc_check = 'East/Not Assigned';
    			}
    
    			//if( $dc_count == 2 && in_array('666', $dc_array ) && in_array('2', $dc_array )) {
      		if( $dc_count == 2 && in_array( $dc_no_assigned_term, $dc_array ) && in_array( $dc_west_term, $dc_array )) {	
    				$dc_check = 'West/Not Assigned';
    			}
    			
    			//if( $dc_count == 3 && in_array('666', $dc_array ) && in_array('2', $dc_array ) && in_array('62', $dc_array )) {
      		if( $dc_count == 3 && in_array( $dc_no_assigned_term, $dc_array ) && in_array( $dc_west_term, $dc_array ) && in_array( $dc_east_term, $dc_array )) {	
    				$dc_check = 'Both/Not Assigned';
    			}
    	        
    	        return $dc_check;
    	  }

        /*
        //Function to obtain program office from database
        public static function fetch_program_office( $id )
        {
            global $wpdb;
            $array = array();
            // $request_program_office = $wpdb->get_results("SELECT acronym FROM wpqa_wpsc_epa_boxinfo, wpqa_wpsc_epa_program_office WHERE wpqa_wpsc_epa_boxinfo.program_office_id = wpqa_wpsc_epa_program_office.id AND ticket_id = " . $GLOBALS['id']);
            $args = [
                'select' => 'acronym',
                'where' => [
                    ['ticket_id',  $id],
                    ['wpqa_wpsc_epa_boxinfo.program_office_id', 'wpqa_wpsc_epa_program_office.id', 'AND'],
                ]
            ];

            $wpqa_wpsc_epa_boxinfo_wpqa_wpsc_epa_program_office = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo, {$wpdb->prefix}wpsc_epa_program_office");
            $request_program_office = $wpqa_wpsc_epa_boxinfo_wpqa_wpsc_epa_program_office->get_results($args, false);
            // dd($request_program_office);
            foreach ($request_program_office as $program_office) {
                array_push($array, strtoupper($program_office->acronym));
            }
            // dd($array);
            return $array;
        }

        //Function to obtain shelf from database
        public static function fetch_shelf( $id )
        {
            global $wpdb;
            $array = array();
            // $request_shelf = $wpdb->get_results("SELECT shelf FROM wpqa_wpsc_epa_boxinfo, wpqa_wpsc_ticket WHERE wpqa_wpsc_epa_boxinfo.ticket_id = wpqa_wpsc_ticket.id AND ticket_id = " . $GLOBALS['id']);
            $db_prefix = $wpdb->prefix;
            $args = [
                'select' => 'shelf',
                'where' => [
                    ['ticket_id',  $id],
                    [$db_prefix.'wpsc_epa_boxinfo.ticket_id', $db_prefix.'wpsc_ticket.id', 'AND'],
                ],
            ];
            $wpqa_wpsc_epa_boxinfo_wpqa_wpsc_ticket = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo, {$wpdb->prefix}wpsc_ticket");
            $request_shelf = $wpqa_wpsc_epa_boxinfo_wpqa_wpsc_ticket->get_results($args, false);

            foreach ($request_shelf as $shelf) {
                array_push($array, strtoupper($shelf->shelf));
            }
            return $array;
        }

        //Function to obtain bay from database
        public static function fetch_bay( $id )
        {
            global $wpdb;
            $array = array();
            // $request_bay = $wpdb->get_results("SELECT bay FROM wpqa_wpsc_epa_boxinfo, wpqa_wpsc_ticket WHERE wpqa_wpsc_epa_boxinfo.ticket_id = wpqa_wpsc_ticket.id AND ticket_id = " . $GLOBALS['id']);
            $db_prefix = $wpdb->prefix;
            $args = [
                'select' => 'bay',
                'where' => [
                    ['ticket_id',  $id],
                    [$db_prefix.'wpsc_epa_boxinfo.ticket_id', $db_prefix.'wpsc_ticket.id', 'AND'],
                ],
            ];
            $wpqa_wpsc_epa_boxinfo_wpqa_wpsc_ticket = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo, {$wpdb->prefix}wpsc_ticket");
            $request_bay = $wpqa_wpsc_epa_boxinfo_wpqa_wpsc_ticket->get_results($args, false);

            foreach ($request_bay as $bay) {
                array_push($array, strtoupper($bay->bay));
            }
            return $array;
        }
*/
        //Function to obtain create month and year from database
        public static function fetch_create_date( $id )
        {
            global $wpdb;
            // $request_create_date = $wpdb->get_row( "SELECT date_created FROM wpqa_wpsc_ticket WHERE id = " . $GLOBALS['id']);

            $args = [
                'select' => 'date_created',
                'where' => ['id',  $id],
            ];
            $wpqa_wpsc_ticket = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_ticket");
            $request_create_date = $wpqa_wpsc_ticket->get_row($args, false);

            $create_date = $request_create_date->date_created;
            $date = strtotime($create_date);

            return strtoupper(date('M y', $date));
        }

        //Function to obtain request key
        public static function fetch_request_key( $id )
        {
            global $wpdb;
            // $request_key = $wpdb->get_row( "SELECT ticket_auth_code FROM wpqa_wpsc_ticket WHERE id = " . $GLOBALS['id']);

            $args = [
                'select' => 'ticket_auth_code',
                'where' => ['id',  $id],
            ];
            $wpqa_wpsc_ticket = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_ticket");
            $request_key = $wpqa_wpsc_ticket->get_row($args, false);

            $key = $request_key->ticket_auth_code;
            return $key;
        }

        //Function to obtain box count
        public static function fetch_box_count( $id )
        {
            global $wpdb;
            // $box_count = $wpdb->get_row( "SELECT COUNT(ticket_id) as count FROM wpqa_wpsc_epa_boxinfo WHERE ticket_id = " . $GLOBALS['id']);

            $args = [
                'select' => 'COUNT(ticket_id) as count',
                'where' => ['ticket_id', $id],
            ];
            $wpqa_wpsc_epa_boxinfo = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_boxinfo");
            $box_count = $wpqa_wpsc_epa_boxinfo->get_row($args, false);

            $count_val = $box_count->count;
            return $count_val;
        }
        
        function convert_epc_pattboxid($epc) 
        {
            $remove_E = strtok($epc, 'E');
            
            $newstr = substr_replace($remove_E, '-', 8, 0);
            
            return $newstr;
        }
            
        function convert_pattboxid_epc($pattid) 
        {
            $add_E = str_replace('-', '', $pattid).'E';
            
            $str_length = 24;
            
            $newstr = str_pad($add_E, $str_length, 0);
            
            
            return $newstr;
        }
        
       	// Translates an array of wp user ids to wpsc agent ids and visaversa
        public static function translate_user_id( $array_of_users, $change_to_type ) {
	
			$agent_ids = self::get_user_lut();
			
			if( $change_to_type == 'wp_user_id' ) {
				if( is_array($array_of_users) ) {
					foreach ( $array_of_users as $wp_id ) {
						$key = array_search( $wp_id, array_column($agent_ids, 'agent_term_id'));
						$wp_user_id = $agent_ids[$key]['wp_user_id']; //current user agent term id
						$assigned_agents[] = $wp_user_id;
					}
				} 
				
			} elseif ( $change_to_type == 'agent_term_id' ) {
				if( is_array($array_of_users) ) {
					foreach ( $array_of_users as $agent_term ) {
						$key = array_search( $agent_term, array_column($agent_ids, 'wp_user_id'));
						$agent_term_id = $agent_ids[$key]['agent_term_id']; //current user agent term id
						$assigned_agents[] = $agent_term_id;
					}
				} 
			} 
          /*elseif ( $change_to_type == 'user_id') {
              if( is_array($array_of_users) ) {
					foreach ( $array_of_users as $user_id ) {
						/*$key = array_search( $user_id, array_column($agent_ids, 'wp_user_id'));
						$agent_user_id = $agent_ids[$key]['agent_term_id']; //current user agent term id
						$assigned_agents[] = $agent_user_id;*/
                    /*  $assigned_agents[] = $user_id;
                      
                      //var_dump($user_id);
					}
				} 
            }*/
			
			return $assigned_agents;
		}
		
		// Creates an array that acts as a lookup table for wp user id and wpsc agent id
        public static function get_user_lut() {
			// Get current user id & convert to wpsc agent id.
			$agent_ids = array();
			$agents = get_terms([
				'taxonomy'   => 'wpsc_agents',
				'hide_empty' => false,
				'orderby'    => 'meta_value_num',
				'order'    	 => 'ASC',
			]);
			foreach ($agents as $agent) {
				$agent_ids[] = [
					'agent_term_id' => $agent->term_id,
					'wp_user_id' => get_term_meta( $agent->term_id, 'user_id', true),
				];
			}
			
			return $agent_ids;
		}
		
		
		// Filters the input $agent_array (agent_ids) based on the roles given by the $role_array
		public static function return_agent_ids_in_role( $agent_array, $role_array ) {
			
			if( count($role_array) == 0 || count($agent_array) == 0 ) {
				return false;
			}
			
			$role_num_array = [];
			$agent_role_lut = self::get_agent_user_role_lut();
			
			foreach( $role_array as $role_name ) {
				if( $role_name == 'Administrator' ) { // name from PATT Digitization Staff (https://086.info/wordpress3/wp-admin/admin.php?page=wpsc-support-agents)
					$role_num_array[] = 1; // role id in termmeta with meta_key = 'role'
				} elseif( $role_name == 'Manager' ) {
					$role_num_array[] = 4;
				} elseif( $role_name == 'Agent' ) {
					$role_num_array[] = 2;
				} elseif( $role_name == 'Requester' ) {
					$role_num_array[] = 3;
				} elseif( $role_name == 'Requester Pallet' ) {
					$role_num_array[] = 5;
				}
			}
			
			$agent_ids_in_roles = [];
			
			foreach( $agent_array as $agent ) {
				
				$index = array_search( $agent, array_column($agent_role_lut, 'agent_id') );

				
				if( in_array( $agent_role_lut[$index]['role'], $role_num_array ) ) {
					$agent_ids_in_roles[] = $agent;
				}
				
				//$agent_ids_in_roles[] = [ $agent, $index ];
			}
			
			return $agent_ids_in_roles;
			
		}
		
		// Creates an array that acts as a lookup table for wp user id and wpsc agent id
        public static function get_agent_user_role_lut() {
			// Get current user id & convert to wpsc agent id.
			$agent_ids = array();
			$agents = get_terms([
				'taxonomy'   => 'wpsc_agents',
				'hide_empty' => false,
				'orderby'    => 'meta_value_num',
				'order'    	 => 'ASC',
			]);
			foreach ($agents as $agent) {
				$agent_ids[] = [
					'agent_id' => $agent->term_id,
					'role' => get_term_meta( $agent->term_id, 'role', true)
				];
			}
			
			return $agent_ids;
		}
		
		//Function to return agent ids for a particular group
        public static function agent_from_group( $agent_group_name )
        {
            global $wpdb;

			$agents = get_terms([
				'taxonomy'   => 'wpsc_agents',
				'hide_empty' => false,
				'meta_query' => array(
			    array(
			      'key'       => 'agentgroup',
			      'value'     => '0',
			      'compare'   => '='
			    )
			  )
			]);
			
			$agent_role = get_option('wpsc_agent_role');
			
			$agent_group_array = array();
			foreach ( $agents as $agent ) {
			    //GET USER ID $agent_user_id = get_term_meta( $agent->term_id, 'user_id', true);
			    $agent_id = $agent->term_id;
			    $role_id = get_term_meta( $agent->term_id, 'role', true);
				if ($agent_role[$role_id]['label'] == $agent_group_name) {
					array_push($agent_group_array, $agent_id);
				}
			
			}

           return $agent_group_array;

        }
        
        //Function to return agent ids for a particular group
        public static function agent_from_group_by_location( $agent_group_name, $location )
        {
            global $wpdb;
            
            if( $location = 'East' ) {
	            
            } elseif( $location = 'West' ) {
	            
            } elseif( $location = '' ) {
	            return false;
            } 

			$agents = get_terms([
				'taxonomy'   => 'wpsc_agents',
				'hide_empty' => false,
				'meta_query' => array(
			    array(
			      'key'       => 'agentgroup',
			      'value'     => '0',
			      'compare'   => '='
			    )
			  )
			]);
			
			$agent_role = get_option('wpsc_agent_role');
			
			$agent_group_array = array();
			foreach ( $agents as $agent ) {
			    //GET USER ID $agent_user_id = get_term_meta( $agent->term_id, 'user_id', true);
			    $agent_id = $agent->term_id;
			    $role_id = get_term_meta( $agent->term_id, 'role', true);
				if ($agent_role[$role_id]['label'] == $agent_group_name) {
					array_push($agent_group_array, $agent_id);
				}
			
			}

           return $agent_group_array;

        }

		//TESTING ONLY REMOVE CONVERT DB EMAIL to USER ID
        public static function convert_db_contact_email( $agent_email )
        {
            global $wpdb;

	        $get_user_id = $wpdb->get_row( "SELECT count(ID) FROM wpqa_users WHERE user_email = '" . $agent_email . "'" );
	        
	        if ( $get_user_id > 0 ){
		       	$user_id_array = array($get_user_id->ID);
		        $user_id = self::translate_user_id( $user_id_array, 'agent_term_id' );
            
            } else {
            	$user_id = 'error';
            }
            
	        return $user_id;

        }	
        
        
        // Returns an array of acceptable box status that can be set given the array of Box Folder File IDs, based on the following rules:
		//
		// 1) IF Nobody assigned to the box THEN all but Pending must be disabled. (672,671,65,6,673,674,743,66,68,67)
		// 2) Box is not validated (66,68,67) - Validation is a status in same list, right? Must be in status of Validation?
		//                                   - count validated flag is in folder doc 
		// 3) Destruction Approval - Check to see if request contains a destruction_approval of 1 in wpqa_wpsc_ticket - IF = 0 then disable
		//                         - Disable the ability to select Destruction approval if Not approved. 
		// 4) if request status = 3,670,69 THEN 672,671,65,6,673,674,743,66,68,67 Need to be disabled (Only allow Pending) 
		// 5) restrict the available status to only the next status in the recall process
		// 
		// Also returns text for warnings/errors
		
        public static function get_restricted_box_status_list_2( $item_ids, $role = 'Agent' ) {
	        
	        global $wpdb;
	        
	        $restricted_status_list = array();
	        $restricted_reason_array = array();
			$restriction_reason = '';
			$all_unassigned_x = true;
			$condition_c1 = false; 
			$condition_c4 = false; 
			
			foreach( $item_ids as $item ) {
				$box_obj = self::get_box_file_details_by_id($item);
				$status_agent_array = self::get_user_status_data( ['box_id' => $box_obj->Box_id_FK ] );
				//$ignore_box_status = ['Pending', 'Ingestion', 'Completed', 'Cancelled', 'Dispositioned', 'Completed/Dispositioned' ];
				$ignore_box_status = [ 'Pending', 'Ingestion', 'Completed Permanent Records', 'Cancelled', 'Completed/Dispositioned' ];
				
				$status_list_assignable = self::get_all_status($ignore_box_status);
			 	$where = ['box_folder_file_id' => $box_obj->box_id ];
			 	$ticket_id_obj = self::get_ticket_id_from_box_folder_file( $where );
			 	
			 	// reset restrictions
			 	$restriction_reason = '';
			
				// Condition 1: IF Nobody assigned to the box THEN all but Pending must be disabled.
				$all_assigned = false;
				$all_unassigned = true;
				foreach( $status_agent_array['status'] as $term_id=>$user_array ) {
					
					if( array_key_exists($term_id, $status_list_assignable) ) {
						if( count($user_array) > 0 && $user_array != 'N/A' ) {
							//users exist
							$all_unassigned = false;
							break;
						}
					}
				}
				
				// Condition 1 SET.
				if( $all_unassigned ) {
					
					$restriction_reason .= '<p>Box '.$box_obj->box_id.' has no one assigned to Any Status. <p>';
					$condition_c1 = true;
					$restricted_reason_array[ $box_obj->box_id ] = $restriction_reason;
					
					if( !in_array('Scanning Preparation', $restricted_status_list) ) {
						$restricted_status_list[] = 'Scanning Preparation';
					} 
					if( !in_array('Scanning/Digitization', $restricted_status_list) ) {
						$restricted_status_list[] = 'Scanning/Digitization';
					} 
					if( !in_array('QA/QC', $restricted_status_list) ) {
						$restricted_status_list[] = 'QA/QC';
					} 
					if( !in_array('Digitized - Not Validated', $restricted_status_list) ) {
						$restricted_status_list[] = 'Digitized - Not Validated';
					} 
					if( !in_array('Ingestion', $restricted_status_list) ) {
						$restricted_status_list[] = 'Ingestion';
					} 
					if( !in_array('Validation', $restricted_status_list) ) {
						$restricted_status_list[] = 'Validation';
					} 
					if( !in_array('Re-scan', $restricted_status_list) ) {
						$restricted_status_list[] = 'Re-scan';
					} 
					if( !in_array('Completed Permanent Records', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed Permanent Records';
					} 
					if( !in_array('Destruction Approved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction Approved';
					} 
/*
					if( !in_array('Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Dispositioned';
					} 
*/
					if( !in_array('Completed/Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed/Dispositioned';
					} 
					if( !in_array('Destruction of Source', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction of Source';
					} 
					if( !in_array('Waiting/Shelved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Waiting/Shelved';
					}
					if( !in_array('Waiting on RLO', $restricted_status_list) ) {
						$restricted_status_list[] = 'Waiting on RLO';
					}
					if( !in_array('Cancelled', $restricted_status_list) ) {
						$restricted_status_list[] = 'Cancelled';
					}
					
				}
				
				
				// Condition 2: Box is not validated (66,68,67)
				$get_sum_total = $wpdb->get_row("SELECT
												    SUM(q.total_count) AS sum_total_count
												FROM
												    (
												    SELECT
												        (
												        SELECT
												            COUNT(fdif.id)
												        FROM
												            ".$wpdb->prefix."wpsc_epa_folderdocinfo_files AS fdif
												        WHERE
												            fdif.box_id = a.id
												    ) AS total_count
												FROM
												    ".$wpdb->prefix."wpsc_epa_boxinfo AS a
												WHERE
												    a.id = '" . $box_obj->Box_id_FK . "'
												) q");

				
				$sum_total_val = $get_sum_total->sum_total_count;

				$get_sum_validation = $wpdb->get_row("SELECT
													    SUM(b.validation) AS sum_validation
													FROM
													    (
													    SELECT
													        (
													        SELECT
													            SUM(VALIDATION = 1)
													        FROM
													            ".$wpdb->prefix."wpsc_epa_folderdocinfo_files fdif
													        WHERE
													            fdif.box_id = a.id
															) AS VALIDATION
														FROM
														    ".$wpdb->prefix."wpsc_epa_boxinfo AS a
														WHERE
														    a.id = '" . $box_obj->Box_id_FK . "'
														) b");	
												
									
				$sum_validation = $get_sum_validation->sum_validation;
			
				$validated = '';
				
				if($sum_total_val == $sum_validation) {
					$validated = 1;
				} else {
					$validated = 0;
				}
				
				// Condition 2 SET
				if( !$validated ) {
					$restriction_reason .= '<p>Contents of Box '.$box_obj->box_id.' have not been Validated.</p>';
					$restricted_reason_array[ $box_obj->box_id ] = $restriction_reason;
					
					if( !in_array('Completed Permanent Records', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed Permanent Records';
					} 
/*
					if( !in_array('Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Dispositioned';
					}
*/
					if( !in_array('Completed/Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed/Dispositioned';
					}
					if( !in_array('Destruction Approved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction Approved';
					}
				}
				
				
				// Condition 3 - Destruction Approval
				
			 	$box_destruction_approval = $wpdb->get_row("SELECT destruction_approval FROM ".$wpdb->prefix."wpsc_ticket WHERE id='".$ticket_id_obj['ticket_id']."'");				
				
				// Condition 3 SET - Show destruction approval setting?
				if( $box_destruction_approval->destruction_approval ) {
					
					// if Destruction Approval has already been restricted AND C1 OR C4 has never come up... 
					if( in_array('Destruction Approved', $restricted_status_list) && !$condition_c1 && !$condition_c4 ) {
						$the_key = array_search('Destruction Approved', $restricted_status_list);
						unset($restricted_status_list[$the_key]);
						array_values($restricted_status_list);
					}
				} else {
					$restriction_reason .= '<p>Contents of Box '.$box_obj->box_id.' have not been approved for Destruction.</p>';
					$restricted_reason_array[ $box_obj->box_id ] = $restriction_reason;
					
					if( !in_array('Destruction Approved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction Approved';
					}
					if( !in_array('Destruction of Source', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction of Source';
					}
					if( !in_array('Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Dispositioned';
					}
					if( !in_array('Completed/Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed/Dispositioned';
					}
				}
				
				// Condition 4 - if request status = 3,670,69 - only allow 'Pending'
				//$data = [ 'ticket_id'=>$ticket_id_obj['ticket_id'] ];
				$ticket_status = self::get_ticket_status( $ticket_id_obj['ticket_id'] );
				
				
				// Condition 4 SET
				// if request status = 3,670,69 THEN 672,671,65,6,673,674,743,66,68,67 Need to be disabled (Only allow Pending) 
				$rqst_status_new_term_id = self::get_term_by_slug( 'open' );	 // 3 aka New
				$rqst_status_review_rejected_term_id = self::get_term_by_slug( 'initial-review-rejected' );	 // 670 aka Initial Review Rejected
				$rqst_status_cancelled_term_id = self::get_term_by_slug( 'destroyed' );	 // 69 aka Cancelled							
				$rqst_status_tabled_term_id = self::get_term_by_slug( 'tabled' );	 // 
				$rqst_status_comp_disp_term_id = self::get_term_by_slug( 'completed/dispositioned' );	 // 
				
// 				if( $ticket_status == 3 || $ticket_status == 670 || $ticket_status == 69 ) {
				if( $ticket_status == $rqst_status_new_term_id || $ticket_status == $rqst_status_review_rejected_term_id || $ticket_status == $rqst_status_cancelled_term_id || $ticket_status == $rqst_status_tabled_term_id || $ticket_status == $rqst_status_comp_disp_term_id ) {
					$save_enabled = false;
					$restriction_reason .= '<p>Request of Box '.$box_obj->box_id.' has a status of New Request, Tabled, Initial Review Rejected, Cancelled or Completed/Dispositioned. </p>';
					$condition_c4 = true;
				
					$restricted_reason_array[ $box_obj->box_id ] = $restriction_reason;
					
					if( !in_array('Scanning Preparation', $restricted_status_list) ) {
						$restricted_status_list[] = 'Scanning Preparation';
					} 
					if( !in_array('Scanning/Digitization', $restricted_status_list) ) {
						$restricted_status_list[] = 'Scanning/Digitization';
					} 
					if( !in_array('QA/QC', $restricted_status_list) ) {
						$restricted_status_list[] = 'QA/QC';
					} 
					if( !in_array('Digitized - Not Validated', $restricted_status_list) ) {
						$restricted_status_list[] = 'Digitized - Not Validated';
					} 
					if( !in_array('Ingestion', $restricted_status_list) ) {
						$restricted_status_list[] = 'Ingestion';
					} 
					if( !in_array('Validation', $restricted_status_list) ) {
						$restricted_status_list[] = 'Validation';
					} 
					if( !in_array('Re-scan', $restricted_status_list) ) {
						$restricted_status_list[] = 'Re-scan';
					} 
					if( !in_array('Completed Permanent Records', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed Permanent Records';
					} 
					if( !in_array('Destruction Approved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction Approved';
					} 
					if( !in_array('Destruction of Source', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction of Source';
					} 
					if( !in_array('Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Dispositioned';
					} 
					if( !in_array('Completed/Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed/Dispositioned';
					} 
					
				}
				
				
				// Condition 5: restrict the available status to only the next status in the process
				// if multi, they all have same status at this point. 
				// if single, find status, get next status. 
				
				// get current status 
				// list all statuses - self::get_all_status();
				// get/set NEXT status
				// remove NEXT status from array
				// get NEW $restricted_status_list
				// compare restricted_status_lists 
				// box_statuses: [723] => Name
				
				if ($role == 'Agent') {
					$current_status_term = self::get_box_file_details_by_id($item)->box_status;
					$next_status = '';
					$next_status_array = array();
					$all_statuses = self::get_all_status();
					$current_status = $all_statuses[$current_status_term];
					
					// Set $next_status
					switch($current_status) {
						case 'Pending':
							//$next_status = 'Scanning Preparation';
							$next_status_array[] = 'Scanning Preparation';
							break;
						case 'Scanning Preparation':
							//$next_status = 'Scanning/Digitization';
							$next_status_array[] = 'Scanning/Digitization';
							break;
						case 'Scanning/Digitization':
							//$next_status = 'QA/QC';
							$next_status_array[] = 'QA/QC';
							break;
						case 'QA/QC':
							//$next_status = 'Digitized - Not Validated';
							$next_status_array[] = 'Digitized - Not Validated';
							break;
						case 'Digitized - Not Validated':
							//$next_status = 'Ingestion';
							$next_status_array[] = 'Ingestion';
							break;
						case 'Ingestion':
							//$next_status = 'Validation';
							$next_status_array[] = 'Completed Permanent Records';
							$next_status_array[] = 'Validation';
							break;
						case 'Validation':
							//$next_status = 'Completed Permanent Records'; 
							//$next_status_array[] = 'Completed Permanent Records';
							$next_status_array[] = 'Destruction Approved'; 
							break;
						case 'Completed':
							//$next_status = '';
							$next_status_array[] = '';
							break;
						case 'Destruction Approved':
							//$next_status = '';
							$next_status_array[] = 'Destruction of Source';
							break;
						case 'Destruction of Source':
							//$next_status = '';
							$next_status_array[] = 'Completed/Dispositioned';
							break;	
/*
						case 'Dispositioned':
							//$next_status = '';
							$next_status_array[] = '';
							break;
*/
						case 'Completed/Dispositioned':
							//$next_status = '';
							$next_status_array[] = '';
							break;	
													
								
							
					}
					
					// remove NEXT status from $all_statuses array
					if( !empty( $next_status_array ) ) {
						
						// $the_key = array_search($next_status, $all_statuses);
						// unset($all_statuses[$the_key]);
						
						foreach( $next_status_array as $key2 => $value2 ) {
							$the_key = array_search( $value2, $all_statuses );
							unset( $all_statuses[$the_key] );
						}
					
					
						//array_values($all_statuses);
						
						// get NEW $restricted_status_list
						// list_2: [748] => Pending [672] => Scanning | $restricted_status_list: [0]=> Pending [1]=> Scanning Preparation
						$restricted_status_list_2 = $all_statuses; 
						foreach( $restricted_status_list_2 as $key=>$value ) {
							$restricted_status_list[] = $value;
						}
						$restricted_status_list = array_unique($restricted_status_list);
					
					} 
						
					
					// When Destruction Approval is selected then Validation is disabled (9)
					if( $current_status == 'Destruction Approved' ) {
						if( !in_array('Validation', $restricted_status_list) ) {
							$restricted_status_list[] = 'Validation';
						} 
					}
					// When Dispositioned is selected then disable Destruction Approval (10) // No longer a status
/*
					if( $current_status == 'Dispositioned' ) {
						if( !in_array('Destruction Approved', $restricted_status_list) ) {
							$restricted_status_list[] = 'Destruction Approved';
						} 
					}	
*/
					
					// When Completed Permanent Records is selected then disable all other statuses (11)
					if( $current_status == 'Completed Permanent Records' ) {
						$restricted_status_list_2 = $all_statuses; 
						foreach( $restricted_status_list_2 as $key=>$value ) {
							$restricted_status_list[] = $value;
						}
						$restricted_status_list = array_unique($restricted_status_list);
					}
					
					// When Completed/Dispositioned is selected then disable all other statuses (11)
					if( $current_status == 'Completed/Dispositioned' ) {
						$restricted_status_list_2 = $all_statuses; 
						foreach( $restricted_status_list_2 as $key=>$value ) {
							$restricted_status_list[] = $value;
						}
						$restricted_status_list = array_unique($restricted_status_list);
					}	
				} // End IF $role == 'Agent'		
				
				// Waiting/Shelved and Re-scan should always be enabled except when status is Completed, or Destruction Approval (1)
				if( $current_status != 'Completed Permanent Records' && $current_status != 'Destruction Approved' && $current_status != 'Dispositioned' && $current_status != 'Completed/Dispositioned' ) {
					
					$ws_index = array_search('Waiting/Shelved', $restricted_status_list);
					$rs_index = array_search('Re-scan', $restricted_status_list);
					$wrlo_index = array_search('Waiting on RLO', $restricted_status_list);
					$cnl_index = array_search('Cancelled', $restricted_status_list);
					
					if ( $ws_index ) {
						unset($restricted_status_list[$ws_index]);
					}
					if ( $rs_index ) {
						unset($restricted_status_list[$rs_index]);
					}
					if ( $wrlo_index ) {
						unset($restricted_status_list[$wrlo_index]);
					}
					if ( $cnl_index ) {
						unset($restricted_status_list[$cnl_index]);
					}
				}
				
				// allow current status to be placed in the list
				$current_status_index = array_search($current_status, $restricted_status_list);
				if ( $current_status_index ) {
					unset($restricted_status_list[$current_status_index]);
				}
				
				// Removed Box Status: Cancelled from list always. 
				//$restricted_status_list[] = 'Cancelled';
				
				
			}
			
			$box_statuses = self::get_all_status($restricted_status_list);
			$return_array = array();
			$return_array[ 'box_statuses' ] = $box_statuses;
			$return_array[ 'restriction_reason' ] = $restriction_reason;
			$return_array[ 'restricted_reason_array' ] = $restricted_reason_array;
			
			// DEBUG - START
			$return_array['restricted_status_list'] = $restricted_status_list;
			$return_array['debug_restricted_status_list_2'] = $restricted_status_list_2;
			$return_array['debug_next_status'] = $next_status;
			$return_array['debug_current_status'] = $current_status;						
			// DEBUG - END

			return $return_array;
	    }
    
    // OLD: NO LONGER USED.     
		// Returns an array of acceptable box status that can be set given the array of Box Folder File IDs, based on the following rules:
		//
		// 1) IF Nobody assigned to the box THEN all but Pending must be disabled. (672,671,65,6,673,674,743,66,68,67)
		// 2) Box is not validated (66,68,67) - Validation is a status in same list, right? Must be in status of Validation?
		//                                   - count validated flag is in folder doc 
		// 3) Destruction Approval - Check to see if request contains a destruction_approval of 1 in wpqa_wpsc_ticket - IF = 0 then disable
		//                         - Disable the ability to select Destruction approval if Not approved. 
		// 4) if request status = 3,670,69 THEN 672,671,65,6,673,674,743,66,68,67 Need to be disabled (Only allow Pending) 
		// 5) restrict the available status to only the next status in the recall process
		
        public static function get_restricted_box_status_list( $item_ids, $role = 'Agent' ) {
	        
	        global $wpdb;
	        
	        $restricted_status_list = array();
			$restriction_reason = '';
			$all_unassigned_x = true;
			$condition_c1 = false; 
			$condition_c4 = false; 
			
			foreach( $item_ids as $item ) {
				$box_obj = self::get_box_file_details_by_id($item);
				$status_agent_array = self::get_user_status_data( ['box_id' => $box_obj->Box_id_FK ] );
				//$ignore_box_status = ['Pending', 'Ingestion', 'Completed', 'Cancelled', 'Dispositioned', 'Completed/Dispositioned' ];
				$ignore_box_status = [ 'Pending', 'Ingestion', 'Completed Permanent Records', 'Cancelled', 'Completed/Dispositioned' ];
				
				$status_list_assignable = self::get_all_status($ignore_box_status);
			 	$where = ['box_folder_file_id' => $box_obj->box_id ];
			 	$ticket_id_obj = self::get_ticket_id_from_box_folder_file( $where );
			
				// Condition 1: IF Nobody assigned to the box THEN all but Pending must be disabled.
				$all_assigned = false;
				$all_unassigned = true;
				foreach( $status_agent_array['status'] as $term_id=>$user_array ) {
					
					if( array_key_exists($term_id, $status_list_assignable) ) {
						if( count($user_array) > 0 && $user_array != 'N/A' ) {
							//users exist
							$all_unassigned = false;
							break;
						}
					}
				}
				
				// Condition 1 SET.
				if( $all_unassigned ) {
					$restriction_reason .= '<p>Box '.$box_obj->box_id.' has no one assigned to Any Status. <p>';
					$condition_c1 = true;
					
					if( !in_array('Scanning Preparation', $restricted_status_list) ) {
						$restricted_status_list[] = 'Scanning Preparation';
					} 
					if( !in_array('Scanning/Digitization', $restricted_status_list) ) {
						$restricted_status_list[] = 'Scanning/Digitization';
					} 
					if( !in_array('QA/QC', $restricted_status_list) ) {
						$restricted_status_list[] = 'QA/QC';
					} 
					if( !in_array('Digitized - Not Validated', $restricted_status_list) ) {
						$restricted_status_list[] = 'Digitized - Not Validated';
					} 
					if( !in_array('Ingestion', $restricted_status_list) ) {
						$restricted_status_list[] = 'Ingestion';
					} 
					if( !in_array('Validation', $restricted_status_list) ) {
						$restricted_status_list[] = 'Validation';
					} 
					if( !in_array('Re-scan', $restricted_status_list) ) {
						$restricted_status_list[] = 'Re-scan';
					} 
					if( !in_array('Completed Permanent Records', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed Permanent Records';
					} 
					if( !in_array('Destruction Approved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction Approved';
					} 
					if( !in_array('Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Dispositioned';
					} 
					if( !in_array('Completed/Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed/Dispositioned';
					} 
					if( !in_array('Destruction of Source', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction of Source';
					} 
					if( !in_array('Waiting/Shelved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Waiting/Shelved';
					}
					if( !in_array('Waiting on RLO', $restricted_status_list) ) {
						$restricted_status_list[] = 'Waiting on RLO';
					}
					if( !in_array('Cancelled', $restricted_status_list) ) {
						$restricted_status_list[] = 'Cancelled';
					}
					
				}
				
				
				// Condition 2: Box is not validated (66,68,67)
				$get_sum_total = $wpdb->get_row("SELECT
												    SUM(q.total_count) AS sum_total_count
												FROM
												    (
												    SELECT
												        (
												        SELECT
												            COUNT(fdif.id)
												        FROM
												            ".$wpdb->prefix."wpsc_epa_folderdocinfo_files AS fdif
												        WHERE
												            fdif.box_id = a.id
												    ) AS total_count
												FROM
												    ".$wpdb->prefix."wpsc_epa_boxinfo AS a
												WHERE
												    a.id = '" . $box_obj->Box_id_FK . "'
												) q");

/*				// OLD: before dropping wpsc_epa_folderdocinfo table
				$get_sum_total = $wpdb->get_row("SELECT
												    SUM(q.total_count) AS sum_total_count
												FROM
												    (
												    SELECT
												        (
												        SELECT
												            COUNT(fdif.id)
												        FROM
												            ".$wpdb->prefix."wpsc_epa_folderdocinfo AS c
												        INNER JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo_files fdif ON fdif.folderdocinfo_id = c.id
												        WHERE
												            c.box_id = a.id
												    ) AS total_count
												FROM
												    ".$wpdb->prefix."wpsc_epa_boxinfo AS a
												WHERE
												    a.id = '" . $box_obj->Box_id_FK . "'
												) q");
*/
				
				$sum_total_val = $get_sum_total->sum_total_count;

				$get_sum_validation = $wpdb->get_row("SELECT
													    SUM(b.validation) AS sum_validation
													FROM
													    (
													    SELECT
													        (
													        SELECT
													            SUM(VALIDATION = 1)
													        FROM
													            ".$wpdb->prefix."wpsc_epa_folderdocinfo_files fdif
													        WHERE
													            fdif.box_id = a.id
															) AS VALIDATION
														FROM
														    ".$wpdb->prefix."wpsc_epa_boxinfo AS a
														WHERE
														    a.id = '" . $box_obj->Box_id_FK . "'
														) b");	
				
/* 				// OLD: before dropping wpsc_epa_folderdocinfo table
				$get_sum_validation = $wpdb->get_row("SELECT
													    SUM(b.validation) AS sum_validation
													FROM
													    (
													    SELECT
													        (
													        SELECT
													            SUM(VALIDATION = 1)
													        FROM
													            ".$wpdb->prefix."wpsc_epa_folderdocinfo_files fdif
													        JOIN ".$wpdb->prefix."wpsc_epa_folderdocinfo fdi ON
													            fdi.id = fdif.folderdocinfo_id
													        WHERE
													            fdi.box_id = a.id
															) AS VALIDATION
														FROM
														    ".$wpdb->prefix."wpsc_epa_boxinfo AS a
														WHERE
														    a.id = '" . $box_obj->Box_id_FK . "'
														) b");		
*/								
									
				$sum_validation = $get_sum_validation->sum_validation;
			
				$validated = '';
				
				if($sum_total_val == $sum_validation) {
					$validated = 1;
				} else {
					$validated = 0;
				}
				
				// Condition 2 SET
				if( !$validated ) {
					$restriction_reason .= '<p>Contents of Box '.$box_obj->box_id.' have not been Validated. </p>';
					
					if( !in_array('Completed Permanent Records', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed Permanent Records';
					} 
					if( !in_array('Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Dispositioned';
					}
					if( !in_array('Completed/Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed/Dispositioned';
					}
					if( !in_array('Destruction Approved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction Approved';
					}
				}
				
				
				// Condition 3 - Destruction Approval
				
			 	$box_destruction_approval = $wpdb->get_row("SELECT destruction_approval FROM ".$wpdb->prefix."wpsc_ticket WHERE id='".$ticket_id_obj['ticket_id']."'");				
				
				// Condition 3 SET - Show destruction approval setting?
				if( $box_destruction_approval->destruction_approval ) {
					
					// if Destruction Approval has already been restricted AND C1 OR C4 has never come up... 
					if( in_array('Destruction Approved', $restricted_status_list) && !$condition_c1 && !$condition_c4 ) {
						$the_key = array_search('Destruction Approved', $restricted_status_list);
						unset($restricted_status_list[$the_key]);
						array_values($restricted_status_list);
					}
				} else {
					$restriction_reason .= '<p>Contents of Box '.$box_obj->box_id.' have not been approved for Destruction. </p>';
					if( !in_array('Destruction Approved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction Approved';
					}
					if( !in_array('Destruction of Source', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction of Source';
					}
					if( !in_array('Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Dispositioned';
					}
					if( !in_array('Completed/Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed/Dispositioned';
					}
				}
				
				// Condition 4 - if request status = 3,670,69 - only allow 'Pending'
				//$data = [ 'ticket_id'=>$ticket_id_obj['ticket_id'] ];
				$ticket_status = self::get_ticket_status( $ticket_id_obj['ticket_id'] );
				
				
				// Condition 4 SET
				// if request status = 3,670,69 THEN 672,671,65,6,673,674,743,66,68,67 Need to be disabled (Only allow Pending) 
				$rqst_status_new_term_id = self::get_term_by_slug( 'open' );	 // 3 aka New
				$rqst_status_review_rejected_term_id = self::get_term_by_slug( 'initial-review-rejected' );	 // 670 aka Initial Review Rejected
				$rqst_status_cancelled_term_id = self::get_term_by_slug( 'destroyed' );	 // 69 aka Cancelled							
				
// 				if( $ticket_status == 3 || $ticket_status == 670 || $ticket_status == 69 ) {
				if( $ticket_status == $rqst_status_new_term_id || $ticket_status == $rqst_status_review_rejected_term_id || $ticket_status == $rqst_status_cancelled_term_id ) {
					$save_enabled = false;
					$restriction_reason .= $ticket_id_obj['ticket_id'].'<p>Containing Request of Box '.$box_obj->box_id.' has a status of New Request, Cancelled, Tabled, or Initial Review Rejected. </p>'.$ticket_status;
					$condition_c4 = true;
					
					if( !in_array('Scanning Preparation', $restricted_status_list) ) {
						$restricted_status_list[] = 'Scanning Preparation';
					} 
					if( !in_array('Scanning/Digitization', $restricted_status_list) ) {
						$restricted_status_list[] = 'Scanning/Digitization';
					} 
					if( !in_array('QA/QC', $restricted_status_list) ) {
						$restricted_status_list[] = 'QA/QC';
					} 
					if( !in_array('Digitized - Not Validated', $restricted_status_list) ) {
						$restricted_status_list[] = 'Digitized - Not Validated';
					} 
					if( !in_array('Ingestion', $restricted_status_list) ) {
						$restricted_status_list[] = 'Ingestion';
					} 
					if( !in_array('Validation', $restricted_status_list) ) {
						$restricted_status_list[] = 'Validation';
					} 
					if( !in_array('Re-scan', $restricted_status_list) ) {
						$restricted_status_list[] = 'Re-scan';
					} 
					if( !in_array('Completed Permanent Records', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed Permanent Records';
					} 
					if( !in_array('Destruction Approved', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction Approved';
					} 
					if( !in_array('Destruction of Source', $restricted_status_list) ) {
						$restricted_status_list[] = 'Destruction of Source';
					} 
					if( !in_array('Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Dispositioned';
					} 
					if( !in_array('Completed/Dispositioned', $restricted_status_list) ) {
						$restricted_status_list[] = 'Completed/Dispositioned';
					} 
					
				}
				
				
				// Condition 5: restrict the available status to only the next status in the recall process
				// if multi, they all have same status at this point. 
				// if single, find status, get next status. 
				
				// get current status 
				// list all statuses - self::get_all_status();
				// get/set NEXT status
				// remove NEXT status from array
				// get NEW $restricted_status_list
				// compare restricted_status_lists 
				// box_statuses: [723] => Name
				
				if ($role == 'Agent') {
					$current_status_term = self::get_box_file_details_by_id($item)->box_status;
					$next_status = '';
					$next_status_array = array();
					$all_statuses = self::get_all_status();
					$current_status = $all_statuses[$current_status_term];
					
					// Set $next_status
					switch($current_status) {
						case 'Pending':
							//$next_status = 'Scanning Preparation';
							$next_status_array[] = 'Scanning Preparation';
							break;
						case 'Scanning Preparation':
							//$next_status = 'Scanning/Digitization';
							$next_status_array[] = 'Scanning/Digitization';
							break;
						case 'Scanning/Digitization':
							//$next_status = 'QA/QC';
							$next_status_array[] = 'QA/QC';
							break;
						case 'QA/QC':
							//$next_status = 'Digitized - Not Validated';
							$next_status_array[] = 'Digitized - Not Validated';
							break;
						case 'Digitized - Not Validated':
							//$next_status = 'Ingestion';
							$next_status_array[] = 'Ingestion';
							break;
						case 'Ingestion':
							//$next_status = 'Validation';
							$next_status_array[] = 'Completed Permanent Records';
							$next_status_array[] = 'Validation';
							break;
						case 'Validation':
							//$next_status = 'Completed Permanent Records'; 
							//$next_status_array[] = 'Completed Permanent Records';
							$next_status_array[] = 'Destruction Approved'; 
							break;
						case 'Completed':
							//$next_status = '';
							$next_status_array[] = '';
							break;
						case 'Destruction Approved':
							//$next_status = '';
							$next_status_array[] = 'Destruction of Source';
							break;
						case 'Destruction of Source':
							//$next_status = '';
							$next_status_array[] = 'Completed/Dispositioned';
							break;	
						case 'Dispositioned':
							//$next_status = '';
							$next_status_array[] = '';
							break;
						case 'Completed/Dispositioned':
							//$next_status = '';
							$next_status_array[] = '';
							break;	
													
								
							
					}
					
					// remove NEXT status from $all_statuses array
					if( !empty( $next_status_array ) ) {
						
						// $the_key = array_search($next_status, $all_statuses);
						// unset($all_statuses[$the_key]);
						
						foreach( $next_status_array as $key2 => $value2 ) {
							$the_key = array_search( $value2, $all_statuses );
							unset( $all_statuses[$the_key] );
						}
					
					
						//array_values($all_statuses);
						
						// get NEW $restricted_status_list
						// list_2: [748] => Pending [672] => Scanning | $restricted_status_list: [0]=> Pending [1]=> Scanning Preparation
						$restricted_status_list_2 = $all_statuses; 
						foreach( $restricted_status_list_2 as $key=>$value ) {
							$restricted_status_list[] = $value;
						}
						$restricted_status_list = array_unique($restricted_status_list);
					
					} 
						
					
					// When Destruction Approval is selected then Validation is disabled (9)
					if( $current_status == 'Destruction Approved' ) {
						if( !in_array('Validation', $restricted_status_list) ) {
							$restricted_status_list[] = 'Validation';
						} 
					}
					// When Dispositioned is selected then disable Destruction Approval (10) // No longer a status
/*
					if( $current_status == 'Dispositioned' ) {
						if( !in_array('Destruction Approved', $restricted_status_list) ) {
							$restricted_status_list[] = 'Destruction Approved';
						} 
					}	
*/
					
					// When Completed Permanent Records is selected then disable all other statuses (11)
					if( $current_status == 'Completed Permanent Records' ) {
						$restricted_status_list_2 = $all_statuses; 
						foreach( $restricted_status_list_2 as $key=>$value ) {
							$restricted_status_list[] = $value;
						}
						$restricted_status_list = array_unique($restricted_status_list);
					}
					
					// When Completed/Dispositioned is selected then disable all other statuses (11)
					if( $current_status == 'Completed/Dispositioned' ) {
						$restricted_status_list_2 = $all_statuses; 
						foreach( $restricted_status_list_2 as $key=>$value ) {
							$restricted_status_list[] = $value;
						}
						$restricted_status_list = array_unique($restricted_status_list);
					}	
				} // End IF $role == 'Agent'		
				
				// Waiting/Shelved and Re-scan should always be enabled except when status is Completed, Destruction Approval, or Dispositioned (1)
				if( $current_status != 'Completed Permanent Records' && $current_status != 'Destruction Approved' && $current_status != 'Dispositioned' && $current_status != 'Completed/Dispositioned' ) {
					
					$ws_index = array_search('Waiting/Shelved', $restricted_status_list);
					$rs_index = array_search('Re-scan', $restricted_status_list);
					$wrlo_index = array_search('Waiting on RLO', $restricted_status_list);
					$cnl_index = array_search('Cancelled', $restricted_status_list);
					
					if ( $ws_index ) {
						unset($restricted_status_list[$ws_index]);
					}
					if ( $rs_index ) {
						unset($restricted_status_list[$rs_index]);
					}
					if ( $wrlo_index ) {
						unset($restricted_status_list[$wrlo_index]);
					}
					if ( $cnl_index ) {
						unset($restricted_status_list[$cnl_index]);
					}
				}
				
				// allow current status to be placed in the list
				$current_status_index = array_search($current_status, $restricted_status_list);
				if ( $current_status_index ) {
					unset($restricted_status_list[$current_status_index]);
				}
				
				// Removed Box Status: Cancelled from list always. 
				//$restricted_status_list[] = 'Cancelled';
				
				
			}
			
			$box_statuses = self::get_all_status($restricted_status_list);
			$return_array = array();
			$return_array['box_statuses'] = $box_statuses;
			$return_array['restriction_reason'] = $restriction_reason;
			
			// DEBUG - START
			$return_array['restricted_status_list'] = $restricted_status_list;
			$return_array['debug_restricted_status_list_2'] = $restricted_status_list_2;
			$return_array['debug_next_status'] = $next_status;
			$return_array['debug_current_status'] = $current_status;						
			// DEBUG - END

			return $return_array;
	    }
	    
	    
	    public static function item_in_return( $item_id, $type, $subfolder_path ) {
		   	
			global $wpdb;
 		   	
			$return_info = [];
		   	
			$decline_cancelled_term_id = self::get_term_by_slug( 'decline-cancelled' );	 // 791 aka Decline Cancelled
			$decline_complete_term_id = self::get_term_by_slug( 'decline-complete' );	 // 754 aka Decline Complete		   	
			
			$item_details =  self::get_box_file_details_by_id( $item_id );
				
			// CHECK if containing Box is Declined.
			$box_fk = $item_details->Box_id_FK;

			$return_check = $wpdb->get_row(
						"SELECT
						    Item.return_id as return_id,
						    Ret.return_status_id as return_status
						FROM
						    ".$wpdb->prefix."wpsc_epa_return_items Item
						JOIN ".$wpdb->prefix."wpsc_epa_return Ret ON
						    Ret.id = Item.return_id
						WHERE
						    Ret.return_status_id <> " . $decline_cancelled_term_id . 
						" AND 
							Ret.return_status_id <> " . $decline_complete_term_id . 
						" AND
							Item.box_id = '" .  $box_fk . "'");	

			
			if( $return_check->return_id != null ) {
				
				$num = $return_check->return_id;	
	            $str_length = 7;	
	            $return_id = substr("000000{$num}", -$str_length);	
	            
	            $box_link = '<a href="'.$subfolder_path.'/wp-admin/admin.php?page=boxdetails&pid=boxsearch&id='.$item_id.'" >'.$item_id.'</a>';
	            
				$return_info['item_error'] = 'Box '.$box_link.' already in Return ';
				$return_info['return_id'] = $return_id;
				
				return $return_info;
			}
		    
		    return $return_info;
	    }

        /**
         * Archive Restore Request
         * @return true
         */
         
        public static function restore_archive($ticket_id)
        {
			global $wpdb;
			
//$folderdocinfo_array = array();
$folderdocinfo_file_array = array();

//Reset review_complete_timestamp to current time
$get_initial_review_complete_timestamp = $wpdb->get_row("SELECT id FROM " . $wpdb->prefix . "wpsc_ticketmeta WHERE meta_key = 'review_complete_timestamp' AND ticket_id = '".$ticket_id."'");
$initial_review_complete_timestamp = $get_initial_review_complete_timestamp->id;

$data_update_timestamp = array('meta_value' => time());
$data_where_timestamp = array('id' => $initial_review_complete_timestamp);
$wpdb->update($wpdb->prefix . 'wpsc_ticketmeta', $data_update_timestamp, $data_where_timestamp);

//BEGIN RESTORING DATA FROM ARCHIVE
$get_related_boxes = $wpdb->get_results("SELECT id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE ticket_id = '".$ticket_id."'");

foreach ($get_related_boxes as $relatedbox) {

$get_related_folderdocinfo = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files_archive WHERE box_id = '".$relatedbox->id."'");


foreach ($get_related_folderdocinfo as $folderdocinfofile) {

$folderdocinfofile_id = $folderdocinfofile->id;
array_push($folderdocinfo_file_array, $folderdocinfofile_id);

$folderdocinfofile_parent = $folderdocinfofile->parent_id;

//moved from folderdocinfo
$folderdocinfofile_author = $folderdocinfofile->author;
$folderdocinfofile_addressee = $folderdocinfofile->addressee;
$folderdocinfofile_site_name = $folderdocinfofile->site_name;
$folderdocinfofile_siteid = $folderdocinfofile->siteid;
$folderdocinfofile_close_date = $folderdocinfofile->close_date;
$folderdocinfofile_box_id = $folderdocinfofile->box_id;
$folderdocinfofile_essential_record = $folderdocinfofile->essential_record;
$folderdocinfofile_folder_identifier= $folderdocinfofile->folder_identifier;
$folderdocinfofile_date_created = $folderdocinfofile->date_created;
$folderdocinfofile_date_updated = $folderdocinfofile->date_updated;
$folderdocinfofile_folderdocinfofile_id = $folderdocinfofile->folderdocinfofile_id;
$folderdocinfofile_DOC_REGID = $folderdocinfofile->DOC_REGID;
$folderdocinfofile_attachment = $folderdocinfofile->attachment;
$folderdocinfofile_file_name = $folderdocinfofile->file_name;
$folderdocinfofile_object_key = $folderdocinfofile->object_key;
$folderdocinfofile_object_location = $folderdocinfofile->object_location;
$folderdocinfofile_source_file_location= $folderdocinfofile->source_file_location;
$folderdocinfofile_file_object_id = $folderdocinfofile->file_object_id;
$folderdocinfofile_file_size = $folderdocinfofile->file_size;
  
$folderdocinfofile_binary_filepath = $folderdocinfofile->binary_filepath;
$folderdocinfofile_text_filepath = $folderdocinfofile->text_filepath;
  
$folderdocinfofile_title = $folderdocinfofile->title;
$folderdocinfofile_date = $folderdocinfofile->date;
$folderdocinfofile_description = $folderdocinfofile->description;
$folderdocinfofile_tags = $folderdocinfofile->tags;
$folderdocinfofile_relation_part = $folderdocinfofile->relation_part;
$folderdocinfofile_relation_part_of = $folderdocinfofile->relation_part_of;
$folderdocinfofile_record_type = $folderdocinfofile->record_type;
$folderdocinfofile_access_restriction = $folderdocinfofile->access_restriction;
$folderdocinfofile_specific_access_restriction = $folderdocinfofile->specific_access_restriction;
$folderdocinfofile_use_restriction = $folderdocinfofile->use_restriction;
$folderdocinfofile_specific_use_restriction = $folderdocinfofile->specific_use_restriction;
$folderdocinfofile_rights_holder = $folderdocinfofile->rights_holder;
$folderdocinfofile_source_format = $folderdocinfofile->source_format;
$folderdocinfofile_source_dimensions = $folderdocinfofile->source_dimensions;
$folderdocinfofile_validation = $folderdocinfofile->validation;
$folderdocinfofile_validation_user_id = $folderdocinfofile->validation_user_id;
$folderdocinfofile_qa_user_id = $folderdocinfofile->qa_user_id;
$folderdocinfofile_rescan = $folderdocinfofile->rescan;
$folderdocinfofile_unauthorized_destruction = $folderdocinfofile->unauthorized_destruction;
$folderdocinfofile_freeze = $folderdocinfofile->freeze;
$folderdocinfofile_damaged = $folderdocinfofile->damaged;
$folderdocinfofile_index_level = $folderdocinfofile->index_level;
$folderdocinfofile_ecms_delete_timestamp = $folderdocinfofile->ecms_delete_timestamp;
$folderdocinfofile_ecms_delete_comment = $folderdocinfofile->ecms_delete_comment;

$folderdocinfofile_program_area = $folderdocinfofile->program_area;
$folderdocinfofile_lan_id = $folderdocinfofile->lan_id;
$folderdocinfofile_lan_id_details = $folderdocinfofile->lan_id_details;

				$wpdb->insert(
					$wpdb->prefix . 'wpsc_epa_folderdocinfo_files',
					array(
'id' => $folderdocinfofile_id,
'parent_id' => $folderdocinfofile_parent,
'box_id' => $folderdocinfofile_box_id,
'folderdocinfofile_id' => $folderdocinfofile_folderdocinfofile_id,
'DOC_REGID' => $folderdocinfofile_DOC_REGID,
'attachment' => $folderdocinfofile_attachment,
'file_name' => $folderdocinfofile_file_name,
'object_key' => $folderdocinfofile_object_key,
'object_location' => $folderdocinfofile_object_location,
'source_file_location' => $folderdocinfofile_source_file_location,
'file_object_id' => $folderdocinfofile_file_object_id,
'file_size' => $folderdocinfofile_file_size,
'binary_filepath' => $folderdocinfofile_binary_filepath,
'text_filepath' => $folderdocinfofile_text_filepath,                      
'title' => $folderdocinfofile_title,
'date' => $folderdocinfofile_date,
'description' => $folderdocinfofile_description,
'tags' => $folderdocinfofile_tags,
'relation_part' => $folderdocinfofile_relation_part,
'relation_part_of' => $folderdocinfofile_relation_part_of,
'record_type' => $folderdocinfofile_record_type,
'access_restriction' => $folderdocinfofile_access_restriction,
'specific_access_restriction' => $folderdocinfofile_specific_access_restriction,
'use_restriction' => $folderdocinfofile_use_restriction,
'specific_use_restriction' => $folderdocinfofile_specific_use_restriction,
'rights_holder' => $folderdocinfofile_rights_holder,
'source_format' => $folderdocinfofile_source_format,
'source_dimensions' => $folderdocinfofile_source_dimensions,
'validation' => $folderdocinfofile_validation,
'validation_user_id' => $folderdocinfofile_validation_user_id,
'qa_user_id' => $folderdocinfofile_qa_user_id,
'rescan' => $folderdocinfofile_rescan,
'unauthorized_destruction' => $folderdocinfofile_unauthorized_destruction,
'freeze' => $folderdocinfofile_freeze,
'damaged' => $folderdocinfofile_damaged,
'index_level' => $folderdocinfofile_index_level,
'ecms_delete_timestamp' => $folderdocinfofile_ecms_delete_timestamp,
'ecms_delete_comment' => $folderdocinfofile_ecms_delete_comment,
'author' => $folderdocinfofile_author,
'addressee' => $folderdocinfofile_addressee,
'site_name' => $folderdocinfofile_site_name,
'siteid' => $folderdocinfofile_siteid,
'close_date' => $folderdocinfofile_close_date,
'essential_record' => $folderdocinfofile_essential_record,
'folder_identifier' => $folderdocinfofile_folder_identifier,
'date_created' => $folderdocinfofile_date_created,
'date_updated' => $folderdocinfofile_date_updated,

'program_area' => $folderdocinfofile_program_area,
'lan_id' => $folderdocinfofile_lan_id,
'lan_id_details' => $folderdocinfofile_lan_id_details
					)
				);

}

}

foreach ($folderdocinfo_file_array as $key => $value) {

//Remove from wpsc_epa_folderdocinfo_files
$wpdb->delete( $wpdb->prefix . 'wpsc_epa_folderdocinfo_files_archive', array( 'id' => $value) );
}
return 'true';

        }


        /**
         * Archive Request
         * @return true
         */
         
        public static function send_to_archive($ticket_id)
        {
			global $wpdb;
			
//$folderdocinfo_array = array();
$folderdocinfo_file_array = array();

$get_related_boxes = $wpdb->get_results("SELECT id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE ticket_id = '".$ticket_id."'");

foreach ($get_related_boxes as $relatedbox) {

$get_related_folderdocinfo = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE box_id = '".$relatedbox->id."'");

foreach ($get_related_folderdocinfo as $folderdocinfofile) {

$folderdocinfofile_id = $folderdocinfofile->id;
array_push($folderdocinfo_file_array, $folderdocinfofile_id);

$folderdocinfofile_parent = $folderdocinfofile->parent_id;

//moved from folderdocinfo
$folderdocinfofile_author = $folderdocinfofile->author;
$folderdocinfofile_addressee = $folderdocinfofile->addressee;
$folderdocinfofile_site_name = $folderdocinfofile->site_name;
$folderdocinfofile_siteid = $folderdocinfofile->siteid;
$folderdocinfofile_close_date = $folderdocinfofile->close_date;
$folderdocinfofile_box_id = $folderdocinfofile->box_id;
$folderdocinfofile_essential_record = $folderdocinfofile->essential_record;
$folderdocinfofile_folder_identifier= $folderdocinfofile->folder_identifier;
$folderdocinfofile_date_created = $folderdocinfofile->date_created;
$folderdocinfofile_date_updated = $folderdocinfofile->date_updated;
$folderdocinfofile_folderdocinfofile_id = $folderdocinfofile->folderdocinfofile_id;
$folderdocinfofile_DOC_REGID = $folderdocinfofile->DOC_REGID;
$folderdocinfofile_attachment = $folderdocinfofile->attachment;
$folderdocinfofile_file_name = $folderdocinfofile->file_name;
$folderdocinfofile_object_key = $folderdocinfofile->object_key;
$folderdocinfofile_object_location = $folderdocinfofile->object_location;
$folderdocinfofile_source_file_location= $folderdocinfofile->source_file_location;
$folderdocinfofile_file_object_id = $folderdocinfofile->file_object_id;
$folderdocinfofile_file_size = $folderdocinfofile->file_size;
$folderdocinfofile_binary_filepath = $folderdocinfofile->binary_filepath;
$folderdocinfofile_text_filepath = $folderdocinfofile->text_filepath;
$folderdocinfofile_title = $folderdocinfofile->title;
$folderdocinfofile_date = $folderdocinfofile->date;
$folderdocinfofile_description = $folderdocinfofile->description;
$folderdocinfofile_tags = $folderdocinfofile->tags;
$folderdocinfofile_relation_part = $folderdocinfofile->relation_part;
$folderdocinfofile_relation_part_of = $folderdocinfofile->relation_part_of;
$folderdocinfofile_record_type = $folderdocinfofile->record_type;
$folderdocinfofile_access_restriction = $folderdocinfofile->access_restriction;
$folderdocinfofile_specific_access_restriction = $folderdocinfofile->specific_access_restriction;
$folderdocinfofile_use_restriction = $folderdocinfofile->use_restriction;
$folderdocinfofile_specific_use_restriction = $folderdocinfofile->specific_use_restriction;
$folderdocinfofile_rights_holder = $folderdocinfofile->rights_holder;
$folderdocinfofile_source_format = $folderdocinfofile->source_format;
$folderdocinfofile_source_dimensions = $folderdocinfofile->source_dimensions;
$folderdocinfofile_validation = $folderdocinfofile->validation;
$folderdocinfofile_validation_user_id = $folderdocinfofile->validation_user_id;
$folderdocinfofile_qa_user_id = $folderdocinfofile->qa_user_id;
$folderdocinfofile_rescan = $folderdocinfofile->rescan;
$folderdocinfofile_unauthorized_destruction = $folderdocinfofile->unauthorized_destruction;
$folderdocinfofile_freeze = $folderdocinfofile->freeze;
$folderdocinfofile_damaged = $folderdocinfofile->damaged;
$folderdocinfofile_index_level = $folderdocinfofile->index_level;
$folderdocinfofile_ecms_delete_timestamp = $folderdocinfofile->ecms_delete_timestamp;
$folderdocinfofile_ecms_delete_comment = $folderdocinfofile->ecms_delete_comment;

$folderdocinfofile_program_area = $folderdocinfofile->program_area;
$folderdocinfofile_lan_id = $folderdocinfofile->lan_id;
$folderdocinfofile_lan_id_details = $folderdocinfofile->lan_id_details;

				$wpdb->insert(
					$wpdb->prefix . 'wpsc_epa_folderdocinfo_files_archive',
					array(
'id' => $folderdocinfofile_id,
'parent_id' => $folderdocinfofile_parent,
'box_id' => $folderdocinfofile_box_id,
'folderdocinfofile_id' => $folderdocinfofile_folderdocinfofile_id,
'DOC_REGID' => $folderdocinfofile_DOC_REGID,
'attachment' => $folderdocinfofile_attachment,
'file_name' => $folderdocinfofile_file_name,
'object_key' => $folderdocinfofile_object_key,
'object_location' => $folderdocinfofile_object_location,
'source_file_location' => $folderdocinfofile_source_file_location,
'file_object_id' => $folderdocinfofile_file_object_id,
'file_size' => $folderdocinfofile_file_size,
'binary_filepath' => $folderdocinfofile_binary_filepath,                      
'text_filepath' => $folderdocinfofile_text_filepath,                      
'title' => $folderdocinfofile_title,
'date' => $folderdocinfofile_date,
'description' => $folderdocinfofile_description,
'tags' => $folderdocinfofile_tags,
'relation_part' => $folderdocinfofile_relation_part,
'relation_part_of' => $folderdocinfofile_relation_part_of,
'record_type' => $folderdocinfofile_record_type,
'access_restriction' => $folderdocinfofile_access_restriction,
'specific_access_restriction' => $folderdocinfofile_specific_access_restriction,
'use_restriction' => $folderdocinfofile_use_restriction,
'specific_use_restriction' => $folderdocinfofile_specific_use_restriction,
'rights_holder' => $folderdocinfofile_rights_holder,
'source_format' => $folderdocinfofile_source_format,
'source_dimensions' => $folderdocinfofile_source_dimensions,
'validation' => $folderdocinfofile_validation,
'validation_user_id' => $folderdocinfofile_validation_user_id,
'qa_user_id' => $folderdocinfofile_qa_user_id,
'rescan' => $folderdocinfofile_rescan,
'unauthorized_destruction' => $folderdocinfofile_unauthorized_destruction,
'freeze' => $folderdocinfofile_freeze,
'damaged' => $folderdocinfofile_damaged,
'index_level' => $folderdocinfofile_index_level,
'ecms_delete_timestamp' => $folderdocinfofile_ecms_delete_timestamp,
'ecms_delete_comment' => $folderdocinfofile_ecms_delete_comment,
'author' => $folderdocinfofile_author,
'addressee' => $folderdocinfofile_addressee,
'site_name' => $folderdocinfofile_site_name,
'siteid' => $folderdocinfofile_siteid,
'close_date' => $folderdocinfofile_close_date,
'essential_record' => $folderdocinfofile_essential_record,
'folder_identifier' => $folderdocinfofile_folder_identifier,
'date_created' => $folderdocinfofile_date_created,
'date_updated' => $folderdocinfofile_date_updated,

'program_area' => $folderdocinfofile_program_area,
'lan_id' => $folderdocinfofile_lan_id,
'lan_id_details' => $folderdocinfofile_lan_id_details
					)
				);

}

}


	
/*
foreach ($folderdocinfo_array as $key => $value) {

//Remove from wpsc_epa_folderdocinfo
$wpdb->delete( $wpdb->prefix . 'wpsc_epa_folderdocinfo', array( 'id' => $value) );
}
*/

foreach ($folderdocinfo_file_array as $key => $value) {

//Remove from wpsc_epa_folderdocinfo_files
$wpdb->delete( $wpdb->prefix . 'wpsc_epa_folderdocinfo_files', array( 'id' => $value) );
}
return 'true';

        }
        
        /**
         * Convert Ticket ID to Request ID
         * @return id Request ID
         */
         
        public static function ticket_to_request_id($ticket_id)
        {

$str_length = 7;
$padded_request_id = substr("000000{$ticket_id}", -$str_length);

return $padded_request_id;

        }

        /**
         * Help text tooltip function
         * @return help text
         */
         
        public static function helptext_tooltip($post_name)
        {
		   	global $wpdb;

			$get_post_details = $wpdb->get_row("SELECT a.post_content 
			FROM ".$wpdb->prefix."posts a 
			INNER JOIN ".$wpdb->prefix."term_relationships b ON a.id = b.object_id 
			INNER JOIN ".$wpdb->prefix."term_taxonomy c ON b.term_taxonomy_id = c.term_taxonomy_id 
			INNER JOIN ".$wpdb->prefix."terms d ON c.term_id = d.term_id WHERE a.post_status = 'publish' AND
			d.slug = 'help-messages' AND a.post_name = '" . $post_name . "'");
			$post_details_content = $get_post_details->post_content; 

return $post_details_content;

        }
        
        /**
         * Determine if request is active or archived
         * @return boolean
         */
         
        public static function request_status($request_id)
        {
		   	global $wpdb;

			$get_request_details = $wpdb->get_row("SELECT active 
			FROM ".$wpdb->prefix."wpsc_ticket
		    WHERE request_id = '" . $request_id . "'");
			$request_status = $get_request_details->active; 

        return $request_status;

        }
        
        /**
         * Determine if request is active or archived
         * @return boolean
         */
         
        public static function ticket_active($ticket_id)
        {
		   	global $wpdb;

			$get_ticket_details = $wpdb->get_row("SELECT active 
			FROM ".$wpdb->prefix."wpsc_ticket
		    WHERE id = '" . $ticket_id . "'");
			$ticket_active = $get_ticket_details->active; 

        return $ticket_active;

        }
        
        /**
         * Archive audit log information
         * @return true
         */
         
        public static function audit_log_backup($ticket_id)
        {
global $current_user, $wpscfunction, $wpdb;

include_once(implode("/", (explode("/", WPPATT_UPLOADS, -3))).'/wp/wp-load.php');
include_once(implode("/", (explode("/", WPPATT_UPLOADS, -3))).'/wp/wp-admin/includes/image.php');
include_once(implode("/", (explode("/", WPPATT_UPLOADS, -3))).'/wp/wp-admin/includes/media.php');
include_once(implode("/", (explode("/", WPPATT_UPLOADS, -3))).'/wp/wp-admin/includes/file.php');

if (!function_exists('strip_tags_deep'))   {
function strip_tags_deep($value)
{
  return is_array($value) ?
    array_map('strip_tags_deep', $value) :
    strip_tags(preg_replace( "/\r|\n/", "", $value ));
}
}

if (!function_exists('add_quotes'))   {
function add_quotes($str) {
    return sprintf('"%s"', $str);
}
}
                       $converted_request_id = self::convert_request_db_id($ticket_id);
                       $file = 'log_backup'; // csv file name
                       $pre_results = $wpdb->get_results("
SELECT 
rel.post_id as id,
posts.post_date as date,
posts.post_content as content
    FROM " . $wpdb->prefix . "posts AS posts
        LEFT JOIN " . $wpdb->prefix . "postmeta AS rel ON 
            posts.ID = rel.post_id
        LEFT JOIN " . $wpdb->prefix . "postmeta AS rel2 ON
            posts.ID = rel2.post_id
    WHERE
        posts.post_type = 'wpsc_ticket_thread' AND
        posts.post_status = 'publish' AND 
        rel2.meta_key = 'thread_type' AND
        rel2.meta_value = 'log' AND
        rel.meta_key = 'ticket_id' AND
        rel.meta_value =" .$ticket_id ."
    ORDER BY
    posts.post_date DESC",ARRAY_A);
    
                       $results = strip_tags_deep($pre_results);
// CONVERT TO EST
foreach ($results as $key => &$value) {
   $date = new DateTime($value['date'], new DateTimeZone('UTC'));
   $date->setTimezone(new DateTimeZone('America/New_York'));
   $value['date'] = $date->format('m-d-Y h:i:s a');
   $date_format = $date->format('Y-m-d_h-i-sa');
}

                        // get column names
                        $columnNamesList = ['ID','Date','Content'];


                        foreach ( $columnNamesList as $column_name ) {
                            $csv_output.=$column_name.",";
                        }


                        // remove last additional comma 
                        $csv_output = substr($csv_output,0,strlen($csv_output)-1);

                        // start dumping csv rows in new line
                        $csv_output.="\n";

                       if(count($results) > 0){
                          foreach($results as $result){
                          $result = array_values($result);
                          $result =  implode(',', array_map('add_quotes', $result));
                          
                          $csv_output .= htmlspecialchars_decode($result)."\n";
                        }
                      
                      
                      $filename = $converted_request_id."_".$file."_".$date_format.".csv";
                      $backup_file  = WPPATT_UPLOADS."/backups/audit/".$filename;
                      
                      //Direct File Download
                      //header("Content-type: application/vnd.ms-excel");
                      //header("Content-disposition: csv" . date("Y-m-d") . ".csv");
                      //header( "Content-disposition: filename=".$filename.".csv");
                      //header("Pragma: no-cache");
                      //header("Expires: 0");
                      //print $csv_output;
                      

                      file_put_contents($backup_file, $csv_output);
                        }

                      /* Check the file type and fetch the mime of the file uploaded */
                      $wp_filetype = wp_check_filetype( $filename, null );

                      /* Attachment information to be saved in the database */
                      $attachment = array(
                        'post_mime_type' => $wp_filetype['type'], /* Mime type */
                        'post_title' => sanitize_file_name( $filename ), /* Title */
                        'post_content' => '', /* Content */
                        'post_status' => 'inherit' /* Status */
                      );

                      /* Inserts the attachment and returns the attachment ID */
                      $attach_id = wp_insert_attachment( $attachment, $backup_file );

                      /* Generates metadata for the attachment */
                      $attach_data = wp_generate_attachment_metadata( $attach_id, $backup_file );

                      /* Update metadata for the attachment */
                      wp_update_attachment_metadata( $attach_id, $attach_data );

                      /**
                       * To display folder in the media plugin, the code is added as follows
                       * File: pattracking/includes/class-wppatt-request-approval-widget.php
                       * Filter: add_filter( 'media_folder_list', __CLASS__ . '::media_folder_list_callback', 10, 1 );
                       * Code in the filter : array_push( $folders, array( 'name' => 'backups' ) ); (Added the name of the folder in the list).
                       */

                      /* Add meta with key 'Folder' and name of the folder as value */
                      update_post_meta( $attach_id, 'folder', 'backups' );

                      /**
                       * Delete attachment
                       * wp_delete_attachment( $attach_id, $force_delete );
                       * $attach_id: Attachment ID
                       * $force_delete: true ( Skips trash and directly deletes the attachment ), false ( moves attachment to trash )
                       */

// DELETE ROWS
$get_log = $wpdb->get_results("
SELECT 
rel.post_id as id,
posts.post_date as date,
posts.post_content as content
    FROM " . $wpdb->prefix . "posts AS posts
        LEFT JOIN " . $wpdb->prefix . "postmeta AS rel ON 
            posts.ID = rel.post_id
        LEFT JOIN " . $wpdb->prefix . "postmeta AS rel2 ON
            posts.ID = rel2.post_id
    WHERE
        posts.post_type = 'wpsc_ticket_thread' AND
        posts.post_status = 'publish' AND 
        rel2.meta_key = 'thread_type' AND
        rel2.meta_value = 'log' AND
        rel.meta_key = 'ticket_id' AND
        rel.meta_value =" . $ticket_id ."
    ORDER BY
    posts.post_date DESC");
    
foreach($get_log as $info){
    $log_id = $info->id;
    
$table_post = $wpdb->prefix."posts";
$wpdb->delete( $table_post, array( 'id' => $log_id ) );

$table_post_meta = $wpdb->prefix."postmeta";
$wpdb->delete( $table_post_meta, array( 'post_id' => $log_id ) );
}


        return true;

        }
        
        /**
         * Identify the type of PATT ID
         * @return id type
         */
         
        public static function patt_id_type($patt_id)
        {
$patt_type = '';
if (strpos($patt_id, 'R-') !== false) {
$patt_type = 'recall';
} elseif ((strpos($patt_id, 'D-') !== false) || (strpos($patt_id, 'RTN-') !== false)) {
$patt_type = 'decline';
//No leter check plus length check
} elseif (strlen($patt_id) == 7 && !preg_match('/[A-Za-z]/', $patt_id)) {
$patt_type = 'request';
//No leter check plus dash check
} elseif (substr_count($patt_id, '-') == 1 && !preg_match('/[A-Za-z]/', $patt_id)) {
$patt_type = 'box';
//No leter check plus dash check
} elseif (substr_count($patt_id, '-') == 3 && !preg_match('/[A-Za-z]/', $patt_id)) {
$patt_type = 'folder_file';
//Attachments check
} elseif (substr_count($patt_id, '-') == 4 && preg_match('/^[0-9]{7}-[0-9]{1,3}-[0-9]{2}-[0-9]{1,3}(-[a][0-9]{1,4})?$/ ', $patt_id)) {
$patt_type = 'folder_file';
}

return $patt_type;
        }
            
        /**
         * Insert new notification to one or many users
         * @return pm id
         */
         
        public static function insert_new_notification($post_name, $array_of_users, $patt_id, $data = [], $email = 0, $decline_reason_msg = '') {

			global $wpdb;
			
			// Get post details
			$get_post_details = $wpdb->get_row("SELECT a.post_title, a.post_content FROM ".$wpdb->prefix."posts a
			INNER JOIN ".$wpdb->prefix."term_relationships b ON a.id = b.object_id 
			INNER JOIN ".$wpdb->prefix."term_taxonomy c ON b.term_taxonomy_id = c.term_taxonomy_id 
			INNER JOIN ".$wpdb->prefix."terms d ON c.term_id = d.term_id
			WHERE a.post_status = 'publish' AND d.slug = 'email-messages' AND a.post_name = '" . $post_name . "'");
			$post_details_subject = $get_post_details->post_title;
			$post_details_content = $get_post_details->post_content;
			
			if($post_details_subject == '' || $post_details_content == '')  {
				return 'Invalid Message Type'; 
			} else {
			    
				if ($patt_id != '') {
				// Determine PATT ID Type: Request, Box, Folder/File, Recall, Decline
				
					$patt_type = Patt_Custom_Func::patt_id_type($patt_id);
					
					switch ($patt_type) {
					    case "recall":
					        $patt_url = admin_url( 'admin.php?page=recalldetails&id='.$patt_id );
					        break;
					    case "decline":
//					        $patt_url = admin_url( 'admin.php?page=returndetails&id='.$patt_id );
					        $patt_url = admin_url( 'admin.php?page=declinedetails&id='.$patt_id );					        
					        break;
					    case "request":
					        $patt_url = admin_url( 'admin.php?page=wpsc-tickets&id='.$patt_id );
					        break;
					    case "box":
					        $patt_url = admin_url( 'admin.php?page=boxdetails&id='.$patt_id );
					        break;
					    case "folder_file":
					        $patt_url = admin_url( 'admin.php?page=filedetails&id='.$patt_id );
					        break;
					}
					
					// Declare Replacement variables
					$item_type = '';
					$action_initiated_by = '';
					$item_id = '';
					$decline_reason = '';
					
					if(!empty($data)) {
					// Data Switch
					foreach( $data as $key => $val ) {
						switch ($key) {
						    case "invalid_site_id":
						        $invalid_site_id = $val;
						        break;
							case "item_type":
						        $item_type = $val;
						        break;
						    case "action_initiated_by":
						        $action_initiated_by = $val;
						        break;
						    case "item_id":
						        if( is_array($val) ) {
							        $item_id = implode( ', ', $val );
						        } else {
							        $item_id = $val;
						        }
						        break;  
						    case "decline_reason":
						        $decline_reason = $val;
						        break;     
						        
						}
					}
					}
					
					$tags = array( '%ID%', '%URL%', '%ITEM_TYPE%', '%ITEM_ID%', '%INITIATED_BY%', '%DECLINE_REASON%', '%INVALID_SITE_ID%' );
					$replacement = array( $patt_id, $patt_url, $item_type, $item_id, $action_initiated_by, $decline_reason, $invalid_site_id );
					
					$post_details_subject = str_replace( $tags, $replacement, $get_post_details->post_title );
					$post_details_content = str_replace( $tags, $replacement, $get_post_details->post_content );
				 
				}
				
				$table_pm = $wpdb->prefix."pm";
				$wpdb->insert($table_pm, array(
					'identifier' => $patt_id,
				    'subject' => $post_details_subject,
				    'content' => $post_details_content,
				    'date' => current_time('mysql', 1)
				));
				
				$insert_id = $wpdb->insert_id;
              
              	/*// Find the requestor group that the current requestor is associated with and get user ids in requestor group
                $requestor_group_array = Patt_Custom_Func::get_requestor_group($user_id);
                    
                // Combine the requestor group array to the array_of_users
                $array_of_users = array_merge($array_of_users,$requestor_group_array);*/
				
				$wp_user_id_array = Patt_Custom_Func::translate_user_id($array_of_users,'wp_user_id');
				
				$wp_user_id_array = array_unique( $wp_user_id_array );
				
				foreach ($wp_user_id_array as $wp_user_id) {

                    $table_pm_users = $wpdb->prefix."pm_users";
					//For each username
					$wpdb->insert($table_pm_users, array(
					    'pm_id' => $insert_id,
					    'recipient' => $wp_user_id,
					    'viewed' => 0,
					    'deleted' => 1
					));
					
					// Send email notification
					$option = get_option( 'rwpm_option' );
					
					// send email to user
					if ( $option['email_enable'] && $email == 1) {
						$sender = $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE user_login = 'admin' LIMIT 1" );
		
						// replace tags with values
						$tags = array( '%TITLE%','%BODY%','%BLOG_NAME%', '%BLOG_ADDRESS%', '%SENDER%', '%INBOX_URL%' );
						$replacement = array( $post_details_subject, $post_details_content, get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ), $sender, admin_url( 'admin.php?page=rwpm_inbox' ) );
		
						$email_name = str_replace( $tags, $replacement, $option['email_name'] );
						$email_address = str_replace( $tags, $replacement, $option['email_address'] );
						$email_subject = str_replace( $tags, $replacement, $option['email_subject'] );
						$email_body = str_replace( $tags, $replacement, $option['email_body'] );
		
						// set default email from name and address if missed
						if ( empty( $email_name ) )
							$email_name = get_bloginfo( 'name' );
		
						if ( empty( $email_address ) )
							$email_address = get_bloginfo( 'admin_email' );
		
						$email_subject = strip_tags( $email_subject );
						if ( get_magic_quotes_gpc() )
						{
							$email_subject = stripslashes( $email_subject );
							$email_body = stripslashes( $email_body );
						}
						$email_body = nl2br( $email_body );
		
						$recipient_email = $wpdb->get_var( "SELECT user_email from $wpdb->users WHERE ID = $wp_user_id" );
						$mailtext = "<html><head><title>$email_subject</title></head><body>$email_body <br><br> $decline_reason_msg</body></html>";
		
						// set headers to send html email
						$headers = "To: $recipient_email\r\n";
						$headers .= "From: $email_name <$email_address>\r\n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) . "\r\n";
		
						wp_mail( $recipient_email, $email_subject, $mailtext, $headers );
					}
				
				}
			
				return $insert_id;
			}
        }	    


// Notifications for Comments - Determine agents assigned.
public static function agents_assigned_request( $ticket_id ) {
	global $wpdb;
  
	//OBTAIN BOX IDs

	$get_box_ids = $wpdb->get_results("SELECT id from ".$wpdb->prefix."wpsc_epa_boxinfo WHERE ticket_id = " . $ticket_id);
	
	$user_id_array = array();

	foreach ($get_box_ids as $item) {
		$box_id = $item->id;
		$get_user_ids = $wpdb->get_results("SELECT user_id from ".$wpdb->prefix."wpsc_epa_boxinfo_userstatus where box_id = " . $box_id);

		foreach ($get_user_ids as $item) {
		    $user_id = $item->user_id;
		    array_push($user_id_array, $user_id);
		}
	}

	$user_id_final = array_values(array_unique($user_id_array));

	return $user_id_final;
}

// Notifications for Recall Comments - Determine agents assigned.
public static function agents_assigned_recall( $recall_id ) {
	global $wpdb;

	$user_id_array = array();
	$table = $wpdb->prefix . 'wpsc_epa_recallrequest_users';
	
 	$get_user_ids = $wpdb->get_results("SELECT user_id from " . $table . " where recallrequest_id = " . $recall_id );
	
	foreach ($get_user_ids as $item) {
	    $user_id = $item->user_id;
	    array_push($user_id_array, $user_id);
	}

	$user_id_final = array_values( array_unique( $user_id_array ) );

	return $user_id_final;
}


public static function get_wp_user_id_by_ticket_id( $ticket_id ) {
	global $wpdb;
    $id = (int)$ticket_id;
    $the_row = $wpdb->get_row("SELECT customer_email FROM {$wpdb->prefix}wpsc_ticket WHERE id = ".$id);
    
    //$the_user_row = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}users WHERE user_email = " . $the_row->customer_email );
    $the_user_row = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}users WHERE user_email = '" . $the_row->customer_email . "'" );
    
    $user_obj = get_user_by( 'email', $the_row->customer_email );
    
    //$the_user_row->id;
    $user_id_array = array($user_obj->ID);
    //$user_agent_id = self::translate_user_id( $user_id_array, 'agent_term_id');
    
    return $user_obj->ID;
}

public static function get_box_list_post_id_by_ticket_id( $ticket_id ) {
	global $wpdb;
    $id = (int)$ticket_id;
    
    $the_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpsc_ticketmeta WHERE ticket_id= %d AND meta_key = 'box_list_post_id'", $ticket_id ) );
    
    
    return $the_row->meta_value;
}


// Outbound ECMS Email Notification Function
	    public static function insert_ecms_notification( $folderfile_id, $comment, $object_ids ) {
		    global $wpdb;

$comment = 'Delete the following objects: <br />';
                        foreach($object_ids as $item) {
$comment .= '<strong>'.$object_ids.'</strong><br />';
                        }
$comment .= '<strong>Reason: </strong><br />'.$comment;

$comment .= ' <br />For more information <a href="'.admin_url( 'admin.php?pid=requestdetails&page=filedetails&id='.$folderfile_id ).'">Click here to go to the folder/file details page.</a><br /><br />
This email is sent automatically. Please don\'t reply.';

$recipient_email = 'ecms@epa.gov';

						if ( empty( $email_name ) )
							$email_name = get_bloginfo( 'name' );
		
						if ( empty( $email_address ) )
							$email_address = get_bloginfo( 'admin_email' );
                        
                        $email_subject = 'PATT ECMS DELETE REQUEST: #'.$folderfile_id;

						$mailtext = "<html><head><title>$email_subject</title></head><body>$comment</body></html>";
		
						// set headers to send html email
						$headers = "To: $recipient_email\r\n";
						$headers .= "From: $email_name <$email_address>\r\n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) . "\r\n";
					
					// Send email notification
					$option = get_option( 'rwpm_option' );
					
					// send email to user
					if ( $option['email_enable']) {		
					wp_mail( $recipient_email, $email_subject, $mailtext, $headers );
					}
		    
	    }
	    
// Notifications for Comments - Send emails out
	    public static function insert_new_comment_notification( $request_id, $comment, $user_ids, $bcc, $recall_id, $type ) {
		    global $wpdb;
		                
if(count($bcc) == 0) {
    $email_array = array();
} else {
    $email_array = $bcc;
}

$converted_request_id = Patt_Custom_Func::convert_request_db_id($request_id,$type);

if($type == 'comment') {
$comment .= ' <br />For more information <a href="'.admin_url( 'admin.php?page=wpsc-tickets&id='.$converted_request_id ).'">Click here to go to the request details page.</a><br /><br />
<a href="'.admin_url( 'admin.php?page=rwpm_inbox' ).'">Click here</a> to go to your inbox.

This email is sent automatically. Please don\'t reply.';

} elseif ($type == 'recall_comment') {
$comment .= ' <br />For more information <a href="'.admin_url( 'admin.php?page=recalldetails&id='.$recall_id ).'">Click here to go to the recall details page.</a><br /><br />
<a href="'.admin_url( 'admin.php?page=rwpm_inbox' ).'">Click here</a> to go to your inbox.

This email is sent automatically. Please don\'t reply.';
}


                        foreach($user_ids as $item) {
                           $additional_email = $wpdb->get_row( "SELECT user_email from ".$wpdb->prefix."users WHERE ID = " . $item ); 

                           array_push($email_array, $additional_email->user_email);
                        }
		   				
                        //foreach email 
                        
                        $email_array = array_unique( $email_array );
                        
                        foreach($email_array as $recipient_email) {
            
						if ( empty( $email_name ) )
							$email_name = get_bloginfo( 'name' );
		
						if ( empty( $email_address ) )
							$email_address = get_bloginfo( 'admin_email' );

if($type == 'comment') {
		                $email_subject = 'PATT New Comment: Request #'.Patt_Custom_Func::ticket_to_request_id($request_id);

} elseif ($type == 'recall_comment') {
		                $email_subject = 'PATT New Comment: Recall #'.$recall_id;
}

						$mailtext = "<html><head><title>$email_subject</title></head><body>$comment</body></html>";
		
						// set headers to send html email
						$headers = "To: $recipient_email\r\n";
						$headers .= "From: $email_name <$email_address>\r\n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) . "\r\n";
					
					// Send email notification
					$option = get_option( 'rwpm_option' );
					
					// send email to user
					if ( $option['email_enable']) {		
					wp_mail( $recipient_email, $email_subject, $mailtext, $headers );
					}
                        }
		    
	    }
	    
	    
	    // Checks if another folder has been recalled in box, 
	    // returns Array of the number of recalls with said box, and the saved_box_status (same for all).
	    // Accepts box_id foriegn key ( '23', '25', etc )
	    public static function existing_recall_box_status( $box_id ) {
		    global $wpdb;
		    
		    //Get term_ids for Recall status slugs
			$status_recall_denied_term_id = self::get_term_by_slug( 'recall-denied' );	 // 878
			$status_recall_cancelled_term_id = self::get_term_by_slug( 'recall-cancelled' ); //734
			$status_recall_complete_term_id = self::get_term_by_slug( 'recall-complete' ); //733
		    
		    $recall_saved_box_status = $wpdb->get_results(
			    "SELECT
				    saved_box_status 
				FROM " . $wpdb->prefix . "wpsc_epa_recallrequest
				WHERE box_id = '" . $box_id . "'
					AND ( 
							recall_status_id <> " . $status_recall_denied_term_id . " AND
							recall_status_id <> " . $status_recall_cancelled_term_id . " AND
							recall_status_id <> " . $status_recall_complete_term_id . "
						)"
			);
			
			$ret_arr['num'] = count( $recall_saved_box_status );
			
			if( $ret_arr['num'] == 0 ) {
				$ret_arr['saved_box_status'] = null;
			} else {
				$ret_arr['saved_box_status'] = $recall_saved_box_status[0]->saved_box_status;
			}
			
			return $ret_arr;
		}
	    
	    
	    
	    public static function item_in_recall( $item_id ) {
		    global $wpdb;
		    //, $type, $subfolder_path
 		   	
		   	
		   	
		   	$box_file_details = self::get_box_file_details_by_id($item_id);
			
			$details_array = json_decode(json_encode($box_file_details), true);
			
			
			if ( $details_array == false ) {
				$details_array['search_error'] = true;
				$details_array['searchByID'] = $item_id;
			} else {
				$details_array['search_error'] = false;
				$details_array['searchByID'] = $item_id;
			}
			
			// Set variables for search
			$is_folder_search = array_key_exists('Folderdoc_Info_id',$details_array);
			$details_array['in_recall'] = false;
			//$details_array['is_folder_search'] = $is_folder_search;
			$details_array['is_folder_search'] =  ($is_folder_search) ? 'true' : 'false';
			$details_array['is_folder_search_TEST'] =  ($is_folder_search) ? 'true' : 'false';
			$details_array['error_message'] = '';
			$db_null = -99999;
			
			//Get term_ids for Recall status slugs
			$status_recall_denied_term_id = self::get_term_by_slug( 'recall-denied' );	 // 878
			$status_recall_cancelled_term_id = self::get_term_by_slug( 'recall-cancelled' ); //734
			$status_recall_complete_term_id = self::get_term_by_slug( 'recall-complete' ); //733
			//Get term_ids for Box status slugs
			$status_box_pending_term_id = self::get_term_by_slug( 'pending' );	// 748
			$status_box_scanning_preparation_term_id = self::get_term_by_slug( 'scanning-preparation' );	// 672
			$status_box_scanning_digitization_term_id = self::get_term_by_slug( 'scanning-digitization' );	// 671	
			$status_box_q_a_term_id = self::get_term_by_slug( 'q-a' );	// 65	
			$status_box_digitized_not_validated_term_id = self::get_term_by_slug( 'closed' );	// 6	
			$status_box_ingestion_term_id = self::get_term_by_slug( 'ingestion' );	// 673	
			$status_box_validation_term_id = self::get_term_by_slug( 'verification' );	// 674	
			$status_box_rescan_term_id = self::get_term_by_slug( 're-scan' );	// 743	
			$status_box_completed_term_id = self::get_term_by_slug( 'completed' );	// 66	
			$status_box_destruction_approval_term_id = self::get_term_by_slug( 'destruction-approval' );	// 68	
			$status_box_destruction_of_source_term_id = self::get_term_by_slug( 'destruction-of-source' );
			$status_box_waiting_on_rlo_term_id = self::get_term_by_slug( 'waiting-on-rlo' );
			$status_box_dispositioned_term_id = self::get_term_by_slug( 'stored' );	// 67	
			
			
			// Check if item is currently in recall database 
			// Added: "WHERE recall_id <> -99999" in Jan 2021 
			$recall_rows = $wpdb->get_results(
			'SELECT 
				'.$wpdb->prefix.'wpsc_epa_recallrequest.id as id, 
			    '.$wpdb->prefix.'wpsc_epa_recallrequest.recall_id as recall_id,	
				'.$wpdb->prefix.'wpsc_epa_recallrequest.box_id as box_id, 
				boxinfo.box_id as display_box_id,
				boxinfo.box_destroyed as box_destroyed,
			    folderinfo.folderdocinfofile_id as dispay_folder_id,
				'.$wpdb->prefix.'wpsc_epa_recallrequest.folderdoc_id as folderdoc_id,
				'.$wpdb->prefix.'wpsc_epa_recallrequest.recall_status_id as status_id,
				'.$wpdb->prefix.'wpsc_epa_recallrequest.saved_box_status as saved_box_status
			FROM 
				'.$wpdb->prefix.'wpsc_epa_recallrequest 
			INNER JOIN 
					'.$wpdb->prefix.'wpsc_epa_boxinfo AS boxinfo 
				ON (
			                '.$wpdb->prefix.'wpsc_epa_recallrequest.box_id = boxinfo.id
				)
			LEFT JOIN 
					'.$wpdb->prefix.'wpsc_epa_folderdocinfo_files AS folderinfo 
				ON (
			                '.$wpdb->prefix.'wpsc_epa_recallrequest.folderdoc_id = folderinfo.id
				)
			WHERE recall_id <> -99999   	
			ORDER BY id ASC' );
			
			// Not needed as this is checked in the indiviual sections so that more details errors are returned. 
			//			WHERE wpqa_wpsc_epa_recallrequest.recall_status_id <> 733 
			//	AND wpqa_wpsc_epa_recallrequest.recall_status_id <> 734
			//	AND wpqa_wpsc_epa_recallrequest.recall_status_id <> 1
			
			$details_array['recall_rows'] = $recall_rows;
			
			// Box Search  
			if( !$is_folder_search ) {
				
				// if Box Destroyed, No recall allowed
				if( $details_array['box_destroyed'] == true ) {
					$details_array['error_message'] = 'Box Destroyed';
				} else { // if box not destroyed, check if it's been recalled
					
					
					// Search through all Recalls to determine if box has been recalled.
					foreach ($recall_rows as $item) {
						// Is Box Recalled?
						if( $details_array['box_id'] == $item->display_box_id && $item->folderdoc_id == $db_null && ($item->status_id != $status_recall_complete_term_id && $item->status_id != $status_recall_cancelled_term_id && $item->status_id != $status_recall_denied_term_id) ) {	
							$details_array['error'] = 'Found: '.$item->status_id.' - '.$details_array['error'];
							$details_array['in_recall'] = true;
							$details_array['in_recall_where'] = $item->recall_id;
							$details_array['error_message'] = 'Box Already Recalled';
							
							break;
						}
						
						// is a folder in the box recalled? //&& $item->folderdoc_id != $db_null
						if( $details_array['box_id'] == $item->display_box_id && $item->folderdoc_id <> $db_null && ($item->status_id != $status_recall_complete_term_id && $item->status_id != $status_recall_cancelled_term_id && $item->status_id != $status_recall_denied_term_id) ) {	
							$details_array['error'] = 'Found: '.$item->status_id.' - '.$details_array['error'];
							$details_array['in_recall'] = true;
							$details_array['in_recall_where'] = $item->recall_id;
							$details_array['error_message'] = 'A Folder/File in the Box Already Recalled';
							break;
						}
						
					}
					
			
					
					
					// if not recalled, check all folder/files inside of box for Destroyed Files
					if( $details_array['in_recall'] == false ) {
						
						// check if box exists
						if( $details_array['Box_id_FK'] == '' || $details_array['Box_id_FK'] == null ) {
							$details_array['error_message'] = 'Box Does Not Exist';
							$details_array['error'] = 'Box Does Not Exist';
						} else {
							
/*							// OLD: before dropping wpsc_epa_folderdocinfo table
							$folder_rows = $wpdb->get_results(
								'SELECT 
									folderinfo.id as id, 
								    folderinfo.folderdocinfo_id as display_folderdocinfo_id,
								    files.unauthorized_destruction as unauthorized_destruction
								FROM 
									'.$wpdb->prefix.'wpsc_epa_folderdocinfo as folderinfo
								JOIN 
									'.$wpdb->prefix.'wpsc_epa_folderdocinfo_files AS files ON files.folderdocinfo_id = folderinfo.folderdocinfo_id
								WHERE
								    folderinfo.box_id = '. $details_array['Box_id_FK'] .'
								   AND
								    files.unauthorized_destruction = 1
								ORDER BY id ASC'
							);
*/
							$folder_rows = $wpdb->get_results(
								'SELECT 
									files.id as id, 
								    files.folderdocinfofile_id as display_folderdocinfo_id,
								    files.unauthorized_destruction as unauthorized_destruction
								FROM 
									'.$wpdb->prefix.'wpsc_epa_folderdocinfo_files AS files 
								WHERE
								    files.box_id = '. $details_array['Box_id_FK'] .'
								   AND
								    files.unauthorized_destruction = 1
								ORDER BY id ASC'
							);
							
							if( $folder_rows ) {
								$list_of_destroyed_files = [];
						
								foreach( $folder_rows as $folder ) {
									$list_of_destroyed_files[] = $folder->display_folderdocinfo_id;
								}
								
								$details_array['error_message'] = 'Box Contains Destroyed Files';
								$details_array['error'] = 'Box Contains Destroyed Files';
								$details_array['destroyed_files'] = $list_of_destroyed_files;	
							}	
						}
					}
					
					
					// Check the box status to determine if box is recallable 
					switch( $details_array['box_status'] ) {
			
//			 			case 748: // Box Status: Pending
						case $status_box_pending_term_id: // Box Status: Pending
							//$details_array['error'] = 'Box Status Not Recallable';
							//$details_array['error_message'] = 'Recalls are not allowed until the Box status enters Scanning/Digitization.';
							$details_array['box_status_name'] = 'Pending';
							break;
//			 			case 672: // Box Status: Scanning Preperation
						case $status_box_scanning_preparation_term_id: // Box Status: Scanning Preperation
							//$details_array['error'] = 'Box Status Not Recallable';
							//$details_array['error_message'] = 'Recalls are not allowed until the Box status enters Scanning/Digitization.';
							$details_array['box_status_name'] = 'Scanning Preperation';
							break;
//			 			case 671: // Box Status: Scanning/Digitization
						case $status_box_scanning_digitization_term_id: // Box Status: Scanning/Digitization
							//$details_array['error'] = '';
							//$details_array['error_message'] = '';
							$details_array['box_status_name'] = 'Scanning/Digitization';
							break;
//			 			case 65: // Box Status: QA/QC
						case $status_box_q_a_term_id: // Box Status: QA/QC
							//$details_array['error'] = '';
							//$details_array['error_message'] = '';
							$details_array['box_status_name'] = 'QA/QC';
							break;
//			 			case 6: // Box Status: Digitized - Not Validated
						case $status_box_digitized_not_validated_term_id: // Box Status: Digitized - Not Validated
							//$details_array['error'] = '';
							//$details_array['error_message'] = '';
							$details_array['box_status_name'] = 'Digitized - Not Validated';
							break;
//			 			case 673: // Box Status: Ingestion
						case $status_box_ingestion_term_id: // Box Status: Ingestion
							//$details_array['error'] = '';
							//$details_array['error_message'] = '';
							$details_array['box_status_name'] = 'Ingestion';
							break;
//			 			case 674: // Box Status: Validation
						case $status_box_validation_term_id: // Box Status: Validation
							//$details_array['error'] = 'Box Status Not Recallable';
							//$details_array['error_message'] = 'Recalls are not allowed for Boxes in Validation to Re-Scan statuses.';
							$details_array['box_status_name'] = 'Validation';
							break;
//			 			case 743: // Box Status: Re-scan
						case $status_box_rescan_term_id: // Box Status: Re-scan
							//$details_array['error'] = 'Box Status Not Recallable';
							//$details_array['error_message'] = 'Recalls are not allowed for Boxes in Validation to Re-Scan statuses.';
							$details_array['box_status_name'] = 'Re-scan';
							break;
//			 			case 66: // Box Status: Completed
						case $status_box_completed_term_id: // Box Status: Completed
							//$details_array['error'] = '';
							//$details_array['error_message'] = '';
							$details_array['box_status_name'] = 'Completed/Dispositioned';
							break;
// 						case 68: // Box Status: Destruction Approval
						case $status_box_destruction_approval_term_id: // Box Status: Destruction Approval
							//$details_array['error'] = 'Box Status Not Recallable';
							//$details_array['error_message'] = 'Recalls are not allowed in the Destruction Approval status.';
							$details_array['box_status_name'] = 'Destruction Approved';
							break;
						case $status_box_destruction_of_source_term_id: // Box Status: Destruction Approval
							//$details_array['error'] = 'Box Status Not Recallable';
							//$details_array['error_message'] = 'Recalls are not allowed in the Destruction Approval status.';
							$details_array['box_status_name'] = 'Destruction of Source';
							break;
						case $status_box_waiting_on_rlo_term_id: // Box Status: Destruction Approval
							//$details_array['error'] = 'Box Status Not Recallable';
							//$details_array['error_message'] = 'Recalls are not allowed in the Destruction Approval status.';
							$details_array['box_status_name'] = 'Waiting on RLO';
							break;	
//						case 67: // Box Status: Dispositioned
						case $status_box_dispositioned_term_id: // Box Status: Dispositioned
							//$details_array['error'] = 'Box Status Not Recallable';
							//$details_array['error_message'] = 'Recalls are not allowed in the Dispositioned status.';
							$details_array['box_status_name'] = 'Dispositioned';
							break;
					}
					
				}
			} else { // Folder/File Search
				
				// if Folder / File  Unauthorized Destruction, No recall allowed
				if( $details_array['unauthorized_destruction'] == true ) {
					$details_array['error_message'] = 'Folder/File Unauthorized Destruction';
				} // if Folder/File not destroyed, check if it's been recalled 
				elseif ( $details_array['in_recall'] == false ) {
					foreach( $recall_rows as $item ) {
// 						if ($details_array['Folderdoc_Info_id'] == $item->dispay_folder_id && ($item->status_id != 733 && $item->status_id != 734 && $item->status_id != 878)) {
						if ($details_array['Folderdoc_Info_id'] == $item->dispay_folder_id && ($item->status_id != $status_recall_complete_term_id && $item->status_id != $status_recall_cancelled_term_id && $item->status_id != $status_recall_denied_term_id)) {		
							$details_array['error'] = 'Found: '.$item->dispay_folder_id.' - '.$details_array['error'];
							$details_array['in_recall'] = true;
							$details_array['in_recall_where'] = $item->recall_id;
							$details_array['error_message'] = 'Folder/File already Recalled';
						}
					}
				} 
				
				// if not destoryed && not recalled, check if containing box has been recalled
				if ( $details_array['in_recall'] == false && $details_array['error_message'] != 'Folder/File Unauthorized Destruction' ) { 
					// Search through all Recalls to determine if box has been recalled.
					foreach ($recall_rows as $item) {
						$details_array['Test'] = $item;
						// Is Box Recalled?

						if( $details_array['Box_id_FK'] == $item->box_id && $item->folderdoc_id == $db_null && ($item->status_id != $status_recall_complete_term_id && $item->status_id != $status_recall_cancelled_term_id && $item->status_id != $status_recall_denied_term_id)) {		
							$details_array['error'] = 'Found: '.$item->status_id.' - '.$details_array['error'];
							$details_array['in_recall'] = true;
							$details_array['in_recall_where'] = $item->recall_id;
							$details_array['error_message'] = 'Folder/File in Recalled Box';
							break;
						}
					}
					
				}
				
				// Check the status of the containing box to determine if it's recallable
				switch( $details_array['box_status'] ) {
					
//			 		case 748: // Box Status: Pending
					case $status_box_pending_term_id: // Box Status: Pending
						//$details_array['error'] = 'Containing Box Status Not Recallable';
						//$details_array['error_message'] = 'Recalls are not allowed until the Box status enters Scanning/Digitization.';
						$details_array['box_status_name'] = 'Pending';
						break;
//			 		case 672: // Box Status: Scanning Preperation
					case $status_box_scanning_preparation_term_id: // Box Status: Scanning Preperation
						//$details_array['error'] = 'Containing Box Status Not Recallable';
						//$details_array['error_message'] = 'Recalls are not allowed until the Box status enters Scanning/Digitization.';
						$details_array['box_status_name'] = 'Scanning Preperation';
						break;
//			 		case 671: // Box Status: Scanning/Digitization
					case $status_box_scanning_digitization_term_id: // Box Status: Scanning/Digitization
						//$details_array['error'] = '';
						//$details_array['error_message'] = '';
						$details_array['box_status_name'] = 'Scanning/Digitization';
						break;
//			 		case 65: // Box Status: QA/QC
					case $status_box_q_a_term_id: // Box Status: QA/QC
						//$details_array['error'] = '';
						//$details_array['error_message'] = '';
						$details_array['box_status_name'] = 'QA/QC';
						break;
//			 		case 6: // Box Status: Digitized - Not Validated
					case $status_box_digitized_not_validated_term_id: // Box Status: Digitized - Not Validated
						//$details_array['error'] = '';
						//$details_array['error_message'] = '';
						$details_array['box_status_name'] = 'Digitized - Not Validated';
						break;
//			 		case 673: // Box Status: Ingestion
					case $status_box_ingestion_term_id: // Box Status: Ingestion
						//$details_array['error'] = '';
						//$details_array['error_message'] = '';
						$details_array['box_status_name'] = 'Ingestion';
						break;
//			 		case 674: // Box Status: Validation
					case $status_box_validation_term_id: // Box Status: Validation
						//$details_array['error'] = 'Containing Box Status Not Recallable';
						//$details_array['error_message'] = 'Recalls are not allowed for Boxes in Validation to Re-Scan statuses.';
						$details_array['box_status_name'] = 'Validation';
						break;
//			 		case 743: // Box Status: Re-scan
					case $status_box_rescan_term_id: // Box Status: Re-scan
						//$details_array['error'] = 'Containing Box Status Not Recallable';
						//$details_array['error_message'] = 'Recalls are not allowed for Boxes in Validation to Re-Scan statuses.';
						$details_array['box_status_name'] = 'Re-scan';
						break;
//			 		case 66: // Box Status: Completed
					case $status_box_completed_term_id: // Box Status: Completed
						//$details_array['error'] = '';
						//$details_array['error_message'] = '';
						$details_array['box_status_name'] = 'Completed';
						break;
//			 		case 68: // Box Status: Destruction Approval
					case $status_box_destruction_approval_term_id: // Box Status: Destruction Approval
						//$details_array['error'] = 'Containing Box Status Not Recallable';
						//$details_array['error_message'] = 'Recalls are not allowed in the Destruction approval status.';
						$details_array['box_status_name'] = 'Destruction Approved';
						break;
					case $status_box_destruction_of_source_term_id: // Box Status: Destruction Approval
						//$details_array['error'] = 'Box Status Not Recallable';
						//$details_array['error_message'] = 'Recalls are not allowed in the Destruction Approval status.';
						$details_array['box_status_name'] = 'Destruction of Source';
						break;
					case $status_box_waiting_on_rlo_term_id: // Box Status: Destruction Approval
						//$details_array['error'] = 'Box Status Not Recallable';
						//$details_array['error_message'] = 'Recalls are not allowed in the Destruction Approval status.';
						$details_array['box_status_name'] = 'Waiting on RLO';
						break;	
//			 		case 67: // Box Status: Dispositioned
					case $status_box_dispositioned_term_id: // Box Status: Dispositioned
						//$details_array['error'] = 'Containing Box Status Not Recallable';
						//$details_array['error_message'] = 'Recalls are not allowed in the Dispositioned status.';
						$details_array['box_status_name'] = 'Dispositioned';
						break;			
					
				}
			
				
			} // if else

			return $details_array;
		   	   	
		} // END item_in_recall
		
		public static function get_term_by_slug( $slug ) {
		    global $wpdb;
		    
		    if( $slug == '' || $slug == null ) {
			    return false;
		    }
		    
		    $table = $wpdb->prefix . "terms";
		    $sql = "SELECT term_id FROM " . $table ." WHERE slug = '" . $slug . "'";
		    $value = $wpdb->get_row( $sql );
		    
		    $result = [
			   'value' => $value->term_id,
			   'sql' => $sql
		    ];
		    
		    //return $result;
		    return $value->term_id;
		    
		}
		
		public static function get_term_name_by_id( $id ) {
		    global $wpdb;
		    
		    if( $id == '' || $id == null ) {
			    return false;
		    }
		    
		    $table = $wpdb->prefix . "terms";
		    $sql = "SELECT name FROM " . $table ." WHERE term_id = '" . $id . "'";
		    $value = $wpdb->get_row( $sql );
		    
		    return $value->name;
		    
		}
        
        
        // Utility function for searching a multidimentional array in php
        public static function searchMultiArray($val, $array) {
			foreach ($array as $element) {
				if ($element['name'] == $val) {
					return $element['slug'];
				}
			}
			return null;
		}
		
		// Utility function for changing bytes to readable format
		// input: bytes
		// @returns: string size with units
        public static function bytes_to_readable_string( $file_size ) {
			
			if( ($file_size / 1000000000) > 1 ) {
				$file_size = round( $file_size / 1000000000, 2);
				$file_size .= ' GB';
			} elseif( ($file_size / 1000000) > 1 ) {
				$file_size = round( $file_size / 1000000, 2);
				$file_size .= ' MB';
			} elseif( ($file_size / 1000) > 1 ) {
				$file_size = round( $file_size / 1000, 2);
				$file_size .= ' KB';
			} elseif( $file_size == 0 ) {
				$file_size = 'zero';
			} else {
				$file_size .= ' Bytes';
			}
			
			return $file_size;
		}
		
		// Utility function for searching a multidimentional array 
		public static function searchMultiArrayByFieldValue( $input_array, $field, $value ) {
		   foreach($input_array as $key => $val)
		   {
		      if ( $val[$field] === $value )
		         return $key;
		   }
		   return false;
		}
		
		// Submit ticket thread
		function submit_recall_thread( $args ) {
			global $wpdb;
			$thread_id = wp_insert_post(
				array(
					'post_type'    => 'wppatt_recall_thread',
					'post_content' => $args['reply_body'],
					'post_status'  => 'publish',
				)
			);
		// 	add_post_meta( $thread_id, 'ticket_id', $args['ticket_id'] );
			add_post_meta( $thread_id, 'recall_id', $args['recall_id'] );
			add_post_meta( $thread_id, 'thread_type', $args['thread_type'] );
			if( isset( $args['reply_source'] ) ) {
				add_post_meta( $thread_id, 'reply_source', $args['reply_source'] );
			}
			$customer_name =  isset($args['customer_name']) ? $args['customer_name'] : '';
			add_post_meta( $thread_id, 'customer_name', $customer_name );
			$customer_email =  isset($args['customer_email']) ? $args['customer_email'] : '';
			add_post_meta( $thread_id, 'customer_email', $customer_email );
			$attachments =  isset($args['attachments']) ? $args['attachments'] : array();
			add_post_meta( $thread_id, 'attachments', $attachments );
			
			if(isset($args['ip_address'])){
				add_post_meta( $thread_id, 'ip_address', $args['ip_address'] );
			}
			
			if(isset($args['os'])){
				add_post_meta($thread_id,'os',$args['os']);
			}
			
			if(isset($args['browser'])){
				add_post_meta($thread_id,'browser',$args['browser']);
			}
			
			if (isset($args['user_seen'])) {
				add_post_meta($thread_id,'user_seen',$args['user_seen']);
			}
			
			//$ticket_id =  isset($args['ticket_id']) ? $args['ticket_id'] : 0;
			//$values= array(
			//	'date_updated' => date("Y-m-d H:i:s"),
			//	'historyId'    => $thread_id,
			//);
			
			//$wpdb->update($wpdb->prefix.'wpsc_ticket',$values,array('id'=>$ticket_id));
			
			do_action('wpsc_after_submit_thread_request',$args,$thread_id);
			
			return $thread_id;
		}
		
		public static function get_wpsc_canned_replies() {
			
			global $wpdb;
			
			$sql = "SELECT
				    *
				FROM
				    ".$wpdb->prefix."posts p
				LEFT JOIN ".$wpdb->prefix."term_relationships rel ON
				    rel.object_id = p.ID
				LEFT JOIN ".$wpdb->prefix."term_taxonomy tax ON
				    tax.term_taxonomy_id = rel.term_taxonomy_id
				LEFT JOIN ".$wpdb->prefix."terms t ON
				    t.term_id = tax.term_id
				WHERE 
				    p.post_type = 'wpsc_canned_reply'
				    AND
				    t.slug = 'canned-replies'";
				    
			$results = $wpdb->get_results( $sql );
			
			return $results;
			
		}
		
		/**
         * Get the ticket_id (request_id) by recall_id.
         * Accepts $recall_id in these formats: R-0000008, r-0000008, 0000008, 8
         * @return ticket_id
         */
		public static function get_ticket_id_from_recall_id( $recall_id ) {
			
			global $wpdb;
			
			$real_recall_id = $recall_id;
			
			$start_str = substr( $recall_id, 0, 2 );
			if( $start_str === 'R-' || $start_str === 'r-' ) {
				$real_recall_id = str_ireplace('r-', '', $recall_id );
			}
			

			$sql = "SELECT
					    request_id
					FROM
					    ".$wpdb->prefix."wpsc_ticket ticket
					LEFT JOIN ".$wpdb->prefix."wpsc_epa_boxinfo box
					ON
					    box.ticket_id = ticket.request_id
					LEFT JOIN ".$wpdb->prefix."wpsc_epa_recallrequest rr
					ON
					    rr.box_id = box.id
					WHERE
					    rr.recall_id = '" . $real_recall_id . "' ";
				    
			$results = $wpdb->get_results( $sql );
			
 			return $results[0]->request_id;

		}


		/**
         * Convert HTTP Error Code
         * Accepts $error_code
         * @return text
         */
		public static function convert_http_error_code( $error_code ) {
		    
		      if ($error_code !== NULL) {

                switch ($error_code) {
                    case 100: $text = 'Continue'; break;
                    case 101: $text = 'Switching Protocols'; break;
                    case 200: $text = 'OK'; break;
                    case 201: $text = 'Created'; break;
                    case 202: $text = 'Accepted'; break;
                    case 203: $text = 'Non-Authoritative Information'; break;
                    case 204: $text = 'No Content'; break;
                    case 205: $text = 'Reset Content'; break;
                    case 206: $text = 'Partial Content'; break;
                    case 300: $text = 'Multiple Choices'; break;
                    case 301: $text = 'Moved Permanently'; break;
                    case 302: $text = 'Moved Temporarily'; break;
                    case 303: $text = 'See Other'; break;
                    case 304: $text = 'Not Modified'; break;
                    case 305: $text = 'Use Proxy'; break;
                    case 400: $text = 'Bad Request'; break;
                    case 401: $text = 'Unauthorized'; break;
                    case 402: $text = 'Payment Required'; break;
                    case 403: $text = 'Forbidden'; break;
                    case 404: $text = 'Not Found'; break;
                    case 405: $text = 'Method Not Allowed'; break;
                    case 406: $text = 'Not Acceptable'; break;
                    case 407: $text = 'Proxy Authentication Required'; break;
                    case 408: $text = 'Request Time-out'; break;
                    case 409: $text = 'Conflict'; break;
                    case 410: $text = 'Gone'; break;
                    case 411: $text = 'Length Required'; break;
                    case 412: $text = 'Precondition Failed'; break;
                    case 413: $text = 'Request Entity Too Large'; break;
                    case 414: $text = 'Request-URI Too Large'; break;
                    case 415: $text = 'Unsupported Media Type'; break;
                    case 500: $text = 'Internal Server Error'; break;
                    case 501: $text = 'Not Implemented'; break;
                    case 502: $text = 'Bad Gateway'; break;
                    case 503: $text = 'Service Unavailable'; break;
                    case 504: $text = 'Gateway Time-out'; break;
                    case 505: $text = 'HTTP Version not supported'; break;
                    default:
                        $text = 'Unknown http status code ' . htmlentities($error_code);
                    break;
                }
                
                return $text;
                
		      }
                
		}
		
		/**
         * Send an alert if API fails. Update wpqa_epa_error_log table.
         * Accepts $id, $service_type, $status_code, $error
         * @return true/false
         */
		public static function insert_api_error( $service_type, $status_code, $error ) {
			
			global $wpdb;
			
			if(!empty($service_type) || !empty($status_code) || !empty($error)){

            $table_error_log = $wpdb->prefix."epa_error_log";
            
			// Send email notification
			$option = get_option( 'rwpm_option' );
					
			$get_post_details = $wpdb->get_row("SELECT a.post_title, a.post_content FROM ".$wpdb->prefix."posts a
			INNER JOIN ".$wpdb->prefix."term_relationships b ON a.id = b.object_id 
			INNER JOIN ".$wpdb->prefix."term_taxonomy c ON b.term_taxonomy_id = c.term_taxonomy_id 
			INNER JOIN ".$wpdb->prefix."terms d ON c.term_id = d.term_id
			WHERE a.post_status = 'publish' AND d.slug = 'email-messages' AND a.post_name = 'email-api-error'");
			$post_details_subject = $get_post_details->post_title;
			$post_details_content = $get_post_details->post_content;
			
					// send email to user
					if ( $option['email_enable'] ) {
						$sender = $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE user_login = 'admin' LIMIT 1" );
		
						// replace tags with values
						$tags = array( '%TITLE%','%BODY%','%BLOG_NAME%', '%BLOG_ADDRESS%', '%SENDER%', '%INBOX_URL%' );
						$replacement = array( $post_details_subject, $post_details_content, get_bloginfo( 'name' ), get_bloginfo( 'admin_email' ), $sender, admin_url( 'admin.php?page=rwpm_inbox' ) );
		
						$email_name = str_replace( $tags, $replacement, $option['email_name'] );
						$email_address = str_replace( $tags, $replacement, $option['email_address'] );
						$email_subject = str_replace( $tags, $replacement, $option['email_subject'] );
						$email_body = str_replace( $tags, $replacement, $option['email_body'] );
		
						// set default email from name and address if missed
						if ( empty( $email_name ) )
							$email_name = get_bloginfo( 'name' );
		
						if ( empty( $email_address ) )
							$email_address = get_bloginfo( 'admin_email' );
		
						$email_subject = strip_tags( $email_subject );
						if ( get_magic_quotes_gpc() )
						{
							$email_subject = stripslashes( $email_subject );
							$email_body = stripslashes( $email_body );
						}
						$email_body = nl2br( $email_body );
                      
                      	$recipient_email = array('ecms@epa.gov', 'ndp@epa.gov', 'garner.kiwane@epa.gov');
                        $recipient_email = implode(", ",$recipient_email);
                      
						/*if (in_array("Array@domain.invalid", $recipient_email)) {
                          $recipient_email = unset($recipient_email[3]);
                          $recipient_email = array_values($recipient_email); 
                        }*/
                      
						$mailtext = "<html><head><title>$email_subject</title></head><body>$email_body <hr /> $service_type : $status_code - $error</body></html>";
		
						// set headers to send html email
						$headers = "To: $recipient_email\r\n";
						$headers .= "From: $email_name <$email_address>\r\n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= 'Content-Type: ' . get_bloginfo( 'html_type' ) . '; charset=' . get_bloginfo( 'charset' ) . "\r\n";
		
			$get_identical_error = $wpdb->get_row("SELECT Timestamp FROM ".$table_error_log."
			WHERE Status_Code = '".$status_code."' AND Service_Type = '".$service_type."' AND Error_Message = '".$error."' ORDER BY Timestamp DESC LIMIT 1");
			$get_timestamp = $get_identical_error->Timestamp;

            // Insert Error
			$wpdb->insert(
				$table_error_log,
				array(
					'Status_Code' => $status_code,
					'Error_Message' => $error,
					'Service_Type' => $service_type
				)
            );
            		
            $ts1 = strtotime($get_timestamp);
            $ts2 = time();

            $seconds_diff = $ts2 - $ts1;                            
            $time = ($seconds_diff/60);		


            if($time > 2 || empty($get_timestamp)) {     //Increase in future 15 minutes from 2 minutes
              wp_mail( $recipient_email, $email_subject, $mailtext, $headers );
            }
   					}
					
			return true;
			} else {
			return false;  
			}
            
		}

		/**
         * Insert Pending and New Request timestamp to Timestamps Tables
         * Accepts $ticket_id
         * @return text
         */
		public static function update_init_timestamp_request( $ticket_id ) {
		    
		      global $wpdb;
		    			
		      if (!empty($ticket_id)) {
		          
			$get_date_timestamp = $wpdb->get_row("SELECT date_created, customer_name FROM ".$wpdb->prefix."wpsc_ticket
			WHERE id = ".$ticket_id);
			
			$date_timestamp = $get_date_timestamp->date_created;
			$customer_name = $get_date_timestamp->customer_name;
			
            $table_timestamp_request = $wpdb->prefix . 'wpsc_epa_timestamps_request';
            
            $wpdb->insert($table_timestamp_request, array('request_id' => $ticket_id, 'type' => 'New Request', 'user' => $customer_name, 'timestamp' => $date_timestamp) );

			$get_boxes = $wpdb->get_results("SELECT id FROM ".$wpdb->prefix."wpsc_epa_boxinfo
			WHERE ticket_id = ".$ticket_id);
			
			$table_timestamp_box = $wpdb->prefix . 'wpsc_epa_timestamps_box';
			            
			foreach ($get_boxes as $items) {
			
			$boxdb_id = $items->id;
			
			$wpdb->insert($table_timestamp_box, array('box_id' => $boxdb_id, 'type' => 'Pending', 'user' => $customer_name, 'timestamp' => $date_timestamp) );
			    
			}

		      }
                
		}
		
        /**
         * Program office to region ID conversion
         * Converts the program office acronym to the region ID, which is to be ingested into sems_site_id_validation
         * @return region ID
         */
         /*
		public static function program_office_to_region_id( $program_office_acronym ) {
		    $region_or_hq = substr($program_office_acronym, 0, 3);
		    $reg_id = '';
		    
		    if( strpos($region_or_hq, 'R') !== false) {
		        $reg_id = substr($region_or_hq, 1);
		    }
		    else {
		        $reg_id = '11';
		    }
		    return $reg_id;
		}
*/
        /**
         * SEMS Site ID Validation
         * Accepts $error_code
         * @return text
         */
		public static function sems_site_id_validation( $site_name_submission, $site_id_submission, $reg_id ) {
		    
		    if ($site_name_submission !== NULL || $site_id_submission !== NULL) {

				$curl = curl_init();
				$url = SEMS_ENDPOINT;
				//7 character site ID
				if(strlen($site_id_submission) == 7) {
                    $url .= '?id='.$site_id_submission;
				}
				//12 character CERCLIS/EPAID
				if(strlen($site_id_submission) == 12) {
                    $url .= '?epaId='.$site_id_submission.'&regId='.$reg_id;
				}
				
				$headers = [
				    'Cache-Control: no-cache'
				];
				
			    curl_setopt($curl,CURLOPT_URL, $url);
			    curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			    curl_setopt($curl,CURLOPT_MAXREDIRS, 10);
			    curl_setopt($curl,CURLOPT_TIMEOUT, 30);
			    curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
			    curl_setopt($curl,CURLOPT_CUSTOMREQUEST, "GET");
			    curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
				//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				
				$response = curl_exec($curl);
				
				//Strip first and last characters
				
				$final_response = substr($response, 1, -1);
				
				//
				$object = json_decode($final_response);
				
				$site_name = $object->{'sitename'};
			
				$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			
				curl_close($curl);
			
				$err = Patt_Custom_Func::convert_http_error_code($status);
			
				if ($status != 200) {
					Patt_Custom_Func::insert_api_error('sems-site-id-validation',$status,$err);
					return 'error';
				} else {
					if (strtoupper($site_name_submission) == strtoupper($site_name)) {
						return 'Success';
					} else {
						return $site_name;
					}	
				} 
		    }
                
		}
		
		/**
         * Get the ticket_id (request_id) by return_id (decline_id).
         * Accepts $decline_id in these formats: D-0000008, d-0000008, 0000008, 8
         * @return ticket_id
         */
		public static function get_ticket_id_from_decline_id( $decline_id ) {
			
			global $wpdb;
			
			$real_decline_id = $decline_id;
			
			$start_str = substr( $decline_id, 0, 2 );
			if( $start_str === 'D-' || $start_str === 'd-' ) {
				//$real_decline_id = str_ireplace('r-', '', $decline_id );
				$real_decline_id = str_ireplace('d-', '', $decline_id );
			}
			
			$decline_id_num = ltrim( $real_decline_id, '0' );

			$sql = "SELECT
					    request_id
					FROM
					    ".$wpdb->prefix."wpsc_ticket ticket
					LEFT JOIN ".$wpdb->prefix."wpsc_epa_boxinfo box
					ON
					    box.ticket_id = ticket.request_id
					LEFT JOIN ".$wpdb->prefix."wpsc_epa_return_items ri
					ON
					    ri.box_id = box.id
					WHERE
					    ri.return_id = '" . $decline_id_num . "' ";
				    
			$results = $wpdb->get_results( $sql );
			
 			return $results[0]->request_id;

		}
		
		/**
         * Get an array of ids for the boxes in a given request
         * Accepts $ticket_id in these formats: '0000008', 8. Note the quotes for padded ticket_id.
         * @return array of box_id
         */
		public static function get_box_id_array_from_ticket_id( $ticket_id ) {
			
			global $wpdb;
			$output = [];
			
			$sql = "SELECT 
						id 
					FROM 
						".$wpdb->prefix."wpsc_epa_boxinfo 
					WHERE 
						ticket_id = " . $ticket_id;
							    
			$results = $wpdb->get_results( $sql );
			
			forEach( $results as $key => $val ) {
				$output[$key] = $val->id;
			}
			
 			return $output;
		}
		
		/**
         * Get an array of ids for the Files in FDIF in a given box
         * Accepts $box_id in these formats: '0000008', 8. 
         * Note: $box_id here is the ID column from the boxinfo table. 
         * @return array of ID for files that are in the given box's ID.
         */
		public static function get_fdif_id_array_from_box_id( $box_id ) {
			
			global $wpdb;
			$output = [];
			
			$sql = "SELECT 
						id 
					FROM 
						".$wpdb->prefix."wpsc_epa_folderdocinfo_files 
					WHERE 
						box_id = " . $box_id;
							    
			$results = $wpdb->get_results( $sql );
			
			forEach( $results as $key => $val ) {
				$output[$key] = $val->id;
			}
			
 			return $output;
		}
		
		/**
         * Get a nested array of ids for the Boxes and Files in a given request
         * Accepts $ticket_id in these formats: '0000008', 8. Note the quotes for padded ticket_id.
         * Note: IDs are the ID column from the boxinfo and FDIF tables. 
         * @return nested array of IDs for boxes and files that are in the given request
         */
		public static function get_box_and_fdif_id_array_from_ticket_id( $ticket_id ) {
			
			$box_arr = self::get_box_id_array_from_ticket_id( $ticket_id );
			$output = [];
			
			foreach( $box_arr as $key => $val ) {
				
				
				$output[ $val ] = self::get_fdif_id_array_from_box_id( $val );
				
				
			}
						
 			return $output;
		}
      
     public static function generate_bay_letters($length = 1) {
        $capital = range('A', 'Z');
        if ($length <= 0) {
            return [];
        } elseif ($length == 1) {
            return $capital;
        } else {
            $result = [];
            foreach ($capital as $letter) {
                foreach (Patt_Custom_Func::generate_bay_letters($length - 1) as $subLetter) {
                    $result[] = $letter . $subLetter;
                }
            }
            return $result;
        }
	}

    public static function get_bay_from_number($bay) {
        if($bay > 26) {
            $bay_arr = Patt_Custom_Func::generate_bay_letters(2);
            $bay_value = $bay_arr[$bay-27];
            return strval($bay_value);
        }
        else {
            $bay_arr = Patt_Custom_Func::generate_bay_letters(1);
            $bay_value = $bay_arr[$bay-1];
            return strval($bay_value);
        }
    }

    public static function get_bay_from_letter($bay) {
        // Get number based on letters
        if(strlen($bay) > 1) {
            $bay_arr = Patt_Custom_Func::generate_bay_letters(2);
            $key = array_search($bay, $bay_arr);
            $bay_val = $key + 27;
        }
        else {
            $bay_arr = Patt_Custom_Func::generate_bay_letters(1);
            $key = array_search($bay, $bay_arr);
            $bay_val = $key + 1;
        }

        return strval($bay_val);
    }
      
      	/**
         * Convert Bay location to Letter from Number.
         * @return Bay Location with letter.
         */
		public static function convert_bay_letter( $shelf_id ) {
			
          [$aisle, $bay, $shelf, $position, $dc] = explode("_", $shelf_id);
          
          $loc_num = trim($bay, 'B');
		  $letter = Patt_Custom_Func::get_bay_from_number($loc_num);
          $output = $aisle.'_'.$letter.'B_'.$shelf.'_'.$position.'_'.$dc;
						
 		  return $output;
          
		}
      
         /**
         * Convert Bay location to Number from Letter.
         * @return Bay Location with number.
         */
		public static function convert_bay_number( $shelf_id ) {
			
          [$aisle, $bay, $shelf, $position, $dc] = explode("_", $shelf_id);
          //$loc_letter = trim($bay, 'B');
          $loc_letter = substr($bay, 0, -1);
          $number = Patt_Custom_Func::get_bay_from_letter($loc_letter);
          $output = $aisle.'_'.$number.'B_'.$shelf.'_'.$position.'_'.$dc;
						
 		  return $output;
		}
		
		/**
         * Inserts a row into the wpqa_wpsc_epa_timestamps_recall table
         * details what action was taken and inserts the timestamp.
         * @return true if inserted, false if not.
         */
		public static function insert_recall_timestamp( $data ) {
	        global $wpdb;
	        
	        if( 
	        	is_array( $data ) && 
	        	array_key_exists( 'recall_id', $data ) &&
	        	array_key_exists( 'type', $data ) &&
	        	array_key_exists( 'user', $data ) &&
	        	array_key_exists( 'digitization_center', $data )
	        ) {
		        
		        $wpsc_epa_recall_timestamp = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_timestamps_recall");
				
				$data[ 'timestamp' ] = date("Y-m-d H:i:s");
                
                $recepit = $wpsc_epa_recall_timestamp->insert( $data );
                
                return true;
                 
	        } else {
		        return false;
	        }

		}
		
		/**
         * Inserts a row into the wpqa_wpsc_epa_timestamps_decline table
         * details what action was taken and inserts the timestamp.
         * @return true if inserted, false if not.
         */
		public static function insert_decline_timestamp( $data ) {
	        global $wpdb;
	        
	        if( 
	        	is_array( $data ) && 
	        	array_key_exists( 'decline_id', $data ) &&
	        	array_key_exists( 'type', $data ) &&
	        	array_key_exists( 'user', $data ) &&
	        	array_key_exists( 'digitization_center', $data )
	        ) {
		        
		        $wpsc_epa_decline_timestamp = new WP_CUST_QUERY("{$wpdb->prefix}wpsc_epa_timestamps_decline");
				
				$data[ 'timestamp' ] = date("Y-m-d H:i:s");
                
                $recepit = $wpsc_epa_decline_timestamp->insert( $data );
                
                return true;
                 
	        } else {
		        return false;
	        }

		}


        /**
         *COMMENTS: Steps 1 and 2 of the datasync process
         */
		public static function patt_datasync_file_check() {
            echo 'Testing datasync function!!!';
            // $text = 'Testing datasync function!!!';
            // return $text;
            
            // checks the # of files left in the binary-stg folder on S3
            // we’ll need it to know if we can trigger datasync
	        // global $wpdb, $current_user, $wpscfunction;

            // $WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -2)));

            // $dir = $_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/app/mu-plugins/pattracking/includes/admin/pages/scripts';

            // require_once($dir."/vendor/autoload.php");

            // function region() {
            //     return 'us-east-1';
            // }

            // $s3 = new Aws\S3\S3Client([
            //     'version'     => 'latest',
            //     'region'  => region()
            // ]);

            // // Specify the bucket name
            // $bucketName = "arms-nuxeo";

            // // Specify the prefix
            // $prefix = "digitization/binary-stg/";
            // $thumbsdb = $prefix . "Thumbs.db";
            // $ignore_arr = array($prefix, $thumbsdb);
            // $file_count = 0;

            // // List objects in the bucket
            // $objects = $s3->listObjects([
            //     'Bucket' => $bucketName,
            //     'Prefix' => $prefix
            // ]);

            // foreach($objects['Contents']  as $o_key => $o_value) {
            //     foreach ($o_value as $n_key => $n_value) {

            //         if($file_count > 0) {
            //             break;
            //         }

            //         // Only check the object keys and ignore the prefix and thumbs.db file
            //         if($n_key == "Key" && !in_array($n_value, $ignore_arr)) {
            //             $file_count++;
            //         }
            //     }
            // }

            // if($file_count > 0) {
            //     echo "Error: files left in the bucket!";
            // }
            // else {
            //     echo "Proceed! </br>";

            //     // Begin Executing Datasync
            //     $client = new Aws\Sts\StsClient([
            //         'version'     => 'latest',
            //         'region'  => region(),
            //         'endpoint' => 'https://vpce-07a469e9e500866e6-wrptt4b4.sts.us-east-1.vpce.amazonaws.com'
            //     ]);
                
            //     $ARN = "arn:aws:iam::114892021311:role/Customer-PATT-Datasync-Access";
            //     $sessionName = "AssumedRoleSession";
                
            //     $new_role = $client->AssumeRole([
            //         'RoleArn' => $ARN,
            //         'RoleSessionName' => $sessionName,
            //     ]);
                
            //     // Initialize the DataSync client
            //     $dataSyncClient = new Aws\DataSync\DataSyncClient([
            //         'version'     => 'latest',
            //         'region'  => region(),
            //         'credentials' =>  [
            //             'key'    => $new_role['Credentials']['AccessKeyId'],
            //             'secret' => $new_role['Credentials']['SecretAccessKey'],
            //             'token'  => $new_role['Credentials']['SessionToken']
            //         ]
            //     ]);
                
            //     // Specify the ARN of the DataSync task you want to trigger
            //     $taskArn = 'arn:aws:datasync:us-east-1:114892021311:task/task-0f1bfec48faf20b0b';
                
            //     // Start the task execution
            //     try {
            //         $result = $dataSyncClient->startTaskExecution([
            //             'TaskArn' => $taskArn,
            //         ]);
                
            //         // Check if the task execution was initiated successfully
            //         echo 'DataSync task execution started successfully.';
            //     } catch (Exception $e) {
            //         echo 'Error starting DataSync task execution: ' . $e->getMessage();
            //     }

            // }


		}
        
        

    }
    // new Patt_Custom_Func;
}