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
            $headers[] = 'cookie: ' . implode(';', $this->cookies);
        }
        if (isset($headers['user-agent']) === false) {
            $headers[] = 'user-agent: ' . static::DEFAULT_USER_AGENT;
        }
        return $headers;
    }

    public function setCookie($name, $value)
    {
        $this->cookies[$name] = $value;
        return $this;
    }

    public function setReferrer($referrer)
    {
        $this->headers['referer'] = $referrer;
        return $this;
    }

    public function setUserAgent($userAgent)
    {
        $this->headers['user-agent'] = $userAgent;
        return $this;
    }
}
