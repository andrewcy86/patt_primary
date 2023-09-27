var theFile = {};
var success = 0;
var ellipsis_limit = 30;
let fileUploadFail = false;

//console.log( 'nothing' );
jQuery(document).ready(function(){
    Dropzone.autoDiscover = false;
    console.log( 'ticket_box_list_save.js loaded' );
    


jQuery(document).on('click', '#wpsc_create_ticket_submit', function() {

jQuery(document).ajaxError(function (evt, jqXHR, settings, err) {
  alert('File upload failed. Please ensure the box list is not open in Excel or \n'
        + 'there was a network request error.');
  
  if(jqXHR.statusText == 'error'){
    fileUploadFail = true;
  }
  //exit;
  //window.location.reload();

  //wpsc_get_create_ticket();
});

});


    
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
		
		
		console.log({settingss:settings});
		
		
		//if ( 'action=wpsc_tickets&setting_action=create_ticket' == settings.data ) {
		if(Object.keys( settings ).includes('data')) {
			console.log( 'includes data' );
			console.log( {data:settings.data} );
			
			let page_href = window.location.href;
			console.log( page_href );
      
			// commented out for use in production before Revisioning is production worthy.
//  				if ( 'action=wpsc_tickets&setting_action=create_ticket' == settings.data || ( settings.data.includes( 'action=wpsc_get_approval_details&ticket_id=' ) ) ) {	
         if ( 'action=wpsc_tickets&setting_action=create_ticket' == settings.data  )  {	
//          if( true ) {
					  console.log( 'start' );
					  
					  
            var dropzoneOptions = {
                url: "test.php",
                autoProcessQueue: false,
                addRemoveLinks: true,
                uploadMultiple: false,
                dictResponseError: 'Error while uploading file.',
                maxFiles: 1,
                acceptedFiles: '.xlsm',

                accept: function (file, done) {
                    
                  console.log( 'ACCEPT' );
                  
                  //this.on( 'error', function( file, errorMessage ) {
                    //alert( 'error' );
                    //if ( errorMessage.indexOf( 'Error 404' ) !== -1 ) {
                      //var errorDisplay = document.querySelectorAll('[data-dz-errormessage]');
                      //errorDisplay[errorDisplay.length - 1].innerHTML = 'Error: The upload page was not found on the server';
                    //}
                 //});
                  
                  console.log( 'this.files.length: ' + this.files.length );
                    
                  jQuery( '#super-fund' ).attr("disabled", true);
                    
                  this.on("addedfile", function (file) {
				            if (this.files.length > 1) {
					            console.log( 'TOO LONG' );
			                this.removeAllFiles()
			                this.addFile(file);
				            }
                    
				            console.log('Do the image');
				            jQuery(".dz-image").attr('id', "document-image" );
				          });
                    
                    
                  theFile.file = file;
                  console.log({theFile:theFile});
                    //jQuery('#file_upload_cr').val(1);
                  wpsc_spreadsheet_new_upload('attach_16','spreadsheet_attachment', file);
                },
                init: function () {
                  
                  console.clear();
                  
                  console.log( 'INIT' );
                  
                  this.on( "maxfilesexceeded", function(file) {
					          console.log( 'maxfilesexceeded' );
                    this.removeAllFiles();
                    this.addFile(file);
                  });
                  
                  this.on( "error", function (file) {
                    console.log( 'error for maxfilesexceeded' );
                    if (!file.accepted) this.removeFile(file);
                  });
                  
                  this.on( "complete", function(file) {
	                  console.log( 'dropzone complete' );
                    jQuery(".dz-remove").html("<div><span class='fa fa-trash text-danger' style='font-size: 1.5em'></span></div>");
	                });
	            
                  this.on("addedfiles", function(files) {
  							    console.log(files.length + ' files added');
  							    console.log( files[0].name );
  							    console.log( files );
  							    
  							    let name_arr = files[0].name.split( '.' );
  							    console.log( name_arr );
  							    let extension = name_arr[ name_arr.length - 1 ];
  							    console.log( extension );
  							    
  							    if( !extension.includes( 'xls' ) ) {
  								    console.log( 'wrong type' );
  								    this.removeAllFiles();
  								    alert( 'Invalid File Type. Accepted file extensions: .xlsx, .xlsm \n\nProvided file extension: ' + extension );
  								    reset_page();
  							    }
  							    jQuery(".dz-remove").attr('onclick', "remove_link_clicked()");
  							    
				            jQuery( ".dz-image img" ).attr( 'id', "spreadsheet-thumbnail" );
				            jQuery( ".dz-image img" ).attr( 'aria-label', "thumbnail for uploaded spreadsheet" );
  							    
  							  });
					
					
                }
          	};
          	
          	
            var uploader = document.querySelector('#dzBoxUpload');
            var newDropzone = new Dropzone(uploader, dropzoneOptions);
            
            // Added event listener to the dzBoxUpload section to prevent the default form submission
			uploader.addEventListener("click", function(event){
				event.preventDefault();
			});
	                  
	        	} else {
		        	//alert( "Didn't grab dropzone files. Error." );
		        	console.log( "Didn't grab dropzone files. Error." );
	        	}
	        //}
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

	jQuery('.wpsc_loading_icon_submit_ticket').css('display','block');
	jQuery('.create_ticket_fields_container').css('display','none');
	jQuery('.create_ticket_frm_submit').css('display','none');
	
    let superfundx = jQuery('#super-fund').val();
    console.log({superfund:superfundx});
  
  	console.log('file upload fail var value: ' + fileUploadFail);
    
    if( superfundx == '' ) {
		alert('Please make a selection for the "Are these records Superfund Records?" dropdown.');
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
              	console.log( obj );
                console.log( typeof obj.attachment_id );
                
                jQuery( '#attachment_upload_cr' ).val( obj.attachment_id );
                
            },  
            error: function (response) {
                console.log("Excel did not upload successfully");
                wpsc_get_create_ticket();
            }
        });
	
	
});


// Grabs the ticket_id and attachment_id and AJAX calls to link them in ticketmeta DB tablefunction 

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
			
			jQuery('.wpsc_loading_icon_submit_ticket').css('display','none');
            jQuery('#patt_thankyou').delay(1000).fadeIn(2000);
		},
    error: function ( response ) {
            //alert('File upload failed. Please ensure the box list is not open in Excel.');
            console.log("Excel did not upload successfully");
            wpsc_get_create_ticket();
    }
	
	});		

}


