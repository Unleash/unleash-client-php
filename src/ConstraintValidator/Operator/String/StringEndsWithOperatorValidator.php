<?php

namespace Unleash\Client\ConstraintValidator\Operator\String;

/**
 * @internal
 */
final class StringEndsWithOperatorValidator extends AbstractStringOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return substr_compare($searchInValue, $currentValue, -strlen($currentValue)) === 0;
    }
}
