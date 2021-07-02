<?php

namespace Rikudou\Unleash\DTO;

final class DefaultFeature implements Feature
{
    /**
     * @param iterable<Strategy> $strategies
     */
    public function __construct(
        private string $name,
        private bool $enabled,
        private iterable $strategies
    ) {
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
}
