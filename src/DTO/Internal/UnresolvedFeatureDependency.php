<?php

namespace Unleash\Client\DTO\Internal;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\FeatureDependency;
use Unleash\Client\DTO\Variant;

/**
 * @internal
 */
final class UnresolvedFeatureDependency implements FeatureDependency
{
    /**
     * @param array<Variant>|null $requiredVariants
     */
    public function __construct(
        private readonly Feature $feature,
        private readonly bool $expectedState,
        private readonly ?array $requiredVariants,
    ) {
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function getExpectedState(): bool
    {
        return $this->expectedState;
    }

    public function getRequiredVariants(): ?array
    {
        return $this->requiredVariants;
    }

    public function isResolved(): bool
    {
        return false;
    }
}
