<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
//require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/image.php');
include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/media.php');
include($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-admin/includes/file.php');

global $current_user, $wpscfunction, $wpdb;

function strip_tags_deep_pm($value)
{
  return is_array($value) ?
    array_map('strip_tags_deep_pm', $value) :
    strip_tags(preg_replace( "/\r|\n/", "", $value ));
}

function add_quotes_pm($str) {
    return sprintf('"%s"', $str);
}

                       $file = 'message_backup'; // csv file name
                       $pre_results = $wpdb->get_results("select a.pm_id, c.user_login, b.subject, b.content, b.date from " . $wpdb->prefix . "pm_users a INNER JOIN " . $wpdb->prefix . "pm b ON a.pm_id = b.id INNER JOIN " . $wpdb->prefix . "users c ON a.recipient = c.ID WHERE a.deleted = 2",ARRAY_A );
                       $results = strip_tags_deep_pm($pre_results);

// CONVERT TO EST
foreach ($results as $key => &$value) {
   $date = new DateTime($value['date'], new DateTimeZone('UTC'));
   $date->setTimezone(new DateTimeZone('America/New_York'));
   $value['date'] = $date->format('m-d-Y h:i:s a');
}

                        // get column names
                        $columnNamesList = ['PM ID','Recipient','Subject','Content','Date'];


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
                          $result =  implode(',', array_map('add_quotes_pm', $result));
                          $csv_output .= $result."\n";
                        }
                      

                      $filename = $file."_".date("Y-m-d_H-i",time()).".csv";
                      $backup_file  = WPPATT_UPLOADS."/backups/pm/".$filename;
                      
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
$delete_pm_id = $wpdb->get_results("select id, pm_id from " . $wpdb->prefix . "pm_users WHERE deleted = 2");
foreach ($delete_pm_id as $data) {
$table_pm = $wpdb->prefix . 'pm';
$wpdb->delete( $table_pm, array( 'id' => $data->pm_id ) );

$table_pm_users = $wpdb->prefix . 'pm_users';
$wpdb->delete( $table_pm_users, array( 'id' => $data->id ) );
}

                      exit;

?>