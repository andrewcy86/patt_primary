<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_user, $wpscfunction, $wpdb;

if (!isset($_SESSION)) {
    session_start();    
}

$dc_id = $_POST["dc_id"];
$_SESSION["dc_id"] = $dc_id;


$box_id  = isset($_POST['box_id']) ? sanitize_text_field($_POST['box_id']) : '' ;
        
ob_start();

$box_details = $wpdb->get_row(
"SELECT " . $wpdb->prefix . "wpsc_epa_storage_location.digitization_center as digitization_center, " . $wpdb->prefix . "wpsc_epa_boxinfo.box_id as patt_box_id, " . $wpdb->prefix . "wpsc_ticket.request_id as request_id
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
INNER JOIN " . $wpdb->prefix . "wpsc_epa_storage_location ON " . $wpdb->prefix . "wpsc_epa_boxinfo.storage_location_id = " . $wpdb->prefix . "wpsc_epa_storage_location.id
INNER JOIN " . $wpdb->prefix . "wpsc_ticket ON " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = " . $wpdb->prefix . "wpsc_ticket.id
WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.id = '" . $box_id . "'"
			);

$digitization_center = $box_details->digitization_center;
$patt_box_id = $box_details->patt_box_id;
$patt_ticket_id = $box_details->request_id;

?>

<style>
#wrapper {
  margin-right: 500px;
}
#content {
  float: left;
  width: 100%;
}
#sidebar {
  float: right;
  width: 500px;
  margin-right: -500px;
}
#cleared {
  clear: both;
}
</style>

<div id="wrapper">
  <div id="content">
<span id="aisle_tag"><strong>Aisle</strong></span>
<select id="aisle_selector" name="aisle_selector" class="form-control" aria-label="Aisle Selector">    
<option value="0">--Select Aisle--</option>
<?php
// Updated 3 boxes to a shelf

//IF EAST
if($digitization_center == 62) {
  $digitization_center_aisle_total = 39;
}
//EDIT FOR WEST
if($digitization_center == 2) {
  $digitization_center_aisle_total = 39;
}

$aisle_array = range(1, $digitization_center_aisle_total);

foreach ($aisle_array as $value) {

if ($value < 10) {
$trim_val = 2;
} else {
$trim_val = 3;
}

    $get_available_aisle = $wpdb->get_row(
				"SELECT SUM(remaining) as count
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE LEFT(shelf_id, ".$trim_val.") = '" . $value . "_' AND digitization_center = '" . $digitization_center . "'"
			);
 
  
// Updated 3 boxes to a shelf
$remaining_boxes = $get_available_aisle->count;
$disabled = $remaining_boxes != 0 ? "" : "disabled";

// Updated 3 boxes to a shelf
  echo '<option value="'.$value.'" '.$disabled .'>Aisle #' . $value . ' [' . ($get_available_aisle->count) . ' boxes remain]'.'</option>';

}

?>
</select>
<br /><br />
<div id="bay_div">
<strong>Bay</strong>
<select name="bay_selector" class="form-control" id="bay_selector" aria-label="Bay Selector">
</select>
<br /><br />
  </div>
  <div id="sidebar">
  </div>
  <div id="cleared"></div>
</div>
		
		<script>

			jQuery(document).ready(function() {
              
              function convertNumberToLetterScheme(number) {
                var baseChar = ("A").charCodeAt(0),
                    letters  = "";

                do {
                  number -= 1;
                  letters = String.fromCharCode(baseChar + (number % 26)) + letters;
                  number = (number / 26) >> 0; // quick `floor`
                } while(number > 0);

                return letters;
             }
			    
			jQuery("#bay_div").hide();
		  // event called when the aisle select is changed
        jQuery("#aisle_selector").change(function(){
            jQuery("#shelf_position").hide();
            jQuery("#sidebar").hide();
            // get the currently selected aisle selector ID
            var aisleId = jQuery(this).val();
            
            jQuery.ajax({
                // make the ajax call to server and pass the aisle ID as a GET variable
                url: "<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/bay_query.php?aisle_id=" + aisleId + "&center=<?php echo $digitization_center ?>",
            }).done(function(data) {
                // our ajax call is finished, we have the data returned from the server in a var called data
                data = JSON.parse(data);
            if (aisleId == '0') {
            jQuery("#bay_div").hide();
            } else {
            jQuery("#bay_div").show();
            }
                    jQuery("#bay_selector").empty();
                // loop through our returned data and add an option to the select for each bay returned
                        jQuery('#bay_selector').append(jQuery('<option>', {value:0, text:'---Select Bay---'}).attr("disabled", false));
                jQuery.each(data, function(i, item) {
                  // Isolates the bay "#number" text out of the entire string
                  var sliced_item = item.slice(4,6);
                  var sliced_bay_number = item.slice(5,6);
                  //console.log(item);
				  var matches = /\[.*?(\d+).*?\]/g.exec(item);
//alert(matches[1]); 

        if (matches[1] = 0) {
        jQuery('#bay_selector').append(jQuery('<option>', {value:i, text:item}).attr("disabled", true));
        } else {
        jQuery('#bay_selector').append(jQuery('<option>', {value:i, text:item}).attr("disabled", false));
        }
                });

            });
        });
        
         jQuery("#bay_selector").change(function(){ 
            var bayId = jQuery(this).val();
            if (bayId == '0') {
            jQuery("#sidebar").hide();
            } else {
            jQuery("#sidebar").show();
            }
            jQuery("#sidebar").load("<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/shelf_position.php?aisle="+jQuery('#aisle_selector').val()+"&bay="+jQuery('#bay_selector').val()+"&center=<?php echo $digitization_center ?>&box_id=<?php echo $patt_box_id ?>&ticket_id=<?php echo $patt_ticket_id ?>"); 
         });
	
});		
		</script>

<?php 
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
