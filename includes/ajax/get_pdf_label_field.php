<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

$agent_permissions = $wpscfunction->get_current_agent_permissions();

$ticket_id  = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '' ;
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
<p>Print box label and attach the label on the inside of the box lid in each box. Box labels should not be placed on the exterior to prevent carrier labels from obscuring barcodes with their shipping labels.</p>

<?php
$tabled_tag = get_term_by('slug', 'tabled', 'wpsc_statuses');
$tabled_term_id = $tabled_tag->term_id;


if ((($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager') || ($agent_permissions['label'] == 'Requester Pallet')) && $tabled_term_id == $status_id) {
    ?>
<strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/preliminary_box_label.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Preliminary Box Label</a></strong>
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
<p>Print Box list and place it into the first box of earch record schedule series.</p>
<strong><a href="<?php echo WPPATT_PLUGIN_URL . 'includes/ajax/pdf/box_list.php?id=' . htmlentities($ticket_id); ?>" target="_blank">Box List Printout</a></strong>

<h3>Step 3</h3>
<!--<p>Print folder/file labels. Folder seperate sheets must be placed as the first document in the folder. File labels must be placed on the top right of each document within the box.</p>-->
<p>Print folder/file labels. Folder separator sheets must be placed as the first document in the folder. File labels must be placed on the lower right hand corner of each document. Labels cannot cover any writing on the record, and location may deviate from this standard as necessary for accuracy. See box preparation/label placing Working Instruction for additional information.</p>

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

<ol>
<li>Use shrink wrap or straps to secure pallet.</li>
</ol>

<h3>Step 5</h3>
<p>Print shipping label and ensure that tracking number is properly entered into the Paper Asset Tracking Tool.</p>


<h3>Shipping Label Placement</h3>

<ol>
<li>Adhere the shipping label to the box using self-adhesive labels only. Do not use tape or glue.</li>  
<li>Be sure all edges are secure.</li>  
<li>Do not cover the barcode with tape or plastic wrap. Doing so will make your barcode un-scannable.</li>  
<li>Place the shipping label so it does not wrap around the edge of the package. The surface area of the address side of the parcel must be large enough to contain the entire label.</li>  
</ol>



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
