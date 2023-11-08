<?php

namespace Unleash\Client\DTO;

interface Feature
{
    public function getName(): string;

    public function isEnabled(): bool;

    /**
     * @return iterable<Strategy>
     */
    public function getStrategies(): iterable;

    /**
     * @return array<Variant>
     */
    public function getVariants(): array;

    public function hasImpressionData(): bool;

    /**
     * @return array<Dependency>
     */
    public function getDependencies(): array;
}
