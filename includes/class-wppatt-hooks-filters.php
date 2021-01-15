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
			return $param;
		}

		/**
		 * After file upload, move it from the temp to custom directory
		 */
		public function move_excel_file() {

			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
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

			add_filter( 'upload_dir', array( $this, 'wpai_set_custom_upload_folder' ) );
			// $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

			// Add file to wordpress media
			$attachment_id = media_handle_upload( 'file', 0 );
			if ( ! is_wp_error( $attachment_id ) ) {
				update_post_meta( $attachment_id, 'folder', 'box-list' );
				array_push( $attach_ids, $attachment_id );

				$request_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
				echo 'File Upload Successfully -> ' . esc_attr( $request_page );
			} else {
				echo esc_attr( $movefile['error'] );
			}

			remove_filter( 'upload_dir', array( $this, 'wpai_set_custom_upload_folder' ) );

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
            
			// Update request id
			$wpdb->update( $wpdb->prefix . 'wpsc_ticket', array( 'request_id' => $request_id ), array( 'id' => $ticket_id ) );
			
			// Add Super Fund flag to ticket_meta
			$wpscfunction->add_ticket_meta( $ticket_id, 'super_fund', $data['super_fund'] );
			
			
			$test = ''; // DEBUG
			
			if( !$superfund ) {
				// New BoxInfo Code.
				$boxinfodata = stripslashes( $data['box_info'] );
				$boxinfo_array = json_decode( $boxinfodata, true );
				$test = 'Box'; // DEBUG
			} else {
				// New SEMS BoxInfo
				$boxinfodata = stripslashes( $data['superfund_data'] );
				$boxinfo_array = json_decode( $boxinfodata, true );
				$test = 'SEMS'; // DEBUG
			}
			
			// Pre-loop Defaults
			$box = '';
			$row_counter = 1;
			$folder_file_counter = 0;
			$folder_file_sub_counter = 1;
			$box_id_legacy = $request_id . '-1'; 
			$epa_contact_legacy = '';
			
			
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
					echo 'test: ' . $test . '<br>';
					echo 'box_id_legacy : ' . $box_id_legacy . '<br>'; 
					echo ': '   . '<br>';
					//echo 'boxinfo_array: <pre>' . $boxinfo_array . '</pre><br>';
					echo '<pre>';
					print_r( $boxinfo_array );
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
			
			

			// Loop through box data
			foreach ( $boxinfo_array as $boxinfo ) {
				
				// Shift Superfund to associative array
				if( $superfund ) {
					$new_boxinfo['DOC_REGID'] = $boxinfo[0];
					$new_boxinfo['DOC_ID'] = $boxinfo[1];
					$new_boxinfo['TITLE'] = $boxinfo[2];
					$new_boxinfo['DATE'] = $boxinfo[3];
					$new_boxinfo['DOC_CREATOR/EDITOR'] = $boxinfo[4];
					$new_boxinfo['PATT_BOX'] = $boxinfo[5];
					//$new_boxinfo['RECORD_SCHEDULE'] = $boxinfo[6];
					//$new_boxinfo['INDEX_LEVEL'] = $boxinfo[7];
					$new_boxinfo['LOCATION'] = $boxinfo[6];
					$new_boxinfo['EPA_ID'] = $boxinfo[7];
					
					// Defaults
					$new_boxinfo['RECORD_SCHEDULE'] = '108 1036a';
					$new_boxinfo['INDEX_LEVEL'] = 'File';
					
					$boxinfo = $new_boxinfo;
				}
				
				
				// Set Box ID and box number
				if( !$superfund ) {
					$box_id = $request_id . '-' . $boxinfo['Box'];
					$box_num = $boxinfo['Box'];
					
					if( $boxinfo['Parent/Child'] == 'P' ) {
						$parent_child_single = 'parent';
					} elseif( $boxinfo['Parent/Child'] == 'C' ) {
						$parent_child_single = 'child';
					} elseif( $boxinfo['Parent/Child'] == 'S' ) {
						$parent_child_single = 'single';
					} else {
						$parent_child_single = 'single';
					}
					
				} else {
					$box_id = $request_id . '-' . $boxinfo['PATT_BOX'];
					$box_num = $boxinfo['PATT_BOX'];
					$parent_child_single = 'single';
				}
				
				
				// Reset row_counter for proper box_id when new Box is detected. 
				if( $box_id != $box_id_legacy ) {
					$row_counter = 1;
					$box_id_legacy = $box_id;
					$folder_file_counter = 0;
				}
				
				//
				// If new Box in Request
				//
				
// 				if ( $box !== $boxinfo['Box'] ) {
				if ( $box !== $box_num ) {
					
					// Record Schedule Number
					if( !$superfund ) {
						$record_schedule_number_break = explode( ':', $boxinfo['Record Schedule & Item Number'] );
						$record_schedule_number = trim( str_replace( array( '[', ']' ), '', $record_schedule_number_break[0] ) );
					} else {
						//$record_schedule_number = 1036;
						$record_schedule_number = $boxinfo['RECORD_SCHEDULE'];
					}
					
					// Program Office
					if( !$superfund ) {
						$program_office_break = explode( ':', $boxinfo['Program Office'] );
						$program_office_id = trim( $program_office_break[0] );
					} else {
						// SEMS Default
						$program_office_id = 'OLEM-OSRTI';
					}
					
					// EPA Contact for Lan ID and Lan ID Details
					if( !$superfund ) {
						// $epa_contact = $boxinfo['EPA Contact'];
						$epa_contact = trim( $boxinfo['EPA Contact'] );
					} else {
						$epa_contact = trim( $boxinfo['DOC_CREATOR/EDITOR'] );
						$epa_contact_legacy = $epa_contact;
					}
					
					
					// Fetch lan id and json
					// Commentted out while on Dev Server that has no access to EPA Network
					
					//$lan_id = Patt_Custom_Func::lan_id_check( $epa_contact, $request_id );
					//$lan_json = Patt_Custom_Func::lan_id_to_json( $lan_id );
					
					// Box information for insertion
					$boxarray = array(
						'box_id' => $box_id,
						'ticket_id' => $ticket_id,
						// 'location' => $boxinfo["Location"],
						// 'bay' => '1',
						'storage_location_id' => $this->get_new_storage_location_row_id(),
						'location_status_id' => 1,
						'lan_id' => $epa_contact,
						'lan_id_details' => '',
						'program_office_id' => $this->get_programe_office_id( $program_office_id ),
						'record_schedule_id' => $this->get_record_schedule_id( $record_schedule_number ),
						'date_created' => gmdate( 'Y-m-d H:i:s' ),
						'date_updated' => gmdate( 'Y-m-d H:i:s' ),
					);

					//Create boxinfo record
					$boxinfo_id = $this->create_new_boxinfo( $boxarray );

					$this->add_boxinfo_meta( $boxinfo_id, 'assigned_agent', '0' );
					$this->add_boxinfo_meta( $boxinfo_id, 'prev_assigned_agent', '0' );
					
					
					// $box = $boxinfo['Box'];
					$box = $box_num;
					
					
					// DEBUG - START
/*
					ob_start();
					?>
					<div class="col-sm-12 ticket-error-msg">
						<?php esc_html_e( 'Angry Test.', 'pattracking' ); ?>
						<br><br>
						<?php 
							echo 'superfund: ' . $superfund . '<br>';
							echo 'superfund bool: ' . is_bool($superfund) . '<br>'; 
							echo 'test: ' . $test . '<br>';
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
					// If Box not inserted properly, delete the ticket and abort ingestion
					//

					if ( 0 === $boxinfo_id ) {
						// if, Box not inserted, delete the ticket.
						$delete_ticket = apply_filters( 'request_ticket_delete', $ticket_id );
						
						// Delete ticket meta

						ob_start();
						?>
						<div class="col-sm-12 ticket-error-msg">
							<?php esc_html_e( 'Error entering box information. Ticket not generated.', 'pattracking' ); ?>
							<br><br>
							<?php 
								echo 'superfund: ' . $superfund . '<br>';
								echo 'superfund bool: ' . is_bool($superfund) . '<br>'; 
								echo 'test: ' . $test . '<br>';
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
					} else {
					//
					// DEBUG
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
								echo 'test: ' . $test . '<br>';
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
					
				}
				
				
				// EPA Contact for Lan ID and Lan ID Details
				if( !$superfund ) {
// 					$index_level = strtolower( $boxinfo['INDEX_LEVEL'] ) == 'file' ? 2 : 1;
					$index_level = strtolower( $boxinfo['Index Level'] ) == 'file' ? 2 : 1;
					$essential_record = 'Yes' == $boxinfo['Essential Record'] ? '00' : '01';
					$docinfo_id = $request_id . '-' . $boxinfo['Box'] . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $row_counter;
				} else {
					// Superfund defaults
					$index_level = strtolower( $boxinfo['INDEX_LEVEL'] ) == 'file' ? 2 : 1;
// 					$index_level = strtolower( $boxinfo['Index Level'] ) == 'file' ? 2 : 1;
					$essential_record = '01';
					$docinfo_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $row_counter;
				}
				
				//
				// Insert folder doc info 
				//
				
				// if Folder/Filename exists, do not add it to the folderdocinfo
				// if ( '' === $boxinfo['Folder/Filename'] || null === $boxinfo['Folder/Filename'] ) {  // electronic files allowed as parents
				if( $parent_child_single != 'child' ) {
				// Now: every entry sans child
					
					if( !$superfund ) {
						
						$datetime = new DateTime();
						//$newDate = $datetime->createFromFormat('d/m/Y', '23/05/2013');
						$new_date = $datetime->createFromFormat( 'm/d/Y H:i:s', $boxinfo['Close Date']);
						
						
						$folderdocarray = array(
							'folderdocinfo_id' => $docinfo_id,
							//'title' => $boxinfo['Title'],
							//'date' => gmdate( 'Y-m-d H:i:s' ),
							//'date' => $boxinfo['Date'],
							'author' => "{$boxinfo['Author']}",
							'addressee' => "{$boxinfo['Addressee']}",
							'record_type' => "{$boxinfo['Record Type']}",
							'site_name' => "{$boxinfo['Site Name']}",
							//'site_id' => "{$boxinfo['Site ID #']}",
							'siteid' => "{$boxinfo['Site ID #']}",
							//'close_date' => "{$boxinfo['Close Date']}",
							'close_date' => $new_date->format( 'Y-m-d H:i:s' ),
							'epa_contact_email' => '{}',
							'access_type' => "{$boxinfo['Access Type']}",
							//'source_format' => "{$boxinfo['Source Format']}",
							// 'rights' => "{$boxinfo['Rights']}",
							// 'contract_number' => "{$boxinfo['Contract #']}",
							// 'grant_number' => "{$boxinfo['Grant #']}",
							'folder_identifier' => "{$boxinfo['Folder Identifier']}",
							// 'file_name' => '',
							// 'file_location' => '',
							// 'freeze' => 1,
							//'index_level' => $index_level,
							'box_id' => $boxinfo_id,
							'essential_record' => "{$essential_record}",
							'date_created' => gmdate( 'Y-m-d H:i:s' ),
							'date_updated' => gmdate( 'Y-m-d H:i:s' ),
						);
					} else {
						
						// Superfund SEMS file information 
						$folderdocarray = array(
							'folderdocinfo_id' => $docinfo_id,
							//'title' => $boxinfo['TITLE'],
							//'date' => gmdate( 'Y-m-d H:i:s' ),
							//'date' => $boxinfo['DATE'],
							'author' => $boxinfo['DOC_CREATOR/EDITOR'],
// 							'author' => $boxinfo['EPA_ID'],
							'addressee' => "",
							//'record_type' => "CORR",
							'record_type' => "",
							'site_name' => $boxinfo['LOCATION'],
							'siteid' => $boxinfo['EPA_ID'],
							//'site_id' => $boxinfo['EPA_ID'],
							//'siteid_alt' => $boxinfo['EPA_ID'],
// 							'site_id' => $boxinfo['DOC_CREATOR/EDITOR'], 
							//'site_id' => "8899",							
							//'close_date' => gmdate( 'Y-m-d H:i:s' ),
							//'close_date' => gmdate( 'Y-m-d H:i:s' ),
							'epa_contact_email' => '{}',
							'access_type' => "", // or "shared"
							//'source_format' => "Paper",
							// 'rights' => "{$boxinfo['Rights']}",
							// 'contract_number' => "{$boxinfo['Contract #']}",
							// 'grant_number' => "{$boxinfo['Grant #']}",
							'folder_identifier' => "",
							// 'file_name' => '',
							// 'file_location' => '',
							// 'freeze' => 1,
							//'index_level' => $index_level,
							'box_id' => $boxinfo_id,
							'essential_record' => "0",
							'date_created' => gmdate( 'Y-m-d H:i:s' ),
							'date_updated' => gmdate( 'Y-m-d H:i:s' ),
							//'doc_id' => $boxinfo['DOC_ID'],
							//'doc_regid' => $boxinfo['DOC_REGID'],
						);
					}
					
					
/*
					// If Box is used for Litigation, Congressional, or FOIA 
					if ( 'files_uploaded' == $data['ticket_useage'] ) {
						$folderdocarray['freeze'] = 1;
					}
*/
	
					$folderdocinfo_id = $this->create_new_folderdocinfo( $folderdocarray );
	
					$row_counter++;
					

/*
					if( $superfund ) {
						$wpdb->update( $wpdb->prefix . 'wpsc_epa_folderdocinfo', array( 'site_id' => $boxinfo['EPA_ID'] ), array( 'id' => $folderdocinfo_id ) );
					}
*/
					
					//
					// DEBUG START
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
					//
					// DEBUG END
					//
					
				} // Remove when removing $boxinfo['Folder/Filename'] check. Allows parent to be electronic // ADDED new if !child
				
				
				//
				// Add data to folderdocinfo_files
				//
				
				// Upload file only if one exists. // Superfund SEMS not possible .:. will not have digital files
// 				if ( '' !== $boxinfo['Folder/Filename'] && null !== $boxinfo['Folder/Filename'] ) {
				if ( $boxinfo['Folder/Filename'] !== '' && $boxinfo['Folder/Filename'] !== null ) {
				
				// if electronic file
				
					// Change upload directory.
					add_filter( 'upload_dir', __CLASS__ . '::change_boxinfo_doc_file_upload_dir' );

					// Get filename with extension
					$file_name = explode( '\\', $boxinfo['Folder/Filename'] );
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

					$check_file_name = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM {$wpdb->prefix}wpsc_epa_folderdocinfo_files WHERE source_file_location = %s ', $boxinfo['Folder/Filename'] ), ARRAY_A );

					// Check if the same filename exists in table
					if ( is_array( $check_file_name ) && isset( $check_file_name['post_id'] ) && $check_file_name['file_object_id'] ) {
						$mdocs_post_id = $check_file_name['post_id'];
						$mdocs_attach_id = $check_file_name['file_object_id'];
					} else {
						// Save mdocs-post for the attachment aka The Parent
						$mdocs_post = array(
							'post_title' => $post_title,
							'post_status' => 'publish',
							'post_author' => $current_user->ID,
							'post_date' => gmdate( 'Y-m-d H:i:s' ),
							'post_date_gmt' => gmdate( 'Y-m-d H:i:s' ),
							'post_type' => 'mdocs-posts',
							'post_content' => '[mdocs_post_page new=true]'
						);
						$mdocs_post_id = wp_insert_post( $mdocs_post, true );

						$wp_filetype = wp_check_filetype( $file_path, null );
						// Save mdocs attachment aka The Child
						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title' => $post_title,
							'post_author' => $current_user->ID,
							'post_status' => 'inherit',
							'comment_status' => 'closed',
							'post_date' => gmdate( 'Y-m-d H:i:s' ),
							'post_date_gmt' => gmdate( 'Y-m-d H:i:s' ),
							'post_content' => '[mdocs_media_attachment]'
						);
						$mdocs_attach_id = wp_insert_attachment( $attachment, $file_path, $mdocs_post_id );
					}
					
					// Updates sub_id to be Attachement
					// Update when updating for Parent/Child
					// add this to every child
					// if child add -a, if not, don't. 
					
					
					
					if( $parent_child_single == 'parent' ) {
						$folder_file_counter++;
						$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						$folder_file_sub_counter = 1; //Reset for new file folder
						$folder_file_sub_id = $folder_file_id;
					} elseif( $parent_child_single == 'child') {
						$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						$folder_file_sub_id = $folder_file_id . '-a' . $folder_file_sub_counter;
						$folder_file_sub_counter++; //Increment it for next file.
					} elseif( $parent_child_single == 'single' ) {
						$folder_file_counter++;
						$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						$folder_file_sub_counter = 1; //Reset for new file folder
						$folder_file_sub_id = $folder_file_id;
					}
					
/*
					$folder_file_id = $request_id . '-' . $boxinfo['Box'] . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
					$folder_file_sub_id = $folder_file_id . '-a' . $folder_file_sub_counter;
					$folder_file_sub_counter++; //Increment it for next file.
*/
					
					
					// Convert Date
					$time = strtotime( $boxinfo['Date'] );
					$newdatetimeformat = date( 'Y-m-d H:i:s' ,$time );
					
					//
					// Save into folderdocinfo_files
					//
					
					if( !$superfund ) {
						$source_format = "{$boxinfo['Source Format']}";
					} else {
						$source_format = 'Paper';
					}
					
					
					$folderdocfiles_info = [
						'post_id' => $mdocs_post_id,
// 						'post_id' => $mdocs_attach_id,
						//'folderdocinfo_id'  => $folder_file_id, 
						'folderdocinfo_id'  => $folderdocinfo_id, 
						'folderdocinfofile_id'   => $folder_file_sub_id,
						'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
						'file_name'  => $file_name,
						//'object_location'   => '/uploads/mdocs/',
						'source_file_location' => $boxinfo['Folder/Filename'],
						'title'  => $boxinfo['Title'],
						//'date' => $boxinfo['Date'],
						'date' => $newdatetimeformat,
						//'description'   => 'x',
						//'tags' => $boxinfo['Tags'],
						'source_format' => $source_format,
						'index_level' => $index_level,
					];
					
					// If Box is used for Litigation, Congressional, or FOIA 
					if ( $data['ticket_useage'] == 'files_uploaded' ) {
						$folderdocfiles_info['freeze'] = 1;
					}
					
 					$table_name = $wpdb->prefix.'wpsc_epa_folderdocinfo_files';
					$diditwork = $wpdb->insert( $table_name, $folderdocfiles_info ); 
					$folderdocfiles_info_id = $wpdb->insert_id;
					
					// Create folder in wp_options name: 'mdocs-cats'
					
					// Get old values from wp_options
					// Get it in array format.
					// Check for current folder.
					// if not there, add it.
					// new function based on pieces from mdocs-categories --> mdocs_update_cats()
					
					// Check wp_options to determine if Folder already exists.
					$mdocs_cats = get_option( 'mdocs-cats' );
					ksort($mdocs_cats);
// 					$mdocs_cats = array_values($mdocs_cats);
					
					$folder_exists_key = Patt_Custom_Func::searchMultiArrayByFieldValue( $mdocs_cats, 'slug', $folder_file_id );
					
					// If folder doesn't exist, create it and add it.
					if( !$folder_exists_key ) {
						$new_folder = [
							'base_parent' => '',
				            'index' => '',
				            'parent_index' => 0,
				            'slug' => $folder_file_id,
				            'name' => $folder_file_id,
				            'parent' => '',
				            'children' => Array
				                (
				                ),
				            'depth' => 0
						];
						
						$mdocs_cats[] = $new_folder;
						$mdocs_cats = array_values($mdocs_cats);
						
						update_option('mdocs-cats',$mdocs_cats, '' , 'no');
					}
					
					// Save files in wp_options name: 'mdocs-list'
					$mdocs = mdocs_array_sort(); // gets values from wp_options
					array_push($mdocs, array(
						'id'=> $mdocs_attach_id,
						'parent'=> $mdocs_post_id,
						'filename'=> $file_name,
						'name'=> $boxinfo['Title'],
						'desc'=> '',
						'type'=> $wp_filetype['ext'], //'pdf'
						'cat'=> $folder_file_id,
						'owner'=> $current_user->display_name,
						'contributors'=> [],
						'author'=> $boxinfo['Author'],
						'size'=> 0, // or NULL
						'modified'=> time(),
						'version'=> '1.0',
						'show_social'=> 'on',
						'non_members'=> 'on',
						'file_status'=> 'public',
						'post_status'=> 'publish',
						'post_status_sys'=> 'publish',
						'doc_preview'=> '',
						'downloads'=> intval(0),
						'archived'=>array(),
						'ratings'=>array(),
						'rating'=>intval(0),
						'box-view-id' => 0,
					));
					
					$mdocs = mdocs_array_sort($mdocs);
					mdocs_save_list($mdocs);
					
/*					// SAVE for visibility
					array_push($mdocs, array(
						'id'=>(string)$upload['attachment_id'],
						'parent'=>(string)$upload['parent_id'],
						'filename'=>$upload['filename'],
						'name'=>$upload['name'],
						'desc'=>$upload['desc'],
						'type'=>$mdocs_fle_type,
						'cat'=>$mdocs_cat,
						'owner'=>$mdocs_user,
						'contributors'=>$_POST['mdocs-contributors'],
						'author'=>$_POST['mdocs-real-author'],
						'size'=>intval($mdocs_fle_size),
						'modified'=>$upload['modified'],
						'version'=>(string)$mdocs_version,
						'show_social'=>(string)$mdocs_social,
						'non_members'=> (string)$mdocs_non_members,
						'file_status'=>(string)$mdocs_file_status,
						'post_status'=> (string)$mdocs_post_status,
						'post_status_sys'=> (string)$mdocs_post_status_sys,
						'doc_preview'=>(string)$mdocs_doc_preview,
						'downloads'=>intval(0),
						'archived'=>array(),
						'ratings'=>array(),
						'rating'=>intval(0),
						'box-view-id' => $boxview_file['id'],
					));
*/

					remove_filter( 'upload_dir', __CLASS__ . '::change_boxinfo_doc_file_upload_dir' );  // Remove custom upload directory folder
				} else {
					
					//
					// Add data to folderdocinfo_files table, 
					// when there is no attachement on the file
					// Soon: when file is paper (not electronic) 
					//
					
					// If blank increment the counter
					//$folder_file_counter++;
					//$folder_file_sub_counter = 1; //Reset for new file folder
					
					
					
					if( $parent_child_single == 'parent' ) {
						$folder_file_counter++;
						$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						$folder_file_sub_counter = 1; //Reset for new file folder
						$folder_file_sub_id = $folder_file_id;
					} elseif( $parent_child_single == 'child') {
						
						$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						$folder_file_sub_id = $folder_file_id . '-a' . $folder_file_sub_counter;
						$folder_file_sub_counter++; //Increment it for next file.
					} elseif( $parent_child_single == 'single' ) {
						$folder_file_counter++;
						$folder_file_id = $request_id . '-' . $box_num . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						$folder_file_sub_counter = 1; //Reset for new file folder
						$folder_file_sub_id = $folder_file_id;
					}
					
					
					// Insert into wp_posts
					if( !$superfund ) {
						$mdocs_post = array(
							'post_title' => $boxinfo['Title'],
							'post_status' => 'publish',
							'post_author' => $current_user->ID,
							'post_date' => gmdate( 'Y-m-d H:i:s' ),
							'post_date_gmt' => gmdate( 'Y-m-d H:i:s' ),
							'post_type' => 'mdocs-posts',
							'post_content' => '[mdocs_post_page new=true]'
						);
						$mdocs_post_id = wp_insert_post( $mdocs_post, true );
						
						//$folder_file_id = $request_id . '-' . $boxinfo['Box'] . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						
						// Convert Date
						$time = strtotime( $boxinfo['Date'] );
						$newdatetimeformat = date( 'Y-m-d H:i:s' ,$time );
						
						// Insert data for folderdocinfo_files :: ECMS
						$folderdocfiles_info = [
							'post_id' => $mdocs_post_id, 
							//'folderdocinfo_id'  => $folder_file_id,
							'folderdocinfo_id'  => $folderdocinfo_id, 
// 							'folderdocinfofile_id'   => $folder_file_id,
							'folderdocinfofile_id'   => $folder_file_sub_id,
							'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
							//'file_name'  => $file_name,
							//'object_location'   => '/uploads/mdocs/',
							//'source_file_location' => $boxinfo['Folder/Filename'],
							'title'  => $boxinfo['Title'],
							//'date' => $boxinfo['Date'],
							'date' => $newdatetimeformat,
							//'description'   => 'x',
							//'tags' => $boxinfo['Tags'],
							'source_format' => "{$boxinfo['Source Format']}",
							'index_level' => $index_level
						];
						
					} else {
						// SUPERFUND
						
						$mdocs_post = array(
							'post_title' => $boxinfo['TITLE'],
							'post_status' => 'publish',
							'post_author' => $current_user->ID,
							'post_date' => gmdate( 'Y-m-d H:i:s' ),
							'post_date_gmt' => gmdate( 'Y-m-d H:i:s' ),
							'post_type' => 'mdocs-posts',
							'post_content' => '[mdocs_post_page new=true]'
						);
						$mdocs_post_id = wp_insert_post( $mdocs_post, true );
						
						$folder_file_id = $request_id . '-' . $boxinfo['PATT_BOX'] . '-' . str_pad( $index_level, 2, '0', STR_PAD_LEFT ) . '-' . $folder_file_counter;
						
						// Convert Date
						$time = strtotime( $boxinfo['DATE'] );
						$newdatetimeformat = date( 'Y-m-d H:i:s' ,$time );
						
						// Insert data for folderdocinfo_files :: SEMS Superfund
						$folderdocfiles_info = [
							'post_id' => $mdocs_post_id,
							//'folderdocinfo_id'  => $folder_file_id,
							'folderdocinfo_id'  => $folderdocinfo_id, 
							//'folderdocinfofile_id'   => $folder_file_id,
							'folderdocinfofile_id'   => $folder_file_sub_id,
							'attachment' => ( isset( $boxinfo['Folder/Filename'] ) && '' !== $boxinfo['Folder/Filename'] ) ? 1 : 0,
							//'file_name'  => $file_name,
							//'object_location'   => '/uploads/mdocs/',
							//'source_file_location' => $boxinfo['Folder/Filename'],
							'title'  => $boxinfo['TITLE'],
							//'date' => $boxinfo['DATE'],
							'date' => $newdatetimeformat,
							'doc_id' => $boxinfo['DOC_ID'],
							'doc_regid' => $boxinfo['DOC_REGID'],
							'source_format' => 'Paper',
							'index_level' => $index_level,
							//'description'   => 'x',
							//'tags' => $boxinfo['Tags']
						];
					}
					
					// If Box is used for Litigation, Congressional, or FOIA 
					if ( 'files_uploaded' == $data['ticket_useage'] ) {
						$folderdocfiles_info['freeze'] = 1;
					}
					
					// Insert into folderdocinfo_files
 					$table_name = $wpdb->prefix.'wpsc_epa_folderdocinfo_files';
					$diditwork = $wpdb->insert( $table_name, $folderdocfiles_info ); 
					$folderdocfiles_info_id = $wpdb->insert_id;
					
					
					
					//
					// DEBUG START
					//
/*
					ob_start();
					?>
					<div class="col-sm-12 ticket-error-msg">
						<?php esc_html_e( 'Folderdocinfo Files Test.', 'pattracking' ); ?>
						<br><br>
						<?php 
							echo 'superfund: ' . $superfund . '<br>';
							echo 'mdocs_post_id: ' . $mdocs_post_id . '<br>';
							echo 'folder_file_id: ' . $folder_file_id . '<br>';
							echo 'Folder/Filename: ' . $boxinfo['Folder/Filename'] . '<br>'; 
							echo 'Folder/Filename null: ' . is_null( $boxinfo['Folder/Filename']) . '<br>'; 
							echo 'folderdocfiles_info_id: ' . $folderdocfiles_info_id . '<br>'; 
							echo 'mdocs_post: <br>'; 
							echo '<pre>';
							print_r( $mdocs_post );
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
					//
					// DEBUG END
					//
					
				}
			}
			// End of New BoxInfo Code.
		}


		/**
		 * Change upload path
		 *
		 * @param  Array $dir Upload directory information as array.
		 */
		public static function change_boxinfo_doc_file_upload_dir( $dir ) {

			$dir['path']   = $dir['basedir'] . '/mdocs';
			$dir['url']    = $dir['baseurl'] . '/mdocs';
			$dir['subdir'] = '/mdocs';

			return $dir;
		}


		/**
		 * Get storage location row id
		 */
		public function get_new_storage_location_row_id() {
			global $wpdb;
			$table = $wpdb->prefix . 'wpsc_epa_storage_location';
			$data = array(
				'digitization_center' => 666,
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
		 * @param String $record_schedule_number Record schedule number as string.
		 */
		public function get_record_schedule_id( $record_schedule_number ) {

			global $wpdb;
			$programe_office_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}epa_record_schedule WHERE Record_Schedule_Number = %s ", $record_schedule_number ) );

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
						<label class="wpsc_ct_field_label" for="super_fund_flag">
						Are these documents part of SEMS? <?php echo $this->required? '<span style="color:red;">*</span>':'';?>
						</label>
						<?php 
						if($this->extra_info['custom_fields_extra_info_'.$field->term_id]){?><p class="help-block" style="<?php echo $extra_info_css?>"><?php echo $this->extra_info['custom_fields_extra_info_'.$field->term_id];?></p><?php }?>
						<select id="super-fund" class="form-control wpsc_drop_down" name="super-fund" >
							<option value=""><?php esc_html_e( 'Please Select', 'supportcandy' ); ?></option>
							<option value="no"><?php esc_html_e( 'No', 'supportcandy' ); ?></option>
							<option value="yes"><?php esc_html_e( 'Yes', 'supportcandy' ); ?></option>
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
					<div id="dzBoxUpload" class="dropzone">
						<div class="fallback">
							<input name="file" type="file" />
						</div>
						<div class="dz-default dz-message">
							<button class="dz-button" type="button">Drop your file here to upload (xlsx files allowed)</button>
						</div>
					</div>
					<div style="margin: 10px 0 10px;" id="attach_16" class="row spreadsheet_container"></div>

					<table style="display:none;margin-bottom:0;" id="boxinfodatatable" class="table table-striped table-bordered nowrap">
						<thead style="margin: 0 auto !important;">
							<tr>
								<th>Box</th>
								<th>Folder Identifier</th>
								<th>Title</th>
								<th>Date</th>
								<th>Author</th>
								<th>Addressee</th>
								<th>Record Type</th>
								<th>Record Schedule & Item Number</th>
								<th>Site Name</th>
								<th>Site ID #</th>
								<th>Close Date</th>
								<th>EPA Contact</th>
								<th>Access Type</th>
								<th>Source Format</th>
								<th>Program Office</th>
								<th>Index Level</th>
								<th>Essential Record</th>
								<th>Folder/Filename</th>
<!-- 								<th>Tags</th> -->
								<th>Parent/Child</th>
							</tr>
						</thead>
					</table>

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
							<button class="dz-button" type="button">Drop your SEMS file here to upload (xlsx files allowed)</button>
						</div>
					</div>
					<div style="margin: 10px 0 10px;" id="attach_SEMS" class="row spreadsheet_container"></div>

					<table style="display:none;margin-bottom:0;" id="boxinfodatatableSEMS" class="table table-striped table-bordered nowrap">
						<thead style="margin: 0 auto !important;">
							<tr>
								<th>DOC_REGID</th>
								<th>DOC_ID</th>
								<th>TITLE</th>
								<th>DATE</th>
								<th>DOC_CREATOR/EDITOR</th>
								<th>PATT_BOX</th>
<!-- 								<th>RECORD_SCHEDULE</th> -->
<!-- 								<th>INDEX_LEVEL</th> -->
								<th>LOCATION</th>
								<th>EPA_ID</th>
							</tr>
						</thead>
					</table>
																	
					<!-- File Upload Validation -->
					<input type="hidden" id="file_upload_cr_SEMS" name="file_upload_cr_SEMS" value="0" />
				</div>

				
				<script>
					jQuery('#boxdisplaydivSEMS').hide();
					
					// Fixes issue where superfund is redeclared on clicking 'Reset Form'
					if (typeof superfund !== 'undefined') {
						let superfund = jQuery('#super-fund').val();
					}
					
// 					let superfund_2 = jQuery('#super-fund').val();
					jQuery('#super-fund').change( function () {
						superfund = jQuery('#super-fund').val();
						if( superfund == 'yes' ) {							
							jQuery('#boxdisplaydivSEMS').show();
							jQuery('#boxdisplaydiv').hide();
						} else if( superfund == 'no' ) {
							jQuery('#boxdisplaydivSEMS').hide();
							jQuery('#boxdisplaydiv').show();							
						}
					});
				</script>
				<!-- End of new datatable -->
				<?php
			}
		}
	}

	new Patt_HooksFilters();
}
