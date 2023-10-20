<?php 
/**
 * Plugin Name: pattracking
 * Description: add-on to the support candy plugin specifically for the EPA Paper Asset Tracking Tool
 * Version: 0.3.11
 * Requires at least: 4.4
 * Tested up to: 5.3
 * Text Domain: pattracking
 * Domain Path: /lang
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'Patt_Tracking' ) ) :
 
  final class Patt_Tracking {
  
      
    public $version    = '0.3.11';
    public $db_version = '2.0';
    
    public function __construct() {
        
        // define global constants
        $this->define_constants();
        
        // Include required files and classes
        $this->includes();

        add_action( 'init', array($this,'load_textdomain') );
        
        /*
         * Cron setup
         */
        $cron_job_schedule = get_option('wppatt_cron_job_schedule_setup');
        if($cron_job_schedule) {
          add_filter('cron_schedules',array( $this, 'wppatt_cron_schedule'));
          if (!wp_next_scheduled('wppatt_cron_job_schedules')) {
              wp_schedule_event(time(), 'wppatt5min', 'wppatt_cron_job_schedules');
          }

          include( wppatt_ABSPATH.'includes/class-wp-cron.php' );
          $cron=new wppattWPCron();
          add_action( 'wppatt_cron_job_schedules', array( $cron, 'wppatt_cron_job'));
        }   
        
        /**
         * Attachment restructure
         */
        $restuct_attach = get_option('wppatt_restructured_attach_completed', 0);
        if( !$restuct_attach ){
          if (! wp_next_scheduled ( 'wppatt_attachment_restructure')) {
            wp_schedule_event( time(), 'hourly', 'wppatt_attachment_restructure');
          }
        }
        
    }
    
    function define_constants() {
        $this->define('WPPATT_STORE_URL', 'https://supportcandy.net');
        $this->define('WPPATT_PLUGIN_FILE', __FILE__);
        $this->define('WPPATT_ABSPATH', dirname(__FILE__) . '/');
        $this->define('WPPATT_PLUGIN_URL', plugin_dir_url( __FILE__ ));
        $this->define('WPPATT_PLUGIN_BASENAME', plugin_basename(__FILE__));
        $this->define('WPPATT_VERSION', $this->version);
        $this->define('WPPATT_DB_VERSION', $this->db_version);
        $this->define('WPPATT_EXT_SHIPPING_TERM', 'external');
        $this->define('WPPATT_EXT_SHIPPING_TERM_R3', 'r3 external');
				
		$upload_dir = wp_upload_dir();
        $this->define('WPPATT_UPLOADS', trailingslashit( $upload_dir['basedir'] ) );
        $this->define('WPPATT_UPLOADS_URL', trailingslashit( $upload_dir['baseurl'] ) );
        
    }
    
    function load_textdomain(){
        $locale = apply_filters( 'plugin_locale', get_locale(), 'pattracking' );
        load_textdomain( 'pattracking', WP_LANG_DIR . '/supportcandy/supportcandy-' . $locale . '.mo' );
        load_plugin_textdomain( 'pattracking', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );
    }
    
    public function includes() {
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-abstraction.php' );
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-custom-function.php' );
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-hooks-filters.php' );
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-install.php' );
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-ajax.php' );
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-functions.php' );
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-actions.php' );
        include_once( WPPATT_ABSPATH . 'includes/rest_api/class-rest-child.php' );
        include_once( WPPATT_ABSPATH . 'includes/admin/tickets/ticket_list/filter_get_ticket_list.php' ); 
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-loc-background-processing.php' );
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-eidw-background-processing.php' );
        include_once( WPPATT_ABSPATH . 'includes/class-wppatt-sems-background-processing.php' );
        $frontend  = new wppatt_Functions();
        // Add PATT Query Shortcode
        //add_shortcode('wppattquery', array($frontend, 'get_id_details'));        
        // Add Shipping CRON
        add_action( 'wppatt_shipping_cron', array($frontend, 'wppatt_shipping_cron_schedule'));
        // Add Recall Shipping CRON
        add_action( 'wppatt_shipping_cron_recall', array($frontend, 'wppatt_recall_shipping_status_schedule'));
        // Add Recall Reminder Email & PM Notification CRON
        add_action( 'wppatt_recall_reminder_cron', array($frontend, 'wppatt_recall_reminder_email_cron_schedule'));
        // Add Program Office/ Record Schedule CRON
        add_action( 'wppatt_po_rs_cron', array($frontend, 'wppatt_po_rs_cron_schedule'));
      	// Add Program Office/ Record Schedule Keyword CRON
        add_action( 'wppatt_po_rs_keyword_cron', array($frontend, 'wppatt_po_rs_keyword_cron_schedule'));
        // Add User Cleanup CRON
        add_action( 'wppatt_user_cleanup_cron', array($frontend, 'wppatt_user_cleanup_schedule'));
        // Add Email Cleanup CRON
        add_action( 'wppatt_email_cleanup_cron', array($frontend, 'wppatt_email_cleanup_schedule'));
        // Add ECMS/SEMS Status CRON
        add_action( 'wppatt_ecms_sems_status_cron', array($frontend, 'wppatt_ecms_sems_status_cron_schedule'));
        
        // Add S3 Cleanup CRON
        add_action( 'wppatt_s3_cleanup_cron', array($frontend, 'wppatt_s3_cleanup_cron_schedule'));

        // Add Request/Box Timestamp CRON
        add_action( 'wppatt_timestamp_reporting_request_box_cron', array($frontend, 'wppatt_timestamp_reporting_request_box_cron_schedule'));
        
        // Add Folder File/Recall/Decline Timestamp CRON
        add_action( 'wppatt_timestamp_reporting_folderfile_recall_decline_cron', array($frontend, 'wppatt_timestamp_reporting_folderfile_recall_decline_schedule'));
        
        // Add Decline Reminder Email & PM Notification CRON // No longer used. All actions done in wppatt_return_shipping_status_schedule
        //add_action( 'wppatt_decline_reminder_cron', array($frontend, 'wppatt_decline_reminder_email_cron_schedule'));

        // Add PATT/ARMS Tranfer Monitoring CRON
        add_action( 'wppatt_patt_arms_monitor_cron', array($frontend, 'wppatt_patt_arms_monitor_cron_schedule'));
      
        // add menu item for specialty upload section - BATCH UPLOADER
        add_action( 'admin_menu', 'wpdocs_register_my_custom_menu_page' );
		function wpdocs_register_my_custom_menu_page() {
		    add_menu_page(
		        __( 'Batch Uploader', 'supportcandy' ),
		        'Batch Uploader',
		        'manage_options',
		        'batch-uploader',
		        'batch_uploader_page',
		        'dashicons-images-alt2',
		        0
		    );
          
          add_menu_page(
              __( 'PATT Transfer', 'supportcandy' ),
              'PATT Transfer',
              'manage_options',
              'patt-transfer',
              'patt_transfer_init_page',
              'dashicons-randomize',
              6
          );
          
		}
        
        // Add Return / Decline Shipping CRON
        add_action( 'wppatt_shipping_cron_return', array($frontend, 'wppatt_return_shipping_status_schedule'));

        
        // Add EIDW CRON
        add_action( 'wppatt_eidw_cron', array($frontend, 'wppatt_eidw_cron_schedule')); 
        // Add Recycle Bin Cron
        add_action( 'wppatt_recycle_bin_cron', array($frontend, 'wppatt_recycle_bin_cron_schedule')); 
      	// Add Patt Transfer Recycle Bin Cron
        add_action( 'wppatt_transfer_recycle_bin_cron', array($frontend, 'wppatt_transfer_recycle_bin_cron_schedule')); 
      	// Add Ticket List Cleanup
        add_action( 'wppatt_ticket_list_cleanup_cron', array($frontend, 'wppatt_ticket_list_cleanup_cron_schedule'));  
        // Add ECMS Ingestion Cron
        add_action( 'wppatt_ecms_ingestion_cron', array($frontend, 'wppatt_ecms_ingestion_cron_schedule')); 
        // Message Cleanup CRON
        add_action( 'wppatt_private_message_cleanup_cron', array($frontend, 'wppatt_private_message_cleanup_cron_schedule'));

        // Add Location Cleanup CRONS
        add_action( 'wppatt_e_location_cleanup_cron', array($frontend, 'wppatt_e_location_cleanup_schedule')); 
        add_action( 'wppatt_w_location_cleanup_cron', array($frontend, 'wppatt_w_location_cleanup_schedule')); 
        // Add Timestamp Cron for Qlik Reports
        add_action( 'wppatt_timestamp_reporting_cron', array($frontend, 'wppatt_timestamp_reporting_schedule'));         
        
        if ($this->is_request('admin')) {
          include_once( WPPATT_ABSPATH . 'includes/class-wppatt-admin.php' );
          
          update_option('wpsc_tl_agent_unresolve_statuses',array(3,4,670,5,63,64,672,671,65));
          update_option('wpsc_tl_customer_unresolve_statuses',array(3,4,670,5,63,64,672,671,65));

          update_option('wpsc_close_ticket_group',array(673,674,743,66,67,68,69));
          
          // PDF Label Add Button
          $backend  = new wppatt_Admin();
          add_action('wpsc_after_indidual_ticket_action_btn', array($backend, 'box_status_assignment_btnAfterClone'));
          add_action('wpsc_after_indidual_ticket_action_btn', array($backend, 'pallet_btnAfterClone'));
          add_action('wpsc_after_indidual_ticket_static_action_btn', array($backend, 'pdflabel_btnAfterClone'));
          add_action('wp_ajax_wpsc_get_pdf_label_field', array($backend, 'get_pdf_label_field'));
 
           // Add Location Instant CRON
          add_action( 'wp_ajax_wppatt_loc_instant', array( $backend, 'get_loc_instant_update')); 
          
          // Add EIDW Instant CRON
          add_action( 'wp_ajax_wppatt_eidw_instant', array( $backend, 'get_eidw_instant_update')); 
          
          // Add SEMS Instant CRON
          add_action( 'wp_ajax_wppatt_sems_instant', array( $backend, 'get_sems_instant_update')); 
          
          // Add Box Details to Request page
          add_action('wpsc_before_request_id', array($backend, 'request_boxes_BeforeRequestID'));

          // Add Pending Users on PATT Agents page
          add_action('wpsc_pending_support_agents', array($backend, 'pending_support_agents'));
 
          // Hide long logs on Request page
          add_action('wpsc_after_individual_ticket', array($backend, 'request_hide_logs'));
          
          // Add Shipping Widget
          add_action( 'wpsc_after_ticket_widget', array($backend, 'shipping_widget'));
          add_action('wp_ajax_wpsc_get_shipping_details', array($backend, 'get_shipping_details'));
          
          // Add Document Relationship Modal on Folder File Details page
          add_action('wp_ajax_wpsc_get_doc_relationship', array($backend, 'get_doc_relationship'));	
          
          // Add Inventory Modal
          add_action('wp_ajax_wpsc_get_inventory_editor', array($backend, 'get_inventory_editor'));

          // Add Digitization Switch Modal
          add_action('wp_ajax_wpsc_get_digitization_editor_final', array($backend, 'get_digitization_editor'));

          // Add Folder/File Editor Modal
          add_action('wp_ajax_wpsc_get_folderfile_editor', array($backend, 'get_folder_file_editor'));

          // Add Box Editor Modal
          add_action('wp_ajax_wpsc_get_box_editor', array($backend, 'get_box_editor'));
          
          // Add EPA Contact Editor Modal
          add_action('wp_ajax_wpsc_get_epa_contact_editor', array($backend,'get_epa_contact_editor'));

          // Add RFID Reader Modal
          add_action('wp_ajax_wpsc_get_clear_rfid', array($backend, 'get_clear_rfid'));
          add_action('wp_ajax_wpsc_get_rfid_box_editor', array($backend, 'get_rfid_box_editor'));
          
          // Add Shipping Modal to Shipping Status Editor
          add_action('wp_ajax_wpsc_get_shipping_sse', array($backend, 'get_alert_replacement'));	

          // Add Destruction Completed Modal to Box Dashboard	
          add_action('wp_ajax_wpsc_get_destruction_completed_b', array($backend, 'get_alert_replacement'));	
          	
          // Add Unathorized Destruction Modal to Box Details	
          add_action('wp_ajax_wpsc_get_unauthorized_destruction_bd', array($backend, 'get_unauthorized_destruction'));	
          	
          // Add Freeze Modal to Box Details	
          add_action('wp_ajax_wpsc_get_freeze_bd', array($backend, 'get_alert_replacement'));	

          // Add Damaged Modal to Box Details	
          add_action('wp_ajax_wpsc_get_damaged_bd', array($backend, 'get_alert_replacement'));	
          	
          // Add Validate Modal to Box Details	
          add_action('wp_ajax_wpsc_get_validate_bd', array($backend, 'get_alert_replacement'));	
            	
          // Add Validate Modal on Folder File Dashboard	
          add_action('wp_ajax_wpsc_get_validate_ff', array($backend, 'get_alert_replacement'));	
    	  
    	  // Add Re-Scan Modal on Folder File Dashboard	
          add_action('wp_ajax_wpsc_get_rescan_ff', array($backend, 'get_alert_replacement'));	
    	
          // Add Freeze Modal on Folder File Dashboard	
          add_action('wp_ajax_wpsc_get_freeze_ff', array($backend, 'get_alert_replacement'));	

          // Add Damaged Modal on Folder File Dashboard	
          add_action('wp_ajax_wpsc_get_damaged_ff', array($backend, 'get_alert_replacement'));	
          	          	
          // Add Unauthorized Destruction Modal on Folder File Dashboard	
          add_action('wp_ajax_wpsc_unauthorized_destruction_ff', array($backend, 'get_unauthorized_destruction'));	
          
          // Add Damaged to Folder File Details	
          add_action('wp_ajax_wpsc_get_damaged_ffd', array($backend, 'get_alert_replacement'));
          	
          // Add Validation Rescan to Folder File Details	
          add_action('wp_ajax_wpsc_get_rescan_ffd', array($backend, 'get_alert_replacement'));
          
          // Add Validation Modal to Folder File Details	
          add_action('wp_ajax_wpsc_get_validate_ffd', array($backend, 'get_alert_replacement'));	
          	         	
          // Add Unauthorized Destruction to Folder File Details	
          add_action('wp_ajax_wpsc_unauthorized_destruction_ffd', array($backend, 'get_unauthorized_destruction'));	

          // Add Unauthorized Destruction to Folder File Details	
          add_action('wp_ajax_wpsc_user_edit_ffd', array($backend, 'get_bulk_user_edit'));	
          
          // Add Freeze to Folder File Details	
          add_action('wp_ajax_wpsc_get_freeze_ffd', array($backend, 'get_alert_replacement'));
          
          // Add Validate Modal on Folder File Dashboard	
          add_action('wp_ajax_wpsc_delete_request', array($backend, 'get_alert_replacement'));	

          // Add Help Alert Modal	
          add_action('wp_ajax_wpsc_help_alert', array($backend, 'get_help_alert'));
          
          // Disable Show Agent Settings Button
          add_action('wpsc_show_agent_setting_button',false);
          
          // Add Recall Search ID functionality  
          add_action('wp_ajax_wppatt_recall_search_id', array($backend, 'recall_search_for_id'));
          
          // Add Recall Submit  
          add_action('wp_ajax_wppatt_recall_submit', array($backend, 'recall_submit'));
          
          // Add Recall Edit Shipping Modal  
          add_action('wp_ajax_wppatt_recall_get_shipping', array($backend, 'recall_get_shipping'));
          
          // Add Recall Edit Requestor Modal 
          add_action('wp_ajax_wppatt_recall_get_requestor', array($backend, 'recall_get_requestor'));
          
          // Add Recall Edit Request Date Modal 
          add_action('wp_ajax_wppatt_recall_get_date', array($backend, 'recall_get_date'));
          
          // Add Recall Edit Status Change Modal - Feature removed as state machine is automatic, not manual. 
          //add_action('wp_ajax_wppatt_recall_status_change', array($backend, 'recall_status_change'));
          
          // Add Recall Edit Shipping Multiple Items Modal 
          add_action('wp_ajax_wppatt_recall_shipping_change', array($backend, 'recall_edit_multi_shipping'));
          
          // Add Recall Setting Pill 
          add_action('wpsc_after_setting_pills', array($frontend, 'recall_settings_pill'));
          
          // Add Recall Get Recall Settings Pill 
          add_action('wp_ajax_wppatt_get_recall_settings', array($backend, 'get_recall_settings'));
          
          // Add Recall Set Recall Settings Pill 
          //add_action('wp_ajax_wppatt_set_recall_settings', array($backend, 'set_recall_settings'));
          
          // Add Recall Status Settings Edit Modal
          add_action('wp_ajax_wppatt_get_edit_recall_status', array($backend, 'get_edit_recall_status')); 
          
          // Add Set Recall Status Settings via Modal
          add_action('wp_ajax_wppatt_set_recall_status', array($backend, 'set_recall_status'));
          
          // Add set ticket status
          add_action('wp_ajax_wppatt_set_ticket_status', array($backend, 'set_ticket_status')); 
                    
          // Add Return Edit Returned 
          //add_action('wp_ajax_wppatt_initiate_return', array($backend, 'ticket_initiate_return'));
          
          // Add Recall Cancel Modal 
          add_action('wp_ajax_wppatt_recall_cancel', array($backend, 'recall_cancel')); 
          
          // Add Recall Cancel Modal 
          add_action('wp_ajax_wppatt_recall_approve_deny', array($backend, 'recall_approve_deny')); 
          
          // Add Recall threaded comment edit functionality  
          add_action('wp_ajax_wppatt_recall_get_edit_thread', array($backend, 'recall_get_edit_thread'));
          
          // Add Return Submit
          add_action('wp_ajax_wppatt_return_submit', array($backend, 'return_submit')); 
          
		  // Add Return Setting Pill 
          add_action('wpsc_after_setting_pills', array($frontend, 'return_settings_pill'));
          
          // Add Return Get Recall Settings Pill 
          add_action('wp_ajax_wppatt_get_return_settings', array($backend, 'get_return_settings'));
          
          // Add Return Status Settings Edit Modal
          add_action('wp_ajax_wppatt_get_edit_return_status', array($backend, 'get_edit_return_status')); 
          
          // Add Set Return Status Settings via Modal
          add_action('wp_ajax_wppatt_set_return_status', array($backend, 'set_return_status'));   
          
          // Add Return Cancel Modal 
          add_action('wp_ajax_wppatt_return_cancel', array($backend, 'return_cancel')); 
          
           // Add Return Expiration Extension Modal 
          add_action('wp_ajax_wppatt_return_extend_expiration', array($backend, 'return_extend_expiration')); 
          
		      // Add Box Status Setting Pill 
          add_action('wpsc_after_setting_pills', array($frontend, 'box_settings_pill'));
          
          // Add Box Status Get Settings Panel 
          add_action('wp_ajax_wppatt_get_box_settings', array($backend, 'get_box_settings'));
          
          // Add Box Status Settings Edit Modal
          add_action('wp_ajax_wppatt_get_edit_box_status', array($backend, 'get_edit_box_status')); 
          
          // Add Set Box Status Settings via Modal
          add_action('wp_ajax_wppatt_set_box_status', array($backend, 'set_box_status')); 
		  
		  // Add Edit Shipping Modal 
          add_action('wp_ajax_wppatt_change_shipping', array($backend, 'change_shipping'));
          
		  // Add Assign Agents Modal 
          add_action('wp_ajax_wppatt_assign_agents', array($backend, 'edit_assign_agents'));
          
		  // Add Change Box Status Modal 
          add_action('wp_ajax_wppatt_change_box_status', array($backend, 'change_box_status'));    
          
		  // Add Pallet Assignment Modal 
          add_action('wp_ajax_wppatt_set_pallet_assignment', array($backend, 'set_pallet_assignment'));    
          
          // Update s3 Details
          add_action('wp_ajax_wppatt_update_s3_details', array($backend, 'update_s3_details'));
		  
		  // Add hook actions which supliment the MLD plugin
		  //include WPPATT_ABSPATH . 'includes/admin/pages/scripts/mld_patt_hooks.php';
		  
          // Set Barcode Scanning Page
          add_action( 'wpsc_add_admin_page', 'epa_admin_menu_items');
          
          // Add Admin/Manager Page
          add_action( 'wpsc_add_admin_manager_page', 'epa_admin_manager_menu_items');
          
          // Add threaded comment to Recall description comments 
          add_action('wp_ajax_wppatt_recall_threaded_comment_reply', array($backend, 'recall_threaded_comment_reply')); 
          
          // Add threaded note to Recall description comments 
          add_action('wp_ajax_wppatt_recall_threaded_comment_note', array($backend, 'recall_threaded_comment_note')); 
          
          // Add ECMS deletion request to Folder File Details page
          add_action('wp_ajax_wpsc_ecms_delete_request', array($backend, 'ecms_deletion_request'));	
          
          // Adds Digitization Center Location for User Profiles and allows saving.
          add_action( 'show_user_profile', array($backend, 'extra_user_profile_fields' ));
          add_action( 'personal_options_update', array($backend,'save_extra_user_profile_fields' ));
		  add_action( 'edit_user_profile_update', array($backend,'save_extra_user_profile_fields' ));
		  add_action( 'admin_notices', array($backend,'error_notice_no_digi_center_set' ));
		  
		  add_action( 'wp_ajax_wppatt_link_ticket_and_attachment', array($backend, 'link_ticket_attachment' ));
		  
		  // checks if boxes can change status based on request status
		  add_action('wp_ajax_wppatt_box_status_changable_due_to_request_status', array( $backend, 'determine_request_statuses' ) );
          
          
          function epa_admin_menu_items() {
            //add_submenu_page( 'wpsc-tickets', 'Barcode Scanning', 'Barcode Scanning', 'wpsc_agent', 'scanning', 'scanning_page' );


            add_submenu_page( '', 'To-Do List', 'To-Do List', 'wpsc_agent', 'todo-init', 'todo_init_page' );
            add_submenu_page( 'wpsc-tickets', 'To-Do List', 'To-Do List', 'wpsc_agent', 'todo', 'todo_page' );
            

            add_submenu_page( 'wpsc-tickets', 'RFID Settings', 'RFID Settings', 'wpsc_agent', 'rfid-settings', 'rfid_settings_page' );
            add_submenu_page( '', 'RFID Dashboard', 'RFID Dashboard', 'wpsc_agent', 'rfid-init', 'rfid_init_page' );
            add_submenu_page( '', 'RFID Dashboard', 'RFID Dashboard', 'wpsc_agent', 'rfid', 'rfid_page' );
            
            add_submenu_page( 'wpsc-tickets', 'RLO Groups', 'RLO Groups', 'wpsc_agent', 'groups', 'requester_groups_page' );         
            
            }
          
          function epa_admin_manager_menu_items(){
            // Only users with the role of Admin or Manager should see this link
            add_submenu_page( 'wpsc-tickets', 'Assign Staff', 'Assign Staff', 'wpsc_agent', 'assign-staff', 'assign_staff_dashboard' );
          }
            
          function scanning_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/scanning.php' );
            }
          
          
          
          // Set Box and File Dashboard and Details Pages
          add_action( 'wpsc_add_submenu_page', 'main_menu_items');

          function main_menu_items() {
            add_submenu_page( '', '', '', 'wpsc_agent', 'request_delete', 'request_delete_page' );
            add_submenu_page( '', '', '', 'wpsc_agent', 'request-delete-init', 'request_delete_init_page' );
            add_submenu_page( '', 'Box Dashboard', 'Box Dashboard', 'wpsc_agent', 'boxes-init', 'boxes_init_page' );
            add_submenu_page( 'wpsc-tickets', 'Box Dashboard', 'Box Dashboard', 'wpsc_agent', 'boxes', 'boxes_page' );
            add_submenu_page( '', 'Folder/File Dashboard', 'Folder/File Dashboard', 'wpsc_agent', 'folderfile-init', 'folderfile_init_page' );
            add_submenu_page( 'wpsc-tickets', 'Folder/File Dashboard', 'Folder/File Dashboard', 'wpsc_agent', 'folderfile', 'folderfile_page' );
            add_submenu_page( '', '', '', 'wpsc_agent', 'boxdetails', 'box_details' );
            add_submenu_page( '', '', '', 'wpsc_agent', 'boxdetails-init', 'boxdetails_init_page' );
            add_submenu_page( '', '', '', 'wpsc_agent', 'filedetails', 'file_details' );
            add_submenu_page( 'wpsc-tickets', 'Recall Dashboard', 'Recall Dashboard', 'wpsc_agent', 'recall', 'recall_page' ); 
            add_submenu_page( '', 'Recall Dashboard', 'Recall Dashboard', 'wpsc_agent', 'recall-init', 'recall_init_page' ); 
            add_submenu_page( '', '', '', 'wpsc_agent', 'recalldetails', 'recall_details' ); 
            add_submenu_page( '', '', '', 'wpsc_agent', 'recallcreate', 'recall_create' ); 
//             add_submenu_page( 'wpsc-tickets', 'Decline Dashboard', 'Decline Dashboard', 'wpsc_agent', 'return', 'return_page' ); 
            add_submenu_page( 'wpsc-tickets', 'Decline Dashboard', 'Decline Dashboard', 'wpsc_agent', 'decline', 'return_page' ); 
            add_submenu_page( '', 'Decline Dashboard', 'Decline Dashboard', 'wpsc_agent', 'decline-init', 'return_init_page' ); 
//             add_submenu_page( '', '', '', 'wpsc_agent', 'returndetails', 'return_details' ); 
            add_submenu_page( '', '', '', 'wpsc_agent', 'declinedetails', 'return_details' ); 
//             add_submenu_page( '', '', '', 'wpsc_agent', 'returncreate', 'return_create' ); 
            add_submenu_page( '', '', '', 'wpsc_agent', 'declinecreate', 'return_create' ); 
            add_submenu_page( 'wpsc-tickets', 'Shipping Status Editor', 'Shipping Status Editor', 'edit_posts', 'shipping', 'shipping_page' ); 
            add_submenu_page( '', 'Shipping Status Editor', 'Shipping Status Editor', 'edit_posts', 'shipping-init', 'shipping_init_page' ); 
            add_submenu_page( 'wpsc-tickets', 'Reports', 'Reports', 'wpsc_agent', 'qlik-report', 'custom_menu_item_redirect_external_link' );
            add_submenu_page( '', '', '', 'wpsc_agent', 'patttransferdetails', 'patt_transfer_details' );
            add_submenu_page( 'patt-transfer', 'Missed Files', 'Missed Files', 'wpsc_agent', 'missed-files', 'missed_files_page' );
            add_submenu_page( '', '', '', 'wpsc_agent', 'patt-transfer-delete-init', 'patt_transfer_delete_init_page' );
          }
            
			function custom_menu_item_redirect_external_link() {
		        $menu_redirect = isset($_GET['page']) ? $_GET['page'] : false;
		        if($menu_redirect == 'qlik-report' ) {
		            echo '<script>window.location.replace("https://qlikviz.epa.gov/sense/app/68e62aad-9f66-4be9-99a3-67068b43a663/sheet/874b516d-5221-4a1c-bcac-227b26978e87/state/analysis");</script>';
		            exit();
		        }
			}

            function shipping_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/shipping.php'
            );
            }

            function shipping_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/shipping_init.php'
            );
            }
            
            function request_delete_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/request_delete.php'
            );
            }
            
            function request_delete_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/request-delete-init.php'
            );
            }
            
            function boxes_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/boxes.php'
            );
            }
            
            function boxes_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/boxes-init.php'
            );
            }
            
            function folderfile_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/folderfile.php'
            );
            }

            function folderfile_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/folderfile-init.php'
            );
            }
            
            function rfid_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/rfid.php'
            );
            }
            
            function rfid_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/rfid-init.php'
            );
            }
            
            function rfid_settings_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/rfid-settings.php'
            );
            }
            
            function todo_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/todo-init.php'
            );
            }
            
            function requester_groups_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/requester_groups.php'
            );
            }
            
            function todo_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/todo.php'
            );
            }
            
            function box_details(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/box-details.php'
            );
            }
            
            function boxdetails_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/box-details-init.php'
            );
            }
            
            function file_details(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/folder-file-details.php'
            );
            }
          
          	function assign_staff_dashboard(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/assign-staff-init.php'
            );
            }
            
          	function patt_transfer_details(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/patt-transfer-details.php'
            );
            }
          
          	function missed_files_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/missed-files.php'
            );
            }
          
            function inventory_test(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/test_inventory.php'
            );
            }
            

            function recall_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/recall.php'
            );
            }
            
            function recall_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/recall-init.php'
            );
            }
            
            function recall_details(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/recall-details.php'
            );
            }
            
            function recall_create(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/recall-create.php'
            );
            }
            
            function return_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/return.php'
            );
            }
            
            function return_init_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/return-init.php'
            );
            }
            
            function return_details(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/return-details.php'
            );
            }
            
            function return_create(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/return-create.php'
            );
            }

            include_once( WPPATT_ABSPATH . 'includes/class-wppatt-request-approval-widget.php' );
            include_once( WPPATT_ABSPATH . 'includes/class-wppatt-new-request-litigation-letter.php' );
			
			function batch_uploader_page(){
            include_once( WPPATT_ABSPATH . 'includes/admin/pages/batch-uploader.php'
            );
            }
          
          	function patt_transfer_init_page(){
              include_once( WPPATT_ABSPATH . 'includes/admin/pages/patt-transfer-init.php'
              );
            }
          
          	function patt_transfer_delete_init_page(){
              include_once( WPPATT_ABSPATH . 'includes/admin/pages/patt-transfer-delete-init.php'
              );
            }
    
        }
        if ($this->is_request('frontend')) {
          include_once( WPPATT_ABSPATH . 'includes/class-wppatt-frontend.php' );
        }
        if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
          include_once( WPPATT_ABSPATH . 'includes/EDD_SL_Plugin_Updater.php' );
        }
        
    }
    
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }
    
    private function is_request($type) {
        switch ($type) {
            case 'admin' :
                return is_admin();
            case 'frontend' :
                return (!is_admin() || defined('DOING_AJAX') ) && !defined('DOING_CRON');
        }
    }
    
    function wppatt_cron_schedule($schedules){
        if(!isset($schedules["wppattsc5min"])){
            $schedules["wppatt5min"] = array(
                'interval' => 5*60,
                'display'  => 'Once every 5 minute',
            );
        }
        return $schedules;
    }
    
    

    
    
  }
  
endif;

new Patt_Tracking();
