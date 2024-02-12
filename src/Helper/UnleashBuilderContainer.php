<?php

namespace Unleash\Client\Helper;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Metrics\MetricsSender;
use Unleash\Client\Stickiness\StickinessCalculator;

/**
 * @internal
 */
final class UnleashBuilderContainer
{
    /**
     * @readonly
     * @var \Psr\SimpleCache\CacheInterface
     */
    private $cache;
    /**
     * @readonly
     * @var \Psr\SimpleCache\CacheInterface
     */
    private $staleCache;
    /**
     * @readonly
     * @var \Psr\Http\Client\ClientInterface
     */
    private $httpClient;
    /**
     * @readonly
     * @var \Unleash\Client\Metrics\MetricsSender|null
     */
    private $metricsSender;
    /**
     * @readonly
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    private $requestFactory;
    /**
     * @readonly
     * @var \Unleash\Client\Stickiness\StickinessCalculator
     */
    private $stickinessCalculator;
    /**
     * @readonly
     * @var \Unleash\Client\Configuration\UnleashConfiguration|null
     */
    private $configuration;
    public function __construct(CacheInterface $cache, CacheInterface $staleCache, ClientInterface $httpClient, ?MetricsSender $metricsSender, RequestFactoryInterface $requestFactory, StickinessCalculator $stickinessCalculator, ?UnleashConfiguration $configuration)
    {
        $this->cache = $cache;
        $this->staleCache = $staleCache;
        $this->httpClient = $httpClient;
        $this->metricsSender = $metricsSender;
        $this->requestFactory = $requestFactory;
        $this->stickinessCalculator = $stickinessCalculator;
        $this->configuration = $configuration;
    }
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getStaleCache(): CacheInterface
    {
        return $this->staleCache;
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    public function getMetricsSender(): ?MetricsSender
    {
        return $this->metricsSender;
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function getStickinessCalculator(): StickinessCalculator
    {
        return $this->stickinessCalculator;
    }

    public function getConfiguration(): ?UnleashConfiguration
    {
        return $this->configuration;
    }
}
