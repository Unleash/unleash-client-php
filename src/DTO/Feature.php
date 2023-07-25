<?php

namespace Unleash\Client\DTO;

/**
 * @method bool hasImpressionData()
 */
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
}
