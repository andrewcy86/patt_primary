<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

include_once( WPPATT_ABSPATH . 'includes/term-ids.php' );

$rescan_validate_status_id_arr = array($request_new_request_tag->term_id, $request_tabled_tag->term_id, $request_initial_review_rejected_tag->term_id, $request_cancelled_tag->term_id, $request_completed_dispositioned_tag->term_id);

if(
!empty($_POST['postvarsboxid'])
){
    
    $box_id = trim($_POST['postvarsboxid']);
    $pages = array();
    //echo "BEGIN " . $begin . "<br/>";
    //echo "END " . $end . "<br/>";
    
    $get_folder_file_dbid = $wpdb->get_row("SELECT MIN(a.id) as minid, MAX(a.id) as maxid FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id WHERE b.box_id = '".$box_id."' AND b.box_status = ".$box_validation_tag->term_id);
    
    $begin_dbid = $get_folder_file_dbid->minid;
    $end_dbid = $get_folder_file_dbid->maxid;
    
    //echo $begin_dbid . "<br/>";
    //echo $end_dbid . "<br/>";
    if( (!empty($begin_dbid) && !empty($end_dbid)) && ($begin_dbid <= $end_dbid)) {
        $get_range = $wpdb->get_results("SELECT id
        FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
        WHERE id BETWEEN " . $begin_dbid . " AND " . $end_dbid);

        foreach($get_range as $item) {
        array_push($pages, $item->id);
        }
    
//print_r($pages);


if ($_POST['postvarspid'] == ''){

$postvarspid = $pages[0];

    $get_folderdocid_initial= $wpdb->get_row("SELECT count(a.id) as count, a.folderdocinfofile_id as folderdocinfofile_id, a.validation as validation, b.box_status as box_status, c.ticket_status as ticket_status
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id
    INNER JOIN " . $wpdb->prefix . "wpsc_ticket c ON b.ticket_id = c.id
    WHERE a.id = '".$postvarspid."'");
    
    $folderdocinfofile_id_initial = $get_folderdocid_initial->folderdocinfofile_id;  
    $folderdocinfofile_id_count = $get_folderdocid_initial->count;
    
echo $box_status;

echo $box_ticket_status;
echo $folderdocinfofile_id_validation;


if($folderdocinfofile_id_count == 0) {
echo'<script type="text/javascript" id="runscript">switchFile("");switchInfo("");</script>';
} else {  
echo'<script type="text/javascript" id="runscript">
switchFile(\'' . $folderdocinfofile_id_initial . '\');switchInfo(\'' . $folderdocinfofile_id_initial . '\');
</script>';
}

} else {
$postvarspid = $_POST['postvarspid'];
}

    $get_folderdocid_subsequent= $wpdb->get_row("SELECT count(a.id) as count, a.folderdocinfofile_id as folderdocinfofile_id, a.validation as validation, a.rescan as rescan, b.box_status as box_status, c.ticket_status as ticket_status
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files a
    INNER JOIN " . $wpdb->prefix . "wpsc_epa_boxinfo b ON a.box_id = b.id
    INNER JOIN " . $wpdb->prefix . "wpsc_ticket c ON b.ticket_id = c.id
    WHERE a.id = '".$postvarspid."'");
    
    $folderdocinfofile_id_validation = $get_folderdocid_subsequent->validation;  
    $folderdocinfofile_id_rescan = $get_folderdocid_subsequent->rescan;  
    $box_status = $get_folderdocid_subsequent->box_status;
    $box_ticket_status = $get_folderdocid_subsequent->ticket_status;
    $folderdocinfofile_id_object_key = $get_folderdocid_subsequent->object_key;  
    

$current = isset($_POST['postvarspid']) ? $_POST['postvarspid'] : $pages[0];

// current page
$key = array_search($current, $pages);


//echo 'current page: ' . $pages[$key] . '<br />';

    $get_folderdocid_current = $wpdb->get_row("SELECT folderdocinfofile_id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE id = '".$pages[$key]."'");

    $folderdocinfofile_id_current = $get_folderdocid_current->folderdocinfofile_id;  
    
// previous page
$prev = $key - 1;
if ($prev >= 0 && $prev < count($pages)) {

    $get_folderdocid_prev = $wpdb->get_row("SELECT folderdocinfofile_id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE id = '".$pages[$prev]."'");
    
    $folderdocinfofile_id_prev = $get_folderdocid_prev->folderdocinfofile_id;

    echo '<button class="btn" onclick="getPaging(' . $pages[$prev] . ');switchInfo(\'' . $folderdocinfofile_id_prev . '\');switchFile(\'' . $folderdocinfofile_id_prev . '\');"><i class="fas fa-angle-left" aria-hidden="true" title="Previous"></i><span class="sr-only">Previous</span>  Previous</button>&nbsp;&nbsp;&nbsp;';
        
} else {
    echo '<button class="btn" disabled>Previous</button>&nbsp;&nbsp;&nbsp;';
}

// next page
$next = $key + 1;
if ($next >= 0 && $next < count($pages)) {
    $get_folderdocid_next = $wpdb->get_row("SELECT folderdocinfofile_id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE id = '".$pages[$next]."'");
    
    $folderdocinfofile_id_next = $get_folderdocid_next->folderdocinfofile_id;    
    
    echo '<button class="btn" onclick="getPaging(' . $pages[$next] . ');switchInfo(\'' . $folderdocinfofile_id_next . '\');switchFile(\'' . $folderdocinfofile_id_next . '\');">Next <i class="fas fa-angle-right" aria-hidden="true" title="Next"></i><span class="sr-only">Next</span></button>';

if (($box_status == $box_validation_tag->term_id || $box_status == $box_rescan_tag->term_id) && !in_array($box_ticket_status, $rescan_validate_status_id_arr) && ($folderdocinfofile_id_validation != 1) && ($folderdocinfofile_id_rescan != 1) && ($folderdocinfofile_id_object_key != NULL || $folderdocinfofile_id_object_key != '')) {
    echo '&nbsp;&nbsp;&nbsp;<button class="btn" id="next_validate" class="btn" onclick="executeValidation(\'' . $folderdocinfofile_id_current . '\');getPaging(' . $pages[$next] . ');switchInfo(\'' . $folderdocinfofile_id_next . '\');switchFile(\'' . $folderdocinfofile_id_next . '\');">Next + Validate <i class="fas fa-angle-double-right" aria-hidden="true" title="Next and Validate"></i><span class="sr-only">Next and Validate</span></button>';
    //Rescan button
    echo '&nbsp;&nbsp;&nbsp;<button class="btn" id="next_rescan" class="btn" onclick="executeRescan(\'' . $folderdocinfofile_id_current . '\');getPaging(' . $pages[$next] . ');switchInfo(\'' . $folderdocinfofile_id_next . '\');switchFile(\'' . $folderdocinfofile_id_next . '\');document.getElementById(\'paging\').contentWindow.location.reload();">Next + Re-Scan <i class="fas fa-angle-double-right" aria-hidden="true" title="Re-Scan"></i><span class="sr-only">Re-Scan</span></button>';
}
    
} else {
    echo '<button class="btn" disabled>Next</button>';
    
if (($box_status == $box_validation_tag->term_id || $box_status == $box_rescan_tag->term_id) && !in_array($box_ticket_status, $rescan_validate_status_id_arr) && ($folderdocinfofile_id_validation != 1) && ($folderdocinfofile_id_rescan != 1) && ($folderdocinfofile_id_object_key != NULL || $folderdocinfofile_id_object_key != '')) {
    echo '&nbsp;&nbsp;&nbsp;<button class="btn" id="next_validate" onclick="executeValidation(\'' . $folderdocinfofile_id_current . '\');getPaging(' . $pages[$next] . ');switchInfo(\'' . $folderdocinfofile_id_next . '\');switchFile(\'' . $folderdocinfofile_id_next . '\');alert(\'' . $folderdocinfofile_id_current . ' has been validated\');location.reload();">Validate <i class="fas fa-check" aria-hidden="true" title="Validate Current"></i><span class="sr-only">Validate Current</span></button>';
    //Rescan button
    echo '&nbsp;&nbsp;&nbsp;<button class="btn" id="next_rescan" class="btn" onclick="executeRescan(\'' . $folderdocinfofile_id_current . '\');getPaging(' . $pages[$next] . ');switchInfo(\'' . $folderdocinfofile_id_next . '\');switchFile(\'' . $folderdocinfofile_id_next . '\');alert(\'' . $folderdocinfofile_id_current . ' has been set to Re-Scan\');location.reload();">Re-Scan <i class="far fa-times-circle" aria-hidden="true" title="Re-Scan"></i><span class="sr-only">Re-Scan Current</span></button>';   
}
    
}

} else {
        echo "<strong>Check that the box is in validation status.</strong><br />No folder/files available for validation in box: ".$box_id;
        echo'<script type="text/javascript" id="runscript">switchFile("");switchInfo("");</script>';
    }
//echo 'Begin:: '.$_POST['postvarsbegin'] .'<br />';
//echo 'End:: '.$_POST['postvarsend'] .'<br />';

//echo $_POST['postvarspid'];
}

?>