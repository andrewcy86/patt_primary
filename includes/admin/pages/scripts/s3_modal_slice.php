<?php	
	$new_path = WPPATT_PLUGIN_URL . 'includes/admin/pages/scripts/s3upload.js';
	
	ob_start();
?>



<style>

canvas {
    padding-left: 0;
    padding-right: 0;
    margin-left: auto;
    margin-right: auto;
    display: block;
    width: 800px;
}

.fileinput-button {
  position: relative;
  overflow: hidden;
  display: inline-block;
}
.fileinput-button input {
  position: absolute;
  top: 0;
  right: 0;
  margin: 0;
  opacity: 0;
  -ms-filter: 'alpha(opacity=0)';
  font-size: 200px !important;
  direction: ltr;
  cursor: pointer;
}

.progress {
	position:relative;
	height: 20px;
	margin-bottom: 20px;
	overflow: hidden;
	background-color: #f5f5f5;
	border-radius: 4px;
	-webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
	box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
}
.progress-number {
	position:absolute;
	left:50%;
	z-index:5;
}
.progress-bar {
	float: left;
	width: 0;
	height: 100%;
	font-size: 12px;
	line-height: 20px;
	color: #fff;
	text-align: center;
	background-color: #d0e2f2;
	-webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
	box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
	-webkit-transition: width .6s ease;
	-o-transition: width .6s ease;
	transition: width .6s ease;
}
#uploadForm.is-dragover {
  background-color: #F7F7F7;
}
.button:focus{color:#fff;background-color:#449d44;border-color:#255625}
.button:hover{color:#fff;background-color:#449d44;border-color:#398439}
.button {
	color: #fff;
	background-color: #5cb85c;
	border-color: #4cae4c;

	display: inline-block;
	padding: 6px 12px;
	margin-bottom: 0;
	font-size: 14px;
	font-weight: 400;
	line-height: 1.42857143;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	-ms-touch-action: manipulation;
	touch-action: manipulation;
	cursor: pointer;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
	background-image: none;
	border: 1px solid transparent;
	border-radius: 4px;
}
/* Fixes for IE < 8 */
@media screen\9 {
  .fileinput-button input {
    filter: alpha(opacity=0);
    font-size: 100%;
    height: 100%;
  }
}
#result {
	border:1px solid gray;
	margin:5px;
	padding:10px;
}

#uploadForm {
	padding: 4px;
	padding-top: 10px;
	padding-bottom: 10px;
	border: 1px dotted black;
	border-radius: 4px;
}

#drop-text-old {
	padding:15px;
	position:relative;
	top:-10px;
	color:gray;
	float:right;
	margin: auto;
}

#drop-text {
	
	position:relative;
	left: 40%;
	color: #000000;
}

#upload_alert_status_modal {
	margin-top: 10px;
}

#upload-alert {
	margin-bottom: 0px !important;
}

.fileinput-button > #fileInput:focus {
  outline: 5px auto -webkit-focus-ring-color;
}

/*
.fileinput-button > #fileInput:focus {
  outline: 5px auto -webkit-focus-ring-color;
}
*/

</style>


<hr>
<div class="">
<!-- 	<h2>File Uploader</h2> -->
<!-- 	<script src="//ajax.googleapis.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script> -->
	<script src="<?php echo $new_path ?>"></script>
	<div class="form-group form-group-lg">
<!-- 		<p>Upload digitized files for transfer to ECMS here.</p> -->
		
		<label class="col-sm-2 control-label">Upload File<span style="color:red;">*</span> </label>
		<div class="col-sm-10">
			<fieldset id='uploadForm' >
			<form >
			   
<!--
		    <span class="button fileinput-button" >
	        <i class="fas fa-plus"></i>
	        <span>Select File...</span>
	        <input id="fileInput" type="file" name="file" accept="*"  >
		    </span>
-->
		    
		    <label id="upload_button_label" for="fileInput" class="button fileinput-button" tabindex="0">
                <i class="fas fa-plus"></i>
                <span role="button" aria-controls="filename">Select File...</span>
            </label>
            <input id="fileInput" type="file" name="file" accept="*" style="display:none" tabindex="-1" /> 
		    			    
			 	<span class="button cancel-button" style='display:none;'>
			        <i class="fas fa-minus"></i>
			        <a href='#' id='cancel' style="color:inherit;text-decoration: none;">Cancel</a>
			    </span>

			    <!--<br><br>-->
			    <input type="hidden" id="uploadId" name="uploadId" size="150" value="">
			    </form>
			    <br>
			    <div id="progress" class="progress">
			        <div class="progress-bar progress-bar-success"></div>
			        <div class="progress-number"></div>
			    </div>
			    <label id="drop-text" for="fileInput" class="text-center" >Drop a file here to upload</label>
			    <!--<div>
			        <canvas id="progressGrid" width=800 height=50></canvas>
			    </div>-->
			</fieldset>
<!-- 			<div id='result'>  -->
			<div id="upload_alert_status_modal">
			
			</div>
<!-- 			<input type="text" style="border: none;background: transparent;" id="objectkey" name="objectkey" size="150" value=""> -->
<!-- 			<input type="text" style="border: none;background: transparent;" id="objectlocation" name="objectlocation" size="150" value=""> -->
				<input type="hidden" style="border: none;background: transparent;" id="objectkey" name="objectkey" size="150" value="">
				<input type="hidden" style="border: none;background: transparent;" id="objectlocation" name="objectlocation" size="150" value="">
          		<input type="hidden" style="border: none;background: transparent;" id="document_id" name="document_id" size="150" value="<?php echo $GLOBALS["id"]; ?>">
		</div>
	</div>
