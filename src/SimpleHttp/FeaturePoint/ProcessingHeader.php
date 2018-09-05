<?php
namespace f2r\SimpleHttp\FeaturePoint;

use f2r\SimpleHttp\FeaturePoint;
use f2r\SimpleHttp\Redirections;

interface ProcessingHeader extends FeaturePoint
{
    public function onProcessingHeader($curlHandle, array $curlInfo, array $header, Redirections $redirections): array;
}