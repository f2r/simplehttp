<?php
namespace f2r\SimpleHttp;

class Response
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $effectiveUrl;

    /**
     * @var Redirections
     */
    private $redirected;

    /**
     * @var \f2r\SimpleHttp\HeaderResponse
     */
    private $header;

    /**
     * @var string
     */
    private $body;

    public function __construct(string $url, HeaderResponse $header, string $body)
    {
        $this->url = $url;
        $this->effectiveUrl = $url;
        $this->redirected = null;

        $this->header = $header;
        $this->body = $body;
    }

    public function setEffectiveUrl(string $url)
    {
        $this->effectiveUrl = $url;
        return $this;
    }

    public function setRedirected(Redirections $redirected)
    {
        $this->redirected = $redirected;
        return $this;
    }

    public function hasRedirection()
    {
        if ($this->redirected === null) {
            return false;
        }

        return count($this->redirected) > 0;
    }

    public function getRedirections()
    {
        return $this->redirected;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getEffectiveUrl()
    {
        return $this->effectiveUrl;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function json()
    {
        $data = json_decode($this->body, true);
        if ($data === null) {
            throw new Exception('Could not parse json response: ' . json_last_error_msg(), 500);
        }

        return $data;
    }

}
