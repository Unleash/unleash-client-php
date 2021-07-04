<?php

namespace Rikudou\Unleash\Metrics;

use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\DTO\Variant;

interface MetricsHandler
{
    public function handleMetrics(Feature $feature, bool $successful, Variant $variant = null): void;
}
