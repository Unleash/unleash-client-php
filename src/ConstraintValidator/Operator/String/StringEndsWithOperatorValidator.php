<?php

namespace Unleash\Client\ConstraintValidator\Operator\String;

/**
 * @internal
 */
final class StringEndsWithOperatorValidator extends AbstractStringOperatorValidator
{
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return str_ends_with($currentValue, $searchInValue);
    }
}
