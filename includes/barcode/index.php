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
    <link rel="stylesheet" type="text/css" href="./css/fonts.css" />
    <link rel="stylesheet" type="text/css" href="./css/styles.css" />
    <link rel="stylesheet" type="text/css" href="./styles.css" />
    <link rel="stylesheet" type="text/css" href="./css/prism.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
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
            <strong>Logged in as: </strong> <?php echo $current_user->display_name; ?> | <a href="<?php echo admin_url( 'admin.php?page=wpsc-tickets', 'https' ); ?>">Request Dashboard</a><br />
            <p>
                <strong>Scan a location barcode along with either a series of boxes or pallets.</strong> Only one location barcode can be scanned for each submission.
            </p>
            <button type="button" class="icon-barcode button scan">Start Scanning</button>
            
            <input type="hidden" id="user" value="<?php echo $current_user->display_name; ?>" />
            <input type="hidden" id="location" value="" />
            <input type="hidden" id="box_pallet_array" value="" />
            
            <div class="readers">

                <input type="hidden" value="code_128_reader" />


                
                <!--<label>
                    <span>EAN-13</span>
                    <input type="checkbox" checked name="ean_reader" />
                </label>
                <label>
                    <span>EAN-8</span>
                    <input type="checkbox" name="ean_8_reader" />
                </label>
                <label>
                    <span>UPC-E</span>
                    <input type="checkbox" name="upc_e_reader" />
                </label>
                <label>
                    <span>Code 39</span>
                    <input type="checkbox" checked name="code_39_reader" />
                </label>
                <label>
                    <span>Codabar</span>
                    <input type="checkbox" name="codabar_reader" />
                </label>
                <label>
                    <span>Code 128</span>
                    <input type="checkbox" checked name="code_128_reader" />
                </label>
                <label>
                    <span>Interleaved 2 of 5</span>
                    <input type="checkbox" name="i2of5_reader" />
                </label>-->
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
<script>
function wppatt_barcode_assignment_update(){		
		   $.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/update_barcode_assignment.php',{
postvarslocation: $("#location").val(),
postvarsboxpallet: $("#box_pallet_array").val(),
postvarsuser: $("#user").val()
}, 
   function (response) {
      if(!alert(response)){window.location.reload();}
   });
}
</script>
    <script src="./vendor/quagga.js"></script>
    <script src="index.js" type="text/javascript"></script>
    <script src="./vendor/prism.js"></script>
</body>

</html>
