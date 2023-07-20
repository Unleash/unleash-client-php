<?php

namespace Unleash\Client\Metrics;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\ProxyVariant;

/**
 * @internal
 */
final class MetricsBucketToggle
{
    public function __construct(
        private readonly Feature $feature,
        private readonly bool $success,
        private readonly ?ProxyVariant $variant = null,
    ) {
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getVariant(): ?ProxyVariant
    {
        return $this->variant;
    }
}
