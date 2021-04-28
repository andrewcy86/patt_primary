var theFile = {};

jQuery(document).ready(function(){
    Dropzone.autoDiscover = false;
    
    
    jQuery(document).ajaxComplete(function (event, xhr, settings) {
	   
		
		//jQuery('#wpsc_create_ticket_submit').attr( 'disabled', 'disabled' );
		//jQuery('#wpsc_create_ticket_submit').removeAttr('disabled');
		
		let check = jQuery( '#file_upload_cr' ).val();
		//console.log( 'check: ' + check );
		
		if( check == 1 ) {
			jQuery('#wpsc_create_ticket_submit').removeAttr('disabled');
		} else if( check == 0 ) {
			jQuery('#wpsc_create_ticket_submit').attr( 'disabled', 'disabled' );
		}
		
		
//         if ( 'action=wpsc_tickets&setting_action=create_ticket' == settings.data && superfund == 'no' ) {
	if ( 'action=wpsc_tickets&setting_action=create_ticket' == settings.data ) {
			
            var dropzoneOptions = {
                url: "test.php",
                autoProcessQueue: false,
                addRemoveLinks: true,
                uploadMultiple: false,
                maxFiles: 1,
                acceptedFiles: '.xlsx, .xlsm',
                accept: function (file, done) {
                    
                    console.log( 'ACCEPT' );
                    console.log( 'this.files.length: ' + this.files.length );
                    
/*
                    this.removeAllFiles()
			        this.addFile(file);
*/
                    
                    this.on("addedfile", function (file) {
			            if (this.files.length > 1) {
				            console.log( 'TOO LONG' );
			                this.removeAllFiles()
			                this.addFile(file);
			            }
			        });
                    
                    
                    theFile.file = file;
                    console.log({theFile:theFile});
                    //jQuery('#file_upload_cr').val(1);
                    wpsc_spreadsheet_new_upload('attach_16','spreadsheet_attachment', file);
                },
                init: function () {
                    //console.log( 'INIT' );
                    this.on("maxfilesexceeded", function(file) {
/*
                        if (this.files[1]!=null){
                            this.removeFile(this.files[0]);
                        }
*/
						console.log( 'maxfilesexceeded' );
                        this.removeAllFiles();
						this.addFile(file);
                    });
                    this.on("error", function (file) {
                        console.log( 'error for maxfilesexceeded' );
                        if (!file.accepted) this.removeFile(file);
                    });
                }
            };
            var uploader = document.querySelector('#dzBoxUpload');
            var newDropzone = new Dropzone(uploader, dropzoneOptions);      
            
            // SEMS Dropzone setup // no longer used. Dropzone unified.
/*
            dropzoneOptions = {
                url: "test.php",
                autoProcessQueue: false,
                addRemoveLinks: true,
                uploadMultiple: false,
                maxFiles: 1,
                acceptedFiles: '.xlsx',
                accept: function (file, done) {
                    jQuery('#file_upload_cr_SEMS').val(1);
                    wpsc_spreadsheet_new_upload_SEMS('attach_SEMS','spreadsheet_attachment', file);
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
            var uploader = document.querySelector('#dzBoxUploadSEMS');
            var newDropzone = new Dropzone(uploader, dropzoneOptions);
*/   
            
            
                  
        } 
    });
});


/* Removes data from the box datatable if there is any error */
function clearBoxTable() {
    var datatable = jQuery('#boxinfodatatable').DataTable();
    datatable.clear().draw();
    
    
    var datatableSEMS = jQuery('#boxinfodatatableSEMS').DataTable();
    datatableSEMS.clear().draw();
}

// reset / reload page
function reset_page() {
	
	jQuery('#file_upload_cr').val(0);
	jQuery( '#processing_notification_div' ).addClass( 'yellow_update' );
	jQuery( '#processing_notification' ).text( '' );
	console.log( 'reset ----------------------' );
	
	wpsc_get_create_ticket();
}

