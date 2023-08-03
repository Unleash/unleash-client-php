<?php

use Unleash\Client\UnleashBuilder;

require __DIR__ . '/_common.php';

$unleash = UnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->withProxy($apiKey)
    ->build();

if ($unleash->isEnabled('myFeature')) {
    echo "myFeature is enabled";
} else {
    echo "myFeature is disabled";
}