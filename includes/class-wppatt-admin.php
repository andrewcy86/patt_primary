<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'wppatt_Admin' ) ) :
  
  final class wppatt_Admin {
      
 // constructor
    public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'loadScripts' ) );
// 		add_action( 'init', array( $this, 'register_patt_taxonomies' ) );
		add_action( 'load_textdomain', array( $this, 'register_patt_taxonomies' ) );
		add_action( 'init', array( $this, 'register_patt_posttypes' ) );
		

    }
    
    // Load admin scripts
    public function loadScripts(){
        wp_enqueue_script('jquery');
        wp_enqueue_script( 'wppatt-disable-warnings', WPPATT_PLUGIN_URL . 'asset/js/disable-jquery-migrate-warnings.js', array(), time(), true ); // removed 13/15 jquery-migrate warnings from console. 
        
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.8.6');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-slider' );
        wp_enqueue_editor();
        //bootstrap
        wp_enqueue_style('wpsc-bootstrap-css', WPSC_PLUGIN_URL.'asset/css/bootstrap-iso.css?version='.WPSC_VERSION );
        wp_enqueue_style('wpsc-bootstrap-select-css', WPPATT_PLUGIN_URL . 'asset/css/bootstrap-select.min.css');
        //wp_enqueue_script( 'bootstrap-cdn-js', WPPATT_PLUGIN_URL . 'asset/js/bootstrap.min.js' );
        wp_enqueue_script( 'bootstrap-cdn-js', WPPATT_PLUGIN_URL . 'asset/js/bootstrap.min.js', array('jquery'), null, true );

        
        wp_enqueue_script( 'bootstrap-multiselect-js', WPPATT_PLUGIN_URL . 'asset/js/bootstrap-select.min.js' );
        //Font-Awesom
        //wp_enqueue_style('wpsc-fa-css', WPSC_PLUGIN_URL.'asset/lib/font-awesome/css/all.css?version='.WPSC_VERSION );
        wp_enqueue_style('wpsc-jquery-ui', WPSC_PLUGIN_URL.'asset/css/jquery-ui.css?version='.WPSC_VERSION );
        //admin scripts
        wp_enqueue_script('wpsc-admin', WPSC_PLUGIN_URL.'asset/js/admin.js?version='.WPSC_VERSION, array('jquery'), null, true);
        wp_enqueue_script('wpsc-public', WPSC_PLUGIN_URL.'asset/js/public.js?version='.WPSC_VERSION, array('jquery'), null, true);
        wp_enqueue_script('wpsc-modal', WPSC_PLUGIN_URL.'asset/js/modal.js?version='.WPSC_VERSION, array('jquery'), null, true);
        
        
        
