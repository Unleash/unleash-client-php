<?php

use Unleash\Client\ProxyUnleashBuilder;

require __DIR__ . '/_common.php';

$unleash = ProxyUnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->withHeader('Authorization', $apiKey)
    ->build();

if ($unleash->isEnabled('myFeature')) {
    echo "myFeature is enabled";
} else {
    echo "myFeature is disabled";
}