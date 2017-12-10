<?php
class hhb_curl {
	protected $curlh;
	protected $curloptions = [ ];
	protected $response_body_file_handle; // CURLOPT_FILE
	protected $response_headers_file_handle; // CURLOPT_WRITEHEADER
	protected $request_body_file_handle; // CURLOPT_INFILE
	protected $stderr_file_handle; // CURLOPT_STDERR
	protected function truncateFileHandles() {
		$trun = ftruncate ( $this->response_body_file_handle, 0 );
		assert ( true === $trun );
		$trun = ftruncate ( $this->response_headers_file_handle, 0 );
		assert ( true === $trun );
		// $trun = ftruncate ( $this->request_body_file_handle, 0 );
		// assert ( true === $trun );
		$trun = ftruncate ( $this->stderr_file_handle, 0 );
		assert ( true === $trun );
		return /*true*/;
	}
	/**
	 * returns the internal curl handle
	 *
	 * its probably a bad idea to mess with it, you'll probably never want to use this function.
	 *
	 * @return resource_curl
	 */
	public function _getCurlHandle()/*: curlresource*/ {
		return $this->curlh;
	}
	/**
	 * replace the internal curl handle with another one...
	 *
	 * its probably a bad idea. you'll probably never want to use this function.
	 *
	 * @param resource_curl $newcurl
	 * @param bool $closeold
	 * @throws InvalidArgumentsException
	 *
	 * @return void
	 */
	public function _replaceCurl($newcurl, bool $closeold = true) {
		if (! is_resource ( $newcurl )) {
			throw new InvalidArgumentsException ( 'parameter 1 must be a curl resource!' );
		}
		if (get_resource_type ( $newcurl ) !== 'curl') {
			throw new InvalidArgumentsException ( 'parameter 1 must be a curl resource!' );
		}
		if ($closeold) {
			curl_close ( $this->curlh );
		}
		$this->curlh = $newcurl;
		$this->_prepare_curl ();
	}
	/**
	 * mimics curl_init, using hhb_curl::__construct
	 *
	 * @param string $url
	 * @param bool $insecureAndComfortableByDefault
	 * @return hhb_curl
	 */
	public static function init(string $url = null, bool $insecureAndComfortableByDefault = false): hhb_curl {
		return new hhb_curl ( $url, $insecureAndComfortableByDefault );
	}
	/**
	 *
	 * @param string $url
	 * @param bool $insecureAndComfortableByDefault
	 * @throws RuntimeException
	 */
	function __construct(string $url = null, bool $insecureAndComfortableByDefault = false) {
		$this->curlh = curl_init ( '' ); // why empty string? PHP Fatal error: Uncaught TypeError: curl_init() expects parameter 1 to be string, null given
		if (! $this->curlh) {
			throw new RuntimeException ( 'curl_init failed!' );
		}
		if ($url !== null) {
			$this->_setopt ( CURLOPT_URL, $url );
		}
		$fhandles = [ ];
		$tmph = NULL;
		for($i = 0; $i < 4; ++ $i) {
			$tmph = tmpfile ();
			if ($tmph === false) {
				// for($ii = 0; $ii < $i; ++ $ii) {
				// // @fclose($fhandles[$ii]);//yay, potentially overwriting last error to fuck your debugging efforts!
				// }
				throw new RuntimeException ( 'tmpfile() failed to create 4 file handles!' );
			}
			$fhandles [] = $tmph;
		}
		unset ( $tmph );
		$this->response_body_file_handle = $fhandles [0]; // CURLOPT_FILE
		$this->response_headers_file_handle = $fhandles [1]; // CURLOPT_WRITEHEADER
		$this->request_body_file_handle = $fhandles [2]; // CURLOPT_INFILE
		$this->stderr_file_handle = $fhandles [3]; // CURLOPT_STDERR
		unset ( $fhandles );
		$this->_prepare_curl ();
		if ($insecureAndComfortableByDefault) {
			$this->_setComfortableOptions ();
		}
	}
	function __destruct() {
		curl_close ( $this->curlh );
		fclose ( $this->response_body_file_handle ); // CURLOPT_FILE
		fclose ( $this->response_headers_file_handle ); // CURLOPT_WRITEHEADER
		fclose ( $this->request_body_file_handle ); // CURLOPT_INFILE
		fclose ( $this->stderr_file_handle ); // CURLOPT_STDERR
	}
	/**
	 * sets some insecure, but comfortable settings..
	 *
	 * @return self
	 */
	public function _setComfortableOptions(): self {
		$this->setopt_array ( array (
				CURLOPT_AUTOREFERER => true,
				CURLOPT_BINARYTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPGET => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_CONNECTTIMEOUT => 4,
				CURLOPT_TIMEOUT => 8,
				CURLOPT_COOKIEFILE => "", // <<makes curl save/load cookies across requests..
				CURLOPT_ENCODING => "", // << makes curl post all supported encodings, gzip/deflate/etc, makes transfers faster
				CURLOPT_USERAGENT => 'hhb_curl_php; curl/' . $this->version () ['version'] . ' (' . $this->version () ['host'] . '); php/' . PHP_VERSION 
		) ); //
		return $this;
	}
	/**
	 * curl_errno — Return the last error number
	 *
	 * @return int
	 */
	public function errno(): int {
		return curl_errno ( $this->curlh );
	}
	/**
	 * curl_error — Return a string containing the last error
	 *
	 * @return string
	 */
	public function error(): string {
		return curl_error ( $this->curlh );
	}
	/**
	 * curl_escape — URL encodes the given string
	 *
	 * @param string $str
	 * @return string
	 */
	public function escape(string $str): string {
		return curl_escape ( $this->curlh, $str );
	}
	/**
	 * curl_unescape — Decodes the given URL encoded string
	 *
	 * @param string $str
	 * @return string
	 */
	public function unescape(string $str): string {
		return curl_unescape ( $this->curlh, $str );
	}
	/**
	 * executes the curl request (curl_exec)
	 *
	 * @param string $url
	 * @throws RuntimeException
	 * @return self
	 */
	public function exec(string $url = null): self {
		$this->truncateFileHandles ();
		// WARNING: some weird error where curl will fill up the file again with 00's when the file has been truncated
		// until it is the same size as it was before truncating, then keep appending...
		// hopefully this _prepare_curl() call will fix that.. (seen on debian 8 on btrfs with curl/7.38.0)
		$this->_prepare_curl ();
		if (is_string ( $url ) && strlen ( $url ) > 0) {
			$this->setopt ( CURLOPT_URL, $url );
		}
		$ret = curl_exec ( $this->curlh );
		if ($this->errno ()) {
			throw new RuntimeException ( 'curl_exec failed. errno: ' . var_export ( $this->errno (), true ) . ' error: ' . var_export ( $this->error (), true ) );
		}
		return $this;
	}
	/**
	 * Create a CURLFile object for use with CURLOPT_POSTFIELDS
	 *
	 * @param string $filename
	 * @param string $mimetype
	 * @param string $postname
	 * @return CURLFile
	 */
	public function file_create(string $filename, string $mimetype = null, string $postname = null): CURLFile {
		return curl_file_create ( $filename, $mimetype, $postname );
	}
	/**
	 * Get information regarding the last transfer
	 *
	 * @param int $opt
	 * @return mixed
	 */
	public function getinfo(int $opt) {
		return curl_getinfo ( $this->curlh, $opt );
	}
	// pause is explicitly undocumented for now, but it pauses a running transfer
	public function pause(int $bitmask): int {
		return curl_pause ( $this->curlh, $bitmask );
	}
	/**
	 * Reset all options
	 */
	public function reset(): self {
		curl_reset ( $this->curlh );
		$this->curloptions = [ ];
		$this->_prepare_curl ();
		return $this;
	}
	/**
	 * curl_setopt_array — Set multiple options for a cURL transfer
	 *
	 * @param array $options
	 * @throws InvalidArgumentException
	 * @return self
	 */
	public function setopt_array(array $options): self {
		foreach ( $options as $option => $value ) {
			$this->setopt ( $option, $value );
		}
		return $this;
	}
	/**
	 * gets the last response body
	 *
	 * @return string
	 */
	public function getResponseBody(): string {
		return file_get_contents ( stream_get_meta_data ( $this->response_body_file_handle ) ['uri'] );
	}
	/**
	 * returns the response headers of the last request (when auto-following Location-redirect, only the last headers are returned)
	 *
	 * @return string[]
	 */
	public function getResponseHeaders(): array {
		$text = file_get_contents ( stream_get_meta_data ( $this->response_headers_file_handle ) ['uri'] );
		// ...
		return $this->splitHeaders ( $text );
	}
	/**
	 * gets the response headers of all the requets for the last execution (including any Location-redirect autofollow headers)
	 *
	 * @return string[][]
	 */
	public function getResponsesHeaders(): array {
		// var_dump($this->getStdErr());die();
		// CONSIDER https://bugs.php.net/bug.php?id=65348
		$Cr = "\x0d";
		$Lf = "\x0a";
		$CrLf = "\x0d\x0a";
		$stderr = $this->getStdErr ();
		$responses = [ ];
		while ( FALSE !== ($startPos = strpos ( $stderr, $Lf . '<' )) ) {
			$stderr = substr ( $stderr, $startPos + strlen ( $Lf ) );
			$endPos = strpos ( $stderr, $CrLf . "<\x20" . $CrLf );
			if ($endPos === false) {
				// ofc, curl has ths quirk where the specific message "* HTTP error before end of send, stop sending" gets appended with LF instead of the usual CRLF for other messages...
				$endPos = strpos ( $stderr, $Lf . "<\x20" . $CrLf );
			}
			// var_dump(bin2hex(substr($stderr,279,30)),$endPos);die("HEX");
			// var_dump($stderr,$endPos);die("PAIN");
			assert ( $endPos !== FALSE ); // should always be more after this with CURLOPT_VERBOSE.. (connection left intact / connecton dropped /whatever)
			$headers = substr ( $stderr, 0, $endPos );
			// $headerscpy=$headers;
			$stderr = substr ( $stderr, $endPos + strlen ( $CrLf . $CrLf ) );
			$headers = preg_split ( "/((\r?\n)|(\r\n?))/", $headers ); // i can NOT explode($CrLf,$headers); because sometimes, in the middle of recieving headers, it will spout stuff like "\n* Added cookie reg_ext_ref="deleted" for domain facebook.com, path /, expire 1457503459"
			                                                           // if(strpos($headerscpy,"report-uri=")!==false){
			                                                           // //var_dump($headerscpy);die("DIEDS");
			                                                           // var_dump($headers);
			                                                           // //var_dump($this->getStdErr());die("DIEDS");
			                                                           // }
			foreach ( $headers as $key => &$val ) {
				$val = trim ( $val );
				if (! strlen ( $val )) {
					unset ( $headers [$key] );
					continue;
				}
				if ($val [0] !== '<') {
					// static $r=0;++$r;var_dump('removing',$val);if($r>1)die();
					unset ( $headers [$key] ); // sometimes, in the middle of recieving headers, it will spout stuff like "\n* Added cookie reg_ext_ref="deleted" for domain facebook.com, path /, expire 1457503459"
					continue;
				}
				$val = trim ( substr ( $val, 1 ) );
			}
			unset ( $val ); // references can be scary..
			$responses [] = $headers;
		}
		unset ( $headers, $key, $val, $endPos, $startPos );
		return $responses;
	}
	// we COULD have a getResponsesCookies too...
	/*
	 * get last response cookies
	 *
	 * @return string[]
	 */
	public function getResponseCookies(): array {
		$headers = $this->getResponsesHeaders ();
		$headers_merged = array ();
		foreach ( $headers as $headers2 ) {
			foreach ( $headers2 as $header ) {
				$headers_merged [] = $header;
			}
		}
		return $this->parseCookies ( $headers_merged );
	}
	// explicitly undocumented for now..
	public function getRequestBody(): string {
		return file_get_contents ( stream_get_meta_data ( $this->request_body_file_handle ) ['uri'] );
	}
	/**
	 * return headers of last execution
	 *
	 * @return string[]
	 */
	public function getRequestHeaders(): array {
		$requestsHeaders = $this->getRequestsHeaders ();
		$requestCount = count ( $requestsHeaders );
		if ($requestCount === 0) {
			return array ();
		}
		return $requestsHeaders [$requestCount - 1];
	}
	// array(0=>array(request1_headers),1=>array(requst2_headers),2=>array(request3_headers))~
	/**
	 * get last execution request headers
	 *
	 * @return string[]
	 */
	public function getRequestsHeaders(): array {
		// CONSIDER https://bugs.php.net/bug.php?id=65348
		$Cr = "\x0d";
		$Lf = "\x0a";
		$CrLf = "\x0d\x0a";
		$stderr = $this->getStdErr ();
		$requests = [ ];
		while ( FALSE !== ($startPos = strpos ( $stderr, $Lf . '>' )) ) {
			$stderr = substr ( $stderr, $startPos + strlen ( $Lf . '>' ) );
			$endPos = strpos ( $stderr, $CrLf . $CrLf );

			if ($endPos === false) {
				// ofc, curl has ths quirk where the specific message "* HTTP error before end of send, stop sending" gets appended with LF instead of the usual CRLF for other messages...
				$endPos = strpos ( $stderr, $Lf . $CrLf );
			}
			assert ( $endPos !== FALSE ); // should always be more after this with CURLOPT_VERBOSE.. (connection left intact / connecton dropped /whatever)
			$headers = substr ( $stderr, 0, $endPos );
			$stderr = substr ( $stderr, $endPos + strlen ( $CrLf . $CrLf ) );
			$headers = explode ( $CrLf, $headers );
			foreach ( $headers as $key => &$val ) {
				$val = trim ( $val );
				if (! strlen ( $val )) {
					unset ( $headers [$key] );
				}
			}
			unset ( $val ); // references can be scary..
			$requests [] = $headers;
		}
		unset ( $headers, $key, $val, $endPos, $startPos );
		return $requests;
	}
	/**
	 * return last execution request cookies
	 *
	 * @return string[]
	 */
	public function getRequestCookies(): array {
		return $this->parseCookies ( $this->getRequestHeaders () );
	}
	/**
	 * get everything curl wrote to stderr of the last execution
	 *
	 * @return string
	 */
	public function getStdErr(): string {
		return file_get_contents ( stream_get_meta_data ( $this->stderr_file_handle ) ['uri'] );
	}
	/**
	 * alias of getResponseBody
	 *
	 * @return string
	 */
	public function getStdOut(): string {
		return $this->getResponseBody ();
	}
	protected function splitHeaders(string $headerstring): array {
		$headers = preg_split ( "/((\r?\n)|(\r\n?))/", $headerstring );
		foreach ( $headers as $key => $val ) {
			if (! strlen ( trim ( $val ) )) {
				unset ( $headers [$key] );
			}
		}
		return $headers;
	}
	protected function parseCookies(array $headers): array {
		$returnCookies = [ ];
		$grabCookieName = function ($str, &$len) {
			$len = 0;
			$ret = "";
			$i = 0;
			for($i = 0; $i < strlen ( $str ); ++ $i) {
				++ $len;
				if ($str [$i] === ' ') {
					continue;
				}
				if ($str [$i] === '=' || $str [$i] === ';') {
					-- $len;
					break;
				}
				$ret .= $str [$i];
			}
			return urldecode ( $ret );
		};
		foreach ( $headers as $header ) {
			// Set-Cookie: crlfcoookielol=crlf+is%0D%0A+and+newline+is+%0D%0A+and+semicolon+is%3B+and+not+sure+what+else
			/*
			 * Set-Cookie:ci_spill=a%3A4%3A%7Bs%3A10%3A%22session_id%22%3Bs%3A32%3A%22305d3d67b8016ca9661c3b032d4319df%22%3Bs%3A10%3A%22ip_address%22%3Bs%3A14%3A%2285.164.158.128%22%3Bs%3A10%3A%22user_agent%22%3Bs%3A109%3A%22Mozilla%2F5.0+%28Windows+NT+6.1%3B+WOW64%29+AppleWebKit%2F537.36+%28KHTML%2C+like+Gecko%29+Chrome%2F43.0.2357.132+Safari%2F537.36%22%3Bs%3A13%3A%22last_activity%22%3Bi%3A1436874639%3B%7Dcab1dd09f4eca466660e8a767856d013; expires=Tue, 14-Jul-2015 13:50:39 GMT; path=/
			 * Set-Cookie: sessionToken=abc123; Expires=Wed, 09 Jun 2021 10:18:14 GMT;
			 * //Cookie names cannot contain any of the following '=,; \t\r\n\013\014'
			 * //
			 */
			if (stripos ( $header, "Set-Cookie:" ) !== 0) {
				continue;
				/* */
			}
			$header = trim ( substr ( $header, strlen ( "Set-Cookie:" ) ) );
			$len = 0;
			while ( strlen ( $header ) > 0 ) {
				$cookiename = $grabCookieName ( $header, $len );
				$returnCookies [$cookiename] = '';
				$header = substr ( $header, $len );
				if (strlen ( $header ) < 1) {
					break;
				}
				if ($header [0] === '=') {
					$header = substr ( $header, 1 );
				}
				$thepos = strpos ( $header, ';' );
				if ($thepos === false) { // last cookie in this Set-Cookie.
					$returnCookies [$cookiename] = urldecode ( $header );
					break;
				}
				$returnCookies [$cookiename] = urldecode ( substr ( $header, 0, $thepos ) );
				$header = trim ( substr ( $header, $thepos + 1 ) ); // also remove the ;
			}
		}
		unset ( $header, $cookiename, $thepos );
		return $returnCookies;
	}
	/**
	 * Set an option for curl
	 *
	 * @param int $option
	 * @param mixed $value
	 * @throws InvalidArgumentException
	 * @return self
	 */
	public function setopt(int $option, $value): self {
		switch ($option) {
			case CURLOPT_VERBOSE :
				{
					trigger_error ( 'you should NOT change CURLOPT_VERBOSE. use getStdErr() instead. we are working around https://bugs.php.net/bug.php?id=65348 using CURLOPT_VERBOSE.', E_USER_WARNING );
					break;
				}
			case CURLOPT_RETURNTRANSFER :
				{
					trigger_error ( 'you should NOT use CURLOPT_RETURNTRANSFER. use getResponseBody() instead. expect problems now.', E_USER_WARNING );
					break;
				}
			case CURLOPT_FILE :
				{
					trigger_error ( 'you should NOT use CURLOPT_FILE. use getResponseBody() instead. expect problems now.', E_USER_WARNING );
					break;
				}
			case CURLOPT_WRITEHEADER :
				{
					trigger_error ( 'you should NOT use CURLOPT_WRITEHEADER. use getResponseHeaders() instead. expect problems now.', E_USER_WARNING );
					break;
				}
			case CURLOPT_INFILE :
				{
					trigger_error ( 'you should NOT use CURLOPT_INFILE. use setRequestBody() instead. expect problems now.', E_USER_WARNING );
					break;
				}
			case CURLOPT_STDERR :
				{
					trigger_error ( 'you should NOT use CURLOPT_STDERR. use getStdErr() instead. expect problems now.', E_USER_WARNING );
					break;
				}
			case CURLOPT_HEADER :
				{
					trigger_error ( 'you NOT use CURLOPT_HEADER. use  getResponsesHeaders() instead. expect problems now. we are working around https://bugs.php.net/bug.php?id=65348 using CURLOPT_VERBOSE, which is, until the bug is fixed, is incompatible with CURLOPT_HEADER.', E_USER_WARNING );
					break;
				}
			case CURLINFO_HEADER_OUT :
				{
					trigger_error ( 'you should NOT use CURLINFO_HEADER_OUT. use  getRequestHeaders() instead. expect problems now.', E_USER_WARNING );
					break;
				}
			
			default :
				{
				}
		}
		return $this->_setopt ( $option, $value );
	}
	/**
	 *
	 * @param int $option
	 * @param unknown $value
	 * @throws InvalidArgumentException
	 * @return self
	 */
	private function _setopt(int $option, $value): self {
		$ret = curl_setopt ( $this->curlh, $option, $value );
		if (! $ret) {
			throw new InvalidArgumentException ( 'curl_setopt failed. errno: ' . $this->errno () . '. error: ' . $this->error () . '. option: ' . var_export ( $this->_curlopt_name ( $option ), true ) . ' (' . var_export ( $option, true ) . '). value: ' . var_export ( $value, true ) );
		}
		$this->curloptions [$option] = $value;
		return $this;
	}
	/**
	 * return an option previously given to setopt(_array)
	 *
	 * @param int $option
	 * @param bool $isset
	 * @return mixed|NULL
	 */
	public function getopt(int $option, bool &$isset = NULL) {
		if (array_key_exists ( $option, $this->curloptions )) {
			$isset = true;
			return $this->curloptions [$option];
		} else {
			$isset = false;
			return NULL;
		}
	}
	/**
	 * return a string representation of the given curl error code
	 *
	 * (ps, most of the time you'll probably want to use error() instead of strerror())
	 *
	 * @param int $errornum
	 * @return string
	 */
	public function strerror(int $errornum): string {
		return curl_strerror ( $errornum );
	}
	/**
	 * gets cURL version information
	 *
	 * @param int $age
	 * @return array
	 */
	public function version(int $age = CURLVERSION_NOW): array {
		return curl_version ( $age );
	}
	private function _prepare_curl() {
		$this->truncateFileHandles ();
		$this->_setopt ( CURLOPT_FILE, $this->response_body_file_handle ); // CURLOPT_FILE
		$this->_setopt ( CURLOPT_WRITEHEADER, $this->response_headers_file_handle ); // CURLOPT_WRITEHEADER
		$this->_setopt ( CURLOPT_INFILE, $this->request_body_file_handle ); // CURLOPT_INFILE
		$this->_setopt ( CURLOPT_STDERR, $this->stderr_file_handle ); // CURLOPT_STDERR
		$this->_setopt ( CURLOPT_VERBOSE, true );
	}
	/**
	 * gets the constants name of the given curl options
	 *
	 * useful for error messages (instead of "FAILED TO SET CURLOPT 21387" , you can say "FAILED TO SET CURLOPT_VERBOSE" )
	 *
	 * @param int $option
	 * @return mixed|boolean
	 */
	public function _curlopt_name(int $option)/*:mixed(string|false)*/{
		// thanks to TML for the get_defined_constants trick..
		// <TML> If you had some specific reason for doing it with your current approach (which is, to me, approaching the problem completely backwards - "I dug a hole! How do I get out!"), it seems that your entire function there could be replaced with: return array_flip(get_defined_constants(true)['curl']);
		$curldefs = array_flip ( get_defined_constants ( true ) ['curl'] );
		if (isset ( $curldefs [$option] )) {
			return $curldefs [$option];
		} else {
			return false;
		}
	}
	/**
	 * gets the constant number of the given constant name
	 *
	 * (what was i thinking!?)
	 *
	 * @param string $option
	 * @return int|boolean
	 */
	public function _curlopt_number(string $option)/*:mixed(int|false)*/{
		// thanks to TML for the get_defined_constants trick..
		$curldefs = get_defined_constants ( true ) ['curl'];
		if (isset ( $curldefs [$option] )) {
			return $curldefs [$option];
		} else {
			return false;
		}
	}
}
?>