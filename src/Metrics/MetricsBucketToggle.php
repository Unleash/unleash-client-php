<?php

namespace Rikudou\Unleash\Metrics;

use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\DTO\Variant;

/**
 * @internal
 */
final class MetricsBucketToggle
{
    public function __construct(
        private Feature $feature,
        private bool $success,
        private ?Variant $variant,
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
