<?php

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Unleash\Client\Client\DefaultRegistrationService;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\Metrics\DefaultMetricsHandler;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Repository\DefaultUnleashRepository;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\ApplicationHostnameStrategyHandler;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutRandomStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutSessionIdStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutUserIdStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Variant\DefaultVariantHandler;

require_once __DIR__ . '/_common.php';

$stickinessCalculator = new MurmurHashCalculator();
$gradualRolloutStrategyHandler = new GradualRolloutStrategyHandler($stickinessCalculator);
$strategies = [
    $gradualRolloutStrategyHandler,
    new ApplicationHostnameStrategyHandler(),
    new DefaultStrategyHandler(),
    new IpAddressStrategyHandler(),
    new UserIdStrategyHandler(),
    // you can skip these three if you don't use the deprecated strategies
    new GradualRolloutRandomStrategyHandler($gradualRolloutStrategyHandler),
    new GradualRolloutSessionIdStrategyHandler($gradualRolloutStrategyHandler),
    new GradualRolloutUserIdStrategyHandler($gradualRolloutStrategyHandler),
];
$httpClient = Psr18ClientDiscovery::find();
$httpFactory = Psr17FactoryDiscovery::findRequestFactory();
$configuration = (new UnleashConfiguration(
    $appUrl,
    $appName,
    $instanceId,
))->setHeaders([
    'Authorization' => $apiKey,
]);
// add any other configuration to the configuration object, like cache configuration etc.
$repository = new DefaultUnleashRepository($httpClient, $httpFactory, $configuration);
$registrationService = new DefaultRegistrationService($httpClient, $httpFactory, $configuration);
$metricsSender = new DefaultMetricsSender($httpClient, $httpFactory, $configuration);
$metricsHandler = new DefaultMetricsHandler($metricsSender, $configuration);
$variantHandler = new DefaultVariantHandler($stickinessCalculator);

$unleash = new DefaultUnleash(
    $strategies,
    $repository,
    $registrationService,
    $configuration,
    $metricsHandler,
    $variantHandler,
);
