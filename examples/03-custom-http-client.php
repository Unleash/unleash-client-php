<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use Unleash\Client\UnleashBuilder;
use Symfony\Component\HttpClient\Psr18Client;

require __DIR__ . '/_common.php';

// let's create some middleware
$headerMiddleware = function (string $headerName, string $headerValue) {
    return function (callable $next) use ($headerName, $headerValue) {
        return function (RequestInterface $request, array $options) use ($next, $headerName, $headerValue) {
            $request = $request->withHeader($headerName, $headerValue);
            return $next($request, $options);
        };
    };
};

$handlerStack = new HandlerStack(new CurlHandler());
$handlerStack->push($headerMiddleware('X-My-Custom-Header', 'Hello there'));
$httpClient = new Client(['handler' => $handlerStack]);

// let's say you want to use Guzzle http client but Symfony/Nyholm request factory. Sure, you can!
$requestFactory = new Psr18Client();

$unleash = UnleashBuilder::create()
    ->withAppName($appName)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->withHttpClient($httpClient) // now every request will contain header X-My-Custom-Header with value Hello there
    ->withRequestFactory($requestFactory) // requests will now be created by Symfony factory
    ->withHeader('Authorization', $apiKey)
    ->build();

var_dump($unleash->isEnabled('test'));