//        wp_enqueue_script('wppatt-public', WPPATT_PLUGIN_URL.'asset/js/public.js', array('jquery'), null, true);
//        wp_enqueue_script('wppatt-modal', WPPATT_PLUGIN_URL.'asset/js/modal.js', array('jquery'), null, true);        

        wp_enqueue_style('wpsc-public-css', WPSC_PLUGIN_URL . 'asset/css/public.css?version='.WPSC_VERSION );
        wp_enqueue_style('wpsc-admin-css', WPSC_PLUGIN_URL . 'asset/css/admin.css?version='.WPSC_VERSION );
        wp_enqueue_style('wpsc-modal-css', WPSC_PLUGIN_URL . 'asset/css/modal.css?version='.WPSC_VERSION );
        //Datetime picker
        wp_enqueue_script('wpsc-dtp-js', WPSC_PLUGIN_URL.'asset/lib/datetime-picker/jquery-ui-timepicker-addon.js?version='.WPSC_VERSION, array('jquery'), null, true);
        wp_enqueue_style('wpsc-dtp-css', WPSC_PLUGIN_URL . 'asset/lib/datetime-picker/jquery-ui-timepicker-addon.css?version='.WPSC_VERSION );
      if(isset($_REQUEST['page'])) :
        //localize script
        $loading_html = '<div class="wpsc_loading_icon"><img src="'.WPSC_PLUGIN_URL.'asset/images/ajax-loader@2x.gif"></div>';
        $localize_script_data = apply_filters( 'wpsc_admin_localize_script', array(
            'ajax_url'             => admin_url( 'admin-ajax.php' ),
            'loading_html'         => $loading_html
        ));
        wp_localize_script( 'wpsc-admin', 'wpsc_admin', $localize_script_data );
      endif;
    }
    
    // Register custom taxonomies created by PATT
    public function register_patt_taxonomies() {
		
		//
		// Register wpsc_box_statuses taxonomy
		//
		if( !taxonomy_exists('wpsc_box_statuses') ) {
			$args = array(
				'public' => false,
				'rewrite' => false
			);
			register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
		}
		
		//
		// Add terms to taxonomy wpsc_box_statuses
		//
		$term = wp_insert_term( 'Pending', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 0);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Scanning Preparation', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 1);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Scanning/Digitization', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 2);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'QA/QC', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 3);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Digitized - Not Validated', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 4);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Ingestion', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 5);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		// 		$term = wp_insert_term( 'Completed', 'wpsc_box_statuses' );
		$term = wp_insert_term( 'Completed Permanent Records', 'wpsc_box_statuses', array( 'slug' => 'completed' ) );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 6);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Validation', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 7);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		//$term = wp_insert_term( 'Destruction Approval', 'wpsc_box_statuses' );
		$term = wp_insert_term( 'Destruction Approved', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 8);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Destruction of Source', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 9);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#d16464');
		}
		
		$term = wp_insert_term( 'Completed/Dispositioned', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 10);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Re-Scan', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 11);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Waiting/Shelved', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 12);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#843ddb');
		}
		
		$term = wp_insert_term( 'Waiting on RLO', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 13);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#843ddb');
		}
		
		$term = wp_insert_term( 'Cancelled', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 14);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#0c0000');
		}
		
		
		
		
		
		
		
		
		
		
		
		/*		// Removed
		$term = wp_insert_term( 'Dispositioned', 'wpsc_box_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
			add_term_meta ($term['term_id'], 'wpsc_box_status_load_order', 11);
			add_term_meta ($term['term_id'], 'wpsc_box_status_color', '#ffffff');
			add_term_meta ($term['term_id'], 'wpsc_box_status_background_color', '#dd9933');
		}
*/
		
		
		//
		// Register wppatt_recall_statuses taxonomy
		//
		if( !taxonomy_exists('wppatt_recall_statuses') ) {
			$args = array(
				'public' => false,
				'rewrite' => false
			);
			register_taxonomy( 'wppatt_recall_statuses', 'wpsc_ticket', $args );
		}
		
		//
		// Add terms to taxonomy wppatt_recall_statuses
		//
		$term = wp_insert_term( 'Recalled', 'wppatt_recall_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_load_order', 0);
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_background_color', '#48c957');
		}

		$term = wp_insert_term( 'Shipped', 'wppatt_recall_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_load_order', 3);
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_background_color', '#30d1c9');
		}
	
		$term = wp_insert_term( 'On Loan', 'wppatt_recall_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_load_order', 4);
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_background_color', '#dd9933');
		}

		$term = wp_insert_term( 'Shipped Back', 'wppatt_recall_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_load_order', 5);
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_background_color', '#30d1c9');
		}
	
		$term = wp_insert_term( 'Recall Complete', 'wppatt_recall_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_load_order', 6);
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_background_color', '#81d742');
		}
		
		$term = wp_insert_term( 'Recall Cancelled', 'wppatt_recall_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_load_order', 7);
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_background_color', '#000000');
		}
		
		$term = wp_insert_term( 'Recall Approved', 'wppatt_recall_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_load_order', 8);
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_background_color', '#81d742');
		}
		
		$term = wp_insert_term( 'Recall Denied', 'wppatt_recall_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_load_order', 9);
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_recall_status_background_color', '#000000');
		}

		
		//
		// Register wppatt_return_statuses taxonomy
		//
		if( !taxonomy_exists('wppatt_return_statuses') ) {
			$args = array(
				'public' => false,
				'rewrite' => false
			);
			register_taxonomy( 'wppatt_return_statuses', 'wpsc_ticket', $args );
		}
		
		//
		// Add terms to taxonomy wppatt_return_statuses
		//
		$term = wp_insert_term( 'Decline Initiated', 'wppatt_return_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_return_status_load_order', 0);
		  add_term_meta ($term['term_id'], 'wppatt_return_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_return_status_background_color', '#dd9933');
		}
		
		$term = wp_insert_term( 'Decline Shipped', 'wppatt_return_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  //$load_order = $wpdb->get_var("select max(meta_value) as load_order from {$wpdb->prefix}termmeta WHERE meta_key='wppatt_recall_status_load_order'");
		  add_term_meta ($term['term_id'], 'wppatt_return_status_load_order', 1);
		  add_term_meta ($term['term_id'], 'wppatt_return_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_return_status_background_color', '#30d1c9');
		}
		
		//$term = wp_insert_term( 'Decline Complete', 'wppatt_return_statuses' );
		$term = wp_insert_term( 'Received', 'wppatt_return_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  //$load_order = $wpdb->get_var("select max(meta_value) as load_order from {$wpdb->prefix}termmeta WHERE meta_key='wppatt_recall_status_load_order'");
		  add_term_meta ($term['term_id'], 'wppatt_return_status_load_order', 2);
		  add_term_meta ($term['term_id'], 'wppatt_return_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_return_status_background_color', '#cc0000');
		}
		
		$term = wp_insert_term( 'Decline Shipped Back', 'wppatt_return_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  //$load_order = $wpdb->get_var("select max(meta_value) as load_order from {$wpdb->prefix}termmeta WHERE meta_key='wppatt_recall_status_load_order'");
		  add_term_meta ($term['term_id'], 'wppatt_return_status_load_order', 3);
		  add_term_meta ($term['term_id'], 'wppatt_return_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_return_status_background_color', '#30d1c9');
		}

		$term = wp_insert_term( 'Decline Complete', 'wppatt_return_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  //$load_order = $wpdb->get_var("select max(meta_value) as load_order from {$wpdb->prefix}termmeta WHERE meta_key='wppatt_recall_status_load_order'");
		  add_term_meta ($term['term_id'], 'wppatt_return_status_load_order', 4);
		  add_term_meta ($term['term_id'], 'wppatt_return_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_return_status_background_color', '#30d1c9');
		}

		
		$term = wp_insert_term( 'Decline Cancelled', 'wppatt_return_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  //$load_order = $wpdb->get_var("select max(meta_value) as load_order from {$wpdb->prefix}termmeta WHERE meta_key='wppatt_recall_status_load_order'");
		  add_term_meta ($term['term_id'], 'wppatt_return_status_load_order', 5);
		  add_term_meta ($term['term_id'], 'wppatt_return_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_return_status_background_color', '#000000');
		}
		
		$term = wp_insert_term( 'Decline Expired', 'wppatt_return_statuses' );
		if (!is_wp_error($term) && isset($term['term_id'])) {
		  add_term_meta ($term['term_id'], 'wppatt_return_status_load_order', 6);
		  add_term_meta ($term['term_id'], 'wppatt_return_status_color', '#ffffff');
		  add_term_meta ($term['term_id'], 'wppatt_return_status_background_color', '#000000');
		}



		//
		// Register Return Reason Taxonomy
		//
		if( !taxonomy_exists('wppatt_return_reason') ) {
		
			$args = array(
				'public' => false,
				'rewrite' => false
			);
			register_taxonomy( 'wppatt_return_reason', 'wpsc_ticket', $args );
		
		}
		
		
		
		
		
		//
		// Register Box Status Taxonomy
		//
		if( !taxonomy_exists('wpsc_box_statuses') ) {
			$args = array(
				'public' => false,
				'rewrite' => false
			);
			register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );
		}
    }
    
    public function register_patt_posttypes() {
	    
		// Recall Threads post
		$args = array(
			'public'             => false,
			'rewrite'            => false
		);
// 		register_post_type( 'wpsc_ticket_thread', $args );
		register_post_type( 'wppatt_recall_thread', $args );
    }
    
