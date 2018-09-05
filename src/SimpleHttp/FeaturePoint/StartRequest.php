<?php
namespace f2r\SimpleHttp\FeaturePoint;

use f2r\SimpleHttp\FeaturePoint;
use f2r\SimpleHttp\Response;

interface StartRequest extends FeaturePoint
{
    public function onRequest(string $method, string $url, array $data = null): ?Response;
}