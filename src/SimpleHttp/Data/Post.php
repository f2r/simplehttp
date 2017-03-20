<?php
namespace f2r\SimpleHttp\Data;

use f2r\SimpleHttp\DataInterface;

class Post implements DataInterface, \ArrayAccess
{
    /**
     * @var array
     */
    private $data;

    /**
     * Post constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        return http_build_query($this->data);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getPayload();
    }
}
