<?php

namespace Unleash\Client\DTO;

final class DefaultFeatureDependency implements FeatureDependency
{
    /**
     * @param array<Variant>|null $requiredVariants
     */
    public function __construct(
        private ?Feature $feature,
        private bool $expectedState,
        private ?array $requiredVariants,
    ) {
    }

    public function getFeature(): ?Feature
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
        return true;
    }
}
