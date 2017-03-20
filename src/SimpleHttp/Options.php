<?php
namespace f2r\SimpleHttp;

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
     * @var array|null
     */
    private $hostsWhiteList;

    /**
     * @var array
     */
    private $hostsBlackList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct()
    {
        $this->connectionTimeout = static::DEFAULT_CONNECTION_TIMEOUT;
        $this->timeout = static::DEFAULT_TIMEOUT;
        $this->followRedirectCount = static::DEFAULT_FOLLOW_REDIRECT_COUNT;
        $this->hostsBlackList = [];
        $this->logger = new NullLogger();
    }

    /**
     * @param string|array $regexp
     * @return $this
     */
    public function addHostWhiteList($regexp)
    {
        if ($this->hostsWhiteList === null) {
            $this->hostsWhiteList = [];
        }
        foreach ((array)$regexp as $pattern) {
            $this->hostsWhiteList[] = $pattern;
        }
        return $this;
    }

    /**
     * @param string|array $regexp
     * @return $this
     */
    public function addHostBlackList($regexp)
    {
        foreach ((array)$regexp as $pattern) {
            $this->hostsBlackList[] = $pattern;
        }
        return $this;
    }

    /**
     * @param string $host
     * @return bool
     */
    public function isHostValid($host)
    {
        foreach ($this->hostsBlackList as $pattern) {
            if (preg_match($pattern, $host) === 1) {
                return false;
            }
        }
        if ($this->hostsWhiteList === null) {
            return true;
        }
        foreach ($this->hostsWhiteList as $pattern) {
            if (preg_match($pattern, $host) === 1) {
                return true;
            }
        }
        return false;
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
        $this->connectionTimeout = $timeout;
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
        $this->timeout = $timeout;
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
     * @param \Psr\Log\LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
