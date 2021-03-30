
var obj_s3link;
var obj_key;
var obj_size;
var obj_type;
var obj_name;


function S3MultiUpload(file) {
    //Specifies 22 mb chunks convert to bytes
    this.PART_SIZE = 22 * 1024 * 1024;
//     this.SERVER_LOC = '?'; // Location of the server

this.SERVER_LOC = '/app/mu-plugins/pattracking/includes/admin/pages/scripts/index_wp6.php';

//this.SERVER_LOC = '/wordpress6/web/app/mu-plugins/pattracking/includes/admin/pages/scripts/index.php';  
//CHANGE TO this.SERVER_LOC = '/app/mu-plugins/pattracking/includes/admin/pages/scripts/index.php';  IN PRODUCTION
  
	console.log('SETTINGS');
	console.log('wp6');
	
	const url = window.location.pathname;
	//console.log({url:url});
    this.completed = false;
    this.file = file;
    this.fileInfo = {
        name: this.file.name,
        type: this.file.type,
        size: this.file.size,
        lastModifiedDate: this.file.lastModifiedDate
    };
    this.sendBackData = null;
    this.uploadXHR = [];
    // Progress monitoring
    this.byterate = []
    this.lastUploadedSize = []
    this.lastUploadedTime = []
    this.loaded = [];
    this.total = [];
    this.parts = []; // pre-partsCompleted parts
    this.partsCompleted = [false];
    this.partsInProgress = [false];
    
    obj_name = this.file.name;
    
    //console.log({SIZE:this.fileInfo.size});
    
    if( this.fileInfo.size == 0 ) {
	    console.log('zero');
    }
}

Array.prototype.remove = function() {
    var what, a = arguments, L = a.length, ax;
    while (L && this.length) {
        what = a[--L];
        while ((ax = this.indexOf(what)) !== -1) {
            this.splice(ax, 1);
        }
    }
    return this;
};

console.log('s3upload.js loaded 1');
/**
 * Creates the multipart upload
 */
S3MultiUpload.prototype.createMultipartUpload = function() {
    var self = this;
    var unixnow = Math.floor(Date.now() / 1000);
    
    // D E B U G
    //console.log({unixnow:unixnow});
    console.log({self_fileInfo:self.fileInfo});
    console.log({SERVER_LOC:self.SERVER_LOC});
    obj_size = self.fileInfo.size;
    obj_type = self.fileInfo.type;
    
    jQuery.post(self.SERVER_LOC, {
        command: 'create',
        fileInfo: self.fileInfo,
        //key: self.file.lastModified + '_' + self.file.name
        key: unixnow + '_' + self.file.name.replace(/\s/g, '')
    }).done(function(data) {
        console.log('Done');
        //console.log({data:data});
        self.sendBackData = data;
        document.getElementById("uploadId").value = self.sendBackData.uploadId;
        document.getElementById("objectkey").value = 'Object key: '+self.sendBackData.key;
        //console.log(self.sendBackData.uploadId);
        //console.log(self.sendBackData.key);
        obj_key = self.sendBackData.key;
        self.uploadParts();
    }).fail(function(jqXHR, textStatus, errorThrown) {
	    console.log('FAILED create');
	    //console.log({errorThrown:errorThrown, textStatus:textStatus, jqXHR:jqXHR});
        self.onServerError('create', jqXHR, textStatus, errorThrown);
    });
};


/** private */
S3MultiUpload.prototype.resumeMultipartUpload = function(uploadId) {
    var self = this;
    self.sendBackData = {
        uploadId: uploadId,
        key: self.file.lastModified + self.file.name
    };

    jQuery.post(self.SERVER_LOC, {
        command: 'listparts',
        sendBackData: self.sendBackData
    }).done(function(data) {
        
        if (data.parts) {
            var parts = data.parts
            //console.log(parts)
        }

        for (var i = 0; i < parts.length; i++) {
            self.loaded[parts[i].PartNumber] = parts[i].Size
            self.total[parts[i].PartNumber] = parts[i].Size
            self.partsCompleted[parts[i].PartNumber] = true
        }

        self.uploadParts();

    }).fail(function(jqXHR, textStatus, errorThrown) {
        self.onServerError('listparts', jqXHR, textStatus, errorThrown);
    });
};

