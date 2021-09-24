<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if(
!empty($_POST['postvarsbegin']) && !empty($_POST['postvarsend'])
){

    $begin = trim($_POST['postvarsbegin']);
    $end = trim($_POST['postvarsend']);
    $pages = array();
    //echo "BEGIN " . $begin . "<br/>";
    //echo "END " . $end . "<br/>";
    
    $get_begin_dbid = $wpdb->get_row("SELECT id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE folderdocinfofile_id = '".$begin."'");
    $begin_dbid = $get_begin_dbid->id;
    
    $get_end_dbid = $wpdb->get_row("SELECT id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE folderdocinfofile_id = '".$end."'");
    $end_dbid = $get_end_dbid->id;
    
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

    $get_folderdocid_initial= $wpdb->get_row("SELECT count(id) as count, folderdocinfofile_id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE id = '".$postvarspid."'");
    
    $folderdocinfofile_id_initial = $get_folderdocid_initial->folderdocinfofile_id;  
    $folderdocinfofile_id_count = $get_folderdocid_initial->count;  

if($folderdocinfofile_id_count == 0) {
echo'<script type="text/javascript" id="runscript">switchFile("");switchInfo("");</script>';
} else {  
echo'<script type="text/javascript" id="runscript">
switchFile("LDF_1_2_6_ldf_09019588800598d8");switchInfo(\'' . $folderdocinfofile_id_initial . '\');
</script>';
}

} else {
$postvarspid = $_POST['postvarspid'];
}


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
    
    echo '<a href="#" onclick="getPaging(' . $pages[$prev] . ');switchInfo(\'' . $folderdocinfofile_id_prev . '\');switchFile(\'LDF_1_2_6_ldf_09019588800598d8\');">prev</a> | ';
} else {
    echo 'prev | ';
}

// next page
$next = $key + 1;
if ($next >= 0 && $next < count($pages)) {
    $get_folderdocid_next = $wpdb->get_row("SELECT folderdocinfofile_id
    FROM " . $wpdb->prefix . "wpsc_epa_folderdocinfo_files
    WHERE id = '".$pages[$next]."'");
    
    $folderdocinfofile_id_next = $get_folderdocid_next->folderdocinfofile_id;    
    
    echo '<a href="#" onclick="getPaging(' . $pages[$next] . ');switchInfo(\'' . $folderdocinfofile_id_next . '\');switchFile(\'LDF_1_2_6_ldf_09019588800598d8\');">next</a> | <a href="#" onclick="executeValidation(\'' . $folderdocinfofile_id_current . '\');getPaging(' . $pages[$next] . ');switchInfo(\'' . $folderdocinfofile_id_next . '\');switchFile(\'LDF_1_2_6_ldf_09019588800598d8\');">[next + validate]</a>';
} else {
    echo 'next | <a href="#" onclick="executeValidation(\'' . $folderdocinfofile_id_current . '\');getPaging(' . $pages[$next] . ');switchInfo(\'' . $folderdocinfofile_id_next . '\');switchFile(\'LDF_1_2_6_ldf_09019588800598d8\');alert(\'' . $folderdocinfofile_id_current . ' has been validated\');location.reload();">[Validate Current]</a>';
}

} else {
        echo "Invalid folder/file ID or range of IDs.";
        echo'<script type="text/javascript" id="runscript">switchFile("");switchInfo("");</script>';
    }
//echo 'Begin:: '.$_POST['postvarsbegin'] .'<br />';
//echo 'End:: '.$_POST['postvarsend'] .'<br />';

//echo $_POST['postvarspid'];
}

?>