/*
    public function extra_user_profile_fields( $user ) { ?>
	    <h3><?php _e("Extra profile information", "blank"); ?></h3>
	
	    <table class="form-table">
	    <tr>
	        <th><label for="address"><?php _e("Address"); ?></label></th>
	        <td>
	            <input type="text" name="address" id="address" value="<?php echo esc_attr( get_the_author_meta( 'address', $user->ID ) ); ?>" class="regular-text" /><br />
	            <span class="description"><?php _e("Please enter your address."); ?></span>
	        </td>
	    </tr>
	    <tr>
	        <th><label for="city"><?php _e("City"); ?></label></th>
	        <td>
	            <input type="text" name="city" id="city" value="<?php echo esc_attr( get_the_author_meta( 'city', $user->ID ) ); ?>" class="regular-text" /><br />
	            <span class="description"><?php _e("Please enter your city."); ?></span>
	        </td>
	    </tr>
	    <tr>
	    <th><label for="postalcode"><?php _e("Postal Code"); ?></label></th>
	        <td>
	            <input type="text" name="postalcode" id="postalcode" value="<?php echo esc_attr( get_the_author_meta( 'postalcode', $user->ID ) ); ?>" class="regular-text" /><br />
	            <span class="description"><?php _e("Please enter your postal code."); ?></span>
	        </td>
	    </tr>
	    </table>
	<?php 
	} 
*/
    
    // Added function to inject box status and assignment buttons
    public function box_status_assignment_btnAfterClone(){
    include WPPATT_ABSPATH . 'includes/admin/wppatt_get_box_status_assignment.php';    
    }
    
    // Added function to pallet buttons
    public function pallet_btnAfterClone(){
    include WPPATT_ABSPATH . 'includes/admin/wppatt_get_pallet_assignment.php';    
    }
    
    // Added function to inject label button
    public function pdflabel_btnAfterClone(){
    include WPPATT_ABSPATH . 'includes/admin/wppatt_get_pdflabel_file.php';    
    }
    
    public function request_boxes_BeforeRequestID(){
    include WPPATT_ABSPATH . 'includes/admin/wppatt_request_boxes.php';    
    }
 
    public function pending_support_agents(){
    include WPPATT_ABSPATH . 'includes/admin/wppatt_pending_agents.php';    
    }
    
    public function request_hide_logs(){
    include WPPATT_ABSPATH . 'includes/admin/wppatt_request_hide_logs.php';    
    }
    
    public function get_pdf_label_field(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_pdf_label_field.php';
    die();
    }
    
    // Added function to create a shipping ticket widget
    public function shipping_widget( $post_id ) {

		global $current_user, $wpscfunction,$wpdb;
		
		$ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '' ;
		$ticket_data = $wpscfunction->get_ticket($ticket_id);
		$status_id   	= $ticket_data['ticket_status'];
		$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');
	    $edit_btn_css = 'background-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_bg_color'].' !important;color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_text_color'].' !important;border-color:'.$wpsc_appearance_individual_ticket_page['wpsc_edit_btn_border_color'].'!important';
	
	    $get_shipping_count = $wpdb->get_var('SELECT COUNT(*) FROM ' .$wpdb->prefix .'wpsc_epa_shipping_tracking WHERE ticket_id = ' . $ticket_id );
	     
		//if ( ! $current_user->has_cap( 'wpsc_agent' ) ) {	// Only show widget for agents.
		//	return;
		//}
	
		//echo $status_id;
		
		//Remove ability to edit widget when active = 0
	    $is_active = Patt_Custom_Func::ticket_active( $ticket_id );
	    $new_request_tag = get_term_by('slug', 'open', 'wpsc_statuses'); //3
	    
	if ($status_id != $new_request_tag->term_id) {
	
		$ticket_widget_name = __( 'Shipping', 'supportcandy' );
	
		$wpsc_appearance_individual_ticket_page = get_option('wpsc_individual_ticket_page');
	
		echo '<div class="row" style="';
		echo 'background-color:' . $wpsc_appearance_individual_ticket_page[ 'wpsc_ticket_widgets_bg_color' ] . ' !important;';
		echo 'color:' . $wpsc_appearance_individual_ticket_page[ 'wpsc_ticket_widgets_text_color' ] . ' !important;';
		echo 'border-color:' . $wpsc_appearance_individual_ticket_page[ 'wpsc_ticket_widgets_border_color' ] . ' !important;';
		echo '">';
	
		echo '<h4 class="widget_header"> <i class="fa fa-truck"></i> ' . $ticket_widget_name;
		if($is_active == 1) {
		echo ' <button id="wpsc_individual_change_agent_fields" onclick="wpsc_get_shipping_details(' . $ticket_id .')" class="btn btn-sm wpsc_action_btn" style="' . $edit_btn_css . '" ><i class="fas fa-edit"></i></button></h4>';
		} else {
		echo ' </h4>';
		}
		echo '<hr style="margin-top: 4px; margin-bottom: 6px" class="widget_devider">';
		
	  if ($get_shipping_count > 0) {
	      
	    echo '<ul>';
	
	
	    $shipping_rows = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix .'wpsc_epa_shipping_tracking WHERE ticket_id = ' . $ticket_id . ' ORDER BY id DESC' );
	    
	    $i = 0;
	
	    foreach( $shipping_rows as $row) {
	
		    $tracking_num = $row->tracking_number;
		    $dhl_tracking_num = substr($tracking_num, 4);
		    $tracking_num_display = mb_strimwidth($tracking_num, 0, 25, "...");
		    $dhl_tracking_num_display = mb_strimwidth($dhl_tracking_num, 0, 25, "...");  
		    $company_name = $row->company_name;
		
		    if ($row->shipped == 1) {
		        $shipped_status = ' <i class="fa fa-check-circle" style="color:#008000;" title="Shipped"></i>';
		    } else {
		        $shipped_status = '';
		    }
		    
		    if ($row->delivered == 1) {
		        $delivered_status = ' <i class="fas fa-truck-loading" style="color:#008000;" title="Received"></i>';
		    } else {
		        $delivered_status = '';
		    }
			switch ($company_name) {
			    case "ups":
			        echo '<li><i class="fab fa-ups fa-lg"></i> <a href="'.Patt_Custom_Func::get_tracking_url($tracking_num_display).'" target="_blank">'. $tracking_num_display .'</a>' . $shipped_status . $delivered_status . '</li>';
			        break;
			    case "fedex":
			        echo '<li><i class="fab fa-fedex fa-lg"></i> <a href="'.Patt_Custom_Func::get_tracking_url($tracking_num_display).'" target="_blank">'. $tracking_num_display .'</a>' . $shipped_status . $delivered_status . '</li>';
			        break;
			    case "usps":
			        echo '<li><i class="fab fa-usps fa-lg"></i> <a href="'.Patt_Custom_Func::get_tracking_url($tracking_num_display).'" target="_blank">'. $tracking_num_display .'</a>' . $shipped_status . $delivered_status . '</li>';
			        break;
			    case "dhl":
			        echo '<li><i class="fab fa-dhl fa-lg"></i> <a href="'.Patt_Custom_Func::get_tracking_url($tracking_num_display).'" target="_blank">'. $dhl_tracking_num_display .'</a>' . $shipped_status . $delivered_status . '</li>';
			        break;
			    default:
			        echo $tracking_num_display;
			
			}
		    if (++$i == 10) break;
	    }
	    echo '</ul>';
	    if ($get_shipping_count > 10) {echo '... <i class="fas fa-plus-square"></i> <a href="#" onclick="wpsc_get_shipping_details(' . $ticket_id . ')">[View More]</a><br /><br />';}
	  } else {
	    echo '<strong>No Tracking Numbers Assigned.</strong><br /><br />';
	  }
	    ?>

	<script>
		function wpsc_get_shipping_details(ticket_id){
		  wpsc_modal_open('Shipping Details');
		  var data = {
		    action: 'wpsc_get_shipping_details',
		    ticket_id: ticket_id
		  };
		  jQuery.post(wpsc_admin.ajax_url, data, function(response_str) {
		    var response = JSON.parse(response_str);
		    jQuery('#wpsc_popup_body').html(response.body);
		    jQuery('#wpsc_popup_footer').html(response.footer);
		    jQuery('#wpsc_cat_name').focus();
		  });  
		}
	</script>
	
	</div>
	<?php
	}
}


    // CRON for location instant
    public function get_loc_instant_update(){
    include WPPATT_ABSPATH . 'includes/ajax/get_location_instant_update.php';
    die();
    }   
    
    // CRON for eidw instant
    public function get_eidw_instant_update(){
    include WPPATT_ABSPATH . 'includes/ajax/get_eidw_instant_update.php';
    die();
    }   

    // CRON for sems instant
    public function get_sems_instant_update(){
    include WPPATT_ABSPATH . 'includes/ajax/get_sems_instant_update.php';
    die();
    }   
    
	public function get_help_alert(){    	
    include WPPATT_ABSPATH . 'includes/ajax/get_help_alert.php';	
    die();	
    }
    
	public function get_alert_replacement(){    	
    include WPPATT_ABSPATH . 'includes/ajax/get_alert_replacement.php';	
    die();	
    }
    
    public function get_unauthorized_destruction(){    	
    include WPPATT_ABSPATH . 'includes/ajax/get_unauthorized_destruction.php';	
    die();	
    }

    public function get_bulk_user_edit(){    	
    include WPPATT_ABSPATH . 'includes/ajax/get_bulk_user_edit.php';	
    die();	
    }
    
    public function get_freeze(){    	
    include WPPATT_ABSPATH . 'includes/ajax/get_freeze.php';	
    die();	
    }
    
    public function get_shipping_details(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_shipping_details.php';
    die();
    }

    public function get_inventory_editor(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_inventory_editor.php';
    die();
    }
    
    public function get_digitization_editor(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_digitization_editor.php';
    die();
    }
    
    public function get_folder_file_editor(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_folder_file_editor.php';
    die();
    }
    
    public function get_box_editor(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_box_editor.php';
    die();
    }
    
    public function get_epa_contact_editor(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_epa_contact_editor.php';
    die();
    }
    
    public function get_clear_rfid(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_clear_rfid.php';
    die();
    }
    
    public function get_rfid_box_editor(){    
    include WPPATT_ABSPATH . 'includes/ajax/get_rfid_box_editor.php';
    die();
    }
    
    // Added function to get document relationship
    public function get_doc_relationship(){
	    include WPPATT_ABSPATH . 'includes/ajax/get_doc_relationship.php';    
	    die();
    }  
    
    // Added function to search for box/folder/file ID in Add Recall page 
    public function recall_search_for_id(){
	    include WPPATT_ABSPATH . 'includes/ajax/get_recall_search_id.php';    
	    die();
    }
    
    // Added function to submit Recall on Add Recall page 
    public function recall_submit(){
	    include WPPATT_ABSPATH . 'includes/ajax/submit_recall.php';    
	    die();
    }
    
    // Added function to search and save recall shipping details
    public function recall_get_shipping(){
// 	    include WPPATT_ABSPATH . 'includes/ajax/recall_shipping.php';  
	    include WPPATT_ABSPATH . 'includes/ajax/recall_shipping_multi.php';       
	    die();
    }    
    
    // Added function to search and save recall requestor details
    public function recall_get_requestor(){
	    include WPPATT_ABSPATH . 'includes/ajax/recall_requestor.php';    
	    die();
    }  
    
    // Added function to search and save recall request date
    public function recall_get_date(){
	    include WPPATT_ABSPATH . 'includes/ajax/recall_date.php';    
	    die();
    }  
    
    // functionality changed - file can be removed. 