/** private */
S3MultiUpload.prototype.uploadParts = function() {
    var blobs = this.blobs = [], promises = [];
    var partNumbers = this.partNumbers = []
    var start = 0;
    var end, blob;
    var partNum = 0;


    while(start < this.file.size) {
        end = Math.min(start + this.PART_SIZE, this.file.size);
		filePart = this.file.slice(start, end);
        
        // this is to prevent push blob with 0Kb
        if (filePart.size > 0) {
            this.partsInProgress.push(false)
            partNumbers.push(partNum+1)
        }

        if (filePart.size > 0 && !this.partsCompleted[partNum+1]) {
            
            blobs.push(filePart);

            //console.log('Getting presigned URL for part ' + (partNum+1))
            promises.push(this.uploadXHR[filePart]=jQuery.post(this.SERVER_LOC, {
                command: 'part',
                sendBackData: this.sendBackData,
                partNumber: partNum+1,
                contentLength: filePart.size
            }));

        }
        start = this.PART_SIZE * ++partNum;
    }
    jQuery.when.apply(null, promises)
     .then(this.sendAll.bind(this), this.onServerError)
     .done(this.onPrepareCompleted);

     //console.log(this.partsInProgress)
     //console.log(this.partNumbers)
};

/**
 * Sends all the created upload parts in a loop
 */
S3MultiUpload.prototype.sendAll = function() {
    var blobs = this.blobs;
    var length = blobs.length;
    var data = Array.from(arguments)

    if (length==1) {
        //console.log("Sending object")
        this.sendToS3(data[0], blobs[0], 0, 1);
    } else {
        for (var i = 0; i < length; i++) {
            //console.log("Sending part " + this.partNumbers[i])
            this.sendToS3(data[i][0], blobs[i], i, this.partNumbers[i]);
        }
    }
};
/**
 * Used to send each uploadPart
 * @param  array data  parameters of the part
 * @param  blob blob  data bytes
 * @param  integer index part index (base zero)
 */
S3MultiUpload.prototype.sendToS3 = function(data, blob, index, partNumber) {
    var self = this;
    var url = data['url'];
    var size = blob.size;
    var request = self.uploadXHR[index] = new XMLHttpRequest();
    request.onreadystatechange = function() {
        if (request.readyState === 4) { // 4 is DONE
            // self.uploadXHR[index] = null;
            if (request.status !== 200) {
                self.updateProgress();
                self.onS3UploadError(request);
                return;
            }
            console.log('Finished part '+partNumber)
            self.partsCompleted[partNumber] = true
            self.partsInProgress[partNumber] = false
            self.updateProgress();
        }
    };

    request.upload.onprogress = function(e) {

        if (e.lengthComputable) {

            if (!self.partsInProgress[partNumber]) {
                    self.partsInProgress[partNumber] = true
            }

            self.total[partNumber] = size;
            self.loaded[partNumber] = e.loaded;
            if (self.lastUploadedTime[partNumber])
            {
                var time_diff=(new Date().getTime() - self.lastUploadedTime[partNumber])/1000;
                if (time_diff > 0.005) // 5 miliseconds has passed
                {
                    var byterate=(self.loaded[partNumber] - self.lastUploadedSize[partNumber])/time_diff;
                    self.byterate[partNumber] = byterate; 
                    self.lastUploadedTime[partNumber]=new Date().getTime();
                    self.lastUploadedSize[partNumber]=self.loaded[partNumber];
                }
            }
            else 
            {
                self.byterate[partNumber] = 0; 
                self.lastUploadedTime[partNumber]=new Date().getTime();
                self.lastUploadedSize[partNumber]=self.loaded[partNumber];
            }
            // Only send update to user once, regardless of how many
            // parallel XHRs we have (unless the first one is over).
            if (index==0 || self.total[0]==self.loaded[0]) {
                self.updateProgress();
            }
        }
    };
    request.open('PUT', url, true);
    request.send(blob);
};

/**
 * Abort multipart upload
 */
S3MultiUpload.prototype.cancel = function() {
    var self = this;
    for (var i=0; i<this.uploadXHR.length; ++i) {
        this.uploadXHR[i].abort();
    }
    jQuery.post(self.SERVER_LOC, {
        command: 'abort',
        sendBackData: self.sendBackData
    }).done(function(data) {

    });
};

