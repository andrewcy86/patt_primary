<?php

//if ( ! defined( 'ABSPATH' ) ) {
//  exit; // Exit if accessed directly
//}
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/image.php');
include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/media.php');
include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/file.php');

global $current_user, $wpscfunction, $wpdb;

function strip_tags_deep($value)
{
  return is_array($value) ?
    array_map('strip_tags_deep', $value) :
    strip_tags(preg_replace( "/\r|\n/", "", $value ));
}

$ticket_id = 6;
                       $converted_request_id = Patt_Custom_Func::convert_request_db_id($ticket_id);
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
                          $result = implode(", ", $result);
                          $csv_output .= $result."\n";
                        }
                      

                      $filename = $converted_request_id."_".$file."_".date("Y-m-d_H-i",time()).".csv";
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

/*// DELETE ROWS
$delete_pm_id = $wpdb->get_results("select id, pm_id from " . $wpdb->prefix . "pm_users WHERE deleted = 2");
foreach ($delete_pm_id as $data) {
$table_pm = $wpdb->prefix . 'pm';
$wpdb->delete( $table_pm, array( 'id' => $data->pm_id ) );

$table_pm_users = $wpdb->prefix . 'pm_users';
$wpdb->delete( $table_pm_users, array( 'id' => $data->id ) );
}*/

        return true;

?>