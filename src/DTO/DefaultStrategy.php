<?php

namespace Unleash\Client\DTO;

final class DefaultStrategy implements Strategy
{
    private string $name;
    /**
     * @var array<string, string>
     */
    private array $parameters = [];
    /**
     * @var \Unleash\Client\DTO\Constraint[]
     */
    private array $constraints = [];
    /**
     * @param array<string,string> $parameters
     * @param array<Constraint>    $constraints
     */
    public function __construct(string $name, array $parameters = [], array $constraints = [])
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->constraints = $constraints;
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
}
