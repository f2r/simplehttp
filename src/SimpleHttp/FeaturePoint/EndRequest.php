<?php
namespace f2r\SimpleHttp\FeaturePoint;

use f2r\SimpleHttp\FeaturePoint;
use f2r\SimpleHttp\Redirections;
use f2r\SimpleHttp\Response;

interface EndRequest extends FeaturePoint
{
    public function onResponse(array $info, array $header, string $body, Redirections $redirections): ?Response;
}