<?php

namespace Unleash\Client\Metrics;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\ProxyVariant;

interface MetricsHandler
{
    public function handleMetrics(Feature $feature, bool $successful, ProxyVariant $variant = null): void;
}
