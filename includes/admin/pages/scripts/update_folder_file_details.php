<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');


//Folder File Details Metadata
if(
!empty($_POST['postvarsil']) ||
!empty($_POST['postvarstitle']) ||
!empty($_POST['postvarsdate']) ||
!empty($_POST['postvarsauthor']) 
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
     /* $api_endpoint = "/api/v1/id/" . $object_key;
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
                "dc:title" => $title,
                "dc:creator" => $lanid, 
                "dc:created" => date("Y-m-d\TH:i:s.000\Z", strtotime("$date"));
            }
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic c3ZjX2FybXNfcm06cGFzc3dvcmQ='
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
           
      }*/
    
    }
   }


$metadata = implode (", ", $metadata_array);

//update date_updated column when metadata is updated
$date_time = date('Y-m-d H:i:s');
$data_update_current_time = array('date_updated' => $date_time);
$data_where_current_time = array('id' => $pattdocid);
$wpdb->update($folderdocinfofiles_table, $data_update_current_time, $data_where_current_time);


} else {
   echo "Please make an edit.";
}
?>