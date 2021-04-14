<?php


$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -7)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );

$subfolder_path = site_url( '', 'relative'); 

//Check to see if URL has the correct Request ID
if (isset($_GET['id']))
{
    //Set SuperGlobal ID variable to be used in all functions below
    $GLOBALS['id'] = $_GET['id'];
    
    //Pull in the TCPDF library
    require_once ('tcpdf/tcpdf.php');
    
    
class MYPDF extends tcpdf {

    //Page header
    public function Header() {
        // Set font
        $this->SetFont('helvetica', '', 10);
        // Page number
        $this->Cell(0, 15, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}
    
    //Set styles
      $style_barcode = array('border' => 0,'vpadding' => 'auto','hpadding' => 'auto','fgcolor' => array(0,0,0),'bgcolor' => false,'module_width' => 1,'module_height' => 1);
    
    
    //Set overall values for PDF
    $obj_pdf = new MYPDF('L', 'mm', array('216', '356'), true, 'UTF-8', false);
    $obj_pdf->SetCreator(PDF_CREATOR);
    $obj_pdf->SetTitle("Box List Labels - Paper Asset Tracking Tool");
    $obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $obj_pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN,'',PDF_FONT_SIZE_MAIN));
    $obj_pdf->setFooterFont(Array(PDF_FONT_NAME_DATA,'',PDF_FONT_SIZE_DATA));
    $obj_pdf->SetDefaultMonospacedFont('helvetica');
    $obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $obj_pdf->SetMargins(6, '22', 6);
    $obj_pdf->setPrintHeader(true);
    $obj_pdf->setPrintFooter(false);

// set auto page breaks
$obj_pdf->SetAutoPageBreak(TRUE, 2);
$obj_pdf->SetFont('helvetica', '', 11);
        
        $record_schedules = $wpdb->get_results("SELECT DISTINCT " . $wpdb->prefix . "wpsc_epa_boxinfo.record_schedule_id as record_schedule_id, " . $wpdb->prefix . "epa_record_schedule.Schedule_Item_Number as rsnum 
FROM " . $wpdb->prefix . "epa_record_schedule, " . $wpdb->prefix . "wpsc_epa_boxinfo 
WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.record_schedule_id = " . $wpdb->prefix . "epa_record_schedule.id AND " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id =" .$GLOBALS['id']);
//print_r($record_schedules);

foreach($record_schedules as $rs_num)
    {
//REVIEW
$box_list = $wpdb->get_results("SELECT " . $wpdb->prefix . "wpsc_epa_program_office.office_acronym as program_office, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.index_level as index_level, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.folderdocinfofile_id as id, SUBSTR(" . $wpdb->prefix . "wpsc_epa_boxinfo.box_id, INSTR(" . $wpdb->prefix . "wpsc_epa_boxinfo.box_id, '-') + 1) as box, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.title as title, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.date as date, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.site_name as site, " . $wpdb->prefix . "wpsc_epa_boxinfo.lan_id as contact, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.source_format as source_format 
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "wpsc_epa_program_office, " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
WHERE
" . $wpdb->prefix . "wpsc_epa_boxinfo.program_office_id = " . $wpdb->prefix . "wpsc_epa_program_office.office_code AND 
" . $wpdb->prefix . "wpsc_epa_boxinfo.record_schedule_id = " .$rs_num->record_schedule_id ." AND
" . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id AND
" . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = ".$GLOBALS['id']
);

//REVIEW
$box_list_get_count = $wpdb->get_row("SELECT count(distinct " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.box_id) as box_count
FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files, " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "epa_record_schedule
WHERE 
" . $wpdb->prefix . "wpsc_epa_folderdocinfo_files.box_id = " . $wpdb->prefix . "wpsc_epa_boxinfo.id AND 
" . $wpdb->prefix . "wpsc_epa_boxinfo.record_schedule_id = " .$rs_num->record_schedule_id . " AND
" . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = ".$GLOBALS['id']);
//print_r($box_list_get_count);

$box_list_count = $box_list_get_count->box_count;
//echo $box_list_count;

$program_office_array_id = array();
        
$boxlist_get_po = $wpdb->get_results("SELECT DISTINCT " . $wpdb->prefix . "wpsc_epa_program_office.office_acronym as program_office
FROM " . $wpdb->prefix . "wpsc_epa_boxinfo, " . $wpdb->prefix . "wpsc_epa_program_office
WHERE " . $wpdb->prefix . "wpsc_epa_boxinfo.program_office_id = " . $wpdb->prefix . "wpsc_epa_program_office.office_code 
AND " . $wpdb->prefix . "wpsc_epa_boxinfo.ticket_id = " .$GLOBALS['id']);
//print_r($boxlist_get_po);

foreach ($boxlist_get_po as $item) {
	array_push($program_office_array_id, $item->program_office);
	}
	
$boxlist_po = join(", ", $program_office_array_id);


       $style_barcode = array(
        'border' => 0,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => array(
            0,
            0,
            0
        ),
        'bgcolor' => false,
        'module_width' => 1,
         'module_height' => 1 
         );
         
//$str_length = 7;
//$request_id = substr("000000{$GLOBALS['id']}", -$str_length);
$request_id = Patt_Custom_Func::ticket_to_request_id($GLOBALS['id']);

$url = 'http://' . $_SERVER['SERVER_NAME'] . $subfolder_path .'/wp-admin/admin.php?page=wpsc-tickets&id=' . $request_id;

$request_id_barcode =  $obj_pdf->serializeTCPDFtagParameters(array($url, 'QRCODE,H', '', '', '', 30, $style_barcode, 'N'));

//ECMS/SEMS indicator
$get_ecms_sems = $wpdb->get_row("SELECT a.request_id, b.meta_key, b.meta_value as ecms_sems
    FROM " . $wpdb->prefix . "wpsc_ticket a
    INNER JOIN " . $wpdb->prefix . "wpsc_ticketmeta b ON b.ticket_id = a.id
    WHERE b.meta_key = 'super_fund' AND a.id = " . $GLOBALS['id']);
$ecms_sems_indicator = $get_ecms_sems->ecms_sems;

$ecms_sems = '';
if($ecms_sems_indicator == 'true') {
    $ecms_sems = 'SEMS';
}
else {
    $ecms_sems = 'ECMS';
}

$tbl = '
<table style="width:97%;">
  <tr>
    <td><h1 style="font-size: 40px">Box List</h1></td>
    <td><strong>Record Schedule:</strong> '.$rs_num->rsnum.'<br /><br />
    <strong>Total Boxes in Accession:</strong> '.$box_list_count.'<br /><br />
    <strong>Program Office:</strong> '.$boxlist_po.' <br /><br />
    <strong>ECMS or SEMS:</strong> '.$ecms_sems.'</td>
    <td align="right"><tcpdf method="write2DBarcode" params="'.$request_id_barcode.'" /><strong>&nbsp; &nbsp; &nbsp; &nbsp; '.$request_id.'</strong><br /></td>
  </tr>
</table>
<table style="width: 763px;" cellspacing="0" nobr="true">
  <tr>
    <th style="border: 1px solid #000000; width: 280px; background-color: #f5f5f5; font-weight: bold;">ID</th>
    <th style="border: 1px solid #000000; width: 50px; background-color: #f5f5f5; font-weight: bold;">Box #</th>
    <th style="border: 1px solid #000000; width: 50px; background-color: #f5f5f5; font-weight: bold;">Index Level</th>
    <th style="border: 1px solid #000000; width: 170px; background-color: #f5f5f5; font-weight: bold;">Title</th>
    <th style="border: 1px solid #000000; width: 110px; background-color: #f5f5f5; font-weight: bold;">Date</th>
    <th style="border: 1px solid #000000; width: 60px; background-color: #f5f5f5; font-weight: bold;">Contact</th>
    <th style="border: 1px solid #000000; width: 110px; background-color: #f5f5f5; font-weight: bold;">Source Format</th>    
    <th style="border: 1px solid #000000; width: 130px; background-color: #f5f5f5; font-weight: bold;">Program Office</th>  
  </tr>
';

foreach($box_list as $info){
    $boxlist_id = $info->id;
    $boxlist_barcode =  $obj_pdf->serializeTCPDFtagParameters(array($boxlist_id, 'C128', '', '', 95, 20, 0.4, array('position'=>'S', 'border'=>false, 'padding'=>1, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
    $boxlist_box = $info->box;
    $boxlist_title = $info->title;
    $boxlist_date = $info->date;
    $boxlist_site = $info->site;
    $boxlist_contact = $info->contact;
    $boxlist_sf = $info->source_format;
    $boxlist_po = $info->program_office;
    $boxlist_il = $info->index_level;
    $boxlist_il_val = '';
    if($boxlist_il == 1) {
        $boxlist_il_val = "(Folder)"; 
        
    } else {
        $boxlist_il_val = "(File)";
    }
    
    $tbl .= '<tr>
            <td style="border: 1px solid #000000; width: 280px;"><tcpdf method="write1DBarcode" params="'.$boxlist_barcode.'" /></td>
            <td style="border: 1px solid #000000; width: 50px;">'.$boxlist_box.'</td>
            <td style="border: 1px solid #000000; width: 50px;">'.$boxlist_il_val.'</td>
            <td style="border: 1px solid #000000; width: 170px;">'.$boxlist_title.'</td>
            <td style="border: 1px solid #000000; width: 110px;">'.Patt_Custom_Func::get_converted_date($boxlist_date).'</td>
            <td style="border: 1px solid #000000; width: 60px;">'.$boxlist_contact.'</td>
            <td style="border: 1px solid #000000; width: 110px;">'.stripslashes($boxlist_sf).'</td>
            <td style="border: 1px solid #000000; width: 130px;">'.$boxlist_po.'</td>
            </tr>';
    
}
$tbl .= '</table>';

$obj_pdf->AddPage();
$obj_pdf->writeHTML($tbl, true, false, false, false, '');
    }
    
    //Generate PDF
    $obj_pdf->Output('patt_box_list_printout.pdf', 'I');
}

else
{
    //Define message for when no ID exists in URL
    echo "Pass request ID in URL";
}

?>