<?php
namespace f2r\SimpleHttp\FeaturePoint;

use f2r\SimpleHttp\FeaturePoint;
use f2r\SimpleHttp\Redirections;

interface ProcessingBody extends FeaturePoint
{
    public function onProcessingBody($curlHandle, array $curlInfo, array $header, string $body, Redirections $redirections): string;
}