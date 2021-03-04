<?php

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -7)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );


//Check to see if URL has the correct Request ID
if (isset($_GET['id']))
{
    //Set SuperGlobal ID variable to be used in all functions below
    $GLOBALS['id'] = $_GET['id'];
    
    //Pull in the TCPDF library
    require_once ('tcpdf/tcpdf.php');
    
    //Set styles
      $style_barcode = array('border' => 0,'vpadding' => 'auto','hpadding' => 'auto','fgcolor' => array(0,0,0),'bgcolor' => false,'module_width' => 1,'module_height' => 1);
 
    //Function to get pallet_id from database
    function fetch_palletid()
    {
        global $wpdb;
        $array = array();
        
        $boxinfo_palletid = $wpdb->get_results("SELECT DISTINCT pallet_id 
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE pallet_id <> '' AND 
ticket_id = '" .$GLOBALS['id']."'");
        
        foreach($boxinfo_palletid as $pallet_id)
        {
            array_push($array, $pallet_id->pallet_id);
        }
        
        return $array;
    }
    
    //Set overall values for PDF
    $obj_pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $obj_pdf->SetCreator(PDF_CREATOR);
    $obj_pdf->SetTitle("Pallet Labels - Paper Asset Tracking Tool");
    $obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $obj_pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN,'',PDF_FONT_SIZE_MAIN));
    $obj_pdf->setFooterFont(Array(PDF_FONT_NAME_DATA,'',PDF_FONT_SIZE_DATA));
    $obj_pdf->SetDefaultMonospacedFont('helvetica');
    $obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $obj_pdf->SetMargins(PDF_MARGIN_LEFT, '10', PDF_MARGIN_RIGHT);
    $obj_pdf->setPrintHeader(false);
    $obj_pdf->setPrintFooter(false);
    $obj_pdf->SetAutoPageBreak(true, 10);
    $obj_pdf->SetFont('helvetica', '', 11);
    //$obj_pdf->AddPage();
    
    //1D barcode coordinates
    $x_loc_1d = 20;
    $y_loc_1d = 120;
    //1D barcode x-coordinate for attachments
    $x_loc_1d_a = 21;
    
    //pallet_id coordinates
    $x_loc_palletid = 55;
    $y_loc_palletid = 180;
    
    //Box Count coordinates
    $x_loc_pallet_count = 40;
    $y_loc_pallet_count = 210;
    
    //Location coordinates
    $x_loc_pallet_center = 80;
    $y_loc_pallet_center = 40;
    
if ((preg_match('/^\d+$/', $GLOBALS['id'])) || (preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $GLOBALS['id']))) {
 
if (preg_match('/^\d+$/', $GLOBALS['id'])) {   
    //Obtain array of Pallet ID's
    $palletid_array = fetch_palletid();
}


//print_r ($palletid_array);
if (preg_match("/^(P-(E|W)-[0-9]{1,5})(?:,\s*(?1))*$/", $GLOBALS['id'])) {

$palletid_array = explode(',', $GLOBALS['id']);

}

    //Begin for loop to iterate through pallet id's arrayb
    for ($i = 0;$i < count($palletid_array);$i++)
    {

        //checks to see if pallet_id is empty, if so won't reprint pallet labels
        $palletid_new = $wpdb->get_row("SELECT COUNT(id) as count
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo
WHERE
pallet_id = '" .$palletid_array[$i]."'");
        
        $pallet_id_count = $palletid_new->count;

        if($pallet_id_count > 0) {

            $obj_pdf->AddPage();

        $strings = explode('-',$palletid_array[$i]);

        $obj_pdf->SetFont('helvetica', '', 190);
        $obj_pdf->MultiCell(145, 0, $strings[1], 0, 'L', 0, 0, $x_loc_pallet_center, $y_loc_pallet_center, true, 0, true);
        
        //1D Box ID Barcode
        $obj_pdf->SetFont('helvetica', '', 30);

        $obj_pdf->write1DBarcode($palletid_array[$i], 'C128', $x_loc_1d, $y_loc_1d, '', 50, 2, $style_barcode, 'N');
        //Folderdocinfo_id
        $obj_pdf->SetXY($x_loc_palletid, $y_loc_palletid);
        $obj_pdf->SetFont('helvetica', '', 50);
        $obj_pdf->SetFillColor(255,255,255);
        $obj_pdf->SetLineStyle(array('width' => 0, 'cap' => 'butt', 'join' => 'butt', 'dash' => 0, 'color' => array(255, 255, 255)));
        $obj_pdf->Cell(100, 0, $palletid_array[$i], 1, 0, 'C', 1);
        
        
        $obj_pdf->SetFont('helvetica', '', 60);
        $txt = 'Box Count: '.$pallet_id_count;
        $obj_pdf->MultiCell(145, 0, $txt, 0, 'L', 0, 0, $x_loc_pallet_count, $y_loc_pallet_count, true, 0, true);
       }
    }
    
    //Generate PDF
    $obj_pdf->Output('pallet_label_printout.pdf', 'I');
    
} else {
echo "Pass a valid ID in URL";
}

} else {
    //Define message for when no ID exists in URL
    echo "Pass request ID in URL";
}

?>