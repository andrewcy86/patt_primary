<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'wppatt_Install' ) ) :

  final class wppatt_Install {

    // Register post types and texonomies
    public function register_post_type(){

      register_taxonomy( 'wpsc_box_statuses', 'wpsc_ticket', $args );

			// Register status texonomy
			$args = array(
          'public'             => false,
          'rewrite'            => false
      );
    }
		}

endif;

new wppatt_Install();