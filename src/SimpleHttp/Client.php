<?php
namespace f2r\SimpleHttp;

class Client
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_HEAD = 'HEAD';

    /**
     * @var HeaderRequest
     */
    private $header;

    /**
     * @var Options
     */
    private $options;

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

    public function __construct(HeaderRequest $header = null, Options $options = null)
    {
        $this->setHeader($header);
        $this->setOptions($options);
    }

    /**
     * @return \f2r\SimpleHttp\Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param \f2r\SimpleHttp\Options|null $options
     * @return $this
     */
    public function setOptions(Options $options = null)
    {
        if ($options === null) {
            $options = new Options();
        }
        $this->options = $options;
        return $this;
    }

    /**
     * @return \f2r\SimpleHttp\HeaderRequest
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param \f2r\SimpleHttp\HeaderRequest|null $header
     * @return $this
     */
    public function setHeader(HeaderRequest $header = null)
    {
        if ($header === null) {
            $header = new HeaderRequest();
        }
        $this->header = $header;
        return $this;
    }

    /**
     * @param string                             $method
     * @param string                            $url
     * @param array|string|null $data
     * @return \f2r\SimpleHttp\Response
     *
     * @throws Exception
     */
    public function request($method, $url, $data = null)
    {
        $curlOpt = [
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => false,
            CURLOPT_TIMEOUT => $this->options->getTimeout(),
            CURLOPT_CONNECTTIMEOUT => $this->options->getConnectionTimeout(),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => $this->header->getHeaders(),
            CURLOPT_HEADER => true
        ];

        switch ($method) {
            case self::METHOD_DELETE:
                $curlOpt[CURLOPT_CUSTOMREQUEST] = self::METHOD_DELETE;
                $data = null;
                break;
            case self::METHOD_HEAD:
                $curlOpt[CURLOPT_CUSTOMREQUEST] = self::METHOD_HEAD;
                $data = null;
                break;
            case self::METHOD_GET:
                $data = null;
                break;
            case self::METHOD_PUT:
                $curlOpt[CURLOPT_CUSTOMREQUEST] = self::METHOD_PUT;
                $curlOpt[CURLOPT_SAFE_UPLOAD] = true;
                if (is_array($data)) {
                    $data = http_build_query($data);
                }
                $curlOpt[CURLOPT_POSTFIELDS] = (string)$data;
                break;
            case self::METHOD_POST:
                $curlOpt[CURLOPT_SAFE_UPLOAD] = true;
                $curlOpt[CURLOPT_POST] = true;
                $curlOpt[CURLOPT_POSTFIELDS] = $this->buildData($data);
                break;
            default:
                throw new Exception\HttpMethodNotSupportedException($method . ' HTTP method is not currently supported');
        }

        return $this->executeCurl($url, $curlOpt);
    }

    /**
     * @param string $url
     * @return \f2r\SimpleHttp\Response
     * @throws Exception
     */
    public function get($url)
    {
        return $this->request(self::METHOD_GET, $url);
    }

    /**
     * @param string $url
     * @param array  $data
     * @return \f2r\SimpleHttp\Response
     * @throws Exception
     */
    public function post($url, array $data = [])
    {
        return $this->request(self::METHOD_POST, $url, $data);
    }

    /**
     * @param string $url
     * @param string $data
     * @return \f2r\SimpleHttp\Response
     * @throws Exception
     */
    public function put($url, $data = null)
    {
        return $this->request(self::METHOD_PUT, $url, $data);
    }

    /**
     * @param string $url
     * @return \f2r\SimpleHttp\Response
     * @throws Exception
     */
    public function head($url)
    {
        return $this->request(self::METHOD_HEAD, $url);
    }

    /**
     * @param string $url
     * @return \f2r\SimpleHttp\Response
     * @throws Exception
     */
    public function delete($url)
    {
        return $this->request(self::METHOD_DELETE, $url);
    }

    /**
     * @param string        $url
     * @param string|array  $files
     * @param array         $additionalData
     * @return \f2r\SimpleHttp\Response
     * @throws \Exception
     * @throws \f2r\SimpleHttp\Exception\UploadException
     */
    public function uploadFile($url, $files, array $additionalData = [])
    {
        if (is_string($files)) {
            $files = ['file' => $files];
        } elseif (is_array($files) === false) {
            throw new \Exception('$files parameter must be a string or an array, ' . gettype($files) . ' given');
        }
        foreach ($files as $name => $file) {
            if (file_exists($file) === false) {
                throw new Exception\UploadException('File does not exist: ' . $file);
            }
            if (is_readable($file) === false) {
                throw new Exception\UploadException('File is not readable: ' . $file);
            }
            $files[$name] = new \CURLFile($file);
            $files[$name]->setMimeType(mime_content_type($file));
        }
        return $this->request(self::METHOD_POST, $url, $files + $additionalData);
    }

    /**
     * @param string $error
     * @param string $raw
     * @throws \f2r\SimpleHttp\Exception, \f2r\SimpleHttp\Exception\HttpErrorException
     */
    private function throwOnError($error, $raw, $url)
    {
        if ($error !== '') {
            $this->getOptions()->getLogger()->debug('Error requesting "' . $url . '": ' . $error);
            throw new Exception\UnknownHttpErrorException($error);
        }

        if (preg_match('`^HTTP/\d+\.\d+\s+(\d+)\s+(.+)`', $raw, $match) === 0) {
            $this->getOptions()->getLogger()->debug('Could not understand HTTP response for URL: ' . $url);
            throw new Exception\UnknownHttpErrorException('Could not understand HTTP response');
        }

        $httpCode = (int)$match[1];
        $httpMessage = trim($match[2]);

        if ($httpCode < 400) {
            return;
        }

        $this->getOptions()->getLogger()->debug("Request error for URL \"$url\": [$httpCode] $httpMessage");
        throw new Exception\HttpErrorException($httpMessage, $httpCode);
    }

    /**
     * @param string $url
     * @param array  $curlOpt
     * @return Response
     * @throws Exception
     */
    private function executeCurl($url, array $curlOpt)
    {
        $response = null;
        if (static::$debugHook !== null) {
            $response = call_user_func(static::$debugHook, $url, $curlOpt);
        }

        if ($response !== null) {
            return $response;
        }

        $originalUrl = $url;
        $logger = $this->getOptions()->getLogger();
        $logMessage = 'Request URL: ';
        $redirectCount = $this->options->getFollowRedirectCount();
        $ch = curl_init();
        curl_setopt_array($ch, $curlOpt);
        do {
            $this->throwOnForbiddenProtocol($url);
            $this->throwOnForbiddenHost($url);
            $this->throwOnInvalidCharacter($url);
            curl_setopt($ch, CURLOPT_URL, $url);
            $logger->debug($logMessage . $url);
            $this->throwOnUnsafeRequest($ch);
            $raw = curl_exec($ch);
            $info = curl_getinfo($ch);
            $error = curl_error($ch);
            // remove "continue" header
            $raw = preg_replace('`^HTTP/\d+\.\d+\s+100\s+.*\s+`m', '', $raw);
            $this->throwOnError($error, $raw, $url);
            $url = $info['redirect_url'];
            $logMessage = 'Redirect URL: ';
        } while ($url !== '' and $redirectCount-- > 0);
        curl_close($ch);

        if ($url !== '' and $redirectCount <= 0) {
            throw new Exception\TooManyRedirectionsException(
                'Too many redirections (max ' . $this->options->getFollowRedirectCount() . ') for URL: ' . $originalUrl
            );
        }
        return $this->processHeaderBody($raw, $info);
    }

    /**
     * @param $ch
     * @throws \f2r\SimpleHttp\Exception\SsrfException
     */
    private function throwOnUnsafeRequest($ch)
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
    private function throwOnForbiddenProtocol($url)
    {
        if (preg_match('`^https?://`i', $url) === 0) {
            throw new Exception\ForbiddenProtocolException('unsupported protocol scheme: ' . $url);
        }
    }

    /**
     * @param string $url
     * @throws \f2r\SimpleHttp\Exception\ForbiddenHostException
     */
    private function throwOnForbiddenHost($url)
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
    private function throwOnInvalidCharacter($url)
    {
        if (preg_match('`[\x00-\x20\x22\x3c\x3e\x5c\x5e\x60\x7b-\x7d\x7f-\xff]`', $url, $match) === 1) {
            throw new Exception\InvalidCharacterException(sprintf('Invalid character in URL: \\x%02X', ord($match[0])));
        }
    }

    private function buildData($data)
    {
        if (is_array($data) and $this->options->isPostAsUrlEncoded() === false) {
            return $data;
        }
        if (is_array($data)) {
            return http_build_query($data);
        }

        return (string)$data;
    }

    /**
     * @param string $raw
     * @param array  $info
     * @return \f2r\SimpleHttp\Response
     */
    private function processHeaderBody($raw, array $info)
    {
        $split = explode("\r\n\r\n", $raw, 2);
        $header = $split[0];
        $body = '';
        if (isset($split[1])) {
            $body = $split[1];
        }
        $headerLines = explode("\r\n", trim($header));
        unset($headerLines[0]);
        $header = [];
        foreach ($headerLines as $line) {
            list($key, $value) = explode(':', $line, 2);
            $header[strtolower(trim($key))] = trim($value);
        }
        return new Response(
            new HeaderResponse($header),
            $body,
            new CurlInfo($info)
        );
    }
}
