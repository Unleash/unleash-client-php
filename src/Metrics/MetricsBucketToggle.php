<?php

namespace Unleash\Client\Metrics;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;

interface MetricsBucketToggle
{
    public function getFeature(): Feature;

    public function isSuccess(): bool;

    public function getVariant(): ?Variant;
}
