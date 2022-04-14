<title>EPA File Plans</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>                  
                       
<?php 
global $wpdb;

 include 'db_connection.php';
 $conn = OpenCon();

 $query ="SELECT office_code, office_acronym, parent_office_code
 FROM " . $wpdb->prefix . "wpsc_epa_program_office
 WHERE id != '-99999' AND parent_office_code != 'TDF00000'";
 $result = mysqli_query($conn, $query);  
                       
   $folders_arr = array();

   while($row = mysqli_fetch_array($result))  
    {  
      $parentid = $row['parent_office_code'];
      if($parentid == '00000000') $parentid = "#";

      $selected = false;$opened = false;
      $folders_arr[] = array(
         "id" => $row['office_code'],
         "parent" => $parentid,
         "text" => $row['office_acronym'],
         "state" => array("selected" => $selected,"opened"=>$opened) 
      );
   }



 $r1_query ="SELECT office_code
 FROM " . $wpdb->prefix . "wpsc_epa_program_office
 WHERE id != '-99999' AND organization = 1";
 $r1_result = mysqli_query($conn, $r1_query);  
 
$schedule_arr = array('0062c','0063a','0068a');

   while($r1_row = mysqli_fetch_array($r1_result))  
    {  
echo $r1_row['office_code'];
$update_file_plan = "UPDATE " . $wpdb->prefix . "wpsc_epa_file_plan SET record_schedules = '" .  json_encode($schedule_arr) . "' WHERE office_code = '" . $r1_row['office_code'] ."'";
                        if ($conn->query($update_file_plan) === true) {
                            echo 'Updated';
                            
                        } else {
                            echo 'Error';
                        }
     
   }

echo json_encode($schedule_arr);

CloseCon($conn);
   ?>

   <!-- Initialize jsTree -->
   <div id="folder_jstree"></div>

   <!-- Store folder list in JSON format -->
   <textarea id='txt_folderjsondata' style="display: none;"><?= json_encode($folders_arr) ?></textarea>

                       
<script>
$(document).ready(function(){
   var folder_jsondata = JSON.parse($('#txt_folderjsondata').val());

   $('#folder_jstree').jstree({ 'core' : {
      'data' : folder_jsondata,
      'multiple': false
   } });

});
</script>