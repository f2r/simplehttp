# SimpleHTTP
Simple HTTP client

Simplehttp aims to be the simplest HTTP requesting library with strict forward compatibility. SimpleHTTP v2.0 will never exist !
SimpleHTTP, however, may (and will) evolve, thus if any incompatible functionality is added, it will be created in an additional class or namespace.

It is a wish for the end of conflicted versions like "guzzle dependency hell".

In addition, SimpleHttp comes with a Server Side Request Forgery protection (SSRF).

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
]); // files in $_FILES['first'] and $_FILES['second'] 
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
$client->get('http://www.mysite.com');
```

## Enable SSRF protection
```php
<?php
$client = new f2r\SimpleHttp\Client();
$client->getOptions()->enableSsrfProtection();
$client->get($userWebSiteUrl);
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
- setConnectionTimeout(int $timeout)
- setTimeout(int $timeout)
- setFollowRedirectCount(int $followRedirectCount)
- setLogger(LoggerInterface $logger)
- enableSsrfProtection()
- disableSsrfProtection()
- enablePostAsUrlEncoded()
- disablePostAsUrlEncoded()

## HeaderRequest class reference
- __construct(array $headers = [], array $cookies = [])
- addField(string $name, string $value)
- setCookie(string $name, string $value)
- setIfModifiedSince(\DateTime $dateTime)
- setNoCache()
- setReferrer(string $referrer)
- setUserAgent(string $userAgent)
