<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Unleash\Client\Bootstrap\BootstrapHandler;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Helper\Builder\MetricsSenderAware;
use Unleash\Client\Metrics\MetricsSender;

final class MetricsSenderAwareBootstrapHandler implements BootstrapHandler, MetricsSenderAware
{
    public function getBootstrapContents(BootstrapProvider $provider): ?string
    {
        return null;
    }

    public function setMetricsSender(MetricsSender $metricsSender): void
    {
        // this is an invalid dependency, this method won't be triggered
    }
}
