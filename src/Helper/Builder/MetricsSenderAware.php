<?php

namespace Unleash\Client\Helper\Builder;

use Unleash\Client\Metrics\MetricsSender;

interface MetricsSenderAware
{
    public function setMetricsSender(MetricsSender $metricsSender): void;
}
