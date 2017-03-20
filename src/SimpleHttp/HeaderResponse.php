<?php
namespace f2r\SimpleHttp;

class HeaderResponse
{
    private $header;

    public function __construct(array $header)
    {
        $this->header = $header;
    }

    public function get($name)
    {
        if (isset($this->header[$name])) {
            return $this->header[$name];
        }
        return null;
    }
}
