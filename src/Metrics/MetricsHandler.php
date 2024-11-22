<?php

namespace Unleash\Client\Metrics;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;

interface MetricsHandler
{
    public function handleMetrics(Feature $feature, bool $successful, ?Variant $variant = null): void;
}
