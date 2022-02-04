<?php

namespace Unleash\Client\ConstraintValidator\Operator\Number;

use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractNumberOperatorValidator extends AbstractOperatorValidator
{
    protected function acceptsValues(array|string $values): bool
    {
        return is_string($values) && is_numeric($values);
    }

    protected function convert(string $number): int|float
    {
        if (str_contains($number, '.')) {
            return (float) $number;
        }

        return (int) $number;
    }
}
