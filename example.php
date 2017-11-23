<?php
require 'vendor/autoload.php';

$url = 'http://localhost/test.html';

$client = new f2r\SimpleHttp\Client();

try {
    $response = $client->get($url);
} catch (Exception $e) {
    die(get_class($e) . ': ' . $e->getMessage() . "\n");
}

echo "URL $url requested. HTTP code: ", $response->getHttpCode(), "\n";
echo $response->getBody();