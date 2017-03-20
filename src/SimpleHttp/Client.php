<?php
namespace f2r\SimpleHttp;

use f2r\SimpleHttp\Data\Payload;
use f2r\SimpleHttp\Data\Post;
use Psr\Log\LoggerInterface;

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
        $this->options = $options;
        if ($this->options === $options) {
            $this->options = new Options();
        }
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
     */
    public function setHeader(HeaderRequest $header = null)
    {
        $this->header = $header;
        if ($this->header === null) {
            $this->header = new HeaderRequest();
        }
    }

    /**
     * @param string                             $method
     * @param string                            $url
     * @param \f2r\SimpleHttp\DataInterface|null $data
     * @return \f2r\SimpleHttp\Response
     *
     * @throws Exception
     */
    public function request($method, $url, DataInterface $data = null)
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

        if ($method !== self::METHOD_GET) {
            $curlOpt[CURLOPT_CUSTOMREQUEST] = $method;
        }

        if ($method === static::METHOD_GET || $method === self::METHOD_HEAD || $method === self::METHOD_DELETE) {
            $data = null;
        }

        if ($data instanceof DataInterface) {
            $payload = $data->getPayload();
            $curlOpt[CURLOPT_POSTFIELDS] = $payload;
            $curlOpt[CURLOPT_HTTPHEADER]['content-length'] = $this->getStrLen($payload);
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
        return $this->request(self::METHOD_POST, $url, new Post($data));
    }

    /**
     * @param string $url
     * @param string $data
     * @return \f2r\SimpleHttp\Response
     * @throws Exception
     */
    public function put($url, $data)
    {
        return $this->request(self::METHOD_PUT, $url, new Payload($data));
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
     * @param string $payload
     * @return int
     */
    private function getStrLen($payload)
    {
        if (function_exists('mb_strlen')) {
            // strlen could return wrong data length with "mbstring.func_overload"
            return mb_strlen($payload, '8bit');
        }
        return strlen($payload);
    }

    /**
     * @param string $error
     * @param string $raw
     * @throws \f2r\SimpleHttp\Exception
     */
    private function throwOnError($error, $raw, $url)
    {
        if ($error !== '') {
            $this->getOptions()->getLogger()->debug('Error requesting "' . $url . '": ' . $error);
            throw new Exception($error, 0);
        }

        if (preg_match('`^HTTP/\d+\.\d+\s+(\d+)\s+(.+)`', $raw, $match) === 0) {
            $this->getOptions()->getLogger()->debug('Could not understand HTTP response for URL: ' . $url);
            throw new Exception('Could not understand HTTP response', 0);
        }

        $httpCode = (int)$match[1];
        $httpMessage = trim($match[2]);

        if ($httpCode < 400) {
            return;
        }

        $this->getOptions()->getLogger()->debug("Request error for URL \"$url\": [$httpCode] $httpMessage");
        throw new Exception($httpMessage, $httpCode);
    }

    /**
     * @param string $url
     * @param array  $curlOpt
     * @return Response
     * @throws Exception
     */
    private function executeCurl($url, array $curlOpt)
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getOptions()->getLogger();
        $response = null;
        if (static::$debugHook !== null) {
            $response = call_user_func(static::$debugHook, $url, $curlOpt);
        }
        if ($response instanceof Response) {
            $logger->debug('Debug hook return a response for URL: ' . $url);
            return $response;
        }
        $redirectCount = $this->options->getFollowRedirectCount();
        $ch = curl_init();
        curl_setopt_array($ch, $curlOpt);
        $logMessage = 'Request URL: ';
        do {
            $this->throwOnForbiddenHost($url);
            curl_setopt($ch, CURLOPT_URL, $url);
            $logger->debug($logMessage . $url);
            $raw = curl_exec($ch);
            $info = curl_getinfo($ch);
            $error = curl_error($ch);
            $this->throwOnError($error, $raw, $url);
            $url = $info['redirect_url'];
            $logMessage = 'Redirect URL: ';
        } while ($url !== '' and $redirectCount-- > 0);
        curl_close($ch);

        return $this->processHeaderBody($raw, $info);
    }

    /**
     * @param string $url
     * @throws \f2r\SimpleHttp\Exception
     */
    private function throwOnForbiddenHost($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($this->options->isHostValid($host) === false) {
            throw new Exception('Invalid host for requesting: ' . $host, 0);
        }
    }

    /**
     * @param string $raw
     * @param array  $info
     * @return \f2r\SimpleHttp\Response
     */
    private function processHeaderBody($raw, array $info)
    {
        list ($header, $body) = explode("\r\n\r\n", $raw, 2);
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
