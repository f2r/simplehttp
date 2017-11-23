<?php
namespace f2r\SimpleHttp;


class AsyncResponse extends Response
{
    /**
     * @var Response
     */
    private $response;

    private $callback;

    /**
     * AsyncResponse constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->response = null;
        $this->callback = $callback;
    }

    private function getResponse()
    {
        if ($this->response === null) {
            $this->response = call_user_func($this->callback);
        }


        return $this->response;
    }
    public function getBody()
    {
        return $this->getResponse()->getBody();
    }
}