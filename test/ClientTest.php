<?php
namespace f2r\SimpleHttp;

class CurlMock {
    static private $instance;
    private $callables = [];

    static public function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setCallback($name, callable $callable)
    {
        $this->callables[$name] = $callable;
        return $this;
    }

    public function __call($name, $args)
    {
        if (isset($this->callables[$name])) {
            return call_user_func_array($this->callables[$name], $args);
        }
        return null;
    }
}

function curl_exec($ch)
{
    return CurlMock::getInstance()->curl_exec($ch);
}

function curl_init($url = null)
{
    return CurlMock::getInstance()->curl_init($url);
}

function curl_setopt($ch, $option, $value)
{
    return CurlMock::getInstance()->curl_setopt($ch, $option, $value);
}

function curl_setopt_array($ch, $options)
{
    return CurlMock::getInstance()->curl_setopt_array($ch, $options);
}

function curl_getinfo($ch, $option = null)
{
    return CurlMock::getInstance()->curl_getinfo($ch, $option);
}

function curl_error($ch)
{
    return CurlMock::getInstance()->curl_error($ch);
}

function curl_close($ch)
{
    return CurlMock::getInstance()->curl_close($ch);
}


class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $httpResponse = null;

    /**
     * @var int
     */
    private $httpCode = null;

    private $redirect = null;

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
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => false,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['user-agent: phpunit'],
            CURLOPT_HEADER => true
        ];

        $this->httpCode = 200;
        $this->redirect = '';

        CurlMock::getInstance()->setCallback('curl_init', function() {
            return 42;
        });
        CurlMock::getInstance()->setCallback('curl_getinfo', function($ch) {
            $this->assertEquals(42, $ch);
            return [
                'redirect_url' => $this->redirect,
                'http_code' => $this->httpCode
            ];
        });
        CurlMock::getInstance()->setCallback('curl_error', function($ch) {
            $this->assertEquals(42, $ch);
            return '';
        });

        CurlMock::getInstance()->setCallback('curl_exec', function($ch) {
            $this->assertEquals(42, $ch);
            return $this->httpResponse;
        });
        CurlMock::getInstance()->setCallback('curl_setopt_array', function($ch, $args) {
            $this->assertEquals(42, $ch);
            $this->assertEquals($this->assertOptions, $args);
        });
        CurlMock::getInstance()->setCallback('curl_setopt', function($ch, $option, $value) {
            $this->assertEquals(42, $ch);
            if ($option === CURLOPT_URL) {
                $this->assertEquals('http://test', $value);
            }
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
        $this->httpResponse = "HTTP/1.1 200 OK\r\n\r\nbody content";

        $response = $this->client->get('http://test');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('body content', $response->getBody());
    }

    public function testContinueHeader()
    {
        $this->assertOptions += [
            CURLOPT_SAFE_UPLOAD => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => []
        ];
        $this->httpResponse = "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\n\r\nbody content";
        $response = $this->client->post('http://test');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('body content', $response->getBody());
    }

    public function testPut()
    {
        $this->assertOptions += [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_SAFE_UPLOAD => true,
            CURLOPT_POSTFIELDS => 'x=1&y=2&string=a+string&boolean=1'
        ];
        $this->httpResponse = "HTTP/1.1 200 OK\r\n\r\nbody content";
        $response = $this->client->put('http://test', ['x' => 1, 'y' => 2, 'string' => 'a string', 'boolean' => true]);
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('body content', $response->getBody());
    }

    public function testProtocol()
    {
        $this->setExpectedException('f2r\SimpleHttp\Exception\ForbiddenProtocolException');
        $this->client->get('ftp://localhost');
    }

    public function testProtocolOnRedirect()
    {
        $this->setExpectedException('f2r\SimpleHttp\Exception\ForbiddenProtocolException');
        $this->httpResponse = "HTTP/1.1 302 Move temporary";
        $this->redirect = 'ftp://localhost';
        $this->client->get('http://test');
    }

    public function testTooManyRedirect()
    {
        $this->setExpectedException('f2r\SimpleHttp\Exception\TooManyRedirectionsException');
        $this->httpResponse = "HTTP/1.1 302 Move temporary";
        $this->redirect = 'http://test';
        $this->client->get('http://test');
    }
}
