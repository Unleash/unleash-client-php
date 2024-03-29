<?php

namespace Unleash\Client\Metrics;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;

/**
 * @internal
 */
final readonly class MetricsBucketToggle
{
    public function __construct(
        private Feature $feature,
        private bool $success,
        private ?Variant $variant = null,
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

    public function getVariant(): ?Variant
    {
        return $this->variant;
    }
}