//    public function recall_status_change(){
//	    include WPPATT_ABSPATH . 'includes/ajax/recall_status_change.php';    
//	    die();
//    }  
    
    // Added function to search and save recall returned date
    public function recall_edit_multi_shipping(){
	    include WPPATT_ABSPATH . 'includes/ajax/recall_shipping_multi.php';    
	    die();
    }  
    
    // Add settings pill for recall statuses 
    public function get_recall_settings(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/get_recall_settings.php';    
	    die();
    }  
    
    // Removed - no longer required. 
    // Add settings pill for recall statuses 
//    public function set_recall_settings(){
//	    include WPPATT_ABSPATH . 'includes/admin/pages/set_recall_settings.php';    
//	    die();
//    }  
    
    // Add edit recall status settings modal 
    public function get_edit_recall_status(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/get_edit_recall_status.php';    
	    die();
    }  

    // Add set recall status settings modal 
    public function set_recall_status(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/set_recall_status_settings.php';    
	    die();
    } 
    
    // Add set ticket status 
    public function set_ticket_status(){
	    include WPPATT_ABSPATH . 'includes/ajax/set_ticket_status.php';    
	    die();
    } 
    
     
    
    // Add file to cancel recall on recall details page 
    public function recall_cancel(){
	    include WPPATT_ABSPATH . 'includes/ajax/recall_cancel.php';    
	    die();
    }
    
    // Add file to cancel recall on recall details page 
    public function recall_approve_deny(){
	    include WPPATT_ABSPATH . 'includes/ajax/recall_approve_deny.php';    
	    die();
    }
    
    // Thread edit on recall details page 
    public function recall_get_edit_thread(){
	    include WPPATT_ABSPATH . 'includes/ajax/recall_get_edit_thread.php';    
	    die();
    }

	 // Added function to submit Return on Add Return page 
    public function return_submit(){ 
	    include WPPATT_ABSPATH . 'includes/ajax/submit_return.php';    
	    die();
    }
    
    // Add settings panel for return statuses 
    public function get_return_settings(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/get_return_settings.php';    
	    die();
    }  
    
    // Add edit return status settings modal 
    public function get_edit_return_status(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/get_edit_return_status.php';    
	    die();
    }  
    // Add set return status settings modal 
    public function set_return_status(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/set_return_status_settings.php';    
	    die();
    }  
    
    // Add file to cancel Return on Return details page 
    public function return_cancel(){
	    include WPPATT_ABSPATH . 'includes/ajax/return_cancel.php';    
	    die();
    }
    
    // Add file to extend the expiration date on a Return on the Decline details page 
    public function return_extend_expiration(){
	    include WPPATT_ABSPATH . 'includes/ajax/return_extend_expiration.php';    
	    die();
    }
    
    // Add settings panel for box statuses 
    public function get_box_settings(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/get_box_settings.php';    
	    die();
    }  
    
    // Add edit box status settings modal 
    public function get_edit_box_status(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/get_edit_box_status.php';    
	    die();
    }  
    // Add set box status settings modal 
    public function set_box_status(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/set_box_status_settings.php';    
	    die();
    }  
    
    // Add edit Shipping modal 
    public function change_shipping(){
	    include WPPATT_ABSPATH . 'includes/ajax/change_shipping_info.php';    
	    die();
    } 
    
    // Add edit Assign Agents modal 
    public function edit_assign_agents(){
	    include WPPATT_ABSPATH . 'includes/ajax/assign_agents_modal.php';    
	    die();
    } 
    
    // Add edit Box Status modal 
    public function change_box_status(){
	    include WPPATT_ABSPATH . 'includes/ajax/change_box_status_modal.php';    
	    die();
    }     
    
    // Add Pallet Assignment modal 
    public function set_pallet_assignment(){
	    include WPPATT_ABSPATH . 'includes/ajax/set_pallet_assignment_modal.php';    
	    die();
    }   
    
    // Update s3 Details including object name, bucket and file size
    public function update_s3_details(){
	    include WPPATT_ABSPATH . 'includes/ajax/update_s3_details.php';    
	    die();
    }
    
    // Add ECMS remove request Modal
    public function ecms_deletion_request(){
	    include WPPATT_ABSPATH . 'includes/ajax/ecms_deletion_request.php';    
	    die();
    }     
    
    
    // Add threaded comment to Recall description comments  
    public function recall_threaded_comment_reply(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/scripts/recall_comments_submit_reply.php'; 
	    die();
    }
    
    
    // Add threaded comment to Recall description comments  
    public function recall_threaded_comment_note(){
	    include WPPATT_ABSPATH . 'includes/admin/pages/scripts/recall_comments_submit_note.php'; 
	    die();
    }
    
    // checks if boxes can change status based on request status  
    public function determine_request_statuses(){
	    include WPPATT_ABSPATH . 'includes/ajax/box_status_changable_due_to_request_status.php'; 
	    die();
    }
    
    
    
    // links ticket_id and attachment id in DB  
    public function link_ticket_attachment(){
	    
	    global $current_user, $wpdb;
	    
	    $ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '';
	    $attachement_id = isset($_POST['attachement_id']) ? sanitize_text_field($_POST['attachement_id']) : '';
	    
	    $data = [
		    'ticket_id' => $ticket_id,
			'meta_key' => 'box_list_post_id',
			'meta_value' => $attachement_id
	    ];
	    
	    //Determine if entry exists prior to insert
	    
	    $table = $wpdb->prefix . 'wpsc_ticketmeta';
	    
	    $get_box_list_ticket_count = $wpdb->get_row("
        SELECT count(id) as count FROM " . $table . " 
        WHERE meta_key = 'box_list_post_id' AND ticket_id = '" . $ticket_id . "'
        ");
        $box_list_ticket_count = $get_box_list_ticket_count->count;

        if($box_list_ticket_count == 0) {
	    $new_id = $wpdb->insert( $table, $data );
        }
	    
	    $response = array(
			"ticket_id" => $ticket_id,
			"attachement_id" => $attachement_id,
			"new_id" => $new_id
		);

		echo json_encode( $response );
		
		die();
    }
    
    
    
    // Displays the user's Digitization Center on their Profile
    public function extra_user_profile_fields( $user ) { 
		global $wpscfunction;
		
		$locations = get_terms([
			'taxonomy'   => 'wpsc_categories',
			'hide_empty' => false,
			'orderby'    => 'meta_value_num',
			'order'    	 => 'ASC'					
		]);
		
		// Get current location election
		$user_location = get_user_meta( $user->data->ID, 'user_digization_center', true );
		
		if( $user_location == '' ) {
			$user_location = 'Not Assigned';
		}
		
		// Disable Exgtra profile information for Requesters
		$agent_permissions = $wpscfunction->get_current_agent_permissions();
		
		if( $agent_permissions['label'] != 'Requester' && $agent_permissions['label'] != 'Requester Pallet' ) {
		
		?>
			<h3><?php _e("Extra profile information", "supportcandy"); ?></h3>  
			
			<table class="form-table">
			<tr>
			<th><label for="profile_digitization_center"><?php _e("Digitization Center", "supportcandy"); ?></label></th>
			<td>
			    <select id="profile_digitization_center" class="form-control wpsc_category" name="profile_digitization_center">
			    <?php
			        foreach( $locations as $center ) {
	// 			        if( !is_int(strpos( $center->name, 'CUI') ) && !is_int(strpos( $center->name, 'Not Assigned') ) ) {
				        if( !is_int(strpos( $center->name, 'CUI') ) ) {
					    	$current_selection = '';
					    	
					    	// Set the default to current selection
					    	if( $center->name == $user_location ) {
						    	$current_selection = 'selected';
					    	}
					    	
					    	echo '<option value="'. $center->name .'" ' . $current_selection . ' >' . $center->name . '</option>';    
					    }
			        }
			    ?>
			    </select>
			    <br />
			    <?php if( $user_location == 'Not Assigned') {  ?>
			    	<span class="description" style="color:red; padding-left: 1px;" ><?php _e("<b>Please select your Digitization Center.</b>", "supportcandy"); ?></span>
			    <?php } ?>
			</td>
			</tr>
			
			</table>
		<?php 
		}
	} 
	
	// Saves the user's Digitization Center selection upon clicking 'Update Profile'
	public function save_extra_user_profile_fields( $user_id ) {
	    if ( !current_user_can( 'edit_user', $user_id ) ) { 
	        return false; 
	    }
	    update_user_meta( $user_id, 'user_digization_center', $_POST['profile_digitization_center'] );
	    
	}
	
	public function error_notice_no_digi_center_set( ) {
		global $current_user, $wpscfunction;
		
		// Get current location election
		$user_location = get_user_meta( $current_user->ID, 'user_digization_center', true );
		$agent_permissions = $wpscfunction->get_current_agent_permissions();
		
		// If user is not assigned, display wp system notification
		if( ($user_location == '' || $user_location == 'Not Assigned') && $agent_permissions['label'] != 'Requester' && $agent_permissions['label'] != 'Requester Pallet') {
			$profile_url = get_site_url();
			?>
			
			<div class="error notice">
		        <p><?php _e( "You do not have a Digitization Center set in your User Profile. <b>Some functionality may not work until this is set.</b> Please click <a href='".$profile_url."/wp-admin/profile.php' >here</a> to set it. ", "supportcandy" );  ?></p>
		    </div>
			
			
			<?php
		}
		
		
		
		
	}
    
  }
  
endif;

new wppatt_Admin();
