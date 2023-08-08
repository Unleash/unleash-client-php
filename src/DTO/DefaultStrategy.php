<?php

namespace Unleash\Client\DTO;

final class DefaultStrategy implements Strategy
{
    /**
     * @param array<string,string> $parameters
     * @param array<Constraint>    $constraints
     * @param array<Segment>       $segments
     */
    public function __construct(
        private string $name,
        private array $parameters = [],
        private array $constraints = [],
        private array $segments = [],
        private bool $nonexistentSegments = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<Constraint>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @return array<Segment>
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    public function hasNonexistentSegments(): bool
    {
        return $this->nonexistentSegments;
    }
}
