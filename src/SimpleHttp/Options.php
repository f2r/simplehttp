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

    /**
     * @var bool
     */
    private $ssrfProtection;

    /**
     * @var bool
     */
    private $postAsUrlEncoded;

    public function __construct()
    {
        $this->connectionTimeout = static::DEFAULT_CONNECTION_TIMEOUT;
        $this->timeout = static::DEFAULT_TIMEOUT;
        $this->followRedirectCount = static::DEFAULT_FOLLOW_REDIRECT_COUNT;
        $this->hostsBlackList = [];
        $this->hostsWhiteList = [];
        $this->logger = new NullLogger();
        $this->ssrfProtection = false;
        $this->postAsUrlEncoded = false;
    }

    private function hostPattern($pattern, $isRegexp)
    {
        if ($isRegexp === false) {
            $pattern = '`^' . preg_quote($pattern, '`') . '$`i';
        }
        if (@preg_match($pattern, '') === false) {
            throw new HostPatternException('Regexp pattern error: ' . $pattern);
        }
        return $pattern;
    }

    /**
     * @param      $host
     * @param bool $isRegexp
     * @return $this
     * @internal param array|string $regexp
     */
    public function addHostWhiteList($host, $isRegexp = false)
    {
        foreach ((array)$host as $pattern) {
            $this->hostsWhiteList[] = $this->hostPattern($pattern, $isRegexp);
        }
        return $this;
    }

    /**
     * @param string|array  $host
     * @param bool          $isRegexp
     * @return $this
     * @internal param array|string $regexp
     */
    public function addHostBlackList($host, $isRegexp = false)
    {
        foreach ((array)$host as $pattern) {
            $this->hostsBlackList[] = $this->hostPattern($pattern, $isRegexp);
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
        if ($this->hostsWhiteList === []) {
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

    /**
     * @return $this
     */
    public function enableSsrfProtection()
    {
        $this->ssrfProtection = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableSsrfProtection()
    {
        $this->ssrfProtection = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSsrfProtected()
    {
        return $this->ssrfProtection;
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
