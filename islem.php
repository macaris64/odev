<?php
	function create($filePath)
	{
		$file = fopen($filePath,'r');
		$n=0;
		while(!feof($file))
		{ 
        	$satir = fgets($file);
        	echo "$satir <br />"; // burada explode, regex vs. kullanarak istedigin islemleri uygulayabilirsin. kolay gelsin
			$mails[$n]=$satir;
			$n=$n+1;
		}
		fclose($file);
		return $mails;
	}
	function post($url)
	{
		$veri=file_get_contents($url);
		preg_match_all('@<input type="hidden" name="_csrf" value="(.*?)">@si',$veri,$a);
		$a=(explode('"',$a[1][0],2));
		$id=$a[0];
		return $id;
	}
	function get()
	{
		
	}
	function grab_page($site){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($ch, CURLOPT_URL, $site);
    ob_start();
    return curl_exec ($ch);
    ob_end_clean();
    curl_close ($ch);
}

?>