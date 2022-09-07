<?php

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -7)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

//Check to see if URL has the correct Request ID
//if (isset($_GET['id']))
//{

    //Set SuperGlobal ID variable to be used in all functions below
    $GLOBALS['id'] = $_GET['id'];
    $GLOBALS['dc'] = $_GET['dc'];

if (isset($_GET['dc'])) {

  if ($_GET['dc'] == 'E') {
    $dc = '62';
  }

  if ($_GET['dc'] == 'W') {
    $dc = '2';
  }
    //Pull in the TCPDF library
    require_once ('tcpdf/tcpdf.php');
   
    //Set overall values for PDF
    $obj_pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $obj_pdf->SetCreator(PDF_CREATOR);
    $obj_pdf->SetTitle("Shelf Labels - Paper Asset Tracking Tool");
    $obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $obj_pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN,'',PDF_FONT_SIZE_MAIN));
    $obj_pdf->setFooterFont(Array(PDF_FONT_NAME_DATA,'',PDF_FONT_SIZE_DATA));
    $obj_pdf->SetDefaultMonospacedFont('helvetica');
    $obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    //$obj_pdf->SetMargins(PDF_MARGIN_LEFT, '10', PDF_MARGIN_RIGHT);
    $obj_pdf->setPrintHeader(false);
    $obj_pdf->setPrintFooter(false);
    $obj_pdf->SetAutoPageBreak(true, 10);
    $obj_pdf->SetFont('helvetica', '', 11);

    $shelf_info = $wpdb->get_results("
    SELECT shelf_id
    FROM " . $wpdb->prefix . "wpsc_epa_storage_status
    WHERE digitization_center = ". $dc ."
    ");

$maxcols = 3;
$i = 0;

$batch_of = 30;
  
//print_r($shelf_info);
$new_shelf_id = array();
$object = new stdClass();
foreach($shelf_info as $info) {
$pc = 0;
foreach(range(1,3) as $index) {
$pc++;
$add_pos = $info->shelf_id . '_' .$pc;
$x = (object) [
    'shelf_id' => $add_pos
];
array_push($new_shelf_id, $x);
}  
}

//print_r($new_shelf_id);
  
$batch = array_chunk($new_shelf_id, $batch_of);
//print_r($batch);

foreach($batch as $b) {

//set table margins
$obj_pdf->SetMargins(2,15,0);
//$obj_pdf->SetRightMargin(500);
//$obj_pdf->SetHeaderMargin(-5);
//$obj_pdf->SetFooterMargin(16);

//Open the table and its first row

$tbl   =  '<style>
                .tableWithOuterBorder{
                    font-size: 9px; 
                    padding-top: 4px;
                    padding-bottom: 8px;
                    padding-left: 32.5px;
                    padding-right: -25px;
                }
                </style>';
                
$tbl .= '<table class="tableWithOuterBorder">';
$tbl .= '<tr>';

foreach($b as $info){

  $pieces = explode("_", $info->shelf_id);
  $aisle = $pieces[0];
  $bay = $pieces[1];
  $shelf = $pieces[2];  
  $position = $pieces[3];
  
  $aisle_bc = $pieces[0].'A';
  $bay_bc = $pieces[1].'B';
  $shelf_bc = $pieces[2].'S';  
  $position_bc = $pieces[3].'P';
  $shelf_id_bc = $aisle_bc.'_'.$bay_bc.'_'.$shelf_bc.'_'.$position_bc.'_'.$_GET['dc'];
  
    $shelf_id = $info->shelf_id;
    $shelf_barcode =  $obj_pdf->serializeTCPDFtagParameters(array($shelf_id_bc, 'C128', '', '', 57, 17, 0.4, array('position'=>'S', 'border'=>false, 'padding'=>1, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>false, 'font'=>'helvetica', 'fontsize'=>8), 'N'));
  
    if ($i == $maxcols) {
        $i = 0;
        $tbl .= '</tr><tr>';
    }
    $tbl .= '<td style="width: 190px; padding-left: 8px;"><tcpdf method="write1DBarcode" params="'.$shelf_barcode.'" /><span style="text-align: center;">Aisle: '. $aisle .' Bay: '. $bay .' Shelf: '. $shelf .' Position: '. $position .'</span></td>';

    $i++;

}

//Add empty <td>'s to even up the amount of cells in a row:
while ($i <= $maxcols-1) {
    $tbl .= '<td>&nbsp;</td>';
    $i++;
}

//Close the table row and the table
$tbl .= '</tr>';
$tbl .= '</table>';

$obj_pdf->AddPage();

//COMMENT OUT WHEN FINISH ADJUSTMENTS
//$img_file = '5160-avery-template.jpg';
//$obj_pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
  
$obj_pdf->writeHTML($tbl, true, false, false, false, '');

}       //endforeach
    
//Generate PDF
$obj_pdf->Output('shelf_label_printout.pdf', 'I');
  
} else {
   echo "Pass request ID in URL";
}
?>