 <?php  
 include 'db_connection.php';
 $conn = OpenCon();
 
 //all columns from the record_schedule table
 $query_rs ="SELECT DISTINCT(Schedule_Number) AS Schedule_Number, Schedule_Title, Function_Code, Function_Title, Program, Applicability, NARA_Disposal_Authority_Record_Schedule_Level, Schedule_Description, Disposition_Instructions, Guidance, Status, Custodians, Reasons_For_Disposition, Related_Schedules, Entry_Date, Previous_NARA_Disposal_Authority, EPA_Approval, NARA_Approval, Revised_Date, Item_Number
 FROM " . $wpdb->prefix . "epa_record_schedule 
 WHERE Reserved_Flag = 0 AND id != '-99999' AND Schedule_Number = ". $_GET["rs"] . " LIMIT 1";  
 $result_main = mysqli_query($conn, $query_rs);
 
 //$query_da ="SELECT NARA_Disposal_Authority_Record_Schedule_Level FROM " . $wpdb->prefix . "epa_record_schedule WHERE Schedule_Number = ". $_GET["rs"];  
 //$result_da = mysqli_query($conn, $query_da);  
 
 $query_di ="SELECT Disposition_Summary FROM " . $wpdb->prefix . "epa_record_schedule WHERE Schedule_Number = ". $_GET["rs"];  
 $result_di = mysqli_query($conn, $query_di);  
 ?>  

                
 <!DOCTYPE html>  
 <html>  
      <head>  
           <title>Record Schedule Details</title>  
      </head>  
      <body>
      
      <?php 
      echo($query);
while($row_main = mysqli_fetch_array($result_main))  
  {  
   echo '
   <h2>EPA Records Schedule '.$row_main["Schedule_Number"].'</h2>
   <strong>Status: </strong>'.$row_main["Status"].', '. Patt_Custom_Func::get_converted_date($row_main["Revised_Date"]) . '<br />
   <strong>Title: </strong>'.$row_main["Schedule_Title"].'<br />  
   <strong>Program: </strong>'.$row_main["Program"].'<br />   
   <strong>Applicability: </strong>'.$row_main["Applicability"].'<br /> 
   <strong>Function: </strong>'.$row_main["Function_Code"].' - '.$row_main["Function_Title"].'
   <p><strong>NARA Disposal Authority:</strong></p>'.$row_main["NARA_Disposal_Authority_Record_Schedule_Level"].'
   ';
   echo '<p><strong>Description:</strong></p>'.$row_main["Schedule_Description"]. '
   <strong>Disposition Instructions:</strong>';
   
   while($disposition_summary = mysqli_fetch_array($result_di)) {
        echo $disposition_summary["Disposition_Summary"];
    }
 echo '
   <strong>Gudiance:</strong><br />'.$row_main["Guidance"].'</br>
   <p><strong>Reasons for Disposition: </strong></p>'.$row_main["Reasons_For_Disposition"].'</br>
   <strong>Custodians: </strong>'.$row_main["Custodians"].'</br>
   <strong>Related Schedules: </strong>'.$row_main["Related_Schedules"].'</br>
   <strong>Previous NARA Disposal Authority: </strong>'.$row_main["Previous_NARA_Disposal_Authority"].'</br>
   <strong>Entry: </strong>'.Patt_Custom_Func::get_converted_date($row_main["Entry_Date"]).'</br>
   <strong>EPA Approval: </strong>'.$row_main["EPA_Approval"].'</br>
   <strong>NARA Approval: </strong>'.$row_main["NARA_Approval"].'</br>
   ';
}


      ?>
      </body>  
 </html>