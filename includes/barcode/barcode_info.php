<?php
global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -6)));
require_once('/public/data/patt/patt.54fc3dee/web/app/mu-plugins/pattracking/wp/wp-load.php');

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

    <title>PATT - Location Assignment Application/Lookup</title>
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width; initial-scale=1.0" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css" integrity="sha384-vSIIfh2YWi9wW0r9iZe7RJPrKwp6bG+s9QZMoITbCckVJqGCCRhc+ccxNcdpHuYu" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
       <link rel="stylesheet" type="text/css" href="./css/fonts.css" />
    <link rel="stylesheet" type="text/css" href="./css/styles.css" />
    <link rel="stylesheet" type="text/css" href="./styles.css" />
    <link rel="stylesheet" type="text/css" href="./css/prism.css" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/plug-ins/f2c75b7247b/integration/bootstrap/3/dataTables.bootstrap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/1.0.4/css/dataTables.responsive.css">
        
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script
  src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"
  integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30="
  crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  
    <script src="https://cdn.datatables.net/1.10.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/f2c75b7247b/integration/bootstrap/3/dataTables.bootstrap.js"></script>
    <script src="https://cdn.datatables.net/responsive/1.0.4/js/dataTables.responsive.js"></script>
    
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

#result 
{ 
            max-width: 100% !important;
            overflow-x: hidden !important;
            font-size:16px;
}

table.dataTable.stripe tbody tr.odd,
table.dataTable.display tbody tr.odd {
    background-color: #f9f9f9
}

table.dataTable.stripe tbody tr.odd.selected,
table.dataTable.display tbody tr.odd.selected {
    background-color: #acbad4
}

table.dataTable.display tbody tr.odd>.sorting_1,
table.dataTable.order-column.stripe tbody tr.odd>.sorting_1 {
    background-color: #f1f1f1
}

table.dataTable.display tbody tr.odd>.sorting_2,
table.dataTable.order-column.stripe tbody tr.odd>.sorting_2 {
    background-color: #f3f3f3
}

table.dataTable.display tbody tr.odd>.sorting_3,
table.dataTable.order-column.stripe tbody tr.odd>.sorting_3 {
    background-color: whitesmoke
}

table.dataTable.display tbody tr.odd.selected>.sorting_1,
table.dataTable.order-column.stripe tbody tr.odd.selected>.sorting_1 {
    background-color: #a6b4cd
}

table.dataTable.display tbody tr.odd.selected>.sorting_2,
table.dataTable.order-column.stripe tbody tr.odd.selected>.sorting_2 {
    background-color: #a8b5cf
}

table.dataTable.display tbody tr.odd.selected>.sorting_3,
table.dataTable.order-column.stripe tbody tr.odd.selected>.sorting_3 {
    background-color: #a9b7d1
}

table.dataTable.display tbody tr.even>.sorting_1,
table.dataTable.order-column.stripe tbody tr.even>.sorting_1 {
    background-color: #fafafa
}

table.dataTable.display tbody tr.even>.sorting_2,
table.dataTable.order-column.stripe tbody tr.even>.sorting_2 {
    background-color: #fcfcfc
}

table.dataTable.display tbody tr.even>.sorting_3,
table.dataTable.order-column.stripe tbody tr.even>.sorting_3 {
    background-color: #fefefe
}

table.dataTable.display tbody tr.even.selected>.sorting_1,
table.dataTable.order-column.stripe tbody tr.even.selected>.sorting_1 {
    background-color: #acbad5
}

table.dataTable.display tbody tr.even.selected>.sorting_2,
table.dataTable.order-column.stripe tbody tr.even.selected>.sorting_2 {
    background-color: #aebcd6
}

table.dataTable.display tbody tr.even.selected>.sorting_3,
table.dataTable.order-column.stripe tbody tr.even.selected>.sorting_3 {
    background-color: #afbdd8
}

  </style>
</head>

<body>
    <header>
        <div class="headline">
            <h1>PATT</h1>
            <h2>Location Assignment Application/Lookup</h2>
        </div>
    </header>

    <?php		
    if (($agent_permissions['label'] == 'Administrator') || ($agent_permissions['label'] == 'Agent') || ($agent_permissions['label'] == 'Manager'))
    {
    ?>
    
    <section id="container" class="container">
        <div class="controls">
            <strong>Logged in as: <i class="fas fa-user"></i></strong> <?php echo $current_user->display_name; ?><br />
            
            <i class="fas fa-home"></i> <a href="<?php echo admin_url( 'admin.php?page=wpsc-tickets', 'https' ); ?>">Request Dashboard</a> | <i class="fas fa-map-marker-alt"></i> <a href="index.php">Location</a> | <i class="fas fa-info-circle"></i> <a href="barcode_info.php"><strong>Lookup</strong></a>
            <hr />
            <p>
                <strong>Scan a request QR code, box, folder or file barcode for details.</strong>
            </p>
            <button type="button" class="icon-qrcode button scan" id="startButton">Start Scanning</button>
            <!--<span class="icon-spinner11" id="resetButton"></span>-->
        </div>
        
    <main class="wrapper" style="padding-top:2em">

      <div id="videoarea">
        <video id="video" width="100%" height="325px" style="margin:0; padding:0;"></video>
      </div>

      <div id="result"></div>

  </main>

    </section>

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

<script type="text/javascript" src="https://unpkg.com/@zxing/library@latest"></script>
  <script type="text/javascript">
    window.addEventListener('load', function () {
      let selectedDeviceId;
      const codeReader = new ZXing.BrowserMultiFormatReader()
      console.log('ZXing code reader initialized')
      codeReader.listVideoInputDevices()
        .then((videoInputDevices) => {

          document.getElementById('startButton').addEventListener('click', () => {
              $("#videoarea").show();
               document.getElementById('result').textContent = '';
            codeReader.decodeFromVideoDevice(undefined, 'video', (result, err) => {
              if (result) {
                console.log(result)
                document.getElementById('result').textContent = result.text
                
$.post(
   '<?php echo WPPATT_PLUGIN_URL; ?>includes/admin/pages/scripts/barcode_lookup.php',{
postvarsbarcode: result.text
}, 
   function (response) {
       
document.getElementById('result').innerHTML = response

    $('#dataTable').DataTable( {
        responsive: {
            details: {
                type: 'column'
            }
        },
        searching: false,
        columnDefs: [ {
            className: 'control',
            orderable: false,
            targets:   0
        } ],
        order: [ 1, 'asc' ]
    } );
$("#videoarea").hide();
codeReader.reset()   
   });
   
              }
              if (err && !(err instanceof ZXing.NotFoundException)) {
                console.error(err)
                document.getElementById('result').textContent = err
              }
            })
            console.log(`Started continous decode from camera with id ${selectedDeviceId}`)
          })

        })
        .catch((err) => {
          console.error(err)
        })
    })
  </script>
</body>

</html>
