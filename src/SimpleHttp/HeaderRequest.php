<?php
namespace f2r\SimpleHttp;

class HeaderRequest
{
    const DEFAULT_USER_AGENT = 'SimpleHttp';
    private $headers;
    private $cookies;

    public function __construct(array $headers = [], array $cookies = [])
    {
        $this->headers = $headers;
        $this->cookies = $cookies;
    }

    public function addField($name, $value)
    {
        $this->headers[strtolower($name)] = $value;
        return $this;
    }

    public function getHeaders()
    {
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        if ($this->cookies !== []) {
            $headers[] = 'cookie: ' . http_build_query($this->cookies, null, '; ');
        }
        if (isset($this->headers['user-agent']) === false) {
            $headers[] = 'user-agent: ' . static::DEFAULT_USER_AGENT;
        }
        return $headers;
    }

    public function setCookie($name, $value)
    {
        $this->cookies[(string)$name] = (string)$value;
        return $this;
    }

    public function setReferrer($referrer)
    {
        $this->headers['referer'] = (string)$referrer;
        return $this;
    }

    public function setUserAgent($userAgent)
    {
        $this->headers['user-agent'] = (string)$userAgent;
        return $this;
    }

    public function setIfModifiedSince(\DateTime $dateTime)
    {
        $tz = new \DateTimeZone('+0');
        $dateTime->setTimezone($tz);
        $this->headers['if-modified-since'] = $dateTime->format('D, d M Y H:i:s') . ' GMT';
        return $this;
    }

    public function setNoCache()
    {
        $this->headers['cache-control'] = 'no-cache';
        return $this;
    }
}
