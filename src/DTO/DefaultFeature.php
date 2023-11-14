<?php

namespace Unleash\Client\DTO;

final class DefaultFeature implements Feature
{
    /**
     * @readonly
     */
    private string $name;
    /**
     * @readonly
     */
    private bool $enabled;
    /**
     * @var iterable<Strategy>
     * @readonly
     */
    private iterable $strategies;
    /**
     * @var array<Variant>
     * @readonly
     */
    private array $variants = [];
    /**
     * @readonly
     */
    private bool $impressionData = false;
    /**
     * @param iterable<Strategy> $strategies
     * @param array<Variant>     $variants
     */
    public function __construct(string $name, bool $enabled, iterable $strategies, array $variants = [], bool $impressionData = false)
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->strategies = $strategies;
        $this->variants = $variants;
        $this->impressionData = $impressionData;
    }
    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return iterable<Strategy>
     */
    public function getStrategies(): iterable
    {
        return $this->strategies;
    }

    /**
     * @return array<Variant>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    public function hasImpressionData(): bool
    {
        return $this->impressionData;
    }
}
