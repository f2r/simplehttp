<?php
require 'vendor/autoload.php';

$client = new f2r\SimpleHttp\Client();
$response = $client->get('https://raw.githubusercontent.com/f2r/simplehttp/master/README.md');

echo $response->getBody(), "\n";
