<?php

class HeaderRequestTest extends PHPUnit_Framework_TestCase
{
    public function testEmpty()
    {
        $header = new \f2r\SimpleHttp\HeaderRequest();
        $this->assertEquals([
            'user-agent: ' . \f2r\SimpleHttp\HeaderRequest::DEFAULT_USER_AGENT
        ], $header->getHeaders());
    }

    public function testUserAgent()
    {
        $header = new \f2r\SimpleHttp\HeaderRequest();
        $header->setUserAgent('phpunit');
        $this->assertEquals([
            'user-agent: phpunit'
        ], $header->getHeaders());
    }

    private function assertHeaderContains($needle, $haystack)
    {
        $this->assertContains($needle, $haystack, "found:\n- " . implode("\n- ", $haystack));
    }

    public function testSetCookie()
    {
        $header = new \f2r\SimpleHttp\HeaderRequest();
        $header->setCookie('cookiename', 'cookievalue');
        $header->setCookie('othercookie', 'othervalue');
        $headers = $header->getHeaders();
        $this->assertHeaderContains('user-agent: ' . \f2r\SimpleHttp\HeaderRequest::DEFAULT_USER_AGENT, $headers);
        $this->assertHeaderContains('cookie: cookiename=cookievalue; othercookie=othervalue', $headers);
        $this->assertCount(2, $headers);
    }

    public function testIfModifiedSince()
    {
        $header = new \f2r\SimpleHttp\HeaderRequest();
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('+2'));
        $date->setDate(2017, 5, 8);
        $date->setTime(21, 24, 53);
        $header->setIfModifiedSince($date);
        $this->assertHeaderContains('if-modified-since: Mon, 08 May 2017 19:24:53 GMT', $header->getHeaders());
    }

    public function testNoCache()
    {
        $header = new \f2r\SimpleHttp\HeaderRequest();
        $header->setNoCache();
        $this->assertHeaderContains('cache-control: no-cache', $header->getHeaders());
    }
}
