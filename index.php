<?php
	include 'islem.php';
	$filePath="a.txt";
	$mails=create($filePath);
	
	$url='https://email-checker.net';
	
?>



<?php
for($i=0;$i<count($mails);$i++)
{
	$post_data['email']=$mails[$i];
	$id=post($url);
	$post_data['_csrf']=$id;
	foreach ( $post_data as $key => $value) {
	$post_items[] = $key . '=' . $value;
	}

	$post_string = implode ('&', $post_items);
	echo $post_string;
	$curl_connection = curl_init();
	curl_setopt($curl_connection, CURLOPT_URL, 'https://email-checker.net/check');
	curl_setopt($curl_connection, CURLOPT_FRESH_CONNECT,true);
	curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36");
	curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_connection, CURLOPT_HEADER, false);
	curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl_connection, CURLOPT_POST, true);
	curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
	$data=curl_exec($curl_connection);
	curl_close($curl_connection);
	echo $data;

 


preg_match_all('@<div id="results-wrapper">
          <h2><span class=(.*?)</span> <small>@si',$url,$a);
}

?>