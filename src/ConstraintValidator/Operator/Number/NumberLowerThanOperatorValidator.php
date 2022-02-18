<?php

namespace Unleash\Client\ConstraintValidator\Operator\Number;

/**
 * @internal
 */
final class NumberLowerThanOperatorValidator extends AbstractNumberOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return $this->convert($currentValue) < $this->convert($searchInValue);
    }
}