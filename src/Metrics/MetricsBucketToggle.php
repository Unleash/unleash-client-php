<?php

namespace Rikudou\Unleash\Metrics;

use Rikudou\Unleash\DTO\Feature;

/**
 * @internal
 */
final class MetricsBucketToggle
{
    public function __construct(
        private Feature $feature,
        private bool $success,
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
}
