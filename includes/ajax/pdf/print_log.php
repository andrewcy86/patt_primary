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

    //Set overall values for PDF
    $obj_pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $obj_pdf->SetCreator(PDF_CREATOR);
    $obj_pdf->SetTitle("Paper Asset Tracking Tool - Request Logs");
    $obj_pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $obj_pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN,'',PDF_FONT_SIZE_MAIN));
    $obj_pdf->setFooterFont(Array(PDF_FONT_NAME_DATA,'',PDF_FONT_SIZE_DATA));
    $obj_pdf->SetDefaultMonospacedFont('helvetica');
    $obj_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $obj_pdf->SetMargins(6, '10', 5);
    $obj_pdf->setPrintHeader(false);
    $obj_pdf->setPrintFooter(false);
    $obj_pdf->SetAutoPageBreak(true, 10);
    $obj_pdf->SetFont('helvetica', '', 11);

$get_all_csv_files = $wpdb->get_row("SELECT count(a.post_id) as count
            FROM wpqa_postmeta a
            INNER JOIN wpqa_posts b ON b.ID = a.post_id
            WHERE a.meta_value LIKE '%".$GLOBALS['id']."_log_backup%' ORDER BY b.post_date DESC");
            
$get_log = $wpdb->get_results("
SELECT 
rel.post_id as id,
posts.post_date as date,
posts.post_content as content
    FROM " . $wpdb->prefix . "posts AS posts
        LEFT JOIN " . $wpdb->prefix . "postmeta AS rel ON 
            posts.ID = rel.post_id
        LEFT JOIN " . $wpdb->prefix . "postmeta AS rel2 ON
            posts.ID = rel2.post_id
    WHERE
        posts.post_type = 'wpsc_ticket_thread' AND
        posts.post_status = 'publish' AND 
        rel2.meta_key = 'thread_type' AND
        rel2.meta_value = 'log' AND
        rel.meta_key = 'ticket_id' AND
        rel.meta_value =" .$GLOBALS['id'] ."
    ORDER BY
    posts.post_date DESC");

$num = $GLOBALS['id'];
$str_length = 7;
$padded_request_id = substr("000000{$num}", -$str_length);

$tbl = '<h1 style="font-size: 18px">Request logs for Request #'.$padded_request_id.'</h1>';

if($get_all_csv_files->count > 0) {

$tbl .= '<strong style="color:red">This request contains archived logs. To view these logs please visit the <a href="'.get_site_url().'/wp-admin/admin.php?page=wpsc-tickets&id='.$padded_request_id.'">request details page</a>.</strong><br /><br />';

}

$tbl .= '
<table style="width: 638px;" cellspacing="0" nobr="true">
  <tr>
    <th style="border: 1px solid #000000; width: 45px; background-color: #f5f5f5; font-weight: bold;">ID</th>
    <th style="border: 1px solid #000000; width: 153px; background-color: #f5f5f5; font-weight: bold;">Log Date</th>
    <th style="border: 1px solid #000000; width: 365px; background-color: #f5f5f5; font-weight: bold;">Log Content</th>
  </tr>
';

foreach($get_log as $info){
    $log_id = $info->id;
    $log_date =  $info->date;
    $log_content = $info->content;
    
    $date = new DateTime($log_date, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('America/New_York'));
    
    $tbl .= '<tr>
            <td style="border: 1px solid #000000; width: 45px;">'.$log_id.'</td>
            <td style="border: 1px solid #000000; width: 153px;">'.$date->format('m-d-Y h:i:s a').' EST</td>
            <td style="border: 1px solid #000000; width: 365px;">'.$log_content.'</td>
            </tr>';
    
}
$tbl .= '</table>';

$obj_pdf->AddPage();
$obj_pdf->writeHTML($tbl, true, false, false, false, '');

$filename = 'patt_request_log_'.$padded_request_id.'.pdf';

    //Generate PDF
    $obj_pdf->Output($filename, 'I');
}

else
{
    //Define message for when no ID exists in URL
    echo "Pass request ID in URL";
}

?>
