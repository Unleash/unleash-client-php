<?php

namespace Unleash\Client\DTO\Internal;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\FeatureDependency;

/**
 * @internal
 */
final class UnresolvedFeature implements Feature
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function getStrategies(): iterable
    {
        return [];
    }

    public function getVariants(): array
    {
        return [];
    }

    public function hasImpressionData(): bool
    {
        return false;
    }

    /**
     * @return array<FeatureDependency>
     */
    public function getDependencies(): array
    {
        return [];
    }
}
