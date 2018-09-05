<?php
namespace f2r\SimpleHttp;

use f2r\SimpleHttp\Exception\HostPatternException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Options
{
    const DEFAULT_CONNECTION_TIMEOUT = 10;
    const DEFAULT_TIMEOUT = 30;
    const DEFAULT_FOLLOW_REDIRECT_COUNT = 10;

    /**
     * @var int
     */
    private $connectionTimeout;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var bool
     */
    private $followRedirectCount;

    /**
     * @var bool
     */
    private $postAsUrlEncoded;

    public function __construct()
    {
        $this->connectionTimeout = static::DEFAULT_CONNECTION_TIMEOUT;
        $this->timeout = static::DEFAULT_TIMEOUT;
        $this->followRedirectCount = static::DEFAULT_FOLLOW_REDIRECT_COUNT;
        $this->postAsUrlEncoded = false;
    }

    /**
     * @return int
     */
    public function getFollowRedirectCount()
    {
        return $this->followRedirectCount;
    }

    /**
     * @param int $followRedirectCount
     * @return $this
     */
    public function setFollowRedirectCount($followRedirectCount)
    {
        $this->followRedirectCount = (int)$followRedirectCount;
        return $this;
    }

    /**
     * @param int $timeout Timeout in seconds
     * @return $this
     */
    public function setConnectionTimeout($timeout)
    {
        $this->connectionTimeout = (int)$timeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int)$timeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return $this
     */
    public function enablePostAsUrlEncoded()
    {
        $this->postAsUrlEncoded = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disablePostAsUrlEncoded()
    {
        $this->postAsUrlEncoded = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPostAsUrlEncoded()
    {
        return $this->postAsUrlEncoded;
    }
}
