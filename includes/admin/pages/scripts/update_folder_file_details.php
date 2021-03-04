<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

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
    
   //folderdocinfo table id
   $folderfileid = $_POST['postvarsffid'];
   $pattdocid = $_POST['postvarspdid'];
   $il = $_POST['postvarsil'];
   $title = $_POST['postvarstitle'];
   $date = $_POST['postvarsdate'];  
   $author = $_POST['postvarsauthor']; 
   $record_type = $_POST['postvarsrt']; 
   $site_name = $_POST['postvarssn']; 
   $site_id = $_POST['postvarssid']; 
   $close_date = $_POST['postvarscd']; 
   $contact_email = $_POST['postvarsce'];
   $source_format = $_POST['postvarssf'];
   //$rights = $_POST['postvarsrights']; 
   //$contract_number = $_POST['postvarscn']; 
   //$grant_number = $_POST['postvarsgn'];
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
   
   //folderdocinfofiles table id
   $folderdocinfofileid = $_POST['postvarsfdiid'];

$request_id = substr($pattdocid, 0, 7);
$get_ticket_id = $wpdb->get_row("
SELECT id FROM " . $wpdb->prefix . "wpsc_ticket WHERE request_id = '" . $request_id . "'");
$ticket_id = $get_ticket_id->id;

$get_folderdocinfo = $wpdb->get_row("
SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo
WHERE
id = '" . $folderfileid . "'
");

$get_folderdocinfo_files = $wpdb->get_row("
SELECT *
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE
id = '" . $folderdocinfofileid . "'
");

   $old_ilval = $get_folderdocinfo_files->index_level;
   if($old_ilval == 1) {
       $old_ilval = 'Folder';
   }
   else {
       $old_ilval = 'File';
   }
   $old_il = $get_folderdocinfo_files->index_level;
   
   if ($get_folderdocinfo_files->title == '' ) { $old_title = 'None'; } else { $old_title = $get_folderdocinfo_files->title; }
   if ($get_folderdocinfo_files->date == '' ) { $old_date = 'None'; } else { $old_date = $get_folderdocinfo_files->date; }
   if ($get_folderdocinfo->author == '' ) { $old_author = 'None'; } else { $old_author = $get_folderdocinfo->author; }
   if ($get_folderdocinfo_files->record_type == '' ) { $old_record_type = 'None'; } else { $old_record_type = $get_folderdocinfo->record_type; }
   if ($get_folderdocinfo->site_name == '' ) { $old_site_name = 'None'; } else { $old_site_name = $get_folderdocinfo->site_name; }
   if ($get_folderdocinfo->siteid == '' ) { $old_site_id = 'None'; } else { $old_site_id = $get_folderdocinfo->siteid; }
   if ($get_folderdocinfo->close_date == '' ) { $old_close_date = 'None'; } else { $old_close_date = $get_folderdocinfo->close_date; }
   if ($get_folderdocinfo->epa_contact_email == '' ) { $old_contact_email = 'None'; } else { $old_contact_email = $get_folderdocinfo->epa_contact_email; }
   if ($get_folderdocinfo_files->source_format == '' ) { $old_source_format = 'None'; } else { $old_source_format = $get_folderdocinfo_files->source_format; }
   if ($get_folderdocinfo->rights == '' ) { $old_rights = 'None'; } else { $old_rights = $get_folderdocinfo->rights; }
   if ($get_folderdocinfo->folder_identifier == '' ) { $old_folder_identifier = 'None'; } else { $old_folder_identifier = $get_folderdocinfo->folder_identifier; }
   if ($get_folderdocinfo->addressee == '' ) { $old_addressee = 'None'; } else { $old_addressee = $get_folderdocinfo->addressee; }
   $old_erval = $get_folderdocinfo->essential_record;
   
   if ($get_folderdocinfo_files->description == '' ) { $old_description = 'None'; } else { $old_description = $get_folderdocinfo_files->description; }
   if ($get_folderdocinfo_files->tags == '' ) { $old_tags = 'None'; } else { $old_tags = $get_folderdocinfo_files->tags; }
   if ($get_folderdocinfo_files->access_restriction == '' ) { $old_access_restriction = 'None'; } else { $old_access_restriction = $get_folderdocinfo_files->access_restriction; }
   if ($get_folderdocinfo_files->specific_access_restriction == '' ) { $old_specific_access_restriction = 'None'; } else { $old_specific_access_restriction = $get_folderdocinfo_files->specific_access_restriction; }
   if ($get_folderdocinfo_files->use_restriction == '' ) { $old_use_restriction = 'None'; } else { $old_use_restriction = $get_folderdocinfo_files->use_restriction; }
   if ($get_folderdocinfo_files->specific_use_restriction == '' ) { $old_specific_use_restriction = 'None'; } else { $old_specific_use_restriction = $get_folderdocinfo_files->specific_use_restriction; }
   if ($get_folderdocinfo_files->rights_holder == '' ) { $old_rights_holder = 'None'; } else { $old_rights_holder = $get_folderdocinfo_files->rights_holder; }
   if ($get_folderdocinfo_files->source_dimensions == '' ) { $old_source_dimensions = 'None'; } else { $old_source_dimensions = $get_folderdocinfo_files->source_dimensions; }
   
$metadata_array = array();

$table_name = $wpdb->prefix . 'wpsc_epa_folderdocinfo';
$folderdocinfofiles_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
$fdiid = $get_folderdocinfo_files->folderdocinfofile_id;

//update index level on document
//if index level is updated, folderdocinfo_id has to be updated and the folderdocinfofiles table
if ($il != $old_il) {
        //updates index_level to either folder/file
        $data_update_il = array('index_level' => $il);
        //pattdocid = folderdocinfo_id
        $pattdocid_split = explode('-', $fdiid);
        //rewrite the folderdocinfo_id
        $il_val = '';
        
        //updates parent folderdocinofo_id and folderdocinfofile_id
        if(count($pattdocid_split) == 4) {
            if($il == '1') {
                $il_val = 'Folder';
                $pattdocid_new = $pattdocid_split[0] . '-' . $pattdocid_split[1] . '-' . '01' . '-' . $pattdocid_split[3];
            } 
            elseif($il == '2') {
                $il_val = 'File';
                $pattdocid_new = $pattdocid_split[0] . '-' . $pattdocid_split[1] . '-' . '02' . '-' . $pattdocid_split[3];
            }
            
            $data_update_parent = array('folderdocinfo_id' => $pattdocid_new);
            $data_update_child = array('folderdocinfofile_id' => $pattdocid_new);
            $data_where_child = array('id' => $folderdocinfofileid);
            $data_where_parent = array('id' => $folderfileid);
            
            //update index_level
            array_push($metadata_array,'Index Level: '. $old_ilval . ' > ' . $il_val);
            $wpdb->update($folderdocinfofiles_table, $data_update_il, $data_where_child);
            
            //update parent and child index level - folderdocinfo_id and folderdocinfofile_id
            $wpdb->update($folderdocinfofiles_table, $data_update_child, $data_where_child);
            $wpdb->update($table_name, $data_update_parent, $data_where_parent);
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
            $data_where = array('id' => $folderdocinfofileid);
            
            //update index_level
            array_push($metadata_array,'Index Level: '. $old_ilval . ' > ' . $il_val);
            $wpdb->update($folderdocinfofiles_table, $data_update_il, $data_where);
            
            //update child index level - folderdocinfofile_id
            $wpdb->update($folderdocinfofiles_table, $data_update_child, $data_where);
        }
}

$old_er = $get_folderdocinfo->essential_record;
if($old_er == 1) {
       $old_er = 'Yes';
   }
   else {
       $old_er = 'No';
   }

if($essential_record != $old_erval) {
$data_update = array('essential_record' => $essential_record);
$data_where = array('id' => $folderfileid);

$er_val = '';
        if($essential_record == '1') {
            $er_val = 'Yes';
        } 
        elseif($essential_record == '0') {
            $er_val = 'No';
        }

array_push($metadata_array,'Essential Record: ' . $old_er . ' > ' . $er_val);

$wpdb->update($table_name, $data_update, $data_where);
}

//updates fields in folder-file-details modal window
if( (!empty($title)) && ($title != $old_title) ) {
$data_update = array('title' => $title);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Title: '.$old_title.' > '.$title);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($date)) && ($date != $old_date) ) {
$data_update = array('date' => $date);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Date: '.$old_date.' > '.$date);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($author)) && ($author != $old_author) ) {
$data_update = array('author' => $author);
$data_where = array('id' => $folderfileid);
array_push($metadata_array,'Author: '.$old_author.' > '.$author);
$wpdb->update($table_name, $data_update, $data_where);
}

if( (!empty($record_type)) && ($record_type != $old_record_type) ) {
$data_update = array('record_type' => $record_type);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Record Type: '.$old_record_type.' > '.$record_type);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($site_name)) && ($site_name != $old_site_name) ) {
$data_update = array('site_name' => $site_name);
$data_where = array('id' => $folderfileid);
array_push($metadata_array,'Site Name: '.$old_site_name.' > '.$site_name);
$wpdb->update($table_name, $data_update, $data_where);
}

if( (!empty($site_id)) && ($site_id != $old_site_id) ) {
$data_update = array('siteid' => $site_id);
$data_where = array('id' => $folderfileid);
array_push($metadata_array,'Site ID: '.$old_site_id.' > '.$site_id);
$wpdb->update($table_name, $data_update, $data_where);
}

if( (!empty($close_date)) && ($close_date != $old_close_date) ) {
$data_update = array('close_date' => $close_date);
$data_where = array('id' => $folderfileid);
array_push($metadata_array,'Close Date: '.$old_close_date.' > '.$close_date);
$wpdb->update($table_name, $data_update, $data_where);
}

if( (!empty($source_format)) && ($source_format != $old_source_format) ) {
$data_update = array('source_format' => stripslashes($source_format));
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Source Format: '.$old_source_format.' > '.$source_format);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($folder_identifier)) && ($folder_identifer != $old_folder_identifier) ) {
$data_update = array('folder_identifier' => $folder_identifier);
$data_where = array('id' => $folderfileid);
array_push($metadata_array,'Folder Identifier: '.$old_folder_identifier.' > '.$folder_identifier);
$wpdb->update($table_name, $data_update, $data_where);
}

if( (!empty($addressee)) && ($addressee != $old_addressee)) {
$data_update = array('addressee' => $addressee);
$data_where = array('id' => $folderfileid);
array_push($metadata_array,'Addressee: '.$old_addressee.' > '.$addressee);
$wpdb->update($table_name, $data_update, $data_where);
}

if( (!empty($description)) && ($description != $old_description) ) {
$data_update = array('description' => $description);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Description: '.$old_description.' > '.$description);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($tags)) && ($tags != $old_tags)) {
$data_update = array('tags' => $tags);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Tags: '.$old_tags.' > '.$tags);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($access_restriction)) && ($access_restriction != $old_access_restriction) ) {
$data_update = array('access_restriction' => $access_restriction);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Access Restriction: '.$old_access_restriction.' > '.$access_restriction);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($specific_access_restriction)) && ($specific_access_restriction != $old_specific_access_restriction) ) {
$data_update = array('specific_access_restriction' => $specific_access_restriction);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Specific Access Restriction: '.$old_specific_access_restriction.' > '.$specific_access_restriction);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($use_restriction)) && ($use_restriction != $old_use_restriction) ) {
$data_update = array('use_restriction' => $use_restriction);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Use Restriction: ' . $old_use_restriction . ' > ' . $use_restriction);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($specific_use_restriction)) && ($specific_use_restriction != $old_specific_use_restriction)) {
$data_update = array('specific_use_restriction' => $specific_use_restriction);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Specific Use Restriction: '.$old_specific_use_restriction.' > '.$specific_use_restriction);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($rights_holder)) && ($rights_holder != $old_rights_holder)) {
$data_update = array('rights_holder' => $rights_holder);
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Rights Holder: '.$old_rights_holder.' > '.$rights_holder);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

if( (!empty($source_dimensions)) && ($source_dimensions != $old_source_dimensions)) {
$data_update = array('source_dimensions' => stripslashes($source_dimensions));
$data_where = array('id' => $folderdocinfofileid);
array_push($metadata_array,'Source Dimensions: '.$old_source_dimensions.' > '.$source_dimensions);
$wpdb->update($folderdocinfofiles_table, $data_update, $data_where);
}

$metadata = implode (", ", $metadata_array);

if (($il != $old_il) && ($pattdocid_new != $fdiid)) {
$pattdocid = $pattdocid_new;
} else {
$pattdocid = $fdiid;
}

//send notification and email when any folder/file metadata has been updated
/*
if($il != $old_il || $essential_record != $old_erval || $old_title != $title || $old_date != $date || $old_author != $author 
|| $old_record_type != $record_type || $old_site_name != $site_name || $old_site_id != $site_id || $old_close_date != $close_date ||
$old_contact_email != $contact_email || $old_source_format != $source_format || $old_folder_identifier != $folder_identifier 
|| $old_addressee != $addressee || $old_description!= $description || $old_tags != $tags || $old_access_restriction != $access_restriction ||
$old_use_restriction != $use_restriction || $old_specific_use_restriction != $specific_use_restriction || $old_rights_holder != $rights_holder ||
$old_source_dimensions != $source_dimensions) {
*/

$get_customer_name = $wpdb->get_row('SELECT customer_name FROM ' . $wpdb->prefix . 'wpsc_ticket WHERE id = "' . $ticket_id . '"');
$get_user_id = $wpdb->get_row('SELECT ID FROM ' . $wpdb->prefix . 'users WHERE display_name = "' . $get_customer_name->customer_name . '"');

$user_id_array = [$get_user_id->ID];
$convert_patt_id = Patt_Custom_Func::translate_user_id($user_id_array,'agent_term_id');
$patt_agent_id = implode($convert_patt_id);
$pattagentid_array = [$patt_agent_id];
$data = [];

//disabled email notification
//$email = 1;
Patt_Custom_Func::insert_new_notification('email-folder-file-metadata-updated',$pattagentid_array,$pattdocid,$data,$email);

do_action('wpppatt_after_folder_doc_metadata', $ticket_id, $metadata, $pattdocid);
//}

} else {
   echo "Please make an edit.";
}
?>