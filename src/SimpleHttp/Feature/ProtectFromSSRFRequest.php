<?php
namespace f2r\SimpleHttp\Feature;

use f2r\SimpleHttp\Exception\SsrfException;
use f2r\SimpleHttp\FeaturePoint\FollowLocation;
use f2r\SimpleHttp\FeaturePoint\StartRequest;
use f2r\SimpleHttp\Response;

class ProtectFromSSRFRequest implements FollowLocation, StartRequest
{

    private function checkUrl($curlHandle, string $url)
    {
        $data = parse_url($url);

        \curl_setopt($curlHandle, CURLOPT_CONNECT_ONLY, true);
        \curl_exec($curlHandle);
        $ip = \curl_getinfo($curlHandle, CURLINFO_PRIMARY_IP);
        $ip = preg_replace('`(?<=^|:)0+(?=:|$)`', '', $ip);

        $ipv6Message = '';
        try {
            if (stripos($ip, 'fd') === 0) {
                throw new SsrfException("Private IPV6 network requested: {$data['host']}[{$ip}]");
            }
            if (stripos($ip, 'fc') === 0) {
                throw new SsrfException("Local IPV6 network requested: {$data['host']}[{$ip}]");
            }
            if (stripos($ip, 'fe80') === 0) {
                throw new SsrfException("Local link IPV6 network requested: {$data['host']}[{$ip}]");
            }
            if ($ip === '::1') {
                throw new SsrfException("Loop back IPV6 network requested: {$data['host']}[{$ip}]");
            }

            if (preg_match('`^::ffff:([^:]+):([^:]+)`', $ip, $match) === 1) {
                $parts = str_split(str_pad($match[1], 4, '0', STR_PAD_LEFT) . str_pad($match[2], 4, '0', STR_PAD_LEFT),
                    2);
                foreach ($parts as $i => $h) {
                    $parts[$i] = base_convert($h, 16, 10);
                }
                $ip = implode('.', $parts);
                $ipv6Message = ' mapped into IPV6';
            } elseif (preg_match('`^::ffff:(\d+\.\d+\.\d+\.\d+)`', $ip, $match) === 1) {
                $ip = $match[1];
                $ipv6Message = ' mapped into IPV6';
            }

            if (preg_match('`^(10|192\.168|172\.1[6-9]|172\.2\d|172\.3[0-1])\.`', $ip)) {
                throw new SsrfException("Private IPV4 network requested{$ipv6Message}: {$data['host']}[{$ip}]");
            }
            if (strpos($ip, '127.') === 0) {
                throw new SsrfException("Local IPV4 network requested{$ipv6Message}: {$data['host']}[{$ip}]");
            }
        } finally {
            \curl_setopt($curlHandle, CURLOPT_CONNECT_ONLY, false);
        }
    }

    public function onFollowLocation($curlHandle, string $location): void
    {
        $this->checkUrl($curlHandle, $location);
    }

    public function onRequest(string $method, string $url, array $data = null): ?Response
    {
        $curlHandle = \curl_init($url);
        try {
            $this->checkUrl($curlHandle, $url);
        } finally {
            \curl_close($curlHandle);
        }
        return null;
    }
}