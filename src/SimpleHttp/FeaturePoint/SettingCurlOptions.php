<?php
namespace f2r\SimpleHttp\FeaturePoint;

use f2r\SimpleHttp\FeaturePoint;

interface SettingCurlOptions extends FeaturePoint
{
    public function onSettingCurlOptions($curlHandle, array $options): array;
}