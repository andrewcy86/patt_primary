var theFile = {};
var batchFiles = {
	file_list: []
};

jQuery(document).ready(function(){
    //Dropzone.autoDiscover = false;
    //Dropzone.autoDiscover = false;
    console.log('batch uploader js ready');
    
    //jQuery(document).ajaxComplete(function (event, xhr, settings) {
	    
	    //console.log({settings:settings});
		 
		//if ( 'action=wpsc_tickets&setting_action=create_ticket' == settings.data ) {
			
            var dropzoneOptions = {
                url: "test.php",
                autoProcessQueue: false,
                addRemoveLinks: true,
                uploadMultiple: false,
                maxFiles: 1,
                acceptedFiles: '.xlsx, .xlsm',
                accept: function (file, done) {
                    theFile.file = file;
                    console.log({theFile:theFile});
                    jQuery('#batch_list_upload_cr').val(1);
                    console.log('Noted the upload.');
                    batch_spreadsheet_new_upload('batch_list_attachment','spreadsheet_attachment', file);
                    
                },
                init: function () {
                    this.on("maxfilesexceeded", function() {
                        if (this.files[1]!=null){
                            this.removeFile(this.files[0]);
                        }
                    });
                    this.on("error", function (file) {
                        if (!file.accepted) this.removeFile(file);
                    });
                }
            };
            var uploader = document.querySelector('#dzBatchListUpload');            
            var newDropzone = new Dropzone(uploader, dropzoneOptions);
            
            
            
            var dropzoneOptions_multi_files = {
                url: "test2.php",
                autoProcessQueue: false,
                addRemoveLinks: true,
                uploadMultiple: true,
                parallelUploads:10,
                maxFiles: 100,
                acceptedFiles: '.xlsx, .xlsm, .pdf',
                accept: function (file, done) {
                    console.log({file:file, done:done});
                    
                    if( batchFiles.file_list.indexOf( file.name ) >= 0 ) {
	                    
	                    console.log('Error: duplicate file name');
	                    
	                    file._removeLink.click();
	                    
                    } else {
	                    
	                    batchFiles.file_list.push( file.name );
	                    
	                    console.log( '--- before S3 upload ---' );
	                    upload( file );
	                    
	                    
                    }
                    
                    
                    //batchFiles.file = file;	
                    console.log({batchFiles:batchFiles});
                    //jQuery('#batch_list_upload_files').val(1);
                    console.log('Batch the upload.');
                    //batch_spreadsheet_new_upload('batch_list_attachment','spreadsheet_attachment', file);
                    
                },
                init: function () {
                    this.on("maxfilesexceeded", function() {
                        if (this.files[1]!=null){
                            this.removeFile(this.files[0]);
                        }
                    });
                    this.on("error", function (file) {
                        if (!file.accepted) this.removeFile(file);
                    });
                }
            };
            var uploader_files = document.querySelector('#dzBatchUpload_files');
            var newDropzone_files = new Dropzone(uploader_files, dropzoneOptions_multi_files);      

        //} 
   // });
});


/* Removes data from the box datatable if there is any error */
function clearBoxTable() {
    var datatable = jQuery('#batchlistdatatable').DataTable();
    datatable.clear().draw();
}

/* Converts datatable data into json */
jQuery.fn.toJson = function () {
    try {
        if (!this.is('table')) {
            return;
        }

        var results = [],
        headings = [];

        var table = jQuery('#batchlistdatatable').DataTable();

        /* Fetch heading */
        this.find('thead tr th').each(function (index, value) {
            headings.push(jQuery(value).text());
        });

        /* Fetch data */
        table.rows().every(function (rowIdx, tableLoop, rowLoop) {
            var row = {};
            var data = this.data();
            headings.forEach(function (key, index) {
                var value = data[index];
                row[key] = value;
            });
            results.push(row);
        });

        return results;

    } catch (ex) {
        alert(ex);
    }
}

// Box list file validation on submit // No Submit button currently. 
jQuery(document).on('click', '#wpsc_create_ticket_submit', function() {
    
        

	
	
});


