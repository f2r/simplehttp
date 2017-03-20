<?php
namespace f2r\SimpleHttp;

class Response
{
    /**
     * @var \f2r\SimpleHttp\CurlInfo
     */
    private $curlInfo;

    /**
     * @var \f2r\SimpleHttp\HeaderResponse
     */
    private $header;

    /**
     * @var string
     */
    private $body;

    public function __construct(HeaderResponse $header, $body, CurlInfo $curlInfo)
    {
        $this->curlInfo = $curlInfo;
        $this->header = $header;
        $this->body = $body;
    }

    public function getCurlInfo()
    {
        $this->curlInfo;
    }

    public function getHttpCode()
    {
        return (int)$this->curlInfo->getHttpCode();
    }

    public function getUrl()
    {
        return $this->curlInfo->getUrl();
    }

    public function getContenType()
    {
        return $this->curlInfo->getContentType();
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
