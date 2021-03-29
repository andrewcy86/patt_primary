<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}



if ( ! class_exists( 'Patt_BatchUpload' ) ) {

	/**
	 * Class to manage the ticket request
	 */
	class Patt_BatchUpload {

		/**
		 * Get things started
		 *
		 * @access  public
		 * @since   1.0
		 */
		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'wpsc_print_ext_js_create_ticket' ) );

			// Print HTML In Request Form - Location: /home/acy3/public_html/wordpress3/wp-content/plugins/supportcandy/includes/admin/tickets/create_ticket/load_create_ticket.php.
			
			// don't want this as this is inside the create ticket form. 
			//add_action( 'print_listing_form_block', array( $this, 'print_listing_form_block_batch_upload' ) );
			
			//include WPPATT_ABSPATH . 'asset/js/batch_uploader_save.php';
			
			$this->print_listing_form_block_batch_upload();
			wp_enqueue_script( 'batch-uploader-save-js', WPPATT_PLUGIN_URL . 'asset/js/batch_uploader_save.js', array(), time(), true );
			
			wp_localize_script(
				'batch-uploader-save-js',
				'attachment_info',
				array(
					'max_filesize' => get_option( 'wpsc_attachment_max_filesize' ),
					'close_image' => WPSC_PLUGIN_URL . 'asset/images/close.png',
				)
			);
			
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
				//array_push( $attach_ids, $attachment_id ); // causing error, $attach_ids should be array, null given.

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

			//wp_enqueue_script( 'save-ticket-boxlist-js', WPPATT_PLUGIN_URL . 'asset/js/ticket_box_list_save.js', array(), time(), true );
			
		}

		/**
		 * Save Boxlist Records
		 *
		 * @param Array $data box list data as array.
		 */
		public function patt_process_boxinfo_records( $data ) {
			global $wpdb, $wpscfunction, $current_user;
			
			echo '<br><br>patt_process_boxinfo_records<br><br>';
			
		}


		//global $wpdb, $current_user, $wpscfunction;
		
		/*
		wp_enqueue_style( 'datatable-style', WPSC_PLUGIN_URL . 'asset/lib/DataTables/datatables.min.css', array(), time(), false );
		wp_enqueue_script( 'datatable-js', WPSC_PLUGIN_URL . 'asset/lib/DataTables/datatables.min.js', array(), time(), true );
		wp_enqueue_style( 'dropzone-style', WPPATT_PLUGIN_URL . 'asset/css/dropzone.min.css', array(), time(), false );
		wp_enqueue_script( 'dropzone-js', WPPATT_PLUGIN_URL . 'asset/js/dropzone.min.js', array(), time(), true );
		wp_enqueue_script( 'batch-uploader-save-js', WPPATT_PLUGIN_URL . 'asset/js/batch_uploader_save.js', array(), time(), true );
		*/


		public function print_listing_form_block_batch_upload(  ) {
			
		
			?>
			
			<div class="bootstrap-iso">
				<h3>Batch Uploader</h3>
				<p>
					1) Upload an excel file with all the files you are uploading here listed. A sample can be found <a href=''>here</a>.<br>
					2) Once the file listing is uploaded, the batch uploader will appear.<br>
					3) Drop all the files you want to upload in this new section. <br>
					
				</p>
				
				<div id="alert_status_file_list" class="alert_spacing"></div>
				<!-- Beginning of new datatable -->
				<form  >
					<div class="box-body table-responsive" id="boxdisplaydiv"
						style="width:100%;padding-bottom: 20px;padding-right:20px;padding-left:15px;margin: 0 auto;">
						<label class="wpsc_ct_field_label">Batch Upload List <span style="color:red;">*</span></label>
				
						<!-- DropZone xls File Drop Uploader -->
						<div id="dzBatchListUpload" class="dropzone">
							<div class="fallback">
								<input name="file" type="file" />
							</div>
							<div class="dz-default dz-message">
								<button class="dz-button" type="button">Drop your file here to upload (xlsx files allowed)</button>
							</div>
						</div>
						<div style="margin: 10px 0 10px;" id="batch_list_attachment" class="row spreadsheet_container"></div>
				
						<table style="display:none;margin-bottom:0;" id="batchlistdatatable" class="table table-striped table-bordered nowrap">
							<thead style="margin: 0 auto !important;">
								<tr>
									<th>File Name</th>
									<th>Disposition Schedule & Item Number</th>
								</tr>
							</thead>
						</table>
						
						<!-- Batch List File Upload Validation -->
						<input type="hidden" id="batch_list_upload_cr" name="batch_list_upload_cr" value="0" />
					</div>
				</form>
				<hr>
				
				<div id="alert_status_batch_uploader" class="alert_spacing"></div>
				
<!-- 				<div id="batch-uploader-dropzone" style="display: none;" > -->
				<div id="batch-uploader-dropzone"  >
<!-- 					<?php //include WPPATT_ABSPATH . 'includes/admin/pages/scripts/s3_modal_slice.php'; ?> -->
					<?php include WPPATT_ABSPATH . 'includes/admin/pages/scripts/s3_modal_slice_wp6.php'; ?>
				</div>
			
			
			</div>
			
			
			<?php 
		}
	
	}

	new Patt_BatchUpload();
}