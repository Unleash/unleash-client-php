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
    ->withMetricsEnabled(true)
    ->withCacheTimeToLive(3)
    ->withMetricsInterval(300)
    ->withHeader('Authorization', $apiKey)
    ->build();

if ($unleash->isEnabled('myFeature')) {
    echo "myFeature is enabled \n";
} else {
    echo "myFeature is disabled \n";
}

$resolvedVariant = $unleash->getVariant('myFeature');
echo "Resolved a variant";
var_dump($resolvedVariant);

sleep(1);

if ($unleash->isEnabled('myFeature')) {
    echo "myFeature is enabled \n";
} else {
    echo "myFeature is disabled \n";
}