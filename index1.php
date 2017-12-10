<?php



$post_data['lang'] = 'en';


$post_data['emailChecker']='mehmetacar683@gmail.com';


foreach ( $post_data as $key => $value) {
$post_items[] = $key . '=' . $value;
}
$post_string = implode ('&', $post_items);
$curl_connection = curl_init();
curl_setopt($curl_connection, CURLOPT_URL, 'https://www.bulkemailchecker.com/free-email-checker/');
curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($curl_connection, CURLOPT_FRESH_CONNECT,true);
curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36");
curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_connection, CURLOPT_HEADER, false);
curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl_connection, CURLOPT_POST, true);
curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
$data=curl_exec($curl_connection);
$http=curl_getinfo($curl_connection,CURLINFO_HTTP_CODE);
echo $data;
//preg_match_all('@<th>Email Address</th><th>Status</th><th>Event</th><th>(.*?)</th>>@si',$data,$a);
//echo $a[0][0];
curl_close($curl_connection);

?>