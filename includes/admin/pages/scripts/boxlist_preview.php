<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require_once 'SimpleXLSX.php';
$attachment_id = $_GET['id'];

$filename_only = basename( get_attached_file( $attachment_id ) );
$file_path = get_attached_file( $attachment_id );

echo '<h3>Box List: '.$filename_only.'</h3>';
?>
<style>
html, body {
  height: 100%;
  overflow: hidden;
}

table{
    table-layout: fixed;
    margin-bottom: 0 !important;
    font-size: 12px !important;
}

td {
  word-wrap:break-word;
  max-width:450px;
}

.datacontent th {
    position: -webkit-sticky; // this is for all Safari (Desktop & iOS), not for Chrome
    position: sticky;
    top: 0;
    z-index: 10; // any positive value, layer order is global
    background: #fff;
}

.clusterize {
max-height: 85% !important;
}

.clusterize-headers {
    display: none;
    width: 100%;
    z-index: 1;
}

</style>
<!--HTML-->
<div class="clusterize">
  <div class="clusterize-headers">
  <table id="headersArea">
    <thead>
      <tr>
          <!-- PATT BEGIN -->
                <th scope="col" >*Box</th>
                <th scope="col" >*Folder Identifier</th>
                <th scope="col" >*Title</th>
                <th scope="col" >*Description of Record</th>                
                <th scope="col" >*Parent/Child</th>                
                <th scope="col" >*Creation Date</th>
                <th scope="col" >*Creator</th>
                <th scope="col" >Addressee</th>
                <th scope="col" >*Record Type</th>
                <th scope="col" >*Disposition Schedule & Item Number</th>
                <th scope="col" >Site Name</th>                
                <th scope="col" >Site ID # / OU</th>
                <th scope="col" >Close Date</th>               
                <th scope="col" >*EPA Contact</th>
                <th scope="col" >*Access Restrictions</th>
                <th scope="col" >**Specific Access Restrictions</th>
                <th scope="col" >*Use Restrictions</th>
                <th scope="col" >**Specific Use Restrictions</th>
                <th scope="col" >**Rights Holder</th>
                <th scope="col" >*Source Type</th>
                <th scope="col" >*Source Dimensions</th>
                <th scope="col" >*Program Office</th>
                <th scope="col" >**Program Area</th>
                <th scope="col" >*Index Level</th>
                <th scope="col" >*Essential Records</th>
                <th scope="col" >**Folder/Filename</th>
                <th scope="col" >Tags</th>
                <!-- PATT END -->
      </tr>
    </thead>
  </table>
  </div>
  <div id="scrollArea" class="clusterize-scroll">
    <table class="datacontent">
        <thead>
      <tr>
          <!-- PATT BEGIN -->
                <th scope="col" >*Box</th>
                <th scope="col" >*Folder Identifier</th>
                <th scope="col" >*Title</th>
                <th scope="col" >*Description of Record</th>                
                <th scope="col" >*Parent/Child</th>                
                <th scope="col" >*Creation Date</th>
                <th scope="col" >*Creator</th>
                <th scope="col" >Addressee</th>
                <th scope="col" >*Record Type</th>
                <th scope="col" >*Disposition Schedule & Item Number</th>
                <th scope="col" >Site Name</th>                
                <th scope="col" >Site ID # / OU</th>
                <th scope="col" >Close Date</th>               
                <th scope="col" >*EPA Contact</th>
                <th scope="col" >*Access Restrictions</th>
                <th scope="col" >**Specific Access Restrictions</th>
                <th scope="col" >*Use Restrictions</th>
                <th scope="col" >**Specific Use Restrictions</th>
                <th scope="col" >**Rights Holder</th>
                <th scope="col" >*Source Type</th>
                <th scope="col" >*Source Dimensions</th>
                <th scope="col" >*Program Office</th>
                <th scope="col" >**Program Area</th>
                <th scope="col" >*Index Level</th>
                <th scope="col" >*Essential Records</th>
                <th scope="col" >**Folder/Filename</th>
                <th scope="col" >Tags</th>
                <!-- PATT END -->
      </tr>
    </thead>
    
      <tbody id="contentArea" class="clusterize-content">
        <tr class="clusterize-no-data">
            <!-- PATT BEGIN -->
            <th scope="col" ></th>
            <!-- PATT END -->
            
          <td>Loading data…</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/clusterize.js/0.18.0/clusterize.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/foundation/6.1.1/foundation.min.css"/>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/clusterize.js/0.18.0/clusterize.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/lodash.js/0.10.0/lodash.min.js"></script>

<?php
if ( $xlsx = SimpleXLSX::parse($file_path)) {
?>
<script>
$('.clusterize-scroll').css({"max-height":"92%"});
var data = [

<?php

	$dim = $xlsx->dimension();
	$num_cols = $dim[0]-5;
	$num_rows = $dim[1];
	
	$count = 0;
	
	$xlsx->setDateTimeFormat('m/d/Y');

	foreach ( $xlsx->rows( 0 ) as $r ) {
	    if($count > 1) {
		echo "'<tr>";
		for ( $i = 0; $i < $num_cols; $i ++ ) {
		    
		    $esc_str1 = str_replace("\n", "", addslashes( ! empty( $r[ $i ] ) ? $r[ $i ] : '&nbsp;' ));
		    $esc_str = str_replace("\r", "", $esc_str1);
		    
		    $out = Patt_Custom_Func::wholeWordTruncate($esc_str,255);
		    
			echo '<td>' . $out . '</td>';
		}
		echo "</tr>',";
	    }
	    
	    $count++;
	}
	
?>

];


var $scroll = $('#scrollArea');
var $content = $('#contentArea');
var $headers = $("#headersArea");


/**
 * Makes header columns equal width to content columns
 */
var fitHeaderColumns = (function() {
  var prevWidth = [];
  return function() {
    var $firstRow = $content.find('tr:not(.clusterize-extra-row):first');
    var columnsWidth = [];
    $firstRow.children().each(function() {
      columnsWidth.push($(this).width());
    });
    if (columnsWidth.toString() == prevWidth.toString()) return;
    $headers.find('tr').children().each(function(i) {
      $(this).width(columnsWidth[i]);
    });
    prevWidth = columnsWidth;
  }
})();

/**
 * Keep header equal width to tbody
 */
var setHeaderWidth = function() {
  $headers.width($content.width());
}

/**
 * Set left offset to header to keep equal horizontal scroll position
 */
var setHeaderLeftMargin = function(scrollLeft) {
  $headers.css('margin-left', -scrollLeft);
}

var clusterize = new Clusterize({
  rows: data,
  scrollId: 'scrollArea',
  contentId: 'contentArea',
  callbacks: {
    clusterChanged: function() {
      fitHeaderColumns();
      setHeaderWidth();
    }
  }
});

/**
 * Update header columns width on window resize
 */
$(window).resize(_.debounce(fitHeaderColumns, 150));

/**
 * Update header left offset on scroll
 */
$scroll.on('scroll', (function() {
  var prevScrollLeft = 0;
  return function() {
      
    var y = $(this).scrollTop();
    if (y > 50) {
        $('.clusterize-headers').show();
        $('.clusterize-scroll').css({"max-height":"80%"});
    } else {
        $('.clusterize-headers').hide();
        $('.clusterize-scroll').css({"max-height":"90%"});
    }
    
    var scrollLeft = $(this).scrollLeft();
    if (scrollLeft == prevScrollLeft) return;
    prevScrollLeft = scrollLeft;

    setHeaderLeftMargin(scrollLeft);
  }
}()));
</script>

<?php
} else {
	echo SimpleXLSX::parseError();
}
?>