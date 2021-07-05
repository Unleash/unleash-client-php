<?php

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Rikudou\Unleash\Enum\ConstraintOperator;

interface Constraint
{
    public function getContextName(): string;

    #[ExpectedValues(valuesFromClass: ConstraintOperator::class)]
    public function getOperator(): string;

    /**
     * @return array<string>
     */
    public function getValues(): array;
}