/**
 * Complete multipart upload
 */
S3MultiUpload.prototype.completeMultipartUpload = function() {
    var self = this;
    if (this.completed) return;
    this.completed=true;
    jQuery.post(self.SERVER_LOC, {
        command: 'complete',
        sendBackData: self.sendBackData
    }).done(function(data) {
        self.onUploadCompleted(data);
        document.getElementById("objectlocation").value = 'Object Location: '+data.locationinfo;
        
        
        
        
        
        // PATT New Addition - START
        //console.log(data);
        //console.log(data.locationinfo);
        console.log('Final');
        obj_s3link = data.locationinfo;
        //console.log({obj_key:obj_key, obj_size:obj_size, obj_type:obj_type, obj_s3link:obj_s3link});
        
        //create_mld_post_from_s3_data( data );
        
        //console.log({obj_key:obj_key});
        

        jQuery("#mdocs-name-single-file").val( obj_key );
        // After upload finishes, start the save file process.
        save_file();
        
        // PATT New Addition - END
        
        
        
    }).fail(function(jqXHR, textStatus, errorThrown) {
        self.onServerError('complete', jqXHR, textStatus, errorThrown);
    });
};

// PATT START
/**
 * Complete multipart upload
 */
// function create_mld_post_from_s3_data(  ) {
function save_s3_data_in_fdi_files() {	
	
	console.log( 'save_s3_data_in_fdi_files' );
	
	//obj_s3link = input.locationinfo;
	
	// Set variables from MLD upload modal
	let mduff = jQuery('#mdocs-upload-file-field').val();
	//let mdocs_name = jQuery('#mdocs-name').val(); // works for modal
	
	let mdocs_name = jQuery("#mdocs-name-single-file").val(); // updated for single file upload on Folder File Details
	
	let mdocs_tags = jQuery('#mdocs-tags').val();
	
	//let mdocs_type = $('input[name=mdocs-type]').val(); 
	
	
	//let mdocs_last_modified = $('input[name=mdocs-last-modified]').val();
	//let mdocs_version = $('input[name=mdocs-version]').val();
	//let mdocs_cat = $('input[name=mdocs-cat]').val(); 
	//let mdocs_social = $('input[name=mdocs-social]').val();
	//let mdocs_non_members = $('input[name=mdocs-non-members]').val();
	//let mdocs_index = $('input[name=mdocs-index]').val();
	//let mdocs_pname = $('input[name=mdocs-pname]').val();
	//let mdocs_file_status = $('input[name=mdocs-file-status]').val();
	//let mdocs_post_status = $('input[name=mdocs-post-status]').val();
	//let mdocs_add_contributors = $('input[name=mdocs-add-contributors]').val();
	//let mdocs_real_author = $('input[name=mdocs-real-author]').val();
	//let mdocs_categories = $('input[name=mdocs-categories]').val();
	//let mdocs_desc = $('input[name=mdocs-desc]').val();	

	//let mdocs_permalink = $('input[name=mdocs-permalink]').val();	
	
/*
	if( mdocs_cat == '' ) {
		let searchstr = "mdocs-cat=";
		let len = searchstr.length;
		let n = mdocs_permalink.indexOf(searchstr);
		
		mdocs_cat = mdocs_permalink.substring(n+len);
	}
*/
	
	let folderdocinfo_files_id = jQuery('input[name=folderdocinfo_files_id]').val();
	
	
	//console.log({obj_key:obj_key, obj_size:obj_size, obj_type:obj_type, obj_s3link:obj_s3link, obj_name:obj_name, mduff:mduff, folderdocinfo_files_id:folderdocinfo_files_id});
	
	
	
/*
	let data = {
		action: 'wppatt_create_mld_post',
		//input: input,
		obj_s3link: obj_s3link,
		obj_key: obj_key, 
		obj_size: obj_size,
		obj_type: obj_type,
		obj_name: obj_name,
		folderdocinfo_files_id: folderdocinfo_files_id,
		mduff: mduff,
		mdocs_name: mdocs_name,
		mdocs_tags: mdocs_tags,
		mdocs_type: mdocs_type,
		mdocs_last_modified: mdocs_last_modified,
		mdocs_version: mdocs_version,
		mdocs_cat: mdocs_cat,
		mdocs_social: mdocs_social,
		mdocs_non_members: mdocs_non_members,
		mdocs_index: mdocs_index,
		mdocs_pname: mdocs_pname,
		mdocs_file_status: mdocs_file_status,
		mdocs_post_status: mdocs_post_status,
		mdocs_add_contributors: mdocs_add_contributors,
		mdocs_real_author: mdocs_real_author,
		mdocs_categories: mdocs_categories,
		mdocs_desc: mdocs_desc
	};
*/
	
	let data = {
		action: 'wppatt_create_mld_post',
		obj_s3link: obj_s3link,
		obj_key: obj_key, 
		obj_size: obj_size,
		obj_type: obj_type,
		obj_name: obj_name,
		folderdocinfo_files_id: folderdocinfo_files_id,
		mduff: mduff
	};
	
	//console.log({data:data});
	
	return jQuery.post(wpsc_admin.ajax_url, data).done( function(response) {

        console.log('AJAX save_s3_data_in_fdi_files Successful');
        //console.log(response);               

    }).fail(function( response ) {
        console.log('AJAX Failed save_s3_data_in_fdi_files');
        //console.log(response);
    });
	
}

