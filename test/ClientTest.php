<?php
class ClientTest extends \PHPUnit\Framework\TestCase
{
    static private $server;

    public static function setUpBeforeClass()
    {
        $desc = [1=>['pipe','w'], ['pipe','w']];

        static::$server = \proc_open(
            'exec php -S localhost:8089 -t ' . __DIR__ . '/test-server 2>&1',
            $desc,
            $pipes
        );

        register_shutdown_function(function(){
            if (static::$server !== null) {
                static::tearDownAfterClass();
            }
        });
        usleep(500000);
    }

    protected function assertPreConditions()
    {
        $this->assertTrue(proc_get_status(static::$server)['running'], '####### HTTP Server not running ##########');
    }


    public static function tearDownAfterClass()
    {
        \proc_terminate(static::$server);
        \proc_close(static::$server);
        static::$server = null;
    }

    public function testGet()
    {
        $client = new \f2r\SimpleHttp\Client();
        $response = $client->get('http://localhost:8089/test-get.php');
        $this->assertEquals('http://localhost:8089/test-get.php', $response->getUrl());
        $this->assertEquals('http://localhost:8089/test-get.php', $response->getEffectiveUrl());
        $this->assertEquals(200, $response->getHeader()->getHttpCode());
        $this->assertEquals('hello', $response->getBody());
        $this->assertEquals('foo', $response->getHeader()->get('x-test'));
        $this->assertNull($response->getHeader()->get('unknown'));
    }

    public function testGetWithRedirect()
    {
        $client = new \f2r\SimpleHttp\Client();
        $response = $client->get('http://localhost:8089/redirect.php?i=2');
        $this->assertTrue($response->hasRedirection());
        $this->assertEquals(2, $response->getRedirections()->count());
        $this->assertEquals('http://localhost:8089/redirect.php?i=2', $response->getUrl());
        $this->assertEquals('http://localhost:8089/redirect.php?i=0', $response->getEffectiveUrl());
        $this->assertEquals(200, $response->getHeader()->getHttpCode());
        $this->assertEquals('hello redirect', $response->getBody());
        $this->assertEquals('foo', $response->getHeader()->get('x-test'));
        $this->assertNull($response->getHeader()->get('unknown'));
    }


}