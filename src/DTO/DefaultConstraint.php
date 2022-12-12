<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Unleash\Client\Enum\ConstraintOperator;

final class DefaultConstraint implements Constraint
{
    /**
     * @param array<string> $values
     */
    public function __construct(
        private string $contextName,
        #[ExpectedValues(valuesFromClass: ConstraintOperator::class)]private string $operator,
        private ?array $values = null,
        private ?string $singleValue = null,
        private bool $inverted = false,
        private bool $caseInsensitive = false,
    ) {
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
     * @return array<string>|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    public function getSingleValue(): ?string
    {
        return $this->singleValue;
    }

    public function isInverted(): bool
    {
        return $this->inverted;
    }

    public function isCaseInsensitive(): bool
    {
        return $this->caseInsensitive;
    }
}
