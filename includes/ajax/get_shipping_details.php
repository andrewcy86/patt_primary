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
        <div class="containerx">  
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
    
    
    // 
    // Duplicate Shipping Tracking Number Check
    // Get list of all shipping tracking numbers
    //
    
    let ship_track_arr = [];
    
    let ticket_id = <?php echo $ticket_id; ?>; 
    let subfolder = '<?php echo WPPATT_PLUGIN_URL; ?>';
       
    let data = {
      type: 'check_dups'
    }  
    
    jQuery.post( subfolder+"includes/ajax/fetch_shipping_data.php?ticket_id="+ticket_id, data, function( response ) {
        
      console.log( '-- validation --' );
      console.log({response:response});
      console.log({response:response.tracking_nums});
      
      jQuery.each( response.tracking_nums, function( index, val ) {
        
        //ship_track_arr[] = val;
        //let obj = {};
        //ship_track_arr.push( val.tracking_number.trim() );
        ship_track_arr.push( val );
        
      });
      
      console.log( 'Ship Track AJAX done.' );
      console.log({ship_track_arr:ship_track_arr});
        
    });

    
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
     /*invalidNotify: function(args) {
        jQuery('#alert-error-not-submit').removeClass('hidden');
     },*/

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
         
        console.log({INSERTitem:item});
        
        item.tracking_number = item.tracking_number.toLowerCase();
        item.tracking_number = item.tracking_number.trim();
        
        
