<?php

namespace Rikudou\Unleash\DTO;

final class DefaultStrategy implements Strategy
{
    /**
     * @param array<string,string> $parameters
     */
    public function __construct(
        private string $name,
        private array $parameters,
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
}
