
function wpsc_change_tab(e,content_id){
    jQuery('.tab').removeClass('active');
    jQuery(e).addClass('active');
    jQuery('.tab_content').removeClass('visible').addClass('hidden');
    jQuery('#'+content_id).removeClass('hidden').addClass('visible');
    return false;
}

function wpsc_get_approval_details(ticket_id){
    wpsc_modal_open('Associated Documents');
    let pid = jQuery('input[name=postid').val();
    let blpo = jQuery('input[name=box_list_path_orig').val();
    
    console.log({pid:pid, blpo:blpo});
    
    var data = {
        action: 'wpsc_get_approval_details',
        ticket_id: ticket_id,
        postid: pid,
        box_list_path_orig: blpo
    };
    jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
        jQuery('#approval_widget_form').remove();
        jQuery('#wpsc_popup_body').remove();
        jQuery('#wpsc_popup_footer').remove();
        var response = JSON.parse(response_str);
        jQuery('#wpsc_popup').append(response.content);

        // jQuery('#wpsc_cat_name').focus();
    });  
}

Dropzone.autoDiscover = false;
jQuery(document).ajaxComplete(function (event, xhr, settings) {

    var action_var = '';
    if( settings.data != '' && settings.data != undefined ) {
        var explode_str = settings.data.toString().split("&ticket_id");
        action_var = explode_str[0];
        console.log({action_var:action_var});
    }

    var requestFormDropzone = {
        url: "/file/post",
        autoProcessQueue: false,
        addRemoveLinks: true,
        uploadMultiple: true,
        acceptedFiles: '.pdf',
        // paramName: 'litigation_letter_files',
        init: function () {
            this.on( "maxfilesexceeded", function() {
                if ( this.files[1]!=null ){
                    this.removeFile( this.files[0] );
                }
            });
            this.on( "error", function( file, errorMessage ) {
	            alert( 'Error: ' + errorMessage + '\n\n Only ".pdf" files are allowed.');
                if ( !file.accepted ) this.removeFile( file );
            });
        }
    };
    
    var dropzoneOptionsBoxList = {
        url: "test.php",
        autoProcessQueue: false,
        addRemoveLinks: true,
        uploadMultiple: false,
        maxFiles: 1,
        acceptedFiles: '.xlsm',
        accept: function (file, done) {
            
            console.log( 'ACCEPT' );
            console.log( 'this.files.length: ' + this.files.length );
            
            
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
            //wpsc_spreadsheet_new_upload('attach_16','spreadsheet_attachment', file);
        },
        init: function () {
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
			    
			});
			
			
        }
    };
 

    if ( 'action=wpsc_get_approval_details' == action_var ) {

        // Destruction Authorization dropzone file for new request form and approval widget
        var destr_autho_dropzone = new Dropzone('#destr-autho-dropzone', requestFormDropzone );
        
        // Add Box List dropzone file for new request form and approval widget
        //var box_list_dropzone = new Dropzone('#add-box-list-dropzone', dropzoneOptionsBoxList );   

    }

    if ('action=wpsc_get_approval_details' == action_var || 'action=wpsc_tickets&setting_action=create_ticket' == settings.data) {

        // jQuery('#wpsc_frm_create_ticket .wpsc_form_field[data-fieldtype=dropdown]').removeClass('visible wpsc_required');

        // Litigation Letter dropzone file for new request form and approval widget
        var litigation_letter_dropzone = new Dropzone('#litigation-letter-dropzone', requestFormDropzone );   

        // Congressional dropzone file for new request form and approval widget
        var congressional_dropzone = new Dropzone('#congressional-dropzone', requestFormDropzone );

        // foia dropzone file for new request form and approval widget
        var foia_dropzone = new Dropzone('#foia-dropzone', requestFormDropzone );   
        
            }

});

