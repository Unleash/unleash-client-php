<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\ProxyVariant;
use Unleash\Client\Helper\Builder\ConfigurationAware;
use Unleash\Client\Metrics\MetricsHandler;

final class ConfigurationAwareMetricsHandler implements MetricsHandler, ConfigurationAware
{
    /**
     * @var UnleashConfiguration|null
     */
    public $configuration = null;

    public function setConfiguration(UnleashConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function handleMetrics(Feature $feature, bool $successful, ProxyVariant $variant = null): void
    {
    }
}
