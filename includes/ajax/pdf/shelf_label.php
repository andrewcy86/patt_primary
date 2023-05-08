<?php

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -7)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

//Check to see if URL has the correct Request ID

    //Set SuperGlobal ID variable to be used in all functions below
	$url_id = $_GET['id'] ?? '';
    $url_dc = $_GET['dc'] ?? '';
	$invalid_shelf_id = 0;

	if(!empty($url_dc) || !empty($url_id)) {
   

    $GLOBALS['id'] = $_GET['id'];
    $GLOBALS['dc'] = $_GET['dc'];

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

$maxcols = 3;
$i = 0;

$batch_of = 30;

if(!empty($url_dc)) {
  
if (preg_match("/^(E|W)/", $GLOBALS['dc'])) {
  
    if ($GLOBALS['dc'] == 'E') {
      $dc = '62';
    }
  
    if ($GLOBALS['dc'] == 'W') {
      $dc = '2';
    }
  
      $shelf_info = $wpdb->get_results("
    SELECT shelf_id
    FROM " . $wpdb->prefix . "wpsc_epa_storage_status
    WHERE digitization_center = ". $dc ." 
    AND (OCCUPIED = 0 && REMAINING = 3) || (OCCUPIED = 1 && REMAINING > 0);
    ");
  
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
  $new_bay = Patt_Custom_Func::get_bay_from_number($bay);
  $shelf = $pieces[2];  
  $position = $pieces[3];
  
  $aisle_bc = $pieces[0].'A';
  $bay_bc = $pieces[1].'B';
  $shelf_bc = $pieces[2].'S';  
  $position_bc = $pieces[3].'P';
  $shelf_id_bc = $aisle_bc.'_'.$bay_bc.'_'.$shelf_bc.'_'.$position_bc.'_'.$_GET['dc'];
  $new_shelf_id_bc = Patt_Custom_Func::convert_bay_letter($shelf_id_bc);
  
    $shelf_id = $info->shelf_id;
    $shelf_barcode =  $obj_pdf->serializeTCPDFtagParameters(array($new_shelf_id_bc, 'C128', '', '', 57, 17, 0.4, array('position'=>'S', 'border'=>false, 'padding'=>1, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>false, 'font'=>'helvetica', 'fontsize'=>8), 'N'));
  
    if ($i == $maxcols) {
        $i = 0;
        $tbl .= '</tr><tr>';
    }
    $tbl .= '<td style="width: 190px; padding-left: 8px;"><tcpdf method="write1DBarcode" params="'.$shelf_barcode.'" /><span style="text-align: center;">Aisle: '. $aisle .' Bay: '. $new_bay .' Shelf: '. $shelf .' Position: '. $position .'</span></td>';

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
echo 'Enter either E or W for digitization center.';
}
}
      
if(!empty($url_id)) {

$shelfid_array= explode(',', $GLOBALS['id']);

foreach($shelfid_array as $item) {
  
        $pre_pieces = explode("_", $item);
        $pre_aisle = preg_replace("/[^0-9]/", "", $pre_pieces[0] );
        
  		$pre_bay_letter = substr($pre_pieces[1], 0, -1);
  		$pre_bay = array_search($pre_bay_letter, $alphabet)+1;

        $pre_shelf = preg_replace("/[^0-9]/", "", $pre_pieces[2] );
        $pre_position = preg_replace("/[^0-9]/", "", $pre_pieces[3] );
  		$pre_dc= $pre_pieces[4];

  if ($pre_dc == 'E') {
    $dc_val = '62';
  }

  if ($pre_dc == 'W') {
    $dc_val = '2';
  }

if($pre_position == 1 || $pre_position == 2 || $pre_position == 3) {
  
$shelf_id_pre = $pre_aisle . '_' . $pre_bay . '_' . $pre_shelf;
  
  //Check Each Self ID
$shelf_info = $wpdb->get_row("SELECT count(id) as count
FROM " . $wpdb->prefix . "wpsc_epa_storage_status
WHERE  
digitization_center = '" .$dc_val."' AND
shelf_id = '" .$shelf_id_pre."'

");

if($shelf_info->count != 1) {
$invalid_shelf_id = 1;
}
  
} else {
$invalid_shelf_id = 1; 
}
  

}
  
if (preg_match("/^(\d{1,3}A_[A-Z]{1,2}B_\d{1,3}S_\d{1,3}P_(E|W|ECUI|WCUI))(?:,\s*(?1))*$/", $GLOBALS['id']) && $invalid_shelf_id != 1) {

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
    
    //set table margins
    $obj_pdf->SetMargins(2,15,0);  
    
    $batch = array_chunk($shelfid_array, $batch_of);
    
    //print_r($batch);
    foreach($batch as $b) {
    
    //Open the table and its first row
    
    foreach($b as $info){
        $pieces = explode("_", $info);
        $aisle = preg_replace("/[^0-9]/", "", $pieces[0] );
        $bay = substr($pieces[1], 0, -1);
        $shelf = preg_replace("/[^0-9]/", "", $pieces[2] ); 
        $position = preg_replace("/[^0-9]/", "", $pieces[3] );

        $shelf_barcode =  $obj_pdf->serializeTCPDFtagParameters(array($info, 'C128', '', '', 57, 17, 0.4, array('position'=>'S', 'border'=>false, 'padding'=>1, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>false, 'font'=>'helvetica', 'fontsize'=>8), 'N'));
      
        if ($i == $maxcols) {
            $i = 0;
            $tbl .= '</tr><tr>';
        }
        $tbl .= '<td style="width: 190px; padding-left: 8px;"><tcpdf method="write1DBarcode" params="'.$shelf_barcode.'" /><span style="text-align: center;">Aisle: '. $aisle .' Bay: '. $bay .' Shelf: '. $shelf .' Position: '. $position .'</span></td>';
    
        $i++;
    
    }
    
    
    //Close the table row and the table
    $tbl .= '</tr>';
    $tbl .= '</table>';
    
    $obj_pdf->AddPage();
    
    $obj_pdf->writeHTML($tbl, true, false, false, false, '');
    
    }
    //Generate PDF
$obj_pdf->Output('shelf_label_printout.pdf', 'I');

} else {
echo 'Enter a valid shelf ID.';
}
}
      
      
} else {
echo 'Please enter either dc or id parameter in the url.';
    }
?>