function wpsc_set_approval_widget(){

    var destruct_auth_element = document.querySelector("#destr-autho-dropzone").dropzone.files;
    var litigation_letter_element = document.querySelector("#litigation-letter-dropzone").dropzone.files;
    var congressional_element = document.querySelector("#congressional-dropzone").dropzone.files;
    var foia_element = document.querySelector("#foia-dropzone").dropzone.files;

    var request_id =jQuery('#approval_widget_form input[name=request_id]').val();

    jQuery('.wpsc_submit_wait').show();
    var dataform = new FormData(jQuery('#approval_widget_form')[0]);
    
    if( destruct_auth_element.length > 0 ) {
        destruct_auth_element.forEach( function( _file ) {
            dataform.append( 'destruction_authorization_files[]', _file );
        } )
    }

    if( litigation_letter_element.length > 0 ) {
        litigation_letter_element.forEach( function( _file ) {
            dataform.append( 'litigation_letter_files[]', _file );
        } )
    }

    if( congressional_element.length > 0 ) {
        congressional_element.forEach( function( _file ) {
            dataform.append( 'congressional_files[]', _file );
        } )
    }

    if( foia_element.length > 0 ) {
        foia_element.forEach( function( _file ) {
            dataform.append( 'foia_files[]', _file );
        } )
    }

    jQuery.ajax({
        url: wpsc_admin.ajax_url,
        type: 'POST',
        data: dataform,
        processData: false,
        contentType: false
    })
    .done(function (response_str) {

        var response = JSON.parse(response_str);

        jQuery('.wpsc_submit_wait').hide();
        if (response.sucess_status=='1') {
        	jQuery('#wpsc_popup_footer').append("<div id='approval_widget_noti_message'><div class='alert-success alert'>" + response.messege + "</div></div>");

            if( response.destruction_approval_warning != '' ) {
                jQuery('#wpsc_popup_footer #approval_widget_noti_message').append("<div class='alert-warning alert'>" + response.destruction_approval_warning + "</div>");
            }
        }

        setTimeout(function(){ jQuery('#approval_widget_noti_message').slideUp('fast',function(){
            wpsc_get_approval_details(request_id);
            console.log({location:location});
			//jQuery("#wpsc_approval_widget").load(location + "#wpsc_approval_widget");
// 			jQuery("#wpsc_approval_widget").load("https://086.info/wordpress3/wp-admin/admin.php?page=wpsc-tickets&id=0000001" + "#wpsc_approval_widget");
            wpsc_modal_close();
            location.reload();
        }); }, 3000);

    });
}

function wpsc_delete_approval_widget(action, request_id, attachment_id){
    if(!confirm('Are you sure?')) return;
    var data = {
        action: action,
        request_id: request_id,
        attachment_id: attachment_id
    };
    jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
        var response = JSON.parse(response_str);

        if (response.sucess_status=='1') {
            jQuery('#wpsc_popup_footer').append("<div id='approval_widget_noti_message'><div class='alert-success alert'>" + response.messege + "</div></div>");
        }
        setTimeout(function(){ jQuery('#approval_widget_noti_message').slideUp('fast',function(){
            wpsc_get_approval_details(request_id);
            jQuery("#wpsc_approval_widget").load(location + "#wpsc_approval_widget");
        }); }, 5000);
    });
}

// Associated document upload tabs shows only if files uploaded option selected
jQuery(document).on('change' ,'#are-these-documents-used-for-the-following', function(){
    jQuery('#wpsc_frm_create_ticket .request-associated-docs').hide();
    var selected_val = jQuery(this).val();
    if( 'files_uploaded' == selected_val ) {
        jQuery('#wpsc_frm_create_ticket .request-associated-docs').show();
    }
} );


/*jQuery(document).ready(function(){

    jQuery(document).on('change' ,'#are-these-documents-used-for-the-following', function(){
        jQuery('.litigation-letter-dropzone').hide();
        jQuery('.congressional-dropzone').hide();
        jQuery('.foia-dropzone').hide();
        var selected_val = jQuery(this).val();
        if( 'Litigation' == selected_val ) {
            jQuery('.litigation-letter-dropzone').show();
        } else if( 'Congressional' == selected_val ) {
            jQuery('.congressional-dropzone').show();
        } else if( 'FOIA' == selected_val ) {
            jQuery('.foia-dropzone').show();
        }
    } );
   
});*/


