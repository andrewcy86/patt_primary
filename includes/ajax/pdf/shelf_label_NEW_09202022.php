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

// JM - 9/19/2022 - Save datacenter
$dc = $GLOBALS['dc'] = $_GET['dc'];

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

$valid_value = 0;
$valid_dc = 0;

function fetch_shelfid($shelf)
{
    
  print_r($shelf);
  // JM - 9/9/2022 - Collect the shelf Id's from the database
  $shelf_id_collection = $wpdb->get_results("
  SELECT shelf_id
  FROM " . $wpdb->prefix . "wpsc_epa_storage_status
  WHERE digitization_center = ". $dc ."
  AND shelf_id = ". $shelf ."
  ");

  $shelf_id_info = array();

  foreach($shelf_id_collection as $shelf_ids)
  {
      array_push($shelf_id_info, $shelf_ids->shelf_id);
  }

  //return $array;
  return $shelf_id_info;
}


// JM - 9/16/2022 - Capture empty data passed from URL
if ($GLOBALS['dc'] == '' ) {
  //echo("The data center is blank.  Please be sure to pass a valid datacenter value, East (E) or West (W).");
  $valid_dc = 1;
}
if ($GLOBALS['id'] == '') {
  //echo("The location value is blank.  Please provide a box location.");
  $valid_value = 1;
}else{
  $valid_value = 0;
}
// JM - 9/19/2022 - Check if the datacenter value is E or W, return error if not
if ($GLOBALS['dc'] == 'E' || $GLOBALS['dc'] == 'W') {
  $valid_dc = 0;
      
}else{
  $valid_dc = 1;
}
    


if($valid_dc < 1){
  // JM - Capture and processing of DataCenter variable from the URL
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

  }
}

if($valid_value < 1){


  $shelfid_final_array = $array();

  // JM - 9/15/2022 - Begin capute and processing of shelf_id's from the url
  if (preg_match("/^(\d{1,3}A_\d{1,3}B_\d{1,3}S_\d{1,3}P_(E|W|ECUI|WCUI))(?:,\s*(?1))*$/", $GLOBALS['id'])) {

    $shelfid_array= explode(',', $GLOBALS['id']);

    $shelfid_shelfonly = array();
    $shelf_valid = 0;



    // validate each shelf id collected from the url, keep only valid id's
    foreach($shelfid_array as $id){

      // cut last position from the shelfid_array to prep for evaluating if the shelf exist. 1A_1B_1S_1P_E
      //$shelfid_url_shelfonly = substr($id, 0, 7);

      // Get the position value of the current shelf id
      $pos_val = substr($id, 9, 1);

      // JM - 9/20/2022 - Keep the underscore, but remove the char's from the string to leave it in the following format: 1_2_3
      $prepped_id = "";
      $prepped_id = preg_replace("/[^a-z0-9_.@\_]/", '', $id);
      $prepped_id = substr($id, 0, 4);

      // JM - 9/19/2022 - Get all db shelf_id's
      $shelfid_shelfonly = fetch_shelfid($prepped_id);
      
      // If position is within 1-4
      if( $pos_val < 0 || $pos_val > 5){

        $shelf_valid = 0;
        
        // Check if the shelf is listed in the db by checking the returned array 
        if(in_array($prepped_id,  $shelfid_shelfonly)){

          // Add the original full location id from the url to the final array for printing
          array_push($shelfid_final_array, $id);
          $shelf_valid = 0;

        }else{

          $shelf_valid = 1;
        }        
      }else{

        $shelf_valid = 1;
      }
    }
    
    // JM - 9/19/2022 - Only create the tag if the Url array does not contain a invalid shelf_id
    if($shelf_valid == 0){

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

      // JM - 9/19/2022 - Chunk the new $shelfid_array values, $shelfid_final_array
      $batch = array_chunk($shelfid_final_array, $batch_of);

      //  $batch = array_chunk($shelfid_array, $batch_of);
        
      //print_r($batch);
      foreach($batch as $b) {
        
        //Open the table and its first row
            
        foreach($b as $info){
          $pieces = explode("_", $info);
          $aisle = substr($pieces[0],0,1);
          $bay = substr($pieces[1],0,1);
          $shelf = substr($pieces[2],0,1); 
          $position = substr($pieces[3],0,1);
                
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
      }

    }else{
      echo("The format of the location value is invalid.  The shelf id does not exist or the position is out of range.  Please provide a valid location.");
    }//end shelf id 
  }
}
  //Generate PDF
  $obj_pdf->Output('shelf_label_printout.pdf', 'I');



?>