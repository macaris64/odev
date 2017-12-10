<?php
declare(strict_types = 1);
const EMAIL = 'asdasd@asdasd.com';
require_once ('hhb_.inc.php');
$hc = new hhb_curl ( '', true );
$html = $hc->exec ( 'https://email-checker.net' )->getStdOut ();
$domd = @DOMDocument::loadHTML ( $html );
$xp = new DOMXPath ( $domd );
$csrf_token = $xp->query ( '//input[@name="_csrf"]' )->item ( 0 )->getAttribute ( "value" );
$html = $hc->setopt_array ( array (
        CURLOPT_URL => 'https://email-checker.net/check',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => http_build_query ( array (
                '_csrf' => $csrf_token,
                'email' => EMAIL 
        ) ) 
) )->exec ()->getStdOut ();
$domd = @DOMDocument::loadHTML ( $html );
$xp = new DOMXPath ( $domd );
$result = trim ( $xp->query ( '//*[@id="results-wrapper"]' )->item ( 0 )->textContent );
var_dump ( $result );

?>