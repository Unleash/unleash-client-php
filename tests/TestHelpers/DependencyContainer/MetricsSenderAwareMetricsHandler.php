<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Helper\Builder\MetricsSenderAware;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Metrics\MetricsSender;

final class MetricsSenderAwareMetricsHandler implements MetricsHandler, MetricsSenderAware
{
    /**
     * @var MetricsSender|null
     */
    public $metricsSender = null;

    public function handleMetrics(Feature $feature, bool $successful, ?Variant $variant = null): void
    {
    }

    public function setMetricsSender(MetricsSender $metricsSender): void
    {
        $this->metricsSender = $metricsSender;
    }
}
