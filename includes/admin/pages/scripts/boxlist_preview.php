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
/* To force exceed overflow-x limit for demo purposes */
td {
  white-space: nowrap;
}

.clusterize-headers {
  overflow: hidden;
}

.clusterize-scroll {
    max-height: 550px !important;
}

.datacontent th {
    position: -webkit-sticky; // this is for all Safari (Desktop & iOS), not for Chrome
    position: sticky;
    top: 0;
    z-index: 10; // any positive value, layer order is global
    background: #fff;
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
  </table>
  </div>
  <div id="scrollArea" class="clusterize-scroll">
    <table class="datacontent">
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
    
      <tbody id="contentArea" class="clusterize-content">
        <tr class="clusterize-no-data">
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

var data = [

<?php

	$dim = $xlsx->dimension();
	$num_cols = $dim[0]-5;
	$num_rows = $dim[1];
	
	$count = 0;

	foreach ( $xlsx->rows( 0 ) as $r ) {
	    if($count > 1) {
		echo "'<tr>";
		for ( $i = 0; $i < $num_cols; $i ++ ) {
			echo '<td>' . addslashes( ! empty( $r[ $i ] ) ? $r[ $i ] : '&nbsp;' ) . '</td>';
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
        $('.clusterize-headers').fadeIn();
    } else {
        $('.clusterize-headers').fadeOut();
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