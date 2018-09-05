<?php
namespace f2r\SimpleHttp;


use f2r\SimpleHttp\Exception\ForbiddenProtocolException;
use f2r\SimpleHttp\Exception\HttpMethodNotSupportedException;
use f2r\SimpleHttp\Exception\InvalidCharacterException;
use f2r\SimpleHttp\FeaturePoint\EndRequest;
use f2r\SimpleHttp\FeaturePoint\Error;
use f2r\SimpleHttp\FeaturePoint\FollowLocation;
use f2r\SimpleHttp\FeaturePoint\ProcessingBody;
use f2r\SimpleHttp\FeaturePoint\ProcessingHeader;
use f2r\SimpleHttp\FeaturePoint\SettingCurlOptions;
use f2r\SimpleHttp\FeaturePoint\StartRequest;

class Client
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_HEAD = 'HEAD';

    /**
     * @var callable[][]
     */
    private $featurePoints;

    /**
     * @var HeaderRequest
     */
    private $header;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var array
     */
    private $lastResponseHeader;

    /**
     * @var Redirections
     */
    private $redirected;

    /**
     * @var string Keep base URL for relative URL redirection
     */
    private $redirectBaseUrl;

    /**
     * @param HeaderRequest|null $header
     * @param Options|null $options
     */
    public function __construct(HeaderRequest $header = null, Options $options = null)
    {
        $this->featurePoints = [
            StartRequest::class => [],
            FollowLocation::class => [],
            SettingCurlOptions::class => [],
            ProcessingHeader::class => [],
            ProcessingBody::class => [],
            Error::class => [],
            EndRequest::class => [],
        ];
        $this->setHeader($header);
        $this->setOptions($options);
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
     * @param Options $options
     *
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
     * Use a feature for this client.
     *
     * @param FeaturePoint $feature
     *
     * @return $this
     */
    public function with(FeaturePoint $feature)
    {
        foreach (class_implements($feature) as $name) {
            if (is_subclass_of($name, FeaturePoint::class)) {
                $this->featurePoints[$name][] = $feature;
            }
        }
        return $this;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $data
     *
     * @return Response
     * @throws Exception
     */
    public function request(string $method, string $url, array $data = null): Response
    {
        $this->redirectBaseUrl = $this->getBaseUrl($url);
        $this->redirected = new Redirections($url);
        $this->checkUrl($url);
        /** @var StartRequest $feature */
        foreach ($this->featurePoints[StartRequest::class] as $feature) {
            $response = $feature->onRequest($method, $url, $data);
            if ($response !== null) {
                return $response;
            }
        }

        $this->lastResponseHeader = [];
        $ch = \curl_init();

        $curlOptions = $this->getCurlOptions($method, $url, $data);

        $curlOptions[CURLOPT_HEADERFUNCTION] = function($ch, $str) {
            $this->handleResponseHeader($ch, $str);
            return strlen($str);
        };

        /** @var SettingCurlOptions $feature */
        foreach ($this->featurePoints[SettingCurlOptions::class] as $feature) {
            $curlOptions = $feature->onSettingCurlOptions($ch, $curlOptions);
        }
        \curl_setopt_array($ch, $curlOptions);

        $curlInfo = [];
        $body = '';
        try {
            $body = @\curl_exec($ch);
            $curlInfo = \curl_getinfo($ch);
            $error = \curl_error($ch);
            $code = $curlInfo['http_code'];
            if ($code >= 400 or $code === 0) {
                if ($error === '') {
                    if (isset($this->lastResponseHeader['http']['message'])) {
                        $error = $this->lastResponseHeader['http']['code'] . ': ';
                        $error .= $this->lastResponseHeader['http']['message'];
                    } else {
                        $error = 'Request return an error: ' . $code;
                    }
                }
                throw new Exception($error, $code);
            }
            /** @var ProcessingHeader $feature */
            foreach ($this->featurePoints[ProcessingHeader::class] as $feature) {
                $this->lastResponseHeader = $feature->onProcessingHeader($ch, $curlInfo, $this->lastResponseHeader, $this->redirected);
            }
            /** @var ProcessingBody $feature */
            foreach ($this->featurePoints[ProcessingBody::class] as $feature) {
                $body = $feature->onProcessingBody($ch, $curlInfo, $this->lastResponseHeader, $body, $this->redirected);
            }
        } catch (Exception $exception) {
            // No feature for error, just throws the exception
            if ($this->featurePoints[Error::class] === []) {
                throw $exception;
            }
            /** @var Error $feature */
            foreach ($this->featurePoints[Error::class] as $feature) {
                $feature->onError($ch, $curlInfo, $exception, $this->redirected);
            }
        } finally {
            \curl_close($ch);
        }

        /** @var EndRequest $feature */
        foreach ($this->featurePoints[EndRequest::class] as $feature) {
            $response = $feature->onResponse($curlInfo, $this->lastResponseHeader, $body, $this->redirected);
            if ($response !== null) {
                return $response;
            }
        }

        $response = new Response($url, new HeaderResponse($this->lastResponseHeader), $body);
        $response->setEffectiveUrl($curlInfo['url']);
        if (count($this->redirected) > 0) {
            $response->setRedirected($this->redirected);
        }
        return $response;
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////// Private methods /////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * When CURL receive a header line, store it and if it's a redirection, use FollowLocation feature point
     *
     * @param resource $curlHandle
     * @param string $string
     *
     * @throws ForbiddenProtocolException
     * @throws InvalidCharacterException
     */
    private function handleResponseHeader($curlHandle, string $string)
    {
        if (trim($string) === '') {
            return;
        }
        $value = null;
        if (preg_match('`^HTTP/([0-9.]+)\s+([0-9]+)\s+(.*)`', $string, $match) === 1) {
            $this->lastResponseHeader = [];
            $name = 'http';
            $value = ['version' => $match[1], 'code' => (int) $match[2], 'message' => trim($match[3])];
        } else {
            preg_match('`^([^:]+)\s*:\s*(.*)$`', $string, $match);
            $name = strtolower($match[1]);
            $value = trim($match[2]);
        }

        if (isset($this->lastResponseHeader[$name])) {
            if (is_array($this->lastResponseHeader[$name])) {
                $this->lastResponseHeader[$name][] = $value;
            } else {
                $this->lastResponseHeader[$name] = [$this->lastResponseHeader[$name], $value];
            }
        } else {
            $this->lastResponseHeader[$name] = $value;
        }

        if ($name === 'location') {
            $data = parse_url($value);
            if (!isset($data['host'])) {
                $value = $this->redirectBaseUrl . $value;
            }
            $this->redirectBaseUrl = $this->getBaseUrl($value);
            $this->checkUrl($value);
            $this->redirected->addRedirect(new RedirectUrl($this->lastResponseHeader['http']['code'], $value));
            /** @var FollowLocation $feature */
            foreach ($this->featurePoints[FollowLocation::class] as $feature) {
                $feature->onFollowLocation($this, $curlHandle, $value);
            }
        }
    }

    /**
     * Check URL for invalid protocol or characters
     *
     * @param string $url
     *
     * @throws ForbiddenProtocolException
     * @throws InvalidCharacterException
     */
    private function checkUrl(string $url): void
    {
        $data = parse_url($url);
        if (isset($data['scheme']) === false) {
            throw new ForbiddenProtocolException('No protocol specified: ' . $url);
        }

        if (in_array(strtolower($data['scheme']), ['http', 'https']) === false) {
            throw new ForbiddenProtocolException('Unsupported protocol scheme: ' . $url);
        }

        if (preg_match('`[\x00-\x20\x22\x3c\x3e\x5c\x5e\x60\x7b-\x7d\x7f-\xff]`', $url, $match) === 1) {
            throw new InvalidCharacterException(sprintf('Invalid character in URL: \\x%02X', ord($match[0])));
        }
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function getBaseUrl(string $url): string
    {
        $parts = parse_url($url);
        $baseUrl = "{$parts['scheme']}://";
        if (isset($parts['username'])) {
            $baseUrl .= $parts['username'];
        }
        if (isset($parts['username'], $parts['password'])) {
            $baseUrl .= ':' . $parts['password'];
        }
        if (isset($parts['username'])) {
            $baseUrl .= '@';
        }
        $baseUrl .= $parts['host'];
        if (isset($parts['port'])) {
            $baseUrl .= ':' . $parts['port'];
        }

        return $baseUrl;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    private function buildData($data)
    {
        if (is_array($data) and $this->options->isPostAsUrlEncoded() === false) {
            return $data;
        }
        if (is_array($data)) {
            return http_build_query($data);
        }

        return (string) $data;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $data
     *
     * @return array
     * @throws HttpMethodNotSupportedException
     */
    private function getCurlOptions(string $method, string $url, array $data = null): array
    {
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => false,
            CURLOPT_TIMEOUT => $this->options->getTimeout(),
            CURLOPT_CONNECTTIMEOUT => $this->options->getConnectionTimeout(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => $this->options->getFollowRedirectCount(),
            CURLOPT_HTTPHEADER => $this->header->getHeaders(),
            CURLOPT_HEADER => false,
            CURLOPT_NOSIGNAL => true,
        ];
        if ($this->options->getFollowRedirectCount())
        switch ($method) {
            case self::METHOD_DELETE:
                $curlOptions[CURLOPT_CUSTOMREQUEST] = self::METHOD_DELETE;
                break;
            case self::METHOD_HEAD:
                $curlOptions[CURLOPT_CUSTOMREQUEST] = self::METHOD_HEAD;
                break;
            case self::METHOD_GET:
                break;
            case self::METHOD_PUT:
                $curlOptions[CURLOPT_CUSTOMREQUEST] = self::METHOD_PUT;
                $curlOptions[CURLOPT_SAFE_UPLOAD] = true;
                if (is_array($data)) {
                    $data = http_build_query($data);
                }
                $curlOptions[CURLOPT_POSTFIELDS] = (string)$data;
                break;
            case self::METHOD_POST:
                $curlOptions[CURLOPT_SAFE_UPLOAD] = true;
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = $this->buildData($data);
                break;
            default:
                throw new HttpMethodNotSupportedException($method . ' HTTP method is not currently supported');
        }

        return $curlOptions;
    }
}