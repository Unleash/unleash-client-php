<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Psr\SimpleCache\CacheInterface;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Helper\Builder\CacheAware;
use Unleash\Client\Metrics\MetricsHandler;

final class CacheAwareMetricsHandler implements MetricsHandler, CacheAware
{
    /**
     * @var null|CacheInterface
     */
    public $cache = null;

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function handleMetrics(Feature $feature, bool $successful, ?Variant $variant = null): void
    {
    }
}