function link_ticket_id_and_attachment( ) {
    console.log( 'link_ticket_id_and_attachment' );
    var ticket_id_loop = setInterval(function() {
    var txtInput = jQuery( '#ticket_id' ).val();
    if ( txtInput !== '' ) {
        
        var attachment_id = jQuery( '#attachment_upload_cr' ).val();
        console.log( 'ticket_id after: ' + txtInput );
        ajax_link_ticket_id_and_attachment( attachment_id, txtInput );
        success = 1;
    }
    
    if (success > 0) {
        console.log( 'success: '+ success);
        clearInterval(ticket_id_loop);
    }
    }, 100);
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

function remove_link_clicked() {
	console.log( 'clickity clack' );
/*
	clearBoxTable();
	jQuery( '.wpsp_spreadsheet' ).hide();
	jQuery( '#processing_notification_div' ).hide();
	jQuery( '#big_wrapper' ).hide();
	
	jQuery('#file_upload_cr').val(0);
	jQuery('#wpsc_create_ticket_submit').attr( 'disabled', 'disabled' );
*/
	
	reset_page();
		
}

// Upload boxlist document, and create the data table
function wpsc_spreadsheet_new_upload(id, name, fileSS) {
	
	console.log( '--wpsc_spreadsheet_new_upload----' );
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
	    reset_page();
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
                        '</div>';
                        
		// Removed x from progress bar.
		// '<img onclick="attachment_cancel(this);clearBoxTable()" class="attachment_cancel" src="' + attachment_info['close_image'] + '" style="display:none;" />'                         

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
        
        // try catch
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
							{ orderable: false, targets: '_all' },
							{
						        targets: [ 2, 3, 6, 7, 9, 10, 15, 17, 18, 26 ],
						        render: function ( data, type, row ) {
                        //console.log({data:data, type:type, row:row});
                        if( data ) {
                          return type === 'display' && data.length > ellipsis_limit ?
                              data.substr( 0, ellipsis_limit ) +'…' :
                              data;
                        } else {
                          return data;
                        }
                    }

						    }
						]

                        
                    });

                    datatable.clear().draw();
                    
                    console.log( 'post draw' );
                    //jQuery('#boxinfodatatable').hide();
					//jQuery('#boxinfodatatable_wrapper').hide();
                    
                    // sets order for data table                    
                    //datatable.column( '0:visible' ).order( 'asc' ).draw();
                    

                    var FR = new FileReader();
                    FR.onload = function (e) {
						
						console.log( 'inside onload' );
						
	                    var data = new Uint8Array(e.target.result);
	                    
	                    console.log( 'post Uint8Array' );
	                    
	                    // Checks if file is corrupt
	                    
	                    try {
							var workbook = XLSX.read( data, {
		                        type: 'array'
		                    });
						}
						catch( err ) {
							let err_message = 'Excel file corrupted. Please try saving/downloading the file again before uploading.\n\n';
							err_message += 'Error from SheetJS: ';
							alert( 'Ingestion Error:\n\n' + err_message + err.message );
							reset_page();
						}
						
	                    
	                    
	                    console.log( 'post workbook' );
	
	                    var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
	
	                    var result = XLSX.utils.sheet_to_json(firstSheet, {
	                        header: 1, raw: false
	                    });
	                    
	                    console.log( 'post sheet to json' );
	
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
	                    
	                    console.log({ col1_null:col1_null, col1_undef:col1_undef, col1_blank:col1_blank, arrayLength:arrayLength });
	                    
	                    console.log({one:parsedData[0]});
		                console.log({one:parsedData[1]});
		                //console.log({one:parsedData[1][0], eighteen:parsedData[1][18]});
                      
                      	// Prevents an empty new request form from being upload
						if(parsedData[2][0] == undefined || parsedData[2][0] == null ){
                          	let alert_msg = '';
                          	alert_msg += "Blank value for column 'Box' on row ";
                          	alert_msg += "3. This field is required.";

                          	alert( alert_msg );
                          	flag = true;
						}
                      
                      	if(parsedData[2][0] != undefined && parsedData[2][0] != null && parsedData[2][0] != 1 ){
							let alert_msg = '';
                          	alert_msg += "Please start the column 'Box' on row ";
                          	alert_msg += "3 with the number 1. This field is required";

                          	alert( alert_msg );
                          	flag = true;
						}
                      
                      // Validate spreadsheet version
                      //console.log('parsed data: ' + parsedData[0][27]);
                      //console.log('parsed data: ' + parsedData[0][28]);
                      if(parsedData[0][27] == '' || parsedData[0][27] == null || parsedData[0][27] == undefined){
                        alert('This is not a valid spreadsheet.');
                        flag = true;
                        reset_page();
                        return;
                      }
                      
                      
                      if(parsedData[2][4].toUpperCase() != "P"){
							let alert_msg = '';
                          	alert_msg += "Please start the column 'Parent/Child' on row ";
                          	alert_msg += "3 with the letter 'P'. A parent folder/file is required";

                          	alert( alert_msg );
                          	flag = true;
						}
                      
                      	
		                
	                    //if( parsedData[1][0] !== undefined && parsedData[1][18] !== undefined ) {
		                if( parsedData[1] !== undefined && parsedData[1] !== null ) {    
	                        let prev_box = '';
	                        let prev_epa_contact = '';
	                        let prev_program_office = '';
	                        let prev_record_schedule = '';
	                        let prev_site_id = '';
                          	let prev_box_id = 0;
                          	let prev_folder_id = 0;
							
							
							// removes asterisks from upload file headers
		                    parsedData[1].forEach( function( item, i ) {
                              if(item == null || item == undefined){
									let alert_message = '';
									alert_message += "Spreadsheet is not in the correct format. Please try again.";
									
									alert( alert_message );
									flag = true;
									
									reset_page();
								}
			                    parsedData[1][i] = item.replaceAll( '*', '' );
		                    });
							                             
	                        //
	                        // Validation
	                        //
							
							// Regex
	                        //let date_time_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4} ([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])$/;
	                        let date_time_reg = /^(0?[0-9]|1[012])[\/\-](0?[0-9]|[12][0-9]|3[01])[\/\-]\d{4} ([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])$/;
	                        //let date_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4}$/;
	                        let date_reg = /^(0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])[\/\-]\d{4}$/;
	                        let num_reg = /^[0-9]/;
	                        
							
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
                          	let index_rights_holder = 18;
							let index_source_type = 19;
							let index_source_dim = 20;
							let index_prog_office = 21; 
							let index_prog_area = 22; 
							let index_index_level = 23; 
							let index_ess_rec = 24;
                          	let index_folder_file_name = 25;  
							let index_tags = 26; 
							
							
							
							
							//
	                        // Loop through spreadsheet data    // OLD // for (var count = 1; count < arrayLength; count++) {		                            
	                        //
	                        
	                        
		                    jQuery( '#processing_notification_div' ).show();
		                    jQuery( '#processing_notification_div' ).addClass( 'yellow_update' );
		                    jQuery( '#processing_notification' ).text( 'Processing Row #' );
		                    //jQuery('#boxinfodatatable_wrapper').hide();
		                    
	                        let isBlank = false;
	                        let count = 2;
                          	var validate = false;
                          	let temp_record_schedule = false;
                          	let Final_Disposition = '';
                          	let parentExists = false;
                          
					        var processLoopID = setInterval(function() {
							    if ( count < arrayLength ) {
							        jQuery('#processing_notification').text( 'Processing Row #' + count );
                                  
                                	
									
									// Find the last line of filled out data
                                   	if (parsedData[count] == undefined) {
										datatable.clear().draw();
										alert('This box list may be corrupted. Please create a new box list and try again.');
										flag = true;
				
										
										reset_page();
                                  	}
									else if(
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
                                      
                                      
                                      		prev_box_id = parsedData[count-1][index_box];
                                          	prev_folder_id = parsedData[count-1][index_folder_id];
                                      
                                      
                                      let digital_source = parsedData[count][index_source_dim];
                                      let folder_file_name = parsedData[count][index_folder_file_name];
                                      let specific_access_restriction = parsedData[count][index_sp_access_rest];
                                      let specific_use_restriction = parsedData[count][index_sp_use_rest];
                                      let rights_holder = parsedData[count][index_rights_holder];
                                      
                                      
                                  // Validation for temporary/disposable record schedules
                                  if (parsedData[count][index_rec_sched] != null || parsedData[count][index_rec_sched] != undefined) {
                                    if(
                                      flag != true && 
                                      count > 1 && 
                                      (
                                        parsedData[count][index_rec_sched].indexOf( ':' ) >= 1 
                                      )
                                    ){
                                      let schedule_item_number = parsedData[count][index_rec_sched];
                                      let index_of_colon = parsedData[count][index_rec_sched].indexOf( ':' );

                                      schedule_item_number = schedule_item_number.slice(0, schedule_item_number.indexOf(':'));
                                      schedule_item_number = schedule_item_number.replace(/[\[\]']+/g,'');

                                      digital_source = parsedData[count][index_source_dim];
                                      folder_file_name = parsedData[count][index_folder_file_name];
                                      specific_access_restriction = parsedData[count][index_sp_access_rest];
                                      specific_use_restriction = parsedData[count][index_sp_use_rest];
                                      rights_holder = parsedData[count][index_rights_holder];

                                      //console.log('schedule item number: ' + parsedData[count][index_source_dim]);

                                      let apiUrl = '';
                                      let apiHostname = window.location.host;
                                      let isDev = true;
                                      const apiPathname = '/app/mu-plugins/pattracking/includes/admin/pages/games/get_rs.php?api_key=fcaaf49f-865e-4899-845f-e9b7cc214d1e&filter=Schedule_Item_Number,eq,'
                                      
                                      if(apiHostname == '086.info'){
                                        //Dev Url
                                        isDev = true;
                                        apiUrl += `https://086.info/wordpress6/app/mu-plugins/pattracking/includes/admin/pages/games/get_rs.php?api_key=fcaaf49f-865e-4899-845f-e9b7cc214d1e&filter=Schedule_Item_Number,eq,${schedule_item_number}`;
                                      }
                                      else {
                                        isDev = false;
                                        apiUrl += window.location.protocol + "//" + window.location.host + apiPathname + `${schedule_item_number}`;
                                      }



                                      var xhr = jQuery.ajax({
                                        url: apiUrl,
                                        type: 'get',
                                        async: false,
                                        success: function(data) {
                                          if(data.records[0].Final_Disposition == 'Disposable'){
                                            Final_Disposition = data.records[0].Final_Disposition;
                                            temp_record_schedule = true;
                                            console.log('this is a temp record');
                                            console.log(Final_Disposition);
                                          }
                                        },
                                        async: true,
                                        error: function(xhr, status, error){
                                          if(xhr.statusText == 'error'){
                                            errorMessage = xhr.status + ': ' + xhr.statusText;
                                          }
                                        },
                                        async: false,
                                        complete: function(xhr, status, error){
                                          if(xhr.statusText == 'error'){
                                            errorMessage = xhr.status + ': ' + xhr.statusText;
                                            validate = true;
                                          }
                                        }
                                      });


                                    }
                                  } 
                                    else {
                                    let alert_message = '';
                                    alert_message += "Blank value for column 'Disposition Schedule & Item Number' on line ";
			                        alert_message += (count + 1) + ". \n\n Please select a Disposition Schedule & Item Number.";
                                    alert(alert_message);
                                    flag = true;
                                  }
                                      
                                      // Required Fields - Checking for blanks // Names for Error Reporting
                                    let arr_fields;
                                    let errorMessage = '';
                                      
                                 
                                      console.log('temp_record_schedule ' + temp_record_schedule );
                                      
                                      if( superfundx == 'no' && temp_record_schedule == true ) {
                                        // ECMS Required Fields For Temp Records
                                        arr_fields = [ 
                                            'Box', 
                                            'Folder Identifier', 
                                            'Title', 
                                            //'Description of Record',
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
                                      }
                                      else if( superfundx == 'yes' && temp_record_schedule == true ) {
                                        // SEMS Required Fields For Temp Records
                                        arr_fields = [ 
                                            'Box',  // total 21
                                            'Folder Identifier', 
                                            'Title', 
                                            //'Description of Record',
                                            'Parent/Child',
                                            'Creation Date', 
                                            'Creator',
                                            'Record Type',
                                            'Disposition Schedule & Item Number',
                                          	'Site Name',
                                          	'Site ID #',
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
                                  
                                    else if( superfundx == 'no' ) {
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
                                          	'Site Name',
                                          	'Site ID #',
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
	
										
			                                
			                            // Required fields Validation - Check for blank/null values
			                            let invalid_index;
                                      	if(temp_record_schedule == true && superfundx == 'no'){
											console.log('temp record schedule is ' + temp_record_schedule);
                                          // ECMS Required Fields For Temp Records
				                            invalid_index = [
				                            	parsedData[count][index_box], // Box
				                            	parsedData[count][index_folder_id], // Folder Identifier
				                            	parsedData[count][index_title], // Title
				                            	//parsedData[count][index_desc_record], // Description of Record
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
										}
                                      	else if(temp_record_schedule == true && superfundx == 'yes'){
                                          // SEMS Required Fields For Temp Records
				                            invalid_index = [
				                            	parsedData[count][index_box], // Box  20 total
				                            	parsedData[count][index_folder_id], // Folder Identifier
				                            	parsedData[count][index_title], // Title
				                            	//parsedData[count][index_desc_record], // Description of Record
				                            	parsedData[count][index_pcd], // Parent / Child
				                            	parsedData[count][index_creation_date], // Creation Date
				                            	parsedData[count][index_creator], // Creator 
				                            	parsedData[count][index_rec_type], // Record Type 
				                            	parsedData[count][index_rec_sched], // Disposition Schedule & Item Number
                                              	parsedData[count][index_site_name], // Site Name
                                              	parsedData[count][index_site_id], // Site #/OU
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
			                            else if( superfundx == 'no' ) {
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
				                            	parsedData[count][index_box], // Box  20 total
				                            	parsedData[count][index_folder_id], // Folder Identifier
				                            	parsedData[count][index_title], // Title
				                            	parsedData[count][index_desc_record], // Description of Record
				                            	parsedData[count][index_pcd], // Parent / Child
				                            	parsedData[count][index_creation_date], // Creation Date
				                            	parsedData[count][index_creator], // Creator 
				                            	parsedData[count][index_rec_type], // Record Type 
				                            	parsedData[count][index_rec_sched], // Disposition Schedule & Item Number
                                              	parsedData[count][index_site_name], // Site Name
                                              	parsedData[count][index_site_id], // Site #/OU
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
                                     
                                      

											
                                      // Validate Box column has sequential box ordering
                                      if( 
                                        flag != true && 
                                        count > 1 && 
                                        (
                                          parsedData[count][index_box] > 0 &&
                                          //parsedData[count-1][index_box] >= 1 &&
                                          prev_box_id != parsedData[count][index_box] &&
                                          (parsedData[count][index_box]) - prev_box_id > 1
                                        )
                                      ) {
                                        let alert_message = '';
                                        alert_message += 'Box ID number should be in sequential order.';
                                        // alert_message += 'number has changed to ' + (parsedData[count][index_box] - prev_box_id) + ' from ' +  prev_box_id;
                                        alert( alert_message );
                                        flag = true;
                                        return;
                                      } else {
                                        // Validate Folder Identifier column has sequential box ordering
                                        if( 
                                          flag != true && 
                                          count > 1 && 
                                          (
                                            parsedData[count][index_box] != 1 &&
                                            parsedData[count-1][index_box] != parsedData[count][index_box] &&
                                            parsedData[count][index_folder_id] != 1 ||
                                            parsedData[count][index_folder_id] == 0
                                          )
                                        ) {
                                          let alert_message = '';
                                          alert_message += 'Please start a new box list folder identifier column with the number 1.';

                                          // DEBUG Messages
                                          // alert_message += 'test number has changed to ' + (parsedData[count][index_folder_id] - prev_folder_id) + ' from ' +  prev_folder_id;
                                          // alert_message += 'number has changed to ' + (parsedData[count][index_folder_id]) + ' from ' + prev_folder_id;
                                          alert( alert_message );
                                          flag = true;
                                          return;
                                        }

                                        if(flag != true && 
                                           count > 1 && 
                                           //Debugging
                                           //parsedData[count][index_folder_id] == 3
                                           
                                          parsedData[count][index_box] >= 1 &&
                                          prev_box_id == parsedData[count][index_box] &&
                                          prev_folder_id != 'Folder Identifier' &&
                                          (parsedData[count][index_folder_id]) - prev_folder_id != 1
                                           //prev_folder_id != 'Folder Identifier'
                                          ){
                                          let alert_message = '';
                                          alert_message += 'Folder identifier number should be in sequential order.';
                                          // alert_message += 'test number has changed to ' + (parsedData[count][index_folder_id] - prev_folder_id) + ' from ' +  prev_folder_id;
                                          // alert_message += 'number has changed to ' + (parsedData[count][index_folder_id]) + ' from ' + prev_folder_id;
                                          alert( alert_message );
                                          flag = true;
                                          //return;
                                        }
                                        
                                        else if(flag != true && 
                                           count > 1 && 
                                           //Debugging
                                           //parsedData[count][index_folder_id] == 3
                                           
                                          parsedData[count][index_box] >= 1 &&
                                          prev_box_id != parsedData[count][index_box] 
                                          ){
                                          
                                          if(prev_folder_id != 'Folder Identifier' &&
                                          	parsedData[count][index_folder_id] == prev_folder_id &&
                                            parsedData[count][index_folder_id] != 1)
                                          {
                                            let alert_message = '';
                                            alert_message += 'Each box should have a unique folder identifier.';
                                            // alert_message += 'test number has changed to ' + (parsedData[count][index_folder_id] - prev_folder_id) + ' from ' +  prev_folder_id;
                                            // alert_message += 'number has changed to ' + (parsedData[count][index_folder_id]) + ' from ' + prev_folder_id;
                                            alert( alert_message );
                                            flag = true;
                                            //return;
                                          }
                                          
                                        }
                                        
                                      }
                                      
                                      console.log(parsedData[count-1][index_box]);
                                      
                                      
                                     // Title Validation
                                      if(
                                          flag != true && 
                                          count > 1 && 
                                            (
                                              parsedData[count][index_title] != '' &&
                                              parsedData[count][index_title] != null &&
                                              parsedData[count][index_title].length > 255
                                            )             
                                        ) {
                                        	let alert_message = '';
											alert_message += "Please limit the amount of characters for Titles to 255 \n\n";										
			                                alert( alert_message );
			                                flag = true;
                                        }
			                            
			                           
                                      
                                      const daysInMonth = function(m, y) {
                                          switch (m) {
                                            case 1:
                                              return (y % 4 == 0 && y % 100) || y % 400 == 0 ? 29 : 28;
                                            case 8:
                                                return 30;
                                            case 3:
                                              return 30;
                                            case 5:
                                              return 30;
                                            case 10:
                                              return 30;
                                            default:
                                              return 31;
                                          }
                                        }

                                        const isValidDate = function(m, d, y) {
                                          return m >= 0 && m < 12 && d > 0 && d <= daysInMonth(m, y);
                                        }

										

										let creationDate = new Date(parsedData[count][index_creation_date]);
										let closeDate = new Date(parsedData[count][index_close_date]);
										let todayDate = new Date();
                                      
                                      	let dateFirstSlash = 0;
										let dayOfMonth = 0;
										let dateSecondSlash = 0; 
                                      
                                      	let month = 0;
										let day = 0;
										let year = 0;

                                      if(parsedData[count][index_creation_date] != null || parsedData[count][index_creation_date] != undefined){
										dateFirstSlash = parsedData[count][index_creation_date].indexOf('/') + 1;
										dayOfMonth = parsedData[count][index_creation_date].slice(dateFirstSlash);
										dateSecondSlash = dayOfMonth.indexOf('/'); 
                                        
                                        month = parsedData[count][index_creation_date].slice(0, 2);
										day = dayOfMonth.slice(0,dateSecondSlash);
										year = creationDate.getFullYear();
                                      }

										
										

										// Months start from 0 - 11 instead of 1 - 12
										month = month - 1;
                                      
                                      
                                      /*DEBUG SECTION*/
                                     /* console.log( creationDate );
                                      console.log('month ' + month );
                                      console.log('day ' + day );
                                      console.log('year ' + year );
                                      console.log(isValidDate(month, day, year)); */
                                      
										
										
										// Validate Creation date
                                      	if(flag != true && count > 1 && creationDate > todayDate){
											let alert_message = '';
												alert_message += "Invalid Creation Date for line " + (count + 1);
												alert_message += ". \n\n";
												alert_message += "Invalid value: " + parsedData[count][index_creation_date] + " \n\n";
												alert_message += "Please enter a past or present date.";
				                                
				                                alert( alert_message );
				                                flag = true;
										}
                                      	else if(flag != true && count > 1 && isValidDate(month, day, year) != true && parsedData[count][index_creation_date] != '00/00/0000'){
											let alert_message = '';
												alert_message += "Invalid Creation Date for line " + (count + 1);
												alert_message += ". \n\n";
												alert_message += "Invalid value: " + parsedData[count][index_creation_date] + " \n\n";
												alert_message += "Please enter a past or present date that exists.";
				                                
				                                alert( alert_message );
				                                flag = true;
										}
										else if( 
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
                                      
                                      // Create an array to store each Creator/Author name that is delimited by a semicolon
                                     /* var creatorsArray = parsedData[count][index_creator].trim().split("; ");                                    
                                      var invalidCreatorStrLength = false;
                                      
                                      // Iterate over the creators array and update the invalidStrLength variable
                                      // if any creator name exceeds 2000 characters
                                      for(var i=0; i < creatorsArray.length; i++){
                                        if(creatorsArray[i].length > 2000){
                                          invalidCreatorStrLength = true;
                                        } else {
                                          invalidCreatorStrLength = false
                                        }
                                      }*/
                                      
                                       // Creator/Author Character Length Validation
                                      /*if(
			                            	flag != true && 
			                            	count > 1 && 
			                            		(
                                                  parsedData[count][index_creator] != '' &&
                                              	  parsedData[count][index_creator] != null &&
			                            		  parsedData[count][index_creator].length > 2000
			                            		) 
			                            	
										) {
											let alert_message = '';
											alert_message += "Please limit the amount of characters for Creators to 2000 \n\n";
											//alert_message += "Expecting a format similar to: '[1051b] : Records of Senior Officials' \n\n";
											//alert_message += "No colon detected. \n\n";
											///alert_message += "Line " + (count+1) + " has value '" + parsedData[count][index_rec_sched] + "'.";										
			                                alert( alert_message );
			                                flag = true;
										}*/
                                      
                                      
                                      
                                      
                                      
			                            
			                           
			
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
                                      
                                       
                                      
			                            
			                            // Disposition Schedule: validate that there is a ':' in the name so that php processing works.
										if(
			                            	flag != true && 
			                            	count > 1 && 
			                            		( 
			                            		   parsedData[count][index_rec_sched].indexOf( ':' ) < 1
			                            		) 
			                            	
										) {
											let alert_message = '';
											alert_message += "Disposition Schedule appears to be in the incorrect format. \n\n";
											alert_message += "Expecting a format similar to: '[1051b] : Records of Senior Officials' \n\n";
											alert_message += "No colon detected. \n\n";
											alert_message += "Line " + (count+1) + " has value '" + parsedData[count][index_rec_sched] + "'.";										
			                                alert( alert_message );
			                                flag = true;
										}
                                      

										
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
                                      
                                      	// Site ID Validation
			                            if(
			                            	flag != true && count > 1 && 
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
											alert_message += 'Site ID has a value of "'+parsedData[count][index_site_id];
											alert_message += '" while Site Name is blank on line '+ (count + 1) + '. \n\n ';
											alert_message += 'Both Site Name and Site ID must be filled in, or both must be blank.';
			                                
			                                alert( alert_message );
			                                flag = true;
			                            }
                                      
										
			                            // Validate Close Date
			                            /*let closeDateFirstSlash = 0;
										let closeDayOfMonth = 0;
										let closeDateSecondSlash = 0; 
                                      
                                      	let closeMonth = '';
										let closeDay = '';
										let closeYear = '';

                                      if(parsedData[count][index_close_date] != null || parsedData[count][index_close_date] != undefined){
											closeDateFirstSlash = parsedData[count][index_close_date].indexOf('/') + 1;
											closeDayOfMonth = parsedData[count][index_close_date].slice(closeDateFirstSlash);
											closeDateSecondSlash = dayOfMonth.indexOf('/'); 
											
											closeMonth = creationDate.getMonth();
											closeDay = closeDayOfMonth.slice(0,closeDateSecondSlash);
											closeYear = creationDate.getFullYear();
										

											

										
											closeMonth = closeMonth.toString();
											closeDay = closeDay.toString();
											closeYear = closeYear.toString();

											if(closeMonth == 0 ){
												closeMonth+= 1;
											}
										
										
										
											
											
											// Validate Close date
											if(flag != true && count > 1 && closeDate > todayDate){
												let alert_message = '';
													alert_message += "Invalid Close Date for line " + (count + 1);
													alert_message += ". \n\n";
													alert_message += "Invalid value: " + parsedData[count][index_close_date] + " \n\n";
													alert_message += "Please enter a past or present date.";
													
													alert( alert_message );
													flag = true;
											}
											else if(flag != true && count > 1 && isValidDate(closeMonth, closeDay, closeYear) != true && parsedData[count][index_close_date] != '00/00/0000'){
												let alert_message = '';
													alert_message += "Invalid Close Date for line " + (count + 1);
													alert_message += ". \n\n";
													alert_message += "Invalid value: " + parsedData[count][index_close_date] + " \n\n";
													alert_message += "Please enter a past or present date that exists.";
													
													alert( alert_message );
													flag = true;
											}
											else if( 
												flag != true && count > 1 && 
												date_reg.test( parsedData[count][index_close_date] ) == false 
											) {
												if( parsedData[count][index_close_date] != '00/00/0000' ) {
													let alert_message = '';
													alert_message += "Invalid Close Date for line " + (count + 1);
													alert_message += ". \n\n";
													alert_message += "Invalid value: " + parsedData[count][index_close_date] + " \n\n";
													alert_message += "Format must be MM/DD/YYYY HH:mm:ss (ex: 1/13/2021 3:00:30) or ";
													alert_message += "MM/DD/YYYY (ex: 1/13/2021).";
													
													alert( alert_message );
													flag = true;
												}
												
											}

										} */
			
			                            
										
										
										
										// Program Office: validate that there is a ':' in the name so that php processing works.
										if(
			                            	flag != true && 
			                            	count > 1 && 
			                            	superfundx != 'yes' && 
			                            		( 
			                            		   parsedData[count][index_prog_office].indexOf( ':' ) < 1
			                            		) 
			                            	
										) {
											let alert_message = '';
											alert_message += "Program Office appears to be in the incorrect format. \n\n";
											alert_message += "Expecting a format similar to: OMS-OEIP-RSD : [REGULATORY SUPPORT DIVISION] \n\n";
											alert_message += "No colon detected. \n\n";
											alert_message += "Line " + (count+1) + " has value '" + parsedData[count][index_prog_office] + "'.";										
			                                alert( alert_message );
			                                flag = true;
										}
										
										
										
										// EPA Contact should not start with a number. 
										
										if(
			                            	flag != true && 
			                            	count > 1 && 
			                            		( 
			                            		   num_reg.test( parsedData[count][index_epa_contact] ) == true 
			                            		) 
			                            	
										) {
											let alert_message = '';
											alert_message += "EPA Contact appears to be in the incorrect format. \n\n";
											alert_message += "Expecting a letter in the first position. \n\n";
											alert_message += "First position contains a number. \n\n";
											alert_message += "Line " + (count+1) + " has value '" + parsedData[count][index_epa_contact] + "'.";										
			                                alert( alert_message );
			                                flag = true;
										}
										
										
			                            // Epa contact, program office, record num validation
			                            
			                            // Record Schedule (Disposition Schedule) Validation
			                            // Must be the same for entire Request
			                            
			                            if(
			                            	flag != true && 
			                            	count > 1 && 
			                            	prev_box != '' && 
												(  
												  prev_record_schedule !== parsedData[count][index_rec_sched] 
												) 
										) {
			
			                                _column = 'Record Schedule & Item Number';
			                                _prev = prev_record_schedule;
			                                _index = parsedData[count][index_rec_sched];
			                                		
											
											let alert_message = '';
											alert_message += "Invalid value in column '" + _column + "' on line " + (count + 1) + ". \n\n";
											alert_message += "'" + _column + "' must have the same value for all items in the same Request \n\n";
											alert_message += "Line " + count + " has value '" + _prev + "' while \n";
											alert_message += "Line " + (count + 1) + " has value '" + _index + "'.";							
											
			                                alert( alert_message );
			                                flag = true;
			                            }
										
										
										// Program Office Validation
			                            // Must be the same for each Box
			                            
			                            if(
			                            	flag != true && 
			                            	count > 1 && 
			                            		( prev_box != '' && 
			                            		  prev_box === parsedData[count][index_box] 
			                            		) 
			                            	&& 
												( 
												  prev_program_office !== parsedData[count][index_prog_office]
												) 
										) {
			
			                                _column = 'Program Office';
			                                
			                                _prev = prev_program_office;
			                                
			                                _index = parsedData[count][index_prog_office];
			                                										
											let alert_message = '';
											alert_message += "Invalid value in column '" + _column + "' on line " + (count + 1) + ". \n\n";
											alert_message += "'" + _column + "' must have the same value for all items in the same box ";
											alert_message += "(Box: " + prev_box + "). \n\n";
											alert_message += "Line " + count + " has value '" + _prev + "' while \n";
											alert_message += "Line " + (count + 1) + " has value '" + _index + "'.";							
											
			                                alert( alert_message );
			                                flag = true;
			                            }
	
			                            
			                            // Remove requirement that epa_contact is the same for each box. 
	/*
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
	*/
			                            
			                            //console.log( 'prev_site_id read: ' + prev_site_id );
			                            
			                            // SUPER FUND
			                            // Site id required
			                            // Site id must be 7 characters long. 
			                            
			                            let cur_site_id_array;
				                        let cur_site_id;
			                            
			                            if( superfundx == 'yes' ) {
				                            
				                            if(
				                            	count > 1 &&
				                            	( 
				                            		parsedData[count][index_site_id] == null ||
													parsedData[count][index_site_id] == undefined 
												)
											) {
												// Superfund AND null or undefined. Error.
												let alert_message = '';
												alert_message += "Site ID is required for SEMS. \n\n";
												alert_message += "Site ID is blank on line: " + ( count + 1) + ".";
												
				                                alert( alert_message );
				                                flag = true;
												
												
											} else if( count > 1 ) {
												// Superfund AND not null or undefined. Process.
												cur_site_id_array = parsedData[count][index_site_id].split( '/' );
					                            cur_site_id = cur_site_id_array[0].trim();
					                            console.log( 'cur_site_id: ' + cur_site_id );
					                            //console.log( count );
					                            
					                            if( cur_site_id.length != 7 ) {
						                            
						                            let alert_message = '';
													alert_message += "Site ID must be 7 digits long. \n\n";
													alert_message += "Site ID on line: " + ( count + 1 );
													alert_message += " has a length of " + cur_site_id.length + " digits.";
													
					                                alert( alert_message );
					                                flag = true;
	
						                            
					                            }
					                            
											}
				                            
				                        }
			                            
			                            //console.log( 'cur_site_id: ' + cur_site_id + ' && prev_site_id: ' + prev_site_id );
			                            
			                            
			                            // Site ID must be the same for entire box listing. Superfund only.
			                            
	/*
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
	
			                            
			                            //console.log( 'ACCESS RESTRICTIONS' );
			                            //console.log( {accessRest:parsedData[count][index_access_rest], specificiAR:parsedData[count][index_sp_access_rest] });
			                            //console.log( parsedData[count][index_access_rest] == 'No' );
			                            //console.log( parsedData[count][index_sp_access_rest] != null );
                                      
                                      
			                            
			                            // Validate Access Restriction (No) & Specific Access Restriction (filled in)
			                            if( 
			                            	flag != true && 
			                            	count > 1 && 
			                            		( parsedData[count][index_access_rest] == 'No' && 
			                            			( parsedData[count][index_sp_access_rest] != null && 
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
                                      
                                     let access_restriction = 0;
			
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
			                                alert_message += parsedData[count][index_sp_use_rest] + '" on line '+ (count + 1) + '. \n\n ';
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
                                      
                                      
                                      	// Temp Record Files Validation Continued
										if(flag != true && count > 1 && Final_Disposition == 'Disposable'){
											// temp_record_schedule = true;
											// console.log('this is a temp record');
											
                                          	// Specific Use and Access Restriction Validation For Temp
											/*if (specific_access_restriction != null || specific_access_restriction != undefined) {
											  if (specific_access_restriction.includes("Controlled / Copyright")){
												access_restriction = 1;
											  }
											} else if (specific_use_restriction != null || specific_use_restriction != undefined ) {
											  if (specific_use_restriction.includes("Controlled / Copyright")){
												access_restriction = 1;
											  }
											} else {
											  access_restriction = 0;
											}

											if((access_restriction != 0) && (rights_holder == null || rights_holder == undefined)){
											  let alert_message = '';
											  alert_message += "The Specific Access Restriction and/or Specific Use Restriction columns appears to have 'Controlled / Copyright' selected on Line " + (count+1) + ". \n\n";
											  alert_message += "The Rights Holder column is now required on Line " + (count+1) + " and currently has an empty value.";									
											  alert( alert_message );
											  flag = true;
											  return;
											}*/
                                          


											if(digital_source == 'Digital Source' && (folder_file_name == null || folder_file_name == undefined)){
											  let alert_message = '';
											  alert_message += "The Source Dimensions column appears to have 'Digital Source' selected on Line " + (count+1) + ". \n\n";
											  alert_message += "The Folder/Filename column is now required on Line " + (count+1) + " and currently has an empty value.";									
											  alert( alert_message );
											  flag = true;
											  return;
											}

										}
                                      
                                      
                                      
                                      // Create an array to store each Rights Holder name that is delimited by a semicolon
                                     /* var rightsHolderArray = parsedData[count][index_rights_holder].trim().split("; ");                                    
                                      var invalidRightsHolderStrLength = false;
                                      
                                      // Iterate over the Rights Holder array and update the invalidStrLength variable
                                      // if any addressee name exceeds 2000 characters
                                      for(var i=0; i < rightsHolderArray.length; i++){
                                        if(rightsHolderArray[i].length > 2000){
                                          invalidRightsHolderStrLength = true;
                                        } else {
                                          invalidRightsHolderStrLength = false
                                        }
                                      }*/
                                      
                                       // Rights Holder Character Length Validation
                                      /*if(
			                            	flag != true && 
			                            	count > 1 && 
			                            		(
                                                  parsedData[count][index_rights_holder] != '' &&
                                              	  parsedData[count][index_rights_holder] != null &&
			                            		  parsedData[count][index_rights_holder].length > 2000
			                            		) 
			                            	
										) {
											let alert_message = '';
											alert_message += "Please limit the amount of characters for each rights holder to 2000 \n\n";
											//alert_message += "Expecting a format similar to: '[1051b] : Records of Senior Officials' \n\n";
											//alert_message += "No colon detected. \n\n";
											///alert_message += "Line " + (count+1) + " has value '" + parsedData[count][index_rec_sched] + "'.";										
			                                alert( alert_message );
			                                flag = true;
										}*/
                                      
                                      
                                      
                                      // Validate Rights Holder When Access or Use Restriction is 'Yes' - Perm Records                          
			                          /*  if( 
			                            	flag != true && 
			                            	count > 1 && 
			                            		( (parsedData[count][index_rights_holder] == null || parsedData[count][index_rights_holder] == undefined) && 
			                            			( parsedData[count][index_use_rest] == 'Yes'
			                            			  //parsedData[count][index_access_rest] == 'Yes'
			                            			)
			                            		)
			                            ) {
			                                let alert_message = '';
			                                alert_message += 'Discrepancy between Rights Holder and Use Restriction. \n\n ';
			                                alert_message += 'Use Restriction value: "Yes", while Rights Holder is blank on line ';
			                                alert_message += (count + 1) + '. \n\n ';
			                                alert_message += 'If Use Restriction is "Yes" then Rights Holder must be filled in.'
			                                
			                                alert(alert_message);
			                                flag = true;
			                            } */

                                          
                                          //console.log('rights holder: ' + parsedData[count][index_rights_holder]);
                                          
                                        // Validate Rights Holder When Access or Use Restriction is 'No' - Perm Records                         
			                            /*if( 
			                            	flag != true && 
			                            	count > 1 && 
			                            		( (parsedData[count][index_rights_holder] != null || parsedData[count][index_rights_holder] != undefined) && 
			                            			( parsedData[count][index_use_rest] == 'No'
			                            			  //parsedData[count][index_access_rest] == 'No'
			                            			)
			                            		)
			                            ) {
			                                let alert_message = '';
			                                alert_message += 'Discrepancy between Rights Holder and Use Restriction. \n\n ';
			                                alert_message += 'Use Restriction: "No", while Rights Holder is not blank on line ';
			                                alert_message += (count + 1) + '. \n\n ';
			                                alert_message += 'If Use Restriction is "No" then Rights Holder must be blank.'
			                                
			                                alert(alert_message);
			                                flag = true;
			                            }*/

			                            
			                            // Validate Program Area. If ECMS, must be blank.
			                            if(
			                            	flag != true && 
			                            	count > 1 && 
			                            	superfundx == 'no' &&
												( parsedData[count][index_prog_area] != null && 
												  parsedData[count][index_prog_area] != undefined && 
												  parsedData[count][index_prog_area] != ''
												)
										) {
											
											_column = 'Program Area';
											
											alert_message = '';
			                                alert_message += "Invalid value in column '" + _column + "' on line " + (count + 1) + ". \n\n";
											alert_message += "'" + _column + "' must be blank when submitting to ECMS. ";
											alert_message += "\n\n";
											alert_message += "Line " + (count + 1) + " has value '" + parsedData[count][index_prog_area] + "'.";										
			                                alert( alert_message );
			                                flag = true;
			                            }
										
										
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
                                      
                                      
                                     	 // Folder/File Validation
                                      	/*if(
                                          	flag != true && 
                                          	count > 1 && 
                                            (
                                              parsedData[count][index_folder_file_name] != '' &&
                                              parsedData[count][index_folder_file_name] != null &&
                                              parsedData[count][index_folder_file_name].length > 2000
                                            )             
                                        ) {
                                        	let alert_message = '';
											alert_message += "Please limit the amount of characters for a folder/file name to 2000 \n\n";										
			                                alert( alert_message );
			                                flag = true;
                                        }*/
			                            
			                            
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
                                      
                                      
                                        // Create an array to store each Creator/Author name that is delimited by a semicolon
                                        /*var tagsArray = parsedData[count][index_tags].trim().split(", ");                                    
                                        var invalidTagsStrLength = false;

                                        // Iterate over the creators array and update the invalidStrLength variable
                                        // if any creator name exceeds 2000 characters
                                        for(var i=0; i < tagsArray.length; i++){
                                          if(tagsArray[i].length > 2000){
                                            invalidTagsStrLength = true;
                                          } else {
                                            invalidTagsStrLength = false
                                          }
                                        }*/
                                      
                                       	// Tags Character Length Validation
                                       /* if(
                                              flag != true && 
                                              count > 1 && 
                                                  (
                                                    parsedData[count][index_tags] != '' &&
                                              		parsedData[count][index_tags] != null &&
                                                    parsedData[count][index_tags].length > 2000
                                                  ) 

                                          ) {
                                              let alert_message = '';
                                              alert_message += "Please limit the amount of characters of each Tag to 2000 \n\n";
                                              //alert_message += "Expecting a format similar to: '[1051b] : Records of Senior Officials' \n\n";
                                              //alert_message += "No colon detected. \n\n";
                                              ///alert_message += "Line " + (count+1) + " has value '" + parsedData[count][index_rec_sched] + "'.";										
                                              alert( alert_message );
                                              flag = true;
                                          } */
                                      
			                            
			                            // Validate Parent/Child
			                            let pcd;
			                            if( parsedData[count][index_pcd] ) {
				                            pcd = parsedData[count][index_pcd].toUpperCase();
			                            }
                                      
                                      	if(pcd == 'P'){
                                          parentExists = true;
                                        }
			                            
			                            if( 
			                            	flag != true && 
			                            	count > 1 && 
			                            		!( pcd == 'P' || pcd == 'C' ) 
			                            ) {
			                                let alert_message = '';
											alert_message += "Invalid Parent/Child format for record " + (count + 1) + ".";
			                                
			                                alert( alert_message );
			                                flag = true;
			                            }
                                      
                                      
                                      
                                      
                                      // Validate Box column starts with number 1
										if( 
			                            	flag != true && 
			                            	count > 1 && 
			                            		( 
												  parsedData[2][index_box] != 1 ||
												  parsedData[2][index_box] == 0
			                            		)
			                            ) {
			                                let alert_message = '';

											alert_message += 'Please start line '+ (count + 1) +' with number 1.' ;
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
										
										//console.log( 'post validation' );
										
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
                                          
                                          	
			                                
			                                if( superfundx == 'yes' ) {
				                                let prev_site_id_array = parsedData[count][index_site_id].split( '/' );
				                                prev_site_id = prev_site_id_array[0].trim();
				                                console.log( 'prev_site_id set: ' + prev_site_id );
				                            }
			                                
			                                
			
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
                                  			  // Checks if a parent folder/file exists in the document at all
                                              //console.log('parent exists: ' + parentExists);
                                              /*if(parentExists == false){
                                                alert('You can not upload a box listing without any Parents.');
                                                flag = true;
									
												reset_page();
                                                //window.location.reload();
                                              }*/
                                           // }
                                  
								        	clearInterval( processLoopID );
								        	//jQuery( '#boxinfodatatable_wrapper' ).show();
								        	//jQuery( '#boxinfodatatable' ).show();
								        	jQuery( '#big_wrapper' ).show();
								        	// sets order for data table                    
											//datatable.column( '0:visible' ).order( 'asc' ).draw();
											datatable.order( [ 0, 'asc' ], [ 1, 'asc' ] ).draw();
	
								        	//
								        	jQuery( '#processing_notification_div' ).removeClass( 'yellow_update' );
								        	jQuery( '#processing_notification_div' ).addClass( 'green_update' );
								        	jQuery( '#processing_notification' ).text( 'Processing Complete.' );
								        	jQuery( '#processing_notification_persistent' ).hide();
								        	
								        	// Set that the file is uploaded.
								        	jQuery('#file_upload_cr').val(1);
					                        jQuery('#wpsc_create_ticket_submit').removeAttr('disabled');
					                        console.log( '#file_upload_cr has been update' );
                                  
                                          //console.log('validate ' + validate);
                                          if(validate == true) {
                                            alert('404 Error: All records have been designated as permanent.');
                                          }  
		
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
            },
            error: function(xhr, status, error){
            //alert('File upload failed. Please ensure the box list is not open in Excel.');
            console.log("Excel did not upload successfully");
            wpsc_get_create_ticket();
     }
        });

    }

}