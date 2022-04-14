<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require_once 'SimpleXLSX.php';
$attachment_id = $_GET['id'];

$filename_only = basename( get_attached_file( $attachment_id ) );
$file_path = get_attached_file( $attachment_id );
?>
<!doctype html>
<html>
<head>
<title>Box List Preview</title>
<link rel="stylesheet" type="text/css" href="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.css';?>"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo WPSC_PLUGIN_URL.'asset/lib/DataTables/datatables.min.js';?>"></script>
<style>
body {
  font: 10px "Helvetica Neue", HelveticaNeue, Verdana, Arial, Helvetica, sans-serif;
  margin: 0;
  padding: 0;
  color: #333;
  background-color: #fff;
}


#resize_wrapper {
      position: absolute;
      top: 0em;
      left: 1em;
      right: 1em;
      bottom: 1em;
}
  </style>
</head>
<body>

<?php
echo '<h3>Box List: '.$filename_only.'</h3>';
?>
<div id="resize_wrapper">
<table id="boxlist" class="display" style="width:100%">
        <thead>
            <tr>
<th>*Box</th>
<th>*Folder Identifier</th>
<th>*Title</th>
<th>*Description of Record</th>                
<th>*Parent/Child</th>                
<th>*Creation Date</th>
<th>*Creator</th>
<th>Addressee</th>
<th>*Record Type</th>
<th>*Disposition Schedule & Item Number</th>
<th>Site Name</th>                
<th>Site ID # / OU</th>
<th>Close Date</th>               
<th>*EPA Contact</th>
<th>*Access Restrictions</th>
<th>**Specific Access Restrictions</th>
<th>*Use Restrictions</th>
<th>**Specific Use Restrictions</th>
<th>**Rights Holder</th>
<th>*Source Type</th>
<th>*Source Dimensions</th>
<th>*Program Office</th>
<th>**Program Area</th>
<th>*Index Level</th>
<th>*Essential Records</th>
<th>**Folder/Filename</th>
<th>Tags</th>
            </tr>
        </thead>
        <tbody>
<?php
if ( $xlsx = SimpleXLSX::parse($file_path)) {
  
	$dim = $xlsx->dimension();
	$num_cols = $dim[0]-5;
	$num_rows = $dim[1];
	
	$count = 0;
	
	$xlsx->setDateTimeFormat('m/d/Y');

	foreach ( $xlsx->rows( 0 ) as $r ) {
	    if($count > 1) {
		echo "<tr>";
		for ( $i = 0; $i < $num_cols; $i ++ ) {
		    
		    $esc_str1 = str_replace("\n", "", addslashes( ! empty( $r[ $i ] ) ? $r[ $i ] : '&nbsp;' ));
		    $esc_str = str_replace("\r", "", $esc_str1);
		    
		    $out = Patt_Custom_Func::wholeWordTruncate($esc_str,255);
		    
			echo '<td>' . $out . '</td>';
		}
		echo "</tr>";
	    }
	    
	    $count++;
	}
} else {
	echo SimpleXLSX::parseError();
}
?>
        </tbody>

    </table>
  </div> 

<script>
$(document).ready(function() {
  
var calcDataTableHeight = function() {
  return $(window).height()-150;
};

    $('#boxlist').dataTable( {
      	"paging": false,
        "bScrollInfinite": true,
        "bScrollCollapse": true,
        "scrollY": calcDataTableHeight(),
        "scrollX": true,
      	"scrollResize": true,
        "scrollCollapse": true,
        "lengthChange": false,
        "scroller": true
    } );
} );

</script>
  
</body>
</html>