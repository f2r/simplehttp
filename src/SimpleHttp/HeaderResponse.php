<?php
namespace f2r\SimpleHttp;

class HeaderResponse implements \Serializable
{
    private $header;

    public function __construct(array $header)
    {
        $this->header = $header;
    }

    public function getHttpCode(): int
    {
        return $this->header['http']['code'] ?? null;
    }

    public function getHttpMessage(): string
    {
        return $this->header['http']['message'] ?? null;
    }

    public function get($name)
    {
        return $this->header[$name] ?? null;
    }

    public function getAll()
    {
        return $this->header;
    }

    public function serialize()
    {
        return \serialize($this->header);
    }

    public function unserialize($serialized)
    {
        $this->header = \unserialize($serialized);
    }
}