// PATT END

/**
 * Track progress, propagate event, and check for completion
 */
S3MultiUpload.prototype.updateProgress = function() {
    var total=0;
    var loaded=0;
    var byterate=0.0;
    var complete=1;
    for (var i=0; i<this.total.length; ++i) {
        loaded += +this.loaded[i] || 0;
        total += this.total[i];
        if (this.loaded[i]!=this.total[i])
        {
            // Only count byterate for active transfers
            byterate += +this.byterate[i] || 0;
            complete=0;
        }
    }
    if (complete) {
        this.completeMultipartUpload();
    }
    total=this.fileInfo.size;
    this.onProgressChanged(loaded, total, byterate, this.partsInProgress, this.partsCompleted);
};

// Overridable events: 

/**
 * Overrride this function to catch errors occured when communicating to your server
 *
 * @param {type} command Name of the command which failed,one of 'CreateMultipartUpload', 'SignUploadPart','CompleteMultipartUpload'
 * @param {type} jqXHR jQuery XHR
 * @param {type} textStatus resonse text status
 * @param {type} errorThrown the error thrown by the server
 */
S3MultiUpload.prototype.onServerError = function(command, jqXHR, textStatus, errorThrown) {};

/**
 * Overrride this function to catch errors occured when uploading to S3
 *
 * @param XMLHttpRequest xhr the XMLHttpRequest object
 */
S3MultiUpload.prototype.onS3UploadError = function(xhr) {};

/**
 * Override this function to show user update progress
 *
 * @param {type} uploadedSize is the total uploaded bytes
 * @param {type} totalSize the total size of the uploading file
 * @param {type} speed bytes per second
 */
S3MultiUpload.prototype.onProgressChanged = function(uploadedSize, totalSize, bitrate, partsInProgress, partsCompleted) {};

/**
 * Override this method to execute something when upload finishes
 *
 */
S3MultiUpload.prototype.onUploadCompleted = function(serverData) {};
/**
 * Override this method to execute something when part preparation is completed
 *
 */
S3MultiUpload.prototype.onPrepareCompleted = function() {};


