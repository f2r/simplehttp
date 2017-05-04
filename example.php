<?php
require 'vendor/autoload.php';

$url = 'http://[::ffff:7f00:0001]/';

$client = new f2r\SimpleHttp\Client();
$client->getOptions()->setSafeRequest();
try {
    $response = $client->get($url);
} catch (Exception $e) {
    die($e->getMessage() . "\n");
}

echo "URL $url requested. HTTP code: ", $response->getHttpCode(), "\n";
