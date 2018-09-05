<?php
require 'vendor/autoload.php';

$url = 'http://[::ffff:7f00:1]/';

$client = new \f2r\SimpleHttp\Client();
$client->with(new \f2r\SimpleHttp\Feature\ProtectFromSSRFRequest());

try {
    $response = $client->get($url);
} catch (Exception $e) {
    die(get_class($e) . ': ' . $e->getMessage() . "\n");
}

echo "URL $url requested. HTTP code: ", $response->getHeader()->getHttpCode(), "\n";
