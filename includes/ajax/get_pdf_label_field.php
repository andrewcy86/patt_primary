<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

$agent_permissions = $wpscfunction->get_current_agent_permissions();

$ticket_id  = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '' ;
$recall_id  = isset($_POST['recall_id']) ? sanitize_text_field($_POST['recall_id']) : '' ;
$ticket_data = $wpscfunction->get_ticket($ticket_id);
$status_id   	= $ticket_data['ticket_status'];

$wpsc_appearance_modal_window = get_option('wpsc_modal_window');

$list_array = array();

//REVIEW   
$box_index_result = $wpdb->get_results( "SELECT DISTINCT b.index_level
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files b ON b.box_id = a.id
WHERE a.ticket_id = " . $ticket_id);

foreach ( $box_index_result as $box_index )
    {
        array_push($list_array, $box_index->index_level);
    }
        

$pallet_array = array();
        
$pallet_id_result = $wpdb->get_results( "SELECT DISTINCT pallet_id 
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE pallet_id <> '' AND 
ticket_id = " . $ticket_id);

foreach ( $pallet_id_result as $pallet_id )
    {
        array_push($pallet_array, $pallet_id->pallet_id);
    }
    
ob_start();
?>

<?php

//error handling: if there are no boxes in a request then steps won't display
$total_box_count = $wpdb->get_row("SELECT COUNT(a.box_id) as box_count
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON b.id = a.ticket_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location c on a.storage_location_id = c.id 
WHERE ((c.digitization_center <> 666) AND a.box_destroyed = 0)
AND b.id = " . $ticket_id);
$box_count = $total_box_count->box_count;


if($box_count == 0) {
?>
<h4>All boxes in this request have been destroyed or do not currently have an assigned location and cannot be printed.</h4>
<?php
}
// Recall Print Label
else if($recall_id != ''){
?>

<?php
if ((count($pallet_array) > 0) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet'))) {
    ?>
<div class="alert alert-danger" role="alert">
<span style="font-size: 1em; color: #8b0000;"> <i class="fas fa-tags" title="Pallet Label"></i> <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/pallet_label.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Print Pallet Labels</a></strong></span>
</div>
<?php
}
?>
<h2>Damaged Label(s)</h2>
<p>Reprint Box/Folder/File Label(s), if needed, (links below)</p>

<?php
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
$tabled_term_id = $tabled_tag->term_id;


if ((($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet')) && $tabled_term_id != $status_id) {
    ?>
<!-- <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/preliminary_box_label.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Preliminary Box Label</a></strong>-->
	<strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/box_label.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Box Labels</a></strong>
<?php
// } elseif(($agent_permissions['label'] == 'Requester') && $tabled_term_id == $status_id)  {
} elseif(($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet') || ($agent_permissions['label'] == 'Requester') && $tabled_term_id == $status_id)  {
?>
	<strong>Label not avaible at this time.</strong>
<?php
} else {
?>
	<!--<strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/preliminary_box_label.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Preliminary Box Label</a></strong>-->
<?php
}
?>

<br>

<?php
$box_index_value = current($list_array);
$list_array_count = count($list_array);

?>

<?php
if ($list_array_count == 1 && $box_index_value == 1 && $list_array_count != 0) {
?>
    <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/folder_separator_sheet.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Folder Labels</a></strong>
<?php
} elseif ($list_array_count == 1 && $box_index_value == 2 && $list_array_count != 0) {
?>
    <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/file_separator_sheet.php?id=' . htmlentities($ticket_id); ?>" target="_blank">File Labels</a></strong>
	<br>
<?php
} else {
?>
<?php
if ($list_array_count>1) {
    ?>
    <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/folder_separator_sheet.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Folder Labels</a></strong><br />
    <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/file_separator_sheet.php?id=' . htmlentities($ticket_id); ?>" target="_blank">File Labels</a></strong>
<?php
} else {
?>
<strong>Box not assigned to request.</strong>

<?php
}
}

?>


<h4>Step 1</h4>
<!--<p>Print box label and afix it to the side of the box.</p>-->
<!-- <p>Print box label and attach the label on the inside of the box lid in each box. Box labels should not be placed on the exterior to prevent carrier labels from obscuring barcodes with their shipping labels.</p> -->
<ul style="list-style: disc; padding-left: 50px;">
    <li>Review Box List and ensure recalled records are in the same order/folder/box as they were when recalled.</li>
</ul>
<strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/box_list.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Box List Printout</a></strong>

<?php
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
$tabled_term_id = $tabled_tag->term_id;
?>

<h4>Step 2</h4>
<p>If shipping more than 25 boxes at a time and a pallet is used, follow these instructions.</p>
<ul style="list-style: disc; padding-left: 50px;">
    <li>Use stretch wrap or straps to secure pallet.</li>
</ul>

<h4>Step 3</h4>
<ul style="list-style: disc; padding-left: 50px;">
    <li>Contact your shipping provider to arrange return shipment.</li>
</ul>

<p>Recalled records returning to NDC East, use the following address:</p>
<ul style="list-style: disc; padding-left: 50px;">
    <li>NDC East</li>
</ul>

<p>Recalled records returning to NDC West, use the following address:</p>
<ul style="list-style: disc; padding-left: 50px;">
    <li>NDC West</li>
</ul>

<h4>Step 4</h4>
<p>Print shipping label and ensure that tracking number is properly entered into the Paper Asset Tracking Tool.</p>
<ul style="list-style: disc; padding-left: 50px;">
  <li>In the Recall Details click the edit button beside "<strong>Shipping Tracking Number:</strong>" then,</li>
  <li>Click the <strong>Green Plus Sign</strong> button in the table to add a new shipping tracking number,</li>
  <li>Enter or paste your tracking number in the <strong>Tracking Number</strong> field, or if using external vendor (e.g. Iron Mountain) type
    "external"</li>
  <li>Click the <strong>Green Checkmark</strong> button</li>
  <li>Click the <strong>Close</strong> button</li>
</ul>

<h4>Step 5</h4>
<p>Apply the shipping label, using the Shipping Label Placement guidelines below, and provide the recalled records to your shipping provider.</p>

<h4>Shipping Label Placement</h4>
<ul style="list-style: disc; padding-left: 50px;">
  <li>Adhere the shipping Label to the box using self-adhesive labels only. Do not use tape or glue.</li>
  <li>Be sure all edges are secure.</li>
  <li>Do not cover the barcode with tape or plastic wrap. Doing so will make your barcode un-scannable.</li>
  <li>Place the shipping label so it does not wrap around the edge of the package. The surface area of the address side of the parcel must
    be large enough to contain the entire label.</li>
</ul>

<h4>Step 6</h4>
<p>PATT will use the shipping tracking number to track the location of your recalled records.</p>
<p>When records arrive at the NDC they will be checked for damage and that all recalled records were returned. If damage or a discrepancy is 
found the NDC will contact the RLO.</p>


<?php 

//end of steps 1-5
}
// Request Print Label
else {
?>

<?php
if ((count($pallet_array) > 0) && (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet'))) {
    ?>
<div class="alert alert-danger" role="alert">
<span style="font-size: 1em; color: #8b0000;"> <i class="fas fa-tags" title="Pallet Label"></i> <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/pallet_label.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Print Pallet Labels</a></strong></span>
</div>
<?php
}
?>

<h3>Step 1</h3>
<!--<p>Print box label and afix it to the side of the box.</p>-->
<!--<p>Print box label and attach the label on the inside of the box lid in each box. Box labels should not be placed on the exterior to prevent carrier labels from obscuring barcodes with their shipping labels.</p>-->
<ul style="list-style: disc; padding-left: 50px;">
    <li>Print box label.</li>
    <li>Tape label to the inside of the box lid.</li>
    <li>Repeat for each box in the request.</li>
</ul>
<p>Box labels should not be placed on the exterior to prevent carrier labels from obscuring barcodes with their shipping labels.</p>

<?php
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
$tabled_term_id = $tabled_tag->term_id;

// Assigned Location bay, aisle, shelf, and position query
$assigned_location_query = $wpdb->get_row("SELECT c.aisle as aisle, c.bay as bay, c.shelf as shelf, c.position as position
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo a
INNER JOIN " . $wpdb->prefix . "wpsc_ticket b ON b.id = a.ticket_id
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location c on a.storage_location_id = c.id 
WHERE ((c.digitization_center <> 666) AND a.box_destroyed = 0)
AND b.id = " . $ticket_id);
  
$assigned_location_aisle = $assigned_location_query->aisle;
$assigned_location_bay = $assigned_location_query->bay;
$assigned_location_shelf = $assigned_location_query->shelf;
$assigned_location_position = $assigned_location_query->position;
 

if ((($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet')) && $tabled_term_id == $status_id && 
   $assigned_location_aisle == 0 && $assigned_location_bay == 0 && $assigned_location_shelf == 0 && $assigned_location_position == 0) {
    ?>
<!--<strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/preliminary_box_label.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Preliminary Box Label</a></strong>-->
<?php
} elseif(($agent_permissions['label'] == 'Requester') && $tabled_term_id == $status_id)  {
?>
<strong>Label not avaible at this time.</strong>
<?php
} else {
?>
<strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/box_label.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Box Label</a></strong>
<?php
}
?>

<h3>Step 2</h3>
<!--<p>Print Box list and place it into the first box of earch record schedule series.</p>-->
<ul style="list-style: disc; padding-left: 50px;">
    <li>Print Box List (link below) and place it into the first box of each digitization request.</li>
</ul>
<strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/box_list.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Box List Printout</a></strong>

<h3>Step 3</h3>
<!--<p>Print folder/file labels. Folder seperate sheets must be placed as the first document in the folder. File labels must be placed on the top right of each document within the box.</p>-->
<!--<p>Print folder/file labels. Folder separator sheets must be placed as the first document in the folder. File labels must be placed on the lower right hand corner of each document. Labels cannot cover any writing on the record, and location may deviate from this standard as necessary for accuracy. See box preparation/label placing Working Instruction for additional information.</p>-->
<ul style="list-style: disc; padding-left: 50px;">
    <li>Print folder/file labels (links below).</li>
    <li>Folder separator sheets must be placed as the first document in the folder.</li>
    <li>File labels must be placed on the lower right hand corner of each document.</li>
  	<ul style="padding-left: 35px;">
      <li>Labels must not cover any writing on the record, and location may deviate from this standard as necessary for accuracy.</li>
      <li>See Generate/Print Box Listing/Barcodes/Labels Work Instruction for additional information.</li>
  	</ul>
</ul>

<?php
$box_index_value = current($list_array);
$list_array_count = count($list_array);

?>

<?php
if ($list_array_count == 1 && $box_index_value == 1 && $list_array_count != 0) {
?>
    <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/folder_separator_sheet.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Folder Labels</a></strong>
<?php
} elseif ($list_array_count == 1 && $box_index_value == 2 && $list_array_count != 0) {
?>
    <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/file_separator_sheet.php?id=' . htmlentities($ticket_id); ?>" target="_blank">File Labels</a></strong>
<?php
} else {
?>
<?php
if ($list_array_count>1) {
    ?>
    <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/folder_separator_sheet.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Folder Labels</a></strong><br />
    <strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/file_separator_sheet.php?id=' . htmlentities($ticket_id); ?>" target="_blank">File Labels</a></strong>
<?php
} else {
?>
<strong>Box not assigned to request.</strong>

<?php
}
}

?>

<h3>Step 4</h3>
<p>If shipping more than 25 boxes at a time and a pallet is used, follow these instructions.</p>
<ul style="list-style: disc; padding-left: 50px;">
    <li>Use shrink wrap or straps to secure pallet.</li>
</ul>


<h3>Step 5</h3>
<p>Print shipping label and ensure that tracking number is properly entered into the Paper Asset Tracking Tool.</p>


<h3>Shipping Label Placement</h3>

<ul style="list-style: disc; padding-left: 50px;">
<li>Adhere the shipping label to the box using self-adhesive labels only. Do not use tape or glue.</li>  
<li>Be sure all edges are secure.</li>  
<li>Do not cover the barcode with tape or plastic wrap. Doing so will make your barcode un-scannable.</li>  
<li>Place the shipping label so it does not wrap around the edge of the package. The surface area of the address side of the parcel must be large enough to contain the entire label.</li>  
</ul>



<?php 

//end of steps 1-5
}

$body = ob_get_clean();
ob_start();
?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>
<?php 
$footer = ob_get_clean();

$output = array(
  'body'   => $body,
  'footer' => $footer
);
echo json_encode($output);
