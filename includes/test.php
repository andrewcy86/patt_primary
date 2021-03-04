<?php
global $current_user, $wpscfunction, $wpdb;
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -5)));
include_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

if (isset($_GET['id']))
{

    //Set SuperGlobal ID variable to be used in all functions below
    $GLOBALS['id'] = $_GET['id'];
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://semspub.epa.gov/src/sitedetails/".$GLOBALS['id'],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
));

$response = curl_exec($curl);
$err = curl_error($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  $json = json_decode($response, true);
  //print_r($json);
  echo '<h1>';
  echo $json['data']['0']['sitename'];
  echo '</h1>';
  echo 'HTTP code: ' . $httpcode;
}

} else {
 echo "Enter Site ID";
}



/*
//echo implode("/", (explode("/", WPPATT_UPLOADS, -3)));
$ticket_id = 7;

function strip_tags_deep($value)
{
  return is_array($value) ?
    array_map('strip_tags_deep', $value) :
    strip_tags(preg_replace( "/\r|\n/", "", $value ));
}

$pre_results = $wpdb->get_results("
SELECT 
rel.post_id as id,
posts.post_date as date,
posts.post_content as content
    FROM " . $wpdb->prefix . "posts AS posts
        LEFT JOIN " . $wpdb->prefix . "postmeta AS rel ON 
            posts.ID = rel.post_id
        LEFT JOIN " . $wpdb->prefix . "postmeta AS rel2 ON
            posts.ID = rel2.post_id
    WHERE
        posts.post_type = 'wpsc_ticket_thread' AND
        posts.post_status = 'publish' AND 
        rel2.meta_key = 'thread_type' AND
        rel2.meta_value = 'log' AND
        rel.meta_key = 'ticket_id' AND
        rel.meta_value =" .$ticket_id ."
    ORDER BY
    posts.post_date DESC",ARRAY_A);

$results = strip_tags_deep($pre_results);

foreach ($results as $key => &$value) {
   $date = new DateTime($value['date'], new DateTimeZone('UTC'));
   $date->setTimezone(new DateTimeZone('America/New_York'));
   $value['date'] = $date->format('m-d-Y h:i:s a');
}

print_r($results);
*/
?>