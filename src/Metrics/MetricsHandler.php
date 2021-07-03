<?php

namespace Rikudou\Unleash\Metrics;

use Rikudou\Unleash\DTO\Feature;

interface MetricsHandler
{
    public function handleMetrics(Feature $feature, bool $successful): void;
}
