 <?php 
 
 header("X-Frame-Options: allow-from https://work.epa.gov");
  
 include 'db_connection.php';
 $conn = OpenCon();

 $query ="SELECT DISTINCT(Schedule_Number) AS Schedule_Number, Schedule_Title, Function_Code, Program, Applicability, Revised_Date
 FROM " . $wpdb->prefix . "epa_record_schedule
 WHERE Reserved_Flag = 0 and id != '-99999'
 ORDER BY Schedule_Number ASC";  
 $result = mysqli_query($conn, $query);  
 ?>  

                
 <!DOCTYPE html>  
 <html>  
      <head>  
           <title>Record Schedule</title>  
           <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>  
           <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />  
           <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>  
           <script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>            
           <link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" />  
      </head>  
      <body>  
           <br /><br />  
           <div class="container">  
                <h3 align="center">Record Schedule</h3>  
                <br />  
                <div class="table-responsive">  
                     <table id="rschedule" class="table table-striped table-bordered stripe">  
                          <thead>  
                               <tr>  
                                <th>No.</th>
                                <th>Title</th>
                                <th>Function</th>
                                <th>Program</th>
                                <th>Applicability</th>
                                <th>Revised</th>
                               </tr>  
                          </thead>  
                          <?php  
                          while($row = mysqli_fetch_array($result))  
                          {  
                               echo '  
                               <tr>  
                                    <td><a href="details.php?rs='.$row["Schedule_Number"].'">'.$row["Schedule_Number"].'</a></td>  
                                    <td>'.$row["Schedule_Title"].'</td>  
                                    <td>'.$row["Function_Code"].'</td>  
                                    <td>'.$row["Program"].'</td>  
                                    <td>'.$row["Applicability"].'</td>
                                    <td>'.Patt_Custom_Func::get_converted_date($row["Revised_Date"]).'</td>
                               </tr>  
                               ';  
                          }  
                          CloseCon($conn);
                          ?>  
                     </table>  
                </div>  
           </div>
<script>  
 $(document).ready(function(){  
       $('#rschedule').dataTable( {
            "paging": false
        } ); 
 });  
 

 </script>  
      </body>  
 </html>