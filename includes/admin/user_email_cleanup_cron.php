<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -4)));
//require_once ($_SERVER['DOCUMENT_ROOT'] . $WP_PATH . '/wp/wp-load.php');

//include_once (WPPATT_UPLOADS . 'api_authorization_strings.php');

global $current_user, $wpscfunction, $wpdb;

// Get all users in PATT
$get_all_users = $wpdb->get_results("SELECT id, user_login
FROM " . $wpdb->prefix . "users WHERE user_email = ''");

foreach ($get_all_users as $item)
{
    $user_id = $item->id;
    $user_login = $item->user_login;

    $curl = curl_init();

    $url = EIDW_ENDPOINT . $user_login;
    
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
    Patt_Custom_Func::insert_api_error('eidw-user-email-cleanup-cron',$status,$err);
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
            if ($active == 1 && $results >= 1)
            {
			// Update user email
            $user_table_name = $wpdb->prefix.'users';
            $wpdb->update( $user_table_name, array( 'user_email' => $employee_email),array('ID'=>$user_id));
            }
        }

    }
}

?>