<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

wp_enqueue_style( 'datatable-style', WPSC_PLUGIN_URL . 'asset/lib/DataTables/datatables.min.css', array(), time(), false );
wp_enqueue_script( 'datatable-js', WPSC_PLUGIN_URL . 'asset/lib/DataTables/datatables.min.js', array(), time(), true );
wp_enqueue_style( 'dropzone-style', WPPATT_PLUGIN_URL . 'asset/css/dropzone.min.css', array(), time(), false );
wp_enqueue_script( 'dropzone-js', WPPATT_PLUGIN_URL . 'asset/js/dropzone.min.js', array(), time(), true );
wp_enqueue_script( 'batch-uploader-save-js', WPPATT_PLUGIN_URL . 'asset/js/batch_uploader_save.js', array(), time(), true );

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
					<th>Box</th>
					<th>Folder Identifier</th>
					<th>Title</th>
					<th>Description of Record</th>
					<th>Parent/Child</th>
					<th>Creation Date</th>
					<th>Creator</th>
					<th>Addressee</th>
					<th>Record Type</th>
				</tr>
			</thead>
		</table>
		
		<!-- Batch List File Upload Validation -->
		<input type="hidden" id="batch_list_upload_cr" name="batch_list_upload_cr" value="0" />
	</div>
	
	<hr>
	
	<div id="alert_status_batch_uploader" class="alert_spacing"></div>
	
	<div id="batch-uploader-dropzone" >
		<?php include WPPATT_ABSPATH . 'includes/admin/pages/scripts/s3_modal_slice.php'; ?>
	</div>


</div>


<?php 