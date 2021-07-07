<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Rikudou\Unleash\UnleashBuilder;

require __DIR__ . '/_common.php';

$cache = new FilesystemCachePool(
    new Filesystem(
        new Local(sys_get_temp_dir() . '/unleash-examples-cache')
    )
);

$unleash = UnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withCacheHandler($cache)
    ->withCacheTimeToLive(3) // 3 seconds
    ->withMetricsInterval(3_000) // 3,000 milliseconds or 3 seconds
    ->withInstanceId($instanceId)
    ->withHeader('Authorization', $apiKey)
    ->build();

var_dump($unleash->isEnabled('test')); // no metrics will be sent yet
var_dump($unleash->isEnabled('test')); // no http call will be made
sleep(4);
var_dump($unleash->isEnabled('test')); // a http call will be made again and metrics will be sent
