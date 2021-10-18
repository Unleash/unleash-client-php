<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Unleash\Client\Enum\ConstraintOperator;

final class DefaultConstraint implements Constraint
{
    private string $contextName;
    private string $operator;
    /**
     * @var string[]
     */
    private array $values;
    /**
     * @param array<string> $values
     */
    public function __construct(
        string $contextName,
        #[\JetBrains\PhpStorm\ExpectedValues(valuesFromClass: \Unleash\Client\Enum\ConstraintOperator::class)]
        string $operator,
        array $values
    )
    {
        $this->contextName = $contextName;
        $this->operator = $operator;
        $this->values = $values;
    }
    public function getContextName(): string
    {
        return $this->contextName;
    }

    #[ExpectedValues(valuesFromClass: ConstraintOperator::class)]
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return array<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
