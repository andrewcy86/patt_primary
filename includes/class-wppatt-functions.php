<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'wppatt_Functions' ) ) :
  
  final class wppatt_Functions {
      
    // Shortcode Query Component
    /*public function get_id_details(){    
    include WPPATT_ABSPATH . 'includes/admin/get_id_details.php';
    }*/
    
    // CRON for ECMS/SEMS Status
    public function wppatt_ecms_sems_status_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/ecms_sems_status_cron.php';
    }
    
    // CRON for s3 Cleanup
    public function wppatt_s3_cleanup_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/s3_cleanup_cron.php';
    }
    
    // CRON for shipping
    public function wppatt_shipping_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/shipping_cron.php';
    }
    
    // CRON for Recall Status Change from Shipping status
    public function wppatt_recall_shipping_status_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/recall_shipping_status_cron.php';
    }
    
    // CRON for Recall Expired Emails and PM Notifications
    public function wppatt_recall_reminder_email_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/recall_reminder_email_cron.php';
    }

    // CRON for Program Office and Record Schedule Update
    public function wppatt_po_rs_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/po_rs_update_cron.php';
    }
    
    // CRON for User Cleanup Update
    public function wppatt_user_cleanup_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/user_cleanup_cron.php';
    }

    // CRON for Email Cleanup Update
    public function wppatt_email_cleanup_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/user_email_cleanup_cron.php';
    }
    
/*
    // No longer used, as all Decline status changes & PM Notifications are done in return_shipping_status_cron.php below. 
    // CRON for Decline Emails and PM Notifications
    public function wppatt_decline_reminder_email_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/decline_reminder_email_cron.php';
    }
*/
    
    
    
    // CRON for Return (Decline) Status Change from Shipping status
    public function wppatt_return_shipping_status_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/return_shipping_status_cron.php';
    }

    // CRON for eidw
    public function wppatt_eidw_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/eidw_cron.php';
    }
    
    // CRON for message cleanup
    public function wppatt_private_message_cleanup_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/private_message_cleanup_cron.php';
    }   
    
    // CRON for recycle bin
    public function wppatt_recycle_bin_cron_schedule(){    
    include WPPATT_ABSPATH . 'includes/admin/recycle_bin_cron.php';
    }   
    
    // Review with Aaron
    public function wppatt_ecms_ingestion_cron_schedule() {
    include WPPATT_ABSPATH . 'includes/admin/ecms_ingestion_cron.php';
    }
    
    public function addStyles(){    
        wp_register_style('wpsc-bootstrap-css', WPSC_PLUGIN_URL.'asset/css/bootstrap-iso.css?version='.WPSC_VERSION );
        //wp_register_style('wpsc-fa-css', WPSC_PLUGIN_URL.'asset/lib/font-awesome/css/all.css?version='.WPSC_VERSION );
        wp_register_style('wpsc-jquery-ui', WPSC_PLUGIN_URL.'asset/css/jquery-ui.css?version='.WPSC_VERSION );
        wp_register_style('wpsc-public-css', WPSC_PLUGIN_URL . 'asset/css/public.css?version='.WPSC_VERSION );
        wp_register_style('wpsc-admin-css', WPSC_PLUGIN_URL . 'asset/css/admin.css?version='.WPSC_VERSION );
        wp_register_style('wpsc-modal-css', WPSC_PLUGIN_URL . 'asset/css/modal.css?version='.WPSC_VERSION );

        wp_enqueue_style('wpsc-bootstrap-css');
        //wp_enqueue_style('wpsc-fa-css');
        wp_enqueue_style('wpsc-jquery-ui');
        wp_enqueue_style('wpsc-public-css');
        wp_enqueue_style('wpsc-admin-css');
        wp_enqueue_style('wpsc-modal-css');
    }
    
    // Add settings pill for recall statuses 
    public function recall_settings_pill(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/recall_settings_pill.php';    
    }  
    
    // Add settings pill for return statuses 
    public function return_settings_pill(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/return_settings_pill.php';    
    }  
    
    // Add settings pill for box statuses 
    public function box_settings_pill(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/box_settings_pill.php';    
    }      
    
}  
endif;

$GLOBALS['wppattfunction'] =  new wppatt_Functions();
