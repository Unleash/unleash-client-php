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
     * @var \Unleash\Client\DTO\Strategy[]
     * @readonly
     */
    private iterable $strategies;
    /**
     * @var \Unleash\Client\DTO\Variant[]
     * @readonly
     */
    private array $variants = [];
    /**
     * @param iterable<Strategy> $strategies
     * @param array<Variant>     $variants
     */
    public function __construct(string $name, bool $enabled, iterable $strategies, array $variants = [])
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->strategies = $strategies;
        $this->variants = $variants;
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
}
