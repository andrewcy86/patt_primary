<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

global $wpdb, $current_user, $wpscfunction;

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

$agent_permissions = $wpscfunction->get_current_agent_permissions();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <title>PATT - Location Assignment Application</title>
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width; initial-scale=1.0" />
    
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" integrity="sha384-vSIIfh2YWi9wW0r9iZe7RJPrKwp6bG+s9QZMoITbCckVJqGCCRhc+ccxNcdpHuYu" crossorigin="anonymous">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
    
    <link rel="stylesheet" type="text/css" href="./css/fonts.css" />
    <link rel="stylesheet" type="text/css" href="./css/styles.css" />
    <link rel="stylesheet" type="text/css" href="./styles.css" />
    <link rel="stylesheet" type="text/css" href="./css/prism.css" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script
  src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"
  integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30="
  crossorigin="anonymous"></script>
  <style>
html,body
{
    max-width: 100% !important;
    overflow-x: hidden !important;
    font-size: 16px;
}

.ui-widget-overlay
{
  opacity: .50 !important; /* Make sure to change both of these, as IE only sees the second one */
  filter: Alpha(Opacity=50) !important;

  background: rgb(50, 50, 50) !important; /* This will make it darker */
}
#dialog
{
        	max-height: 271px;
        	overflow-y: auto;
}
  </style>
</head>

<body>
    <header>
        <div class="headline">
            <h1>PATT</h1>
            <h2>Location Assignment Application</h2>
        </div>
    </header>

    <?php		
    if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
    {
    ?>
    
    <section id="container" class="container">
        <div class="controls">
            <strong>Logged in as: <i class="fas fa-user"></i></strong> <?php echo $current_user->display_name; ?><br />
            <i class="fas fa-home"></i> <a href="<?php echo admin_url( 'admin.php?page=wpsc-tickets', 'https' ); ?>">Request Dashboard</a> | <i class="fas fa-map-marker-alt"></i> <a href="index.php"><strong>Location</strong></a> | <i class="fas fa-info-circle"></i> <a href="barcode_info.php">Lookup</a>
            <hr />
            <p>
                <strong>Scan a location barcode along with either a series of boxes or pallets.</strong> Only one location barcode can be scanned for each submission.
            </p>
            <button type="button" class="icon-barcode button scan">Start Scanning</button>
            
            <input type="hidden" id="user" value="<?php echo $current_user->display_name; ?>" />
            <input type="hidden" id="location" value="" />
            <input type="hidden" id="box_pallet_array" value="" />
            
            <div class="readers">

                <input type="hidden" value="code_128_reader" />

            </div>
            <span id="box_pallet_count"></span>
        </div>
        <div class="overlay overlay--inline">
            <div class="overlay__content">
                <div class="overlay__close">X</div>
            </div>
        </div>
    </section>
    <ul class="results">
    <li>

    <span class="icon-spinner11" id="refresh" onClick="window.location.reload();"></span>

    </li>
    </ul>

<?php
} else {
?>
<section id="container" class="container">
Please login to access this application. <br />
<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" alt="<?php esc_attr_e( 'Login', 'textdomain' ); ?>">
    <?php _e( 'Login', 'textdomain' ); ?>
</a>
</section>
<?php
}
?>
<!-- ui-dialog --> 
<div id="dialog" title=" "> 
</div>
<!-- ui-dialog --> 
<div id="dialog_warn" title=" "> 
</div>
<script>

$(document).ready(function() {

$('#dialog').dialog({
       autoOpen: false,
        height: "auto",
        width: 350,
        modal: true,
        position: {
            my: "center",
            at: "center",
            of: window
        },
          close: function(event, ui) {
          location.reload();
     }
});

$('#dialog_warn').dialog({
       autoOpen: false,
        height: "auto",
        width: 350,
        modal: true,
        position: {
            my: "center",
            at: "center",
            of: window
        }
});

});

function wppatt_barcode_assignment_update(){		
		   $.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_barcode_assignment.php',{
postvarslocation: $("#location").val(),
postvarsboxpallet: $("#box_pallet_array").val(),
postvarsuser: $("#user").val()
}, 
   function (response) {

$('.ui-dialog-title').text('Details');
$('.ui-dialog-content').html(response);

$('#dialog').dialog('open');

//if(!alert(response)){window.location.reload();}
//$('.ui-dialog-title').text('Details');
//$('.ui-dialog-content').html(response);

//$('#dialog').dialog('open');

//alert(response);
//window.location.reload();
   });
}
</script>
    <script src="./vendor/quagga.js"></script>
    <script src="index.js" type="text/javascript"></script>
    <script src="./vendor/prism.js"></script>
</body>

</html>
