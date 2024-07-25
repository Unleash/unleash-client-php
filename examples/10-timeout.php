<?php

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Unleash\Client\UnleashBuilder;

require __DIR__ . '/_common.php';

if (class_exists(Client::class)) {
    $httpClient = new Client([
        RequestOptions::TIMEOUT => 2,
    ]);
} else if (class_exists(Psr18Client::class)) {
    $httpClient = HttpClient::create([
        'timeout' => 2,
    ]);
    $httpClient = new Psr18Client($httpClient);
} else {
    throw new LogicException('No supported http client (for this example) found');
}

$unleash = UnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->withHttpClient($httpClient)
    ->build()
;
