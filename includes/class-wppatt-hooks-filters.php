<?php
/**
 * Exit if accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Patt_HooksFilters' ) ) {

	/**
	 * Class to manage the ticket request
	 */
	class Patt_HooksFilters {

		/**
		 * Get things started
		 *
		 * @access  public
		 * @since   1.0
		 */
		public function __construct() {


			add_action( 'admin_enqueue_scripts', array( $this, 'wpsc_print_ext_js_create_ticket' ) );

			// Print HTML In Request Form - Location: /home/acy3/public_html/wordpress3/wp-content/plugins/supportcandy/includes/admin/tickets/create_ticket/load_create_ticket.php.
			add_action( 'print_listing_form_block', array( $this, 'print_listing_form_block_SEMS' ) );
			add_action( 'print_listing_form_block', array( $this, 'print_listing_form_block' ) );
			

			// Not required.
			// add_action( 'patt_custom_imports_tickets', array( $this, 'patt_custom_imports_tickets' ) );.

			// Not required.
			// add_action( 'patt_print_js_functions_create', array( $this, 'patt_print_js_functions_create' ) );.

			// Print Scripts - Location: /home/acy3/public_html/wordpress3/wp-content/plugins/supportcandy/includes/admin/tickets/tickets.php.
			// Not required.
			// add_action( 'admin_footer', array( $this, 'patt_print_js_tickets_page' ) );.

			// Location: /home/acy3/public_html/wordpress3/wp-content/plugins/supportcandy/includes/functions/create_ticket.php.
			add_action( 'patt_process_boxinfo_records', array( $this, 'patt_process_boxinfo_records' ) );

			// Move uploaded file.
			add_action( 'wp_ajax_move_excel_file', array( $this, 'move_excel_file' ) );
			add_action( 'wp_ajax_nopriv_move_excel_file', array( $this, 'move_excel_file' ) );
			
			//add_action( 'wpsc_ticket_created', 'move_excel_file', 10, 1 );

		}

		/**
		 * Assign a new folder for box list excel file
		 *
		 * @param Array $param Upload directory information as array.
		 */
		public function wpai_set_custom_upload_folder( $param ) {
			$mydir         = '/box-list';
			$param['path'] = $param['basedir'] . $mydir;
			$param['url']  = $param['baseurl'] . $mydir;
			//$param['subdir'] = $mydir; // New 4/20/2021 // Breaks WP6 on Dev
			
			//echo 'param: ';
			//print_r($param);
			
			
			return $param;
		}

		/**
		 * After file upload, move it from the temp to custom directory
		 */
		public function move_excel_file(  ) {

			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				//require_once ABSPATH . 'wp-admin/includes/image.php'; // Added 4/20/2021 // breaks ingestion WP6 Dev
				require_once ABSPATH . 'wp-admin/includes/media.php'; // Added 4/20/2021
			}
			

			// Move upload file
			$uploadedfile = ! empty( $_FILES['file'] ) ? $_FILES['file'] : array();  //phpcs:ignore
			$file_name = ! empty( $_FILES['file']['name'] ) ? basename( $_FILES['file']['name'] ) : ''; //phpcs:ignore
			$time = gmdate( 'd-m-Y' ) . '-' . time();
			$fty = $time . '-' . $file_name;
			$upload_overrides = array(
				'test_form' => false,
				'unique_filename_callback' => $fty,
			);
			
			//print_r( $uploadedfile );
			//print_r( $_FILES );
			
			add_filter( 'upload_dir', array( $this, 'wpai_set_custom_upload_folder' ) );
			// $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

			// Add file to wordpress media
			$attachment_id = media_handle_upload( 'file', 0 );
			//$attachment_id = media_handle_upload( 'file', $post_id );
			
			//echo 'attachment_id: '. $attachment_id;
			//echo $attachment_id;
			//print_r( $attachment_id );
			
			if ( ! is_wp_error( $attachment_id ) ) {
				update_post_meta( $attachment_id, 'folder', 'box-list' );
				//array_push( $attach_ids, $attachment_id ); // causing error, $attach_ids should be array, null given.

				$request_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
				//echo 'File Upload Successfully -> ' . esc_attr( $request_page );
			} else {
				//echo esc_attr( $movefile['error'] );
			}

			remove_filter( 'upload_dir', array( $this, 'wpai_set_custom_upload_folder' ) );
			
			
			$response = array(
				"attachment_id" => $attachment_id,
				"test" => 'test'
			);
			
			echo json_encode( $response );

			die();
		}

		/**
		 * Add custom JS.
		 */
		public function wpsc_print_ext_js_create_ticket() {

			wp_enqueue_style( 'datatable-style', WPSC_PLUGIN_URL . 'asset/lib/DataTables/datatables.min.css', array(), time(), false );
			wp_enqueue_script( 'datatable-js', WPSC_PLUGIN_URL . 'asset/lib/DataTables/datatables.min.js', array(), time(), true );

			wp_enqueue_style( 'tagsinput-style', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.3/jquery.tagsinput.css', array(), time(), false );
			//508 Corrections wp_enqueue_script( 'tagsinput-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.3/jquery.tagsinput.js', array(), time(), true );
	        wp_enqueue_script( 'tagsinput-js', WPPATT_PLUGIN_URL . 'includes/admin/js/jquery.tagsinput.js', array(), time(), true );

			wp_enqueue_style( 'added-global-style', WPPATT_PLUGIN_URL . 'includes/admin/css/global-styles.css', array(), time(), false );
			
			wp_enqueue_style( 'datatable-checkbox-style', '//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css', array(), time(), false );
			//508 Corrections wp_enqueue_script( 'datatable-checkbox-js', '//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js', array(), time(), true );
	        wp_enqueue_script( 'datatable-checkbox-js', WPPATT_PLUGIN_URL . 'asset/lib/DataTables/dataTables.checkboxes.min.js', array(), time(), true );
	        
			wp_enqueue_style( 'dropzone-style', WPPATT_PLUGIN_URL . 'asset/css/dropzone.min.css', array(), time(), false );
			wp_enqueue_script( 'dropzone-js', WPPATT_PLUGIN_URL . 'asset/js/dropzone.min.js', array(), time(), true );

			wp_enqueue_script( 'xlsx-full-js', 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.14.5/xlsx.full.min.js', array(), time(), true );
    
if($_GET['page'] == 'wpsc-tickets'){
            wp_enqueue_script( 'save-ticket-boxlist-js', WPPATT_PLUGIN_URL . 'asset/js/ticket_box_list_save.js', array(), time(), true );

			wp_localize_script(
				'save-ticket-boxlist-js',
				'attachment_info',
				array(
					'max_filesize' => get_option( 'wpsc_attachment_max_filesize' ),
					'close_image' => WPSC_PLUGIN_URL . 'asset/images/close.png',
				)
			);
}

		}

		/**
		 * Save Boxlist Records
		 *
		 * @param Array $data box list data as array.
		 */
		public function patt_process_boxinfo_records( $data ) {
			global $wpdb, $wpscfunction, $current_user;
			
			// Get Ticket ID
			$ticket_id = $data['ticket_id'];
      $request_id = Patt_Custom_Func::ticket_to_request_id( $ticket_id );
      
      // Get Superfund flag
      $superfund = isset( $data['super_fund'] ) ? $data['super_fund'] : false;
      $superfund = $superfund == 'false' ? false : true;
      
      // Get Update Flag
      $update_flag = isset( $data['update_box_list'] ) ? $data['update_box_list'] : false;
      $update_flag_txt = isset( $data['update_box_list'] ) ? 'true' : 'false';
            
			// Update request id
			$wpdb->update( $wpdb->prefix . 'wpsc_ticket', array( 'request_id' => $request_id ), array( 'id' => $ticket_id ) );
			
			// Add Super Fund flag to ticket_meta
			$wpscfunction->add_ticket_meta( $ticket_id, 'super_fund', $data['super_fund'] );
			
			// variables for storing lan_id obj in DB with min memory usage.
			$lan_id_name_array = [];
			$lan_id_obj_array = [];
			$lan_id_box_array = [];
			
			// Create Restore array if this is a boxlist update. Not applicable for new Requests.
			if( $update_flag ) {
				$restore_arr = $this->create_restore_array( $ticket_id );
			}
			
			// If statement can be altered to reduce code.
			if( !$superfund ) {
				// New BoxInfo Code.
				$boxinfodata = stripslashes( $data['box_info'] );
				$boxinfo_array = json_decode( $boxinfodata, true );
				
			} else {
				// New SEMS BoxInfo
				$boxinfodata = stripslashes( $data['box_info'] );
				
				// OLD: before SEMS dropzone merged with ECMS
				//$boxinfodata = stripslashes( $data['superfund_data'] );
				$boxinfo_array = json_decode( $boxinfodata, true );
				
			}
			
			// Pre-loop Defaults
			$box = '';
			$row_counter = 1;
			$folder_file_counter = 1;
			$folder_file_sub_counter = 1;
			$box_id_legacy = $request_id . '-1'; 
			$epa_contact_legacy = '';
			$is_parent = true;
			$new_box_child = false;
			$sems_check_site_name;
			$sems_check_site_id;
			$num_in_request = count( $boxinfo_array );
			
			
			// DEBUG START
/*
			ob_start();
			?>
			<div class="col-sm-12 ticket-error-msg">
				<?php esc_html_e( 'Early Test.', 'pattracking' ); ?>
				<br><br>
				<?php 
					echo 'superfund: ' . $superfund . '<br>';
					echo 'superfund bool: ' . is_bool($superfund) . '<br>'; 
					
					echo 'box_id_legacy : ' . $box_id_legacy . '<br>'; 
					echo ': '   . '<br>';
					//echo 'boxinfo_array: <pre>' . $boxinfo_array . '</pre><br>';
					echo '<pre>';
					print_r( $restore_arr );
					echo '</pre>';
					echo '<pre>';
					print_r( $data );
					echo '</pre>';
				?>
				<br>
				<pre>
				<?php print_r( $boxarray );  ?>
				</pre>
			</div>
			<?php
			$ticket_error_message = ob_get_clean();

			$response = array(
				'redirct_url'    => '',
				'thank_you_page' => $ticket_error_message,
			);

			echo json_encode( $response );
			die();
*/
			// DEBUG END
			
			// Loop Details
			// If new Box in Request
			// Insert folder doc info - OLD changed up
			// PREP DATA for Folder Doc Info Files
			// Validation
			//   If !validation --> delete ticket
			// ELECTRONIC FILE - Folder Doc Info File
			// PAPER FILE - Folder Doc Info File
			//   !superfund
			//   & superfun
			
			

			// Loop through box data
			foreach ( $boxinfo_array as $index => $boxinfo ) {
				
				// Set Box ID and box number
				$box_id = $request_id . '-' . $boxinfo['Box'];
				$box_num = $boxinfo['Box'];
				//$is_new_box = !( $box_num !== $box );
				
				$boxinfo['Parent/Child'] = trim(strtoupper($boxinfo['Parent/Child']));
				
				if( $boxinfo['Parent/Child'] == 'P' ) {
					$parent_child_single = 'parent';
					if( $new_box_child ) {
						$new_box_child = false;
					}
				} elseif( $boxinfo['Parent/Child'] == 'C' ) {
					$parent_child_single = 'child';
				} else {
					//$parent_child_single = 'single';
					$parent_child_single = 'parent';
				}
				
				
				// Reset row_counter for proper box_id when new Box is detected. 
				// If New Box
				if( $box_id != $box_id_legacy ) {
					$row_counter = 1;
					$box_id_legacy = $box_id;
					$is_new_box = true;
					
					// If child AND new Box, then keep numbering from last parent
					if( $parent_child_single == 'child' ) {
						// keep numbering the same. No change. 
						$new_box_child = true;
					} else {
						// New Box and Parent .:. reset counter
						$folder_file_counter = 1;
					}
					
					//$folder_file_counter = 1;
				} else {
					$is_new_box = false;
				}
				
				// Get final values for Site Name and Site ID for SEMS
				if(  $superfund && ( $index == $num_in_request - 1 ) ) {
					$sems_check_site_name = $boxinfo['Site Name'];
					
					$site_id_string = $boxinfo['Site ID # / OU'];
					$site_id_array = explode( "/", $site_id_string );
					$sems_check_site_id = trim( $site_id_array[0] );
				}
				
				
				
				//
				// If new Box in Request
				//

				if ( $box !== $box_num ) {
					
					$box_validation = true;
					
					// Disposition Schedule Number //old: Record Schedule Number
					$record_schedule_number_break = explode( ':', $boxinfo['Disposition Schedule & Item Number'] );
					$record_schedule_number = trim( str_replace( array( '[', ']' ), '', $record_schedule_number_break[0] ) );
					
					$program_office_break = explode( ':', $boxinfo['Program Office'] );
					$program_office_id = trim( $program_office_break[0] );

					
					// Program Office
					if( !$superfund ) {
						$region_id = null;
					} else {
						// SEMS Default
						//$program_office_id = 'OLEM-OSRTI';
						
						// Get DOC_REGID
						$program_office_break = explode( ':', $boxinfo['Program Office'] );
						$region_break = explode( '-', $program_office_break[0] );
						$region_code = $region_break[0];
						
						//if( $region_code )
						$is_reg = strpos( $region_code, 'R' );
						if( $is_reg === 0 ) {
							$region_id = str_replace( 'R', '', $region_code);
						} else {
							$region_id = '11';
						}
						
						
					}
					
					// Box Insertion or Update
									
					if( !$update_flag ) {
						
						// Box information for insertion
						$boxarray = array(
							'box_id' => $box_id,
							'ticket_id' => $ticket_id,
							// 'location' => $boxinfo["Location"],
							// 'bay' => '1',
							'storage_location_id' => $this->get_new_storage_location_row_id(),
							'location_status_id' => 1,
							//'lan_id' => $lan_id, 
							//'lan_id_details' => $lan_json,
							'program_office_id' => $this->get_programe_office_id( $program_office_id ),
							'record_schedule_id' => $this->get_record_schedule_id( $record_schedule_number ),
							'date_created' => gmdate( 'Y-m-d H:i:s' ),
							'date_updated' => gmdate( 'Y-m-d H:i:s' ),
						);
	
						// Create boxinfo record
						$boxinfo_id = $this->create_new_boxinfo( $boxarray );
						
						// D E B U G
						//$box_validation = false;
						
						// Error handling
						if( $boxinfo_id === 0 ) {
  						$box_validation = false;
						}
						
						// save box_id FK into array for targeting lan_id update after for loop
						$lan_id_box_array[] = $boxinfo_id; 
					} else {
						
						// Box information for updation
						$boxarray = array(
							'box_id' => $box_id,
							'ticket_id' => $ticket_id,
							// 'location' => $boxinfo["Location"],
							// 'bay' => '1',
							//'storage_location_id' => $this->get_new_storage_location_row_id(),
							//'location_status_id' => 1,
							//'lan_id' => $lan_id, 
							//'lan_id_details' => $lan_json,
							'program_office_id' => $this->get_programe_office_id( $program_office_id ),
							'record_schedule_id' => $this->get_record_schedule_id( $record_schedule_number ),
							//'date_created' => gmdate( 'Y-m-d H:i:s' ),
							'date_updated' => gmdate( 'Y-m-d H:i:s' ),
						);
						
						$where = array(
							'box_id' => $box_id
						);
						
						// Update boxinfo record
						$num_rows = $this->update_boxinfo( $boxarray, $where );
						
						// D E B U G
						//$box_validation = false;
						
						// Error handling
						if( !$num_rows ) {
							$box_validation = false;
						}
						
						// Get the FK for the box
						$boxinfo_id = Patt_Custom_Func::get_id_by_box_id( $box_id );
						
						// Need to use the new functions in the custom functions?
						
						// save box_id FK into array for targeting lan_id update after for loop
						//$lan_id_box_array[] = $boxinfo_id; 

						
					}
					
					// tracking for new box
					$box = $box_num;

					// D E B U G - START
/*
					ob_start();
					?>
					<div class="col-sm-12 ticket-error-msg">
						<?php esc_html_e( 'Angry Test.', 'pattracking' ); ?>
						<br><br>
						<?php 
							echo 'superfund: ' . $superfund . '<br>';
							echo 'superfund bool: ' . is_bool($superfund) . '<br>'; 
							echo 'boxinfo_id: ' . $boxinfo_id . '<br>';
							echo 'box: ' . $box . '<br>';
							//echo 'boxinfo_array: <pre>' . $boxinfo_array . '</pre><br>';
							echo '<pre>';
							print_r( $boxinfo );
							echo '</pre>';
						?>
						<br>
						<pre>
						<?php print_r( $boxarray );  ?>
						</pre>
					</div>
					<?php
					$ticket_error_message = ob_get_clean();
		
					$response = array(
						'redirct_url'    => '',
						'thank_you_page' => $ticket_error_message,
					);
		
					echo json_encode( $response );
					die();
*/
					// DEBUG - END
					
					
					//
					// If Box not inserted properly, delete the ticket and abort ingestion (or restore old data and abort)
					//

// 					if ( $boxinfo_id === 0 ) {
  				if( !$box_validation ) {
    				
    				// If this is for a NEW ingestion file
    				if( !$update_flag ) {
    				
  						// if, Box not inserted, delete the ticket.
  						$delete_ticket = apply_filters( 'request_ticket_delete', $ticket_id );
  						
            } else {
              // ELSE this is for an existing file, and data must be restored.
              
              //restore_previous_boxlist( $ticket_id, $restore_arr ); 
              
              //OLD
              $box_table = $wpdb->prefix . 'wpsc_epa_boxinfo';
              $fdif_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
              $error_arr = [];
              
              foreach( $restore_arr["boxes"] as $box_key => $the_box ) {
                
                //$pallet = $the_box->pallet_id == '' ? 'x' : $the_box->pallet_id;
                $pallet = $the_box->pallet_id;
                                
                $box_update_arr = [
                  'box_id' => $the_box->box_id,
                  'box_status' => $the_box->box_status,
                  'ticket_id' => $the_box->ticket_id,
                  'storage_location_id' => $the_box->storage_location_id,
                  'location_status_id' => $the_box->location_status_id,
                  'program_office_id' => $the_box->program_office_id,
                  'record_schedule_id' => $the_box->record_schedule_id,
                  'box_destroyed' => $the_box->box_destroyed,
                  'pallet_id' => $pallet,
//                   'pallet_id' => 'x',
                  'scan_list_id' => $the_box->scan_list_id,
                  'date_created' => $the_box->date_created,
                  'date_updated' => $the_box->date_updated
                ];
                
                $box_where = [
                  'ID' => $the_box->id
                ];
                
                
                $error_check = $wpdb->update( $box_table, $box_update_arr, $box_where );
                
                // D E B U G
                $error_check_text = $error_check === false ? 'false' : 'true';
                
                if( !$error_check ) {
                  $error_arr['box'][ $the_box->id ] = $the_box->id;
                  //$error_arr[''][] = $the_box->pallet_id
                }
                                
                //$error_arr[] = $the_box->id;
                //$error_arr[] = $box_update_arr;
                
              }
              
            }
            
            // Display the message
            
            ob_start();
						?>
						<div class="col-sm-12 ticket-error-msg">
							<h3><?php esc_html_e( 'Error', 'pattracking' ); ?></h3>
							<?php esc_html_e( 'Error entering box information. Ticket not generated.', 'pattracking' ); ?>
							<?php esc_html_e( 'If this error persists, please copy this error message and the details below, and send them to the development team.', 'pattracking' ); ?>
							<br><br>
							<?php 
/*
									echo 'Box: ' + $box_num + '<br>';
									echo 'Row in box: ' + $row_counter + '<br>';
									
									echo 'epa_contact: ' + $epa_contact + '<br>';
									echo 'lan_id: ' + $lan_id + '<br>';
									echo 'lan_json: <br>';
									echo '<pre>';
									print_r( $lan_json );
									echo '</pre>';
*/
									echo 'update_flag: ' . $update_flag . '<br>';
									echo 'update_flag_txt: ' . $update_flag_txt . '<br>';
									echo 'test: ' . $restore_arr["boxes"][0]->id . '<br>';
									echo 'error_check: ' . $error_check . '<br>';
									echo 'error_check_text: ' . $error_check_text . '<br>';
									echo 'pallet: ' . $pallet . '<br>';
									
/*
									echo '<pre>';
									print_r( $restore_arr ); //$restore_arr //$boxarray
									echo '</pre>';
*/
									echo '<pre>';
									print_r( $error_arr ); //$restore_arr //$boxarray //$error_arr //$restore_arr["boxes"]
									echo '</pre>';
									echo '<pre>';
									print_r( $box_update_arr );
									echo '</pre>';								
								
							?>
							<br>							
						</div>
						<?php
						$ticket_error_message = ob_get_clean();

						$response = array(
							'redirct_url'    => '',
							'thank_you_page' => $ticket_error_message,
						);

						echo json_encode( $response );
						die();
            
            
					} else { 
					
					//
					// D E B U G
					//

/*
						ob_start();
						?>
						<div class="col-sm-12 ticket-error-msg">
							<?php esc_html_e( 'Data seems to be good.', 'pattracking' ); ?>
							<br><br>
							<?php 
								echo 'superfund: ' . $superfund . '<br>';
								echo 'superfund bool: ' . is_bool($superfund) . '<br>'; 
								
								//echo 'boxinfo_array: <pre>' . $boxinfo_array . '</pre><br>';
								echo '<pre>';
								print_r( $boxarray );
								echo '</pre>';
								echo '<pre>';
								print_r( $boxinfo_array );
								echo '</pre>';
							?>
							<br>
							<pre>
							<?php print_r( $boxarray );  ?>
							</pre>
						</div>
						<?php
						$ticket_error_message = ob_get_clean();

						$response = array(
							'redirct_url'    => '',
							'thank_you_page' => $ticket_error_message,
						);

						echo json_encode( $response );
						die();
*/
					}
					
				} // end new box
				
				
				// EPA Contact for Lan ID and Lan ID Details
				
				
				//
				// Insert folder doc info 
				//
				
				
// 				if( $parent_child_single != 'child' || ( $box !== $box_num ) ) {
				//if( $parent_child_single != 'child' ) {
				// Now: every entry sans child
					
					
					
/*
					$folderdocarray = array(
						'folderdocinfo_id' => $docinfo_id,
						'author' => "{$boxinfo['Creator']}",
						'addressee' => "{$boxinfo['Addressee']}",
						'site_name' => "{$boxinfo['Site Name']}",
						'siteid' => "{$boxinfo['Site ID #']}",
						//'close_date' => $new_date->format( 'Y-m-d H:i:s' ),
						'close_date' => $new_date,
						'folder_identifier' => "{$boxinfo['Folder Identifier']}",
						'box_id' => $boxinfo_id,
						'essential_record' => "{$essential_record}",
						'date_created' => gmdate( 'Y-m-d H:i:s' ),
						'date_updated' => gmdate( 'Y-m-d H:i:s' ),
					);
*/
					
					//
					// D E B U G
					//
/*
					ob_start();
					?>
					<div class="col-sm-12 ticket-error-msg">
						<?php esc_html_e( 'Before the File.', 'pattracking' ); ?>
						<br><br>
						<?php 
							echo 'superfund: ' . $superfund . '<br>';
							echo 'superfund bool: ' . is_bool($superfund) . '<br>'; 
							echo 'essential_record: ' . $essential_record . '<br>';
							//echo 'boxinfo_array: <pre>' . $boxinfo_array . '</pre><br>';
							echo '<pre>';
							print_r( $folderdocarray );
							echo '</pre>';
							echo '<pre>';
							print_r( $boxinfo_array );
							echo '</pre>';
						?>
						<br>
						<pre>
						<?php print_r( $boxarray );  ?>
						</pre>
					</div>
					<?php
					$ticket_error_message = ob_get_clean();

					$response = array(
						'redirct_url'    => '',
						'thank_you_page' => $ticket_error_message,
					);

					echo json_encode( $response );
					die();
*/
					// D E B U G - END
					
					
					// Save into Folder Doc Info
					//$folderdocinfo_id = $this->create_new_folderdocinfo( $folderdocarray );
					//$row_counter++;
					

					
					//
					// D E B U G - START
					//
/*
					ob_start();
					?>
					<div class="col-sm-12 ticket-error-msg">
						<?php esc_html_e( 'Folder Test.', 'pattracking' ); ?>
						<br><br>
						<?php 
							echo 'superfund: ' . $superfund . '<br>';
							echo 'site_id: ' . $folderdocarray['site_id'] . '<br>';
							echo 'site_id type: ' . gettype( $folderdocarray['site_id'] ) . '<br>';
							echo 'author: ' . $folderdocarray['author'] . '<br>'; 
							echo 'author type: ' . gettype( $folderdocarray['author'] ) . '<br>'; 
							//echo 'boxinfo_array: <pre>' . $boxinfo_array . '</pre><br>';
							echo '<pre>';
							print_r( $folderdocarray );
							echo '</pre>';
							echo '<pre>';
							print_r( $boxinfo_array );
							echo '</pre>';
						?>
						<br>
						<pre>
						<?php print_r( $boxarray );  ?>
						</pre>
					</div>
					<?php
					$ticket_error_message = ob_get_clean();

					$response = array(
						'redirct_url'    => '',
						'thank_you_page' => $ticket_error_message,
					);

					echo json_encode( $response );
					die();
*/
					// DEBUG - END

					
				//} // Remove when removing $boxinfo['Folder/Filename'] check. Allows parent to be electronic // ADDED new if !child
				
				
				//
				// PREP DATA for Folder Doc Info Files
				// Add data to folderdocinfo_files
				//
				
				// for naming convention
				$row_counter++;
				
				// Set superfund and ECMS defaults - edit: currently the same.
				//if( !$superfund ) {

				$index_level = strtolower( $boxinfo['Index Level'] ) == 'file' ? 2 : 1;
				$essential_record = 'Yes' == $boxinfo['Essential Record'] ? '1' : '0';
				$docinfo_id = $request_id . '-' . $boxinfo['Box'] . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $row_counter;
					
/*
				} else {
					// Superfund defaults
					$index_level = strtolower( $boxinfo['Index Level'] ) == 'file' ? 2 : 1;
					$essential_record = 'Yes' == $boxinfo['Essential Record'] ? '1' : '0';
					$docinfo_id = $request_id . '-' . $boxinfo['Box'] . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $row_counter;
					
				}
*/
				
				
				
				// Prep Close date
				if( $boxinfo['Close Date'] == '' ) {
					$new_date = '';
				} else {
					$datetime = new DateTime();
					//$new_date = $datetime->createFromFormat( 'm/d/Y H:i:s', $boxinfo['Close Date']);
					$new_date = $datetime->createFromFormat( 'm/d/Y', $boxinfo['Close Date']);
					//$new_date = $new_date->format( 'Y-m-d H:i:s' );
					$new_date = $new_date->format( 'Y-m-d' );
				}
				
				
				
				// Get & Set folderdocinfofile_id
				if( $parent_child_single == 'parent' ) {
					
					$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
					$folder_file_sub_counter = 1; //Reset for new file folder
					$folder_file_sub_id = $folder_file_id;
					
					
					$is_parent = true;
					
				} elseif( $parent_child_single == 'child') {
					
					// If not new box, decrement folder file counter to keep child name same as parent.
					// if new box, then folder_file_counter = 1; 
					
					if( !$is_new_box || $new_box_child ) {
						$folder_file_counter--;
					}
					
					
					
					$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
					$folder_file_sub_id = $folder_file_id . '-a' . $folder_file_sub_counter;
					$folder_file_sub_counter++; //Increment it for next file.
					
					$is_parent = false;
					
				} /*
elseif( $parent_child_single == 'single' ) {  // NOT REAL ANYMORE
					$folder_file_counter++;
					$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
					$folder_file_sub_counter = 1; //Reset for new file folder
					$folder_file_sub_id = $folder_file_id;
					
					$is_parent = false;
				}
*/
				
				// Get & Set relation_part & relation_part_of
				$part_match = preg_match( "/\[Part(.*?)]/", $boxinfo['Title'], $matches);
				$clean = trim($matches[1]);
				
				$x_of_y_match = preg_match( "/(\d+)(.of.?)(\d+)/", $clean, $matches_2);
				
				if ( $part_match && $x_of_y_match ) {
				    $x_of = $matches_2[1];
				    $of_y = $matches_2[3];
				} else {
					$x_of = '';
					$of_y = '';
				}
				
				if( !$superfund ) {
					$source_format = "{$boxinfo['Source Type']}";
				} else {
					//$source_format = 'Paper';
					$source_format = "{$boxinfo['Source Type']}";
				}
				
				// EPA Contact for Lan ID and Lan ID Details
				$lan_id = trim( $boxinfo['EPA Contact'] );
				$lan_json = '';
				
				// if lan_id is not in the name_array, add it.
				if( array_search( $lan_id, $lan_id_name_array ) === false ) {
					$lan_id_name_array[] = $lan_id;
				}
				
				
				//
				// Validation
				//
				
				$validation = true;
				
				// Fetch lan id and json
				$table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
				$inserted = [];
					
				// Real - for Staging and Production Servers
				//$lan_id = Patt_Custom_Func::lan_id_check( $a_lan_id, $request_id );
				//$lan_json = Patt_Custom_Func::lan_id_to_json( $lan_id );
					
				// Fake for D E B U G 
				//$lan_json = '{"name":"Andrew Yuen","email":"Yuen.Andrew@epa.gov","phone":"202-510-6390","org":"HGA00000","lan_id":"' . $lan_id . '"}';
				//$lan_json = 'Error';
				
				
				// Error Checking
/*
				if( $lan_id == 'LAN ID cannot be assigned' || $lan_id == null || $lan_id == '' ) {
					$validation = false;
					$val_type = 'single';
					$err_message_1 = 'The lan_id used in the "EPA Contact" column caused an error. This may be due to it being an invalid lan_id or due to the validating server.';
					
				}
*/
				
				// Error Checking
/*
				if( $lan_json == 'Error' || $lan_json == null || $lan_json == '' ) {
					$validation = false;
					$val_type = 'single';
					$err_message_1 = 'The lan_id used in the "EPA Contact" column caused an error for lan_json. This may be due to it being an invalid lan_id or due to the validating server.';
				}
*/
					
				// if lan_id is not in the name_array, add it.
				if( array_search( $lan_json, $lan_id_obj_array ) === false ) {
					$lan_id_obj_array[] = $lan_json;
				}
					
					

				// Specific Access Restrictions - Validation Check
				if( $boxinfo['Access Restrictions'] == 'Yes' && $boxinfo['Specific Access Restrictions'] == '' ) {
					$validation = false;
					$val_type = 'a-b';
					
					$col_a_key = 'Access Restrictions';
					$col_a_val = $boxinfo['Access Restrictions'];
					$col_b_key = 'Specific Access Restrictions';
					//$col_b_val = $boxinfo['Specific Access Restrictions'];
					$col_b_val = '[empty]';
					
				} elseif( $boxinfo['Access Restrictions'] == 'No' && $boxinfo['Specific Access Restrictions'] != '' ) {
					$validation = false;
					$val_type = 'a-b';
					
					$col_a_key = 'Access Restrictions';
					$col_a_val = $boxinfo['Access Restrictions'];
					$col_b_key = 'Specific Access Restrictions';
					$col_b_val = $boxinfo['Specific Access Restrictions'];
					
				} 
								
				// Use Restrictions - Validation Check
				if( $boxinfo['Use Restrictions'] == 'Yes' && $boxinfo['Specific Use Restrictions'] == '' && $validation == true ) {
					$validation = false;
					$val_type = 'a-b';
					
					$col_a_key = 'Use Restrictions';
					$col_a_val = $boxinfo['Use Restrictions'];
					$col_b_key = 'Specific Use Restrictions';
					//$col_b_val = $boxinfo['Specific Use Restrictions'];
					$col_b_val = '[empty]';
					
				} elseif( $boxinfo['Use Restrictions'] == 'No' && $boxinfo['Specific Use Restrictions'] != '' && $validation == true ) {
					$validation = false;
					$val_type = 'a-b';
					
					$col_a_key = 'Use Restrictions';
					$col_a_val = $boxinfo['Use Restrictions'];
					$col_b_key = 'Specific Use Restrictions';
					$col_b_val = $boxinfo['Specific Use Restrictions'];
					
				} 
				
				// Site Name & Site ID - Validation Check
				if( $boxinfo['Site Name'] == '' && $validation == true ) {
					
/*
					$validation = false;
					$val_type = 'single';
					
					$col_key = 'Site Name';
					$col_val = $boxinfo['Site Name'];
					$err_message_1 = 'Blank value for column <b>' . $col_key .'</b><br>';
*/
					
				} elseif( $boxinfo['Site ID # / OU'] == '' && $validation == true ) {
					
/*
					$validation = false;
					$val_type = 'single';
					
					$col_key = 'Site ID #';
					$col_val = $boxinfo['Site ID #'];
					$err_message_1 = 'Blank value for column <b>' . $col_key .'</b><br>';
					$err_message_2 = ' ';
*/
					
				} elseif( $boxinfo['Site Name'] != '' && $boxinfo['Site ID # / OU'] != '' && $validation == true ) {
					//$validation = true;
					//$validation = false;
					// hit api to get Site Name from Site ID #.
					// Compare Site Name with returned Site Name
					
				} 
				
				//
				// if validation failed, delete ticket, associated data, and display error message.
				//
				
				if( !$validation ) {
					
					// If NOT update, delete ticket. 
					// Else, restore previous ticket
					if( !$update_flag ) {
					
  					// delete the ticket.
  					$delete_ticket = apply_filters( 'request_ticket_delete', $ticket_id );
  
  					
  					
  				} else { 
    				// Else, restore previous ticket
  				  
  				  // ELSE this is for an existing file, and data must be restored.
/*
            $box_table = $wpdb->prefix . 'wpsc_epa_boxinfo';
            $fdif_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
            $error_arr = [];
            
            foreach( $restore_arr["boxes"] as $box_key => $the_box ) {
              
              //$pallet = $the_box->pallet_id == '' ? 'x' : $the_box->pallet_id;
              $pallet = $the_box->pallet_id;
                              
              $box_update_arr = [
                'box_id' => $the_box->box_id,
                'box_status' => $the_box->box_status,
                'ticket_id' => $the_box->ticket_id,
                'storage_location_id' => $the_box->storage_location_id,
                'location_status_id' => $the_box->location_status_id,
                'program_office_id' => $the_box->program_office_id,
                'record_schedule_id' => $the_box->record_schedule_id,
                'box_destroyed' => $the_box->box_destroyed,
                'pallet_id' => $pallet,
//                   'pallet_id' => 'x',
                'scan_list_id' => $the_box->scan_list_id,
                'date_created' => $the_box->date_created,
                'date_updated' => $the_box->date_updated
              ];
              
              $box_where = [
                'ID' => $the_box->id
              ];
              
              
              $error_check = $wpdb->update( $box_table, $box_update_arr, $box_where );
              
              // D E B U G
              $error_check_text = $error_check === false ? 'false' : 'true';
              
              if( !$error_check ) {
                $error_arr['box'][ $the_box->id ] = $the_box->id;
                //$error_arr[''][] = $the_box->pallet_id
              }
                              
              //$error_arr[] = $the_box->id;
              //$error_arr[] = $box_update_arr;
              
            }
*/
  				  
				  } // else
				  
				  
				  ob_start();
					?>
					<div class="col-sm-12 ticket-error-msg">
						<?php echo '<h3>Validation Failed. Ticket not generated.</h3>' ?>
						
						<?php 
							
							if( $val_type == 'single' ) {
								echo $err_message_1;
								echo 'On Box: <b>' . $boxinfo['Box'] . '</b>, Folder Identifier: <b>' . $boxinfo['Folder Identifier'] . '</b>';
								echo '<br>';
							}
							
							if( $val_type == 'a-b' ) {
								echo 'Discrepancy between <b>' . $col_a_key . '</b> and <b>' . $col_b_key .  '</b><br>';
								echo 'On Box: <b>' . $boxinfo['Box'] . '</b>, Folder Identifier: <b>' . $boxinfo['Folder Identifier'] . '</b>';
								echo '<br>';
								echo '<b>' . $col_a_key . '</b> has value of <b><u>' . $col_a_val 
										. '</u></b> and <b>' . $col_b_key . '</b> has value of <b><u>' .$col_b_val .'</u></b><br>';
							}
							
/*
							echo 'Ticket ID: ' . $ticket_id . ' not created. <br>';
							echo 'delete_ticket: ' . $delete_ticket . ' <br>';
							echo '<pre>';
							print_r( $delete_ticket );
							echo '</pre>';
*/
							
							
							// D E B U G - START
/*
							echo '<br><br>';
							echo '------------------------D-E-B-U-G------------------------' . '<br>';
							echo 'superfund: ' . $superfund . '<br>';
							echo 'mdocs_post_id: ' . $mdocs_post_id . '<br>';
							echo 'folder_file_id: ' . $folder_file_id . '<br>';
							echo 'Folder/Filename: ' . $boxinfo['Folder/Filename'] . '<br>'; 
							echo 'Folder/Filename null: ' . is_null( $boxinfo['Folder/Filename']) . '<br>'; 
							echo 'folderdocfiles_info_id: ' . $folderdocfiles_info_id . '<br>'; 
							echo 'region_code: ' . $region_code . '<br>';
							echo 'region_id: ' . $region_id . '<br>';
							echo 'validation: ' . $validation . '<br>';
							echo 'col_a_key: ' . $col_a_key . '<br>';
							echo 'col_a_val: ' . $col_a_val . '<br>';
							echo 'col_b_key: ' . $col_b_key . '<br>';
							echo 'col_b_val: ' . $col_b_val . '<br>';
							
							echo 'region_break: <br>'; 
							echo '<pre>';
							print_r( $region_break );
							echo '</pre>';
							
							
							echo 'program_office_break: <br>'; 
							echo '<pre>';
							print_r( $program_office_break );
							echo '</pre>';
							
							echo 'Matches: <br>'; 
							echo '<pre>';
							print_r( $matches );
							echo '</pre>';
							echo 'folderdocfiles_info: <br>';
							echo '<pre>';
							print_r( $folderdocfiles_info );
							echo '</pre>';
							
							echo '<pre>';
							print_r( $boxarray );
							echo '</pre>';
*/
							
							// D E B U G - END
						?>
					</div>
					<?php
					$ticket_error_message = ob_get_clean();

					$response = array(
						'redirct_url'    => '',
						'thank_you_page' => $ticket_error_message,
					);

					echo json_encode( $response );
					die();
				  
				  
				} 
				
				// Prep more Data
				
				// Fix issue
				if( is_null( $boxinfo['Specific Access Restrictions'] ) ) {
					$SAR = '';
				} else {
					$SAR = $boxinfo['Specific Access Restrictions'];
				}

				if( is_null( $boxinfo['Specific Use Restrictions'] ) ) {
					$SUR = '';
				} else {
					$SUR = $boxinfo['Specific Use Restrictions'];
				}
				
				if( is_null( $boxinfo['Rights Holder'] ) ) {
					$RH = '';
				} else {
					$RH = $boxinfo['Rights Holder'];
				}
				
				if( $is_parent ) {
					// temp parent_id
					$parent_id = 1;
				} else {
					// use previous $parent_id
				}
				
				$file_name = '';
				
				//$folderdocfiles_id_FAKE = null;
				
				// Convert Date
				$time = strtotime( $boxinfo['Creation Date'] );
				$newdatetimeformat = date( 'Y-m-d' ,$time );
				//$newdatetimeformat = gmdate( 'Y-m-d' );
				//$newdatetimeformat = '1996-11-08';
				
				
				
				// fake data debug
				//$region_id = 1;
				//$file_name = 'fake';
				//$boxinfo['Folder/Filename'] = 'fake';
				
				// 
				// ELECTRONIC FILE - Folder Doc Info File
				// 
				
				// Upload file only if one exists. //SEMS not possible .:. will not have digital files :: but, um like, totally possible.
				if ( $boxinfo['Folder/Filename'] !== '' && $boxinfo['Folder/Filename'] !== null ) {
				
					// Change upload directory.
/*
					add_filter( 'upload_dir', __CLASS__ . '::change_boxinfo_doc_file_upload_dir' );

					// Get filename with extension
					//$file_name = explode( '\\', $boxinfo['Folder/Filename'] );
					$file_name = explode( '/', $boxinfo['Folder/Filename'] );
					$file_name = end( $file_name );

					// Get filename without extension
					$post_title = explode( '.', $file_name );
					$post_title = current( $post_title );

					// Get directory path for mdocs folder
					$upload_dir = wp_get_upload_dir();
					$file_path = $upload_dir['path'] . '/' . $file_name;

					// Check file exist with same name 
					if ( ! file_exists( $file_path ) ) {
						$file = fopen( $file_path, 'w' ); // create file if not exist in folder
						fwrite( $file, '' );
						fclose( $file );
					}
*/
					
/*
					// Convert Date
					$time = strtotime( $boxinfo['Creation Date'] );
					//$newdatetimeformat = date( 'Y-m-d H:i:s' ,$time );
					$newdatetimeformat = date( 'Y-m-d' ,$time );
*/
				
					// 
					// Save into folderdocinfo_files - Electronic File
					//
					
					// NEW
					
/*
					$folderdocarray = array(
						'folderdocinfo_id' => $docinfo_id,
						'author' => "{$boxinfo['Creator']}",
						'addressee' => "{$boxinfo['Addressee']}",
						'site_name' => "{$boxinfo['Site Name']}",
						'siteid' => "{$boxinfo['Site ID #']}",
						'close_date' => $new_date,
						'folder_identifier' => "{$boxinfo['Folder Identifier']}",
						'box_id' => $boxinfo_id,
						'essential_record' => "{$essential_record}",
						'date_created' => gmdate( 'Y-m-d H:i:s' ),
						'date_updated' => gmdate( 'Y-m-d H:i:s' ),
					);
*/
					
					
					
					// ECMS FDIF data
					$folderdocfiles_info = [
						//'folderdocinfo_id'  => $folderdocfiles_id_FAKE, 
						'parent_id' => $parent_id,
						'box_id' => $boxinfo_id,
						'folderdocinfofile_id'   => $folder_file_sub_id,
						'doc_regid' => $region_id,
						'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
						'file_name'  => $file_name,
						'source_file_location' => $boxinfo['Folder/Filename'],
						'title'  => $boxinfo['Title'],
						'date' => $newdatetimeformat,
						'tags' => $boxinfo['Tags'],
						'source_format' => $source_format,
						'index_level' => $index_level,
						'description' => $boxinfo['Description of Record'],
						'access_restriction' => $boxinfo['Access Restrictions'],
						//'specific_access_restriction' => $boxinfo['Specific Access Restrictions'],
						'specific_access_restriction' => $SAR,
						'use_restriction' => $boxinfo['Use Restrictions'],
						//'specific_use_restriction' => $boxinfo['Specific Use Restrictions'],
						'specific_use_restriction' => $SUR,
						'rights_holder' => $RH,
						'source_dimensions' => $boxinfo['Source Dimensions'],
						'relation_part' => $x_of,
						'relation_part_of' => $of_y,
						'record_type' => $boxinfo['Record Type'],
						'author' => "{$boxinfo['Creator']}",
						'addressee' => "{$boxinfo['Addressee']}",
						'site_name' => "{$boxinfo['Site Name']}",
						'siteid' => "{$boxinfo['Site ID # / OU']}",
						'close_date' => $new_date,
						'folder_identifier' => "{$boxinfo['Folder Identifier']}",	
						'essential_record' => "{$essential_record}",
						'program_area' => $boxinfo['Program Area'],
						'lan_id' => $lan_id,
						'lan_id_details' => $lan_json,
						'date_created' => gmdate( 'Y-m-d H:i:s' ),
						'date_updated' => gmdate( 'Y-m-d H:i:s' ),
					];
					
					// If Box is used for Litigation, Congressional, or FOIA 
					if ( $data['ticket_useage'] == 'files_uploaded' ) {
						$folderdocfiles_info['freeze'] = 1;
					}
					
					//
					// Save or Update data in FDIF Tabld
					//
					
					if( !$update_flag ) {
						
						// Save in FDIF DB Table
	 					$table_name = $wpdb->prefix.'wpsc_epa_folderdocinfo_files';
						$diditwork = $wpdb->insert( $table_name, $folderdocfiles_info ); 
						$folderdocfiles_info_id = $wpdb->insert_id;
					} else {
						
						// Update in FDIF DB Table
						unset( $folderdocfiles_info[ 'date_created' ] );
						//$fdif_array = [ 'date_updated' => gmdate( 'Y-m-d H:i:s' ) ];
						
						//$folder_file_sub_id = '0000024-1-02-1';
						
						$where = array(
							'folderdocinfofile_id' => $folder_file_sub_id
						);
						
						//Create boxinfo record
						$num_rows = $this->update_fdif( $folderdocfiles_info, $where );
						
						if( !$num_rows ) {
							// some type of flag for error handling. 
						}
						
					}
					
					
					
					// Remove custom upload directory folder
					//remove_filter( 'upload_dir', __CLASS__ . '::change_boxinfo_doc_file_upload_dir' );  
					
					//
					// D E B U G - START
					//
/*
					ob_start();
					?>
					<div class="col-sm-12 ticket-error-msg">
						<?php esc_html_e( 'Folderdocinfo Files Electronic Debug.', 'pattracking' ); ?>
						<br><br>
						<?php 
							
							echo 'insert id: ' . $folderdocfiles_info_id . '<br>';
							echo 'test 1 : ' . ($boxinfo['Folder/Filename'] !== "") . '<br>';
							echo 'test 2 : ' . ($boxinfo['Folder/Filename'] !== null ) . '<br>';
							echo 'test 3 &&: ' . (($boxinfo['Folder/Filename'] !== "") && $boxinfo['Folder/Filename'] !== null) . '<br>';
							echo 'folder Filename: ' . $boxinfo['Folder/Filename'] . '<br>';
							
							echo 'folderdocfiles_info: <br>';
							echo '<pre>';
							print_r( $folderdocfiles_info );
							echo '</pre>';
						?>
						<br>
						<pre>
						<?php print_r( $boxarray );  ?>
						</pre>
					</div>
					<?php
					$ticket_error_message = ob_get_clean();

					$response = array(
						'redirct_url'    => '',
						'thank_you_page' => $ticket_error_message,
					);

					echo json_encode( $response );
					die();
*/
					// D E B U G - END
					
				} else {
					
					// 
					// PAPER FILE - Folder Doc Info File
					// 
					
					// Prep FDIF data for (if) ECMS and (else) SEMS
					if( !$superfund ) {
												
						// Convert Date
/*
						$time = strtotime( $boxinfo['Creation Date'] );
						//$newdatetimeformat = date( 'Y-m-d H:i:s' ,$time ); 
						$newdatetimeformat = date( 'Y-m-d' ,$time ); 
*/
						
						
						
						
						// Insert data for folderdocinfo_files :: ECMS
						// ECMS FDIF data
/*
						$folderdocfiles_info = [
							'folderdocinfo_id'  => $folderdocinfo_id, 
							'folderdocinfofile_id'   => $folder_file_sub_id,
							'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
							'title'  => $boxinfo['Title'],
							'date' => $newdatetimeformat,
							'tags' => $boxinfo['Tags'],
							'source_format' => $source_format,
							'index_level' => $index_level,
							'description' => $boxinfo['Description of Record'],
							'access_restriction' => $boxinfo['Access Restrictions'],
							//'specific_access_restriction' => $boxinfo['Specific Access Restrictions'],
							'specific_access_restriction' => $SAR,
							'use_restriction' => $boxinfo['Use Restrictions'],
							//'specific_use_restriction' => $boxinfo['Specific Use Restrictions'],
							'specific_use_restriction' => $SUR,
							'rights_holder' => $RH,
							'source_dimensions' => $boxinfo['Source Dimensions'],
							'relation_part' => $x_of,
							'relation_part_of' => $of_y,
							'record_type' => $boxinfo['Record Type'],
						];
*/
						
						$folderdocfiles_info = [
							//'folderdocinfo_id'  => $folderdocfiles_id_FAKE, 
							'parent_id' => $parent_id,
							'box_id' => $boxinfo_id,
							'folderdocinfofile_id'   => $folder_file_sub_id,
							'doc_regid' => $region_id,
							'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
							'file_name'  => $file_name,
							'source_file_location' => $boxinfo['Folder/Filename'],
							'title'  => $boxinfo['Title'],
							'date' => $newdatetimeformat,
							'tags' => $boxinfo['Tags'],
							'source_format' => $source_format,
							'index_level' => $index_level,
							'description' => $boxinfo['Description of Record'],
							'access_restriction' => $boxinfo['Access Restrictions'],
							'specific_access_restriction' => $SAR,
							'use_restriction' => $boxinfo['Use Restrictions'],
							'specific_use_restriction' => $SUR,
							'rights_holder' => $RH,
							'source_dimensions' => $boxinfo['Source Dimensions'],
							'relation_part' => $x_of,
							'relation_part_of' => $of_y,
							'record_type' => $boxinfo['Record Type'],
							'author' => "{$boxinfo['Creator']}",
							'addressee' => "{$boxinfo['Addressee']}",
							'site_name' => "{$boxinfo['Site Name']}",
							'siteid' => "{$boxinfo['Site ID # / OU']}",
							'close_date' => $new_date,
							'folder_identifier' => "{$boxinfo['Folder Identifier']}",	
							'essential_record' => "{$essential_record}",
							'program_area' => $boxinfo['Program Area'],
							'lan_id' => $lan_id,
							'lan_id_details' => $lan_json,
							'date_created' => gmdate( 'Y-m-d H:i:s' ),
							'date_updated' => gmdate( 'Y-m-d H:i:s' ),
						];

					} else {
						// Prep FDIF data for SEMS - SUPERFUND
						
						// appears to not be needed. Saving for an hour or so. 
						// $folder_file_id = $request_id . '-' . $boxinfo['Box'] . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						
						// Convert Date
/*
						$time = strtotime( $boxinfo['Creation Date'] );
						//$newdatetimeformat = date( 'Y-m-d H:i:s', $time );
						$newdatetimeformat = date( 'Y-m-d', $time );
*/
						
						// Insert data for folderdocinfo_files :: SEMS Superfund
/*
						$folderdocfiles_info = [
							'folderdocinfo_id'  => $folderdocinfo_id, 
							'folderdocinfofile_id'   => $folder_file_sub_id,
							'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
							'title'  => $boxinfo['Title'],
							'date' => $newdatetimeformat,
							'tags' => $boxinfo['Tags'],
							'doc_regid' => $region_id,
							'source_format' => $source_format,
							'index_level' => $index_level,
							'description' => $boxinfo['Description of Record'],
							'access_restriction' => $boxinfo['Access Restrictions'],
							//'specific_access_restriction' => $boxinfo['Specific Access Restrictions'],
							'specific_access_restriction' => $SAR,
							'use_restriction' => $boxinfo['Use Restrictions'],
							//'specific_use_restriction' => $boxinfo['Specific Use Restrictions'],
							'specific_use_restriction' => $SUR,
							'rights_holder' => $RH,
							'source_dimensions' => $boxinfo['Source Dimensions'],
							'relation_part' => $x_of,
							'relation_part_of' => $of_y
						];
*/

						
						
						$folderdocfiles_info = [
							//'folderdocinfo_id'  => $folderdocfiles_id_FAKE, 
							'parent_id' => $parent_id,
							'box_id' => $boxinfo_id,
							'folderdocinfofile_id'   => $folder_file_sub_id,
							'doc_regid' => $region_id,
							'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
							'file_name'  => $file_name,
							'source_file_location' => $boxinfo['Folder/Filename'],
							'title'  => $boxinfo['Title'],
							'date' => $newdatetimeformat,
							'tags' => $boxinfo['Tags'],
							'source_format' => $source_format,
							'index_level' => $index_level,
							'description' => $boxinfo['Description of Record'],
							'access_restriction' => $boxinfo['Access Restrictions'],
							'specific_access_restriction' => $SAR,
							'use_restriction' => $boxinfo['Use Restrictions'],
							'specific_use_restriction' => $SUR,
							'rights_holder' => $RH,
							'source_dimensions' => $boxinfo['Source Dimensions'],
							'relation_part' => $x_of,
							'relation_part_of' => $of_y,
							'record_type' => $boxinfo['Record Type'],
							'author' => "{$boxinfo['Creator']}",
							'addressee' => "{$boxinfo['Addressee']}",
							'site_name' => "{$boxinfo['Site Name']}",
							'siteid' => "{$boxinfo['Site ID # / OU']}",
							'close_date' => $new_date,
							'folder_identifier' => "{$boxinfo['Folder Identifier']}",	
							'essential_record' => "{$essential_record}",
							'program_area' => $boxinfo['Program Area'],
							'lan_id' => $lan_id,
							'lan_id_details' => $lan_json,
							'date_created' => gmdate( 'Y-m-d H:i:s' ),
							'date_updated' => gmdate( 'Y-m-d H:i:s' ),
						];
					}
					
					// If Box is used for Litigation, Congressional, or FOIA 
					if ( 'files_uploaded' == $data['ticket_useage'] ) {
						$folderdocfiles_info['freeze'] = 1;
					}
					
					
					
					//
					// Save or Update data in FDIF Table
					//
					
					if( !$update_flag ) {
						
						
						// Insert into folderdocinfo_files for either ECMS or SEMS
	 					$table_name = $wpdb->prefix.'wpsc_epa_folderdocinfo_files';
						$diditwork = $wpdb->insert( $table_name, $folderdocfiles_info ); 
						$folderdocfiles_info_id = $wpdb->insert_id;
						
					} else {
						
						// Update in FDIF DB Table
						unset( $folderdocfiles_info[ 'date_created' ] );
						//$fdif_array = [ 'date_updated' => gmdate( 'Y-m-d H:i:s' ) ];

						
						//$folder_file_sub_id = '0000024-1-02-1';
						
						$where = array(
							'folderdocinfofile_id' => $folder_file_sub_id
						);
						
						//Create boxinfo record
						$num_rows = $this->update_fdif( $folderdocfiles_info, $where );
						
						if( !$num_rows ) {
							// some type of flag for error handling. 
						}
						
					}
					
					
					
					
					//
					// D E B U G - START
					//
/*
					ob_start();
					?>
					<div class="col-sm-12 ticket-error-msg">
						<?php esc_html_e( 'Folderdocinfo Files Test.', 'pattracking' ); ?>
						<br><br>
						<?php 
							echo 'superfund: ' . $superfund . '<br>';
							echo 'folder_file_id: ' . $folder_file_id . '<br>';
							echo 'Specific Access Restrictions: ' . $boxinfo['Specific Access Restrictions'] . '<br>'; 
							echo 'Specific Access Restrictions null: ' . is_null( $boxinfo['Specific Access Restrictions'] ) . '<br>'; 
							echo 'SAR: ' . $SAR . '<br>'; 
							echo 'SAR null: ' . is_null( $SAR ) . '<br>'; 
							echo 'folderdocfiles_info_id: ' . $folderdocfiles_info_id . '<br>'; 
							echo 'region_code: ' . $region_code . '<br>';
							echo 'region_id: ' . $region_id . '<br>';
							echo 'validation: ' . $validation . '<br>';
							echo 'col_a_key: ' . $col_a_key . '<br>';
							echo 'col_a_val: ' . $col_a_val . '<br>';
							echo 'col_b_key: ' . $col_b_key . '<br>';
							echo 'col_b_val: ' . $col_b_val . '<br>';
							
							
							echo 'region_break: <br>'; 
							echo '<pre>';
							print_r( $region_break );
							echo '</pre>';
							
							
							echo 'program_office_break: <br>'; 
							echo '<pre>';
							print_r( $program_office_break );
							echo '</pre>';
							
							echo 'Matches: <br>'; 
							echo '<pre>';
							print_r( $matches );
							echo '</pre>';
							echo 'folderdocfiles_info: <br>';
							echo '<pre>';
							print_r( $folderdocfiles_info );
							echo '</pre>';
						?>
						<br>
						<pre>
						<?php print_r( $boxarray );  ?>
						</pre>
					</div>
					<?php
					$ticket_error_message = ob_get_clean();

					$response = array(
						'redirct_url'    => '',
						'thank_you_page' => $ticket_error_message,
					);

					echo json_encode( $response );
					die();
*/
					// D E B U G - END
					
				} // End - FDIF Electronic or Paper
				
				
				// if parent, set parent_id to self id, store $parent_id for children
				if( $is_parent ) { 
					
					$parent_id = $folderdocfiles_info_id;
					$wpdb->update( $table_name, array( 'parent_id' => $parent_id ), array( 'id' => $parent_id ) );
				}
				
				// Increment folder file counter for naming convention
				$folder_file_counter++;
				
				
				
			} // End of foreach loop
			
			
			//
			// lan_id validation
			//
			
			// Update each new box that was added
			foreach( $lan_id_name_array as $num => $the_lan_id ) {	
				foreach( $lan_id_box_array as $boxid ) {
				
					//$inserted[] = $wpdb->update( $table, array( 'lan_id_details' => $lan_json ), array( 'lan_id' => $lan_id, 'box_id' => $boxid ) );
					$wpdb->update( $table, array( 'lan_id_details' => $lan_id_obj_array[$num] ), array( 'lan_id' => $the_lan_id, 'box_id' => $boxid ) );
					
				}
			}
			
			
/*
			$lan_id_validation = true;
			$table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
			$inserted = [];
			
			// Loop through each unique lan_id, hit the endpoint, update all item's lan_id_details with the same lan_id
			forEach( $lan_id_name_array as $a_lan_id ) {
				
				// Real
				//$lan_id = Patt_Custom_Func::lan_id_check( $a_lan_id, $request_id );
				//$lan_json = Patt_Custom_Func::lan_id_to_json( $lan_id );
				
				// Fake for D E B U G 
				$lan_id = $a_lan_id;
				$lan_json = '{"name":"Andrew Yuen","email":"Yuen.Andrew@epa.gov","phone":"202-510-6390","org":"HGA00000","lan_id":"' . $lan_id . '"}';
				//$lan_json = 'Error';
				
				// Error Checking
				if( $lan_id == 'LAN ID cannot be assigned' || $lan_id == null || $lan_id == '' ) {
					$lan_id_validation = false;
					$err_message_1 = 'The lan_id used in the "EPA Contact" column caused an error. This may be due to it being an invalid lan_id or due to the validating server.';
					
				}
				
				// Error Checking
				if( $lan_json == 'Error' || $lan_json == null || $lan_json == '' ) {
					$lan_id_validation = false;
					$err_message_1 = 'The lan_id used in the "EPA Contact" column caused an error for lan_json. This may be due to it being an invalid lan_id or due to the validating server.';
				}
				
				// If Valid, update DB
				if( $lan_id_validation ) {
					
					// Update each new box that was added
					foreach( $lan_id_box_array as $boxid ) {
					
						$inserted[] = $wpdb->update( $table, array( 'lan_id_details' => $lan_json ), array( 'lan_id' => $lan_id, 'box_id' => $boxid ) );
						
					}
					
				}
				
			}
*/
			
			
			
			

			
			
			
			// D E B U G - START
/*
			ob_start();
			?>
			<div class="col-sm-12 ticket-error-msg">
				<?php esc_html_e( 'Post Ingestion D E B U G.', 'pattracking' ); ?>
				<br><br>
				<?php 
			echo '<br><br>';
			echo '------------------------D-E-B-U-G------------------------' . '<br>';
			echo 'ticket_id: ' . $ticket_id . '<br>';
			echo 'lan_id: ' . $lan_id . '<br>';
			echo 'lan_json: ' . $lan_json . '<br><br>';
			
			echo 'lan_id_name_array: <br>'; 
			echo '<pre>';
			print_r( $lan_id_name_array );
			echo '</pre>';
			
			echo 'inserted: <br>'; 
			echo '<pre>';
			print_r( $inserted );
			echo '</pre>';
			
			echo 'inserted: <br>'; 
			echo '<pre>';
			print_r( $inserted );
			echo '</pre>';
			
			echo 'lan_id_obj_array: <br>'; 
			echo '<pre>';
			print_r( $lan_id_obj_array );
			echo '</pre>';
			
			//echo '<pre>';
			//print_r( $boxarray );
			//echo '</pre>';
			
			$ticket_error_message = ob_get_clean();

			$response = array(
				'redirct_url'    => '',
				'thank_you_page' => $ticket_error_message,
			);

			echo json_encode( $response );
			die();
*/
			// D E B U G - END
			




			//
			// if validation failed, delete ticket, associated data, and display error message.
			//
			
/*
			if( !$lan_id_validation ) {
				
				// delete the ticket.
				$delete_ticket2 = apply_filters( 'request_ticket_delete', $ticket_id );

				ob_start();
				?>
				<div class="col-sm-12 ticket-error-msg">
					<h3>EPA Contact (lan_id) Validation Failed. Ticket not generated.</h3>
					<?php 
						echo $err_message_1 . '<br><br>';	
									
						echo 'Ticket ID: ' . $ticket_id . ' not created. <br>';
						echo 'delete_ticket: ' . $delete_ticket2 . ' <br>';
						
						
						echo 'delete_ticket: <br>'; 
						echo '<pre>';
						print_r( $delete_ticket2 );
						echo '</pre>';

						
						echo 'lan_id_name_array: <br>'; 
						echo '<pre>';
						print_r( $lan_id_name_array );
						echo '</pre>';
												
						echo 'lan_id_box_array: <br>'; 
						echo '<pre>';
						print_r( $lan_id_box_array );
						echo '</pre>';

						echo '<br>';
					?>
				</div>
				<?php
				$ticket_error_message = ob_get_clean();

				$response = array(
					'redirct_url'    => '',
					'thank_you_page' => $ticket_error_message,
				);

				echo json_encode( $response );
				die();
			}
*/
			

			do_action( 'wppatt_eidw_instant', $ticket_id );
			
          /*
          Move to after Initial Review Complete in the auto assignment script.
			if( $superfund ) {				
				do_action( 'wppatt_sems_instant', $ticket_id );
			} 
		 */
			
			// Confirm site name and site id are valid (from api) 
			if( $superfund ) {
				
				// Kick off SEMS site id validation AJAX here:
				
				
				
				

				
				$site_name_id_valid = Patt_Custom_Func::sems_site_id_validation( $sems_check_site_name, $sems_check_site_id, $region_id );
				
				// D E V SITE - TESTING
				//$site_name_id_valid = 'Success';
				

				if( $site_name_id_valid != 'Success') {
					
					$delete_ticket = apply_filters( 'request_ticket_delete', $ticket_id );
					
					ob_start();
						?>
						<div class="col-sm-12 ticket-error-msg">
							<?php esc_html_e( 'Error entering box information. Ticket not generated.', 'pattracking' ); ?>
							<?php esc_html_e( 'If this error persists, please copy this error message and the details below, and send them to the development team.', 'pattracking' ); ?>
							<br><br>
							<?php 
								echo 'Site ID Validation failed. <br>';
								echo 'Site ID Validation response: ' . $site_name_id_valid . '<br>';
							?>
							<br>							
						</div>
						<?php
						$ticket_error_message = ob_get_clean();

						$response = array(
							'redirct_url'    => '',
							'thank_you_page' => $ticket_error_message,
						);

						echo json_encode( $response );
						die();
					
				}

			} 
			
			
			
			
		}

		// OLD Not used. 4/16/2020
		/**
		 * Change upload path
		 *
		 * @param  Array $dir Upload directory information as array.
		 */
/*
		public static function change_boxinfo_doc_file_upload_dir( $dir ) {

			$dir['path']   = $dir['basedir'] . '/mdocs';
			$dir['url']    = $dir['baseurl'] . '/mdocs';
			$dir['subdir'] = '/mdocs';

			return $dir;
		}
*/


		/**
		 * Get storage location row id
		 */
		public function get_new_storage_location_row_id() {
			global $wpdb;
			
			$dc_no_assigned_tag = get_term_by('slug', 'not-assigned-digi-center', 'wpsc_categories');
      $dc_no_assigned_term = $dc_no_assigned_tag->term_id;

			
			$table = $wpdb->prefix . 'wpsc_epa_storage_location';
			$data = array(
// 				'digitization_center' => 666,
        'digitization_center' => $dc_no_assigned_term,
				'aisle' => 0,
				'bay' => 0,
				'shelf' => 0,
				'position' => 0,
			);
			$format = array( '%s', '%d', '%d', '%d', '%d' );
			$wpdb->insert( $table, $data, $format );
			return $wpdb->insert_id;
		}

		/**
		 * Get storage location row id
		 *
		 * @param String $record_schedule_number Record schedule number as string. // Now called Dispositioned schedule
		 */
		public function get_record_schedule_id( $record_schedule_number ) {

			global $wpdb;
// 			$programe_office_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}epa_record_schedule WHERE Record_Schedule_Number = %s ", $record_schedule_number ) );
	$programe_office_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}epa_record_schedule WHERE Schedule_Item_Number = %s ", $record_schedule_number ) );

			return $programe_office_id;
		}

		/**
		 * Get program office id
		 *
		 * @param String $office_id_key office id key as string.
		 */
		public function get_programe_office_id( $office_id_key ) {

			global $wpdb;
			$programe_office_id = $wpdb->get_var( $wpdb->prepare( "SELECT office_code FROM {$wpdb->prefix}wpsc_epa_program_office WHERE office_acronym = %s ", $office_id_key ) );

			return $programe_office_id;
		}

		/**
		 * Adds ticketmeta for BoxInfo
		 *
		 * @param Integer $boxinfo_id box info id as Integer.
		 * @param String  $meta_key meta key as string.
		 * @param String  $meta_value meta value as string.
		 */
		public function add_boxinfo_meta( $boxinfo_id, $meta_key, $meta_value ) {
			global $wpdb;
			$wpdb->insert(
				$wpdb->prefix . 'wpsc_epa_boxmeta',
				array(
					'box_id' => $boxinfo_id,
					'meta_key' => $meta_key,
					'meta_value' => $meta_value,
				)
			);
		}

		/**
		 * Create a folderdocinfo record
		 *
		 * @param Array $folderdocarray folder doc array as array.
		 */
		public function create_new_folderdocinfo( $folderdocarray ) {
			global $wpdb;
			$wpdb->insert( $wpdb->prefix . 'wpsc_epa_folderdocinfo', $folderdocarray );
			$folderdocinfo_id = $wpdb->insert_id;
			return $folderdocinfo_id;
		}

		/**
		 * Create a boxinfo record
		 *
		 * @param Array $boxarray box info as array.
		 */
		public function create_new_boxinfo( $boxarray ) {
			global $wpdb;
			$wpdb->insert( $wpdb->prefix . 'wpsc_epa_boxinfo', $boxarray );
			$boxinfo_id = $wpdb->insert_id;
			return $boxinfo_id;
		}
		
		/**
		 * Update a boxinfo record
		 *
		 * @param Array $boxarray box info as array.
		 */
		public function update_boxinfo( $boxarray, $where ) {
			global $wpdb;
			$num_rows = $wpdb->update( $wpdb->prefix . 'wpsc_epa_boxinfo', $boxarray, $where );
			return $num_rows;
		}
		
		/**
		 * Update a FDIF record
		 *
		 * @param Array $fdif_array box info as array.
		 */
		public function update_fdif( $fdif_array, $where ) {
			global $wpdb;
			$num_rows = $wpdb->update( $wpdb->prefix . 'wpsc_epa_folderdocinfo_files', $fdif_array, $where );
			return $num_rows;
		}
		
		/**
		 * Creates a Restore Array containing all the data needed to restore previous box list data on php error.
		 *
		 * @param $ticket_id is the id for the SupportCandy ticket
		 */
		public function create_restore_array( $ticket_id ) {
			global $wpdb;
			
			$box_n_folder_restore_arr = [];
			
			$sql = "SELECT * FROM " . $wpdb->prefix . "wpsc_epa_boxinfo WHERE ticket_id = " . $ticket_id;
			$boxinfo_restore_arr = $wpdb->get_results( $sql );
			
			$box_n_folder_restore_arr[ 'boxes' ] = $boxinfo_restore_arr;
			
			foreach( $boxinfo_restore_arr as $key => $box ) {
				
				$sql = "SELECT * FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files WHERE box_id = " . $box->id;
				$folderfile_restore_arr = $wpdb->get_results( $sql );
				
				$box_n_folder_restore_arr[ 'boxes' ][ $key ]->folderfile_arr = $folderfile_restore_arr;
					
			}
			
			return $box_n_folder_restore_arr;
		}

		/**
		 * Restore the previous box list
		 *
		 * @param ticket_id, restore_arr is the returned array from function create_restore_array( $ticket_id )
		 */
		public function restore_previous_boxlist( $ticket_id, $restore_arr ) {
			global $wpdb;
			
			$box_table = $wpdb->prefix . 'wpsc_epa_boxinfo';
      $fdif_table = $wpdb->prefix . 'wpsc_epa_folderdocinfo_files';
      $error_arr = [];
      
      // Update the box table.
      foreach( $restore_arr["boxes"] as $box_key => $the_box ) {
        
        //$pallet = $the_box->pallet_id == '' ? 'x' : $the_box->pallet_id;
        $pallet = $the_box->pallet_id;
                        
        $box_update_arr = [
          'box_id' => $the_box->box_id,
          'box_status' => $the_box->box_status,
          'ticket_id' => $the_box->ticket_id,
          'storage_location_id' => $the_box->storage_location_id,
          'location_status_id' => $the_box->location_status_id,
          'program_office_id' => $the_box->program_office_id,
          'record_schedule_id' => $the_box->record_schedule_id,
          'box_destroyed' => $the_box->box_destroyed,
          'pallet_id' => $pallet,
//                   'pallet_id' => 'x',
          'scan_list_id' => $the_box->scan_list_id,
          'date_created' => $the_box->date_created,
          'date_updated' => $the_box->date_updated
        ];
        
        $box_where = [
          'ID' => $the_box->id
        ];
        
        
        $error_check = $wpdb->update( $box_table, $box_update_arr, $box_where );
        
        // D E B U G
        $error_check_text = $error_check === false ? 'false' : 'true';
        
        if( !$error_check ) {
          $error_arr['box'][ $the_box->id ] = $the_box->id;
          //$error_arr[''][] = $the_box->pallet_id
        }
                        
        //$error_arr[] = $the_box->id;
        //$error_arr[] = $box_update_arr;
        
        foreach( $the_box->folderfile_arr as $the_folder ) {
          
          
          
          $fdif_update_arr = [
						//'parent_id' => $parent_id,
						//'box_id' => $boxinfo_id,
						//'folderdocinfofile_id'   => $folder_file_sub_id,
						'doc_regid' => $region_id,
						//'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
						'file_name'  => $file_name,
						'source_file_location' => $boxinfo['Folder/Filename'],
						'title'  => $boxinfo['Title'],
						'date' => $newdatetimeformat,
						'tags' => $boxinfo['Tags'],
						'source_format' => $source_format,
						'index_level' => $index_level,
						'description' => $boxinfo['Description of Record'],
						'access_restriction' => $boxinfo['Access Restrictions'],
						'specific_access_restriction' => $SAR,
						'use_restriction' => $boxinfo['Use Restrictions'],
						'specific_use_restriction' => $SUR,
						'rights_holder' => $RH,
						'source_dimensions' => $boxinfo['Source Dimensions'],
						'relation_part' => $x_of,
						'relation_part_of' => $of_y,
						'record_type' => $boxinfo['Record Type'],
						'author' => "{$boxinfo['Creator']}",
						'addressee' => "{$boxinfo['Addressee']}",
						'site_name' => "{$boxinfo['Site Name']}",
						'siteid' => "{$boxinfo['Site ID # / OU']}",
						'close_date' => $new_date,
						'folder_identifier' => "{$boxinfo['Folder Identifier']}",	
						'essential_record' => "{$essential_record}",
						'program_area' => $boxinfo['Program Area'],
						'lan_id' => $lan_id,
						'lan_id_details' => $lan_json,
						//'date_created' => gmdate( 'Y-m-d H:i:s' ),
						'date_updated' => gmdate( 'Y-m-d H:i:s' ),
					];
          
          $fdif_where = [
            'ID' => $the_folder->id
          ];
          
          $error_check_fdif = $wpdb->update( $fdif_table, $fdif_update_arr, $fdif_where );
        
          // D E B U G
          $error_check_text_fdif = $error_check_fdif === false ? 'false' : 'true';
          
          if( !$error_check_fdif ) {
            $error_arr['fdif'][ $the_folder->id ] = $the_folder->id;
          }

          
          
        }
        
      }
      
      // CHANGE THIS
      // Update the FDIF table.
/*
      foreach( $restore_arr["boxes"] as $box_key => $the_box ) {
        
        //$pallet = $the_box->pallet_id == '' ? 'x' : $the_box->pallet_id;
        $pallet = $the_box->pallet_id;
                        
        $box_update_arr = [
          'box_id' => $the_box->box_id,
          'box_status' => $the_box->box_status,
          'ticket_id' => $the_box->ticket_id,
          'storage_location_id' => $the_box->storage_location_id,
          'location_status_id' => $the_box->location_status_id,
          'program_office_id' => $the_box->program_office_id,
          'record_schedule_id' => $the_box->record_schedule_id,
          'box_destroyed' => $the_box->box_destroyed,
          'pallet_id' => $pallet,
//                   'pallet_id' => 'x',
          'scan_list_id' => $the_box->scan_list_id,
          'date_created' => $the_box->date_created,
          'date_updated' => $the_box->date_updated
        ];
        
        $box_where = [
          'ID' => $the_box->id
        ];
        
        
        $error_check = $wpdb->update( $box_table, $box_update_arr, $box_where );
        
        // D E B U G
        $error_check_text = $error_check === false ? 'false' : 'true';
        
        if( !$error_check ) {
          $error_arr['box'][ $the_box->id ] = $the_box->id;
          //$error_arr[''][] = $the_box->pallet_id
        }
                        
        //$error_arr[] = $the_box->id;
        //$error_arr[] = $box_update_arr;
        
      }
			
			return $error_arr;
*/
			
		}

		
		/**
		 * SEMS form html
		 *
		 * @param array $field field info as array.
		 */
		public function print_listing_form_block_SEMS( $field ) {
			
			$extra_info_text_color = get_option('wpsc_create_ticket');
			$extra_info_css = 'color:'.$extra_info_text_color['wpsc_extra_info_text_color'].' !important;';
			if ( 'ticket_category' == $field->name ) {
				?>
				<div class="row">
		          	<div  data-fieldtype="dropdown" data-visibility="<?php echo $this->visibility_conditions?>" class="<?php echo $this->col_class?> <?php echo $this->visibility? 'hidden':'visible'?> <?php echo $this->required? 'wpsc_required':''?> form-group wpsc_form_field <?php echo 'field_'.$field->term_id?> col-sm-4">
						<label class="wpsc_ct_field_label" for="super-fund">
						Are these records Superfund Records? <span style="color:red;">*</span>
						</label>
						<?php 
						if($this->extra_info['custom_fields_extra_info_'.$field->term_id]){?><p class="help-block" style="<?php echo $extra_info_css?>"><?php echo $this->extra_info['custom_fields_extra_info_'.$field->term_id];?></p><?php }?>
						<select id="super-fund" class="form-control wpsc_drop_down" name="super-fund" >
							<option value=""><?php esc_html_e( 'Please Select', 'supportcandy' ); ?></option>
							<option value="no" selected><?php esc_html_e( 'No', 'supportcandy' ); ?></option>
							<option value="yes"><?php esc_html_e( 'Yes', 'supportcandy' ); ?></option>
						</select>
		          	</div>
				</div>
                <div class="row">
		          	<div data-fieldtype="dropdown" data-visibility="<?php echo $this->visibility_conditions?>" class="<?php echo $this->col_class?> <?php echo $this->visibility? 'hidden':'visible'?> <?php echo $this->required? 'wpsc_required':''?> form-group wpsc_form_field <?php echo 'field_'.$field->term_id?> col-sm-4 ndc-assignment-container">
						<label class="wpsc_ct_field_label" for="ndc-assignment">
						We will automatically assign an NDC for you to send boxes to. 
                        Do you need to override manual assignment? <span style="color:red;">*</span>
						</label>
						<?php 
						if($this->extra_info['custom_fields_extra_info_'.$field->term_id]){?><p class="help-block" style="<?php echo $extra_info_css?>"><?php echo $this->extra_info['custom_fields_extra_info_'.$field->term_id];?></p><?php }?>
						<select id="ndc-assignment" class="form-control wpsc_drop_down" name="ndc-assignment" >
							<option value=""><?php esc_html_e( 'Please Select', 'supportcandy' ); ?></option>
							<option value="no" selected><?php esc_html_e( 'No', 'supportcandy' ); ?></option>
							<option value="yes"><?php esc_html_e( 'Yes', 'supportcandy' ); ?></option>
						</select>
		          	</div>
				</div>
                <div class="row">
		          	<div id="ndc-selector-container" data-fieldtype="dropdown" data-visibility="<?php echo $this->visibility_conditions?>" class="<?php echo $this->col_class?> <?php echo $this->visibility? 'hidden':'visible'?> <?php echo $this->required? 'wpsc_required':''?> form-group wpsc_form_field <?php echo 'field_'.$field->term_id?> col-sm-4" style="display: none;">
						<label class="wpsc_ct_field_label" for="ndc-selector">
						    Which NDC do you need to send your boxes to? <span style="color:red;">*</span>
						</label>
						<?php 
						if($this->extra_info['custom_fields_extra_info_'.$field->term_id]){?><p class="help-block" style="<?php echo $extra_info_css?>"><?php echo $this->extra_info['custom_fields_extra_info_'.$field->term_id];?></p><?php }?>
						<select id="ndc-selector" class="form-control wpsc_drop_down" name="ndc-selector" >
							<option value=""><?php esc_html_e( 'Please Select', 'supportcandy' ); ?></option>
							<option value="east" selected><?php esc_html_e( 'NDC East', 'supportcandy' ); ?></option>
							<option value="west"><?php esc_html_e( 'NDC West', 'supportcandy' ); ?></option>
						</select>
		          	</div>
				</div>
				<?php
			}
		}
		
		
		/**
		 * Box list form html
		 *
		 * @param array $field field info as array.
		 */
		public function print_listing_form_block( $field ) {

			if ( 'ticket_category' == $field->name ) {
				?>
				<!-- Beginning of new datatable -->
				<div class="box-body table-responsive" id="boxdisplaydiv"
					style="width:100%;padding-bottom: 20px;padding-right:20px;padding-left:15px;margin: 0 auto;">
					<label class="wpsc_ct_field_label">Box List <span style="color:red;">*</span></label>

					<!-- DropZone File Grag Drop Uploader -->
					<div id="dzBoxUpload" class="dropzone" tabindex="0">
						<div class="fallback">
							<input name="file" type="file" />
						</div>
						<div class="dz-default dz-message">
							<button class="dz-button" type="button" tabindex="-1">Drop your file here to upload (only .xlsm files allowed)</button>
						</div>
					</div>
					
					<div style="margin: 10px 0 10px;" id="attach_16" class="row spreadsheet_container"></div>
					
					<div style="margin: 10px 0 10px;"  class="row">
						<div class="col-sm-4"></div>
						<div id="processing_notification_div" class="col-sm-4" >
							<span id="processing_notification" ></span>
							<br>
							<span id="processing_notification_persistent" >Do Not Navigate Away from this page. Processing will Halt.</span>
						</div>
						<div class="col-sm-4"></div>
					</div>
					
					
					<div id="big_wrapper">
						<table style="display:none;margin-bottom:0;" id="boxinfodatatable" class="table table-striped table-bordered nowrap">
							<thead style="margin: 0 auto !important;">
								<tr>
									<th scope="col" >Box</th>
									<th scope="col" >Folder Identifier</th>
									<th scope="col" >Title</th>
									<th scope="col" >Description of Record</th>
									<th scope="col" >Parent/Child</th>
									<th scope="col" >Creation Date</th>
									<th scope="col" >Creator</th>
									<th scope="col" >Addressee</th>
									<th scope="col" >Record Type</th>
	<!-- 								<th scope="col" >Record Schedule & Item Number</th> -->
									<th scope="col" >Disposition Schedule & Item Number</th>
									<th scope="col" >Site Name</th>
									<th scope="col" >Site ID # / OU</th>
									<th scope="col" >Close Date</th>
									<th scope="col" >EPA Contact</th>								
									<th scope="col" >Access Restrictions</th>
									<th scope="col" >Specific Access Restrictions</th>
									<th scope="col" >Use Restrictions</th>
									<th scope="col" >Specific Use Restrictions</th>
									<th scope="col" >Rights Holder</th>
									<th scope="col" >Source Type</th>
									<th scope="col" >Source Dimensions</th>
									<th scope="col" >Program Office</th>
									<th scope="col" >Program Area</th>
									<th scope="col" >Index Level</th>
									<th scope="col" >Essential Record</th>
									<th scope="col" >Folder/Filename</th>
									<th scope="col" >Tags</th>
								</tr>
							</thead>
						</table>
					</div>

					<!-- O L D  F I L E  U P L O A D E R         
					<div class="row attachment_link">
						<span onclick="wpsc_spreadsheet_upload('attach_16','spreadsheet_attachment');">Attach spreadsheet</span>
					</div>
					-->
					<!-- File Upload Validation -->
					<input type="hidden" id="file_upload_cr" name="file_upload_cr" value="0" />
					
					
				</div>
				
				
				<!-- Beginning of SEMS datatable -->
				<div class="box-body table-responsive" id="boxdisplaydivSEMS"                                                                               						style="width:100%;padding-bottom: 20px;padding-right:20px;padding-left:15px;margin: 0 auto;">
<!-- 				<div class="box-body table-responsive" id="boxdisplaydiv"                                                                               						style="width:100%;padding-bottom: 20px;padding-right:20px;padding-left:20px;margin: 0 auto;"> -->
					<label class="wpsc_ct_field_label">SEMS List <span style="color:red;">*</span></label>

					<!-- DropZone File Grag Drop Uploader -->
					<div id="dzBoxUploadSEMS" class="dropzone">
<!-- 					<div id="dzBoxUpload" class="dropzone"> -->
						<div class="fallback">
							<input name="file" type="file" />
						</div>
						<div class="dz-default dz-message">
							<button class="dz-button" type="button">Drop your SEMS file here to upload (only .xlsm files allowed)</button>
						</div>
					</div>
					<div style="margin: 10px 0 10px;" id="attach_SEMS" class="row spreadsheet_container"></div>

					<table style="display:none;margin-bottom:0;" id="boxinfodatatableSEMS" class="table table-striped table-bordered nowrap">
						<thead style="margin: 0 auto !important;">
							<tr>
								<th scope="col" >DOC_REGID</th>
								<th scope="col" >DOC_ID</th>
								<th scope="col" >TITLE</th>
								<th scope="col" >DATE</th>
								<th scope="col" >DOC_CREATOR/EDITOR</th>
								<th scope="col" >PATT_BOX</th>
<!-- 								<th scope="col" >RECORD_SCHEDULE</th> -->
<!-- 								<th scope="col" >INDEX_LEVEL</th> -->
								<th scope="col" >LOCATION</th>
								<th scope="col" >EPA_ID</th>
							</tr>
						</thead>
					</table>
																	
					<!-- File Upload Validation -->
					<input type="hidden" id="file_upload_cr_SEMS" name="file_upload_cr_SEMS" value="0" />
				</div>
				
				<style>
					#processing_notification_div {
						display: none;
						text-align: center;
						width: 20em;
						padding: 15px 0px;
						border-radius: 4px;
						
					}
					
					#processing_notification {
						color: white;
						font-size: 1.5em;
					}
					
					#processing_notification_persistent {
						color: white;
						font-size: 1.1em;
						font-weight: 700;
					}
					
					.yellow_update {
/* 						background-image: linear-gradient(to bottom, #eaec50 0%, #b1b315 100%); */
            background-color: #17505e;
					}
					
					.green_update {
/* 						background-image: linear-gradient(to bottom, #5cb85c 0%, #449d44 100%); */
            background-color: #2f631d;
					}
					
					.progress-bar {
/*   					background-color: #17505e !important; */
  					background-image: linear-gradient(to bottom, #17505e 0%, #17505e 100%) !important;
					}
					
					.progress-bar-success {
/*   					background-color: #2f631d !important; */
  					background-image: linear-gradient(to bottom, #2f631d 0%, #2f631d 100%) !important;
					}
					
/* 					class="progress-bar progress-bar-success" */
					
					.dropzone .dz-preview .dz-progress {
						top: 70%;
						display: none;
					}
					
					.sorting_asc {
  					background-image: none !important;
					}
					
					#dzBoxUpload:focus {
  					outline: 5px auto -webkit-focus-ring-color;
					}
					
					
					
				</style>
				
				<script>
					jQuery('#boxdisplaydivSEMS').hide();
					
					// Fixes issue where superfund is redeclared on clicking 'Reset Form'
					if (typeof superfund !== 'undefined') {
						let superfund = jQuery('#super-fund').val();
					}

					// ALERT: doesn't allow a user to change SEMS/ECMS after uploading file due to variations in how validation 
					// is done for either file type.
					jQuery('#super-fund').change( function () {
						let file_upload_cr = jQuery('#file_upload_cr').val();
						console.log({file_upload_cr:file_upload_cr});

						if( file_upload_cr == '1' ) {
							alert( 'You are not allowed to change the value of "Are these documents part of SEMS?" after a file is uploaded. This form will automatically "Reset" after closing this alert. \n\n Please select the correct value for "Are these documents part of SEMS?", then upload the file. ' );
							
							wpsc_get_create_ticket();
						}
					});
					
					// Remove file, click is not removing the flag #file_upload_cr, which would make the ingestion page more robust. 
					// probably a scope issue as the class is being provided by another file. 
/*
					jQuery(document).on('click', '.dz-remove', function() {
					    alert('hello');
					});
*/
					
/*
					jQuery('.dz-remove').click( function () {
						console.log('file removed');
						jQuery('#file_upload_cr').val('0');
					});
*/
					
// 					let superfund_2 = jQuery('#super-fund').val();
					jQuery('#super-fund').change( function () {
/*
						superfund = jQuery('#super-fund').val();
						if( superfund == 'yes' ) {							
							jQuery('#boxdisplaydivSEMS').show();
							jQuery('#boxdisplaydiv').hide();
						} else if( superfund == 'no' ) {
							jQuery('#boxdisplaydivSEMS').hide();
							jQuery('#boxdisplaydiv').show();							
						}
*/
					});
				</script>
				<!-- End of new datatable -->
				<?php
			}
		}
	}

	new Patt_HooksFilters();
}