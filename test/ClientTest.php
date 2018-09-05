<?php
use f2r\CurlStub\CurlStub;
use function f2r\CurlStub\loadFile;
use f2r\SimpleHttp\Options;
use f2r\SimpleHttp\HeaderRequest;
use f2r\SimpleHttp\Client;

require 'stub/rewrite-class-with-curl.php';

loadFile(__DIR__ . '/../src/SimpleHttp/Client.php');

class ClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var int
     */
    private $httpCode = null;

    /**
     * @var array
     */
    private $info;

    /**
     * @var string
     */
    private $error;

    /**
     * @var string
     */
    private $httpResponse = null;

    /**
     * @var array
     */
    private $curlOptions;


    /**
     * @var \f2r\SimpleHttp\Client
     */
    private $client = null;

    /**
     * @var array
     */
    private $assertOptions = null;

    public function setUp()
    {
        $this->assertOptions = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_TIMEOUT' => 300,
            'CURLOPT_CONNECTTIMEOUT' => 120,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS' => 5,
            'CURLOPT_HTTPHEADER' => ['user-agent: phpunit'],
            'CURLOPT_HEADER' => false,
            'CURLINFO_HEADER_OUT' => false,
            'CURLOPT_NOSIGNAL' => true,
        ];

        $consts = [];
        foreach (get_defined_constants(true)['curl'] as $name => $value) {
            if (substr($name, 7) === 'CURLOPT') {
                $consts[$value] = $name;
            }
        }

        $this->httpCode = 200;

        CurlStub::getInstance()->setCallback('curl_init', function() {
            return 42;
        });
        CurlStub::getInstance()->setCallback('curl_getinfo', function($ch) {
            $this->assertEquals(42, $ch);
            return $this->info;
        });
        CurlStub::getInstance()->setCallback('curl_error', function($ch) {
            $this->assertEquals(42, $ch);
            return $this->error;
        });

        CurlStub::getInstance()->setCallback('curl_exec', function($ch) {
            $this->assertEquals(42, $ch);
            $headerFunction = null;
            if (isset($this->curlOptions[CURLOPT_HEADERFUNCTION])) {
                $headerFunction = $this->curlOptions[CURLOPT_HEADERFUNCTION];
            }
            $httpResponse = $this->httpResponse;
            $baseUrl = null;
            $lastUrl = $this->curlOptions[CURLOPT_URL];
            if (preg_match('`^\w+://[^/]+`', $lastUrl, $match)) {
                $baseUrl = $match[0];
            }
            do {
                list($head, $body) = explode("\r\n\r\n", $httpResponse, 2);
                $location = null;
                foreach (explode("\r\n", $head . "\r\n") as $line) {
                    if (preg_match('`^HTTP/[0-9.]+\s+([0-9]+)`', $line, $match) === 1) {
                        $this->info['http_code'] = (int)$match[1];
                    } elseif (preg_match('`^location:\s*(.*)`i', $line, $match)) {
                        $location = trim($match[1]);
                        if ($location[0] === '/') {
                            $location = $baseUrl . $location;
                        }
                        if (preg_match('`^\w+://[^/]+`', $location, $match)) {
                            $baseUrl = $match[0];
                        }
                        $lastUrl = $location;
                    }
                    if ($headerFunction !== null) {
                        $headerFunction($ch, $line . "\r\n");
                    }
                }
                $httpResponse = $body;
            } while ($location !== null);

            $this->info['url'] = $lastUrl;

            return $body;
        });
        CurlStub::getInstance()->setCallback('curl_setopt_array', function($ch, $args) {
            $this->assertEquals(42, $ch);
            $this->curlOptions = $args;
        });
        CurlStub::getInstance()->setCallback('curl_setopt', function($ch, $option, $value) {
            $this->assertEquals(42, $ch);
            $this->curlOptions[$option] = $value;
        });

        $options = new Options();
        $options->setConnectionTimeout(120);
        $options->setTimeout(300);
        $options->setFollowRedirectCount(30);

        $header = new HeaderRequest();
        $header->setUserAgent('phpunit');
        $this->client = new Client($header, $options);
    }

    public function testGet()
    {
        $this->httpResponse = "HTTP/1.1 200 OK\r\nX-Test: foo\r\n\r\nbody content";
        $response = $this->client->get('http://test');
        $this->assertEquals('http://test', $response->getUrl());
        $this->assertEquals('http://test', $response->getEffectiveUrl());
        $this->assertEquals(200, $response->getHeader()->getHttpCode());
        $this->assertEquals('body content', $response->getBody());
        $this->assertEquals('foo', $response->getHeader()->get('x-test'));
        $this->assertNull($response->getHeader()->get('unknown'));
    }

    public function testGetWithRedirect()
    {
        $this->httpResponse = "HTTP/1.1 302 Move temporary\r\nlocation: /test\r\nunknown: bar\r\n\r\nHTTP/1.1 200 OK\r\nX-Test: foo\r\n\r\nbody content";
        $response = $this->client->get('http://test');
        $this->assertTrue($response->hasRedirection());
        $this->assertEquals('http://test/test', $response->getEffectiveUrl());
        $this->assertEquals(200, $response->getHeader()->getHttpCode());
        $this->assertEquals('body content', $response->getBody());
        $this->assertEquals('foo', $response->getHeader()->get('x-test'));
        $this->assertNull($response->getHeader()->get('unknown'));
    }

    public function testPut()
    {
        $this->httpResponse = "HTTP/1.1 200 OK\r\n\r\nbody content";
        $response = $this->client->put('http://test', ['x' => 1, 'y' => 2, 'string' => 'a string', 'boolean' => true]);
        $this->assertEquals('http://test', $response->getUrl());
        $this->assertEquals(200, $response->getHeader()->getHttpCode());
        $this->assertEquals('body content', $response->getBody());
        $this->assertArrayHasKey(CURLOPT_CUSTOMREQUEST, $this->curlOptions, 'CURLOPT_CUSTOMREQUEST');
        $this->assertEquals($this->curlOptions[CURLOPT_CUSTOMREQUEST], 'PUT');
        $this->assertArrayHasKey(CURLOPT_SAFE_UPLOAD, $this->curlOptions, 'CURLOPT_SAFE_UPLOAD');
        $this->assertEquals($this->curlOptions[CURLOPT_SAFE_UPLOAD], true);
        $this->assertArrayHasKey(CURLOPT_POSTFIELDS, $this->curlOptions, 'CURLOPT_POSTFIELDS');
        $this->assertEquals($this->curlOptions[CURLOPT_POSTFIELDS], 'x=1&y=2&string=a+string&boolean=1');
    }

    public function testProtocol()
    {
        $this->expectException('f2r\SimpleHttp\Exception\ForbiddenProtocolException');
        $this->httpResponse = "HTTP/1.1 200 OK\r\n\r\nbody content";
        $this->client->get('ftp://localhost');
    }

    public function testProtocolOnRedirect()
    {
        $this->expectException('f2r\SimpleHttp\Exception\ForbiddenProtocolException');
        $this->httpResponse = "HTTP/1.1 302 Move temporary\r\nlocation: ftp://localhost\r\n\r\nbody content";
        $this->client->get('http://test');
    }
    public function testInvalidCharacter()
    {
        $this->expectException('f2r\SimpleHttp\Exception\InvalidCharacterException');
        $this->httpResponse = "HTTP/1.1 200 OK\r\n\r\nbody content";
        $this->client->get("https://\x00");
    }
}
