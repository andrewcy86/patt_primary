var theFile = {};
var batchFiles = {
	file_list: []
};
var metaData = {
	file_list: [],
};




var peaches = [];
peaches.push(1);
peaches.push(2);
peaches.push(3);
console.log( peaches );
console.log( peaches.length );

//
// Setup
//

// Column Names
let name_file_name = 'File Name';
let name_disp_sched = 'Disposition Schedule & Item Number';
let name_title = 'Title';
let name_desc = 'Description';
let name_creator = 'Creator';
let name_creation_date = 'Creation Date';
let name_rights = 'Rights';
let name_coverage = 'Coverage';
let name_relation = 'Relation';

// Selectors
batch_uploader_status_div_sel = '#batch_uploader_status_div';
metadata_file_num_sel = '#metadata_file_num';
batchfiles_file_num_sel = '#batchfiles_file_num';
file_diff_sel = '#file_diff';

// Note: col_names Must be in exact order as on the spreadsheet.
var spreadsheetMetaData = {
	file_list: [],
	col_names: [
		name_file_name,
		name_disp_sched,
		name_title,
		name_desc,
		name_creator,
		name_creation_date,
		name_rights,
		name_coverage,
		name_relation
	]
};

let index_file_name = spreadsheetMetaData.col_names.indexOf( name_file_name );
let index_disp_sched = spreadsheetMetaData.col_names.indexOf( name_disp_sched );
let index_title = spreadsheetMetaData.col_names.indexOf( name_title );
let index_description = spreadsheetMetaData.col_names.indexOf( name_desc );
let index_creator = spreadsheetMetaData.col_names.indexOf( name_creator );
let index_creation_date = spreadsheetMetaData.col_names.indexOf( name_creation_date );
let index_rights = spreadsheetMetaData.col_names.indexOf( name_rights );
let index_coverage = spreadsheetMetaData.col_names.indexOf( name_coverage );
let index_relation = spreadsheetMetaData.col_names.indexOf( name_relation );


// D E B U G - START
//console.log({index_file_name:index_file_name, index_disp_sched:index_disp_sched, index_title:index_title, index_description:index_description, index_creator:index_creator, index_creation_date:index_creation_date, index_rights:index_rights, index_coverage:index_coverage, index_relation:index_relation });
// D E B U G - END

