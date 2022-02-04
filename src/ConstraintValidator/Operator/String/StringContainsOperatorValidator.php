<?php

namespace Unleash\Client\ConstraintValidator\Operator\String;

/**
 * @internal
 */
final class StringContainsOperatorValidator extends AbstractStringOperatorValidator
{
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return str_contains($currentValue, $searchInValue);
    }
}
