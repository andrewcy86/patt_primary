<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -4)));
//require_once ($_SERVER['DOCUMENT_ROOT'] . $WP_PATH . '/wp/wp-load.php');

include_once (WPPATT_UPLOADS . 'api_authorization_strings.php');

global $current_user, $wpscfunction, $wpdb;

// Get all users in PATT
$get_all_users = $wpdb->get_results("SELECT id, user_login
FROM " . $wpdb->prefix . "users");

foreach ($get_all_users as $item)
{
    $user_id = $item->id;
    $user_login = $item->user_login;

    $curl = curl_init();

    $url = 'https://wamssoprd.epa.gov/iam/governance/scim/v1/Users?filter=userName%20eq%20' . $user_login;

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
    $err = curl_error($curl);
    curl_close($curl);

    $lan_id_details = '';

    if ($err)
    {
        $lan_id_details = 'Error';
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
            if ($agent_id != '' && $active != 1)
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
				//echo $user_id.', '.$agent_id.', '.$user_login.'<br />';
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

?>