jQuery(document).ready(function(){
    //Dropzone.autoDiscover = false;
    //Dropzone.autoDiscover = false;
    console.log('batch uploader js ready');
    
    //jQuery(document).ajaxComplete(function (event, xhr, settings) {
	    
	    //console.log({settings:settings});
		 
		//if ( 'action=wpsc_tickets&setting_action=create_ticket' == settings.data ) {
			
            var dropzoneOptions_meta_data = {
                //url: "notreal.php",
                url: "#",
                //autoProcessQueue: false,
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
                    this.on( "maxfilesexceeded", function() {
                        if ( this.files[1]!=null ){
                            this.removeFile( this.files[0] );
                        }
                    });
                    this.on( "error", function ( file ) {
                        if (!file.accepted) this.removeFile(file);
                    });
                    // working
                    this.on( "addedfile", function ( file ) {
                        console.log( '------ addedfile.' );
                        console.log( file );
                        jQuery(".dz-remove").attr('onclick', "metadata_remove_link_clicked()");
                    });
                    // working
                    this.on( "removedfile", function ( file ) {
                        console.log( '------ removedfile.' );
                        console.log( file );
                    });
                    // NOT working
                    this.on("uploadprogress", function ( file, progress, bytes ) {
                        console.log( '------ uploadprogress.' );
                        console.log( progress );
                    });
                    // NOT working
                    this.on("completed", function ( progress ) {
                        console.log( '------ completed.' );
                        console.log( progress );
                    });
                },
                // NOT WIRKING
                uploadprogress: function( file, progress, bytes ) {
			        console.log( 'progress' );
			        console.log( progress );
			        //document.querySelector("#total-progress .progress-bar").style.width = progress + "%";
			    }, 
			    // NOT working
			    completed: function( progress ) {
			        //$(file.previewElement).find('#total-progress').css('display','none');
			        console.log( 'completed' );
			        console.log( progress );
			    }, 
			    // working
			    removedfile: function( file ) {
			        //removeFile(file.name);
			        //$(file.previewElement).remove();
			        console.log( 'removed' );
			        console.log( file );
			    },
			    // NOT working
			    thumbnail: function( file ) {
			        //removeFile(file.name);
			        //$(file.previewElement).remove();
			        console.log( 'thumbnail' );
			        console.log( file );
			    }

            };
            var uploader = document.querySelector('#dzBatchListUpload');            
            var newDropzone = new Dropzone(uploader, dropzoneOptions_meta_data);
            
            
            // Update the total progress bar
			newDropzone.on( "totaluploadprogress", function( progress ) { 
			//newDropzone.on( "uploadprogress", function( progress ) { 
				//document.querySelector("#total-progress .progress-bar").style.width = progress + "%";
				console.log( 'progress' );
				console.log( progress );
			});
            
            
            // Batch File Upload Area
            var dropzoneOptions_multi_files = {
                //url: "test2.php",
                url: "/", // new
                method: 'put', // new
                sending (file, xhr) { // new
				    let _send = xhr.send
				    xhr.send = () => {
				      _send.call(xhr, file)
				    }
				},
				header: '', // new
                uploadMultiple: false, // new // previously: true
                autoProcessQueue: false,
                addRemoveLinks: true,
                parallelUploads:1, // previously 10
                maxFiles: 100,
                acceptedFiles: '.xlsx, .xlsm, .pdf',
                accept: function (file, done) {
                    console.log({file:file, done:done});
                    
                    if( batchFiles.file_list.indexOf( file.name ) >= 0 ) {
	                    
	                    console.log('Error: duplicate file name');
	                    
	                    file._removeLink.click();
	                    
                    } else {
	                    
	                    if( metaData.file_list.includes( file.name ) ) {
							
		                    batchFiles.file_list.push( file.name );
		                    console.log( '--- before S3 upload ---' );
		                    
/*
		                    getS3Upload( file )
								.then( ( url ) => {
									file.uploadURL = s3upload.uploadXHR[0].responseURL;
									done();
									// Manually process each file
									setTimeout( () => vm.dropzone.processFile( file ) );
								})
								.catch( ( err ) => {
									done( 'Failed to get an S3 signed upload URL' , err );
								});
*/
		                    
/*
		                    lambda.getSignedURL(file)
								.then((url) => {
									file.uploadURL = url
									done()
									// Manually process each file
									setTimeout(() => vm.dropzone.processFile(file))
								})
								.catch((err) => {
									done('Failed to get an S3 signed upload URL', err)
								})
*/
		                    let newid = file.name;
							jQuery("#dzBatchUpload_files .dz-preview:last-child").attr('id', "document-" + newid );
		                    
		                    upload( file );
		                    
		                    updateFileDashboard();
		                    
	                    } else {
		                    console.log( 'Error: file name not in metadata list' );
		                    file._removeLink.click();
	                    }
	                    
	                    
	                    
	                    
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
                    // working
                    this.on( "addedfile", function ( file ) {
                        console.log( '------ addedfile. Batch.' );
                        console.log( file );
                    });
                    // working
                    this.on( "removedfile", function ( file ) {
                        console.log( '------ removedfile. BATCH' );
                        console.log( file );
                        batch_uploader_remove_link_clicked( file );
                    });
                    // unknown
                    this.on( "success", function ( file, response ) {
                        console.log( '------ success. BATCH' );
                        console.log( {file:file, response:response} );
                        file.serverId = response.id;
						//jQuery(".dz-preview:last-child").attr('id', "document-" + file.serverId);
                    });

                }
                            };
            var uploader_files = document.querySelector('#dzBatchUpload_files');
            var newDropzone_files = new Dropzone(uploader_files, dropzoneOptions_multi_files);      

        //} 
   // });
   
   
	
}); // ready


/*
newDropzone_files.on("addedfile", function(file) {
		file.previewElement.addEventListener("click", function() { 
			myDropzone.removeFile(file); 
			console.log( 'NEW REMOVE' );
		});
	});
*/


//
// checks file to ensure that it isn't a duplicate and if not, adds
//

