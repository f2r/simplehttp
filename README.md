# SimpleHTTP
Simple HTTP client

Simplehttp aims to be the simplest HTTP requesting library without breaking compatibility between two versions.
If an incompatible functionality is added, it will be created as an additional class or namespace.

This is the end of conflicted versions like "guzzle syndrome".

In addition, SimpleHttp comes with a "safeRequest" mode to protect from Server Side Request Forgery (SSRF).

## GET request
```php
<?php
require 'vendor/autoload.php';

$client = new f2r\SimpleHttp\Client();
$response = $client->get('https://raw.githubusercontent.com/f2r/simplehttp/master/README.md');

echo $response->getBody(), "\n";
```

## POST, PUT, DELETE, HEAD requests
```php
<?php
$client = new f2r\SimpleHttp\Client();
$client->post('http://www.mysite.com/', ['id' => 97564]);
$client->put('http://www.mysite.com/', '{"id": 7882}');
$client->delete('http://www.mysite.com/');
$client->head('http://www.mysite.com/');
```

## Upload a file
```php
<?php
$client = new f2r\SimpleHttp\Client();
$client->uploadFile('http//www.mysite.com/upload', '/path/to/file'); // file in $_FILES['file']
$client->uploadFile('http//www.mysite.com/upload', ['x-files' => '/path/to/file']); // file in $_FILES['x-files']
$client->uploadFile('http//www.mysite.com/upload', [
    'first' => '/path/to/first-file',
    'second' => '/path/to/second-file'
]); // file in $_FILES['first'] and $_FILES['second'] 
```

## Request header
```php
<?php
$client = new f2r\SimpleHttp\Client();
$client->getHeader()->addField('x-custom', 'value');
```

Injecting request header object
```php
<?php
$header = new \f2r\SimpleHttp\HeaderRequest();
$header->setUserAgent('my-great-user-agent');
$header->setReferrer('http://previous');
$header->setCookie('sid', '97dfa874fe00146');
$header->setIfModifiedSince(new \DateTime('yesterday'));
$client = new f2r\SimpleHttp\Client($header);
$client->getHeader()->addField('x-custom', 'value');
```

## safeRequest
```php
<?php
$client = new f2r\SimpleHttp\Client();
$client->getOptions()->setSafeRequest();
```

Host black list

```php
<?php
$options = new \f2r\SimpleHttp\Options();
$options->addHostBlackList('unsafe.com');
$client = new f2r\SimpleHttp\Client(null, $options);
```

Host white list with a regular expression

```php
<?php
$options = new \f2r\SimpleHttp\Options();
$options->addHostWhiteList('\.mysite\.com$', /* isRegexp */ true);
$client = new f2r\SimpleHttp\Client(null, $options);
```
Both "blacklist" and "whitelist" support regular expression matching

## Options class reference

- addHostBlackList(string|array $host, bool $isRegexp = false)
- addHostWhiteList(string|array $host, bool $isRegexp = false)
- postAsMultipart()
- postAsUrlencoded()
- setConnectionTimeout(int $timeout)
- setFollowRedirectCount(int $followRedirectCount)
- setLogger(LoggerInterface $logger)
- setSafeRequest(bool $safe = true)
- setTimeout(int $timeout)

## HeaderRequest class reference
- __construct(array $headers = [], array $cookies = [])
- addField(string $name, string $value)
- setCookie(string $name, string $value)
- setIfModifiedSince(\DateTime $dateTime)
- setNoCache()
- setReferrer(string $referrer)
- setUserAgent(string $userAgent)
