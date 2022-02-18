<?php

namespace Unleash\Client\ConstraintValidator\Operator\Date;

/**
 * @internal
 */
final class DateAfterOperatorValidator extends AbstractDateOperatorValidator
{
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return $this->convert($currentValue) > $this->convert($searchInValue);
    }
}
