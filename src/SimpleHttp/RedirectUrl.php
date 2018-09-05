<?php
namespace f2r\SimpleHttp;

class RedirectUrl
{
    private $code;
    private $url;
    private $source;

    public function __construct(int $code, string $url)
    {
        $this->code = $code;
        $this->url = $url;
    }
    public function getCode(): int
    {
        return $this->code;
    }
    public function getUrl(): string
    {
        return $this->url;
    }

    public function setSourceUrl(string $from): self
    {
        $this->source = $from;
        return $this;
    }
    public function getSourceUrl(): string
    {
        return $this->source;
    }

}