// Upload boxlist document, and create the data table
function batch_spreadsheet_new_upload(id, name, fileSS) {
	
	console.log('Batch upload');
	console.log({the_File:theFile.file});
	
    jQuery('#attachment_upload').unbind('change');

    jQuery.fn.dataTable.ext.errMode = 'none';
    var flag = false;
    var file = fileSS;
    //jQuery('#attachment_upload').val('');

    var file_name_split = file.name.split('.');
    var file_extension = file_name_split[file_name_split.length - 1];
    file_extension = file_extension.toLowerCase(); 

    var allowedExtensionSetting = ["xls", "xlsx", "xlsm"];
    if (!flag && (jQuery.inArray(file_extension, allowedExtensionSetting) <= -1)) {
        flag = true;
        alert('Attached file type not allowed!');
    }

    var current_filesize = file.size / 1000000;
    if (current_filesize > attachment_info['max_filesize'] ) {
        flag = true;
        alert('File size exceed allowed limit!');
    }

    //No file
    if( flag == true ) {
        jQuery('#batch_list_upload_cr').val(0);
        var _ref;
        return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
    }
	
	
	// If basic validation passes
    if ( ! flag ) {

        jQuery('.row.wpsp_spreadsheet').each(function (i, obj) {
            console.log({i:i, obj:obj});
            obj.remove();
        });

        // Progress bar
        var html_str = '<div class="row wpsp_spreadsheet">' +
                            '<div class="progress" style="float: none !important; width: unset !important;">' +
                                '<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">' + file.name + '</div>' +
                            '</div>' +
                            '<img onclick="attachment_cancel(this);clearBoxTable()" class="attachment_cancel" src="' + attachment_info['close_image'] + '" style="display:none;" />' +
                        '</div>';

        jQuery('#' + id).append(html_str);

        var attachment = jQuery('#' + id).find('.wpsp_spreadsheet').last();

        var data = new FormData();
        data.append('file', file);
        data.append('arr_name', name);
        data.append('action', 'wpsc_tickets');
        data.append('setting_action', 'upload_file');
        //data.append('nonce', jQuery('#wpsc_nonce').val().trim());

        // Read file and provide json response
        jQuery.ajax({
            type: 'post',
            url: wpsc_admin.ajax_url,
            data: data,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = Math.floor((evt.loaded / evt.total) * 100);
                        jQuery(attachment).find('.progress-bar').css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            processData: false,
            contentType: false,
            success: function (response) {
                console.log('batch list upload success');
                jQuery('#batchlistdatatable').show();
                var return_obj = JSON.parse(response);
                jQuery(attachment).find('.attachment_cancel').show();
                
                console.log({response:response});
                console.log({return_obj:return_obj});

                if (parseInt(return_obj.id) != 0) {
                    jQuery(attachment).append('<input type="hidden" name="' + name + '[]" value="' + return_obj.id + '">');
                    jQuery(attachment).find('.progress-bar').addClass('progress-bar-success');

                    //Start of new Datatable code
                    var datatable = jQuery('#batchlistdatatable').DataTable({
                        "autoWidth": true,
                        "scrollX": "100%",
                        "scrollXInner": "110%"
                    });

                    datatable.clear().draw();

                    var FR = new FileReader();
                    FR.onload = function (e) {

	                    var data = new Uint8Array(e.target.result);
	                    var workbook = XLSX.read(data, {
	                        type: 'array'
	                    });
	
	                    var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
	
	                    var result = XLSX.utils.sheet_to_json(firstSheet, {
	                        header: 1, raw: false
	                    });
	
	                    var arrayOfData = JSON.stringify(result);
	                    var parsedData = JSON.parse(arrayOfData);
	                    var arrayLength = Object.keys(parsedData).length;
	                    
	                    console.log({parsedData:parsedData});
	                    
	                    // removes asterisks from upload file headers
	                    parsedData[1].forEach( function( item, i ) {
		                    parsedData[1][i] = item.replaceAll( '*', '' );
	                    });
		                
	                    if (parsedData[1][0] !== undefined && parsedData[1][1] !== undefined) {
	                        	                             
	                        //
	                        // Validation
	                        //
							
							// Regex
	                        let date_time_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4} ([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])$/;
	                        let date_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4}$/;
							
														
							// Column Indexes for Validation checks
							let index_box = 0;
							let index_folder_id = 1;
							let index_title = 2;
							let index_desc_record = 3;
							let index_pcd = 4;
							let index_creation_date = 5;
							let index_creator = 6;
							let index_rec_type = 8;
							let index_rec_sched = 9;
							let index_site_name = 10;
							let index_close_date = 12; // 11
							let index_epa_contact = 13; // 12
							let index_access_rest = 14;
							let index_sp_access_rest = 15;
							let index_use_rest = 16;
							let index_sp_use_rest = 17;
							let index_source_type = 19;
							let index_source_dim = 20;
							let index_prog_office = 21; // 20
							let index_index_level = 22; //21 
							let index_ess_rec = 23; // 22
							let index_tags = 25; 
							//let index_last_col = 25;
							
							
	                        /* Loop through spreadsheet data */
	                        for (var count = 1; count < arrayLength; count++) {
								
								
								console.log( parsedData[count][0] );
	                            
	
	                            // Clear table if err
	                            if( flag == true ) {
	
	                                datatable.clear().draw();
	                                jQuery('#batch_list_upload_cr').val(0);
	
	                                jQuery('.row.wpsp_spreadsheet').each(function (i, obj) {
	                                    obj.remove();
	                                });
	
	                                var _ref;
	                                return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
	                            }
	
	                            // Add record to datatable if no error
	                            if (parsedData[count] !== undefined && parsedData[count].length > 0 && parsedData[count][0].toString().trim() != "Box") {
	
	                                
	
	                                datatable.row.add([
	                                    parsedData[count][0],
	                                    parsedData[count][1]
	                                ]).draw().node();
	                            }
	                        }
	                    } else {
	                        alert("Spreadsheet is not in the correct format! Please try again.");
	                        jQuery('.row.wpsp_spreadsheet').each(function (i, obj) {
	                            obj.remove();
	                        });
	                        flag = true;
	
	                        datatable.clear().draw();
	
	                        jQuery('#batch_list_upload_cr').val(0);
	                        var _ref;
	                        return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
	                    }
	                };
                FR.readAsArrayBuffer(file);
                document.getElementById("boxdisplaydiv").style.display = "block";
                
                //
                jQuery('#batch-uploader-dropzone').show();

                    //End of new Datatable code

                } else {
                    jQuery(attachment).find('.progress-bar').addClass('progress-bar-danger');
                    alert('Something went wrong. Please try again.');
                }
            }
        });
		
		
		
        // Upload excel
/*
        var form_data = new FormData();
        form_data.append('file', fileSS);
        form_data.append('action', 'move_excel_file');
        jQuery.ajax({
            url: wpsc_admin.ajax_url,
            type: 'post',
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                console.log("Excel uploaded successfully");
            },  
            error: function (response) {
                console.log("Excel did not upload successfully");
            }
        });
*/
        
        

        // Send excel file to S3
        console.log('before upload');
        

        
        //upload( fileSS );
        console.log('post upload');
        

    }

}


