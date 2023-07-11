<?php

require __DIR__ . '/_common.php';

use Unleash\Client\ProxyUnleashBuilder;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;


$psr6Cache = new FilesystemAdapter();
$cache = new Psr16Cache($psr6Cache);

$unleash = ProxyUnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->withCacheHandler($cache)
    ->withMetricsEnabled(false)
    ->withCacheTimeToLive(3)
    ->withMetricsInterval(3_000)
    ->withHeader('Authorization', $apiKey)
    ->build();

if ($unleash->isEnabled('myFeature')) {
    echo "myFeature is enabled";
} else {
    echo "myFeature is disabled";
}

if ($unleash->isEnabled('myFeature')) {
    echo "myFeature is enabled";
} else {
    echo "myFeature is disabled";
}