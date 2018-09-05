<?php
namespace f2r\SimpleHttp\Feature;


use f2r\SimpleHttp\FeaturePoint\EndRequest;
use f2r\SimpleHttp\FeaturePoint\StartRequest;
use f2r\SimpleHttp\HeaderResponse;
use f2r\SimpleHttp\Redirections;
use f2r\SimpleHttp\Response;
use Psr\SimpleCache\CacheInterface;

class Cache implements StartRequest, EndRequest
{
    const DEFAULT_TIME_TO_LIVE = 60; // 1 minute

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $cachePrefix;

    /**
     * @var int
     */
    private $timeToLive;

    /**
     * @var string
     */
    private $lastCacheKey;

    public function __construct(CacheInterface $cache, string $cachePrefix = null)
    {
        $this->cache = $cache;
        if ($cachePrefix === null) {
            $cachePrefix = 'simplehttp_request_';
        }
        $this->cachePrefix = $cachePrefix;
        $this->timeToLive = self::DEFAULT_TIME_TO_LIVE;
    }

    public function setTtl(int $ttl)
    {
        $this->timeToLive = $ttl;
        return $this;
    }

    public function onRequest(string $method, string $url, array $data = null): ?Response
    {
        $this->lastCacheKey = $this->cachePrefix . md5($method . ':' . $url . '?' . serialize($data));
        $body = $this->cache->get($this->lastCacheKey);
        if ($body === null) {
            return null;
        }
        return new Response($url, new HeaderResponse([]), $body);
    }

    public function onResponse(array $info, array $header, string $body, Redirections $redirections): ?Response
    {
        $this->cache->set($this->lastCacheKey, $body, $this->timeToLive);
        return null;
    }

}