</div>
<hr>

<script>
console.log('S3 Modal Slice page loaded');
/*
if (typeof jQuery != 'undefined') {  
    // jQuery is loaded => print the version
    alert(jQuery.fn.jquery);
}
*/

var s3upload=null;
function upload(file) {
    if (!(window.File && window.FileReader && window.FileList && window.Blob && window.Blob.prototype.slice)) {
        alert("You are using an unsupported browser. Please update your browser.");
        return;
    }
    jQuery(".fileinput-button").toggle();
    jQuery(".cancel-button").toggle();
//     $("#result").text(""); upload_alert_status_modal
    jQuery("#upload_alert_status_modal").text(""); 
    jQuery("#objectkey").val("");
    jQuery("#objectlocation").val("");
  	jQuery("#document_id").val();
    jQuery('#progress .progress-bar').css('width',"0px");
    jQuery('#progress .progress-number').text("");
  
  	var doc_id_filename_ext = jQuery("#document_id").val() + "_" + file.name;

    s3upload = new S3MultiUpload(file);
  	//console.log(doc_id_filename_ext);
    console.log('about to upload');
    //console.log({s3upload:s3upload.fileInfo.size});
    
    
    jQuery('#alert-alert').remove();
    
    // If uploading a zero byte file, disallow and show warning. 
    if( s3upload.fileInfo.size == 0 ) {
	    console.log('zero clear it');
	    jQuery(".cancel-button").toggle();
	    jQuery(".fileinput-button").toggle();
	    set_alert( 'danger', 'Zero Byte Files are not allowed. Please try again.' );
	    
	    return;
    }
    
    s3upload.onServerError = function(command, jqXHR, textStatus, errorThrown) {
//         $("#result").text("Upload failed with server error.");
		set_upload_notification( 'danger', 'Upload failed. Server error.');

    };
    s3upload.onS3UploadError = function(xhr) {
//         $("#result").text("Upload to S3 failed.");
        set_upload_notification( 'danger', 'Upload to S3 failed.');
    };
    s3upload.onProgressChanged = function(uploadedSize, totalSize, speed, partsInProgress, partsCompleted) {
        var progress = parseInt(uploadedSize / totalSize * 100, 10);
        jQuery('#progress .progress-bar').css(
            'width',
            progress + '%'
        );
        jQuery(".progress-number").html(getReadableFileSizeString(uploadedSize)+" / "+getReadableFileSizeString(totalSize)
            + " <span style='font-size:smaller;color:gray;'>("
            +uploadedSize+" / "+totalSize
            +" at "
            +getReadableFileSizeString(speed)+"ps"
            +")</span>").css({'margin-left' : -jQuery('.progress-number').width()/2});


        /*var ctx = document.getElementById('progressGrid').getContext('2d'); 
        for (var partnum = 1; partnum < partsInProgress.length; partnum++) {
            var i = partnum - 1
            if (partsInProgress[partnum]) {
                ctx.fillStyle = "rgb(200,200,0)";
            } else if (partsCompleted[partnum]) {
                ctx.fillStyle = "rgb(0,200,0)";
            } else {
                ctx.fillStyle = "rgb(200,200,200)";
            }
            ctx.beginPath();
            ctx.rect ((i%160)*5, Math.floor(i/160)*5, 4, 4);
            ctx.fill();
            ctx.closePath();
        }*/

    };
    s3upload.onPrepareCompleted = function() {
//          $("#result").text("Uploading...");
        	set_upload_notification( 'warning', 'Uploading...');
        document.getElementById('uploadId').readOnly=true
    }
    s3upload.onUploadCompleted = function() {
//         $("#result").text("Upload successful.");
        set_upload_notification( 'success', 'Upload successful.');
        jQuery("#wppatt-mdocs-save-doc-btn").toggle();
        jQuery(".fileinput-button").toggle();
        jQuery(".cancel-button").toggle();
        jQuery("#uploadId").val("");
        document.getElementById('uploadId').readOnly=false
    };
//     $("#result").text("Preparing upload...");
    set_upload_notification( 'warning', 'Preparing upload...');

    var uploadId = document.getElementById("uploadId").value
    if (uploadId === "") {
        s3upload.createMultipartUpload();
    } else {
        s3upload.resumeMultipartUpload(uploadId);
    }
    
}

function getReadableFileSizeString(fileSizeInBytes) {
    var i = -1;
    var byteUnits = [' KB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
    do {
        fileSizeInBytes = fileSizeInBytes / 1024;
        i++;
    } while (fileSizeInBytes > 1024);

    return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
}

jQuery(function(){
    jQuery("#fileInput").change(function() {
        jQuery("#objectkey").val("");
        jQuery("#objectlocation").val("");
        upload(jQuery('#fileInput')[0].files[0]);
    });
    // Drag & drop support.
    jQuery("#uploadForm").on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
    }).on('dragover dragenter', function() {
        jQuery(this).addClass('is-dragover');
    }).on('dragleave dragend drop', function() {
        jQuery(this).removeClass('is-dragover');
    }).on('drop', function(e) {
        droppedFiles = e.originalEvent.dataTransfer.files;
        upload(droppedFiles[0]);
    });
    jQuery("#cancel").click(function() {
        s3upload.cancel();
        jQuery(".fileinput-button").toggle();
        jQuery(".cancel-button").toggle();
        jQuery("#uploadId").val("");
        jQuery("#objectkey").val("");
        jQuery("#objectlocation").val("");
        document.getElementById('uploadId').readOnly=false
    });
})

</script>
<?php
	
$the_uploader = ob_get_clean();
echo $the_uploader;