jQuery(document).ready(function(){
	
	
	
	// Cover up color issue with MLD plugin
	jQuery("label[for='mdocs-name']").css( 'color', '#444' );
	
	// Adds 'required' to MLD Name field. 
	jQuery("label[for='mdocs-name']").append('<span style="color:red;">*</span>');
	
	
	//$(".modal-footer").append('<input type="submit" class="btn btn-primary" id="wppatt-mdocs-save-doc-btn" value="PATT Add/Update" />');
	
	
	//
	// Adjust modal window to look like Support Candy's.
	//
	jQuery("#mdocs-add-update-container > .page-header").attr( "id", "wpsc_popup_title" );
	jQuery("#mdocs-add-update .close").hide();
	jQuery("#mdocs-add-update .modal-footer").hide();	
	
	
	let style_override = '<style> .modal-body{ padding: 0px !important; } h1{ color: #fff !important; } .page-header{ margin: 0px !important; } .well{ background-color: #fff !important; border: none !important; border-radius: 0px !important; } .well-lg{ border-radius: 0px !important; padding: 15px !important; } #wppatt_popup_footer{ padding: 15px; background-color: #F6F6F6; } .patt-button{ width: 145px; height: 40px; border-radius: 0px !important; border: 0px; cursor: pointer;} .patt-button-close{ background-color:#ffffff !important;color:#000000 !important; } .patt-button-save{ background-color:#0473AA !important;color:#FFFFFF !important; }</style>';
	
	jQuery("#mdocs-add-update").append(style_override);
	
	
	// Inital set save button to hide
	jQuery("#wppatt-mdocs-save-doc-btn").hide();
	
	// Show save button after name change
	jQuery('#mdocs-name').keyup( function() {
		jQuery("#wppatt-mdocs-save-doc-btn").show();
	});
	
	// Show save button after tags change
	jQuery('#mdocs-tags').keyup( function() {
		jQuery("#wppatt-mdocs-save-doc-btn").show();
	});
	
	//
	jQuery('.patt-button-close').click(function(){
		console.log('close');
		//upload_alert_dismiss();	
		jQuery( '#upload-alert' ).hide();
		jQuery( '.progress-bar' ).hide();
		jQuery( '.progress-number' ).hide();
		
	});
	
	// Submit button for PATT MLD integration. Validates and initiates save. 
	jQuery("#wppatt-mdocs-save-doc-btn").click(function() {
		console.log('clickity click clack');
		let validation = true;
		
		let name = jQuery("#mdocs-name").val();
		name = name.trim();
		let tags = jQuery("#mdocs-tags").val();
		tags = tags.trim();
// 		let s3_upload_status = $("#result").html(); 
		let s3_upload_status = jQuery("#upload-alert").html(); 
		if( typeof s3_upload_status !== 'undefined' ) {
			s3_upload_status = s3_upload_status.trim();
		} else {
			s3_upload_status = 'no file uploaded';
		}
		
		
		//console.log({name:name, tags:tags, s3_upload_status:s3_upload_status});
		
		// Validation checks
		if( name == '' ) {
			validation = false;
			set_alert( 'danger', 'Submission Error: Name cannot be blank.' );
		}
		
		if( tags == '' ) {
			//validation = false;
			//set_alert( 'danger', 'Tags cannot be blank.' );
		}
		
		if( s3_upload_status != 'Upload successful.' ) {
			validation = false;
			console.log( 'validation false' );
			//console.log({s3_upload_status:s3_upload_status});
			
			
			if( s3_upload_status == 'no file uploaded' ) {
				// allows no file to be uploaded when updating name [title] and/or tags
				validation = true;
			} else if( s3_upload_status == 's3_upload_status' ) {
				set_alert( 'danger', 'Submission Error: S3 upload not complete.' );
			} else {
				//set_alert( 'danger', 'Submission Error: No file uploaded.' );
				set_alert( 'danger', 'Submission Error: File not uploaded.' );
			}
			
/*
			if( s3_upload_status == 's3_upload_status' ) {
				set_alert( 'danger', 'Submission Error: S3 upload not complete.' );
			} else {
				//set_alert( 'danger', 'Submission Error: No file uploaded.' );
				set_alert( 'danger', 'Submission Error: File not uploaded.' );
			}
*/
			
			
		}
		
		// Submit
		if( validation ) {
			console.log('Validated. Do it.');
			
// 			$.when( create_mld_post_from_s3_data() ).then( console.log( 'This is it.') ); //location.reload()
 			jQuery.when( create_mld_post_from_s3_data() ).then( location.reload() ); // working
//			$.when( create_mld_post_from_s3_data() ).then( console.log( 'finished create_mld_post_from_s3_data' ) ); 
			
			//create_mld_post_from_s3_data();
			//mdocs_modal_close();
			//setTimeout( location.reload() , 2200);
		}
		
	});
	

	


  
}); // jquery doc ready



