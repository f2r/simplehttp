<?php
namespace f2r\SimpleHttp\FeaturePoint;

use f2r\SimpleHttp\FeaturePoint;

interface FollowLocation extends FeaturePoint
{
    public function onFollowLocation($curlHandle, string $location): void;
}