/*
function addToBatchList( file ) { // NOT USED?
	
	
	if( batchFiles.file_list.indexOf( file.name ) >= 0 ) {

        console.log('Error: duplicate file name');        
        file._removeLink.click();
        
    } else {
        
        //batchFiles.file_list.push( file.name );
        batchFiles.file_list.push( file );
        
        console.log( '--- before S3 upload ---' );
        //upload( file );
        
    }


}
*/

// returns an array of all the file names in the batchFiles object
function getArrayOfFileNames() {
	
	
	
}

// Starts the upload process for files in batchFiles
function startS3Upload() {
	
	
}

function batch_uploader_remove_link_clicked( file ) {	
	console.log( 'batch remove' );
	console.log( file.name );
	
	
	
	const index = batchFiles.file_list.indexOf( file.name );
	console.log({index:index});
	console.log( batchFiles.file_list );
	if ( index > -1 ) {
		batchFiles.file_list.splice( index, 1 );
		console.log( batchFiles.file_list );
	}
	
	updateFileDashboard();

}

function metadata_remove_link_clicked() {	
	reset_page();
}

// reset / reload page
function reset_page() {
	
	console.log( 'reset ----------------------' );
	//jQuery( '#batch_list_upload_cr' ).val(0);
	//jQuery( '#processing_notification_div' ).addClass( 'yellow_update' );
	//jQuery( '#processing_notification' ).text( '' );
	
	//wpsc_get_create_ticket();
	window.location.reload();
}

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
                
                // Show the DataTable
                jQuery( '#batchlistdatatable' ).show();
                //jQuery( '#spreadsheet_dt_row' ).append( '<th>Pods</th>' );
                
                spreadsheetMetaData.col_names.forEach( function( colName ) {
	            	jQuery( '#spreadsheet_dt_row' ).append( '<th>' + colName + '</th>' );
	            });
                
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
	                    
	                    
	                    // Get the real arrayLength (previous arrayLength contains a bunch of blanks)
	                    let col1 = parsedData.map( function( value, index ) { return value[0]; });
	                    col1[0] = 'x';  // masks the first undefined, while keeping the index the same. 
	                    console.log({ col1:col1 });
	                    
	                    col1_null = col1.indexOf( null );
	                    col1_undef = col1.indexOf( undefined );
	                    col1_blank = col1.indexOf( '' );
	                    
	                    // if array of spreadsheet is NOT the exact length (i.e. blank rows), then use updated length
	                    if( col1_undef != -1 ) {
	                    	arrayLength = col1_undef + 2;
	                    } 
	                    console.log({ col1_null:col1_null, col1_undef:col1_undef, col1_blank:col1_blank });
	                    
	                    // removes asterisks from upload file headers
	                    parsedData[1].forEach( function( item, i ) {
		                    parsedData[1][i] = item.replaceAll( '*', '' );
	                    });
		                
	                    if (parsedData[1][0] !== undefined && parsedData[1][1] !== undefined) {
	                        
	                        jQuery( '#processing_div' ).show();
	                        //jQuery( '#processing_notification_div' ).show();
		                    jQuery( '#processing_notification_div' ).addClass( 'yellow_update' );
		                    jQuery( '#processing_notification' ).text( 'Processing Row #' );
		                    //jQuery('#boxinfodatatable_wrapper').hide();                             
	                        //
	                        // Validation
	                        //
	                        
	                        
	                        
	                        
							
							// Regex
	                        let date_time_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4} ([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])$/;
	                        let date_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4}$/;
							
														
							// Column Indexes for Validation checks
							
							
							
	                        /* Loop through spreadsheet data */
	                        //for (var count = 2; count < arrayLength; count++) {
		                        
		                    //
	                        // Loop through spreadsheet data    // OLD // for (var count = 1; count < arrayLength; count++) {		                            
	                        //
	                        
	                        
		                    
		                    
	                        let isBlank = false;
	                        let count = 2;
					        var processLoopID = setInterval(function() {
							    if ( count < arrayLength ) {
							        jQuery('#processing_notification').text( 'Processing Row #' + count );    
									
									//
									// VALIDATION
									//
									
									// Find the last line of filled out data
									if(
										count > 1 && 
											(
												( parsedData[count][0] == null && 
												  parsedData[count][1] == null
												) 
												||
												( parsedData[count][0] == undefined && 
												  parsedData[count][1] == undefined
												)
												||
												( parsedData[count][0] == '' && 
												  parsedData[count][1] == ''
												) 
											)
											
									) {
		                                console.log( 'SKIP');
		                                isBlank = true;
		                                //continue;
		                            } else {
			                            isBlank = false;
		                            }
		                            
		                            
		                            //
									// DATA
									//
									
									// MetaData file list. Used to compare against batchFiles.file_list
									if( !isBlank ) {
										metaData.file_list.push( parsedData[count][index_file_name] );
										
									}

		                            
		                            // if row is not blank, then process it. Once the first blank is hit, then processing is finished.
		                            if( !isBlank ) {    
								
								
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
			                            if( 
			                            	parsedData[count] !== undefined && 
		                            		parsedData[count].length > 0 && 
		                            		parsedData[count][0].toString().trim() != "Box"
			                            ) {
			
			                                
			                                datatable.row.add([
			                                    parsedData[count][0],
			                                    parsedData[count][1],
			                                    parsedData[count][2],
			                                    parsedData[count][3],
			                                    parsedData[count][4],
			                                    parsedData[count][5],
			                                    parsedData[count][6],
			                                    parsedData[count][7],
			                                    parsedData[count][8]
			                                ]).draw().node();
			                            }
			                        }
			                        
			                        
			                    } else {
						        	clearInterval( processLoopID );
						        	
						        	
						        	// sets order for data table                    
									datatable.column( '0:visible' ).order( 'asc' ).draw();

									afterInitialProcessing();
									
								}
						    count++ 
						}, 1 ); //end of setInterval, 1ms    
	                        
	                        
	                        
	                        
	                        
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
                document.getElementById("boxdisplaydiv_files").style.display = "block";
                
                
                //jQuery('#batch-uploader-dropzone').show();
				
				
				
				
				
				
				
                //End of new Datatable code
                
                // D E B U G
                console.log( metaData.file_list );

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
        
        


        

    }

}

