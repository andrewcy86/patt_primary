<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -4)));
//require_once ($_SERVER['DOCUMENT_ROOT'] . $WP_PATH . '/wp/wp-load.php');

//include_once (WPPATT_UPLOADS . 'api_authorization_strings.php');

global $current_user, $wpscfunction, $wpdb;

// Comparison arrays
$wordpress_users = array();
$patt_agents = array();

$agents = get_terms([
	'taxonomy'   => 'wpsc_agents',
	'hide_empty' => false,
	'meta_query' => array(
    array(
      'key'       => 'agentgroup',
      'value'     => '0',
      'compare'   => '='
    )
  )
]);

foreach ($agents as $agent) {
    array_push($patt_agents, $agent->term_id);
}

// Get all users in PATT
$get_all_users = $wpdb->get_results("SELECT id, user_login
FROM " . $wpdb->prefix . "users");

foreach ($get_all_users as $item)
{
    $user_id = $item->id;
    $user_login = $item->user_login;
    
    $patt_agent_id = Patt_Custom_Func::translate_user_id(array($user_id),'agent_term_id')[0];
    array_push($wordpress_users, $patt_agent_id);
    
    $curl = curl_init();

    $url = EIDW_ENDPOINT . $user_login;
    
    //Check for non-active/non-existant users in PATT
    $eidw_authorization = 'Authorization: Basic '.EIDW;
    
    $headers = ['Cache-Control: no-cache', $eidw_authorization];

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

    $response = curl_exec($curl);

$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

curl_close($curl);

$err = Patt_Custom_Func::convert_http_error_code($status);


    if ($status != 200) {
    Patt_Custom_Func::insert_api_error('eidw-user-cleanup-cron',$status,$err);
    }
    else
    {

        $json = json_decode($response, true);

        $results = $json['totalResults'];
		
        // For each user ping the EIDW API and determine if account is active
        $active = $json['Resources']['0']['active'];

        $employee_id = $json['Resources']['0']['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User']['employeeNumber'];
        $employee_email = $json['Resources']['0']['emails']['0']['value'];
        $employee_org = $json['Resources']['0']['urn:ietf:params:scim:schemas:extension:enterprise:2.0:User']['department'];
        
        // Convert user id to agent id
        $get_agent_id = $wpdb->get_row("
		SELECT term_id as agent_id
		FROM " . $wpdb->prefix . "termmeta
		WHERE meta_key = 'user_id' AND
		meta_value = '" . $user_id . "'
		");

        $agent_id = $get_agent_id->agent_id;

        // Make exceptions for test user accounts
        $exceptions_array = array(
            "admin",
            "user",
            "user2",
            "user3"
        );
		
		// Skip exception test accounts
        if (!in_array($user_login, $exceptions_array))
        {

			// Only proceed if user is determined to be inactive
            if ($agent_id != '' && $active != 1 && $results >= 1)
            {
				
                // Remove the user from PATT
                $user_id_remove = get_term_meta($agent_id, 'user_id', true);
                $user = get_user_by('id', $user_id_remove);

                if ($user)
                {
                    $user->remove_cap('wpsc_agent');
                    delete_user_option($user_id_remove, 'wpsc_agent_role');
                }

                wp_delete_term($agent_id, 'wpsc_agents');

                do_action('wpsc_delete_agent', $agent_id);

                // Alter the Wordpress user role
                // Fetch the WP_User object of the user.
                $u = new WP_User($user_id);

                // Replace the current role with 'No role for this site' role
                $u->set_role('');

                // Insert into log and print modified user information
				echo $active.', '.$user_id.', '.$agent_id.', '.$user_login.'<br />';
                //print_r($user);
                $lan_log_table = $wpdb->prefix . 'wpsc_epa_lan_log';
                $wpdb->insert($lan_log_table, array(
                    'user_login' => $user_login,
                    'employee_id' => $employee_id,
                    'employee_email' => $employee_email,
                    'employee_org' => $employee_org
                ));

            }
        }

    }
}

// Get all PATT agents that are not in the wordpress users list
$result = array_diff($patt_agents,$wordpress_users);
// Delete associated agent IDs
foreach($result as $agent_id) {
    $user_id = get_term_meta( $agent_id, 'user_id', true);
    $user = get_user_by('id',$agent_id);
    if($user){
    	$user->remove_cap('wpsc_agent');
    	delete_user_option($user_id,'wpsc_agent_role');
    }
    wp_delete_term($agent_id, 'wpsc_agents');
    do_action('wpsc_delete_agent',$agent_id);
}
?>