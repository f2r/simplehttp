<?php
namespace f2r\SimpleHttp\FeaturePoint;

use f2r\SimpleHttp\Exception;
use f2r\SimpleHttp\FeaturePoint;
use f2r\SimpleHttp\Redirections;

interface Error extends FeaturePoint
{
    public function onError($curlHandle, array $curlInfo, Exception $exception, Redirections $redirections);
}