// Updates displayed data
// Fires after JS finishes processing xls for datatable
function afterInitialProcessing() {
	
	jQuery('#batch-uploader-dropzone').show();
	jQuery( '#meta_data_wrapper' ).show();
	
	jQuery( '#processing_notification_div' ).removeClass( 'yellow_update' );
	jQuery( '#processing_notification_div' ).addClass( 'green_update' );
	jQuery( '#processing_notification' ).text( 'Processing Complete.' );
	jQuery( '#processing_notification_persistent' ).hide();
	
	//jQuery('#wpsc_create_ticket_submit').removeAttr('disabled');
	
	// Comparison Div
	jQuery( batch_uploader_status_div_sel ).show();
	jQuery( metadata_file_num_sel ).text( metaData.file_list.length );
	jQuery( file_diff_sel ).text( metaData.file_list.length );
	jQuery( batchfiles_file_num_sel ).text( 0 );
}

// Updates
// fires after a batch file is added
function updateFileDashboard() {
	
	const diff_red_threshold = 3;
	let batchLength = batchFiles.file_list.length;
	let metaLength = metaData.file_list.length;
	let diff = metaLength - batchLength;
	
	jQuery( batchfiles_file_num_sel ).text( batchFiles.file_list.length );
	jQuery( file_diff_sel ).text( diff );
	
	if( diff == 0 ) {
		jQuery( file_diff_sel ).removeClass( 'alert-warning' );
		jQuery( file_diff_sel ).addClass( 'alert-success' );
	} else if( diff < diff_red_threshold ) {
		jQuery( file_diff_sel ).removeClass( 'alert-danger' );
		jQuery( file_diff_sel ).addClass( 'alert-warning' );
	} else if( diff >= diff_red_threshold ) {
		jQuery( file_diff_sel ).removeClass( 'alert-warning' );
		jQuery( file_diff_sel ).addClass( 'alert-danger' );
	}
	
	if( batchLength == metaLength ) {
		jQuery( batchfiles_file_num_sel ).removeClass( 'alert-warning' );
		jQuery( batchfiles_file_num_sel ).addClass( 'alert-success' );
	} else {
		jQuery( batchfiles_file_num_sel ).removeClass( 'alert-success' );
		jQuery( batchfiles_file_num_sel ).addClass( 'alert-warning' );
	}
	
}