/* Converts datatable data into json */
jQuery.fn.toJson = function () {
    try {
        if (!this.is('table')) {
            return;
        }

        var results = [],
        headings = [];

        var table = jQuery('#boxinfodatatable').DataTable();

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

// Box list file validation on submit
jQuery(document).on('click', '#wpsc_create_ticket_submit', function() {
    
    let superfundx = jQuery('#super-fund').val();
    console.log({superfund:superfundx});
    
    if( superfundx == '' ) {
		alert('Please make a selection for the "Are these records part of SEMS?" dropdown.');
	    return false;
		
	} else if( superfundx == 'no' ) {
    
	    if( 0 === jQuery('#file_upload_cr').val() || "0" === jQuery('#file_upload_cr').val()){
	        alert('Please upload the Box List excel sheet');
	        return false;
	    }
	} else if( superfundx == 'yes' ) {
		
		if( 0 === jQuery('#file_upload_cr').val() || "0" === jQuery('#file_upload_cr').val()){
	        alert('Please upload the Box List excel sheet');
	        return false;
	    }
		
/*		// OLD code for when
		if( 0 === jQuery('#file_upload_cr_SEMS').val() || "0" === jQuery('#file_upload_cr_SEMS' ).val()){
	        alert('Please upload the SEMS excel sheet');
	        return false;
	    }
*/
		
	}
	
	var form_data = new FormData();
        form_data.append('file', theFile.file);
        form_data.append('action', 'move_excel_file');
        console.log('The Form Data');
        console.log( form_data );
        console.log( {file:theFile.file} );
        jQuery.ajax({
            url: wpsc_admin.ajax_url,
            type: 'post',
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                
                console.log("Excel uploaded successfully");
                console.log(response);
                let obj = JSON.parse( response );
                console.log( obj.attachment_id );
                
                jQuery( '#attachment_upload_cr' ).val( obj.attachment_id );
                
                link_ticket_id_and_attachment();
                
                
            },  
            error: function (response) {
                console.log("Excel did not upload successfully");
            }
        });
	
	
});


// Grabs the ticket_id and attachment_id and AJAX calls to link them in ticketmeta DB table
function link_ticket_id_and_attachment( ) {
		
	console.log( 'link_ticket_id_and_attachment' );
	
	//let attachment_id = get_attachment_id();
	let attachment_id = jQuery( '#attachment_upload_cr' ).val();
	let ticket_id;
	
	//let ticket_id = get_ticket_id();
	let ticket_id_promise = new Promise( function( success, failure ) {
		//ticket_id = get_ticket_id();
		
		let counter = 1;
	
		var timer = setInterval(function () {
			
		    console.log( "get_ticket_id attempt # " + counter );
		    ticket_id = jQuery( '#ticket_id' ).val();
		    
		    if( ticket_id == '' || ticket_id == null || ticket_id == undefined ) {
				console.log( 'waiting... on ticket_id' );
				
				if( counter >= 120 ) {
					console.log( 'waiting on ticket_id timeout.' );
					
					clearInterval(timer);
					failure( 'Took too long' );
				}
				
			} else {
				console.log( 'No (more) waiting necessary: ticket_id' );
				console.log( 'ticket_id: ' + ticket_id );
				clearInterval(timer);
				success( 'We did it.' );
				
			}
		
		    counter++;
		
		}, 250);
		
				
	});
	
	

	ticket_id_promise.then(
		function(value) { console.log( 'ticket_id after: ' + ticket_id ); ajax_link_ticket_id_and_attachment( attachment_id, ticket_id ); },
		function(error) { ticket_id_failed(  ); }
		
	);
/*
	ticket_id_promise.then(
		function( value ) {
			console.log( value );
			ajax_link_ticket_id_and_attachment( attachment_id, ticket_id );
		},
		function( error ) {
			console.log( error );
		}
	);
*/
	
	//ajax_link_ticket_id_and_attachment( attachment_id, ticket_id );


}

function ajax_link_ticket_id_and_attachment( attachment_id, ticket_id ) {
		
	console.log( 'AJAX link_ticket_id_and_attachment' );
	

	let data = {
		action: 'wppatt_link_ticket_and_attachment',
		ticket_id : ticket_id ,
		attachement_id: attachment_id
	}
	
	jQuery.ajax({
		type: "POST",
		url: wpsc_admin.ajax_url,
		data: data,
		success: function( response ){
			console.log('link_ticket_and_attachment done');
			console.log( response );	
		}
	
	});		

}

function ticket_id_failed() {
	console.log( 'ticket_id failed' );
	//alert( 'Waiting for ticket_id failed. Please retry.' );
}

// gets the ticket_id from the page (set in sc/.../load_create_ticket.php), and waits until it's set. 
/*
function get_ticket_id() {
	
	let ticket_id = jQuery( '#ticket_id' ).val();
	
	if( ticket_id == '' || ticket_id == null || ticket_id == undefined ) {
		console.log( 'waiting... on ticket_id' );
		window.setTimeout( get_ticket_id, 300 );
	} else {
		console.log( 'No waiting necessary: ticket_id' );
		return ticket_id;
	}
	
}
*/

// gets the ticket_id from the page (set in sc/.../load_create_ticket.php), and waits until it's set. 
function get_ticket_id() {
	
	let counter = 1;
	
	var timer = setInterval(function () {
		
	    console.log( "get_ticket_id attempt # " + counter );
	    let ticket_id = jQuery( '#ticket_id' ).val();
	    
	    if( ticket_id == '' || ticket_id == null || ticket_id == undefined ) {
			console.log( 'waiting... on ticket_id' );
			
			if( counter >= 120 ) {
				console.log( 'waiting on ticket_id timeout.' );
				clearInterval(timer);
			}
			
		} else {
			console.log( 'No (more) waiting necessary: ticket_id' );
			clearInterval(timer);
			
		}
	
	    counter++;
	
	}, 250);

	return ticket_id;
}



// gets the attchment_id from the page (set in sc/.../load_create_ticket.php), and waits until it's set. 
/*
function get_attachment_id() {
	
	let attachment_id = jQuery( '#attachment_upload_cr' ).val();
	
	if( attachment_id == '' || attachment_id == null || attachment_id == undefined ) {
		console.log( 'waiting... on attachment_id' );
		window.setTimeout( get_attachment_id, 300 );
	} else {
		console.log( 'No waiting necessary: attachment_id' );
		return attachment_id;
	}
	
}
*/



// Upload boxlist document, and create the data table
function wpsc_spreadsheet_new_upload(id, name, fileSS) {
	
	console.log( '--wpsc_spreadsheet_new_upload--' );
	console.log({id:id, name:name, fileSS:fileSS});
	
    jQuery('#attachment_upload').unbind('change');

    jQuery.fn.dataTable.ext.errMode = 'none';
    var flag = false;
    var file = fileSS;
    jQuery('#attachment_upload').val('');
    

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
    
    let superfundx = jQuery('#super-fund').val();
    if( superfundx == '' ) {
	    flag = true;
	    alert( 'Please make a selection for "Are these records part of SEMS?" before uploading the Box List.' );
    }

    //No file
    if( flag == true ) {
        jQuery('#file_upload_cr').val(0);
        var _ref;
        return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
    }

    if ( ! flag ) {

        jQuery('.row.wpsp_spreadsheet').each(function (i, obj) {
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
        data.append('nonce', jQuery('#wpsc_nonce').val().trim());
        
        console.log( 'The Data' );
        console.log( data );
        
        
        
/*
        let limit = 2000;
        let j =0;
        var refreshIntervalId = setInterval(function() {
		    //length = array.length;
		    if ( j < (limit) ) {
		        jQuery('#processing_notification').text( 'Processing Row #'+j );    
		    } else {
		        clearInterval(refreshIntervalId);
		    }
		    j++ 
		}, 1);
*/
        
/*
        for( var j = 1; j < 2000; j++ ) {
	    	jQuery('#processing_notification').text( 'Processing Row #'+j );    
        }
*/
        

        // Read file and provide json response
        jQuery.ajax({
            type: 'post',
            url: wpsc_admin.ajax_url,
            data: data,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener( "progress", function (evt) {
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
                console.log('box list upload success');
                console.log(response);
                
                jQuery( '#boxinfodatatable' ).show();
                //jQuery( '#boxinfodatatable' ).hide();
                jQuery( '#big_wrapper' ).hide();
                
                
                // If, after an unsuccessful upload, all subsequent unsuccessful uploads have a reponse of false.
                // This catches the response so there is no console error ( JSON.parse(response) would throw an error )
                if( response == false ) {
	                return;
                }
                
                
                var return_obj = JSON.parse(response);
                jQuery(attachment).find('.attachment_cancel').show();
                
                console.log({response:response});
                console.log({return_obj:return_obj});

                if( parseInt( return_obj.id ) != 0 ) {
                    jQuery(attachment).append( '<input type="hidden" name="' + name + '[]" value="' + return_obj.id + '">' );
                    jQuery(attachment).find( '.progress-bar' ).addClass( 'progress-bar-success' );

                    //Start of new Datatable code
                    var datatable = jQuery( '#boxinfodatatable' ).DataTable({
                        "autoWidth": true,
                        "scrollX": "100%",
                        "scrollXInner": "110%",
                        columnDefs: [
							{ orderable: false, targets: '_all' }
						]

                        
                    });

                    datatable.clear().draw();
                    //jQuery('#boxinfodatatable').hide();
					//jQuery('#boxinfodatatable_wrapper').hide();
                    
                    // sets order for data table                    
                    //datatable.column( '0:visible' ).order( 'asc' ).draw();
                    

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
                    
                    //console.log( 'arrayLength: ' + arrayLength );
                    
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
	                
                    if( parsedData[1][0] !== undefined && parsedData[1][18] !== undefined ) {
                        let prev_box = '';
                        let prev_epa_contact = '';
                        let prev_program_office = '';
                        let prev_record_schedule = '';
                        let prev_site_id = '';

						                             
                        //
                        // Validation
                        //
						
						// Regex
                        //let date_time_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4} ([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])$/;
                        let date_time_reg = /^(0?[0-9]|1[012])[\/\-](0?[0-9]|[12][0-9]|3[01])[\/\-]\d{4} ([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])$/;
                        //let date_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4}$/;
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
						let index_site_id = 11;
						let index_close_date = 12; 
						let index_epa_contact = 13; 
						let index_access_rest = 14;
						let index_sp_access_rest = 15;
						let index_use_rest = 16;
						let index_sp_use_rest = 17;
						let index_source_type = 19;
						let index_source_dim = 20;
						let index_prog_office = 21; 
						let index_prog_area = 22; 
						let index_index_level = 23; 
						let index_ess_rec = 24; 
						let index_tags = 26; 
						
						// Required Fields - Checking for blanks // Names for Error Reporting
						let arr_fields;
						if( superfundx == 'no' ) {
	                        // ECMS Required Fields
	                        arr_fields = [ 
	                        	'Box', 
	                        	'Folder Identifier', 
	                        	'Title', 
	                        	'Description of Record',
	                        	'Parent/Child',
	                        	'Creation Date', 
	                        	'Creator',
	                        	'Record Type',
	                        	'Disposition Schedule & Item Number',
	                        	'EPA Contact',
	                        	'Access Restrictions',
	                        	'Use Restrictions',
	                        	'Source Type',
	                        	'Source Dimensions',
	                        	'Program Office', 
	                        	'Index Level', 
	                        	'Essential Records'
	                        ];
	                    } else if( superfundx == 'yes' ) {
		                    // SEMS Required Fields
		                    arr_fields = [ 
	                        	'Box', 
	                        	'Folder Identifier', 
	                        	'Title', 
	                        	'Description of Record',
	                        	'Parent/Child',
	                        	'Creation Date', 
	                        	'Creator',
	                        	'Record Type',
	                        	'Disposition Schedule & Item Number',
	                        	'EPA Contact',
	                        	'Access Restrictions',
	                        	'Use Restrictions',
	                        	'Source Type',
	                        	'Source Dimensions',
	                        	'Program Office', 
	                        	'Program Area', 
	                        	'Index Level', 
	                        	'Essential Records'
	                        ];
		                    
	                    }
						
						
						//
                        // Loop through spreadsheet data    // OLD // for (var count = 1; count < arrayLength; count++) {		                            
                        //
                        
                        
	                    jQuery( '#processing_notification_div' ).show();
	                    jQuery( '#processing_notification_div' ).addClass( 'yellow_update' );
	                    jQuery( '#processing_notification' ).text( 'Processing Row #' );
	                    //jQuery('#boxinfodatatable_wrapper').hide();
	                    
                        let isBlank = false;
                        let count = 1;
				        var processLoopID = setInterval(function() {
						    if ( count < arrayLength ) {
						        jQuery('#processing_notification').text( 'Processing Row #' + count );    
						    
								
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
										)
										
								) {
	                                console.log( 'SKIP');
	                                isBlank = true;
	                                //continue;
	                            } else {
		                            isBlank = false;
	                            }
	                            
	                            // if row is not blank, then process it. Once the first blank is hit, then processing is finished.
	                            if( !isBlank ) {
								
									// D E B U G Data View
									//console.log( 'count: ' + count );
									//console.log( 'index_rec_type: ' + parsedData[count][index_rec_type] );
									//console.log( 'index_source_type: ' + parsedData[count][index_source_type] );
									//console.log( 'index_access_rest: ' + parsedData[count][index_access_rest] );
									
	
									// Trim white space, as white space counts as
									
									if(
										count > 1 && 
											(
												parsedData[count][index_box] != null
											&&
												parsedData[count][index_box] != undefined
											)
											
									) {
										parsedData[count][index_box] = parsedData[count][index_box].trim();
										//console.log('trimmed');
		                            }
		
									
									
		
									
									
									
									
									
		/*
		                        	parsedData[count][index_folder_id] = parsedData[count][index_folder_id].trim(); // Folder Identifier
		                        	parsedData[count][index_title] = parsedData[count][index_title].trim(); // Title
		                        	parsedData[count][index_desc_record] = parsedData[count][index_desc_record].trim(); // Description of Record
		                        	parsedData[count][index_pcd] = parsedData[count][index_pcd].trim(); // Parent / Child
		                        	parsedData[count][index_creation_date] = parsedData[count][index_creation_date].trim(); // Creation Date
		                        	parsedData[count][index_creator] = parsedData[count][index_creator].trim(); // Creator 
		                        	parsedData[count][index_rec_type] = parsedData[count][index_rec_type].trim(); // Record Type 
		                        	parsedData[count][index_rec_sched] = parsedData[count][index_rec_sched].trim(); // Disposition Schedule & Item Number 
		                        	parsedData[count][index_epa_contact] = parsedData[count][index_epa_contact].trim(); // EPA Contact 
		                        	parsedData[count][index_access_rest] = parsedData[count][index_access_rest].trim(); // Access Restrictions 
		                        	parsedData[count][index_use_rest] = parsedData[count][index_use_rest].trim(); // Use Restrictions 
		                        	parsedData[count][index_source_type] = parsedData[count][index_source_type].trim(); // Source Type 
		                        	parsedData[count][index_source_dim] = parsedData[count][index_source_dim].trim(); // Source Dimensions 
		                        	parsedData[count][index_prog_office] = parsedData[count][index_prog_office].trim(); // Program Office
		                        	parsedData[count][index_prog_area] = parsedData[count][index_prog_area].trim(); // Program Area
		                        	parsedData[count][index_index_level] = parsedData[count][index_index_level].trim(); // Index Level
		                        	parsedData[count][index_ess_rec] = parsedData[count][index_ess_rec].trim();
		*/
									
									
									// Clean Record Type. If * remove them. 
									if( parsedData[count][index_rec_type] ) {
										parsedData[count][index_rec_type] = parsedData[count][index_rec_type].replace( '*', '' );
									}
									
									// Clean Source Type. If * remove them. 
									if( parsedData[count][index_source_type] ) {
										parsedData[count][index_source_type] = parsedData[count][index_source_type].replace( '*', '' );
									}
		                                
		                            // Required fields Validation - Check for blank/null values
		                            let invalid_index;
		                            if( superfundx == 'no' ) {
			                            // ECMS Required Fields
			                            invalid_index = [
			                            	parsedData[count][index_box], // Box
			                            	parsedData[count][index_folder_id], // Folder Identifier
			                            	parsedData[count][index_title], // Title
			                            	parsedData[count][index_desc_record], // Description of Record
			                            	parsedData[count][index_pcd], // Parent / Child
			                            	parsedData[count][index_creation_date], // Creation Date
			                            	parsedData[count][index_creator], // Creator 
			                            	parsedData[count][index_rec_type], // Record Type 
			                            	parsedData[count][index_rec_sched], // Disposition Schedule & Item Number 
			                            	parsedData[count][index_epa_contact], // EPA Contact 
			                            	parsedData[count][index_access_rest], // Access Restrictions 
			                            	parsedData[count][index_use_rest], // Use Restrictions 
			                            	parsedData[count][index_source_type], // Source Type 
			                            	parsedData[count][index_source_dim], // Source Dimensions 
			                            	parsedData[count][index_prog_office], // Program Office
			                            	parsedData[count][index_index_level], // Index Level
			                            	parsedData[count][index_ess_rec]  // Essential Records
			                            ];  
			                        } else if( superfundx == 'yes' ) {
				                        // SEMS Required Fields
				                        invalid_index = [
			                            	parsedData[count][index_box], // Box
			                            	parsedData[count][index_folder_id], // Folder Identifier
			                            	parsedData[count][index_title], // Title
			                            	parsedData[count][index_desc_record], // Description of Record
			                            	parsedData[count][index_pcd], // Parent / Child
			                            	parsedData[count][index_creation_date], // Creation Date
			                            	parsedData[count][index_creator], // Creator 
			                            	parsedData[count][index_rec_type], // Record Type 
			                            	parsedData[count][index_rec_sched], // Disposition Schedule & Item Number 
			                            	parsedData[count][index_epa_contact], // EPA Contact 
			                            	parsedData[count][index_access_rest], // Access Restrictions 
			                            	parsedData[count][index_use_rest], // Use Restrictions 
			                            	parsedData[count][index_source_type], // Source Type 
			                            	parsedData[count][index_source_dim], // Source Dimensions 
			                            	parsedData[count][index_prog_office], // Program Office
			                            	parsedData[count][index_prog_area], // Program Area
			                            	parsedData[count][index_index_level], // Index Level
			                            	parsedData[count][index_ess_rec]  // Essential Records
			                            ];  
			                        }
		                            
		                            // D E B U G Data View
		                            //console.log( 'indexOf Null: ' + invalid_index.indexOf( null ) );
		                            //console.log( 'indexOf Undefined: ' + invalid_index.indexOf( undefined ) );
		                            
		                            // Validate - Check for blank/null values
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            		( 
		                            			( invalid_index.indexOf( null ) > -1 ) || 
		                            			( invalid_index.indexOf( undefined ) > -1 ) ||
		                            			( invalid_index.indexOf( '' ) > -1 ) 
		                            		) 
		                            ) {
										let err_index;
										
										if( invalid_index.indexOf( null ) > -1 ) {
											err_index = invalid_index.indexOf( null );
										} else if( invalid_index.indexOf( undefined ) > -1  ) {
											err_index = invalid_index.indexOf( undefined );
										} else if( invalid_index.indexOf( '' ) > -1  ) {
											err_index = invalid_index.indexOf( '' );
										}
										
										let alert_message = '';
		                                alert_message += "Blank value for column '" + arr_fields[err_index] + "' on line ";
		                                alert_message += (count + 1) + ". This field is required.";
		                                
		                                alert( alert_message );
		                                flag = true;
		                            
		                            }
									
									
									// Validate Creation date
									if( 
		                            	flag != true && count > 1 && 
		                            	date_reg.test( parsedData[count][index_creation_date] ) == false 
		                            ) {
		                                if( parsedData[count][index_creation_date] != '00/00/0000' ) {
			                                let alert_message = '';
											alert_message += "Invalid Creation Date for line " + (count + 1);
											alert_message += ". \n\n";
											alert_message += "Invalid value: " + parsedData[count][index_creation_date] + " \n\n";
											alert_message += "Format must be MM/DD/YYYY HH:mm:ss (ex: 1/13/2021 3:00:30) or ";
											alert_message += "MM/DD/YYYY (ex: 1/13/2021).";
			                                
			                                alert( alert_message );
			                                flag = true;
		                                }
			                             
		                            }
		
		/*							// Removed validation for date time, as time is no longer required. 
		                            if( 
		                            	flag != true && count > 1 && 
		                            	date_time_reg.test( parsedData[count][index_creation_date] ) == false 
		                            ) {
		                                
		                                if( date_reg.test( parsedData[count][index_creation_date] ) == false ) {
			                                if( parsedData[count][index_creation_date] != '00/00/0000' ) {
				                                let alert_message = '';
												alert_message += "Invalid Creation Date for line " + (count + 1) + ". \n\n ";
												alert_message += "Format must be MM/DD/YYYY HH:mm:ss (ex: 1/13/2021 3:00:30) or ";
												alert_message += "MM/DD/YYYY (ex: 1/13/2021).";
				                                
				                                alert( alert_message );
				                                flag = true;
			                                }
			                            } 
		                            }
		*/
		                            
		
									
									// Validate Site Name & Site ID. Both must be filled in, or both blank. No Halfsies. 
		                            if(
		                            	flag != true && count > 1 && 
		                            		(
			                            		( parsedData[count][index_site_name] != null && 
			                            		  parsedData[count][index_site_id] == null 
			                            		)
			                            	||
			                            		( parsedData[count][index_site_name] != undefined && 
			                            		  parsedData[count][index_site_id] == undefined 
			                            		)
			                            	)
		                            ) {
		                                let alert_message = '';
										alert_message += ' Discrepancy between Site Name and Site ID. \n\n ';
										alert_message += 'Site Name has a value of "'+parsedData[count][index_site_name];
										alert_message += '" while Site ID is blank on line '+ (count + 1) + '. \n\n ';
										alert_message += 'Both Site Name and Site ID must be filled in, or both must be blank.';
		                                
		                                alert( alert_message );
		                                flag = true;
		                            }
		                            
		                            // Validate Site Name & Site ID. Both must be filled in, or both blank. No Halvesis. 
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            		(
		                            			( parsedData[count][index_site_name] == null && 
			                            		  parsedData[count][index_site_id] != null 
			                            		)
			                            	||
			                            		( parsedData[count][index_site_name] == undefined && 
			                            		  parsedData[count][index_site_id] != undefined 
			                            		)
			                            	)
		                            ) {
		                                let alert_message = '';
										alert_message += ' Discrepancy between Site Name and Site ID. \n\n ';
										alert_message += 'Site Name is blank while Site ID has a value of "' + parsedData[count][index_site_id];
										alert_message += '" on line '+ (count + 1) + '. \n\n ';
										alert_message += 'Both Site Name and Site ID must be filled in, or both must be blank.';
		                                
		                                alert( alert_message );
		                                flag = true;
		                            }
									
									
		                            // Validate Close Date
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            	date_time_reg.test( parsedData[count][index_close_date] ) == false 
		                            ) {
		                                
		                                if( date_reg.test( parsedData[count][index_close_date] ) == false ) {
			                                
			                                if( 
			                                	parsedData[count][index_close_date] == null || 
			                                	parsedData[count][index_close_date] == undefined || 
			                                	parsedData[count][index_close_date] == '00/00/0000' 
			                                ) {
												// nothing. Blanks is fine.
			                                } else {
				                                let alert_message = '';
												alert_message += "Invalid Close Date for line " + (count + 1) + ". \n\n";
												alert_message += "Format must be MM/DD/YYYY HH:mm:ss (ex: 1/13/2021 3:00:30).";
				                                alert( alert_message );
												flag = true;
			                                }
			                                
			                            } else {
				                            // If valid date without time, add time to date for insertion
				                            //parsedData[count][index_close_date] = parsedData[count][index_close_date] + ' 00:00:01';
			                            }
		                            }
		
		
		                            // Box ID validation // Redundant. Can be removed.
		/*
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            		( parsedData[count][index_box] == null || 
		                            		  parsedData[count][index_box] === undefined
		                            		)
		                            ) {
		                                let alert_message = '';
										alert_message += 'Box ID value "'+parsedData[count][index_box]+'" seems incorrect for line '+ (count + 1);
		                                alert( alert_message );
		                                flag = true;
		                            }
		*/
		
		                            // Index level validation
		                            if(
		                            	flag != true && 
		                            	count > 1 && 
		                            		( parsedData[count][index_index_level].toLowerCase() != 'file' && 
		                            		  parsedData[count][index_index_level].toLowerCase() != 'folder'
		                            		)
		                            ){
		                                let alert_message = '';
										alert_message += 'Index level value "'+parsedData[count][index_index_level];
										alert_message += '" seems incorrect for the line number '+ (count + 1);
		                                alert( alert_message );
		                                flag = true;
		                            }
									
									
		                            // Epa contact, program office, record no validation
		                            
		                            // Remove requirement that epa_contact is the same for each box. 
		                            prev_epa_contact = parsedData[count][index_epa_contact];
		                            
		                            if(
		                            	flag != true && 
		                            	count > 1 && 
		                            		( prev_box != '' && 
		                            		  prev_box === parsedData[count][index_box] 
		                            		) 
		                            	&& 
											( prev_epa_contact !== parsedData[count][index_epa_contact] || 
											  prev_program_office !== parsedData[count][index_prog_office] || 
											  prev_record_schedule !== parsedData[count][index_rec_sched] 
											) 
									) {
		
		                                _column = ( prev_epa_contact !== parsedData[count][index_epa_contact] ? 
		                                	'EPA Contact' : 
		                                	( prev_program_office !== parsedData[count][index_prog_office] ? 
		                                		'Program Office' : 
		                                		( prev_record_schedule !== parsedData[count][index_rec_sched] ? 
		                                		'Record Schedule & Item Number' : '' 
		                                		) 
		                                	) 
		                                );
		                                
		                                _prev = ( prev_epa_contact !== parsedData[count][index_epa_contact] ? 
		                                	prev_epa_contact : 
		                                	( prev_program_office !== parsedData[count][index_prog_office] ? 
		                                		prev_program_office : 
		                                		( prev_record_schedule !== parsedData[count][index_rec_sched] ? 
		                                		prev_record_schedule : '' 
		                                		) 
		                                	) 
		                                );
		                                
		                                _index = ( prev_epa_contact !== parsedData[count][index_epa_contact] ? 
		                                	parsedData[count][index_epa_contact] : 
		                                	( prev_program_office !== parsedData[count][index_prog_office] ? 
		                                		parsedData[count][index_prog_office] : 
		                                		( prev_record_schedule !== parsedData[count][index_rec_sched] ? 
		                                		parsedData[count][index_rec_sched] : '' 
		                                		) 
		                                	) 
		                                );
										
										let alert_message = '';
										alert_message += "Invalid value in column '" + _column + "' on line " + (count + 1) + ". \n\n";
										alert_message += "'" + _column + "' must have the same value for all items in the same box ";
										alert_message += "(Box: " + prev_box + "). \n\n";
										alert_message += "Line " + count + " has value '" + _prev + "' while \n";
										alert_message += "Line " + (count + 1) + " has value '" + _index + "'.";							
										
		                                alert( alert_message );
		                                flag = true;
		                            }
		                            
		                            // SUPER FUND
		                            // Site id req
		                            
/*
		                            let cur_site_id_array;
			                        let cur_site_id;
		                            
		                            if( superfundx == 'yes' ) {
			                            let cur_site_id_array = parsedData[count][index_site_id].split( '/' );
			                            let cur_site_id = cur_site_id_array[0];
			                        }
		                            
		                            if(
		                            	flag != true && 
		                            	count > 1 && 
		                            	superfundx == 'yes' &&
		                            		( prev_site_id != '' && 
		                            		  prev_site_id !== cur_site_id 
		                            		) 
		                            	
									) {
		
		                                _column = 'Site ID # / OU';
		                                	 
		                                _prev = prev_site_id;
		                               
		                                _index = cur_site_id;
										
										let alert_message = '';
										alert_message += "Invalid value in column '" + _column + "' on line " + (count + 1) + ". \n\n";
										alert_message += "'" + _column + "' must have the same value for all items in the same request. ";
										alert_message += "\n\n";
										alert_message += "Line " + count + " has value '" + _prev + "' while \n";
										alert_message += "Line " + (count + 1) + " has value '" + _index + "'.";							
										
		                                alert( alert_message );
		                                flag = true;
		                            }
*/

		                            
		                            
		                            // Validate Access Restriction (No) & Specific Access Restriction (filled in)
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            		( parsedData[count][index_access_rest] == 'No' && 
		                            			( parsedData[count][index_sp_access_rest] != null || 
		                            			  parsedData[count][index_sp_access_rest] != undefined 
		                            			)
		                            		) 
		                            ) {
		                                
		                                let alert_message = '';
		                                alert_message += 'Discrepancy between Access Restriction and Specific Access Restriction. \n\n ';
		                                alert_message += 'Access Restriction value: "No", while Specific Access Restiction is "'; 
		                                alert_message += parsedData[count][index_sp_access_rest] + '" on line ' + (count + 1) + '. \n\n '; 
		                                alert_message += 'If Access Restriction is "No" then Specific Access Restriction must be blank.';
		                                
		                                
		                                alert( alert_message );
		                                flag = true;
		                            }
		
		                            // Validate Access Restriction (Yes) & Specific Access Restriction (blank)                            
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            		( parsedData[count][index_access_rest] == 'Yes' && 
		                            			( parsedData[count][index_sp_access_rest] == null || 
		                            			  parsedData[count][index_sp_access_rest] == undefined 
		                            			) 
		                            		) 
		                            ) {
		                                let alert_message = '';
		                                alert_message += 'Discrepancy between Access Restriction and Specific Access Restriction. \n\n ';
		                                alert_message += 'Access Restriction value: "Yes", while Specific Access Restiction is blank on line ';
		                                alert_message += (count + 1) + '. \n\n ';
		                                alert_message += 'If Access Restriction is "Yes" then Specific Access Restriction must be filled in.'
		                                
		                                alert( alert_message );
		                                flag = true;
		                            }
		                            
		                            
		                            // Validate Use Restriction (No) & Specific Use Restriction (filled in)
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            		(parsedData[count][index_use_rest] == 'No' && 
		                            			( parsedData[count][index_sp_use_rest] != null ||
		                            			  parsedData[count][index_sp_use_rest] != undefined 
		                            			)
		                            		)
		                            ) {
		                                let alert_message = '';
		                                alert_message += 'Discrepancy between Use Restriction and Specific Use Restriction. \n\n ';
		                                alert_message += 'Use Restriction value: "No", while Specific Use Restiction is "';
		                                alert_message += parsedData[count][index_sp_access_rest] + '" on line '+ (count + 1) + '. \n\n ';
		                                alert_message += 'If Use Restriction is "No" then Specific Use Restriction must be blank.';
		                                
		                                alert(alert_message);
		                                flag = true;
		                            }
		
		                            // Validate Use Restriction (Yes) & Specific Use Restriction (blank)                            
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            		( parsedData[count][index_use_rest] == 'Yes' && 
		                            			( parsedData[count][index_sp_use_rest] == null ||
		                            			  parsedData[count][index_sp_use_rest] == undefined
		                            			)
		                            		)
		                            ) {
		                                let alert_message = '';
		                                alert_message += 'Discrepancy between Use Restriction and Specific Use Restriction. \n\n ';
		                                alert_message += 'Use Restriction value: "Yes", while Specific Use Restiction is blank on line ';
		                                alert_message += (count + 1) + '. \n\n ';
		                                alert_message += 'If Use Restriction is "Yes" then Specific Use Restriction must be filled in.'
		                                
		                                alert(alert_message);
		                                flag = true;
		                            }
		                            
		                            
		                            // Validate JSON - Tags Column
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            	( parsedData[count][index_tags] != null ||
		                            	  parsedData[count][index_tags] != undefined
		                            	)
		                            	&& 
		                            	parsedData[count][index_tags] != 'Tags' 
		                            ) {
										
										let str = 'x';
										if( parsedData[count][index_tags].indexOf( '{' ) == 0 ) {
											str = parsedData[count][index_tags];
										} else {
											str = '{ ' + parsedData[count][index_tags] + '}';
										}
										
		                                try {
									        JSON.parse( str );
									    } catch ( e ) {
									        let alert_message = '';
											alert_message += "Invalid JSON format in the Tags column on line " + (count + 1) + ". ";
									        alert( alert_message );
											flag = true;
									    }
									}
		                            
		                            // Validate Parent/Child
		                            let pcd;
		                            if( parsedData[count][index_pcd] ) {
			                            pcd = parsedData[count][index_pcd].toUpperCase();
		                            }
		                            
		                            if( 
		                            	flag != true && 
		                            	count > 1 && 
		                            		!( pcd == 'P' || pcd == 'C' || pcd == 'S' ) 
		                            ) {
		                                let alert_message = '';
										alert_message += "Invalid Parent/Child format for record " + (count + 1) + ".";
		                                
		                                alert( alert_message );
		                                flag = true;
		                            }
									
									
		                            // Clear table if err
		                            if( flag == true ) {
										
										
										console.log('clear table');
		                                datatable.clear().draw();
		                                jQuery('#file_upload_cr').val(0);
		
		                                jQuery('.row.wpsp_spreadsheet').each(function (i, obj) {
		                                    obj.remove();
		                                });
										
										console.log( file.previewElement );
										console.log( file.previewElement.parentNode );
										
										//flag = false;
										
										if( file.previewElement != null && file.previewElement.parentNode != null ) {
											console.log( 'remove preview' );
											file.previewElement.parentNode.removeChild(file.previewElement);
										}
										
										// Stops the interval (e.g. for loop)
										clearInterval( processLoopID );
										
										// resets the page to defaults. 
										reset_page();
										
										// Changed return to false, as after an unsuccessful upload, the response was causing JSON.parse to throw
										// an error.
		                                var _ref;
		                                //return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
		                                return false;
		                            }
									
									console.log( 'post validation' );
									
		                            // Add record to datatable if no error
		                            if (
		                            	parsedData[count] !== undefined && 
		                            	parsedData[count].length > 0 && 
		                            	parsedData[count][0].toString().trim() != "Box"
		                            ) {
		
		                                prev_box = parsedData[count][index_box];
		                                prev_epa_contact = parsedData[count][index_epa_contact];
		                                prev_program_office = parsedData[count][index_prog_office];
		                                prev_record_schedule = parsedData[count][index_rec_sched];
		                                
/*
		                                if( superfundx == 'yes' ) {
			                                prev_site_id_array = parsedData[count][index_site_id].split( '/' );
			                                prev_site_id = prev_site_id_array[0];
			                            }
*/
		                                
		                                
		
		                                datatable.row.add([
		                                    parsedData[count][0],
		                                    parsedData[count][1],
		                                    parsedData[count][2],
		                                    parsedData[count][3],
		                                    parsedData[count][4],
		                                    parsedData[count][5],
		                                    parsedData[count][6],
		                                    parsedData[count][7],
		                                    parsedData[count][8],
		                                    parsedData[count][9],
		                                    parsedData[count][10],
		                                    parsedData[count][11],
		                                    parsedData[count][12],
		                                    parsedData[count][13],
		                                    parsedData[count][14],
		                                    parsedData[count][15],
		                                    parsedData[count][16],
		                                    parsedData[count][17], // For Folder/Filename field value
		                                    //parsedData[count][18], // For Tags field value // removed
		                                    parsedData[count][18], // For Parent/Child field value
		                                    parsedData[count][19],
		                                    parsedData[count][20],
		                                    parsedData[count][21],
		                                    parsedData[count][22],
		                                    parsedData[count][23],
		                                    parsedData[count][24],
		                                    parsedData[count][25],
		                                    parsedData[count][26]
		                                ]).draw().node();
		                                
		                                //jQuery('#processing_notification').text( 'Processing Row #' + count );
		                            }
		                        //} //for
	                        
	                        	} //if
	                        
	                        } else {
							        	clearInterval( processLoopID );
							        	//jQuery( '#boxinfodatatable_wrapper' ).show();
							        	//jQuery( '#boxinfodatatable' ).show();
							        	jQuery( '#big_wrapper' ).show();
							        	// sets order for data table                    
										datatable.column( '0:visible' ).order( 'asc' ).draw();

							        	//
							        	jQuery( '#processing_notification_div' ).removeClass( 'yellow_update' );
							        	jQuery( '#processing_notification_div' ).addClass( 'green_update' );
							        	jQuery( '#processing_notification' ).text( 'Processing Complete.' );
							        	jQuery( '#processing_notification_persistent' ).hide();
							        	
							        	// Set that the file is uploaded.
							        	jQuery('#file_upload_cr').val(1);
				                        jQuery('#wpsc_create_ticket_submit').removeAttr('disabled');
				                        console.log( '#file_upload_cr has been update' );
	
							       }
						    count++ 
						}, 1 ); //end of setInterval, 1ms
                        
                        
                                                
                    } else {
                        
                        let alert_message = '';
						alert_message += "Spreadsheet is not in the correct format. Please try again.";
                        
                        alert( alert_message );
                        
                        //window.location.reload();
                        
                        jQuery('.row.wpsp_spreadsheet').each(function (i, obj) {
                            obj.remove();
                        });
                        flag = true;

                        datatable.clear().draw();

                        jQuery('#file_upload_cr').val(0);
                        
                        reset_page();
                        
                        var _ref;
                        return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
                    }
                };
                FR.readAsArrayBuffer(file);
                document.getElementById("boxdisplaydiv").style.display = "block";

                    //End of new Datatable code

                } else {
                    jQuery(attachment).find('.progress-bar').addClass('progress-bar-danger');
                    //alert('Something went wrong. Please try again.');
                }
            }
        });

    }

}






