<?php

namespace Unleash\Client\DTO;

final class DefaultFeature implements Feature
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var bool
     */
    private $enabled;
    /**
     * @var \Unleash\Client\DTO\Strategy[]
     */
    private $strategies;
    /**
     * @var \Unleash\Client\DTO\Variant[]
     */
    private $variants = [];
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
