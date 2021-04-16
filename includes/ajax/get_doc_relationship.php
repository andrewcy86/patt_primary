<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

if (!isset($_SESSION)) {
    session_start();    
}

$doc_id = $_POST["doc_id"];
$is_active = $_POST["is_active"];

ob_start();

if($is_active == 1) {
    $type = 'folderfile';
}
else {
    $type = 'folderfile_archive';
}

$get_parent_child = Patt_Custom_Func::get_parent_of_child($doc_id, $type);

//Display document IDs from folderdocinfofile and folderdocinfofile_archive table
if($is_active == 1) {
    $get_parent_child_id = $wpdb->get_row("SELECT id FROM ".$wpdb->prefix."wpsc_epa_folderdocinfo_files WHERE folderdocinfofile_id = '" . $get_parent_child . "'");
}
else {
    $get_parent_child_id = $wpdb->get_row("SELECT id FROM ".$wpdb->prefix."wpsc_epa_folderdocinfo_files_archive WHERE folderdocinfofile_id = '" . $get_parent_child . "'");
}
$parent_child_id = $get_parent_child_id->id;
			
echo '<h4>Parent Document ID: '.$get_parent_child.'</strong></h4>';
?>

<table id="tbl_child_documents" class="display nowrap" cellspacing="5" cellpadding="5" width="100%">
        <thead>
            <tr>
                <th class="datatable_header">ID</th>
                <th class="datatable_header">Child Document ID</th>
                <th class="datatable_header">Title</th>
            </tr>
        </thead>
    </table>

<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>

<script>
jQuery(document).ready(function(){

  var dataTable = jQuery('#tbl_child_documents').DataTable({
    'processing': true,
    'serverSide': true,
    'serverMethod': 'post',
    'stateSave': true,
    'scrollX' : true,
    'paging' : true,
    'searching': false, // Remove default Search Control
    'ajax': {
       'url':'<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/doc_relationship_processing.php',
       'data': function(data){
          // Read values
          var docid = <?php echo $parent_child_id; ?>;
          var isactive = <?php echo $is_active; ?>;
          // Append to data
          data.docid = docid;
          data.isactive = isactive;
       }
    },
    'lengthMenu': [[10, 25, 50, 100, 500, 1000], [10, 25, 50, 100, 500, 1000]],
    'drawCallback': function (settings) { 
        // Here the response
        var response = settings.json;
        console.log(response);
	},
	'columnDefs': [
    { 'visible': false, 'targets': 0 },
    { 'orderable': false, 'targets': 1 },
    { 'orderable': false, 'targets': 2 }
  ],
    'order': [[0, 'asc']],
    'columns': [
       { data: 'id' },
       { data: 'folderdocinfofile_id' },
       { data: 'title' }
    ]
  });

});
</script>

<?php 
$body = ob_get_clean();
ob_start();
?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();<?php if($response_page == 'filedetails') { ?>window.location.reload();<?php } ?>"><?php _e('Close','wpsc-export-ticket');?></button>
<?php 
$footer = ob_get_clean();

$output = array(
  'body'   => $body,
  'footer' => $footer
);
echo json_encode($output);