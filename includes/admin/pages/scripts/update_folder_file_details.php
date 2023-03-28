<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');


//Folder File Details Metadata
if(
!empty($_POST['postvarsil']) ||
!empty($_POST['postvarstitle']) ||
!empty($_POST['postvarsdate']) ||
!empty($_POST['postvarsauthor']) ||
!empty($_POST['postvarsrt']) ||
!empty($_POST['postvarssn']) ||
!empty($_POST['postvarssid']) ||
!empty($_POST['postvarscd']) ||
!empty($_POST['postvarsce']) ||
!empty($_POST['postvarssf']) ||
!empty($_POST['postvarser']) ||
!empty($_POST['postvarsfi']) ||
!empty($_POST['postvarsaddressee']) ||
!empty($_POST['postvarsdescription']) ||
!empty($_POST['postvarstags']) ||
!empty($_POST['postvarsaccessrestriction']) ||
!empty($_POST['postvarsspecificaccessrestriction']) ||
!empty($_POST['postvarsuserestriction']) ||
!empty($_POST['postvarsspecificuserestriction']) ||
!empty($_POST['postvarsrightsholder'])
){
    
   //folderdocinfo_files table id
   $pattdocid = $_POST['postvarspdid'];
   $doc_id_array = explode(",", $_POST['docidarray']);
   //folderdocinfo_files table folderdocinfofile_id

   
   
   $folderdocinfofileid = $_POST['postvarsfdiid'];
   $il = $_POST['postvarsil'];
   $title = $_POST['postvarstitle'];
   $date = $_POST['postvarsdate'] == '0001-01-01' ? '0000-00-00' : $_POST['postvarsdate'] ;  
   $author = $_POST['postvarsauthor']; 
   $record_type = $_POST['postvarsrt']; 
   $site_name = $_POST['postvarssn']; 
   $site_id = $_POST['postvarssid']; 
   $close_date = $_POST['postvarscd']; 
   $contact_email = $_POST['postvarsce'];
   $source_format = $_POST['postvarssf'];
   $essential_record = $_POST['postvarser'];
   $folder_identifier = $_POST['postvarsfi'];
   $addressee = $_POST['postvarsaddressee'];
   
   $description = $_POST['postvarsdescription'];
   $tags = $_POST['postvarstags'];
   $access_restriction = $_POST['postvarsaccessrestriction'];
   $specific_access_restriction = $_POST['postvarsspecificaccessrestriction'];
   $use_restriction = $_POST['postvarsuserestriction'];
   $specific_use_restriction = $_POST['postvarsspecificuserestriction'];
   $rights_holder = $_POST['postvarsrightsholder'];
   $source_dimensions = $_POST['postvarssourcedimensions'];
   $program_area = $_POST['postvarsprogramarea'];

$request_id = substr($folderdocinfofileid, 0, 7);
$get_ticket_id = $wpdb->get_row("
SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '" . $request_id . "'");
$ticket_id = $get_ticket_id->id;

$metadata_array = array();

//$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo';



if(!empty($doc_id_array)){
  	// START LAN ID LOGIC
      if(!empty($_POST['postvarslanid'])){

        $lanpattboxid = $_POST['postvarsboxid'];
        if(!empty($_POST['postvarsfolderdocid'])) {
        $box_id = Patt_Custom_Func::convert_box_id($_POST['postvarsboxid']);
        } else {
        $box_id = $_POST['postvarsboxid']; 
        }

        $lanid = $_POST['postvarslanid'];
        $lanpattdocid = $_POST['postvarspattdocid'];
        $dbid = $_POST['postvarsdbid'];

     $get_ticket_id = $wpdb->get_row("SELECT ticket_id FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE id = '" . $box_id . "'");
     $ticket_id = $get_ticket_id->ticket_id;


        $folderdocid_string = $_POST['postvarsfolderdocid'];

        $folderdocid_arr = explode (",", $folderdocid_string);

     $curl = curl_init();

     $url = 'https://wamssoprd.epa.gov/iam/governance/scim/v1/Users?filter=userName%20eq%20'.$lanid;
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
        Patt_Custom_Func::insert_api_error('eidw-update-epa-contact',$status,$err);
        } 
        else {

        $json = json_decode($response, true);

        $active = $json['Resources']['0']['active'];

        if ($active == 1) {

            if(!empty($_POST['postvarsfolderdocid'])) {

            foreach($folderdocid_arr as $item) {
            $get_json = Patt_Custom_Func::lan_id_to_json($lanid);
            $data_update = array('lan_id' => $lanid, 'lan_id_details' => $get_json);
            $data_where = array('folderdocinfofile_id' => $item);
            $wpdb->update($table_name, $data_update, $data_where);

            //update date_updated column when metadata is updated
            $data_update_current_time = array('date_updated' => $date_time);
            $data_where_current_time = array('folderdocinfofile_id' => $item);
            $wpdb->update($table_name, $data_update_current_time, $data_where_current_time);

            }

            $metadata = 'Multiple EPA contacts have been updated to ' . $lanid;

            //sends email/notification to user when epa contact is updated
            $get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
            $get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');
            $user_id_array = [$get_user_id->ID];
            $convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
            $patt_agent_id = implode($convert_patt_id);
            $pattagentid_array = [$patt_agent_id];
            $data = [];

            $email = 1;

            Patt_Custom_Func::insert_new_notification('email-epa-contact-updated-box',$pattagentid_array,$lanpattboxid,$data,$email);

            do_action('wpppatt_after_box_metadata', $ticket_id, $metadata, $lanpattboxid);

            echo "Multiple EPA contacts have been updated to " . $lanid . ". Box ID: ". $lanpattboxid;

            } else {

            $metadata_array = array();

            $old_box_lanid = $wpdb->get_row("SELECT lan_id FROM " . $table_name . " WHERE id = '" . $dbid . "'");
            $old_lanid = $old_box_lanid->lan_id;

            //$folderfile_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo';


            //updates the epa contact by entering a LANID
            if(!empty($lanid) && $old_lanid != $lanid) {

            $get_json = Patt_Custom_Func::lan_id_to_json($lanid);
            $data_update = array('lan_id' => $lanid, 'lan_id_details' => $get_json);
            $data_where = array('id' => $dbid);
            array_push($metadata_array,'EPA Contact: '.$old_lanid.' > '.$lanid);
            $wpdb->update($table_name, $data_update, $data_where);

            $metadata = implode (", ", $metadata_array);

            do_action('wpppatt_after_folder_doc_metadata', $ticket_id, $metadata, $lanpattdocid);

            //update date_updated column when metadata is updated
            $data_update_current_time = array('date_updated' => $date_time);
            $data_where_current_time = array('id' => $dbid);
            $wpdb->update($table_name, $data_update_current_time, $data_where_current_time);

            //sends email/notification to user when epa contact is updated
            /*$get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
            $get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');
            $user_id_array = [$get_user_id->ID];
            $convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
            $patt_agent_id = implode($convert_patt_id);
            $pattagentid_array = [$patt_agent_id];
            $data = [];

            $email = 1;

            Patt_Custom_Func::insert_new_notification('email-epa-contact-changed',$pattagentid_array,$lanpattdocid,$data,$email);*/

            echo "Folder/File ID #: " . $lanpattdocid . " has been updated.";
            }

            }

        } else { echo "Please enter a valid LAN ID"; }

        }
     }
     // End LAN ID LOGIC
  
    foreach($doc_id_array as $doc_id){

        $get_folderdocinfo_files = $wpdb->get_row("
        SELECT *
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE
        id = '" . $doc_id . "'
        ");

        if ($get_folderdocinfo_files->title == '' ) { $old_title = 'None'; } else { $old_title = $get_folderdocinfo_files->title; }
      
      	$folderdocinfofiles_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';

        //updates fields in folder-file-details modal window
        if( (!empty($title)) && ($title != $old_title) ) {
            $data_update = array('title' => $title);
            //REVIEW
            $data_where = array('id' => $doc_id);
            array_push($metadata_array,'Title: '.$old_title.' > '.$title);
            $wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
        }
            
        $fdiid = $get_folderdocinfo_files->folderdocinfofile_id;
      	

        if( (!empty($date)) && ($date != $old_date) ) {
          $data_update = array('date' => $date);
          //REVIEW
          $data_where = array('id' => $doc_id);

          array_push($metadata_array,'Creation Date: '. Patt_Custom_Func::get_converted_date($old_date) .' > '. Patt_Custom_Func::get_converted_date($date) );
          $wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
          }

        if( (!empty($author)) && ($author != $old_author) ) {
          $data_update = array('author' => $author);
          //REVIEW
          $data_where = array('id' => $doc_id);
          array_push($metadata_array,'Creator: '.$old_author.' > '.$author);
          $wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
        }
      
      if( (!empty($site_name)) && ($site_name != $old_site_name) ) {
        $data_update = array('site_name' => $site_name);
        //REVIEW
        $data_where = array('id' => $doc_id);
        array_push($metadata_array,'Site Name: '.$old_site_name.' > '.$site_name);
        $wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
      }

      if( (!empty($site_id)) && ($site_id != $old_site_id) ) {
        $data_update = array('siteid' => $site_id);
        //REVIEW
        $data_where = array('id' => $doc_id);
        array_push($metadata_array,'Site ID: '.$old_site_id.' > '.$site_id);
        $wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
      }
      
      
      // Update metadata in NUXEO using PUT api endpoint
      $object_key = $get_folderdocinfo_files->object_key;
      	// Update with environment variable
      $api_endpoint = "/api/v1/id/" . $object_key;
      $nuxeo_url = "https://arms-dev-nuxeo.aws.epa.gov/nuxeo" . $api_endpont;
      
      
      if( !empty($object_key) || $object_key != "" || $object_key == NULL){
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $nuxeo_url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          // Both creator and date values will have to be updated when Nuxeo is updated
          CURLOPT_POSTFIELDS =>'{
            "entity-type": "document",
            "properties": {
                "dc:title": ' . $title .',
                "dc:creator": ' . $lanid .', 
                "dc:created": ' . date("Y-m-d\TH:i:s.000\Z", strtotime("$date")) .'
            }
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        
       /*$ch = curl_init();
        
       $values = array(
         'dc:title' => $title,
         'dc:creator' => $author,
         'dc:created' => $date
        );

        $params = http_build_query($values);
        
        curl_setopt($ch, CURLOPT_URL, $nuxeo_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_exec($ch);
        
        // Close curl request connection
        curl_close ($ch);*/      
      }
    
    }
   }


   /*$old_ilval = $get_folderdocinfo_files->index_level;
   if($old_ilval == 1) {
       $old_ilval = 'Folder';
   }
   else {
       $old_ilval = 'File';
   }
   $old_il = $get_folderdocinfo_files->index_level;
   
   
   if ($get_folderdocinfo_files->date == '' ) { $old_date = 'None'; } else { $old_date = $get_folderdocinfo_files->date; }
   //START REVIEW
   if ($get_folderdocinfo_files->author == '' ) { $old_author = 'None'; } else { $old_author = $get_folderdocinfo_files->author; }
   if ($get_folderdocinfo_files->record_type == '' ) { $old_record_type = 'None'; } else { $old_record_type = $get_folderdocinfo_files->record_type; }
   if ($get_folderdocinfo_files->site_name == '' ) { $old_site_name = 'None'; } else { $old_site_name = $get_folderdocinfo_files->site_name; }
   if ($get_folderdocinfo_files->siteid == '' ) { $old_site_id = 'None'; } else { $old_site_id = $get_folderdocinfo_files->siteid; }
   if ($get_folderdocinfo_files->close_date == '' ) { $old_close_date = 'None'; } else { $old_close_date = $get_folderdocinfo_files->close_date; }
   if ($get_folderdocinfo_files->folder_identifier == '' ) { $old_folder_identifier = 'None'; } else { $old_folder_identifier = $get_folderdocinfo_files->folder_identifier; }
   if ($get_folderdocinfo_files->addressee == '' ) { $old_addressee = 'None'; } else { $old_addressee = $get_folderdocinfo_files->addressee; }
   //END REVIEW
   
   if ($get_folderdocinfo_files->source_format == '' ) { $old_source_format = 'None'; } else { $old_source_format = $get_folderdocinfo_files->source_format; }
   $old_erval = $get_folderdocinfo_files->essential_record;
   if ($get_folderdocinfo_files->description == '' ) { $old_description = 'None'; } else { $old_description = $get_folderdocinfo_files->description; }
   if ($get_folderdocinfo_files->tags == '' ) { $old_tags = 'None'; } else { $old_tags = $get_folderdocinfo_files->tags; }
   if ($get_folderdocinfo_files->access_restriction == '' ) { $old_access_restriction = 'None'; } else { $old_access_restriction = $get_folderdocinfo_files->access_restriction; }
   if ($get_folderdocinfo_files->specific_access_restriction == '' ) { $old_specific_access_restriction = 'None'; } else { $old_specific_access_restriction = $get_folderdocinfo_files->specific_access_restriction; }
   if ($get_folderdocinfo_files->use_restriction == '' ) { $old_use_restriction = 'None'; } else { $old_use_restriction = $get_folderdocinfo_files->use_restriction; }
   if ($get_folderdocinfo_files->specific_use_restriction == '' ) { $old_specific_use_restriction = 'None'; } else { $old_specific_use_restriction = $get_folderdocinfo_files->specific_use_restriction; }
   if ($get_folderdocinfo_files->rights_holder == '' ) { $old_rights_holder = 'None'; } else { $old_rights_holder = $get_folderdocinfo_files->rights_holder; }
   if ($get_folderdocinfo_files->source_dimensions == '' ) { $old_source_dimensions = 'None'; } else { $old_source_dimensions = $get_folderdocinfo_files->source_dimensions; }
   if ($get_folderdocinfo_files->program_area == '' ) { $old_program_area = 'None'; } else { $old_program_area = $get_folderdocinfo_files->program_area; }
   


//update index level on document
//if index level is updated, folderdocinfo_id has to be updated and the folderdocinfofiles table
if ($il != $old_il) {
        //updates index_level to either folder/file
        $data_update_il = array('index_level' => $il);
        //pattdocid = folderdocinfo_id
        $pattdocid_split = explode('-', $fdiid);
        //rewrite the folderdocinfo_id
        $il_val = '';
        
        //START REVIEW
        //updates parent folderdocinfofile_id
        if(count($pattdocid_split) == 4) {
            if($il == '1') {
                $il_val = 'Folder';
                $pattdocid_new = $pattdocid_split[0] . '-' . $pattdocid_split[1] . '-' . '01' . '-' . $pattdocid_split[3];
            } 
            elseif($il == '2') {
                $il_val = 'File';
                $pattdocid_new = $pattdocid_split[0] . '-' . $pattdocid_split[1] . '-' . '02' . '-' . $pattdocid_split[3];
            }
            
            $data_update_parent = array('folderdocinfofile_id' => $pattdocid_new);
            $data_where_parent = array('id' => $pattdocid);

            //update index_level
            array_push($metadata_array,'Index Level: '. $old_ilval . ' > ' . $il_val);
            $wpdb->update($folderdocinfofiles_table, $data_update_il, $data_where_parent);
            
            //update parent and child index level - folderdocinfo_id and folderdocinfofile_id
            // ASK STEPHANIE Need to check if $folderfileid maps to $folderdocinfofiles_table id column
            $wpdb->update($folderdocinfofiles_table, $data_update_parent, $data_where_parent);
        }
        //updates child folderdocinfofile_id
        else {
            if($il == '1') {
                $il_val = 'Folder';
                $pattdocid_new = $pattdocid_split[0] . '-' . $pattdocid_split[1] . '-' . '01' . '-' . $pattdocid_split[3] . '-' . $pattdocid_split[4];
            } 
            elseif($il == '2') {
                $il_val = 'File';
                $pattdocid_new = $pattdocid_split[0] . '-' . $pattdocid_split[1] . '-' . '02' . '-' . $pattdocid_split[3] . '-' . $pattdocid_split[4];
            }
            
            $data_update_child = array('folderdocinfofile_id' => $pattdocid_new);
            $data_where = array('id' => $pattdocid);
            
            //update index_level
            array_push($metadata_array,'Index Level: '. $old_ilval . ' > ' . $il_val);
            $wpdb->update($folderdocinfofiles_table, $data_update_il, $data_where);
            
            //update child index level - folderdocinfofile_id
            $wpdb->update($folderdocinfofiles_table, $data_update_child, $data_where);
        }
        //END REVIEW
}*/

$old_er = $get_folderdocinfo->essential_record;
if($old_er == 1) {
       $old_er = 'Yes';
   }
   else {
       $old_er = 'No';
   }

if($essential_record != $old_erval) {
$data_update = array('essential_record' => $essential_record);
//REVIEW
$data_where = array('id' => $pattdocid);

$er_val = '';
        if($essential_record == '1') {
            $er_val = 'Yes';
        } 
        elseif($essential_record == '0') {
            $er_val = 'No';
        }

array_push($metadata_array,'Essential Record: ' . $old_er . ' > ' . $er_val);

$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}
  




if( (!empty($record_type)) && ($record_type != $old_record_type) ) {
//Remove asterisk from SEMS values
$data_update = array('record_type' => ltrim($record_type, '*'));
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Record Type: '. ltrim($old_record_type, '*') .' > '. ltrim($record_type, '*'));
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}



if( (!empty($close_date)) && ($close_date != $old_close_date) ) {
$data_update = array('close_date' => $close_date);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Close Date: '. Patt_Custom_Func::get_converted_date($old_close_date) .' > '. Patt_Custom_Func::get_converted_date($close_date));
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($source_format)) && ($source_format != $old_source_format) ) {
//Remove asterisk from all SEMS values
$data_update = array('source_format' => stripslashes(ltrim($source_format, '*')));
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Source Type: '. ltrim($old_source_format, '*') .' > '. ltrim($source_format, '*'));
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($folder_identifier)) && ($folder_identifer != $old_folder_identifier) ) {
$data_update = array('folder_identifier' => $folder_identifier);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Folder Identifier: '.$old_folder_identifier.' > '.$folder_identifier);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($addressee)) && ($addressee != $old_addressee)) {
$data_update = array('addressee' => $addressee);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Addressee: '.$old_addressee.' > '.$addressee);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($description)) && ($description != $old_description) ) {
$data_update = array('description' => $description);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Description: '.$old_description.' > '.$description);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($tags)) && ($tags != $old_tags)) {
$quoted_tags_array = array();
$tags_explode = explode(",", $tags);

//Adds double quotes around key value pair
foreach($tags_explode as $tag) {
    //trim whitespace at beginning and end, trim whitespace around :
    $tags_trim = trim($tag);
    $tags_trim_colon_whitespace = preg_replace('/(?<=[:]) +| +(?=[:])/', '', $tags_trim);
    
    $split_tag_beginning = strtok($tags_trim_colon_whitespace, ':');
    $split_tag_end = substr($tags_trim_colon_whitespace, strpos($tags_trim_colon_whitespace, ':') + 1);
    $new_tag = '"' . $split_tag_beginning . '"' . ":" . '"' . $split_tag_end . '"';
    
    array_push($quoted_tags_array, $new_tag);
}

$tags_implode = implode(',', $quoted_tags_array);

$data_update = array('tags' => $tags_implode);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Tags: '.$old_tags.' > '.$tags_implode);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($access_restriction)) && ($access_restriction != $old_access_restriction) ) {
$data_update = array('access_restriction' => $access_restriction);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Access Restriction: '.$old_access_restriction.' > '.$access_restriction);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

//If user selects No then clear specific_access_restriction
//REVIEW
$get_access_restriction_no = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE id = '" . $pattdocid . "'"); 
$access_restriction_no = $get_access_restriction_no->access_restriction;

if($access_restriction_no == 'No') {
    $data_update_no = array('specific_access_restriction' => '');
    //REVIEW
    $data_where_no = array('id' => $pattdocid);
    $wpdb->update($folderdocinfofiles_table, $data_update_no, $data_where_no);
}

if( strtolower(str_contains('Please select...', $specific_access_restriction )) == false && $access_restriction_no == 'Yes' && (!empty($specific_access_restriction)) && ($specific_access_restriction != $old_specific_access_restriction) ) {
$data_update = array('specific_access_restriction' => $specific_access_restriction);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Specific Access Restriction: '.$old_specific_access_restriction.' > '.$specific_access_restriction);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($use_restriction)) && ($use_restriction != $old_use_restriction) ) {
$data_update = array('use_restriction' => $use_restriction);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Use Restriction: ' . $old_use_restriction . ' > ' . $use_restriction);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

//If user selects No then clear specific_access_restriction
//REVIEW
$get_use_restriction_no = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE id = '" . $pattdocid . "'"); 
$use_restriction_no = $get_use_restriction_no->use_restriction;

if($use_restriction_no == 'No') {
    $data_update_no = array('specific_use_restriction' => '');
    //REVIEW
    $data_where_no = array('id' => $pattdocid);
    $wpdb->update($folderdocinfofiles_table, $data_update_no, $data_where_no);
}

if( strtolower(str_contains('Please select...', $specific_use_restriction )) == false && $use_restriction_no == 'Yes' && (!empty($specific_use_restriction)) && ($specific_use_restriction != $old_specific_use_restriction)) {
$data_update = array('specific_use_restriction' => $specific_use_restriction);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Specific Use Restriction: '.$old_specific_use_restriction.' > '.$specific_use_restriction);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($rights_holder)) && ($rights_holder != $old_rights_holder)) {
$data_update = array('rights_holder' => $rights_holder);
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Rights Holder: '.$old_rights_holder.' > '.$rights_holder);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($source_dimensions)) && ($source_dimensions != $old_source_dimensions)) {
$data_update = array('source_dimensions' => stripslashes($source_dimensions));
//REVIEW
$data_where = array('id' => $pattdocid);
array_push($metadata_array,'Source Dimensions: '.$old_source_dimensions.' > '.$source_dimensions);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( !empty($program_area) && ($program_area != $old_program_area) ) {
$data_update = array('program_area' => $program_area);
$data_where = array('id' => $pattdocid);
array_push($metadata_array, 'Program Area: ' . $old_program_area . ' > ' . $program_area);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

$metadata = implode (", ", $metadata_array);

//update date_updated column when metadata is updated
$date_time = date('Y-m-d H:i:s');
$data_update_current_time = array('date_updated' => $date_time);
$data_where_current_time = array('id' => $pattdocid);
$wpdb->update($folderdocinfofiles_table, $data_update_current_time, $data_where_current_time);


//REVIEW
/*
if (($il != $old_il) && ($pattdocid_new != $fdiid)) {
$pattdocid = $pattdocid_new;
} else {
$pattdocid = $fdiid;
}
*/

//send notification and email when any folder/file metadata has been updated
/*$get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
$get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');

$user_id_array = [$get_user_id->ID];
$convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
$patt_agent_id = implode($convert_patt_id);
$pattagentid_array = [$patt_agent_id];
$data = [];

//disabled email notification
//$email = 1;

//START REVIEW
Patt_Custom_Func::insert_new_notification('email-folder-file-metadata-updated',$pattagentid_array,$folderdocinfofileid,$data,$email);

do_action('wpppatt_after_folder_doc_metadata', $ticket_id, $metadata, $folderdocinfofileid);*/
//END REVIEW
//}

} else {
   echo "Please make an edit.";
}
?>