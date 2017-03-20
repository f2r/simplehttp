<?php
namespace f2r\SimpleHttp;

class CurlInfo
{

    /**
     * @var array
     */
    private $info;

    public function __construct(array $info)
    {
        $this->info = $info;
    }

    public function getCertinfo() {
        return $this->info['certinfo'];
    }

    public function getConnectTime() {
        return $this->info['connect_time'];
    }

    public function getContentType() {
        return $this->info['content_type'];
    }

    public function getDownloadContentLength() {
        return $this->info['download_content_length'];
    }

    public function getFiletime() {
        return $this->info['filetime'];
    }

    public function getHeaderSize() {
        return $this->info['header_size'];
    }

    public function getHttpCode() {
        return $this->info['http_code'];
    }

    public function getLocalIp() {
        return $this->info['local_ip'];
    }

    public function getLocalPort() {
        return $this->info['local_port'];
    }

    public function getNamelookupTime() {
        return $this->info['namelookup_time'];
    }

    public function getPretransferTime() {
        return $this->info['pretransfer_time'];
    }

    public function getPrimaryIp() {
        return $this->info['primary_ip'];
    }

    public function getPrimaryPort() {
        return $this->info['primary_port'];
    }

    public function getRedirectCount() {
        return $this->info['redirect_count'];
    }

    public function getRedirectTime() {
        return $this->info['redirect_time'];
    }

    public function getRedirectUrl() {
        return $this->info['redirect_url'];
    }

    public function getRequestSize() {
        return $this->info['request_size'];
    }

    public function getSizeDownload() {
        return $this->info['size_download'];
    }

    public function getSizeUpload() {
        return $this->info['size_upload'];
    }

    public function getSpeedDownload() {
        return $this->info['speed_download'];
    }

    public function getSpeedUpload() {
        return $this->info['speed_upload'];
    }

    public function getSslVerifyResult() {
        return $this->info['ssl_verify_result'];
    }

    public function getStarttransferTime() {
        return $this->info['starttransfer_time'];
    }

    public function getTotalTime() {
        return $this->info['total_time'];
    }

    public function getUploadContentLength() {
        return $this->info['upload_content_length'];
    }

    public function getUrl() {
        return $this->info['url'];
    }
}
