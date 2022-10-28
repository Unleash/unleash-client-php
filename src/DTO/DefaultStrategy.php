<?php

namespace Unleash\Client\DTO;

final class DefaultStrategy implements Strategy
{
    /**
     * @readonly
     */
    private string $name;
    /**
     * @var array<string, string>
     * @readonly
     */
    private array $parameters = [];
    /**
     * @var array<Constraint>
     * @readonly
     */
    private array $constraints = [];
    /**
     * @var array<Segment>
     * @readonly
     */
    private array $segments = [];
    /**
     * @readonly
     */
    private bool $nonexistentSegments = false;
    /**
     * @param array<string,string> $parameters
     * @param array<Constraint>    $constraints
     * @param array<Segment>       $segments
     */
    public function __construct(string $name, array $parameters = [], array $constraints = [], array $segments = [], bool $nonexistentSegments = false)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->constraints = $constraints;
        $this->segments = $segments;
        $this->nonexistentSegments = $nonexistentSegments;
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
