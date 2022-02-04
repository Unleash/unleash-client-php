<?php

namespace Unleash\Client\ConstraintValidator\Operator\Number;

/**
 * @internal
 */
final class NumberLowerThanOrEqualsOperatorValidator extends AbstractNumberOperatorValidator
{
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return $this->convert($currentValue) <= $this->convert($searchInValue);
    }
}
