<?php
namespace f2r\SimpleHttp;

class CurlInfo
{

    /**
     * @var array
     */
    private $info;

    public function __construct(array $info = [])
    {
        $this->info = $info;
    }

    public function getHttp()
    {
        return $this->info['http'] ?? null;
    }

    public function getCertinfo() {
        return $this->info['certinfo'] ?? null;
    }

    public function getConnectTime() {
        return $this->info['connect_time'] ?? null;
    }

    public function getContentType() {
        return $this->info['content_type'] ?? null;
    }

    public function getDownloadContentLength() {
        return $this->info['download_content_length'] ?? null;
    }

    public function getFiletime() {
        return $this->info['filetime'] ?? null;
    }

    public function getHeaderSize() {
        return $this->info['header_size'] ?? null;
    }

    public function getHttpCode() {
        return $this->info['http_code'] ?? null;
    }

    public function getLocalIp() {
        return $this->info['local_ip'] ?? null;
    }

    public function getLocalPort() {
        return $this->info['local_port'] ?? null;
    }

    public function getNamelookupTime() {
        return $this->info['namelookup_time'] ?? null;
    }

    public function getPretransferTime() {
        return $this->info['pretransfer_time'] ?? null;
    }

    public function getPrimaryIp() {
        return $this->info['primary_ip'] ?? null;
    }

    public function getPrimaryPort() {
        return $this->info['primary_port'] ?? null;
    }

    public function getRedirectCount() {
        return $this->info['redirect_count'] ?? null;
    }

    public function getRedirectTime() {
        return $this->info['redirect_time'] ?? null;
    }

    public function getRedirectUrl() {
        return $this->info['redirect_url'] ?? null;
    }

    public function getRequestSize() {
        return $this->info['request_size'] ?? null;
    }

    public function getSizeDownload() {
        return $this->info['size_download'] ?? null;
    }

    public function getSizeUpload() {
        return $this->info['size_upload'] ?? null;
    }

    public function getSpeedDownload() {
        return $this->info['speed_download'] ?? null;
    }

    public function getSpeedUpload() {
        return $this->info['speed_upload'] ?? null;
    }

    public function getSslVerifyResult() {
        return $this->info['ssl_verify_result'] ?? null;
    }

    public function getStarttransferTime() {
        return $this->info['starttransfer_time'] ?? null;
    }

    public function getTotalTime() {
        return $this->info['total_time'] ?? null;
    }

    public function getUploadContentLength() {
        return $this->info['upload_content_length'] ?? null;
    }

    public function getUrl() {
        return $this->info['url'] ?? null;
    }
}