/*        // OLD: 
          function( response ) {
            console.log( 'insert ajax response' );
            console.log( response );
            ship_track_arr.push( {'id':response.ticket_id ,'tracking_number':item.tracking_number } );
            console.log({INSERTship_track_arr:ship_track_arr});
            return response;
*/
        
        return jQuery.ajax({
          type: "POST",
          url: subfolder+"includes/ajax/fetch_shipping_data.php?ticket_id="+ticket_id,
          data: item
          });
      },
      onItemInserted: function( args ) {
        console.log( '--- onItemInserted ---' );
        console.log({args:args});
      },
      updateItem: function(item){
       var ticket_id = <?php echo $ticket_id; ?>; 
       var subfolder = '<?php echo WPPATT_PLUGIN_URL; ?>';
        
        console.log( 'UPDATE' );
        
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
        
        console.log({delete_item:item});
        console.log({trackingID:item.tracking_number});
        
        let index = -1;
        // Search through object
        ship_track_arr.forEach( function( value, i ){
          
          console.log({i:i, value:value});
          
          if( value.tracking_number.toLowerCase() == item.tracking_number ) {
            index = i;
            console.log( 'Delete Found.' );
          }
        });
        
        console.log({index:index});
        
        //const index = ship_track_arr.indexOf( item.tracking_number );
        if (index > -1) {
          ship_track_arr.splice(index, 1);
        }
        
        console.log({NEWship_track_arr:ship_track_arr});
       
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

   /* validate: function(value, item) { 
      console.log({item:item, value:value});
      
      var isTrue = '';
      var string = "DHL:";
      value = value.trim();
      
      let ext_shipping_regex = new RegExp( ext_shipping_term, 'i' ); // || ext_shipping_regex.test(value)
      let ext_shipping_regex_r3 = new RegExp( ext_shipping_term_r3, 'i' );
      
      let ext_shipping_bool = ( value.toLowerCase( ) == ext_shipping_term );
      let ext_shipping_r3_bool = ( value.toLowerCase( ) == ext_shipping_term_r3 );
      let isDup = false;
      let doubleExternal = false;
      
      // If statement only true when updating a previous tracking number.
      if( item.id ) {
        console.log( 'THIS IS AN UPDATE' );
        // item.id is the id of the shipping table
        // get the list of shipping tracking numbers WITH the ID, then compare to that. 
        console.log({});
        
        jQuery.each( ship_track_arr, function( i, val ) {
          
          console.log('------------------------------');
          console.log({val:val});
          
          if( val == undefined ) {
            console.log( 'val is undefined' );
          } else {
          
            if( val.id == item.id ) {
              console.log( 'Same ID' );
              
              if( val.tracking_number == ext_shipping_term || val.tracking_number == ext_shipping_term_r3 ) {
                console.log( 'old value was an external shipping number' );
                
                // remove from array.
                let index = -1;
                // Search through object
                index = i;
                
                console.log({index:index});
                
                //const index = ship_track_arr.indexOf( item.tracking_number );
                if (index > -1) {
                  ship_track_arr.splice(index, 1);
                  //return;
                }
                
              }
            } else if( item.tracking_number == ext_shipping_term || item.tracking_number == ext_shipping_term_r3 ) {
              console.log( 'external was added and modified.' );
              
              
              if( val.tracking_number.toLowerCase() == ext_shipping_term && ext_shipping_r3_bool == true ) {
                doubleExternal = true;
              }
              
              if( val.tracking_number.toLowerCase() == ext_shipping_term_r3 && ext_shipping_bool == true ) {
                doubleExternal = true;
              }
              
              console.log({doubleExternalLOOP:doubleExternal});
              
              if( doubleExternal == true ) {
                console.log( 'remove current' );
                
                let index = -1;
                // Search through object
                index = i;
                
                console.log({index:index});
                console.log('removing:');
                console.log({val:val});
                
                //const index = ship_track_arr.indexOf( item.tracking_number );
                if (index > -1) {
                  ship_track_arr.splice(index, 1);
                  console.log({ship_track_arrEXT:ship_track_arr});
                  //return;
                }
                
                // reset 
                doubleExternal = false;
                ext_shipping_bool = ( value.toLowerCase( ) == ext_shipping_term );
                ext_shipping_r3_bool = ( value.toLowerCase( ) == ext_shipping_term_r3 );
                
                
                
              }

              
            }
            
          }
        });
        
      } */
      
      // think this might not be needed. 
/*
      if( ext_shipping_bool == true && ext_shipping_r3_bool == true ) {
        doubleExternal = true;
      }
*/
      
  /*    console.log({ship_track_arr:ship_track_arr});
      
      // How to check if field being updated from external to r3 external or visa versa???
      
      jQuery.each( ship_track_arr, function( index, val ) {
        console.log({val:val, value:value});
        if( val.tracking_number == value.trim() ) {
          isDup = true;
        }
        
        if( val.tracking_number.toLowerCase() == ext_shipping_term && ext_shipping_r3_bool == true ) {
          doubleExternal = true;
        }
        
        if( val.tracking_number.toLowerCase() == ext_shipping_term_r3 && ext_shipping_bool == true ) {
          doubleExternal = true;
        }
        
        console.log({x:(val.tracking_number.toLowerCase() == ext_shipping_term), y:(val.tracking_number.toLowerCase() == ext_shipping_term_r3)});
        
      });
      
      console.log({ext_shipping_bool:ext_shipping_bool, ext_shipping_r3_bool:ext_shipping_r3_bool, isDup:isDup, doubleExternal:doubleExternal, ext_shipping_term:ext_shipping_term, ext_shipping_term_r3:ext_shipping_term_r3 });
      
      if( isDup == false && doubleExternal == false ) {
        if ( (/\b(1Z ?[0-9A-Z]{3} ?[0-9A-Z]{3} ?[0-9A-Z]{2} ?[0-9A-Z]{4} ?[0-9A-Z]{3} ?[0-9A-Z]|T\d{3} ?\d{4} ?\d{3})\b/i.test(value)
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
       /*       ticket_status_change( ticket_id, shipping_term_id, ext_shipping_r3_bool );
                
          }
        } else if((/\b([a-zA-Z0-9]{10,43})$/i.test(value)) && (value.toUpperCase().includes(string))) {
          var isTrue = true;
        } else {
          var isTrue = false;
        }
        
        if( isTrue == true ) {
          //ship_track_arr.push( value.trim() );
          console.log('Add tracking numer to ship_track_arr');
          ship_track_arr.push( {'id':'' ,'tracking_number':value.trim() } );
          console.log({ship_track_arr_VALIDATE:ship_track_arr});
        }
        
        return isTrue;
      } else if( isDup == true && doubleExternal == false ) {
        
        alert( 'Duplicate Tracking Number Dectected.');
        return false;
      } else if( doubleExternal == true ) {
        
        alert( 'Cannot use "' + ext_shipping_term + '" and "' + ext_shipping_term_r3 + '" on the same Request.');
        return false;
      }
      
      
      
      //});
    },*/
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
     //{ Name: "", Id: '' },
     { Name: "UPS", Id: 'ups' },
     { Name: "FedEx", Id: 'fedex' },
     { Name: "USPS", Id: 'usps' },
     { Name: "DHL", Id: 'dhl' },
     { Name: "External", Id: 'external' },
    ], 
    valueField: "Id", 
    textField: "Name", 
    editing: true,
    inserting: true,
    //css: "hide"
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
jQuery('.jsgrid-insert-mode-button').attr('title', 'Add New');
jQuery("<span class='sr-only'>Add New</span>").insertAfter(".jsgrid-insert-mode-button");
   
   
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