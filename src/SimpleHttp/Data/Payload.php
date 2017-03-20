<?php
namespace f2r\SimpleHttp\Data;

use f2r\SimpleHttp\DataInterface;

class Payload implements DataInterface
{
    /**
     * @var string
     */
    private $payload;

    /**
     * PostPayload constructor.
     *
     * @param string $payload
     */
    public function __construct($payload = '')
    {
        $this->setPayload($payload);
    }

    /**
     * @param string $payload
     * @return $this
     */
    public function setPayload($payload)
    {
        $this->payload = (string)$payload;
        return $this;
    }

    public function __toString()
    {
        return $this->getPayload();
    }

    public function getPayload()
    {
        return $this->payload;
    }

}
