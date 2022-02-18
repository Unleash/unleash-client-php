<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Unleash\Client\Enum\ConstraintOperator;

/**
 * @todo move to required methods in next major
 *
 * @method string|null getSingleValue()
 * @method bool        isInverted()
 * @method bool        isCaseInsensitive()
 */
interface Constraint
{
    public function getContextName(): string;

    #[ExpectedValues(valuesFromClass: ConstraintOperator::class)]
    public function getOperator(): string;

    /**
     * @return array<string>|null
     */
    public function getValues(): ?array;
}
