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

    public function __construct(HeaderRequest $header = null, Options $options = null)
    {
        if ($header === null) {
            $header = new HeaderRequest();
        }
        $this->header = $header;

        if ($options === null) {
            $options = new Options();
        }
        $this->options = $options;
    }

    /**
     * @return \f2r\SimpleHttp\Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param \f2r\SimpleHttp\Options $options
     * @return $this
     */
    public function setOptions(Options $options)
    {
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
     * @param \f2r\SimpleHttp\HeaderRequest $header
     * @return $this
     */
    public function setHeader(HeaderRequest $header)
    {
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
        $curlOpt = $this->getOptions()->getCurlOptions() + [
            CURLOPT_HTTPHEADER => $this->header->getHeaders(),
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

        if ($this->options->isAsynchronousRequesting()) {
            $curlExec = new CurlAsyncExec($this->options);
        } else {
            $curlExec = new CurlExec($this->options);
        }

        return $curlExec->request($url, $curlOpt);
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

}
