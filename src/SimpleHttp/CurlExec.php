<?php

namespace f2r\SimpleHttp;


class CurlExec
{
    /**
     * @var Options
     */
    protected $options;

    /**
     * Used for testing mode : all client requests will use this callable. If callable does not return a Response object
     * Curl request is executed
     *
     * @var callable
     */
    protected static $debugHook;

    /**
     * @param callable|null $debugHook
     */
    public static function setDebugHook(callable $debugHook = null)
    {
        static::$debugHook = $debugHook;
    }

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function request($url, array $curlOpt)
    {
        $response = null;
        if (static::$debugHook !== null) {
            $response = call_user_func(static::$debugHook, $url, $curlOpt);
        }

        if ($response !== null) {
            return $response;
        }

        $originalUrl = $url;
        $logger = $this->options->getLogger();
        $logMessage = 'Request URL: ';
        $redirectCount = $this->options->getFollowRedirectCount();
        if ($this->options->hasHostFiltering() === false) {
            $curlOpt[CURLOPT_FOLLOWLOCATION] = true;
            $curlOpt[CURLOPT_MAXREDIRS] = $redirectCount;
            $curlOpt[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP + CURLPROTO_HTTPS;
            $redirectCount = 0;
        } else {
            $curlOpt[CURLOPT_FOLLOWLOCATION] = false;
        }
        $curlOpt[CURLOPT_RETURNTRANSFER] = true;
        $curlOpt[CURLOPT_HEADER] = true;
        $curlOpt[CURLINFO_HEADER_OUT] = true;
        $ch = curl_init();
        curl_setopt_array($ch, $curlOpt);
        do {
            $this->throwOnForbiddenProtocol($url);
            $this->throwOnForbiddenHost($url);
            $this->throwOnInvalidCharacter($url);
            curl_setopt($ch, CURLOPT_URL, $url);
            $logger->debug($logMessage . $url);
            $this->throwOnUnsafeRequest($ch);
            $exec = $this->execute($ch);
            $result = $this->decomposeRawResponse($exec['raw']);
            $this->throwOnError($exec['error'], $result, $url);
            $url = $exec['info']['redirect_url'];
            $logMessage = 'Redirect URL: ';
        } while ($url !== '' and $redirectCount-- > 0);
        curl_close($ch);

        if ($url !== '' and $redirectCount <= 0) {
            throw new Exception\TooManyRedirectionsException(
                'Too many redirections (max ' . $this->options->getFollowRedirectCount() . ') for URL: ' . $originalUrl
            );
        }

        return new Response(
            new HeaderResponse($result['headers']),
            $result['body'],
            new CurlInfo($exec['info'])
        );
    }

    protected function execute($ch)
    {
        return [
            'raw' => curl_exec($ch),
            'info' => curl_getinfo($ch),
            'error' => curl_error($ch)
        ];
    }

    protected function decomposeRawResponse($raw)
    {
        $matchCount = preg_match_all('`^HTTP/\d+\.\d+\s+(\d+)\s+(.*)`m', $raw, $matches, PREG_OFFSET_CAPTURE);
        if ($matchCount === 0) {
            throw new Exception\UnknownHttpErrorException('Could not parse HTTP response:' . $raw);
        }
        $i = 0;
        foreach ($matches[1] as $i => $match) {
            if (in_array($match[0], [100, 301, 302, 308, 309]) === false) {
                break;
            }
        }
        $raw = substr($raw, $matches[0][$i][1]);
        $split = explode("\r\n\r\n", $raw, 2);
        $body = '';
        if (isset($split[1])) {
            $body = trim($split[1]);
        }
        $head = explode("\r\n", $split[0]);
        $proto = array_shift($head);
        $split = explode(' ', $proto, 3);
        list ($httpVersion, $httpCode, $httpMessage) = $split;

        $headers = [];
        foreach ($head as $line) {
            list ($name, $value) = explode(':', $line);
            $headers[trim($name)] = trim($value);
        }
        return [
            'http_version' => $httpVersion,
            'http_code' => (int)$httpCode,
            'http_message' => $httpMessage,
            'headers' => $headers,
            'body' => $body
        ];
    }

    /**
     * @param string $error
     * @param $result
     * @param $url
     * @throws Exception\HttpErrorException
     * @throws Exception\UnknownHttpErrorException
     */
    protected function throwOnError($error, $result, $url)
    {
        if ($error !== '') {
            $this->options->getLogger()->debug('Error requesting "' . $url . '": ' . $error);
            throw new Exception\UnknownHttpErrorException($error);
        }

        if ($result['http_code'] < 400) {
            return;
        }

        $this->options->getLogger()->debug("Request error for URL \"$url\": [$result[http_code] $result[http_message]");
        throw new Exception\HttpErrorException($result['http_message'], $result['http_code']);
    }

    /**
     * @param $ch
     * @throws \f2r\SimpleHttp\Exception\SsrfException
     */
    protected function throwOnUnsafeRequest($ch)
    {
        if ($this->options->isSsrfProtected() === false) {
            return;
        }
        curl_setopt($ch, CURLOPT_CONNECT_ONLY, true);
        curl_exec($ch);
        $ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        $ip = preg_replace('`(?<=^|:)0+(?=:|$)`', '', $ip);
        try {
            if (stripos($ip, 'fd') === 0) {
                throw new Exception\SsrfException('Private IPV6 network requested: ' . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            }
            if (stripos($ip, 'fc') === 0) {
                throw new Exception\SsrfException('Local IPV6 network requested: ' . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            }
            if (stripos($ip, 'fe80') === 0) {
                throw new Exception\SsrfException('Local link IPV6 network requested: ' . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            }
            if ($ip === '::1') {
                throw new Exception\SsrfException('Loop back IPV6 network requested: ' . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            }

            $ipv6Message = '';
            if (preg_match('`^::ffff:([^:]+):([^:]+)`', $ip, $match) === 1) {
                $parts = str_split(str_pad($match[1], 4, '0', STR_PAD_LEFT) . str_pad($match[2], 4, '0', STR_PAD_LEFT), 2);
                foreach ($parts as $i => $h) {
                    $parts[$i] = base_convert($h, 16, 10);
                }
                $ip = implode('.', $parts);
                $ipv6Message = ' mapped into IPV6';
            }
            if (preg_match('`^::ffff:(\d+\.\d+\.\d+\.\d+)`', $ip, $match) === 1) {
                $ip = $match[1];
                $ipv6Message = ' mapped into IPV6';
            }
            if (preg_match('`^(10|192\.168|172\.1[6-9]|172\.2\d|172\.3[0-1])\.`', $ip)) {
                throw new Exception\SsrfException("Private IPV4 network requested$ipv6Message: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            }
            if (strpos($ip, '127.') === 0) {
                throw new Exception\SsrfException("Local IPV4 network requested$ipv6Message: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            }
        } finally {
            curl_setopt($ch, CURLOPT_CONNECT_ONLY, false);
        }
    }

    /**
     * @param string $url
     * @throws \f2r\SimpleHttp\Exception\ForbiddenProtocolException
     */
    protected function throwOnForbiddenProtocol($url)
    {
        if (preg_match('`^https?://`i', $url) === 0) {
            throw new Exception\ForbiddenProtocolException('unsupported protocol scheme: ' . $url);
        }
    }

    /**
     * @param string $url
     * @throws \f2r\SimpleHttp\Exception\ForbiddenHostException
     */
    protected function throwOnForbiddenHost($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($this->options->isHostValid($host) === false) {
            throw new Exception\ForbiddenHostException('Invalid host for requesting: ' . $host, 0);
        }
    }

    /**
     * @param $url
     * @throws \f2r\SimpleHttp\Exception\InvalidCharacterException
     */
    protected function throwOnInvalidCharacter($url)
    {
        if (preg_match('`[\x00-\x20\x22\x3c\x3e\x5c\x5e\x60\x7b-\x7d\x7f-\xff]`', $url, $match) === 1) {
            throw new Exception\InvalidCharacterException(sprintf('Invalid character in URL: \\x%02X', ord($match[0])));
        }
    }
}