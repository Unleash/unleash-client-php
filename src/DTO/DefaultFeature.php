<?php

namespace Unleash\Client\DTO;

final class DefaultFeature implements Feature
{
    private string $name;
    private bool $enabled;
    /**
     * @var \Unleash\Client\DTO\Strategy[]
     */
    private iterable $strategies;
    /**
     * @var \Unleash\Client\DTO\Variant[]
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
