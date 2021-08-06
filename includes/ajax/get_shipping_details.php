<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $current_user, $wpscfunction;

$ticket_id 	 = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0 ;

ob_start();
?>

  <style>
  .hide
  {
     display:none;
  }
  
  .jsgrid-header-row>.jsgrid-header-cell {
    text-align: center;
  }
  
  .jsgrid-row>.jsgrid-cell {
    text-align: center;
  }
  
  .jsgrid-alt-row>.jsgrid-cell {
    text-align: center;
  }
  
    #grid_table {
  width: auto !IMPORTANT
}
  </style>
        <div class="container>  
   <br />
   <div class="table-responsive">
    <div id="grid_table"></div>
   </div>  
  </div>
<link rel="stylesheet" type="text/css" href="<?php echo WPPATT_PLUGIN_URL.'includes/admin/css/jsgrid.min.css';?>"/>
<link rel="stylesheet" type="text/css" href="<?php echo WPPATT_PLUGIN_URL.'includes/admin/css/jsgrid-theme.min.css';?>"/>
<script type="text/javascript" src="<?php echo WPPATT_PLUGIN_URL.'includes/admin/js/jsgrid.min.js';?>"></script>

<script>
 jQuery(document).ready(function() {

    jsGrid.ControlField.prototype.insertButtonClass = "jsgrid-update-button";
                            
    let ext_shipping_term = "<?php echo WPPATT_EXT_SHIPPING_TERM; ?>";
    ext_shipping_term = ext_shipping_term.toLowerCase( );
    let ext_shipping_term_r3 = "<?php echo WPPATT_EXT_SHIPPING_TERM_R3; ?>";
    ext_shipping_term_r3 = ext_shipping_term_r3.toLowerCase( );
    let shipping_term_id = "<?php echo get_term_by('slug', 'awaiting-agent-reply', 'wpsc_statuses')->term_id ?>";
    
    jQuery('#grid_table').jsGrid({
  
     width: "auto",
     height: "auto",

     //filtering: true,
     inserting:true,
     editing: true,
     sorting: true,
     paging: true,
     autoload: true,
     pageSize: 20,
     pageButtonCount: 5,
     deleteConfirm: "Do you really want to delete this tracking number?",

     controller: {
      loadData: function(filter){
       var ticket_id = <?php echo $ticket_id; ?>; 
       var subfolder = '<?php echo WPPATT_PLUGIN_URL; ?>';
       return jQuery.ajax({
        type: "GET",
        url: subfolder+"includes/ajax/fetch_shipping_data.php?ticket_id="+ticket_id,
        data: filter
       });
      },
      insertItem: function(item){
       var ticket_id = <?php echo $ticket_id; ?>; 
       var subfolder = '<?php echo WPPATT_PLUGIN_URL; ?>';
         
          item.tracking_number = item.tracking_number.toLowerCase();
          item.tracking_number = item.tracking_number.trim();
       return jQuery.ajax({
        type: "POST",
        url: subfolder+"includes/ajax/fetch_shipping_data.php?ticket_id="+ticket_id,
        data: item
       });
      },
      updateItem: function(item){
       var ticket_id = <?php echo $ticket_id; ?>; 
       var subfolder = '<?php echo WPPATT_PLUGIN_URL; ?>';
        
        item.tracking_number = item.tracking_number.toLowerCase();
        item.tracking_number = item.tracking_number.trim();
       return jQuery.ajax({
        type: "PUT",
        url: subfolder+"includes/ajax/fetch_shipping_data.php?ticket_id="+ticket_id,
        data: item
       });
      },
      deleteItem: function(item){
       var ticket_id = <?php echo $ticket_id; ?>; 
       var subfolder = '<?php echo WPPATT_PLUGIN_URL; ?>';
       return jQuery.ajax({
        type: "DELETE",
        url: subfolder+"includes/ajax/fetch_shipping_data.php?ticket_id="+ticket_id,
        data: item
       });
      },
     },

     fields: [
      {
       name: "id",
    type: "hidden",
    css: 'hide'
      },
            {
       name: "ticket-id",
    type: "hidden",
    css: 'hide'
      },
      {
       name: "tracking_number",
       title: "Tracking Number",
    type: "text", 
    width: 150, 

    validate: function(value, item) { 
    
    var isTrue = '';
    var string = "DHL:";
    value = value.trim();
    
    let ext_shipping_regex = new RegExp( ext_shipping_term, 'i' ); // || ext_shipping_regex.test(value)
    let ext_shipping_regex_r3 = new RegExp( ext_shipping_term_r3, 'i' );
    
    let ext_shipping_bool = ( value.toLowerCase( ) == ext_shipping_term );
    let ext_shipping_r3_bool = ( value.toLowerCase( ) == ext_shipping_term_r3 );
    

    if ((/\b(1Z ?[0-9A-Z]{3} ?[0-9A-Z]{3} ?[0-9A-Z]{2} ?[0-9A-Z]{4} ?[0-9A-Z]{3} ?[0-9A-Z]|T\d{3} ?\d{4} ?\d{3})\b/i.test(value)
    || /\b((420 ?\d{5} ?)?(91|92|93|94|01|03|04|70|23|13)\d{2} ?\d{4} ?\d{4} ?\d{4} ?\d{4}( ?\d{2,6})?)\b/i.test(value)
    || /\b((M|P[A-Z]?|D[C-Z]|LK|E[A-C]|V[A-Z]|R[A-Z]|CP|CJ|LC|LJ) ?\d{3} ?\d{3} ?\d{3} ?[A-Z]?[A-Z]?)\b/i.test(value)
    || /\b(82 ?\d{3} ?\d{3} ?\d{2})\b/i.test(value)
    || ext_shipping_bool
    || ext_shipping_r3_bool
    || /\b(((96\d\d|6\d)\d{3} ?\d{4}|96\d{2}|\d{4}) ?\d{4} ?\d{4}( ?\d{3})?)\b/i.test(value)) && (!value.toUpperCase().includes(string))) {
      var isTrue = true;
      
      // if using external shipping, move to new state .
      if( ext_shipping_bool || ext_shipping_r3_bool ) {
          let ticket_id = <?php echo $ticket_id; ?>; 
          ticket_status_change( ticket_id, shipping_term_id, ext_shipping_r3_bool );
            
      }
    } else if((/\b([a-zA-Z0-9]{10,43})$/i.test(value)) && (value.toUpperCase().includes(string))) {
      var isTrue = true;
    } else {
      var isTrue = false;
    }
    
    return isTrue;
        },
    formatter: function (cellvalue, options, rowObject) {
                    return "<a href='javascript:void(0);' class='anchor usergroup_name link'>" +
                           cellvalue + '</a>';
                }
      },
      {
       name: "company_name",
       title: "Shipping Company",
    type: "select", 
    items: [
     { Name: "", Id: '' },
     { Name: "UPS", Id: 'ups' },
     { Name: "FedEx", Id: 'fedex' },
     { Name: "USPS", Id: 'usps' },
     { Name: "DHL", Id: 'dhl' },
    ], 
    valueField: "Id", 
    textField: "Name", 
    editing: false,
    inserting: false,
    css: "hide"
      },
      {
       name: "status",
       title: "Shipping Status",
    type: "text", 
    width: 150, 
    editing: false,
    inserting: false
      },
      {
       type: "control"
      }
     ]


    });
jQuery('.jsgrid-insert-mode-button').attr('aria-label', 'Add New');

                 });
                 
                 
  function ticket_status_change( ticket_id, status_id, ext_shipping_r3_bool = false ) {
    console.log({ticket_id:ticket_id, status:status_id, ext_shipping_r3_bool:ext_shipping_r3_bool});
    
    let data = {
      action: "wppatt_set_ticket_status",
      ticket_id: ticket_id,
      status: status_id,
      ext_shipping_r3_bool: ext_shipping_r3_bool
    }
    
    jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
			
			var response = JSON.parse(response_str);
			console.log( 'new response' );
			console.log( {response:response} );
			
			
		});
  }
</script>

<?php 
$body = ob_get_clean();
ob_start();
?>
<button type="button" class="btn wpsc_popup_close"  style="background-color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_bg_color']?> !important;color:<?php echo $wpsc_appearance_modal_window['wpsc_close_button_text_color']?> !important;"   onclick="wpsc_open_ticket(<?php echo htmlentities($ticket_id)?>);wpsc_modal_close();"><?php _e('Close','wpsc-export-ticket');?></button>

<?php 
$footer = ob_get_clean();

$output = array(
  'body'   => $body,
  'footer' => $footer
);
echo json_encode($output);