// New Function for Single file upload
// Submit button to save S3 data into folderdocinfo_files
function save_file() {
	console.log('clickity click');
	let validation = true;
	
	let name = jQuery("#mdocs-name-single-file").val();
	name = name.trim();
	//let tags = jQuery("#mdocs-tags").val();
	//tags = tags.trim();

	let s3_upload_status = jQuery("#upload-alert").html(); 
	if( typeof s3_upload_status !== 'undefined' ) {
		s3_upload_status = s3_upload_status.trim();
	} else {
		s3_upload_status = 'no file uploaded';
	}
	
	//console.log({name:name, tags:tags, s3_upload_status:s3_upload_status});
	
	// Validation checks
	if( name == '' ) {
		validation = false;
		set_alert( 'danger', 'Submission Error: Name cannot be blank.' );
	}
	
/*
	if( tags == '' ) {
		//validation = false;
		//set_alert( 'danger', 'Tags cannot be blank.' );
	}
*/
	
	if( s3_upload_status != 'Upload successful.' ) {
		validation = false;
		console.log( 'validation false' );
		//console.log({s3_upload_status:s3_upload_status});
		
		
		if( s3_upload_status == 'no file uploaded' ) {
			// allows no file to be uploaded when updating name [title] and/or tags
			validation = true;
		} else if( s3_upload_status == 's3_upload_status' ) {
			set_alert( 'danger', 'Submission Error: S3 upload not complete.' );
		} else {
			//set_alert( 'danger', 'Submission Error: No file uploaded.' );
			set_alert( 'danger', 'Submission Error: File not uploaded.' );
		}
		
	}
	
	// Submit
	if( validation ) {
		console.log('Validated. Do it.');
		
			jQuery.when( save_s3_data_in_fdi_files() ).then( function() {console.log('we here, we done.'); location.reload();} ); // working
			//create_mld_post_from_s3_data
	}
	
}






// Simple hash function based on java's. Used for set_alert.
String.prototype.hashCode = function(){
    var hash = 0;
    for (var i = 0; i < this.length; i++) {
        var character = this.charCodeAt(i);
        hash = ((hash<<5)-hash)+character;
        hash = hash & hash; // Convert to 32bit integer
    }
    return hash;
}


// Sets an error message notificaiton
function set_alert( type, message ) {
	
	let alert_style = '';
	let hash = message.hashCode();
	//console.log({hash:hash});
	
	switch( type ) {
		case 'success':
			alert_style = 'alert-success';		
			break;
		case 'warning':
			alert_style = 'alert-warning';
			break;
		case 'danger':
			alert_style = 'alert-danger';
			break;		
	}
	
	// ID for modal window // Removed Jan 2021
	//jQuery('#alert_status_modal').show();
	//jQuery('#alert_status_modal').append('<div id="alert-alert" class=" alert '+alert_style+'">'+message+'</div>'); 
	//jQuery('#alert_status_modal').addClass('alert_spacing');
	
	jQuery('#alert_status_filefolder').show();
	jQuery('#alert_status_filefolder').append('<div id="alert-alert" class=" alert '+alert_style+'">'+message+'</div>'); 
	jQuery('#alert_status_filefolder').addClass('alert_spacing');
	
	alert_dismiss();
}

// Sets the time for dismissing the error notification
function alert_dismiss(  ) {
// 		setTimeout(function(){ jQuery('#alert_status').fadeOut(1000); }, 9000);	
	//setTimeout( function(){ jQuery( '#alert-alert' ).fadeOut( 1000 ); jQuery( '#alert-alert' ).remove(); }, 7000 );	
	setTimeout( function(){ jQuery( '#alert-alert' ).fadeOut( 1000 ); }, 9000 );	
}

// Upload Notification
function set_upload_notification( type, message ) {
	
	let alert_style = '';
	
	switch( type ) {
		case 'success':
			alert_style = 'alert-success';		
			break;
		case 'warning':
			alert_style = 'alert-warning';
			break;
		case 'danger':
			alert_style = 'alert-danger';
			break;		
	}
	jQuery('#upload_alert_status_modal').show();
	jQuery('#upload_alert_status_modal').html('<div id="upload-alert' + '" class=" alert '+alert_style+'">'+message+'</div>'); //badge badge-danger
	//jQuery('#alert_status_modal').append('<div id="alert-' + hash + '" class=" alert '+alert_style+'">'+message+'</div>'); // shows more notificaitons than desired. 
	jQuery('#upload_alert_status_modal').addClass('alert_spacing');
	
	//upload_alert_dismiss( timeout );
}

function upload_alert_dismiss( ) {
	
	setTimeout( function(){ jQuery( '#upload-alert' ).fadeOut( 1000 ); }, 1000 ); 
}



